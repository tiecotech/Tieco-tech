<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectCategory;
use Illuminate\Database\Seeder;

/** Local only. Everything here is flagged is_demo = true and must never be shown as real work. */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $cat = ProjectCategory::firstOrCreate(['slug' => 'sample'], ['name' => ['en' => 'Sample', 'ar' => 'تجريبي']]);
        Project::firstOrCreate(['slug' => 'sample-project'], [
            'project_category_id' => $cat->id,
            'title' => ['en' => '[Sample] Placeholder project', 'ar' => '[تجريبي] مشروع افتراضي'],
            'summary' => ['en' => 'Demo data for layout testing only.', 'ar' => 'بيانات تجريبية لاختبار التصميم فقط.'],
            'is_demo' => true, 'is_featured' => false, 'is_visible' => false,
        ]);
    }
}
