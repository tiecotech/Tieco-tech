<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->post('/_deploy/finish', [], ['X-Deploy-Token' => $token])->assertOk()->assertJson(['ok' => true]);
    }
}
