<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Google\Cloud\BigQuery\BigQueryClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class BigQueryIngestionService
{
    private const TARGETS = [
        'dim_sensor_hardware' => [
            'hardware_id', 'location_id', 'sensor_type', 'battery_level_pct', 'status', 'ingested_at',
        ],
        'dim_emergency_shelters' => [
            'location_id', 'shelter_name', 'capacity', 'has_backup_power',
        ],
        'dim_district_demographics' => [
            'location_id', 'population_density_km2', 'vulnerable_elderly_pct',
        ],
    ];

    public function __construct(private readonly IngestionPreviewService $sourceService) {}

    /**
     * @return array<int, string>
     */
    public static function targetTables(): array
    {
        return array_keys(self::TARGETS);
    }

    /**
     * @return array{target: string, inserted: int, rows: array<int, array<string, mixed>>, total: int}
     */
    public function ingestFile(UploadedFile $file, string $target): array
    {
        return $this->upload($this->sourceService->rowsFromFile($file), $target);
    }

    /**
     * @param  array{host: string, port: int, database: string, username: string, password: string, query: string}  $credentials
     * @return array{target: string, inserted: int, rows: array<int, array<string, mixed>>, total: int}
     */
    public function ingestDatabase(array $credentials, string $target): array
    {
        return $this->upload($this->sourceService->rowsFromExternalQuery($credentials), $target);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function normalizeRows(array $rows, string $target): array
    {
        if (! isset(self::TARGETS[$target])) {
            throw new RuntimeException('The selected BigQuery table is not supported.');
        }

        if ($rows === []) {
            throw new RuntimeException('The source returned no rows to upload.');
        }

        return array_map(
            fn (array $row, int $index): array => match ($target) {
                'dim_sensor_hardware' => $this->normalizeSensorHardware($row, $index),
                'dim_emergency_shelters' => $this->normalizeShelter($row, $index),
                'dim_district_demographics' => $this->normalizeDemographics($row, $index),
            },
            $rows,
            array_keys($rows),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{target: string, inserted: int, rows: array<int, array<string, mixed>>, total: int}
     */
    private function upload(array $rows, string $target): array
    {
        $normalizedRows = $this->normalizeRows($rows, $target);
        $options = ['projectId' => config('services.bigquery.project_id')];

        if (config('services.bigquery.credentials')) {
            $options['keyFilePath'] = config('services.bigquery.credentials');
        }

        try {
            $client = new BigQueryClient($options);
            $table = $client
                ->dataset(config('services.bigquery.dataset'))
                ->table($target);

            $jsonLines = implode(PHP_EOL, array_map(
                fn (array $row): string => json_encode($row, JSON_THROW_ON_ERROR),
                $normalizedRows,
            ));

            $job = $client->runJob(
                $table->load($jsonLines)
                    ->sourceFormat('NEWLINE_DELIMITED_JSON')
                    ->writeDisposition('WRITE_APPEND'),
            );

            $jobError = $job->info()['status']['errorResult'] ?? null;

            if ($jobError !== null) {
                throw new RuntimeException($jobError['message'] ?? 'The BigQuery load job was not successful.');
            }
        } catch (Throwable $exception) {
            Log::error('BigQuery batch load failed.', [
                'target' => $target,
                'row_count' => count($normalizedRows),
                'error' => $exception->getMessage(),
            ]);

            throw new RuntimeException('BigQuery batch upload failed. Check the project, dataset, credentials, and table schema.');
        }

        return [
            'target' => $target,
            'inserted' => count($normalizedRows),
            'rows' => array_slice($normalizedRows, 0, 5),
            'total' => count($normalizedRows),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeSensorHardware(array $row, int $index): array
    {
        return [
            'hardware_id' => $this->integer($row['hardware_id'] ?? $row['id'] ?? null, 'hardware_id', $index, true),
            'location_id' => $this->requiredString($row['location_id'] ?? null, 'location_id', $index),
            'sensor_type' => $this->requiredString($row['sensor_type'] ?? null, 'sensor_type', $index),
            'battery_level_pct' => $this->integer($row['battery_level_pct'] ?? null, 'battery_level_pct', $index),
            'status' => $this->requiredString($row['status'] ?? null, 'status', $index),
            'ingested_at' => $this->timestamp($row['ingested_at'] ?? null, $index),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeShelter(array $row, int $index): array
    {
        return [
            'location_id' => $this->requiredString($row['location_id'] ?? null, 'location_id', $index),
            'shelter_name' => $this->requiredString($row['shelter_name'] ?? null, 'shelter_name', $index),
            'capacity' => $this->integer($row['capacity'] ?? null, 'capacity', $index, true),
            'has_backup_power' => $this->boolean($row['has_backup_power'] ?? null, 'has_backup_power', $index),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeDemographics(array $row, int $index): array
    {
        return [
            'location_id' => $this->requiredString($row['location_id'] ?? null, 'location_id', $index),
            'population_density_km2' => $this->integer($row['population_density_km2'] ?? null, 'population_density_km2', $index, true),
            'vulnerable_elderly_pct' => $this->float($row['vulnerable_elderly_pct'] ?? null, 'vulnerable_elderly_pct', $index, true),
        ];
    }

    private function requiredString(mixed $value, string $column, int $index): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            throw new RuntimeException(sprintf('Row %d is missing %s.', $index + 1, $column));
        }

        return $value;
    }

    private function integer(mixed $value, string $column, int $index, bool $required = false): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            if ($required) {
                throw new RuntimeException(sprintf('Row %d is missing %s.', $index + 1, $column));
            }

            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new RuntimeException(sprintf('Row %d has an invalid integer in %s.', $index + 1, $column));
        }

        return (int) $value;
    }

    private function float(mixed $value, string $column, int $index, bool $required = false): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            if ($required) {
                throw new RuntimeException(sprintf('Row %d is missing %s.', $index + 1, $column));
            }

            return null;
        }

        if (! is_numeric($value)) {
            throw new RuntimeException(sprintf('Row %d has an invalid number in %s.', $index + 1, $column));
        }

        return (float) $value;
    }

    private function boolean(mixed $value, string $column, int $index): bool
    {
        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            '1', 'true', 'yes', 'y', 'on' => true,
            '0', 'false', 'no', 'n', 'off' => false,
            default => throw new RuntimeException(sprintf('Row %d has an invalid boolean in %s.', $index + 1, $column)),
        };
    }

    private function timestamp(mixed $value, int $index): string
    {
        try {
            return CarbonImmutable::parse($value ?: 'now')->utc()->toIso8601String();
        } catch (Throwable) {
            throw new RuntimeException(sprintf('Row %d has an invalid timestamp in ingested_at.', $index + 1));
        }
    }
}
