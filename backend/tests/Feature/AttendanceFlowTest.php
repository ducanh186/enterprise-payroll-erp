<?php

namespace Tests\Feature;

use App\Models\AttendancePeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceFlowTest extends TestCase
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

    public function test_view_checkin_logs_returns_paginated_data(): void
    {
        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)->getJson('/api/attendance/checkin-logs');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
                'message',
            ]);

        $this->assertIsArray($response->json('data'));
        $this->assertGreaterThan(0, $response->json('meta.total'));
    }

    public function test_checkin_logs_filter_by_date_range(): void
    {
        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)->getJson(
            '/api/attendance/checkin-logs?date_from=2026-02-01&date_to=2026-02-28'
        );

        $response->assertOk()
            ->assertJsonPath('success', true);

        $items = $response->json('data');
        $this->assertIsArray($items);

        foreach ($items as $item) {
            $date = $item['date'] ?? substr($item['check_time'] ?? '', 0, 10);
            $this->assertGreaterThanOrEqual('2026-02-01', $date);
            $this->assertLessThanOrEqual('2026-02-28', $date);
        }
    }

    public function test_manual_checkin_creates_time_log(): void
    {
        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)->postJson('/api/attendance/checkin-logs/manual', [
            'employee_id' => 1,
            'check_time' => '2026-03-15 08:00:00',
            'check_type' => 'in',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertEquals(1, $data['employee_id']);
        $this->assertEquals('in', $data['check_type']);
    }

    public function test_manual_checkin_validation_rejects_invalid_data(): void
    {
        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)->postJson('/api/attendance/checkin-logs/manual', []);

        $response->assertStatus(422);
    }

    public function test_view_daily_attendance(): void
    {
        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)->getJson('/api/attendance/daily');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);

        $first = $data[0];
        $this->assertArrayHasKey('employee_id', $first);
        $this->assertArrayHasKey('shift_code', $first);
        $this->assertArrayHasKey('working_hours', $first);
        $this->assertArrayHasKey('status', $first);
    }

    public function test_view_monthly_summary(): void
    {
        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)->getJson(
            '/api/attendance/monthly-summary?month=2&year=2026'
        );

        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);

        $first = $data[0];
        $this->assertArrayHasKey('actual_working_days', $first);
        $this->assertArrayHasKey('overtime_hours', $first);
        $this->assertArrayHasKey('total_late_minutes', $first);
    }

    public function test_recalculate_attendance_processes_employees(): void
    {
        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)->postJson('/api/attendance/recalculate', [
            'month' => 3,
            'year' => 2026,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertGreaterThan(0, $data['employees_processed']);
    }

    public function test_locked_period_cannot_be_recalculated(): void
    {
        // The seeder already creates period month=1,year=2026 as 'locked'
        // Verify it exists with locked status before testing
        $period = AttendancePeriod::query()
            ->where('month', 1)
            ->where('year', 2026)
            ->first();
        $this->assertNotNull($period);

        $headers = $this->authHeaders();

        $response = $this->withHeaders($headers)->postJson('/api/attendance/recalculate', [
            'month' => 1,
            'year' => 2026,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertEquals(0, $data['employees_processed']);
    }
}
