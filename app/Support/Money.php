<?php

namespace App\Support;

use InvalidArgumentException;

/** Decimal text <-> integer minor units, without floats. Also used for percentages (12.5 -> 1250 basis points). */
final class Money
{
    public const PATTERN = '/^\d{1,9}([.,]\d{1,2})?$/';

    public static function toMinor(?string $value): ?int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (! preg_match('/^(\d{1,9})(?:[.,](\d{1,2}))?$/', $value, $m)) {
            throw new InvalidArgumentException("Invalid amount: {$value}");
        }

        return ((int) $m[1]) * 100 + (int) str_pad($m[2] ?? '', 2, '0');
    }

    public static function toDecimal(?int $minor): string
    {
        return $minor === null ? '' : sprintf('%d.%02d', intdiv($minor, 100), $minor % 100);
    }
}
