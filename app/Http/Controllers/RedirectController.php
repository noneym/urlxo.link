<?php

namespace App\Http\Controllers;

use App\Jobs\RecordClick;
use App\Models\Link;
use App\Services\LinkService;
use App\Support\Qr;
use App\Support\UserAgent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RedirectController extends Controller
{
    public function __construct(private readonly LinkService $links) {}

    public function show(Request $request, string $code): Response
    {
        $link = $this->links->resolve($code);
        if (! $link) {
            return $this->page('link.missing', 404);
        }
        if ($problem = $this->unavailable($link)) {
            return $this->page('link.unavailable', 410, ['reason' => $problem]);
        }
        if ($link['password'] && ! $request->session()->get('unlocked.'.$link['id'])) {
            return $this->page('link.password', 200, ['code' => $code]);
        }

        return $this->go($request, $link);
    }

    public function unlock(Request $request, string $code): Response
    {
        $link = $this->links->resolve($code);
        if (! $link || ! $link['password']) {
            return redirect('/'.$code);
        }
        if ($problem = $this->unavailable($link)) {
            return $this->page('link.unavailable', 410, ['reason' => $problem]);
        }

        $key = 'unlock:'.$link['id'].'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return $this->page('link.password', 429, ['code' => $code, 'error' => 'Too many attempts. Try again in a minute.']);
        }

        if (! Hash::check((string) $request->input('password'), $link['password'])) {
            RateLimiter::hit($key, 60);

            return $this->page('link.password', 422, ['code' => $code, 'error' => 'That password is not right.']);
        }

        RateLimiter::clear($key);
        $request->session()->put('unlocked.'.$link['id'], true);

        return $this->go($request, $link);
    }

    public function qr(Request $request, string $code): Response
    {
        abort_unless($this->links->resolve($code), 404);

        $svg = Qr::svg(Link::shortUrlFor($code));

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400',
            'Content-Disposition' => $request->boolean('download') ? 'attachment; filename="urlxo-'.$code.'.svg"' : 'inline',
        ]);
    }

    /** @param array{id:int, url:string, password:?string, expires_at:?int, max_clicks:?int, is_active:bool} $link */
    private function unavailable(array $link): ?string
    {
        if (! $link['is_active']) {
            return 'disabled';
        }
        if ($link['expires_at'] !== null && $link['expires_at'] <= time()) {
            return 'expired';
        }
        // Click caps need a fresh count, so only these links pay for a DB read.
        if ($link['max_clicks'] !== null && Link::query()->whereKey($link['id'])->value('clicks_count') >= $link['max_clicks']) {
            return 'exhausted';
        }

        return null;
    }

    /** @param array{id:int, url:string} $link */
    private function go(Request $request, array $link): RedirectResponse
    {
        $ua = new UserAgent((string) $request->userAgent());

        if ($request->isMethod('GET') || $request->isMethod('POST')) {
            if (! $ua->isBot()) {
                RecordClick::dispatch($link['id'], now()->toDateTimeString(), [
                    'country' => $this->country($request),
                    'referrer_host' => $this->referrer($request),
                    'device' => $ua->device(),
                    'browser' => $ua->browser(),
                    'os' => $ua->os(),
                    // Rotates daily, so unique visitors can be counted without keeping IPs.
                    'visitor_hash' => hash('sha256', $request->ip().'|'.$request->userAgent().'|'.now()->toDateString().'|'.config('app.key')),
                ]);
            }
        }

        return redirect()->away($link['url'], 302)->withHeaders([
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    private function country(Request $request): ?string
    {
        $c = strtoupper((string) ($request->header('CF-IPCountry') ?? $request->header('X-Country-Code') ?? ''));

        return preg_match('/^[A-Z]{2}$/', $c) && ! in_array($c, ['XX', 'T1'], true) ? $c : null;
    }

    private function referrer(Request $request): ?string
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return null;
        }
        $host = preg_replace('/^www\./', '', strtolower($host));

        return $host === config('urlxo.short_domain') ? null : mb_substr($host, 0, 191);
    }

    /** @param array<string, mixed> $data */
    private function page(string $view, int $status, array $data = []): Response
    {
        return response()->view($view, $data, $status)->withHeaders(['X-Robots-Tag' => 'noindex', 'Cache-Control' => 'no-store']);
    }
}
