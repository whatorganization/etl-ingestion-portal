<?php

namespace App\Http\Controllers;

use App\Services\BigQueryIngestionService;
use App\Services\IngestionPreviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class IngestionController extends Controller
{
    public function __construct(
        private readonly IngestionPreviewService $previewService,
        private readonly BigQueryIngestionService $bigQueryService,
    ) {}

    public function index(): View
    {
        return view('ingestion.index');
    }

    public function previewFile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,json', 'extensions:csv,json', 'max:10240'],
        ]);

        try {
            return response()->json($this->previewService->previewFile($validated['file']));
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function previewDatabase(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'database' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'query' => ['required', 'string', 'max:10000'],
        ]);

        try {
            return response()->json($this->previewService->previewExternalQuery([
                'host' => $validated['host'],
                'port' => (int) $validated['port'],
                'database' => $validated['database'],
                'username' => $validated['username'],
                'password' => $validated['password'] ?? '',
                'query' => $validated['query'],
            ]));
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function uploadFile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,json', 'extensions:csv,json', 'max:10240'],
            'target_table' => ['required', Rule::in(BigQueryIngestionService::targetTables())],
        ]);

        try {
            return response()->json($this->bigQueryService->ingestFile($validated['file'], $validated['target_table']));
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function uploadDatabase(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'database' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'query' => ['required', 'string', 'max:10000'],
            'target_table' => ['required', Rule::in(BigQueryIngestionService::targetTables())],
        ]);

        try {
            return response()->json($this->bigQueryService->ingestDatabase([
                'host' => $validated['host'],
                'port' => (int) $validated['port'],
                'database' => $validated['database'],
                'username' => $validated['username'],
                'password' => $validated['password'] ?? '',
                'query' => $validated['query'],
            ], $validated['target_table']));
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
