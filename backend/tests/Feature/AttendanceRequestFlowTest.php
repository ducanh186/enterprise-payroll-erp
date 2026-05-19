<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttendanceRequestFlowTest extends TestCase
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

    public function test_create_attendance_request_returns_pending(): void
    {
        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)->postJson('/api/attendance/requests', [
            'employee_id' => 1,
            'request_type' => 'leave',
            'request_date' => '2026-03-10',
            'reason' => 'Personal leave',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertEquals('pending', $data['status']);
    }

    public function test_approve_request_changes_status(): void
    {
        $headers = $this->authHeaders();

        // Create a new request first
        $create = $this->withHeaders($headers)->postJson('/api/attendance/requests', [
            'employee_id' => 1,
            'request_type' => 'leave',
            'request_date' => '2026-03-11',
            'reason' => 'Family event',
        ]);

        $create->assertStatus(201);
        $requestId = $create->json('data.id');
        $this->assertNotNull($requestId);

        // Approve it
        $approve = $this->withHeaders($headers)->postJson(
            "/api/attendance/requests/{$requestId}/approve",
            ['note' => 'Approved by admin']
        );

        $approve->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEquals('approved', $approve->json('data.status'));
    }

    public function test_reject_request_changes_status(): void
    {
        $headers = $this->authHeaders();

        // Create a new request first
        $create = $this->withHeaders($headers)->postJson('/api/attendance/requests', [
            'employee_id' => 1,
            'request_type' => 'leave',
            'request_date' => '2026-03-12',
            'reason' => 'Doctor appointment',
        ]);

        $create->assertStatus(201);
        $requestId = $create->json('data.id');
        $this->assertNotNull($requestId);

        // Reject it
        $reject = $this->withHeaders($headers)->postJson(
            "/api/attendance/requests/{$requestId}/reject",
            ['note' => 'Insufficient reason']
        );

        $reject->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEquals('rejected', $reject->json('data.status'));
    }

    public function test_list_requests_returns_all_requests(): void
    {
        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)->getJson('/api/attendance/requests');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
    }

    public function test_list_requests_filter_by_status(): void
    {
        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)->getJson('/api/attendance/requests?status=pending');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertIsArray($data);

        foreach ($data as $request) {
            $this->assertEquals('pending', $request['status']);
        }
    }

    public function test_view_single_request_detail(): void
    {
        $headers = $this->authHeaders();

        // Use seeded request id=1 (annual_leave, approved)
        $response = $this->withHeaders($headers)->getJson('/api/attendance/requests/1');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('employee_id', $data);
        $this->assertArrayHasKey('request_type', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('reason', $data);
        $this->assertEquals(1, $data['id']);
    }

    public function test_customer_leave_request_create_update_and_detail_persist_to_d30_tables(): void
    {
        $this->createCustomerLeaveTables();
        $headers = $this->authHeaders();

        $create = $this->withHeaders($headers)->postJson('/api/attendance/requests', [
            'doc_type' => 'AL',
            'employee_code' => 'HANPN',
            'manager_code' => 'MANAGER',
            'request_date' => '2026-01-12',
            'to_date' => '2026-01-12',
            'reason' => 'Xin nghi buoi sang',
            'working_hours' => 4,
            'working_days' => 0.5,
            'absence_type' => 1,
        ]);

        $create->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.doc_type', 'AL')
            ->assertJsonPath('data.employee_code', 'HANPN');

        $this->assertEquals(4.0, $create->json('data.details.0.working_hours'));

        $id = $create->json('data.id');
        $docId = $create->json('data.doc_id');

        $this->assertDatabaseHas('D30AttendanceDoc', [
            'Id' => $id,
            'DocId' => $docId !== '' ? $docId : null,
            'DocType' => 'AL',
            'EmployeeCode' => 'HANPN',
            'Description' => 'Xin nghi buoi sang',
        ]);

        $update = $this->withHeaders($headers)->putJson("/api/attendance/requests/{$id}", [
            'doc_type' => 'AL',
            'employee_code' => 'HANPN',
            'manager_code' => 'MANAGER',
            'request_date' => '2026-01-13',
            'to_date' => '2026-01-13',
            'reason' => 'Da sua ly do',
            'working_hours' => 8,
            'working_days' => 1,
            'absence_type' => 2,
            'detail_description' => 'Nghi ca ngay',
        ]);

        $update->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reason', 'Da sua ly do')
            ->assertJsonPath('data.details.0.description', 'Nghi ca ngay');

        $this->assertEquals(1.0, $update->json('data.details.0.working_days'));

        $show = $this->withHeaders($headers)->getJson("/api/attendance/requests/{$id}");

        $show->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.reason', 'Da sua ly do')
            ->assertJsonPath('data.details.0.work_date', '2026-01-13');

        $this->assertDatabaseHas('D30AbsenceDetail', [
            'DocId' => $docId,
            'Date' => '2026-01-13',
            'Description' => 'Nghi ca ngay',
            'AbsenceType' => 2,
        ]);
    }

    private function createCustomerLeaveTables(): void
    {
        Schema::create('D30AttendanceDoc', function (Blueprint $table) {
            $table->id('Id');
            $table->string('DocId', 6)->nullable();
            $table->string('DocNo', 32);
            $table->date('DocDate')->nullable();
            $table->string('DocType', 2)->default('');
            $table->string('EmployeeCode', 16)->default('');
            $table->string('ManagerCode', 16)->default('');
            $table->string('Description', 256)->default('');
            $table->boolean('IsActive')->default(true);
            $table->integer('CreatedBy')->default(-1);
            $table->timestamp('CreatedAt')->nullable();
            $table->integer('ModifiedBy')->default(-1);
            $table->timestamp('ModifiedAt')->nullable();
        });

        Schema::create('D30AbsenceDetail', function (Blueprint $table) {
            $table->id('Id');
            $table->string('RowId', 8)->nullable();
            $table->string('DocId', 32)->default('');
            $table->date('Date')->nullable();
            $table->decimal('WorkingHours', 8, 2)->default(0);
            $table->decimal('WorkingDays', 8, 2)->default(0);
            $table->string('Description', 256)->nullable();
            $table->boolean('IsActive')->default(true);
            $table->integer('CreatedBy')->default(-1);
            $table->timestamp('CreatedAt')->nullable();
            $table->integer('ModifiedBy')->default(-1);
            $table->timestamp('ModifiedAt')->nullable();
            $table->integer('AbsenceType')->nullable();
        });

        Schema::create('D20Employee', function (Blueprint $table) {
            $table->id('Id');
            $table->string('Code', 16)->unique();
            $table->string('FullName', 256);
        });

        DB::table('D20Employee')->insert([
            'Code' => 'HANPN',
            'FullName' => 'Phạm Ngọc Hân',
        ]);
    }
}
