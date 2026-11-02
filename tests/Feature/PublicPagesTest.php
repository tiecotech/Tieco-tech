<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Page;
use App\Models\Service;
use App\Models\User;
use App\Notifications\NewContactMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function service(string $slug = 'web', array $extra = []): Service
    {
        return Service::create($extra + [
            'slug' => $slug, 'icon' => 'web', 'name' => ['en' => 'Web', 'ar' => 'ويب'],
            'summary' => ['en' => 'Sites', 'ar' => 'مواقع'], 'is_visible' => true, 'sort_order' => 1,
        ]);
    }

    public function test_services_index_lists_only_visible(): void
    {
        $this->service('web');
        $this->service('hidden', ['is_visible' => false]);

        $this->get('/en/services')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('services')->has('services', 1)->where('services.0.slug', 'web'));
    }

    public function test_service_detail_filters_empty_items_and_translates(): void
    {
        $this->service('web', [
            'problems' => ['en' => ['Slow site', '', 5], 'ar' => ['موقع بطيء']],
            'steps' => ['en' => [['title' => 'Plan', 'text' => 'We plan'], ['title' => 'No text']], 'ar' => []],
            'faqs' => ['en' => [['q' => 'How long?', 'a' => 'Depends']], 'ar' => []],
        ]);

        $this->get('/en/services/web')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('service-show')
            ->where('service.problems', ['Slow site'])
            ->has('service.steps', 1)->where('service.faqs.0.q', 'How long?'));

        $this->get('/ar/services/web')->assertInertia(fn (Assert $p) => $p->where('service.name', 'ويب')->where('service.problems', ['موقع بطيء']));
    }

    public function test_service_detail_404_for_hidden_or_unknown(): void
    {
        $this->service('hidden', ['is_visible' => false]);
        $this->get('/en/services/hidden')->assertNotFound();
        $this->get('/en/services/nope')->assertNotFound();
    }

    public function test_about_uses_custom_text_only_when_published(): void
    {
        Page::create(['slug' => 'about', 'title' => ['en' => 'About X'], 'body' => ['en' => "One.\n\nTwo."], 'is_published' => false]);
        $this->get('/en/about')->assertOk()->assertInertia(fn (Assert $p) => $p->where('custom', null));

        Page::where('slug', 'about')->update(['is_published' => true]);
        $this->get('/en/about')->assertInertia(fn (Assert $p) => $p->where('custom.title', 'About X')->where('custom.body', ['One.', 'Two.']));
    }

    public function test_legal_pages_are_hidden_until_published(): void
    {
        Page::create(['slug' => 'privacy', 'title' => ['en' => 'Privacy'], 'body' => ['en' => 'A.'], 'is_published' => false]);
        $this->get('/en/legal/privacy')->assertNotFound();
        $this->get('/en')->assertInertia(fn (Assert $p) => $p->where('legal', []));

        Page::where('slug', 'privacy')->update(['is_published' => true]);
        $this->get('/en/legal/privacy')->assertOk()->assertInertia(fn (Assert $p) => $p->where('paragraphs', ['A.']));
        $this->get('/en')->assertInertia(fn (Assert $p) => $p->where('legal.0.slug', 'privacy'));
    }

    public function test_only_legal_slugs_are_served(): void
    {
        Page::create(['slug' => 'about', 'title' => ['en' => 'About'], 'body' => ['en' => 'x'], 'is_published' => true]);
        $this->get('/en/legal/about')->assertNotFound();
    }

    public function test_contact_message_is_saved_with_locale(): void
    {
        $this->post('/ar/contact', ['name' => 'Sara', 'email' => 'sara@example.com', 'subject' => 'Hello', 'message' => 'I would like to talk.'])
            ->assertRedirect();

        $this->assertDatabaseHas('contact_messages', ['name' => 'Sara', 'locale' => 'ar', 'status' => 'new']);
        $this->get('/ar/contact')->assertInertia(fn (Assert $p) => $p->where('sent', true));
        $this->get('/ar/contact')->assertInertia(fn (Assert $p) => $p->where('sent', false)); // flag is one-time
    }

    public function test_contact_needs_email_or_phone_and_a_real_message(): void
    {
        $this->post('/en/contact', ['name' => 'A', 'subject' => 'Hi', 'message' => 'Long enough text here'])->assertSessionHasErrors(['email', 'phone']);
        $this->post('/en/contact', ['name' => 'A', 'email' => 'a@b.com', 'subject' => 'Hi', 'message' => 'short'])->assertSessionHasErrors('message');
        $this->post('/en/contact', ['name' => 'A', 'phone' => 'abc', 'subject' => 'Hi', 'message' => 'Long enough text here'])->assertSessionHasErrors('phone');
        $this->assertSame(0, ContactMessage::count());
    }

    public function test_honeypot_drops_the_message(): void
    {
        $this->post('/en/contact', ['name' => 'Bot', 'email' => 'b@b.com', 'subject' => 'Spam', 'message' => 'Buy things now please', 'website' => 'http://spam'])->assertRedirect();
        $this->assertSame(0, ContactMessage::count());
    }

    public function test_staff_with_message_permission_is_notified(): void
    {
        Notification::fake();
        $manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
        $viewer = User::factory()->create(['role' => 'viewer', 'is_active' => true]);

        $this->post('/en/contact', ['name' => 'Sara', 'phone' => '+963992355844', 'subject' => 'Hello', 'message' => 'Please call me back.'])->assertRedirect();

        Notification::assertSentTo($manager, NewContactMessageNotification::class);
        Notification::assertNotSentTo($viewer, NewContactMessageNotification::class);
    }

    public function test_contact_form_is_rate_limited(): void
    {
        $payload = ['name' => 'Sara', 'email' => 'sara@example.com', 'subject' => 'Hello', 'message' => 'Same message again.'];
        for ($i = 0; $i < 5; $i++) {
            $this->post('/en/contact', $payload)->assertRedirect();
        }
        $this->post('/en/contact', $payload)->assertStatus(429);
    }

    public function test_robots_and_sitemap(): void
    {
        $this->service('web');
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin', false)->assertSee('/sitemap.xml', false);
        $this->get('/sitemap.xml')->assertOk()->assertSee('/en/services/web', false)->assertSee('hreflang="ar"', false)
            ->assertDontSee('/legal/', false);
    }

    public function test_publish_command(): void
    {
        Page::create(['slug' => 'terms', 'title' => ['en' => 'Terms'], 'body' => ['en' => 'x'], 'is_published' => false]);
        $this->artisan('tieco:page terms')->assertSuccessful();
        $this->assertTrue(Page::where('slug', 'terms')->first()->is_published);
        $this->artisan('tieco:page terms --hide')->assertSuccessful();
        $this->assertFalse(Page::where('slug', 'terms')->first()->is_published);
        $this->artisan('tieco:page missing')->assertFailed();
    }
}
