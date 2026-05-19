<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReferenceFlowTest extends TestCase
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

    public function test_customer_salary_grade_detail_can_be_created_and_updated(): void
    {
        $this->createCustomerSalaryTables();
        $headers = $this->authHeaders();

        $create = $this->withHeaders($headers)->postJson('/api/reference/salary-grades/10/details', [
            'salary_type' => 'BASE_SAL',
            'amount' => 6500000,
            'description' => 'Luong co ban test',
        ]);

        $create->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.parent_id', '10')
            ->assertJsonPath('data.salary_type', 'BASE_SAL');

        $this->assertEquals(6500000.0, $create->json('data.amount'));

        $detailId = $create->json('data.id');
        $this->assertNotNull($detailId);

        $update = $this->withHeaders($headers)->putJson("/api/reference/salary-grade-details/{$detailId}", [
            'amount' => 7000000,
            'description' => 'Luong co ban updated',
        ]);

        $update->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.description', 'Luong co ban updated');

        $this->assertEquals(7000000.0, $update->json('data.amount'));

        $list = $this->withHeaders($headers)->getJson('/api/reference/salary-grades/10/details');

        $list->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'Luong co ban updated');
    }

    private function createCustomerSalaryTables(): void
    {
        Schema::create('D20SalaryScale', function (Blueprint $table) {
            $table->id('Id');
            $table->integer('ParentId')->default(-1);
            $table->boolean('IsGroup')->default(false);
            $table->boolean('IsActive')->default(true);
            $table->integer('CreatedBy')->default(-1);
            $table->timestamp('CreatedAt')->nullable();
            $table->integer('ModifiedBy')->default(-1);
            $table->timestamp('ModifiedAt')->nullable();
            $table->string('Code', 24)->unique();
            $table->string('Name', 256);
            $table->string('Description', 512)->nullable();
        });

        Schema::create('D20SalaryGrade', function (Blueprint $table) {
            $table->id('Id');
            $table->integer('ParentId')->default(-1);
            $table->boolean('IsGroup')->default(false);
            $table->boolean('IsActive')->default(true);
            $table->integer('CreatedBy')->default(-1);
            $table->timestamp('CreatedAt')->nullable();
            $table->integer('ModifiedBy')->default(-1);
            $table->timestamp('ModifiedAt')->nullable();
            $table->string('ScaleCode', 24);
            $table->date('EffectiveDate')->nullable();
            $table->integer('SalaryLevel')->default(1);
            $table->string('Description', 512)->nullable();
        });

        Schema::create('D20SalaryGradeDetail', function (Blueprint $table) {
            $table->id('Id');
            $table->integer('ParentId')->default(-1);
            $table->boolean('IsGroup')->default(false);
            $table->boolean('IsActive')->default(true);
            $table->integer('CreatedBy')->default(-1);
            $table->timestamp('CreatedAt')->nullable();
            $table->integer('ModifiedBy')->default(-1);
            $table->timestamp('ModifiedAt')->nullable();
            $table->string('SalaryType', 128)->default('');
            $table->decimal('Amount', 18, 2)->default(0);
            $table->string('Description', 512)->nullable();
            $table->string('RowId', 8)->nullable();
        });

        \DB::table('D20SalaryScale')->insert([
            'Id' => 1,
            'Code' => 'IT_SCALE',
            'Name' => 'IT',
        ]);

        \DB::table('D20SalaryGrade')->insert([
            'Id' => 10,
            'ScaleCode' => 'IT_SCALE',
            'EffectiveDate' => '2026-01-01',
            'SalaryLevel' => 1,
            'Description' => 'Level 1',
        ]);
    }
}
