<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\ProjectRequest;
use App\Models\RequestStatus;
use App\Models\SiteSetting;
use App\Models\User;
use App\Notifications\NewContactMessageNotification;
use App\Notifications\NewProjectRequestNotification;
use App\Support\Mailing;
use Database\Seeders\RequestStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SalesTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RequestStatusSeeder::class);
        SiteSetting::put('currency', ['code' => 'USD', 'symbol' => '$', 'decimals' => 2, 'position' => 'before']);
    }

    private function loginAs(string $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role, 'is_active' => true])->save();
        $this->actingAs($user);

        return $user;
    }

    private function makeRequest(): ProjectRequest
    {
        return ProjectRequest::create([
            'number' => ProjectRequest::generateNumber(), 'project_type_snapshot' => ['key' => 'x', 'name' => ['en' => 'Site', 'ar' => 'موقع']],
            'request_status_id' => RequestStatus::default()->id, 'customer_name' => 'Ali', 'phone' => '+963992355844',
            'currency' => 'USD', 'total_minor' => 125000, 'subtotal_minor' => 125000, 'pricing_snapshot' => [],
        ]);
    }

    public function test_sale_and_payments_track_received_and_expected(): void
    {
        $this->loginAs('admin');
        $r = $this->makeRequest();

        $this->post("/admin/requests/{$r->id}/sale", ['sold_at' => '2026-11-10', 'final_price' => '1000'])->assertRedirect();
        $this->post("/admin/requests/{$r->id}/payments", ['amount' => '400.50', 'paid_at' => '2026-11-11', 'method' => 'cash'])->assertRedirect();

        $r->refresh();
        $this->assertSame('2026-11-10', $r->sold_at->toDateString());
        $this->assertSame(100000, $r->final_price_minor);
        $this->assertSame(40050, $r->receivedMinor());

        $this->get("/admin/requests/{$r->id}")->assertInertia(fn (Assert $p) => $p
            ->where('request.final_price_minor', 100000)->where('request.received_minor', 40050)->has('request.payments', 1));

        $this->get('/admin')->assertInertia(fn (Assert $p) => $p
            ->where('sales.sold_total_minor', 100000)->where('sales.expected_minor', 59950)->has('sales.due', 1));

        $this->get('/admin/requests?deal=due')->assertInertia(fn (Assert $p) => $p->where('requests.total', 1));
        $this->get('/admin/requests?deal=unsold')->assertInertia(fn (Assert $p) => $p->where('requests.total', 0));

        // Undo the sale; payments stay.
        $this->post("/admin/requests/{$r->id}/sale", ['sold_at' => null, 'final_price' => null])->assertRedirect();
        $this->assertNull($r->fresh()->sold_at);
        $this->assertSame(1, $r->payments()->count());
    }

    public function test_manual_deal_can_be_archived(): void
    {
        $this->loginAs('admin');
        $this->get('/admin/requests/create')->assertOk();

        $this->post('/admin/requests', [
            'customer_name' => 'Offline Client', 'phone' => '+963900000000', 'title' => 'Restaurant website',
            'request_status_id' => RequestStatus::where('key', 'accepted')->value('id'),
            'final_price' => '750', 'sold_at' => '2026-11-19', 'paid_amount' => '250', 'paid_at' => '2026-11-19', 'method' => 'transfer',
        ])->assertRedirect();

        $r = ProjectRequest::where('customer_name', 'Offline Client')->firstOrFail();
        $this->assertSame('manual', $r->source);
        $this->assertSame(75000, $r->final_price_minor);
        $this->assertSame(25000, $r->receivedMinor());
        $this->get('/admin/requests?source=manual')->assertInertia(fn (Assert $p) => $p->where('requests.total', 1));
    }

    public function test_viewer_cannot_record_sales(): void
    {
        $this->loginAs('viewer');
        $r = $this->makeRequest();
        $this->post("/admin/requests/{$r->id}/sale", ['sold_at' => '2026-11-10', 'final_price' => '1000'])->assertForbidden();
        $this->post('/admin/requests', ['customer_name' => 'X', 'title' => 'Y'])->assertForbidden();
    }

    public function test_owner_alert_email_receives_new_request_and_message(): void
    {
        config(['mail.default' => 'smtp']);
        Notification::fake();
        SiteSetting::put('notifications', ['email' => 'owner@example.com', 'enabled' => true]);
        $staff = User::factory()->create(['email' => 'staff@example.com']);
        $staff->forceFill(['role' => 'admin', 'is_active' => true])->save();

        Mailing::alertStaff([$staff], new NewProjectRequestNotification($this->makeRequest()));
        $msg = ContactMessage::create(['name' => 'Sara', 'subject' => 'Hi', 'message' => 'Hello there', 'locale' => 'en']);
        Mailing::alertStaff([$staff], new NewContactMessageNotification($msg));

        Notification::assertSentTo($staff, NewProjectRequestNotification::class, fn ($n, $channels) => $channels === ['database', 'mail']);
        Notification::assertSentOnDemand(NewProjectRequestNotification::class,
            fn ($n, $channels, AnonymousNotifiable $to) => $to->routes['mail'] === 'owner@example.com' && $channels === ['mail']);
        Notification::assertSentOnDemand(NewContactMessageNotification::class);
    }

    public function test_alert_not_duplicated_when_owner_is_staff_and_skipped_when_disabled(): void
    {
        config(['mail.default' => 'smtp']);
        Notification::fake();
        $staff = User::factory()->create(['email' => 'Owner@example.com']);
        $staff->forceFill(['role' => 'admin', 'is_active' => true])->save();

        SiteSetting::put('notifications', ['email' => 'owner@example.com', 'enabled' => true]);
        Mailing::alertStaff([$staff], new NewProjectRequestNotification($this->makeRequest()));
        Notification::assertSentOnDemandTimes(NewProjectRequestNotification::class, 0);

        SiteSetting::put('notifications', ['email' => 'other@example.com', 'enabled' => false]);
        Mailing::alertStaff([$staff], new NewProjectRequestNotification($this->makeRequest()));
        Notification::assertSentOnDemandTimes(NewProjectRequestNotification::class, 0);
    }

    public function test_admin_saves_alert_email(): void
    {
        $this->loginAs('admin');
        $this->put('/admin/content/settings/alerts', ['email' => 'me@gmail.com', 'enabled' => true])->assertRedirect();
        $this->assertSame('me@gmail.com', Mailing::alertEmail());
        $this->get('/admin/content/settings')->assertInertia(fn (Assert $p) => $p->where('alerts.email', 'me@gmail.com'));
    }
}
