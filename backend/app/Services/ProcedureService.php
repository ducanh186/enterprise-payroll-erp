<?php

namespace App\Services;

use App\Models\ProcedureCatalog;
use App\Models\ProcedureExecutionLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcedureService
{
    /**
     * List all active procedures with meta info.
     */
    public function listProcedures(): array
    {
        return ProcedureCatalog::query()
            ->active()
            ->with(['parameters', 'columns'])
            ->orderBy('module')
            ->orderBy('label')
            ->get()
            ->map(fn (ProcedureCatalog $proc) => $this->formatCatalogEntry($proc))
            ->values()
            ->all();
    }

    /**
     * Get full metadata for a single procedure by code.
     */
    public function getMeta(string $code): array
    {
        $proc = $this->findByCode($code);

        return $this->formatMeta($proc);
    }

    /**
     * Execute a stored procedure with validated, typed parameters.
     *
     * @return array{meta: array, records: array, row_count: int, execution_ms: int}
     */
    public function execute(string $code, array $inputParams, ?int $userId = null, ?string $ipAddress = null): array
    {
        $proc = $this->findByCode($code);
        $paramDefs = $proc->parameters;
        $columnDefs = $proc->columns;

        // Validate required params
        $errors = [];
        foreach ($paramDefs as $paramDef) {
            if ($paramDef->required && !isset($inputParams[$paramDef->name]) && $paramDef->default_value === null) {
                $errors[$paramDef->name] = ["Tham số {$paramDef->label} là bắt buộc."];
            }
        }
        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        // Build SP parameter list
        $spParams = [];
        $bindings = [];
        foreach ($paramDefs as $paramDef) {
            $value = $inputParams[$paramDef->name] ?? $paramDef->default_value;
            $spParams[] = $paramDef->sp_param_name . ' = ?';
            $bindings[] = $this->castValue($value, $paramDef->type);
        }

        $spCall = 'EXEC ' . $proc->procedure_name;
        if (!empty($spParams)) {
            $spCall .= ' ' . implode(', ', $spParams);
        }

        // Execute with timing
        $startMs = (int) (microtime(true) * 1000);
        $status = 'success';
        $errorMessage = null;
        $rows = [];

        try {
            $rows = DB::select($spCall, $bindings);
        } catch (\Throwable $e) {
            $status = 'error';
            $errorMessage = $e->getMessage();
        }

        $executionMs = (int) (microtime(true) * 1000) - $startMs;
        $rowCount = count($rows);

        // Log execution
        ProcedureExecutionLog::query()->create([
            'procedure_id' => $proc->id,
            'user_id' => $userId,
            'parameters' => $inputParams,
            'row_count' => $rowCount,
            'execution_ms' => $executionMs,
            'status' => $status,
            'error_message' => $errorMessage,
            'ip_address' => $ipAddress,
            'executed_at' => now(),
        ]);

        if ($status === 'error') {
            throw new \RuntimeException('Lỗi khi thực thi stored procedure: ' . $errorMessage);
        }

        // Convert rows to arrays
        $records = collect($rows)->map(fn ($row) => (array) $row)->all();

        // Build visible columns meta
        $visibleColumns = $columnDefs
            ->where('visible', true)
            ->map(fn ($col) => [
                'key' => $col->key,
                'label' => $col->label,
                'type' => $col->type,
            ])
            ->values()
            ->all();

        return [
            'procedure_code' => $proc->code,
            'procedure_label' => $proc->label,
            'columns' => $visibleColumns,
            'records' => $records,
            'row_count' => $rowCount,
            'execution_ms' => $executionMs,
            'generated_at' => now()->toISOString(),
        ];
    }

    // ---------------------------------------------------------------
    // Catalog Management (for admin)
    // ---------------------------------------------------------------

    public function createProcedure(array $data): ProcedureCatalog
    {
        return ProcedureCatalog::query()->create([
            'code' => $data['code'],
            'label' => $data['label'],
            'procedure_name' => $data['procedure_name'],
            'module' => $data['module'] ?? 'general',
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function updateProcedure(string $code, array $data): ProcedureCatalog
    {
        $proc = $this->findByCode($code);
        $proc->update($data);
        return $proc->fresh();
    }

    public function syncParameters(string $code, array $params): void
    {
        $proc = $this->findByCode($code);
        $proc->parameters()->delete();

        foreach ($params as $sortOrder => $param) {
            $proc->parameters()->create([
                'name' => $param['name'],
                'sp_param_name' => $param['sp_param_name'],
                'type' => $param['type'] ?? 'string',
                'label' => $param['label'] ?? $param['name'],
                'required' => $param['required'] ?? false,
                'default_value' => $param['default_value'] ?? null,
                'sort_order' => $param['sort_order'] ?? $sortOrder,
            ]);
        }
    }

    public function syncColumns(string $code, array $columns): void
    {
        $proc = $this->findByCode($code);
        $proc->columns()->delete();

        foreach ($columns as $sortOrder => $col) {
            $proc->columns()->create([
                'key' => $col['key'],
                'label' => $col['label'],
                'type' => $col['type'] ?? 'string',
                'visible' => $col['visible'] ?? true,
                'exportable' => $col['exportable'] ?? true,
                'sort_order' => $col['sort_order'] ?? $sortOrder,
            ]);
        }
    }

    // ---------------------------------------------------------------
    // Private Helpers
    // ---------------------------------------------------------------

    private function findByCode(string $code): ProcedureCatalog
    {
        $proc = ProcedureCatalog::query()
            ->active()
            ->with(['parameters', 'columns'])
            ->where('code', $code)
            ->first();

        if (!$proc) {
            abort(404, "Stored procedure '{$code}' không tồn tại hoặc đã bị tắt.");
        }

        return $proc;
    }

    private function formatCatalogEntry(ProcedureCatalog $proc): array
    {
        return [
            'code' => $proc->code,
            'label' => $proc->label,
            'module' => $proc->module,
            'description' => $proc->description,
            'param_count' => $proc->parameters->count(),
            'column_count' => $proc->columns->count(),
        ];
    }

    private function formatMeta(ProcedureCatalog $proc): array
    {
        return [
            'code' => $proc->code,
            'label' => $proc->label,
            'procedure' => $proc->procedure_name,
            'module' => $proc->module,
            'description' => $proc->description,
            'params' => $proc->parameters->map(fn ($p) => [
                'name' => $p->name,
                'sp_param_name' => $p->sp_param_name,
                'type' => $p->type,
                'label' => $p->label,
                'required' => $p->required,
                'default' => $p->default_value,
            ])->values()->all(),
            'columns' => $proc->columns->map(fn ($c) => [
                'key' => $c->key,
                'label' => $c->label,
                'type' => $c->type,
                'visible' => $c->visible,
                'exportable' => $c->exportable,
            ])->values()->all(),
        ];
    }

    private function castValue(mixed $value, string $type): mixed
    {
        if ($value === null || $value === '') {
            return match ($type) {
                'integer', 'tinyint' => 0,
                'boolean' => false,
                default => '',
            };
        }

        return match ($type) {
            'integer' => (int) $value,
            'tinyint' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'date' => (string) $value,
            default => (string) $value,
        };
    }
}
