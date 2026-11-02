<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['web', 'web', 'Web design & development', 'تصميم وتطوير المواقع', 'Websites that load fast and are easy to update.', 'مواقع سريعة وسهلة التحديث.'],
            ['software', 'code', 'Software & applications', 'البرمجيات والأنظمة', 'Custom systems built around how you work.', 'أنظمة مخصصة على قياس طريقة عملك.'],
            ['mobile', 'mobile', 'Mobile apps', 'تطبيقات الجوال', 'Android and iOS apps from one brief.', 'تطبيقات أندرويد وiOS من طلب واحد.'],
            ['advertising', 'ads', 'Advertising & creative', 'الإعلان والإبداع', 'Campaigns with a clear message and a way to measure it.', 'حملات برسالة واضحة وقابلة للقياس.'],
            ['branding', 'brand', 'Graphic design & branding', 'التصميم والهوية البصرية', 'Logos and identities that hold together.', 'شعارات وهويات متماسكة.'],
            ['growth', 'growth', 'Business growth & digital transformation', 'تطوير الأعمال والتحول الرقمي', 'Move manual work onto systems that scale.', 'انقل العمل اليدوي إلى أنظمة تنمو معك.'],
            ['photo-video', 'camera', 'Photography & video', 'التصوير والفيديو', 'Visual content for your brand and products.', 'محتوى مرئي لعلامتك ومنتجاتك.'],
        ];
        // Summaries only: detailed pages (problems, deliverables, FAQs) are written by the company in the admin panel.
        foreach ($rows as $i => [$slug, $icon, $en, $ar, $sen, $sar]) {
            Service::updateOrCreate(['slug' => $slug], [
                'icon' => $icon, 'name' => ['en' => $en, 'ar' => $ar],
                'summary' => ['en' => $sen, 'ar' => $sar], 'is_visible' => true, 'sort_order' => $i,
            ]);
        }
    }
}
