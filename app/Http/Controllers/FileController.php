<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\File;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function __construct(
        private FileService $fileService
    ) {}

    /**
     * 파일 목록 조회 (Cursor 기반 페이지네이션)
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'cursor' => ['sometimes', 'string'],
            'visibility' => ['sometimes', 'string', 'in:public,private'],
            'mime_type' => ['sometimes', 'string', 'max:255'],
            'path_prefix' => ['sometimes', 'string', 'max:255'],
            'created_from' => ['sometimes', 'date'],
            'created_to' => ['sometimes', 'date'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 15);
        $cursor = $validated['cursor'] ?? null;

        $filters = array_filter([
            'visibility' => $validated['visibility'] ?? null,
            'mime_type' => $validated['mime_type'] ?? null,
            'path_prefix' => $validated['path_prefix'] ?? null,
            'created_from' => $validated['created_from'] ?? null,
            'created_to' => $validated['created_to'] ?? null,
        ]);

        $paginator = $this->fileService->listFiles($perPage, $cursor, $filters);

        return $this->paginatedResponse(
            $paginator,
            fn ($file) => $this->formatFileResponse($file)
        );
    }

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
     *
     * HTTP 캐싱 지원:
     * - ETag: 파일 식별자 기반 캐시 검증
     * - Last-Modified: 수정 시간 기반 캐시 검증
     * - 304 Not Modified: 변경 없을 시 빈 응답
     * - Range Request: 대용량 파일 부분 다운로드
     */
    public function download(Request $request, string $id): StreamedResponse|Response
    {
        $file = $this->fileService->getFile($id);

        // 304 Not Modified 체크
        if ($this->isNotModified($request, $file)) {
            return response('', 304)
                ->header('ETag', $file->etag)
                ->header('Last-Modified', $file->last_modified);
        }

        // Range Request 처리
        $rangeHeader = $request->header('Range');
        if ($rangeHeader && $this->isValidRangeRequest($rangeHeader, $file->size)) {
            return $this->handleRangeRequest($request, $file, $rangeHeader);
        }

        // 전체 파일 다운로드
        $stream = $this->fileService->downloadFile($file);

        return response()->streamDownload(
            function () use ($stream) {
                fpassthru($stream);
                fclose($stream);
            },
            $file->original_name,
            $this->buildDownloadHeaders($file)
        );
    }

    /**
     * 캐시 검증 (If-None-Match, If-Modified-Since)
     */
    private function isNotModified(Request $request, File $file): bool
    {
        $ifNoneMatch = $request->header('If-None-Match');
        $ifModifiedSince = $request->header('If-Modified-Since');

        // ETag 검증
        if ($ifNoneMatch && $ifNoneMatch === $file->etag) {
            return true;
        }

        // Last-Modified 검증
        if ($ifModifiedSince) {
            $clientTime = strtotime($ifModifiedSince);
            $fileTime = $file->updated_at->timestamp;

            if ($clientTime && $clientTime >= $fileTime) {
                return true;
            }
        }

        return false;
    }

    /**
     * Range Request 유효성 검증
     */
    private function isValidRangeRequest(string $rangeHeader, int $fileSize): bool
    {
        if (! preg_match('/^bytes=(\d*)-(\d*)$/', $rangeHeader, $matches)) {
            return false;
        }

        $start = $matches[1] !== '' ? (int) $matches[1] : null;
        $end = $matches[2] !== '' ? (int) $matches[2] : null;

        // 적어도 하나는 있어야 함
        if ($start === null && $end === null) {
            return false;
        }

        // 범위 검증
        if ($start !== null && $start >= $fileSize) {
            return false;
        }

        return true;
    }

    /**
     * Range Request 처리 (206 Partial Content)
     */
    private function handleRangeRequest(Request $request, File $file, string $rangeHeader): StreamedResponse
    {
        preg_match('/^bytes=(\d*)-(\d*)$/', $rangeHeader, $matches);

        $fileSize = $file->size;
        $start = $matches[1] !== '' ? (int) $matches[1] : null;
        $end = $matches[2] !== '' ? (int) $matches[2] : null;

        // 범위 계산
        if ($start === null) {
            // bytes=-500 (마지막 500 바이트)
            $start = max(0, $fileSize - $end);
            $end = $fileSize - 1;
        } elseif ($end === null || $end >= $fileSize) {
            // bytes=500- (500부터 끝까지)
            $end = $fileSize - 1;
        }

        $length = $end - $start + 1;

        $stream = $this->fileService->downloadFile($file);

        return response()->stream(
            function () use ($stream, $start, $length) {
                fseek($stream, $start);
                echo fread($stream, $length);
                fclose($stream);
            },
            206,
            array_merge($this->buildDownloadHeaders($file), [
                'Content-Length' => $length,
                'Content-Range' => "bytes {$start}-{$end}/{$fileSize}",
                'Accept-Ranges' => 'bytes',
            ])
        );
    }

    /**
     * 다운로드 응답 헤더 생성
     */
    private function buildDownloadHeaders(File $file): array
    {
        return [
            'Content-Type' => $file->mime_type,
            'Content-Length' => $file->size,
            'ETag' => $file->etag,
            'Last-Modified' => $file->last_modified,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, max-age=86400',
        ];
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

        $expirationMinutes = (int) ($validated['expiration_minutes'] ?? 60);

        $url = $this->fileService->getTemporaryUrl($id, $expirationMinutes);

        return $this->successResponse([
            'url' => $url,
            'expires_at' => now()->addMinutes($expirationMinutes)->toIso8601String(),
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
