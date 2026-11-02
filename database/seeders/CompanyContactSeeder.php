<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/** Company contact + currency. Re-runnable: php artisan db:seed --class=CompanyContactSeeder */
class CompanyContactSeeder extends Seeder
{
    public function run(): void
    {
        $contact = SiteSetting::get('contact', []) ?: [];
        SiteSetting::put('contact', array_merge($contact, [
            'phone' => '+963992355844',
            'whatsapp' => '+963992355844',
        ]), 'general');

        SiteSetting::put('currency', ['code' => 'USD', 'symbol' => '$', 'decimals' => 2, 'position' => 'before'], 'general');
    }
}
