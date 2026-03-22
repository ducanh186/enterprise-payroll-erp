<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeContractFlowTest extends TestCase
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

    public function test_list_employees_returns_data(): void
    {
        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)->getJson('/api/employees');

        $response->assertOk()
            ->assertJsonPath('success', true);

        // Paginated response: data is the items array, meta has pagination info
        $this->assertIsArray($response->json('data'));
        $this->assertNotEmpty($response->json('data'));
        $this->assertArrayHasKey('meta', $response->json());
        $this->assertArrayHasKey('total', $response->json('meta'));
        $this->assertArrayHasKey('per_page', $response->json('meta'));
        $this->assertArrayHasKey('current_page', $response->json('meta'));
        $this->assertGreaterThanOrEqual(15, $response->json('meta.total'));
    }

    public function test_view_employee_detail(): void
    {
        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)->getJson('/api/employees/1');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertSame(1, $data['id']);
        $this->assertSame('NV001', $data['employee_code']);
        $this->assertSame('Nguyen Van Admin', $data['full_name']);
        $this->assertArrayHasKey('department_name', $data);
        $this->assertArrayHasKey('position', $data);
        $this->assertArrayHasKey('email', $data);
        $this->assertArrayHasKey('phone', $data);
    }

    public function test_employee_not_found_returns_404(): void
    {
        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)->getJson('/api/employees/9999');

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_view_employee_active_contract(): void
    {
        $headers = $this->authHeaders();

        // Employee 1 (Admin) has an indefinite contract starting 2020-01-15 with no end_date
        $response = $this->withHeaders($headers)->getJson('/api/employees/1/active-contract');

        // The contract is active (start_date <= today, end_date is null)
        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertNotNull($data);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('contract_number', $data);
        $this->assertArrayHasKey('start_date', $data);
        $this->assertArrayHasKey('basic_salary', $data);
        $this->assertArrayHasKey('insurance_salary', $data);
        $this->assertArrayHasKey('is_probation', $data);
    }

    public function test_view_employee_dependents(): void
    {
        $headers = $this->authHeaders();

        // Employee 1 (Admin) has 2 dependents: wife and child
        $response = $this->withHeaders($headers)->getJson('/api/employees/1/dependents');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertCount(2, $data);

        // Check first dependent has expected fields
        $first = $data[0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('employee_id', $first);
        $this->assertArrayHasKey('full_name', $first);
        $this->assertArrayHasKey('relationship', $first);
        $this->assertArrayHasKey('date_of_birth', $first);
        $this->assertArrayHasKey('is_active', $first);
    }

    public function test_list_contracts(): void
    {
        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)->getJson('/api/contracts');

        $response->assertOk()
            ->assertJsonPath('success', true);

        // Paginated response: data is the items array
        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('meta', $response->json());
        $this->assertGreaterThanOrEqual(1, $response->json('meta.total'));

        // Each contract has expected fields
        $first = $data[0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('contract_number', $first);
        $this->assertArrayHasKey('employee_code', $first);
        $this->assertArrayHasKey('employee_name', $first);
        $this->assertArrayHasKey('start_date', $first);
        $this->assertArrayHasKey('basic_salary', $first);
    }

    public function test_view_contract_detail(): void
    {
        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)->getJson('/api/contracts/1');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertSame(1, $data['id']);
        $this->assertSame('HD-2020-001', $data['contract_number']);
        $this->assertSame('NV001', $data['employee_code']);
        $this->assertSame('Nguyen Van Admin', $data['employee_name']);
        $this->assertArrayHasKey('allowances', $data);
        $this->assertIsArray($data['allowances']);
        $this->assertArrayHasKey('insurance_salary', $data);
        $this->assertArrayHasKey('is_probation', $data);
        $this->assertFalse($data['is_probation']);
    }

    public function test_contract_not_found_returns_404(): void
    {
        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)->getJson('/api/contracts/9999');

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }
}
