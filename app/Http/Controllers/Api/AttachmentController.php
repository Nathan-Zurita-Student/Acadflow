<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Project;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $attachments = $project->attachments()
            ->with('uploader')
            ->whereNull('attachable_type')
            ->latest()
            ->get()
            ->map(fn($a) => $this->resource($a));

        return response()->json($attachments);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $request->validate([
            'file' => ['required', 'file', 'max:51200'],
            'task_id' => ['nullable', 'exists:tasks,id'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $request->file('file');
        $path = $file->store('attachments/' . $project->id, 'public');

        $customName = $request->input('name');
        $extension  = $file->getClientOriginalExtension();
        $displayName = $customName
            ? ($extension ? rtrim($customName, '.') . '.' . $extension : $customName)
            : $file->getClientOriginalName();

        $attachment = Attachment::create([
            'project_id' => $project->id,
            'uploaded_by' => $request->user()->id,
            'attachable_type' => $request->task_id ? 'App\\Models\\Task' : null,
            'attachable_id' => $request->task_id,
            'name' => $displayName,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        $attachment->load('uploader');

        // notificar membros do projeto (exceto quem fez upload)
        $members = $project->members()
            ->where('users.id', '!=', $request->user()->id)
            ->get();

        $uploaderName = $request->user()->name;
        $this->notifications->notifyMany(
            $members,
            'file_uploaded',
            'Novo arquivo enviado',
            "{$uploaderName} enviou \"{$displayName}\" em {$project->name}",
            ['project_id' => $project->id, 'attachment_id' => $attachment->id],
        );

        return response()->json($this->resource($attachment), 201);
    }

    public function view(Project $project, Attachment $attachment)
    {
        $this->authorize('view', $project);

        return Storage::disk('public')->response($attachment->path, $attachment->name, [
            'Content-Type'        => $attachment->mime_type,
            'Content-Disposition' => 'inline; filename="' . $attachment->name . '"',
        ]);
    }

    public function download(Project $project, Attachment $attachment)
    {
        $this->authorize('view', $project);

        return Storage::disk('public')->download($attachment->path, $attachment->name);
    }

    public function destroy(Request $request, Project $project, Attachment $attachment): JsonResponse
    {
        $this->authorize('view', $project);

        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return response()->json(null, 204);
    }

    private function resource(Attachment $a): array
    {
        return [
            'id' => $a->id,
            'name' => $a->name,
            'mime_type' => $a->mime_type,
            'size' => $a->size,
            'url' => $a->url,
            'uploader' => $a->uploader ? ['id' => $a->uploader->id, 'name' => $a->uploader->name] : null,
            'created_at' => $a->created_at,
        ];
    }
}
