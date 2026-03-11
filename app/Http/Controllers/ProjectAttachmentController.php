<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectAttachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProjectAttachmentController extends Controller
{
    public function view(Project $project, ProjectAttachment $attachment): BinaryFileResponse
    {
        $this->ensureAttachmentBelongsToProject($project, $attachment);

        if (! $this->isPdf($attachment)) {
            abort(404);
        }

        if (! Storage::disk('local')->exists($attachment->file_path)) {
            abort(404);
        }

        return response()->file(
            Storage::disk('local')->path($attachment->file_path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$attachment->original_filename.'"',
            ]
        );
    }

    public function download(Project $project, ProjectAttachment $attachment): BinaryFileResponse
    {
        $this->ensureAttachmentBelongsToProject($project, $attachment);

        if (! Storage::disk('local')->exists($attachment->file_path)) {
            abort(404);
        }

        return response()->download(
            Storage::disk('local')->path($attachment->file_path),
            $attachment->original_filename
        );
    }

    private function ensureAttachmentBelongsToProject(Project $project, ProjectAttachment $attachment): void
    {
        if ($attachment->project_id !== $project->id) {
            abort(404);
        }
    }

    private function isPdf(ProjectAttachment $attachment): bool
    {
        return $attachment->mime_type === 'application/pdf'
            || str_ends_with(strtolower($attachment->original_filename), '.pdf');
    }
}
