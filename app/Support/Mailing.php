<?php

namespace App\Support;

class Mailing
{
    /** True only when a real mail transport is configured (not log/array). */
    public static function isConfigured(): bool
    {
        return ! in_array(config('mail.default'), ['log', 'array'], true);
    }
}
