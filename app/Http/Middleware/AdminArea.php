<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/** Staff-only area: active account, locale from the user's preference, admin props for Inertia. */
class AdminArea
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user && $user->is_active, 403);

        $locale = in_array($user->locale, config('tieco.locales'), true) ? $user->locale : 'en';
        app()->setLocale($locale);

        $role = $user->role;
        $permissions = $role ? $role->permissions() : [];

        Inertia::share([
            'locale' => $locale,
            'dir' => $locale === 'ar' ? 'rtl' : 'ltr',
            'ui' => array_merge((array) trans('admin'), (array) trans('admin_more')),
            'currency' => SiteSetting::get('currency') ?? ['code' => 'USD', 'symbol' => '$', 'decimals' => 2, 'position' => 'before'],
            'admin' => fn () => [
                'user' => ['name' => $user->name, 'email' => $user->email, 'role' => $role?->value],
                'can' => $permissions,
                'unread' => $user->unreadNotifications()->count(),
                'notifications' => $user->unreadNotifications()->latest()->limit(6)->get()->map(fn ($n) => [
                    'id' => $n->id, 'data' => $n->data, 'at' => $n->created_at?->diffForHumans(),
                ])->values(),
            ],
        ]);

        return $next($request);
    }
}
