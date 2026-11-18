<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SupportPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['tieco.force_live' => true]);
        Storage::fake('public');
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => 'admin', 'is_active' => true])->save();

        return $user;
    }

    public function test_page_is_hidden_until_enabled_with_details(): void
    {
        $this->get('/ar/support')->assertNotFound();

        $this->actingAs($this->admin())->put('/admin/content/support', ['enabled' => true, 'bank' => ['enabled' => false]])->assertRedirect();
        auth()->logout();
        $this->get('/ar/support')->assertNotFound(); // on, but no method has details
    }

    public function test_admin_fills_details_and_uploads_qr_then_page_shows_them(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->put('/admin/content/support', [
            'enabled' => true,
            'bank' => ['enabled' => true, 'bank_name' => 'Al Baraka', 'holder' => 'Tieco', 'account' => ' 123-456 ', 'branch' => ''],
            'shamcash' => ['enabled' => true, 'holder' => 'Tieco', 'account' => 'SC1'],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->post('/admin/content/support/qr', ['qr' => UploadedFile::fake()->image('qr.png', 300, 300)])->assertRedirect();

        $this->get('/admin/content/support')->assertInertia(fn (Assert $p) => $p->where('support.bank.account', '123-456')->whereNot('support.shamcash.qr_url', null));
        auth()->logout();

        $this->get('/en/support')->assertOk()->assertInertia(fn (Assert $p) => $p->component('support')
            ->where('bank.account', '123-456')->where('shamcash.account', 'SC1')->whereNot('shamcash.qr', null)
            ->where('site.support', true));

        // Disabling bank keeps the page with Sham Cash only.
        $this->actingAs($admin)->put('/admin/content/support', ['enabled' => true, 'bank' => ['enabled' => false], 'shamcash' => ['enabled' => true, 'account' => 'SC1']]);
        $this->get('/en/support')->assertInertia(fn (Assert $p) => $p->where('bank', null)->has('shamcash'));
    }

    public function test_editor_without_content_permission_is_blocked(): void
    {
        $viewer = User::factory()->create();
        $viewer->forceFill(['role' => 'viewer', 'is_active' => true])->save();
        $this->actingAs($viewer)->put('/admin/content/support', ['enabled' => true])->assertForbidden();
    }
}
