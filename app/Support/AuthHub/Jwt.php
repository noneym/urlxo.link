<?php

namespace App\Support\AuthHub;

/** Minimal HS256 JWT, enough for hub-to-site tokens without another dependency. */
class Jwt
{
    public static function encode(array $claims, string $secret): string
    {
        $header = self::b64(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = self::b64(json_encode($claims, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $header.'.'.$payload.'.'.self::b64(hash_hmac('sha256', $header.'.'.$payload, $secret, true));
    }

    /** Returns the claims when the signature matches, null otherwise. Callers check exp/aud. */
    public static function decode(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;
        $expected = self::b64(hash_hmac('sha256', $header.'.'.$payload, $secret, true));
        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $claims = json_decode(self::unb64($payload), true);

        return is_array($claims) ? $claims : null;
    }

    /** Reads a JWT payload without checking the signature (only for tokens fetched directly from Google over TLS). */
    public static function peek(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        $claims = json_decode(self::unb64($parts[1]), true);

        return is_array($claims) ? $claims : null;
    }

    public static function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function unb64(string $data): string
    {
        return (string) base64_decode(strtr($data, '-_', '+/'), true);
    }
}
