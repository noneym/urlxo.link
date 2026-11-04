<?php

namespace App\Http\Controllers\AuthHub;

use App\Http\Controllers\Controller;
use App\Models\AuthSite;
use App\Support\AuthHub\Jwt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Google sign-in on behalf of registered sites.
 *
 * Site:  GET /start?site=<slug>&nonce=<random, kept in the site session>&return=/path
 * Hub:   signs {site, return, nonce, browser} into `state`, sends the user to Google
 * Hub:   GET /callback swaps the code for the ID token, then redirects to the site's
 *        consume_url with ?token=<HS256 JWT signed with the site's secret>
 */
class HubController extends Controller
{
    private const BROWSER_COOKIE = 'hub_b';

    public function home()
    {
        return view('authhub.home');
    }

    public function missing()
    {
        return $this->fail('There is nothing at this address.', 404);
    }

    public function start(Request $request)
    {
        $slug = (string) $request->query('site', '');
        $nonce = (string) $request->query('nonce', '');
        $return = (string) $request->query('return', '/');

        $site = $slug !== '' ? AuthSite::where('slug', $slug)->where('active', true)->first() : null;
        if (! $site) {
            return $this->fail('This site is not registered for sign-in.', 404);
        }
        if (! preg_match('/^[A-Za-z0-9_-]{16,128}$/', $nonce)) {
            return $this->fail('The sign-in request is missing a valid nonce.', 400);
        }
        if (! $this->isLocalPath($return)) {
            $return = '/';
        }
        if (! config('authhub.google.client_id') || ! config('authhub.google.client_secret')) {
            return $this->fail('Sign-in is not configured yet.', 503);
        }

        $browser = Str::random(40);
        $state = $this->sign([
            's' => $site->slug,
            'r' => $return,
            'n' => $nonce,
            'b' => hash('sha256', $browser),
            'e' => time() + config('authhub.state_ttl'),
        ]);

        $url = 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('authhub.google.client_id'),
            'redirect_uri' => config('authhub.google.redirect'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
            'include_granted_scopes' => 'true',
        ]);

        return redirect()->away($url)->withCookie(
            Cookie::make(self::BROWSER_COOKIE, $browser, (int) ceil(config('authhub.state_ttl') / 60), '/', null, $request->isSecure(), true, false, 'lax')
        );
    }

    public function callback(Request $request)
    {
        $state = $this->verify((string) $request->query('state', ''));
        if (! $state || ($state['e'] ?? 0) < time()) {
            return $this->fail('This sign-in link has expired. Go back to the site and try again.', 400);
        }

        $browser = (string) $request->cookie(self::BROWSER_COOKIE, '');
        if ($browser === '' || ! hash_equals($state['b'], hash('sha256', $browser))) {
            return $this->fail('This sign-in was started in a different browser. Go back to the site and try again.', 400);
        }

        $site = AuthSite::where('slug', $state['s'])->where('active', true)->first();
        if (! $site) {
            return $this->fail('This site is not registered for sign-in.', 404);
        }

        $forget = Cookie::forget(self::BROWSER_COOKIE);

        if ($request->filled('error')) {
            return redirect()->away($this->withQuery($site->consume_url, [
                'error' => $request->query('error') === 'access_denied' ? 'access_denied' : 'google_error',
                'return' => $state['r'],
            ]))->withCookie($forget);
        }

        $profile = $this->exchange((string) $request->query('code', ''));
        if (! $profile) {
            return $this->fail('Google did not confirm the sign-in. Please try again.', 502);
        }

        $now = time();
        $token = Jwt::encode([
            'iss' => 'https://'.config('authhub.domain'),
            'aud' => $site->slug,
            'sub' => (string) $profile['sub'],
            'email' => $profile['email'] ?? null,
            'email_verified' => (bool) ($profile['email_verified'] ?? false),
            'name' => $profile['name'] ?? null,
            'given_name' => $profile['given_name'] ?? null,
            'family_name' => $profile['family_name'] ?? null,
            'picture' => $profile['picture'] ?? null,
            'locale' => $profile['locale'] ?? null,
            'nonce' => $state['n'],
            'return' => $state['r'],
            'iat' => $now,
            'exp' => $now + config('authhub.token_ttl'),
            'jti' => (string) Str::uuid(),
        ], $site->secret);

        $site->forceFill(['last_login_at' => now()])->save();
        $site->increment('logins_count');

        return redirect()->away($this->withQuery($site->consume_url, ['token' => $token]))->withCookie($forget);
    }

    /** Swaps the authorization code for Google's ID token claims. */
    private function exchange(string $code): ?array
    {
        if ($code === '') {
            return null;
        }

        $response = Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => config('authhub.google.client_id'),
            'client_secret' => config('authhub.google.client_secret'),
            'redirect_uri' => config('authhub.google.redirect'),
            'grant_type' => 'authorization_code',
        ]);

        if (! $response->successful() || ! $response->json('id_token')) {
            Log::warning('authhub: token exchange failed', ['status' => $response->status(), 'error' => $response->json('error')]);

            return null;
        }

        // Received straight from Google's token endpoint over TLS, so the claims
        // can be trusted without checking the signature (OIDC Core 3.1.3.7).
        $claims = Jwt::peek($response->json('id_token'));
        if (! $claims
            || ! in_array($claims['iss'] ?? '', ['https://accounts.google.com', 'accounts.google.com'], true)
            || ($claims['aud'] ?? '') !== config('authhub.google.client_id')
            || ($claims['exp'] ?? 0) < time()
            || empty($claims['sub'])) {
            Log::warning('authhub: unexpected id_token claims');

            return null;
        }

        return $claims;
    }

    private function sign(array $data): string
    {
        $payload = Jwt::b64(json_encode($data, JSON_UNESCAPED_SLASHES));

        return $payload.'.'.Jwt::b64(hash_hmac('sha256', $payload, $this->stateKey(), true));
    }

    private function verify(string $state): ?array
    {
        $parts = explode('.', $state);
        if (count($parts) !== 2 || ! hash_equals(Jwt::b64(hash_hmac('sha256', $parts[0], $this->stateKey(), true)), $parts[1])) {
            return null;
        }
        $data = json_decode(Jwt::unb64($parts[0]), true);

        return is_array($data) && isset($data['s'], $data['r'], $data['n'], $data['b'], $data['e']) ? $data : null;
    }

    private function stateKey(): string
    {
        return 'authhub-state|'.config('app.key');
    }

    private function isLocalPath(string $path): bool
    {
        return strlen($path) <= 2000
            && str_starts_with($path, '/')
            && ! str_starts_with($path, '//')
            && ! str_contains($path, '\\')
            && ! preg_match('/[\x00-\x1F\x7F]/', $path);
    }

    private function withQuery(string $url, array $query): string
    {
        return $url.(str_contains($url, '?') ? '&' : '?').http_build_query($query);
    }

    private function fail(string $message, int $status)
    {
        return response()->view('authhub.error', ['message' => $message], $status);
    }
}
