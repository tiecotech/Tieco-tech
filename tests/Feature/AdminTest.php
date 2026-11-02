<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\DiscountRule;
use App\Models\ProjectRequest;
use App\Models\ProjectRequestAttachment;
use App\Models\ProjectType;
use App\Models\RequestStatus;
use App\Models\ServiceOption;
use App\Models\User;
use App\Support\Money;
use Database\Seeders\RequestStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RequestStatusSeeder::class);
        Storage::fake('local');
    }

    private function loginAs(string $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role, 'is_active' => true])->save();
        $this->actingAs($user);

        return $user;
    }

    private function makeRequest(string $name = 'Ali'): ProjectRequest
    {
        return ProjectRequest::create([
            'number' => ProjectRequest::generateNumber(), 'project_type_snapshot' => ['key' => 'x', 'name' => ['en' => 'Site', 'ar' => 'موقع']],
            'request_status_id' => RequestStatus::default()->id, 'customer_name' => $name, 'phone' => '+963992355844',
            'currency' => 'USD', 'total_minor' => 125000, 'subtotal_minor' => 125000, 'pricing_snapshot' => [],
        ]);
    }

    public function test_guests_cannot_enter_and_inactive_staff_are_blocked(): void
    {
        $this->get('/admin')->assertRedirect('/login');

        $user = $this->loginAs('admin');
        $user->forceFill(['is_active' => false])->save();
        $this->get('/admin')->assertForbidden();
    }

    public function test_viewer_can_look_but_not_change_or_edit_prices(): void
    {
        $this->loginAs('viewer');
        $req = $this->makeRequest();

        $this->get('/admin')->assertOk()->assertInertia(fn (Assert $p) => $p->component('admin/dashboard')->where('kpis.total', 1)->where('kpis.new', 1));
        $this->get('/admin/requests')->assertOk()->assertInertia(fn (Assert $p) => $p->has('requests.data', 1));
        $this->get("/admin/requests/{$req->id}")->assertOk();
        $this->get('/admin/pricing')->assertForbidden();
        $this->post("/admin/requests/{$req->id}/status", ['request_status_id' => 2])->assertForbidden();
        $this->get('/admin/requests/export')->assertForbidden();
    }

    public function test_manager_changes_status_adds_notes_and_assigns(): void
    {
        $manager = $this->loginAs('manager');
        $req = $this->makeRequest();
        $inReview = RequestStatus::where('key', 'in_review')->firstOrFail();

        $this->post("/admin/requests/{$req->id}/status", ['request_status_id' => $inReview->id, 'note' => 'Called the client'])->assertRedirect();
        $this->assertSame($inReview->id, $req->fresh()->request_status_id);
        $this->assertSame(1, $req->notes()->where('type', 'status')->count());
        $this->assertTrue(ActivityLog::where('action', 'request.status_changed')->exists());

        $this->post("/admin/requests/{$req->id}/notes", ['type' => 'contact', 'channel' => 'whatsapp', 'body' => 'Sent details'])->assertRedirect();
        $this->assertSame('whatsapp', $req->notes()->where('type', 'contact')->firstOrFail()->channel);

        $this->post("/admin/requests/{$req->id}/assign", ['assigned_to' => $manager->id])->assertRedirect();
        $this->assertSame($manager->id, $req->fresh()->assigned_to);

        $this->post("/admin/requests/{$req->id}/status", ['request_status_id' => 9999])->assertSessionHasErrors('request_status_id');
    }

    public function test_search_and_filters(): void
    {
        $this->loginAs('manager');
        $this->makeRequest('Layla Hassan');
        $this->makeRequest('Omar Khaled');

        $this->get('/admin/requests?q=Layla')->assertInertia(fn (Assert $p) => $p->has('requests.data', 1)->where('requests.data.0.customer', 'Layla Hassan'));
        $this->get('/admin/requests?status=accepted')->assertInertia(fn (Assert $p) => $p->has('requests.data', 0));
        $this->get('/admin/requests?q=%25')->assertInertia(fn (Assert $p) => $p->has('requests.data', 0)); // % is searched literally
    }

    public function test_attachments_download_only_for_their_own_request_and_staff(): void
    {
        $this->loginAs('viewer');
        $a = $this->makeRequest();
        $b = $this->makeRequest('Other');
        Storage::disk('local')->put('requests/x.pdf', 'pdf-bytes');
        $file = ProjectRequestAttachment::create(['project_request_id' => $a->id, 'disk' => 'local', 'path' => 'requests/x.pdf',
            'original_name' => 'brief.pdf', 'mime' => 'application/pdf', 'size' => 9, 'sha256' => str_repeat('a', 64)]);

        $this->get("/admin/requests/{$a->id}/attachments/{$file->id}")->assertOk()->assertDownload('brief.pdf');
        $this->get("/admin/requests/{$b->id}/attachments/{$file->id}")->assertNotFound();
    }

    public function test_csv_export_is_for_managers_and_neutralises_formulas(): void
    {
        $this->loginAs('manager');
        $this->makeRequest('=HYPERLINK("http://evil")');

        $csv = $this->get('/admin/requests/export')->assertOk()->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString(',=HYPERLINK', $csv);
    }

    public function test_only_admins_edit_prices_and_changes_are_logged(): void
    {
        $type = ProjectType::create(['key' => 't', 'kind' => 'website', 'name' => ['en' => 'Site'], 'base_price_minor' => 80000,
            'supports_pages' => true, 'min_pages' => 1, 'max_pages' => 20, 'included_pages' => 3, 'extra_page_price_minor' => 3500]);
        $payload = ['base_price' => '1250.50', 'extra_page_price' => '40', 'min_pages' => 1, 'max_pages' => 30, 'included_pages' => 5, 'is_active' => true];

        $this->loginAs('manager');
        $this->put("/admin/pricing/types/{$type->id}", $payload)->assertForbidden();

        $this->loginAs('admin');
        $this->put("/admin/pricing/types/{$type->id}", $payload)->assertRedirect();
        $type->refresh();
        $this->assertSame(125050, $type->base_price_minor);
        $this->assertSame(4000, $type->extra_page_price_minor);
        $log = ActivityLog::where('action', 'pricing.type_updated')->firstOrFail();
        $this->assertSame(80000, (int) $log->properties['old']['base_price_minor']);

        $this->put("/admin/pricing/types/{$type->id}", ['base_price' => 'abc'] + $payload)->assertSessionHasErrors('base_price');
        $this->put("/admin/pricing/types/{$type->id}", ['included_pages' => 99] + $payload)->assertSessionHasErrors('included_pages');
    }

    public function test_option_and_discount_management(): void
    {
        $this->loginAs('admin');
        $opt = ServiceOption::create(['code' => 'seo', 'group' => 'addon', 'name' => ['en' => 'SEO'], 'pricing_mode' => 'percent_bp', 'amount' => 1000]);
        $this->put("/admin/pricing/options/{$opt->id}", ['amount' => '12.5', 'max_quantity' => 1, 'is_active' => true])->assertRedirect();
        $this->assertSame(1250, $opt->fresh()->amount); // 12.5% stored as basis points

        $this->post('/admin/pricing/discounts', [
            'code' => 'spring-sale', 'name_en' => 'Spring', 'name_ar' => 'الربيع', 'type' => 'percent_bp', 'value' => '10',
            'condition_type' => 'min_subtotal', 'condition_value' => '500', 'max_discount' => '100', 'priority' => 5, 'is_active' => true,
        ])->assertRedirect();
        $rule = DiscountRule::where('code', 'spring-sale')->firstOrFail();
        $this->assertSame(1000, $rule->value);
        $this->assertSame(50000, $rule->condition_value);
        $this->assertSame(10000, $rule->max_discount_minor);
        $this->assertSame('0.00', Money::toDecimal(0));
    }

    public function test_notifications_can_be_marked_read_and_locale_switched(): void
    {
        $user = $this->loginAs('admin');
        $user->notify(new \App\Notifications\NewProjectRequestNotification($this->makeRequest()));
        $this->assertSame(1, $user->unreadNotifications()->count());

        $this->post('/admin/notifications/read')->assertRedirect();
        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());

        $this->post('/admin/locale', ['locale' => 'ar'])->assertRedirect();
        $this->get('/admin')->assertInertia(fn (Assert $p) => $p->where('locale', 'ar')->where('dir', 'rtl'));
    }
}
