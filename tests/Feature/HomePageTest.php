<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_root_redirects_to_a_supported_locale(): void
    {
        $this->get('/', ['Accept-Language' => 'ar'])->assertRedirect('/ar');
        $this->get('/', ['Accept-Language' => 'fr'])->assertRedirect('/en');
    }

    public function test_english_and_arabic_homepages_set_direction_and_ui_strings(): void
    {
        Service::create(['slug' => 'web', 'name' => ['en' => 'Web', 'ar' => 'مواقع'], 'summary' => ['en' => 'E', 'ar' => 'ع']]);

        $this->get('/en')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('home')->where('locale', 'en')->where('dir', 'ltr')
            ->where('alternate.url', url('/ar'))->where('services.0.name', 'Web')->has('ui'));

        $this->get('/ar')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->where('locale', 'ar')->where('dir', 'rtl')
            ->where('alternate.url', url('/en'))->where('services.0.name', 'مواقع'));
    }

    public function test_unknown_locale_is_404_and_hidden_services_are_not_listed(): void
    {
        $this->get('/fr')->assertNotFound();
        Service::create(['slug' => 'x', 'name' => ['en' => 'Hidden'], 'is_visible' => false]);
        $this->get('/en')->assertInertia(fn (Assert $p) => $p->has('services', 0));
    }

    public function test_only_real_featured_projects_are_shown(): void
    {
        Project::create(['slug' => 'real', 'title' => ['en' => 'Real'], 'is_featured' => true, 'is_demo' => false]);
        Project::create(['slug' => 'demo', 'title' => ['en' => 'Demo'], 'is_featured' => true, 'is_demo' => true]);
        Project::create(['slug' => 'plain', 'title' => ['en' => 'Plain'], 'is_featured' => false]);

        $this->get('/en')->assertInertia(fn (Assert $p) => $p->has('projects', 1)->where('projects.0.slug', 'real'));
    }

    public function test_language_switch_keeps_the_page_and_query(): void
    {
        $this->get('/en/start?service=web')->assertInertia(fn (Assert $p) => $p
            ->component('builder')->where('alternate.url', url('/ar/start').'?service=web'));
    }
}
