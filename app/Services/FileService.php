<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\File;
use App\Repositories\FileRepositoryInterface;
use App\Shared\Exceptions\BadRequestException;
use App\Shared\Exceptions\NotFoundException;
use Illuminate\Http\UploadedFile;

class FileService
{
    public function __construct(
        private FileRepositoryInterface $fileRepository
    ) {}

    /**
     * 파일 조회
     *
     * @throws NotFoundException
     */
    public function getFile(string $id): File
    {
        $file = $this->fileRepository->findById($id);

        if (! $file) {
            throw NotFoundException::forResource('File', $id);
        }

        return $file;
    }

    /**
     * 파일 목록 조회 (Cursor 기반 페이지네이션)
     *
     * @param  array{
     *     visibility?: string,
     *     mime_type?: string,
     *     path_prefix?: string,
     *     created_from?: string,
     *     created_to?: string
     * }  $filters
     */
    public function listFiles(int $perPage = 15, ?string $cursor = null, array $filters = []): \Illuminate\Contracts\Pagination\CursorPaginator
    {
        // visibility 필터 검증
        if (isset($filters['visibility'])) {
            $this->validateVisibility($filters['visibility']);
        }

        return $this->fileRepository->paginate($perPage, $cursor, $filters);
    }

    /**
     * 파일 업로드
     *
     * @throws BadRequestException
     */
    public function uploadFile(
        UploadedFile $uploadedFile,
        string $visibility = 'private',
        ?string $path = null,
        ?array $metadata = null
    ): File {
        $this->validateVisibility($visibility);

        return $this->fileRepository->upload(
            $uploadedFile,
            $visibility,
            $path,
            $metadata
        );
    }

    /**
     * 파일 다운로드 스트림 반환
     *
     * @return resource
     *
     * @throws NotFoundException
     */
    public function downloadFile(File $file): mixed
    {
        $stream = $this->fileRepository->download($file);

        if (! $stream) {
            throw NotFoundException::withMessage('파일 데이터를 찾을 수 없습니다.')
                ->withDetails(['file_id' => $file->id, 'path' => $file->full_path]);
        }

        return $stream;
    }

    /**
     * 파일 삭제 (soft delete)
     *
     * @throws NotFoundException
     */
    public function deleteFile(string $id): File
    {
        $file = $this->getFile($id);
        $this->fileRepository->delete($file);
        $file->refresh();

        return $file;
    }

    /**
     * 파일 완전 삭제 (스토리지 파일 포함)
     *
     * @throws NotFoundException
     */
    public function forceDeleteFile(string $id): bool
    {
        $file = $this->fileRepository->findByIdWithTrashed($id);

        if (! $file) {
            throw NotFoundException::forResource('File', $id);
        }

        return $this->fileRepository->forceDelete($file);
    }

    /**
     * 파일 visibility 변경
     *
     * @throws NotFoundException
     * @throws BadRequestException
     */
    public function updateVisibility(string $id, string $visibility): File
    {
        $this->validateVisibility($visibility);

        $file = $this->getFile($id);

        return $this->fileRepository->updateVisibility($file, $visibility);
    }

    /**
     * 임시 URL 생성 (private 파일용)
     *
     * @throws NotFoundException
     */
    public function getTemporaryUrl(string $id, int $expirationMinutes = 60): string
    {
        $file = $this->getFile($id);
        $url = $this->fileRepository->temporaryUrl($file, $expirationMinutes);

        if (! $url) {
            throw NotFoundException::withMessage('파일 데이터를 찾을 수 없습니다.')
                ->withDetails(['file_id' => $file->id]);
        }

        return $url;
    }

    /**
     * 파일 존재 여부 확인 (스토리지)
     */
    public function fileExists(string $id): bool
    {
        $file = $this->fileRepository->findById($id);

        if (! $file) {
            return false;
        }

        return $this->fileRepository->exists($file);
    }

    /**
     * visibility 값 검증
     *
     * @throws BadRequestException
     */
    private function validateVisibility(string $visibility): void
    {
        if (! in_array($visibility, ['public', 'private'], true)) {
            throw BadRequestException::invalidValue('visibility', $visibility);
        }
    }
}
