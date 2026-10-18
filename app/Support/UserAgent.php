<?php

namespace App\Support;

/**
 * Deliberately small user agent classifier. Good enough for click stats,
 * with no external database to keep up to date.
 */
class UserAgent
{
    private const BOT = '/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|telegram|discord|slack|embedly|curl|wget|python-requests|httpclient|go-http|headless|lighthouse|monitor|uptime/i';

    public function __construct(private readonly string $ua) {}

    public function isBot(): bool
    {
        return $this->ua === '' || (bool) preg_match(self::BOT, $this->ua);
    }

    public function device(): string
    {
        if ($this->isBot()) {
            return 'bot';
        }
        if (preg_match('/ipad|tablet|kindle|silk|android(?!.*mobile)/i', $this->ua)) {
            return 'tablet';
        }
        if (preg_match('/mobi|iphone|ipod|android|windows phone|blackberry|opera mini/i', $this->ua)) {
            return 'mobile';
        }

        return 'desktop';
    }

    public function browser(): ?string
    {
        $map = [
            'Edge' => '/edg(e|a|ios)?\//i',
            'Opera' => '/opr\/|opera/i',
            'Samsung' => '/samsungbrowser/i',
            'Yandex' => '/yabrowser/i',
            'Firefox' => '/firefox|fxios/i',
            'Chrome' => '/chrome|crios/i',
            'Safari' => '/safari/i',
        ];

        return $this->match($map);
    }

    public function os(): ?string
    {
        $map = [
            'iOS' => '/iphone|ipad|ipod/i',
            'Android' => '/android/i',
            'Windows' => '/windows/i',
            'ChromeOS' => '/cros/i',
            'macOS' => '/mac os x|macintosh/i',
            'Linux' => '/linux/i',
        ];

        return $this->match($map);
    }

    /** @param array<string, string> $map */
    private function match(array $map): ?string
    {
        foreach ($map as $name => $re) {
            if (preg_match($re, $this->ua)) {
                return $name;
            }
        }

        return $this->ua === '' ? null : 'Other';
    }
}
