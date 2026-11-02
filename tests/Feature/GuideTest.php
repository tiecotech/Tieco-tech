<?php

namespace Tests\Feature;

use App\Models\ProjectType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GuideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_builder_receives_clean_presets_from_the_guide_link(): void
    {
        ProjectType::create(['key' => 'company_site', 'kind' => 'website', 'name' => ['en' => 'Company', 'ar' => 'شركة'], 'supports_pages' => true, 'min_pages' => 1, 'max_pages' => 20, 'included_pages' => 3, 'is_active' => true]);

        $this->get('/en/start?type=company_site&pages=8&options=seo,multilingual')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('builder')->where('preselect', 'company_site')->where('presetPages', 8)->where('presetOptions', ['seo', 'multilingual']));
    }

    public function test_preset_options_ignore_junk_and_huge_values(): void
    {
        $this->get('/en/start?pages=99999&options='.urlencode('seo,<script>,../etc,SEO,ok_1,seo'))->assertOk()->assertInertia(fn (Assert $p) => $p
            ->where('presetPages', 500)->where('presetOptions', ['seo', 'ok_1']));
    }

    public function test_builder_without_presets_is_unchanged(): void
    {
        $this->get('/en/start')->assertInertia(fn (Assert $p) => $p->where('presetPages', null)->where('presetOptions', []));
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertInertia(fn (Assert $p) => $p->component('auth/login'));
    }

    public function test_guide_strings_exist_in_both_languages(): void
    {
        foreach (['en', 'ar'] as $l) {
            $site = require base_path("lang/{$l}/site.php");
            foreach (['g.title', 'g.hello', 'g.done.builder', 'g.t.online_store', 'g.o.android_ios', 'g.faq.3.a'] as $k) {
                $this->assertArrayHasKey($k, $site, "{$l} missing {$k}");
            }
            // every type and option the guide can suggest has a label
            foreach (['personal_site', 'company_site', 'service_site', 'online_store', 'blog', 'booking_system', 'custom_software', 'mobile_app', 'branding', 'ads_marketing', 'photo_video', 'bundle'] as $t) {
                $this->assertArrayHasKey("g.t.{$t}", $site);
            }
        }
    }
}
