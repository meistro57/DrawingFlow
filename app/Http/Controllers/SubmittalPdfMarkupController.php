<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePdfMarkupRequest;
use App\Models\DrawingSubmittal;
use App\Models\PdfMarkup;
use App\Models\SubmittalFile;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmittalPdfMarkupController extends Controller
{
    public function index(DrawingSubmittal $submittal, SubmittalFile $submittalFile): JsonResponse
    {
        $this->ensureSubmittalFileBelongsToSubmittal($submittal, $submittalFile);
        $this->ensureSubmittalFileIsPdf($submittalFile);

        $markups = $submittalFile->markups()
            ->with('user:id,name')
            ->orderBy('created_at')
            ->get();

        return response()->json(['data' => $markups]);
    }

    public function store(StorePdfMarkupRequest $request, DrawingSubmittal $submittal, SubmittalFile $submittalFile): JsonResponse
    {
        $this->ensureSubmittalFileBelongsToSubmittal($submittal, $submittalFile);
        $this->ensureSubmittalFileIsPdf($submittalFile);

        $markup = PdfMarkup::create([
            'submittal_file_id' => $submittalFile->id,
            'user_id' => $request->user()->id,
            'page_number' => $request->integer('page_number'),
            'markup_type' => $request->string('markup_type')->toString(),
            'markup_data' => $request->validated('markup_data'),
        ])->load('user:id,name');

        return response()->json(['data' => $markup], 201);
    }

    public function export(DrawingSubmittal $submittal, SubmittalFile $submittalFile): StreamedResponse
    {
        $this->ensureSubmittalFileBelongsToSubmittal($submittal, $submittalFile);
        $this->ensureSubmittalFileIsPdf($submittalFile);

        $markups = $submittalFile->markups()
            ->with('user:id,name')
            ->orderBy('created_at')
            ->get(['id', 'submittal_file_id', 'user_id', 'page_number', 'markup_type', 'markup_data', 'created_at']);

        $payload = [
            'submittal_id' => $submittal->id,
            'submittal_number' => $submittal->submittal_number,
            'submittal_file_id' => $submittalFile->id,
            'filename' => $submittalFile->original_filename,
            'exported_at' => now()->toIso8601String(),
            'markups' => $markups,
        ];

        return response()->streamDownload(function () use ($payload): void {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }, 'markups-'.$submittalFile->id.'.json', [
            'Content-Type' => 'application/json',
        ]);
    }

    private function ensureSubmittalFileBelongsToSubmittal(DrawingSubmittal $submittal, SubmittalFile $submittalFile): void
    {
        if ($submittalFile->submittal_id !== $submittal->id) {
            abort(404);
        }
    }

    private function ensureSubmittalFileIsPdf(SubmittalFile $submittalFile): void
    {
        if (! $submittalFile->isPdf()) {
            abort(404);
        }
    }
}
