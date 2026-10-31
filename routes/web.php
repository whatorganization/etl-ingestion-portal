<?php

use App\Http\Controllers\IngestionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [IngestionController::class, 'index'])->name('ingestion.index');
Route::post('/ingestion/file-preview', [IngestionController::class, 'previewFile'])->name('ingestion.file-preview');
Route::post('/ingestion/database-preview', [IngestionController::class, 'previewDatabase'])->name('ingestion.database-preview');
