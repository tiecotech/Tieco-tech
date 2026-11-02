<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/** Staff accounts. Nobody can lock themselves out or remove the last administrator. */
class UserController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('admin/users', [
            'users' => User::orderBy('name')->get()->map(fn (User $u) => [
                'id' => $u->id, 'name' => $u->name, 'email' => $u->email,
                'role' => $u->role instanceof Role ? $u->role->value : (string) $u->role,
                'is_active' => (bool) $u->is_active, 'locale' => $u->locale, 'is_me' => $u->id === $request->user()->id,
            ])->values(),
            'roles' => array_map(fn (Role $r) => $r->value, Role::cases()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', 'unique:users,email'],
            'role' => ['required', Rule::enum(Role::class)],
            'locale' => ['required', 'in:'.implode(',', config('tieco.locales'))],
            'password' => ['required', Password::min(12)->letters()->numbers()],
        ]);

        $user = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password'])]);
        $user->forceFill(['role' => $data['role'], 'locale' => $data['locale'], 'is_active' => true, 'email_verified_at' => now()])->save();
        ActivityLog::record('user.created', $user, ['role' => $data['role']]); // never log the password

        return back();
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['required', Rule::enum(Role::class)],
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', Password::min(12)->letters()->numbers()],
        ]);

        $isMe = $user->id === $request->user()->id;
        $stillAdmin = $data['role'] === Role::Admin->value && $data['is_active'];
        if ($isMe && (! $stillAdmin && $user->isAdmin())) {
            return back()->withErrors(['role' => __('admin_more.a.users.self_lock')]);
        }
        if ($user->isAdmin() && ! $stillAdmin && $this->activeAdmins() <= 1) {
            return back()->withErrors(['role' => __('admin_more.a.users.last_admin')]);
        }

        $before = ['role' => $user->role?->value, 'is_active' => (bool) $user->is_active];
        $user->name = $data['name'];
        $user->forceFill(['role' => $data['role'], 'is_active' => (bool) $data['is_active']]);
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        ActivityLog::record('user.updated', $user, [
            'old' => $before, 'new' => ['role' => $data['role'], 'is_active' => (bool) $data['is_active']],
            'password_changed' => ! empty($data['password']),
        ]);

        return back();
    }

    private function activeAdmins(): int
    {
        return User::where('is_active', true)->where('role', Role::Admin->value)->count();
    }
}
