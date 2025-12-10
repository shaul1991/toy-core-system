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
    public function downloadFile(string $id)
    {
        $file = $this->getFile($id);
        $stream = $this->fileRepository->download($file);

        if (! $stream) {
            throw new NotFoundException('파일 데이터를 찾을 수 없습니다.');
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
            throw new NotFoundException('파일 데이터를 찾을 수 없습니다.');
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
            throw new BadRequestException("유효하지 않은 visibility 값입니다: {$visibility}");
        }
    }
}
