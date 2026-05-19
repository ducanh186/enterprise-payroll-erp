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
        $this->assertArrayHasKey('gender', $data);
        $this->assertArrayHasKey('birth_date', $data);
        $this->assertArrayHasKey('id_card_no', $data);
        $this->assertArrayHasKey('mobile', $data);
        $this->assertArrayHasKey('resign_date', $data);
    }

    public function test_create_update_and_suspend_employee_persists_to_database(): void
    {
        $headers = $this->authHeaders();

        $create = $this->withHeaders($headers)->postJson('/api/employees', [
            'employee_code' => 'NV999',
            'full_name' => 'Nguyen Thi Test',
            'gender' => 'female',
            'birth_date' => '1999-01-15',
            'id_card_no' => '079199900001',
            'email' => 'nv999@fujimart.local',
            'mobile' => '0909999999',
            'hire_date' => '2026-01-01',
            'resign_date' => null,
            'status' => 'active',
        ]);

        $create->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.employee_code', 'NV999')
            ->assertJsonPath('data.birth_date', '1999-01-15')
            ->assertJsonPath('data.id_card_no', '079199900001')
            ->assertJsonPath('data.mobile', '0909999999');

        $id = $create->json('data.id');

        $update = $this->withHeaders($headers)->putJson("/api/employees/{$id}", [
            'full_name' => 'Nguyen Thi Test Updated',
            'mobile' => '0911111111',
            'resign_date' => '2026-12-31',
        ]);

        $update->assertOk()
            ->assertJsonPath('data.full_name', 'Nguyen Thi Test Updated')
            ->assertJsonPath('data.mobile', '0911111111')
            ->assertJsonPath('data.resign_date', '2026-12-31');

        $suspend = $this->withHeaders($headers)->postJson("/api/employees/{$id}/suspend");

        $suspend->assertOk()
            ->assertJsonPath('data.status', 'inactive')
            ->assertJsonPath('data.active_status', false);
    }

    public function test_employee_dependent_can_be_created_and_updated_from_detail_tab(): void
    {
        $headers = $this->authHeaders();

        $create = $this->withHeaders($headers)->postJson('/api/employees/1/dependents', [
            'full_name' => 'Nguyen Dependent Test',
            'relationship' => 'Con',
            'date_of_birth' => '2020-02-20',
            'identity_number' => '020202020202',
            'tax_deduction_from' => '2026-01-01',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.full_name', 'Nguyen Dependent Test')
            ->assertJsonPath('data.date_of_birth', '2020-02-20');

        $dependentId = $create->json('data.id');

        $update = $this->withHeaders($headers)->putJson("/api/employees/1/dependents/{$dependentId}", [
            'full_name' => 'Nguyen Dependent Updated',
            'relationship' => 'Con ruot',
        ]);

        $update->assertOk()
            ->assertJsonPath('data.full_name', 'Nguyen Dependent Updated')
            ->assertJsonPath('data.relationship', 'Con ruot');
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

    public function test_update_contract_uses_salary_level_id_not_base_salary_for_grade_code(): void
    {
        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)->putJson('/api/contracts/1', [
            'salary_level_id' => 2,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.salary_level_id', 2);

        $data = $response->json('data');
        $this->assertArrayHasKey('salary_level_code', $data);
        $this->assertNotEmpty($data['salary_level_code']);

        $this->assertDatabaseHas('labour_contracts', [
            'id' => 1,
            'salary_level_id' => 2,
        ]);
    }

    public function test_contract_not_found_returns_404(): void
    {
        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)->getJson('/api/contracts/9999');

        $response->assertNotFound()
            ->assertJsonPath('success', false);
    }
}
