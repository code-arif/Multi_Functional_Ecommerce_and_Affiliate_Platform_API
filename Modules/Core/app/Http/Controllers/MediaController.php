<?php

namespace Modules\Core\Http\Controllers;

use Modules\Core\Models\Medium;
use Modules\Core\Http\Resources\MediumResource;
use Modules\Core\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $media = Medium::with('uploader')
            ->when($request->type, fn($q, $t) => $q->byType($t))
            ->when($request->public, fn($q) => $q->public())
            ->latest()
            ->paginate($request->per_page ?? 30);

        return $this->paginatedResponse(MediumResource::collection($media));
    }

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file'          => 'required|file|mimes:jpg,jpeg,png,webp,gif,svg,pdf,doc,docx,xls,xlsx,csv|max:10240',
            'directory'     => 'nullable|string|max:100',
            'mediable_type' => 'nullable|string',
            'mediable_id'   => 'nullable|integer',
            'is_public'     => 'boolean',
        ]);

        $file = $request->file('file');
        $directory = $request->directory ?? 'uploads';
        $path = $file->store($directory, 'public');

        $medium = Medium::create([
            'disk'           => 'public',
            'directory'      => $directory,
            'file_name'      => basename($path),
            'original_name'  => $file->getClientOriginalName(),
            'mime_type'      => $file->getMimeType(),
            'file_size'      => $file->getSize(),
            'extension'      => $file->getClientOriginalExtension(),
            'mediable_type'  => $request->mediable_type,
            'mediable_id'    => $request->mediable_id,
            'uploaded_by'    => $request->user()?->id,
            'is_public'      => $request->boolean('is_public', true),
        ]);

        return $this->createdResponse(new MediumResource($medium), 'File uploaded.');
    }

    public function destroy(Medium $medium): JsonResponse
    {
        // Delete file from storage
        \Illuminate\Support\Facades\Storage::disk($medium->disk)->delete($medium->path);
        $medium->delete();

        return $this->noContentResponse('File deleted.');
    }
}
