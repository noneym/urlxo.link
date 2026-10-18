<?php

namespace App\Services;

use App\Models\Link;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LinkService
{
    // No 0/O, 1/l/I so codes survive being read aloud or typed from print.
    private const ALPHABET = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Validation rules shared by the dashboard, the landing form and the API.
     *
     * @return array<string, mixed>
     */
    public static function rules(bool $updating = false): array
    {
        return [
            'url' => [$updating ? 'sometimes' : 'required', 'string', 'max:2048'],
            'code' => ['nullable', 'string', 'min:3', 'max:32', 'regex:/^[A-Za-z0-9_-]+$/'],
            'title' => ['nullable', 'string', 'max:191'],
            'password' => ['nullable', 'string', 'min:4', 'max:100'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'max_clicks' => ['nullable', 'integer', 'min:1', 'max:100000000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /** @param array<string, mixed> $data */
    public function create(array $data, ?User $user, ?string $ip = null): Link
    {
        $url = $this->normalizeUrl((string) $data['url']);
        $code = filled($data['code'] ?? null) ? $this->assertCodeAvailable((string) $data['code']) : $this->generateCode();

        return Link::create([
            'user_id' => $user?->id,
            'code' => $code,
            'url' => $url,
            'title' => $data['title'] ?? null,
            'password' => filled($data['password'] ?? null) ? $data['password'] : null,
            'expires_at' => $data['expires_at'] ?? null,
            'max_clicks' => $data['max_clicks'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'creator_ip_hash' => $ip ? self::hashIp($ip) : null,
        ]);
    }

    /**
     * Only keys present in $data are touched. A null/empty password removes it,
     * a missing password key keeps the current one.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Link $link, array $data): Link
    {
        $oldCode = $link->code;

        if (array_key_exists('url', $data)) {
            $link->url = $this->normalizeUrl((string) $data['url']);
        }
        if (filled($data['code'] ?? null) && $data['code'] !== $link->code) {
            $link->code = $this->assertCodeAvailable((string) $data['code']);
        }
        if (array_key_exists('password', $data)) {
            $link->password = filled($data['password']) ? $data['password'] : null;
        }
        $link->fill(Arr::only($data, ['title', 'expires_at', 'max_clicks', 'is_active']));
        $link->save();

        $this->forget($oldCode);
        $this->forget($link->code);

        return $link;
    }

    public function delete(Link $link): void
    {
        $this->forget($link->code);
        $link->delete();
    }

    /**
     * The minimum the redirect path needs, cached so a hit costs one Redis GET.
     *
     * @return array{id:int, url:string, password:?string, expires_at:?int, max_clicks:?int, is_active:bool}|null
     */
    public function resolve(string $code): ?array
    {
        $payload = Cache::remember($this->cacheKey($code), config('urlxo.cache_ttl'), function () use ($code) {
            $link = Link::query()->where('code', $code)->first();

            return $link ? [
                'id' => $link->id,
                'url' => $link->url,
                'password' => $link->getRawOriginal('password'),
                'expires_at' => $link->expires_at?->getTimestamp(),
                'max_clicks' => $link->max_clicks,
                'is_active' => $link->is_active,
            ] : false;
        });

        return $payload ?: null;
    }

    public function forget(string $code): void
    {
        Cache::forget($this->cacheKey($code));
    }

    public function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url !== '' && ! preg_match('~^[a-z][a-z0-9+.-]*://~i', $url)) {
            $url = 'https://'.$url;
        }

        $parts = parse_url($url) ?: [];
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower($parts['host'] ?? '');

        if (! in_array($scheme, ['http', 'https'], true) || $host === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            $this->fail('url', 'Enter a valid http or https URL.');
        }
        if (! str_contains($host, '.') && ! filter_var($host, FILTER_VALIDATE_IP)) {
            $this->fail('url', 'Enter a URL with a full domain name.');
        }

        $blocked = array_merge(
            [config('urlxo.short_domain'), (string) parse_url((string) config('app.url'), PHP_URL_HOST)],
            config('urlxo.blocked_hosts'),
        );
        foreach (array_filter($blocked) as $b) {
            $b = strtolower($b);
            if ($host === $b || str_ends_with($host, '.'.$b)) {
                $this->fail('url', 'Links to this domain cannot be shortened.');
            }
        }

        return $url;
    }

    public function assertCodeAvailable(string $code): string
    {
        if (! preg_match('/^[A-Za-z0-9_-]{3,32}$/', $code)) {
            $this->fail('code', 'Use 3 to 32 letters, numbers, dashes or underscores.');
        }
        if (in_array(Str::lower($code), config('urlxo.reserved'), true)) {
            $this->fail('code', 'This alias is reserved.');
        }
        if (Link::query()->where('code', $code)->exists()) {
            $this->fail('code', 'This alias is already taken.');
        }

        return $code;
    }

    public function generateCode(): string
    {
        $length = max(4, (int) config('urlxo.code_length'));

        for ($attempt = 0; $attempt < 9; $attempt++) {
            // Grow the code after repeated collisions instead of looping forever.
            $len = $length + intdiv($attempt, 3);
            $code = '';
            for ($i = 0; $i < $len; $i++) {
                $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
            if (! in_array(Str::lower($code), config('urlxo.reserved'), true) && ! Link::query()->where('code', $code)->exists()) {
                return $code;
            }
        }

        throw new \RuntimeException('Could not generate a unique code.');
    }

    public static function hashIp(string $ip): string
    {
        return hash('sha256', $ip.'|'.config('app.key'));
    }

    private function cacheKey(string $code): string
    {
        // md5 keeps case-different codes apart even on case-insensitive stores.
        return 'link:'.md5($code);
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
