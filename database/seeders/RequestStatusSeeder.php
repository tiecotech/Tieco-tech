<?php

namespace Database\Seeders;

use App\Models\RequestStatus;
use Illuminate\Database\Seeder;

class RequestStatusSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['new', 'New', 'جديد', 'blue', true, false],
            ['in_review', 'In review', 'قيد المراجعة', 'amber', false, false],
            ['needs_info', 'Needs more information', 'بحاجة إلى معلومات إضافية', 'orange', false, false],
            ['quote_prepared', 'Quote prepared', 'تم إعداد عرض السعر', 'violet', false, false],
            ['quote_sent', 'Quote sent', 'تم إرسال عرض السعر', 'indigo', false, false],
            ['accepted', 'Accepted', 'مقبول', 'green', false, false],
            ['rejected', 'Rejected', 'مرفوض', 'red', false, true],
            ['in_progress', 'In progress', 'قيد التنفيذ', 'cyan', false, false],
            ['completed', 'Completed', 'مكتمل', 'emerald', false, true],
            ['cancelled', 'Cancelled', 'ملغي', 'slate', false, true],
        ];
        foreach ($rows as $i => [$key, $en, $ar, $color, $default, $final]) {
            RequestStatus::updateOrCreate(['key' => $key], [
                'name' => ['en' => $en, 'ar' => $ar], 'color' => $color,
                'is_default' => $default, 'is_final' => $final, 'sort_order' => $i,
            ]);
        }
    }
}
