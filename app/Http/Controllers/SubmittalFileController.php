<?php

namespace App\Http\Controllers;

use App\Models\DrawingSubmittal;
use App\Models\SubmittalFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SubmittalFileController extends Controller
{
    public function view(DrawingSubmittal $submittal, SubmittalFile $submittalFile): BinaryFileResponse
    {
        $this->ensureSubmittalFileBelongsToSubmittal($submittal, $submittalFile);

        if (! $this->isPdf($submittalFile)) {
            abort(404);
        }

        if (! Storage::disk('local')->exists($submittalFile->file_path)) {
            abort(404);
        }

        return response()->file(
            Storage::disk('local')->path($submittalFile->file_path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$submittalFile->original_filename.'"',
            ]
        );
    }

    public function download(DrawingSubmittal $submittal, SubmittalFile $submittalFile): BinaryFileResponse
    {
        $this->ensureSubmittalFileBelongsToSubmittal($submittal, $submittalFile);

        if (! Storage::disk('local')->exists($submittalFile->file_path)) {
            abort(404);
        }

        return response()->download(
            Storage::disk('local')->path($submittalFile->file_path),
            $submittalFile->original_filename
        );
    }

    private function ensureSubmittalFileBelongsToSubmittal(DrawingSubmittal $submittal, SubmittalFile $submittalFile): void
    {
        if ($submittalFile->submittal_id !== $submittal->id) {
            abort(404);
        }
    }

    private function isPdf(SubmittalFile $submittalFile): bool
    {
        return $submittalFile->mime_type === 'application/pdf'
            || str_ends_with(strtolower($submittalFile->original_filename), '.pdf');
    }
}
