<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFlowTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function authHeaders(string $username = 'admin01'): array
    {
        $login = $this->postJson('/api/auth/login', [
            'username' => $username,
            'password' => 'password',
        ]);
        $login->assertOk();

        return ['Authorization' => 'Bearer ' . $login->json('data.token')];
    }

    public function test_admin_can_list_users(): void
    {
        $headers = $this->authHeaders('admin01');

        $response = $this->withHeaders($headers)->getJson('/api/users');
        $response->assertOk()
            ->assertJsonPath('success', true);

        $users = $response->json('data');
        $this->assertIsArray($users);
        $this->assertNotEmpty($users);

        // Seeded database has 15 users -- verify some are present
        $usernames = array_column($users, 'username');
        $this->assertContains('admin01', $usernames);
        $this->assertContains('hr01', $usernames);
        $this->assertContains('emp001', $usernames);
    }

    public function test_admin_can_create_user(): void
    {
        $headers = $this->authHeaders('admin01');

        $payload = [
            'username' => 'newuser01',
            'name'     => 'Nguyen Van Test',
            'email'    => 'newuser01@erp.vn',
            'password' => 'SecurePass123!',
            'role'     => 'hr_staff',
        ];

        $response = $this->withHeaders($headers)->postJson('/api/users', $payload);
        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.username', 'newuser01')
            ->assertJsonPath('data.name', 'Nguyen Van Test')
            ->assertJsonPath('data.email', 'newuser01@erp.vn');
    }

    public function test_admin_create_user_validation_fails_without_required_fields(): void
    {
        $headers = $this->authHeaders('admin01');

        $response = $this->withHeaders($headers)->postJson('/api/users', []);
        $response->assertStatus(422);
    }

    public function test_admin_can_update_user(): void
    {
        $headers = $this->authHeaders('admin01');

        $response = $this->withHeaders($headers)->putJson('/api/users/6', [
            'name' => 'Updated Name',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Name');
    }

    public function test_admin_can_reset_password(): void
    {
        $headers = $this->authHeaders('admin01');

        $response = $this->withHeaders($headers)->postJson('/api/users/6/reset-password');
        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user_id', 6)
            ->assertJsonPath('data.must_change_password', true);

        $this->assertNotEmpty($response->json('data.temporary_password'));
    }

    public function test_admin_can_list_roles(): void
    {
        $headers = $this->authHeaders('admin01');

        $response = $this->withHeaders($headers)->getJson('/api/roles');
        $response->assertOk()
            ->assertJsonPath('success', true);

        $roles = $response->json('data');
        $this->assertIsArray($roles);
        $this->assertNotEmpty($roles);

        // Check that seeded roles are present
        $roleCodes = array_column($roles, 'value');
        $this->assertContains('system_admin', $roleCodes);
        $this->assertContains('hr_staff', $roleCodes);
        $this->assertContains('accountant', $roleCodes);
        $this->assertContains('management', $roleCodes);
        $this->assertContains('employee', $roleCodes);
    }

    public function test_admin_can_assign_roles(): void
    {
        $headers = $this->authHeaders('admin01');

        $response = $this->withHeaders($headers)->postJson('/api/users/6/roles', [
            'roles' => ['hr_staff', 'management'],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user_id', 6);

        $assignedRoles = $response->json('data.roles');
        $this->assertIsArray($assignedRoles);
        $this->assertContains('hr_staff', $assignedRoles);
        $this->assertContains('management', $assignedRoles);
    }
}
