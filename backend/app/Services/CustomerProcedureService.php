<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\ConnectionInterface;
use PDO;
use RuntimeException;

class CustomerProcedureService
{
    /**
     * @param array<string, mixed> $parameters keyed by SQL parameter name, including @.
     * @return array{available: bool, procedure: string, result_sets: array<int, array<int, array<string, mixed>>>, row_count: int, execution_ms: int, error: ?string}
     */
    public function execute(string $procedureName, array $parameters): array
    {
        $this->assertProcedureName($procedureName);
        $connection = $this->connection();

        if ($connection->getDriverName() !== 'sqlsrv') {
            return $this->unavailable($procedureName, 'SQL Server connection is not active.');
        }

        if (!$this->procedureExists($connection, $procedureName)) {
            return $this->unavailable($procedureName, "Stored procedure {$procedureName} was not found.");
        }

        $sqlParams = [];
        $bindings = [];
        foreach ($parameters as $name => $value) {
            $this->assertParameterName($name);
            $sqlParams[] = "{$name} = ?";
            $bindings[] = $value;
        }

        $sql = 'EXEC ' . $procedureName . ($sqlParams ? ' ' . implode(', ', $sqlParams) : '');
        $startedAt = (int) (microtime(true) * 1000);

        try {
            $statement = $connection->getPdo()->prepare($sql);
            $statement->execute($bindings);

            $sets = [];
            do {
                $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
                if ($rows !== false && $rows !== []) {
                    $sets[] = $rows;
                }
            } while ($statement->nextRowset());

            return [
                'available' => true,
                'procedure' => $procedureName,
                'result_sets' => $sets,
                'row_count' => array_sum(array_map('count', $sets)),
                'execution_ms' => (int) (microtime(true) * 1000) - $startedAt,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'available' => true,
                'procedure' => $procedureName,
                'result_sets' => [],
                'row_count' => 0,
                'execution_ms' => (int) (microtime(true) * 1000) - $startedAt,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function connection(): ConnectionInterface
    {
        $customerDatabase = config('database.connections.customer_sqlsrv.database');

        if ($customerDatabase && DB::connection()->getDriverName() === 'sqlsrv') {
            return DB::connection('customer_sqlsrv');
        }

        return DB::connection();
    }

    private function procedureExists(ConnectionInterface $connection, string $procedureName): bool
    {
        try {
            $row = $connection->selectOne('SELECT OBJECT_ID(?) AS object_id', [$procedureName]);
            return !empty($row?->object_id);
        } catch (\Throwable) {
            return false;
        }
    }

    private function unavailable(string $procedureName, string $reason): array
    {
        return [
            'available' => false,
            'procedure' => $procedureName,
            'result_sets' => [],
            'row_count' => 0,
            'execution_ms' => 0,
            'error' => $reason,
        ];
    }

    private function assertProcedureName(string $procedureName): void
    {
        if (!preg_match('/^(dbo\.)?[A-Za-z_][A-Za-z0-9_]*$/', $procedureName)) {
            throw new RuntimeException('Unsafe stored procedure name.');
        }
    }

    private function assertParameterName(string $name): void
    {
        if (!preg_match('/^@[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            throw new RuntimeException('Unsafe stored procedure parameter name.');
        }
    }
}
