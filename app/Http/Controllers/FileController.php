<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function __construct(
        private FileService $fileService
    ) {}

    /**
     * 파일 메타데이터 조회
     */
    public function show(string $id): JsonResponse
    {
        $file = $this->fileService->getFile($id);

        return $this->successResponse($this->formatFileResponse($file));
    }

    /**
     * 파일 업로드
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:102400'], // 100MB
            'visibility' => ['sometimes', 'string', 'in:public,private'],
            'path' => ['sometimes', 'string', 'max:255'],
            'metadata' => ['sometimes', 'array'],
        ]);

        $file = $this->fileService->uploadFile(
            $request->file('file'),
            $validated['visibility'] ?? 'private',
            $validated['path'] ?? null,
            $validated['metadata'] ?? null
        );

        return $this->createdResponse($this->formatFileResponse($file));
    }

    /**
     * 파일 다운로드
     */
    public function download(string $id): StreamedResponse
    {
        $file = $this->fileService->getFile($id);
        $stream = $this->fileService->downloadFile($id);

        return response()->streamDownload(
            function () use ($stream) {
                fpassthru($stream);
                fclose($stream);
            },
            $file->original_name,
            [
                'Content-Type' => $file->mime_type,
                'Content-Length' => $file->size,
            ]
        );
    }

    /**
     * 파일 삭제 (soft delete)
     */
    public function destroy(string $id): JsonResponse
    {
        $file = $this->fileService->deleteFile($id);

        return $this->successResponse([
            'id' => $file->id,
            'deleted_at' => $file->deleted_at->toIso8601String(),
        ]);
    }

    /**
     * 파일 완전 삭제
     */
    public function forceDestroy(string $id): JsonResponse
    {
        $this->fileService->forceDeleteFile($id);

        return $this->successResponse([
            'id' => $id,
            'message' => '파일이 완전히 삭제되었습니다.',
        ]);
    }

    /**
     * 파일 visibility 변경
     */
    public function updateVisibility(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'visibility' => ['required', 'string', 'in:public,private'],
        ]);

        $file = $this->fileService->updateVisibility($id, $validated['visibility']);

        return $this->successResponse($this->formatFileResponse($file));
    }

    /**
     * 임시 URL 생성 (private 파일용)
     */
    public function temporaryUrl(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'expiration_minutes' => ['sometimes', 'integer', 'min:1', 'max:10080'], // 최대 7일
        ]);

        $url = $this->fileService->getTemporaryUrl(
            $id,
            $validated['expiration_minutes'] ?? 60
        );

        return $this->successResponse([
            'url' => $url,
            'expires_at' => now()->addMinutes($validated['expiration_minutes'] ?? 60)->toIso8601String(),
        ]);
    }

    /**
     * 파일 응답 포맷
     */
    private function formatFileResponse($file): array
    {
        return [
            'id' => $file->id,
            'original_name' => $file->original_name,
            'mime_type' => $file->mime_type,
            'size' => $file->size,
            'human_readable_size' => $file->human_readable_size,
            'visibility' => $file->visibility,
            'url' => $file->url,
            'metadata' => $file->metadata,
            'created_at' => $file->created_at->toIso8601String(),
            'updated_at' => $file->updated_at->toIso8601String(),
        ];
    }
}
