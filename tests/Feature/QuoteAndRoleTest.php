<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ProjectRequest;
use App\Models\Quote;
use App\Models\RequestStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class QuoteAndRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_issued_quote_cannot_be_edited_but_status_can_change(): void
    {
        $status = RequestStatus::create(['key' => 'new', 'name' => ['en' => 'New'], 'is_default' => true]);
        $req = ProjectRequest::create([
            'number' => ProjectRequest::generateNumber(), 'project_type_snapshot' => ['key' => 'x'],
            'request_status_id' => $status->id, 'customer_name' => 'Test', 'currency' => 'USD', 'pricing_snapshot' => [],
        ]);
        $quote = Quote::create(['project_request_id' => $req->id, 'number' => 'Q-1', 'currency' => 'USD', 'total_minor' => 1000]);

        $quote->update(['total_minor' => 2000]); // draft: allowed
        $quote->update(['status' => 'sent']);    // issue

        $quote->update(['status' => 'accepted']); // status change allowed
        $this->expectException(LogicException::class);
        $quote->update(['total_minor' => 3000]);  // locked
    }

    public function test_roles_follow_least_privilege(): void
    {
        $this->assertTrue(Role::Admin->can('anything'));
        $this->assertTrue(Role::Manager->can('quotes.manage'));
        $this->assertFalse(Role::Manager->can('content.manage'));
        $this->assertFalse(Role::Editor->can('requests.view'));
        $this->assertFalse(Role::Viewer->can('requests.manage'));
    }

    public function test_request_numbers_are_unique_and_formatted(): void
    {
        $a = ProjectRequest::generateNumber();
        $this->assertMatchesRegularExpression('/^TT-\d{6}-[A-Z0-9]{5}$/', $a);
    }
}
