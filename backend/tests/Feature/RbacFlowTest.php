<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class RbacFlowTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function authHeaders(string $username = 'admin01'): array
    {
        // Reset cached auth guard so Sanctum resolves the new token's user
        Auth::forgetGuards();

        $login = $this->postJson('/api/auth/login', [
            'username' => $username,
            'password' => 'password',
        ]);
        $login->assertOk();

        return ['Authorization' => 'Bearer ' . $login->json('data.token')];
    }

    public function test_each_role_can_login_and_get_correct_role_info(): void
    {
        $cases = [
            'admin01'   => 'system_admin',
            'hr01'      => 'hr_staff',
            'payroll01' => 'accountant',
            'manager01' => 'management',
            'emp001'    => 'employee',
        ];

        foreach ($cases as $username => $expectedRole) {
            $headers = $this->authHeaders($username);

            $me = $this->withHeaders($headers)->getJson('/api/me');
            $me->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.username', $username)
                ->assertJsonPath('data.role', $expectedRole);
        }
    }

    public function test_each_role_can_access_reference_endpoints(): void
    {
        $users = ['admin01', 'hr01', 'payroll01', 'manager01', 'emp001'];

        foreach ($users as $username) {
            $headers = $this->authHeaders($username);

            $response = $this->withHeaders($headers)->getJson('/api/reference/shifts');
            $response->assertOk()
                ->assertJsonPath('success', true);

            $this->assertIsArray($response->json('data'));
        }
    }

    public function test_all_authenticated_users_can_list_employees(): void
    {
        $users = ['admin01', 'hr01', 'payroll01', 'manager01', 'emp001'];

        foreach ($users as $username) {
            $headers = $this->authHeaders($username);

            $response = $this->withHeaders($headers)->getJson('/api/employees');
            $response->assertOk()
                ->assertJsonPath('success', true);
        }
    }

    public function test_all_authenticated_users_can_access_payroll_periods(): void
    {
        $users = ['admin01', 'hr01', 'payroll01', 'manager01', 'emp001'];

        foreach ($users as $username) {
            $headers = $this->authHeaders($username);

            $response = $this->withHeaders($headers)->getJson('/api/payroll/periods');
            $response->assertOk()
                ->assertJsonPath('success', true);
        }
    }

    public function test_all_authenticated_users_can_access_reports(): void
    {
        $users = ['admin01', 'hr01', 'payroll01', 'manager01', 'emp001'];

        foreach ($users as $username) {
            $headers = $this->authHeaders($username);

            $response = $this->withHeaders($headers)->getJson('/api/reports/templates');
            $response->assertOk()
                ->assertJsonPath('success', true);

            $this->assertIsArray($response->json('data'));
        }
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $endpoints = [
            '/api/employees',
            '/api/users',
            '/api/payroll/periods',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $response->assertUnauthorized()
                ->assertJsonPath('success', false);
        }
    }

    public function test_permissions_endpoint_returns_role_based_permissions(): void
    {
        $cases = [
            'admin01'   => 'system_admin',
            'hr01'      => 'hr_staff',
            'payroll01' => 'accountant',
            'manager01' => 'management',
            'emp001'    => 'employee',
        ];

        $permissionsByRole = [];

        foreach ($cases as $username => $expectedRole) {
            $headers = $this->authHeaders($username);

            $response = $this->withHeaders($headers)->getJson('/api/me/permissions');
            $response->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.role', $expectedRole);

            $permissions = $response->json('data.permissions');
            $this->assertIsArray($permissions);
            $this->assertNotEmpty($permissions);

            $permissionsByRole[$expectedRole] = $permissions;
        }

        // system_admin should have the most permissions
        $this->assertGreaterThan(
            count($permissionsByRole['employee']),
            count($permissionsByRole['system_admin']),
        );

        // hr_staff should have more than employee
        $this->assertGreaterThan(
            count($permissionsByRole['employee']),
            count($permissionsByRole['hr_staff']),
        );
    }
}
