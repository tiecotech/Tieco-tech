<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $note = ['en' => 'Draft text. Not reviewed by a lawyer. Replace before publishing.', 'ar' => 'نص أولي لم تتم مراجعته قانونيًا. استبدله قبل النشر.'];
        $pages = [
            'privacy' => ['Privacy policy', 'سياسة الخصوصية'],
            'terms' => ['Terms of use', 'شروط الاستخدام'],
            'requests-policy' => ['Requests and attachments policy', 'سياسة الطلبات والمرفقات'],
            'about' => ['About us', 'من نحن'],
        ];
        foreach ($pages as $slug => [$en, $ar]) {
            Page::firstOrCreate(['slug' => $slug], [
                'title' => ['en' => $en, 'ar' => $ar],
                'body' => str_starts_with($slug, 'about') ? ['en' => '', 'ar' => ''] : $note,
                'is_published' => false, // publish after review
            ]);
        }
    }
}
