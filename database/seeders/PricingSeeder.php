<?php

namespace Database\Seeders;

use App\Models\DiscountRule;
use App\Models\ProjectType;
use App\Models\ServiceOption;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * APPROXIMATE PLACEHOLDER PRICES (USD, minor units = cents).
 * They exist so the calculator can be demonstrated. They are NOT Tieco Tech's real price list.
 * Edit everything from the admin panel (Batch 4). `pricing_is_placeholder` drives a visible
 * "approximate estimate" notice in the builder; switch it off once real prices are in.
 */
class PricingSeeder extends Seeder
{
    public function run(): void
    {
        // key => [base, included_pages, extra_page_price, max_pages]
        $types = [
            'personal_site' => [30000, 3, 2000, 10],
            'company_site' => [80000, 5, 3500, 30],
            'service_site' => [60000, 5, 3000, 20],
            'online_store' => [200000, 5, 4000, 50],
            'blog' => [50000, 5, 2500, 30],
            'booking_system' => [150000, null, null, null],
            'custom_software' => [300000, null, null, null],
            'mobile_app' => [100000, null, null, null],
            'branding' => [30000, null, null, null],
            'ads_marketing' => [20000, null, null, null],
            'photo_video' => [25000, null, null, null],
            'bundle' => [10000, 5, 3500, 50],
        ];
        foreach ($types as $key => [$base, $included, $extra, $max]) {
            $data = ['base_price_minor' => $base];
            if ($included !== null) {
                $data += ['included_pages' => $included, 'extra_page_price_minor' => $extra, 'max_pages' => $max];
            }
            ProjectType::where('key', $key)->update($data);
        }

        // code => [amount minor units, max_quantity]
        $options = [
            'cms' => [25000, 1], 'articles' => [15000, 1], 'gallery' => [8000, 1], 'advanced_contact' => [5000, 1],
            'booking' => [30000, 1], 'payments' => [35000, 1], 'user_accounts' => [25000, 1], 'custom_dashboard' => [60000, 1],
            'integrations' => [20000, 5], 'multilingual' => [20000, 1], 'seo' => [12000, 1],
            'android' => [150000, 1], 'ios' => [170000, 1], 'android_ios' => [280000, 1],
            'logo_identity' => [30000, 1], 'graphic_design' => [10000, 10], 'ad_design' => [8000, 10],
            'campaign_management' => [20000, 1], 'photography' => [25000, 1], 'video_production' => [40000, 1],
        ];
        foreach ($options as $code => [$amount, $max]) {
            ServiceOption::where('code', $code)->update(['amount' => $amount, 'max_quantity' => $max]);
        }

        // Discount policies are the company's decision: seeded OFF with example values.
        DiscountRule::updateOrCreate(['code' => 'whatsapp_contact'], [
            'name' => ['en' => 'WhatsApp contact', 'ar' => 'التواصل عبر واتساب'],
            'description' => ['en' => 'Valid international number with WhatsApp confirmed.', 'ar' => 'رقم دولي صالح مع تأكيد استخدام واتساب.'],
            'type' => 'percent_bp', 'value' => 500, 'condition_type' => 'whatsapp_phone',
            'max_discount_minor' => 20000, 'stackable' => true, 'priority' => 10, 'is_active' => false,
        ]);
        DiscountRule::updateOrCreate(['code' => 'requirements_file'], [
            'name' => ['en' => 'Requirements file attached', 'ar' => 'إرفاق ملف المتطلبات'],
            'description' => ['en' => 'You attached a brief or requirements document.', 'ar' => 'أرفقت ملفًا يشرح متطلبات المشروع.'],
            'type' => 'percent_bp', 'value' => 300, 'condition_type' => 'attachment',
            'max_discount_minor' => 15000, 'stackable' => true, 'priority' => 5, 'is_active' => false,
        ]);

        SiteSetting::put('pricing_is_placeholder', true, 'pricing');
    }
}
