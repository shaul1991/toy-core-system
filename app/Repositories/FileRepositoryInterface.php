<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\File;
use Illuminate\Http\UploadedFile;

interface FileRepositoryInterface
{
    /**
     * ID로 파일 조회
     */
    public function findById(string $id): ?File;

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
    public function paginate(int $perPage = 15, ?string $cursor = null, array $filters = []): \Illuminate\Contracts\Pagination\CursorPaginator;

    /**
     * ID로 파일 조회 (삭제된 것 포함)
     */
    public function findByIdWithTrashed(string $id): ?File;

    /**
     * 파일 업로드 및 메타데이터 저장
     */
    public function upload(
        UploadedFile $file,
        string $visibility = 'private',
        ?string $path = null,
        ?array $metadata = null
    ): File;

    /**
     * 파일 다운로드 스트림 반환
     *
     * @return resource|null
     */
    public function download(File $file): mixed;

    /**
     * 파일 삭제 (soft delete, 스토리지 파일 유지)
     */
    public function delete(File $file): bool;

    /**
     * 파일 완전 삭제 (스토리지 파일 포함)
     */
    public function forceDelete(File $file): bool;

    /**
     * 삭제된 파일 복원
     */
    public function restore(File $file): bool;

    /**
     * 파일 visibility 변경
     */
    public function updateVisibility(File $file, string $visibility): File;

    /**
     * 파일 존재 여부 확인 (스토리지)
     */
    public function exists(File $file): bool;

    /**
     * 임시 URL 생성 (private 파일용)
     */
    public function temporaryUrl(File $file, int $expirationMinutes = 60): ?string;
}
