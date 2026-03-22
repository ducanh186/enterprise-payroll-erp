<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
