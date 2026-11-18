<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DeployHookTest extends TestCase
{
    use RefreshDatabase;

    public function test_hook_is_hidden_without_a_configured_token(): void
    {
        config(['tieco.deploy_token' => null]);
        $this->post('/_deploy/finish')->assertNotFound();
    }

    public function test_hook_rejects_wrong_token_and_runs_with_the_right_one(): void
    {
        $token = str_repeat('a', 40);
        config(['tieco.deploy_token' => $token]);

        $this->post('/_deploy/finish', [], ['X-Deploy-Token' => 'wrong'])->assertNotFound();

        // Don't really run optimize here: it would write the test config into bootstrap/cache.
        Artisan::shouldReceive('call')->times(4)->andReturn(0);
        Artisan::shouldReceive('output')->andReturn('');
        $this->post('/_deploy/finish', [], ['X-Deploy-Token' => $token])->assertOk()->assertJson(['ok' => true]);
    }
}
