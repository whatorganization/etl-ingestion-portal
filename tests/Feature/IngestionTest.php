<?php

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
