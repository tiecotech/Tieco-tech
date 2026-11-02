<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('tieco.admin.email');
        $password = config('tieco.admin.password');

        if (! $email || ! $password) {
            $this->command?->warn('ADMIN_EMAIL / ADMIN_PASSWORD not set: no admin created. Set them in .env and re-run this seeder.');

            return;
        }
        if (strlen($password) < 12) {
            $this->command?->error('ADMIN_PASSWORD must be at least 12 characters.');

            return;
        }

        $user = User::firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => config('tieco.admin.name'),
            'password' => Hash::make($password),
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ])->save();
    }
}
