<?php

namespace Tests\Feature;

use App\Models\Project;
use Database\Seeders\ProjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProjectsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_seeder_adds_the_two_projects_and_is_safe_to_rerun(): void
    {
        $this->seed(ProjectSeeder::class);
        $this->seed(ProjectSeeder::class);

        $this->assertSame(['anan-alreyah', 'dr-sarah-bouzo'], Project::orderBy('sort_order')->pluck('slug')->all());
        $this->assertSame(4, Project::where('slug', 'anan-alreyah')->first()->images()->count());
    }

    public function test_portfolio_page_lists_public_projects_in_the_active_language(): void
    {
        $this->seed(ProjectSeeder::class);
        Project::create(['slug' => 'demo', 'title' => ['en' => 'Demo'], 'is_demo' => true]);
        Project::create(['slug' => 'hidden', 'title' => ['en' => 'Hidden'], 'is_visible' => false]);

        $this->get('/ar/projects')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('projects')->has('projects', 2)
            ->where('projects.0.slug', 'anan-alreyah')->where('projects.0.title', 'عنان الرياح للتجارة')
            ->where('projects.0.host', 'anan-g.com')->where('projects.1.stats.0.label', 'أقسام رئيسية')
            ->where('projects.1.logo_light', true));

        $this->get('/en/projects')->assertInertia(fn (Assert $p) => $p
            ->where('projects.0.title', 'Anan Al Riyah Trading')->where('projects.0.stats.0.value', '9'));
    }

    public function test_project_page_has_the_story_features_and_gallery(): void
    {
        $this->seed(ProjectSeeder::class);

        $this->get('/ar/projects/dr-sarah-bouzo')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('project')->has('project.paragraphs', 2)->has('project.features', 6)->has('project.gallery', 4)
            ->where('project.gallery.3.mobile', true)->where('project.gallery.0.mobile', false)
            ->where('project.url', 'https://drsara.tieco-tech.com')->has('others', 1)->where('others.0.slug', 'anan-alreyah'));
    }

    public function test_demo_hidden_and_unknown_projects_are_not_found(): void
    {
        Project::create(['slug' => 'demo', 'title' => ['en' => 'Demo'], 'is_demo' => true]);
        Project::create(['slug' => 'hidden', 'title' => ['en' => 'Hidden'], 'is_visible' => false]);

        $this->get('/en/projects/demo')->assertNotFound();
        $this->get('/en/projects/hidden')->assertNotFound();
        $this->get('/en/projects/missing')->assertNotFound();
    }

    public function test_home_page_shows_the_featured_projects_and_the_sitemap_lists_them(): void
    {
        $this->seed(ProjectSeeder::class);

        $this->get('/en')->assertInertia(fn (Assert $p) => $p->has('projects', 2)->where('projects.1.slug', 'dr-sarah-bouzo'));
        $this->get('/sitemap.xml')->assertOk()->assertSee('/en/projects/anan-alreyah', false)->assertSee('/ar/projects', false);
    }
}
