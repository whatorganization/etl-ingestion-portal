<?php

use App\Services\BigQueryIngestionService;
use Illuminate\Http\UploadedFile;

test('the ingestion workspace is available', function () {
    $this->get('/')->assertOk()->assertSee('Bring your data into focus.');
});

test('a csv upload returns a five row preview', function () {
    $file = UploadedFile::fake()->createWithContent('weather.csv', "district,temp\nColombo,29\nKandy,24\nGalle,28\nJaffna,31\nMatara,27\nKurunegala,30\n");

    $this->postJson('/ingestion/file-preview', ['file' => $file])
        ->assertOk()->assertJsonPath('total', 6)->assertJsonCount(5, 'rows')->assertJsonPath('rows.0.district', 'Colombo');
});

test('file preview validates the file type', function () {
    $file = UploadedFile::fake()->createWithContent('weather.txt', 'not supported');

    $this->postJson('/ingestion/file-preview', ['file' => $file])->assertUnprocessable()->assertJsonValidationErrors('file');
});

test('database preview rejects write queries', function () {
    $this->postJson('/ingestion/database-preview', ['host' => '127.0.0.1', 'port' => 3306, 'database' => 'weather', 'username' => 'readonly', 'password' => 'secret', 'query' => 'DROP TABLE weather_readings'])
        ->assertUnprocessable()->assertJsonPath('message', 'Only read-only SQL statements are allowed for previewing data.');
});

test('shelter rows are cleaned into BigQuery types', function () {
    $rows = app(BigQueryIngestionService::class)->normalizeRows([
        [
            'location_id' => ' LK-11 ',
            'shelter_name' => 'Colombo Central College',
            'capacity' => '1500',
            'has_backup_power' => 'true',
        ],
    ], 'dim_emergency_shelters');

    expect($rows)->toBe([
        [
            'location_id' => 'LK-11',
            'shelter_name' => 'Colombo Central College',
            'capacity' => 1500,
            'has_backup_power' => true,
        ],
    ]);
});

test('demographic rows are cleaned into BigQuery numeric types', function () {
    $rows = app(BigQueryIngestionService::class)->normalizeRows([
        [
            'location_id' => 'LK-11',
            'population_density_km2' => '1250',
            'vulnerable_elderly_pct' => '8.75',
        ],
    ], 'dim_district_demographics');

    expect($rows[0]['population_density_km2'])->toBe(1250)
        ->and($rows[0]['vulnerable_elderly_pct'])->toBe(8.75);
});

test('mysql sensor rows map id to hardware id and generate an ingestion timestamp', function () {
    $rows = app(BigQueryIngestionService::class)->normalizeRows([
        [
            'id' => '1',
            'location_id' => 'LK-11',
            'sensor_type' => 'Rain Gauge',
            'battery_level_pct' => '92',
            'status' => 'Online',
        ],
    ], 'dim_sensor_hardware');

    expect($rows[0])
        ->hardware_id->toBe(1)
        ->location_id->toBe('LK-11')
        ->battery_level_pct->toBe(92)
        ->ingested_at->toBeString();
});

test('invalid source values are rejected before upload', function () {
    expect(fn () => app(BigQueryIngestionService::class)->normalizeRows([
        [
            'location_id' => 'LK-11',
            'shelter_name' => 'Colombo Central College',
            'capacity' => 'not-a-number',
            'has_backup_power' => 'true',
        ],
    ], 'dim_emergency_shelters'))
        ->toThrow('Row 1 has an invalid integer in capacity.');
});
