<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/**
 * A site that signs users in through the hub. The secret signs the tokens
 * handed to consume_url; the site verifies them with the same secret.
 */
class AuthSite extends Model
{
    protected $fillable = ['slug', 'name', 'consume_url', 'secret', 'active'];

    protected $hidden = ['secret'];

    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public static function generateSecret(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
    }

    /** Validation rules shared by the artisan command and the registry API. */
    public static function rules(?self $site = null): array
    {
        $required = $site ? 'sometimes' : 'required';

        return [
            'slug' => [$required, 'string', 'max:64', 'regex:/^[a-z0-9][a-z0-9_-]*$/', Rule::unique('auth_sites', 'slug')->ignore($site?->id)],
            'name' => [$required, 'string', 'max:120'],
            'consume_url' => [$required, 'string', 'max:500', 'url', function ($attribute, $value, $fail) {
                if (! self::isAllowedConsumeUrl((string) $value)) {
                    $fail('The consume URL must use https (http is only allowed for localhost).');
                }
            }],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    public static function isAllowedConsumeUrl(string $url): bool
    {
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower($parts['host'] ?? '');

        if ($scheme === 'https') {
            return $host !== '';
        }

        return $scheme === 'http' && ($host === 'localhost' || $host === '127.0.0.1' || str_ends_with($host, '.localhost') || str_ends_with($host, '.test'));
    }

    public function toRegistryArray(bool $withSecret = false): array
    {
        return array_filter([
            'slug' => $this->slug,
            'name' => $this->name,
            'consume_url' => $this->consume_url,
            'active' => $this->active,
            'start_url' => 'https://'.config('authhub.domain').'/start?site='.$this->slug,
            'logins_count' => $this->logins_count,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'secret' => $withSecret ? $this->secret : null,
        ], fn ($v) => $v !== null);
    }
}
