<?php

namespace Tests\Feature;

use App\Services\ExcelWorkbookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AttendanceImportTest extends TestCase
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

    public function test_import_checkin_checkout_excel(): void
    {
        $path = storage_path('app/testing-checkin.xlsx');
        app(ExcelWorkbookService::class)->writeRows($path, [
            ['Thời gian', 'Mã NV'],
            ['2026-01-05 08:00:00', 'NV001'],
            ['2026-01-05 17:30:00', 'NV001'],
        ]);

        $response = $this
            ->withHeaders($this->authHeaders())
            ->post('/api/attendance/checkin-logs/import', [
                'file' => UploadedFile::fake()->createWithContent(
                    'Data checkinout.xlsx',
                    file_get_contents($path)
                ),
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.imported', 2)
            ->assertJsonPath('data.skipped', 0);

        $this->assertSame(2, DB::table('time_logs')->where('source', 'excel')->count());
    }
}
