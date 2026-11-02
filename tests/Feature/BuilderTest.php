<?php

namespace Tests\Feature;

use App\Models\DiscountRule;
use App\Models\ProjectRequest;
use App\Models\ProjectType;
use App\Models\ServiceOption;
use Database\Seeders\RequestStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BuilderTest extends TestCase
{
    use RefreshDatabase;

    private ProjectType $type;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RequestStatusSeeder::class);
        Storage::fake('local');
        config(['tieco.uploads.disk' => 'local']);

        $this->type = ProjectType::create([
            'key' => 'company_site', 'kind' => 'website', 'name' => ['en' => 'Company website', 'ar' => 'موقع شركة'],
            'base_price_minor' => 100000, 'supports_pages' => true, 'min_pages' => 1, 'max_pages' => 20,
            'included_pages' => 3, 'extra_page_price_minor' => 5000, 'is_active' => true,
        ]);
    }

    private function payload(array $o = []): array
    {
        return $o + [
            'project_type_id' => $this->type->id, 'pages' => 8,
            'customer_name' => 'Test Customer', 'phone' => '+963992355844', 'email' => 'c@example.com',
            'preferred_contact' => 'whatsapp', 'description' => 'We need a company website with a blog.',
        ];
    }

    public function test_builder_page_lists_active_types_with_options(): void
    {
        $opt = ServiceOption::create(['code' => 'seo', 'group' => 'addon', 'name' => ['en' => 'SEO'], 'amount' => 12000]);
        $this->type->options()->attach($opt->id);

        $this->get('/en/start')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('builder')->has('types', 1)->where('types.0.key', 'company_site')
            ->has('types.0.options', 1)->where('currency.code', 'USD'));
    }

    public function test_estimate_endpoint_prices_on_the_server(): void
    {
        $this->postJson('/en/start/estimate', ['project_type_id' => $this->type->id, 'pages' => 8])
            ->assertOk()->assertJsonPath('total_minor', 125000);

        $this->postJson('/en/start/estimate', ['project_type_id' => $this->type->id, 'pages' => 99])
            ->assertStatus(422)->assertJsonPath('errors.pages', 'Pages must be between 1 and 20.');
    }

    public function test_submit_saves_request_snapshot_items_and_private_files(): void
    {
        DiscountRule::create(['code' => 'wa', 'name' => ['en' => 'WhatsApp'], 'type' => 'percent_bp', 'value' => 500,
            'condition_type' => 'whatsapp_phone', 'is_active' => true, 'stackable' => true]);

        $response = $this->post('/en/start', $this->payload([
            'whatsapp_opt_in' => true,
            'total_minor' => 1, // tampering attempt: must be ignored
            'attachments' => [UploadedFile::fake()->create('brief.pdf', 100, 'application/pdf')],
        ]));

        $response->assertRedirect('/en/start/received');
        $req = ProjectRequest::with(['items', 'attachments'])->firstOrFail();

        $this->assertSame(125000, $req->subtotal_minor);
        $this->assertSame(6250, $req->discount_minor); // 5% of 125000
        $this->assertSame(118750, $req->total_minor);
        $this->assertSame('USD', $req->currency);
        $this->assertSame('new', $req->status->key);
        $this->assertMatchesRegularExpression('/^TT-\d{6}-[A-Z0-9]{5}$/', $req->number);
        $this->assertSame(1, $req->items->where('kind', 'discount')->count());
        $this->assertSame(-6250, $req->items->firstWhere('kind', 'discount')->line_total_minor);

        $file = $req->attachments->first();
        $this->assertSame('brief.pdf', $file->original_name);
        $this->assertNotSame('brief.pdf', basename($file->path));
        Storage::disk('local')->assertExists($file->path);
    }

    public function test_old_requests_keep_their_prices_when_prices_change(): void
    {
        $this->post('/en/start', $this->payload());
        $this->type->update(['base_price_minor' => 999999]);

        $req = ProjectRequest::firstOrFail();
        $this->assertSame(125000, $req->total_minor);
        $this->assertSame(125000, $req->pricing_snapshot['total_minor']);
    }

    public function test_whatsapp_discount_is_not_applied_without_opt_in(): void
    {
        DiscountRule::create(['code' => 'wa', 'name' => ['en' => 'WhatsApp'], 'type' => 'fixed', 'value' => 5000,
            'condition_type' => 'whatsapp_phone', 'is_active' => true]);

        $this->post('/en/start', $this->payload(['whatsapp_opt_in' => false]));
        $this->assertSame(0, ProjectRequest::firstOrFail()->discount_minor);
    }

    public function test_validation_blocks_bad_input_and_bots(): void
    {
        $this->post('/en/start', $this->payload(['customer_name' => '']))->assertSessionHasErrors('customer_name');
        $this->post('/en/start', $this->payload(['phone' => null, 'email' => null]))->assertSessionHasErrors(['phone', 'email']);
        $this->post('/en/start', $this->payload(['whatsapp_opt_in' => true, 'phone' => '0992355844']))->assertSessionHasErrors('phone');
        $this->post('/en/start', $this->payload(['website' => 'http://spam.example']))->assertSessionHasErrors('website');
        $this->post('/en/start', $this->payload(['attachments' => [UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')]]))
            ->assertSessionHasErrors('attachments.0');
        $this->post('/en/start', $this->payload(['pages' => 99]))->assertSessionHasErrors('pages');

        $this->assertSame(0, ProjectRequest::count());
    }

    public function test_mobile_apps_require_a_platform(): void
    {
        $app = ProjectType::create(['key' => 'mobile_app', 'kind' => 'mobile', 'name' => ['en' => 'App'], 'base_price_minor' => 100000, 'is_active' => true]);
        $ios = ServiceOption::create(['code' => 'ios', 'group' => 'platform', 'exclusive_group' => 'platform', 'name' => ['en' => 'iOS'], 'amount' => 170000]);
        $app->options()->attach($ios->id);

        $this->post('/en/start', $this->payload(['project_type_id' => $app->id, 'pages' => null]))->assertSessionHasErrors('options');
        $this->post('/en/start', $this->payload(['project_type_id' => $app->id, 'pages' => null, 'options' => [$ios->id => 1]]))->assertRedirect('/en/start/received');
        $this->assertSame(270000, ProjectRequest::firstOrFail()->total_minor);
    }

    public function test_received_page_needs_a_submission(): void
    {
        $this->get('/en/start/received')->assertRedirect('/en/start');

        $this->post('/en/start', $this->payload());
        $this->get('/en/start/received')->assertOk()->assertInertia(fn (Assert $p) => $p
            ->component('builder-received')->where('submitted.total_minor', 125000));
    }

    public function test_staff_get_a_dashboard_notification(): void
    {
        $admin = \App\Models\User::factory()->create();
        $admin->forceFill(['role' => 'admin', 'is_active' => true])->save();

        $this->post('/en/start', $this->payload());
        $this->assertSame(1, $admin->fresh()->notifications()->count());
    }
}
