<?php

namespace App\Support;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class Locale
{
    /** Props shared with every Inertia page under /{locale}. */
    public static function props(Request $request, string $locale): array
    {
        $other = $locale === 'ar' ? 'en' : 'ar';
        $segments = $request->segments();
        array_shift($segments);
        $path = '/'.$other.($segments ? '/'.implode('/', $segments) : '');
        $qs = $request->getQueryString();

        $company = SiteSetting::get('company', []);
        $builder = trans('builder');
        $site = trans('site');

        return [
            'locale' => $locale,
            'dir' => $locale === 'ar' ? 'rtl' : 'ltr',
            'canonical' => $request->url(),
            'ui' => array_merge((array) trans('ui'), is_array($builder) ? $builder : [], is_array($site) ? $site : []),
            'alternate' => ['locale' => $other, 'url' => url($path).($qs ? '?'.$qs : ''), 'label' => $other === 'ar' ? 'العربية' : 'English'],
            // Legal pages appear in the footer only once published.
            'legal' => Page::whereIn('slug', ['privacy', 'terms', 'requests-policy'])->where('is_published', true)->get()
                ->map(fn (Page $p) => ['slug' => $p->slug, 'title' => $p->tr('title')])->values()->all(),
            'site' => [
                'name' => $company['name'][$locale] ?? 'Tieco Tech',
                'contact' => SiteSetting::get('contact', []),
            ],
        ];
    }
}
