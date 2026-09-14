<?php

namespace App\Support\AiRequests;

use PDO;
use RuntimeException;

class SqliteKnowledgeBaseReader
{
    /**
     * @return array{tag_column: string, tables: array<int, array{name: string, columns: array<int, string>, rows: array<int, array<string, mixed>>}>}
     */
    public function read(string $path, string $tagColumn): array
    {
        $database = new PDO(sprintf('sqlite:%s', $path));
        $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $database->exec('PRAGMA query_only = ON');

        $tables = $database
            ->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'")
            ->fetchAll(PDO::FETCH_COLUMN);
        $knowledgeBaseTables = [];
        $hasTagColumn = false;

        foreach ($tables as $table) {
            $quotedTable = $this->quoteIdentifier((string) $table);
            $columns = $database->query(sprintf('PRAGMA table_info(%s)', $quotedTable))->fetchAll(PDO::FETCH_ASSOC);
            $column = collect($columns)->first(
                fn (array $candidate): bool => strcasecmp((string) $candidate['name'], $tagColumn) === 0,
            );

            if ($column !== null) {
                $hasTagColumn = true;
            }

            $knowledgeBaseTables[] = [
                'name' => (string) $table,
                'columns' => array_map(
                    static fn (array $column): string => (string) $column['name'],
                    $columns,
                ),
                'rows' => $database->query(sprintf('SELECT * FROM %s', $quotedTable))->fetchAll(PDO::FETCH_ASSOC),
            ];
        }

        if (! $hasTagColumn) {
            throw new RuntimeException(sprintf('The SQLite database has no table with a "%s" column.', $tagColumn));
        }

        return [
            'tag_column' => $tagColumn,
            'tables' => $knowledgeBaseTables,
        ];
    }

    private function quoteIdentifier(string $identifier): string
    {
        return sprintf('"%s"', str_replace('"', '""', $identifier));
    }
}
