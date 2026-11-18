<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;

/** "Support our journey" details (bank account, Sham Cash), edited in the admin panel, never hard-coded. */
class SupportInfo
{
    public static function raw(): array
    {
        $s = (array) SiteSetting::get('support', []);

        return [
            'enabled' => (bool) ($s['enabled'] ?? false),
            'bank' => array_merge(['enabled' => false, 'bank_name' => '', 'holder' => '', 'account' => '', 'branch' => ''], (array) ($s['bank'] ?? [])),
            'shamcash' => array_merge(['enabled' => false, 'holder' => '', 'account' => '', 'qr' => null], (array) ($s['shamcash'] ?? [])),
        ];
    }

    /** What the public page may show: only switched-on methods with real details. Null when the page is off. */
    public static function public(): ?array
    {
        $s = static::raw();
        $bank = $s['bank']['enabled'] && filled($s['bank']['account'])
            ? array_intersect_key($s['bank'], array_flip(['bank_name', 'holder', 'account', 'branch'])) : null;
        $sham = $s['shamcash']['enabled'] && (filled($s['shamcash']['account']) || filled($s['shamcash']['qr']))
            ? ['holder' => $s['shamcash']['holder'], 'account' => $s['shamcash']['account'], 'qr' => static::qrUrl($s['shamcash']['qr'])] : null;

        return $s['enabled'] && ($bank || $sham) ? ['bank' => $bank, 'shamcash' => $sham] : null;
    }

    public static function isLive(): bool
    {
        return static::public() !== null;
    }

    public static function qrUrl(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }
}
