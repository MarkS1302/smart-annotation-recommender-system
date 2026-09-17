<?php

namespace App\Support\AiRequests;

use Illuminate\Support\Arr;
use PDO;

class SqliteKnowledgeBasePreview
{
    /**
     * @return array<int, array{name: string, columns: array<int, string>, rows: array<int, array<string, mixed>>}>
     */
    public function tables(string $path, int $rowLimit = 20): array
    {
        $database = new PDO(sprintf('sqlite:%s', $path));
        $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $tableNames = $database
            ->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'")
            ->fetchAll(PDO::FETCH_COLUMN);

        return collect($tableNames)
            ->map(function (mixed $tableName) use ($database, $rowLimit): array {
                $name = (string) $tableName;
                $quotedName = $this->quoteIdentifier($name);
                $columns = $database
                    ->query(sprintf('PRAGMA table_info(%s)', $quotedName))
                    ->fetchAll(PDO::FETCH_ASSOC);

                return [
                    'name' => $name,
                    'columns' => array_map(
                        static fn (array $column): string => (string) Arr::get($column, 'name'),
                        $columns,
                    ),
                    'rows' => $database
                        ->query(sprintf('SELECT * FROM %s LIMIT %d', $quotedName, $rowLimit))
                        ->fetchAll(PDO::FETCH_ASSOC),
                ];
            })
            ->values()
            ->all();
    }

    private function quoteIdentifier(string $identifier): string
    {
        return sprintf('"%s"', str_replace('"', '""', $identifier));
    }
}
