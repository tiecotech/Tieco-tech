<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Pre-launch gate. Until an admin marks the site live, visitors see the coming-soon page;
 * signed-in staff and holders of the private preview link see the real site.
 */
class Launch
{
    public const COOKIE = 'tieco_preview';

    public const DEFAULT_DATE = '2026-11-11';

    /** @return array{live: bool, date: ?string, token: ?string} */
    public static function settings(): array
    {
        $s = (array) SiteSetting::get('launch', []);

        return [
            'live' => (bool) ($s['live'] ?? false),
            'date' => array_key_exists('date', $s) ? $s['date'] : self::DEFAULT_DATE,
            'token' => $s['token'] ?? null,
        ];
    }

    public static function isLive(): bool
    {
        return (bool) config('tieco.force_live') || self::settings()['live'];
    }

    /** The private preview token, created on first use. */
    public static function token(): string
    {
        $s = self::settings();
        if (! $s['token']) {
            $s['token'] = Str::random(40);
            SiteSetting::put('launch', $s);
        }

        return $s['token'];
    }

    public static function rotateToken(): string
    {
        SiteSetting::put('launch', ['token' => Str::random(40)] + self::settings());

        return self::settings()['token'];
    }

    public static function previewUrl(): string
    {
        return url('/preview/'.self::token());
    }

    public static function canPreview(Request $request): bool
    {
        if ($request->user()) {
            return true;
        }
        $cookie = $request->cookie(self::COOKIE);
        $token = self::settings()['token'];

        return is_string($cookie) && $token && hash_equals($token, $cookie);
    }
}
