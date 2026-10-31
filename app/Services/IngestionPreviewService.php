<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use PDO;
use PDOException;
use RuntimeException;

class IngestionPreviewService
{
    /**
     * @return array{columns: array<int, string>, rows: array<int, array<string, mixed>>, total: int}
     */
    public function previewFile(UploadedFile $file): array
    {
        return match (strtolower($file->getClientOriginalExtension())) {
            'csv' => $this->previewCsv($file),
            'json' => $this->previewJson($file),
            default => throw new RuntimeException('Only CSV and JSON files are supported.'),
        };
    }

    /**
     * @param  array{host: string, port: int, database: string, username: string, password: string, query: string}  $credentials
     * @return array{columns: array<int, string>, rows: array<int, array<string, mixed>>, total: int}
     */
    public function previewExternalQuery(array $credentials): array
    {
        $query = trim($credentials['query']);

        if (! preg_match('/^(select|show|describe|desc|explain)\b/i', $query)) {
            throw new RuntimeException('Only read-only SQL statements are allowed for previewing data.');
        }

        try {
            $pdo = new PDO(
                sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $credentials['host'], $credentials['port'], $credentials['database']),
                $credentials['username'],
                $credentials['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 5,
                ],
            );

            $rows = $pdo->query($query)->fetchAll();
        } catch (PDOException) {
            throw new RuntimeException('Unable to connect or execute the query. Check the credentials and SQL syntax.');
        }

        return $this->formatPreview($rows);
    }

    /**
     * @return array{columns: array<int, string>, rows: array<int, array<string, mixed>>, total: int}
     */
    private function previewCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            throw new RuntimeException('The uploaded file could not be read.');
        }

        $headers = fgetcsv($handle);
        $rows = [];

        if ($headers !== false) {
            $headers = array_map(fn (string $header): string => trim($header), $headers);

            while (($row = fgetcsv($handle)) !== false) {
                if (count(array_filter($row, fn (string|false|null $value): bool => $value !== null && $value !== '')) === 0) {
                    continue;
                }

                $rows[] = $this->rowWithHeaders($headers, $row);
            }
        }

        fclose($handle);

        return $this->formatPreview($rows);
    }

    /**
     * @return array{columns: array<int, string>, rows: array<int, array<string, mixed>>, total: int}
     */
    private function previewJson(UploadedFile $file): array
    {
        $decoded = json_decode(file_get_contents($file->getRealPath()) ?: '', true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            throw new RuntimeException('The JSON file must contain an object or an array of objects.');
        }

        $rows = array_is_list($decoded) ? $decoded : [$decoded];

        if (count(array_filter($rows, 'is_array')) !== count($rows)) {
            throw new RuntimeException('The JSON file must contain objects with named fields.');
        }

        return $this->formatPreview($rows);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, string|null>  $row
     * @return array<string, string|null>
     */
    private function rowWithHeaders(array $headers, array $row): array
    {
        $normalized = [];

        foreach ($headers as $index => $header) {
            $normalized[$header !== '' ? $header : 'column_'.($index + 1)] = $row[$index] ?? null;
        }

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{columns: array<int, string>, rows: array<int, array<string, mixed>>, total: int}
     */
    private function formatPreview(array $rows): array
    {
        $columns = [];

        foreach ($rows as $row) {
            foreach (array_keys($row) as $column) {
                if (! in_array($column, $columns, true)) {
                    $columns[] = (string) $column;
                }
            }
        }

        return ['columns' => $columns, 'rows' => array_slice($rows, 0, 5), 'total' => count($rows)];
    }
}
