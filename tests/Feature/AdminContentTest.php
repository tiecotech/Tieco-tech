<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ContactMessage;
use App\Models\Page;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function loginAs(string $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role, 'is_active' => true])->save();
        $this->actingAs($user);

        return $user;
    }

    private function message(): ContactMessage
    {
        return ContactMessage::create(['name' => 'Sara', 'email' => 'sara@example.com', 'subject' => 'Hello', 'message' => 'Please call me.', 'locale' => 'en']);
    }

    // ---- messages ----
    public function test_manager_reads_and_manages_messages(): void
    {
        $this->loginAs('manager');
        $m = $this->message();

        $this->get('/admin/messages')->assertOk()->assertInertia(fn (Assert $p) => $p->component('admin/messages/index')->has('messages.data', 1));
        $this->get("/admin/messages/{$m->id}")->assertOk();
        $this->assertSame('read', $m->fresh()->status); // opening marks it read

        $this->post("/admin/messages/{$m->id}/status", ['status' => 'replied'])->assertRedirect();
        $this->assertSame('replied', $m->fresh()->status);
        $this->post("/admin/messages/{$m->id}/status", ['status' => 'bogus'])->assertSessionHasErrors('status');

        $this->delete("/admin/messages/{$m->id}")->assertRedirect();
        $this->assertSoftDeleted('contact_messages', ['id' => $m->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'message.deleted']);
    }

    public function test_viewer_can_read_but_not_change_or_mark_read(): void
    {
        $this->loginAs('viewer');
        $m = $this->message();

        $this->get("/admin/messages/{$m->id}")->assertOk();
        $this->assertSame('new', $m->fresh()->status);
        $this->post("/admin/messages/{$m->id}/status", ['status' => 'read'])->assertForbidden();
        $this->delete("/admin/messages/{$m->id}")->assertForbidden();
    }

    public function test_message_search_and_status_filter(): void
    {
        $this->loginAs('manager');
        $this->message();
        $omar = ContactMessage::create(['name' => 'Omar', 'phone' => '+96300000000', 'subject' => 'Quote', 'message' => 'Need a quote', 'locale' => 'en']);
        $omar->forceFill(['status' => 'archived'])->save(); // status is not mass-assignable on purpose

        $this->get('/admin/messages?q=Omar')->assertInertia(fn (Assert $p) => $p->has('messages.data', 1)->where('messages.data.0.name', 'Omar'));
        $this->get('/admin/messages?status=new')->assertInertia(fn (Assert $p) => $p->has('messages.data', 1)->where('messages.data.0.name', 'Sara'));
    }

    // ---- users ----
    public function test_only_admin_manages_users(): void
    {
        foreach (['manager', 'editor', 'viewer'] as $role) {
            $this->loginAs($role);
            $this->get('/admin/users')->assertForbidden();
            $this->post('/admin/users', [])->assertForbidden();
        }
    }

    public function test_admin_creates_a_staff_user_with_strong_password(): void
    {
        $this->loginAs('admin');

        $this->post('/admin/users', ['name' => 'Lina', 'email' => 'lina@example.com', 'role' => 'editor', 'locale' => 'ar', 'password' => 'weak'])
            ->assertSessionHasErrors('password');

        $this->post('/admin/users', ['name' => 'Lina', 'email' => 'lina@example.com', 'role' => 'editor', 'locale' => 'ar', 'password' => 'Str0ngPassword123'])->assertRedirect();
        $lina = User::where('email', 'lina@example.com')->first();
        $this->assertSame('editor', $lina->role->value);
        $this->assertNotNull($lina->email_verified_at);
        $this->assertTrue(password_verify('Str0ngPassword123', $lina->password));

        $log = ActivityLog::where('action', 'user.created')->first();
        $this->assertStringNotContainsString('Str0ng', json_encode($log->properties));

        $this->post('/admin/users', ['name' => 'Dup', 'email' => 'lina@example.com', 'role' => 'viewer', 'locale' => 'en', 'password' => 'Str0ngPassword123'])->assertSessionHasErrors('email');
        $this->post('/admin/users', ['name' => 'Bad', 'email' => 'bad@example.com', 'role' => 'superuser', 'locale' => 'en', 'password' => 'Str0ngPassword123'])->assertSessionHasErrors('role');
    }

    public function test_admin_changes_role_deactivates_and_resets_password(): void
    {
        $this->loginAs('admin');
        $u = User::factory()->create();
        $u->forceFill(['role' => 'viewer', 'is_active' => true])->save();

        $this->put("/admin/users/{$u->id}", ['name' => 'New Name', 'role' => 'manager', 'is_active' => true, 'password' => 'Another1Password9'])->assertRedirect();
        $u->refresh();
        $this->assertSame('manager', $u->role->value);
        $this->assertTrue(password_verify('Another1Password9', $u->password));

        $this->put("/admin/users/{$u->id}", ['name' => 'New Name', 'role' => 'manager', 'is_active' => false])->assertRedirect();
        $this->assertFalse((bool) $u->fresh()->is_active);
        $this->assertTrue(password_verify('Another1Password9', $u->fresh()->password)); // empty password keeps the old one
    }

    public function test_nobody_can_lock_out_the_last_admin(): void
    {
        $me = $this->loginAs('admin');

        $this->put("/admin/users/{$me->id}", ['name' => 'Me', 'role' => 'viewer', 'is_active' => true])->assertSessionHasErrors('role');
        $this->put("/admin/users/{$me->id}", ['name' => 'Me', 'role' => 'admin', 'is_active' => false])->assertSessionHasErrors('role');
        $this->assertSame('admin', $me->fresh()->role->value);

        // With a second admin, a different admin may be demoted, but never the last one.
        $other = User::factory()->create();
        $other->forceFill(['role' => 'admin', 'is_active' => true])->save();
        $this->put("/admin/users/{$other->id}", ['name' => 'Other', 'role' => 'viewer', 'is_active' => true])->assertRedirect();
        $this->assertSame('viewer', $other->fresh()->role->value);
    }

    // ---- activity ----
    public function test_activity_log_is_admin_only(): void
    {
        $this->loginAs('manager');
        $this->get('/admin/activity')->assertForbidden();

        $this->loginAs('admin');
        ActivityLog::record('pricing.type_updated');
        $this->get('/admin/activity?q=pricing')->assertOk()->assertInertia(fn (Assert $p) => $p->component('admin/activity')->has('logs.data', 1));
    }

    // ---- content ----
    public function test_editor_manages_services_and_cleans_input(): void
    {
        $this->loginAs('editor');

        $payload = [
            'slug' => 'web-design', 'icon' => 'web', 'is_visible' => true, 'sort_order' => 3,
            'name' => ['en' => 'Web design', 'ar' => 'تصميم المواقع'], 'summary' => ['en' => 'Sites', 'ar' => 'مواقع'], 'description' => ['en' => "One.\n\nTwo.", 'ar' => ''],
            'problems' => ['en' => ['Slow site', '', 'Hard to edit'], 'ar' => []],
            'deliverables' => ['en' => [], 'ar' => []], 'features' => ['en' => [], 'ar' => []],
            'steps' => ['en' => [['title' => 'Plan', 'text' => 'We plan'], ['title' => 'Half', 'text' => '']], 'ar' => []],
            'faqs' => ['en' => [['q' => 'How long?', 'a' => 'Depends']], 'ar' => []],
        ];
        $this->post('/admin/content/services', $payload)->assertRedirect();

        $s = Service::where('slug', 'web-design')->first();
        $this->assertSame(['Slow site', 'Hard to edit'], $s->problems['en']);
        $this->assertCount(1, $s->steps['en']); // half-filled rows are dropped
        $this->assertSame('How long?', $s->faqs['en'][0]['q']);

        // public page shows it
        $this->get('/en/services/web-design')->assertOk()->assertInertia(fn (Assert $p) => $p->where('service.problems', ['Slow site', 'Hard to edit']));

        // slug can't be changed on update; bad input rejected
        $this->put("/admin/content/services/{$s->id}", ['slug' => 'other'] + $payload)->assertSessionHasErrors('slug');
        $this->post('/admin/content/services', ['slug' => 'Bad Slug'] + $payload)->assertSessionHasErrors('slug');
        $this->post('/admin/content/services', ['slug' => 'web-design'] + $payload)->assertSessionHasErrors('slug');

        $update = $payload; unset($update['slug']); $update['is_visible'] = false;
        $this->put("/admin/content/services/{$s->id}", $update)->assertRedirect();
        $this->get('/en/services/web-design')->assertNotFound();

        $this->delete("/admin/content/services/{$s->id}")->assertRedirect();
        $this->assertSoftDeleted('services', ['id' => $s->id]);
    }

    public function test_page_publish_toggle_and_validation(): void
    {
        $this->loginAs('editor');
        $page = Page::create(['slug' => 'privacy', 'title' => ['en' => 'Privacy', 'ar' => 'الخصوصية'], 'body' => ['en' => 'Draft', 'ar' => 'مسودة'], 'is_published' => false]);

        $this->put("/admin/content/pages/{$page->id}", ['title' => ['en' => 'Privacy', 'ar' => ''], 'body' => ['en' => 'x', 'ar' => 'y'], 'is_published' => true])->assertSessionHasErrors('title.ar');

        $this->put("/admin/content/pages/{$page->id}", ['title' => ['en' => 'Privacy', 'ar' => 'الخصوصية'], 'body' => ['en' => 'Reviewed text', 'ar' => 'نص'], 'is_published' => true])->assertRedirect();
        $this->get('/en/legal/privacy')->assertOk();
        $this->assertDatabaseHas('activity_logs', ['action' => 'page.published']);
    }

    public function test_team_crud_and_contact_settings(): void
    {
        $this->loginAs('editor');

        $this->post('/admin/content/team', ['name' => ['en' => 'Sam', 'ar' => 'سام'], 'role' => ['en' => 'Dev', 'ar' => 'مطور'], 'is_visible' => true, 'sort_order' => 1])->assertRedirect();
        $m = TeamMember::first();
        $this->put("/admin/content/team/{$m->id}", ['name' => ['en' => 'Sam 2', 'ar' => 'سام'], 'role' => ['en' => 'Dev', 'ar' => 'مطور'], 'is_visible' => false, 'sort_order' => 1])->assertRedirect();
        $this->assertSame('Sam 2', $m->fresh()->name['en']);
        $this->get('/en/about')->assertInertia(fn (Assert $p) => $p->has('team', 0)); // hidden members stay hidden
        $this->delete("/admin/content/team/{$m->id}")->assertRedirect();
        $this->assertSame(0, TeamMember::count());

        $this->put('/admin/content/settings', ['email' => 'hi@tieco.test', 'phone' => '+963 992 355 844', 'whatsapp' => '+963992355844', 'address' => 'Damascus'])->assertRedirect();
        $this->assertSame('+963992355844', SiteSetting::get('contact')['whatsapp']);
        $this->put('/admin/content/settings', ['email' => 'nope', 'whatsapp' => '0992355844'])->assertSessionHasErrors(['email', 'whatsapp']);
    }

    public function test_content_screens_need_content_permission(): void
    {
        foreach (['viewer', 'manager'] as $role) {
            $this->loginAs($role);
            $this->get('/admin/content/services')->assertForbidden();
            $this->get('/admin/content/pages')->assertForbidden();
            $this->put('/admin/content/settings', [])->assertForbidden();
        }
        $this->loginAs('editor');
        $this->get('/admin/content/services')->assertOk();
        $this->get('/admin/content/services/new')->assertOk();
        $this->get('/admin/content/pages')->assertOk();
        $this->get('/admin/content/team')->assertOk();
        $this->get('/admin/content/settings')->assertOk();
    }
}
