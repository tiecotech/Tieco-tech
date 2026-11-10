<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectCategory;
use Illuminate\Database\Seeder;

/**
 * The real projects shown in "Our work". Safe to re-run: it updates each project by slug.
 * Screenshots live in public/projects/<slug>/ and ship with the app ("/..." paths are public assets).
 */
class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $sites = ProjectCategory::updateOrCreate(['slug' => 'websites'], [
            'name' => ['en' => 'Websites & dashboards', 'ar' => 'مواقع ولوحات تحكم'], 'is_visible' => true, 'sort_order' => 1,
        ]);

        $this->project($sites, 0, [
            'slug' => 'anan-alreyah',
            'title' => ['en' => 'Anan Al Riyah Trading', 'ar' => 'عنان الرياح للتجارة'],
            'summary' => [
                'en' => 'A bilingual corporate website for a multidisciplinary Saudi company in Riyadh, presenting its services, sectors and specialised brands, and receiving quote requests with attachments.',
                'ar' => 'موقع تعريفي بلغتين لشركة سعودية متعددة التخصصات في الرياض، يعرض خدماتها وقطاعاتها وأذرعها المتخصصة، ويستقبل طلبات عروض الأسعار بمرفقاتها.',
            ],
            'description' => [
                'en' => "Anan Al Riyah Trading is a Saudi company in Riyadh offering technical, operational and digital services, and supplying what its clients need. The brief was a site that shows this whole range clearly without losing the visitor: nine main service areas, seven sectors and seven specialised brands.\n\nWe built it in two languages (Arabic right-to-left and English) with 21 pages each, and connected it to a separate dashboard where the team updates every text and image without coming back to us. Any edit refreshes the site automatically. The motion identity grew out of the company name: clouds, golden winds and an emblem that assembles in front of the visitor.",
                'ar' => "عنان الرياح للتجارة شركة سعودية في الرياض تقدّم خدمات فنية وتشغيلية ورقمية، وتورّد ما يحتاجه عملاؤها. كانت الحاجة إلى موقع يُظهر هذا التنوع كله بوضوح دون أن يضيع الزائر: تسعة مجالات خدمة رئيسية، وسبعة قطاعات، وسبعة أذرع وعلامات متخصصة.\n\nبنينا الموقع بلغتين (العربية من اليمين إلى اليسار، والإنجليزية) في 21 صفحة لكل لغة، وربطناه بلوحة تحكم مستقلة يحدّث الفريق منها كل نص وصورة دون الرجوع إلينا، ويتجدد الموقع تلقائياً عند أي تعديل. وجاءت الهوية الحركية من اسم الشركة نفسه: غيوم، ورياح ذهبية، وشعار يتشكّل أمام الزائر.",
            ],
            'cover_path' => '/projects/anan/desktop-home.webp',
            'url' => 'https://anan-g.com',
            'tech' => ['Next.js 15', 'React 19', 'TypeScript', 'Tailwind CSS 4', 'Laravel API', 'MySQL'],
            'is_featured' => true,
            'meta' => [
                'accent' => ['#e5b06a', '#0d182c'],
                'logo' => '/projects/anan/logo.png',
                'desktop' => '/projects/anan/desktop-home.webp',
                'mobile' => '/projects/anan/mobile-home.webp',
                'year' => 2026,
                'role' => [
                    'en' => ['UI design', 'Website development', 'Dashboard', 'SEO'],
                    'ar' => ['تصميم الواجهات', 'تطوير الموقع', 'لوحة التحكم', 'تحسين محركات البحث'],
                ],
                'stats' => [
                    ['value' => '9', 'label' => ['en' => 'Main service areas', 'ar' => 'مجالات خدمة رئيسية']],
                    ['value' => '21', 'label' => ['en' => 'Pages per language', 'ar' => 'صفحة لكل لغة']],
                    ['value' => '2', 'label' => ['en' => 'Languages, RTL and LTR', 'ar' => 'لغتان، عربي وإنجليزي']],
                ],
                'features' => [
                    ['icon' => 'globe',
                        'title' => ['en' => 'Fully Arabic and English', 'ar' => 'عربي وإنجليزي بالكامل'],
                        'text' => ['en' => 'True right-to-left and left-to-right layouts, with SEO per language: hreflang, canonical links and a sitemap.', 'ar' => 'اتجاه صحيح من اليمين إلى اليسار وبالعكس، مع تحسين لمحركات البحث لكل لغة: hreflang وروابط أساسية وخريطة موقع.']],
                    ['icon' => 'file',
                        'title' => ['en' => 'Quote requests with attachments', 'ar' => 'طلب عرض سعر مع المرفقات'],
                        'text' => ['en' => 'A form that takes up to five files, protected against spam. Files are stored privately and downloaded from the dashboard only.', 'ar' => 'نموذج يستقبل حتى خمسة ملفات مع حماية من الرسائل المزعجة. تُحفظ المرفقات بشكل خاص وتُنزَّل من اللوحة فقط.']],
                    ['icon' => 'layout',
                        'title' => ['en' => 'A full page for every service', 'ar' => 'صفحة كاملة لكل خدمة'],
                        'text' => ['en' => 'Capabilities, common questions, working method, deliverables and the related brands, for each of the nine service areas.', 'ar' => 'القدرات والأسئلة الشائعة وطريقة العمل والمخرجات والعلامات المرتبطة، لكل مجال من المجالات التسعة.']],
                    ['icon' => 'search',
                        'title' => ['en' => 'Search that understands Arabic', 'ar' => 'بحث يفهم العربية'],
                        'text' => ['en' => 'Visitors find a service or a capability by typing it the way they would say it.', 'ar' => 'يصل الزائر إلى الخدمة أو القدرة التي يريدها بكتابتها كما ينطقها.']],
                    ['icon' => 'dashboard',
                        'title' => ['en' => 'A dashboard for all content', 'ar' => 'لوحة تحكم لكل المحتوى'],
                        'text' => ['en' => 'Services, brands, sectors, jobs and quote requests are managed from one place, and every edit refreshes the site.', 'ar' => 'الخدمات والعلامات والقطاعات والوظائف وطلبات الأسعار تُدار من مكان واحد، وكل تعديل يحدّث الموقع.']],
                    ['icon' => 'spark',
                        'title' => ['en' => 'Motion drawn from the name', 'ar' => 'حركة مستوحاة من الاسم'],
                        'text' => ['en' => 'Clouds, golden wind streaks and an animated emblem, all switched off for visitors who prefer reduced motion.', 'ar' => 'غيوم وخطوط رياح ذهبية وشعار متحرك، وتتوقف الحركة كلها لمن يفضّل تقليلها.']],
                ],
            ],
        ], [
            ['/projects/anan/desktop-services.webp', ['en' => 'The services page', 'ar' => 'صفحة الخدمات']],
            ['/projects/anan/desktop-brands.webp', ['en' => 'Specialised brands', 'ar' => 'الأذرع والعلامات المتخصصة']],
            ['/projects/anan/desktop-quote.webp', ['en' => 'Request a quote', 'ar' => 'طلب عرض سعر']],
            ['/projects/anan/mobile-services.webp', ['en' => 'Services on mobile', 'ar' => 'الخدمات على الجوال']],
        ]);

        $this->project($sites, 1, [
            'slug' => 'dr-sarah-bouzo',
            'title' => ['en' => 'Dr. Sarah Bouzo, Special Education Clinic', 'ar' => 'عيادة الدكتورة ساره محمود بوظو'],
            'summary' => [
                'en' => "An Arabic website for a children's special-education clinic: guidance for parents, a question bank the doctor answers, a live opening-hours status, all managed by her from a dashboard.",
                'ar' => 'موقع عربي لعيادة تربية خاصة للأطفال: توعية للأهل، وبنك أسئلة تجيب عنه الدكتورة، وحالة دوام لحظية، وكل شيء تديره بنفسها من لوحة تحكم.',
            ],
            'description' => [
                'en' => "Dr. Sarah Mahmoud Bouzo holds a PhD in special education and has ten years of experience assessing and rehabilitating children with disabilities. She wanted a website that reassures parents at first glance, gives them reliable information, and makes it easy to ask and to get in touch.\n\nWe chose a warm identity: soft pastel pinks, with clouds and bubbles drifting slowly, and an Arabic-first layout. Parents read awareness articles and success stories (under aliases), browse the doctor's conference appearances, and send their questions, which she answers and publishes herself. Everything can be hidden or shown with one click from a dashboard in Arabic.",
                'ar' => "الدكتورة ساره محمود بوظو حاصلة على الدكتوراه في التربية الخاصة، ولها خبرة عشر سنوات في تشخيص الأطفال ذوي الإعاقة وتقييمهم وتأهيلهم. أرادت موقعاً يطمئن الأهل من النظرة الأولى، ويقدّم لهم معلومة موثوقة، ويسهّل عليهم السؤال والتواصل.\n\nاخترنا هوية دافئة بألوان الباستيل الوردية، وغيوم وفقاعات تطفو ببطء، وتصميماً عربياً أولاً. يقرأ الأهل مقالات التوعية وقصص النجاح (بأسماء مستعارة)، ويتصفحون مشاركات الدكتورة في المؤتمرات، ويرسلون أسئلتهم فتجيب عنها وتنشرها بنفسها. وكل محتوى قابل للإخفاء والإظهار بنقرة من لوحة تحكم بالعربية.",
            ],
            'cover_path' => '/projects/sara/desktop-home.webp',
            'url' => 'https://drsara.tieco-tech.com',
            'tech' => ['Laravel 13', 'Filament 3', 'Blade', 'Tailwind CSS', 'Alpine.js', 'MySQL'],
            'is_featured' => true,
            'meta' => [
                'accent' => ['#f7a8c4', '#7b5cc9'],
                'logo' => '/projects/sara/logo.webp',
                'logo_on_light' => true,
                'desktop' => '/projects/sara/desktop-home.webp',
                'mobile' => '/projects/sara/mobile-home.webp',
                'year' => 2026,
                'role' => [
                    'en' => ['Identity & UI design', 'Website development', 'Dashboard', 'Animated logo', 'SEO'],
                    'ar' => ['تصميم الهوية والواجهات', 'تطوير الموقع', 'لوحة التحكم', 'شعار متحرك', 'تحسين محركات البحث'],
                ],
                'stats' => [
                    ['value' => '8', 'label' => ['en' => 'Main sections', 'ar' => 'أقسام رئيسية']],
                    ['value' => '100%', 'label' => ['en' => 'Arabic, right-to-left', 'ar' => 'عربي بالكامل']],
                    ['value' => ['en' => 'Live', 'ar' => 'مباشر'], 'label' => ['en' => 'Opening status, Damascus time', 'ar' => 'حالة الدوام بتوقيت دمشق']],
                ],
                'features' => [
                    ['icon' => 'book',
                        'title' => ['en' => 'Awareness articles', 'ar' => 'مقالات توعوية'],
                        'text' => ['en' => 'Categories, search, reading time, related articles and share buttons, with search-engine markup for each article.', 'ar' => 'تصنيفات وبحث وزمن قراءة ومقالات ذات صلة وأزرار مشاركة، مع ترميز لمحركات البحث لكل مقال.']],
                    ['icon' => 'chat',
                        'title' => ['en' => 'A question bank under her control', 'ar' => 'بنك أسئلة بإشراف الدكتورة'],
                        'text' => ['en' => "A visitor's question stays hidden until the doctor answers and publishes it. The asker's contact details are never shown.", 'ar' => 'يبقى سؤال الزائر مخفياً حتى تجيب عنه الدكتورة وتنشره، ولا تُعرض بيانات السائل أبداً.']],
                    ['icon' => 'heart',
                        'title' => ['en' => 'Stories and appearances', 'ar' => 'قصص ومشاركات'],
                        'text' => ['en' => 'Success stories under aliases with parents\' quotes, and a timeline of conferences and workshops, filterable by type.', 'ar' => 'قصص نجاح بأسماء مستعارة مع اقتباسات من الأهل، وخط زمني للمؤتمرات وورش العمل مع فلتر حسب النوع.']],
                    ['icon' => 'clock',
                        'title' => ['en' => 'Live opening status', 'ar' => 'حالة الدوام لحظياً'],
                        'text' => ['en' => 'The site says "open now" or "closed now" from the working hours and Damascus time.', 'ar' => 'يعرض الموقع «مفتوحة الآن» أو «مغلقة الآن» بحسب أوقات الدوام وتوقيت دمشق.']],
                    ['icon' => 'link',
                        'title' => ['en' => 'An "all my links" page', 'ar' => 'صفحة «كل روابطي»'],
                        'text' => ['en' => 'A mobile-first page for the Instagram bio: clinic map, WhatsApp, YouTube and the rest in one place.', 'ar' => 'صفحة للجوال تُوضع في نبذة إنستغرام: خريطة العيادة وواتساب ويوتيوب وغيرها في مكان واحد.']],
                    ['icon' => 'dashboard',
                        'title' => ['en' => 'Arabic dashboard and SEO', 'ar' => 'لوحة تحكم بالعربية وSEO'],
                        'text' => ['en' => 'She manages every section herself, and each page carries the markup search engines expect for a clinic.', 'ar' => 'تدير الدكتورة كل قسم بنفسها، وفي كل صفحة الترميز الذي تتوقعه محركات البحث لعيادة.']],
                ],
            ],
        ], [
            ['/projects/sara/desktop-about.webp', ['en' => 'About the doctor', 'ar' => 'عن الدكتورة']],
            ['/projects/sara/desktop-participations.webp', ['en' => 'Conferences and workshops', 'ar' => 'المشاركات والمؤتمرات']],
            ['/projects/sara/desktop-questions.webp', ['en' => 'The question bank', 'ar' => 'بنك الأسئلة']],
            ['/projects/sara/mobile-links.webp', ['en' => 'The "all my links" page', 'ar' => 'صفحة «كل روابطي»']],
        ]);
    }

    /** @param  list<array{0: string, 1: array<string, string>}>  $gallery */
    private function project(ProjectCategory $category, int $order, array $data, array $gallery): void
    {
        $project = Project::updateOrCreate(['slug' => $data['slug']], $data + [
            'project_category_id' => $category->id, 'is_visible' => true, 'is_demo' => false,
            'sort_order' => $order, 'published_at' => '2026-11-01 00:00:00',
        ]);

        $project->images()->delete();
        foreach ($gallery as $i => [$path, $alt]) {
            $project->images()->create(['path' => $path, 'alt' => ['text' => $alt], 'sort_order' => $i]);
        }
    }
}
