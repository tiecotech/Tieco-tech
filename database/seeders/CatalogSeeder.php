<?php

namespace Database\Seeders;

use App\Models\ProjectType;
use App\Models\ServiceOption;
use Illuminate\Database\Seeder;

/**
 * Builder catalog structure only. Every price is NULL ("not configured") on purpose:
 * the company sets real prices in the admin panel. Page limits below are neutral defaults.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            // key, kind, en, ar, supports_pages
            ['personal_site', 'website', 'Personal website', 'موقع شخصي', true],
            ['company_site', 'website', 'Company website', 'موقع شركة', true],
            ['service_site', 'website', 'Services website', 'موقع خدمات', true],
            ['online_store', 'website', 'Online store', 'متجر إلكتروني', true],
            ['blog', 'website', 'Blog or articles site', 'مدونة أو موقع مقالات', true],
            ['booking_system', 'system', 'Booking system', 'نظام حجز', false],
            ['custom_software', 'system', 'Custom software', 'نظام برمجي مخصص', false],
            ['mobile_app', 'mobile', 'Mobile app', 'تطبيق هاتف', false],
            ['branding', 'creative', 'Design & visual identity', 'تصميم وهوية بصرية', false],
            ['ads_marketing', 'creative', 'Ads & marketing', 'إعلانات وتسويق', false],
            ['photo_video', 'creative', 'Photography & content production', 'تصوير وإنتاج محتوى', false],
            ['bundle', 'bundle', 'Multiple services', 'مشروع يجمع أكثر من خدمة', true],
        ];
        $typeModels = [];
        foreach ($types as $i => [$key, $kind, $en, $ar, $pages]) {
            $typeModels[$key] = ProjectType::updateOrCreate(['key' => $key], [
                'kind' => $kind, 'name' => ['en' => $en, 'ar' => $ar], 'supports_pages' => $pages,
                'min_pages' => 1, 'max_pages' => $pages ? 50 : 1, 'included_pages' => 1,
                'is_active' => true, 'sort_order' => $i,
            ]);
        }

        // group, code, en, ar, exclusive_group, pricing_mode, compatible kinds
        $web = ['website', 'bundle'];
        $options = [
            ['addon', 'cms', 'Content management panel', 'لوحة إدارة المحتوى', null, 'fixed', $web],
            ['addon', 'articles', 'Articles system', 'نظام المقالات', null, 'fixed', $web],
            ['addon', 'gallery', 'Image gallery', 'معرض الصور', null, 'fixed', $web],
            ['addon', 'advanced_contact', 'Advanced contact form', 'نموذج تواصل متقدم', null, 'fixed', $web],
            ['addon', 'booking', 'Booking system', 'نظام الحجز', null, 'fixed', $web],
            ['addon', 'payments', 'Online payments', 'الدفع الإلكتروني', null, 'fixed', $web],
            ['addon', 'user_accounts', 'User registration', 'تسجيل المستخدمين', null, 'fixed', $web],
            ['addon', 'custom_dashboard', 'Custom dashboard', 'لوحة تحكم مخصصة', null, 'fixed', $web],
            ['addon', 'integrations', 'Third-party integrations', 'الربط مع خدمات خارجية', null, 'fixed', $web],
            ['addon', 'multilingual', 'Multiple languages', 'تعدد اللغات', null, 'fixed', $web],
            ['addon', 'seo', 'Search engine optimization', 'تحسين محركات البحث', null, 'fixed', $web],
            ['platform', 'android', 'Android', 'أندرويد', 'platform', 'fixed', ['mobile', 'bundle']],
            ['platform', 'ios', 'iOS', 'iOS', 'platform', 'fixed', ['mobile', 'bundle']],
            ['platform', 'android_ios', 'Android and iOS', 'أندرويد وiOS معًا', 'platform', 'fixed', ['mobile', 'bundle']],
            ['creative', 'logo_identity', 'Logo & visual identity', 'تصميم الشعار والهوية البصرية', null, 'fixed', ['creative', 'bundle']],
            ['creative', 'graphic_design', 'Graphic design', 'التصميم الجرافيكي', null, 'fixed', ['creative', 'bundle']],
            ['creative', 'ad_design', 'Ad design', 'تصميم الإعلانات', null, 'fixed', ['creative', 'bundle']],
            ['creative', 'campaign_management', 'Ad campaign management', 'إدارة الحملات الإعلانية', null, 'fixed', ['creative', 'bundle']],
            ['creative', 'photography', 'Professional photography', 'التصوير الاحترافي', null, 'fixed', ['creative', 'bundle']],
            ['creative', 'video_production', 'Video content production', 'إنتاج المحتوى المرئي', null, 'fixed', ['creative', 'bundle']],
        ];
        foreach ($options as $i => [$group, $code, $en, $ar, $excl, $mode, $kinds]) {
            $opt = ServiceOption::updateOrCreate(['code' => $code], [
                'group' => $group, 'exclusive_group' => $excl, 'pricing_mode' => $mode,
                'name' => ['en' => $en, 'ar' => $ar], 'is_active' => true, 'sort_order' => $i,
            ]);
            $ids = collect($typeModels)->filter(fn ($t) => in_array($t->kind, $kinds, true))->pluck('id');
            $opt->projectTypes()->syncWithoutDetaching($ids);
        }
    }
}
