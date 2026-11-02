<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_is_closed(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', ['name' => 'X', 'email' => 'x@example.com', 'password' => 'password-12345', 'password_confirmation' => 'password-12345'])->assertNotFound();
        $this->assertDatabaseMissing('users', ['email' => 'x@example.com']);
    }
}
