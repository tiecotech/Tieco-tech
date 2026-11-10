<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SiteSettingsSeeder::class,
            CompanyContactSeeder::class,
            RequestStatusSeeder::class,
            CatalogSeeder::class,
            PricingSeeder::class,
            ServiceSeeder::class,
            PageSeeder::class,
            ProjectSeeder::class,
            AdminUserSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call(DemoSeeder::class);
        }
    }
}
