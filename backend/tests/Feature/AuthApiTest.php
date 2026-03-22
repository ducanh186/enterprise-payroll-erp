<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_user_can_login_fetch_profile_and_logout(): void
    {
        $loginResponse = $this->postJson('/api/auth/login', [
            'username' => 'admin01',
            'password' => 'password',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.username', 'admin01')
            ->assertJsonPath('data.user.role', 'system_admin');

        $token = $loginResponse->json('data.token');

        $this->assertNotEmpty($token);
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.username', 'admin01')
            ->assertJsonPath('data.role', 'system_admin');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(0, PersonalAccessToken::query()->count());
    }

    public function test_protected_api_requires_bearer_token(): void
    {
        $this->getJson('/api/employees')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_login_api_does_not_require_csrf_in_token_flow(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'username' => 'admin01',
            'password' => 'password',
        ]);

        $this->assertNotSame(419, $response->getStatusCode());

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.username', 'admin01');
    }
}
