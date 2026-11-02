<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use App\Support\Launch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ComingSoonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['tieco.force_live' => false]); // phpunit.xml forces the site live for every other test
    }

    private function loginAs(string $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role, 'is_active' => true])->save();
        $this->actingAs($user);

        return $user;
    }

    public function test_visitors_see_coming_soon_before_launch(): void
    {
        $this->get('/ar')->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertInertia(fn (Assert $p) => $p->component('coming-soon')->where('locale', 'ar')->where('launchDate', Launch::DEFAULT_DATE));
        $this->get('/en/services')->assertInertia(fn (Assert $p) => $p->component('coming-soon')->where('locale', 'en'));
        $this->get('/')->assertOk()->assertInertia(fn (Assert $p) => $p->component('coming-soon'));
        $this->post('/en/contact', ['name' => 'x'])->assertInertia(fn (Assert $p) => $p->component('coming-soon'));
        $this->get('/sitemap.xml')->assertNotFound();
    }

    public function test_signed_in_staff_see_the_real_site(): void
    {
        $this->loginAs('viewer');

        $this->get('/en')->assertInertia(fn (Assert $p) => $p->component('home'));
        $this->get('/?coming-soon')->assertInertia(fn (Assert $p) => $p->component('coming-soon'));
    }

    public function test_preview_link_unlocks_the_site_and_a_wrong_token_does_not(): void
    {
        $token = Launch::token();

        $this->get('/preview/wrong-token')->assertNotFound();
        $this->get("/preview/{$token}")->assertRedirect('/')->assertCookie(Launch::COOKIE, $token);
        $this->withCookie(Launch::COOKIE, $token)->get('/en')->assertInertia(fn (Assert $p) => $p->component('home'));
        $this->withCookie(Launch::COOKIE, 'stale')->get('/en')->assertInertia(fn (Assert $p) => $p->component('coming-soon'));
    }

    public function test_rotating_the_link_revokes_old_previews(): void
    {
        $old = Launch::token();
        $this->loginAs('admin');
        $this->post('/admin/launch/preview-link')->assertRedirect();
        auth()->logout();

        $this->assertNotSame($old, Launch::settings()['token']);
        $this->withCookie(Launch::COOKIE, $old)->get('/en')->assertInertia(fn (Assert $p) => $p->component('coming-soon'));
    }

    public function test_admin_launches_the_site(): void
    {
        $this->loginAs('admin');
        $this->get('/admin/launch')->assertOk()->assertInertia(fn (Assert $p) => $p->component('admin/launch')->where('live', false));

        $this->put('/admin/launch', ['live' => true, 'date' => '2026-12-01'])->assertRedirect();
        auth()->logout();

        $this->assertSame(['live' => true, 'date' => '2026-12-01'], array_intersect_key(SiteSetting::get('launch'), ['live' => 1, 'date' => 1]));
        $this->get('/en')->assertInertia(fn (Assert $p) => $p->component('home'));
        $this->get('/sitemap.xml')->assertOk();
    }

    public function test_only_admins_can_launch(): void
    {
        $this->loginAs('editor');

        $this->get('/admin/launch')->assertForbidden();
        $this->put('/admin/launch', ['live' => true])->assertForbidden();
        $this->assertFalse(Launch::isLive());
    }
}
