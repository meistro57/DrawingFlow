<?php

namespace App\Http\Controllers;

use App\Models\DrawingSubmittal;
use App\Models\SubmittalFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SubmittalFileController extends Controller
{
    public function store(Request $request, DrawingSubmittal $submittal): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:51200', 'mimes:pdf,dwg,dxf,jpg,jpeg,png,doc,docx,xls,xlsx'],
            'file_type' => ['required', 'in:drawing,calculation,specification,photo,markup,approval,other'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $file = $request->file('file');
        $originalFilename = $file->getClientOriginalName();
        $uuid = Str::uuid();
        $extension = $file->getClientOriginalExtension();
        $storedFilename = "{$uuid}.{$extension}";
        $storagePath = "submittals/{$submittal->id}/{$storedFilename}";

        Storage::disk('local')->putFileAs(
            "submittals/{$submittal->id}",
            $file,
            $storedFilename
        );

        // Get the next version number for this file type
        $existingCount = $submittal->files()->where('file_type', $validated['file_type'])->count();

        $submittal->files()->create([
            'file_type' => $validated['file_type'],
            'filename' => $storedFilename,
            'original_filename' => $originalFilename,
            'file_path' => $storagePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'version' => $existingCount + 1,
            'is_current' => true,
            'uploaded_by_user_id' => auth()->id(),
            'uploaded_at' => now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', "File \"{$originalFilename}\" uploaded successfully.");
    }

    public function download(SubmittalFile $submittalFile): Response
    {
        abort_unless(Storage::disk('local')->exists($submittalFile->file_path), 404);

        return response()->download(
            Storage::disk('local')->path($submittalFile->file_path),
            $submittalFile->original_filename
        );
    }

    public function destroy(SubmittalFile $submittalFile): RedirectResponse
    {
        Storage::disk('local')->delete($submittalFile->file_path);
        $submittalFile->delete();

        return back()->with('success', 'File deleted.');
    }
}
