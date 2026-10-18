<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Link extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'code', 'url', 'title', 'password', 'expires_at', 'max_clicks', 'is_active', 'creator_ip_hash',
    ];

    protected $hidden = ['password', 'creator_ip_hash'];

    // Mirrors the column defaults so a freshly created model serializes correctly.
    protected $attributes = [
        'clicks_count' => 0,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'expires_at' => 'datetime',
            'last_clicked_at' => 'datetime',
            'is_active' => 'boolean',
            'max_clicks' => 'integer',
            'clicks_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(Click::class);
    }

    public function shortUrl(): string
    {
        return self::shortUrlFor($this->code);
    }

    public static function shortUrlFor(string $code): string
    {
        return 'https://'.config('urlxo.short_domain').'/'.$code;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isExhausted(): bool
    {
        return $this->max_clicks !== null && $this->clicks_count >= $this->max_clicks;
    }

    public function destinationHost(): string
    {
        return (string) parse_url($this->url, PHP_URL_HOST);
    }

    /** @return array<string, mixed> */
    public function toApi(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'short_url' => $this->shortUrl(),
            'url' => $this->url,
            'title' => $this->title,
            'has_password' => $this->password !== null,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'max_clicks' => $this->max_clicks,
            'clicks' => $this->clicks_count,
            'is_active' => $this->is_active,
            'last_clicked_at' => $this->last_clicked_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
