<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Placeholders only. The company fills the real values from the admin panel.
        $defaults = [
            'currency' => ['code' => 'USD', 'symbol' => '$', 'decimals' => 2, 'position' => 'before'],
            'company' => ['name' => ['en' => 'Tieco Tech', 'ar' => 'تايكو تيك'], 'tagline' => ['en' => 'Digital Solutions', 'ar' => 'حلول رقمية']],
            'contact' => ['email' => null, 'phone' => null, 'whatsapp' => null, 'address' => null],
            'social' => [],
            'nav' => [
                ['key' => 'services', 'visible' => true], ['key' => 'work', 'visible' => true],
                ['key' => 'about', 'visible' => true], ['key' => 'team', 'visible' => true], ['key' => 'contact', 'visible' => true],
            ],
        ];
        foreach ($defaults as $key => $value) {
            SiteSetting::firstOrCreate(['key' => $key], ['value' => $value, 'group' => 'general']);
        }
    }
}
