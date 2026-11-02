<?php

namespace App\Concerns;

/** Reads a {"en": "...", "ar": "..."} JSON attribute in the active locale with fallback. */
trait Translatable
{
    public function tr(string $attribute, ?string $locale = null): mixed
    {
        $value = $this->{$attribute};
        if (! is_array($value)) {
            return $value;
        }
        $locale ??= app()->getLocale();

        return $value[$locale]
            ?? $value[config('tieco.default_locale')]
            ?? (reset($value) ?: null);
    }
}
