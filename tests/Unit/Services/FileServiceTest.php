<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\File;
use App\Repositories\FileRepositoryInterface;
use App\Services\FileService;
use App\Shared\Exceptions\BadRequestException;
use App\Shared\Exceptions\NotFoundException;
use Illuminate\Http\UploadedFile;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class FileServiceTest extends TestCase
{
    private MockInterface $fileRepository;

    private FileService $fileService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileRepository = Mockery::mock(FileRepositoryInterface::class);
        $this->fileService = new FileService($this->fileRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ========================================
    // getFile 테스트
    // ========================================

    public function test_get_file_returns_file_when_found(): void
    {
        $file = $this->createFileMock('test-id');

        $this->fileRepository
            ->shouldReceive('findById')
            ->with('test-id')
            ->once()
            ->andReturn($file);

        $result = $this->fileService->getFile('test-id');

        $this->assertSame($file, $result);
    }

    public function test_get_file_throws_not_found_exception_when_not_found(): void
    {
        $this->fileRepository
            ->shouldReceive('findById')
            ->with('nonexistent')
            ->once()
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('File(을)를 찾을 수 없습니다: nonexistent');

        $this->fileService->getFile('nonexistent');
    }

    // ========================================
    // uploadFile 테스트
    // ========================================

    public function test_upload_file_with_default_visibility(): void
    {
        $uploadedFile = Mockery::mock(UploadedFile::class);
        $file = $this->createFileMock('new-id');

        $this->fileRepository
            ->shouldReceive('upload')
            ->with($uploadedFile, 'private', null, null)
            ->once()
            ->andReturn($file);

        $result = $this->fileService->uploadFile($uploadedFile);

        $this->assertSame($file, $result);
    }

    public function test_upload_file_with_public_visibility(): void
    {
        $uploadedFile = Mockery::mock(UploadedFile::class);
        $file = $this->createFileMock('new-id');

        $this->fileRepository
            ->shouldReceive('upload')
            ->with($uploadedFile, 'public', 'custom/path', ['key' => 'value'])
            ->once()
            ->andReturn($file);

        $result = $this->fileService->uploadFile(
            $uploadedFile,
            'public',
            'custom/path',
            ['key' => 'value']
        );

        $this->assertSame($file, $result);
    }

    public function test_upload_file_throws_bad_request_for_invalid_visibility(): void
    {
        $uploadedFile = Mockery::mock(UploadedFile::class);

        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('유효하지 않은 visibility 값입니다: invalid');

        $this->fileService->uploadFile($uploadedFile, 'invalid');
    }

    // ========================================
    // downloadFile 테스트
    // ========================================

    public function test_download_file_returns_stream(): void
    {
        $file = $this->createFileMock('test-id');
        $stream = fopen('php://memory', 'r');

        $this->fileRepository
            ->shouldReceive('findById')
            ->with('test-id')
            ->once()
            ->andReturn($file);

        $this->fileRepository
            ->shouldReceive('download')
            ->with($file)
            ->once()
            ->andReturn($stream);

        $result = $this->fileService->downloadFile('test-id');

        $this->assertSame($stream, $result);

        fclose($stream);
    }

    public function test_download_file_throws_not_found_when_stream_is_null(): void
    {
        $file = $this->createFileMock('test-id');

        $this->fileRepository
            ->shouldReceive('findById')
            ->with('test-id')
            ->once()
            ->andReturn($file);

        $this->fileRepository
            ->shouldReceive('download')
            ->with($file)
            ->once()
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('파일 데이터를 찾을 수 없습니다.');

        $this->fileService->downloadFile('test-id');
    }

    // ========================================
    // deleteFile 테스트
    // ========================================

    public function test_delete_file_soft_deletes_and_returns_file(): void
    {
        $file = $this->createFileMock('delete-id');

        $this->fileRepository
            ->shouldReceive('findById')
            ->with('delete-id')
            ->once()
            ->andReturn($file);

        $this->fileRepository
            ->shouldReceive('delete')
            ->with($file)
            ->once()
            ->andReturn(true);

        $file->shouldReceive('refresh')
            ->once()
            ->andReturnSelf();

        $result = $this->fileService->deleteFile('delete-id');

        $this->assertSame($file, $result);
    }

    // ========================================
    // forceDeleteFile 테스트
    // ========================================

    public function test_force_delete_file_permanently_deletes(): void
    {
        $file = $this->createFileMock('force-delete-id');

        $this->fileRepository
            ->shouldReceive('findByIdWithTrashed')
            ->with('force-delete-id')
            ->once()
            ->andReturn($file);

        $this->fileRepository
            ->shouldReceive('forceDelete')
            ->with($file)
            ->once()
            ->andReturn(true);

        $result = $this->fileService->forceDeleteFile('force-delete-id');

        $this->assertTrue($result);
    }

    public function test_force_delete_file_throws_not_found_when_not_exists(): void
    {
        $this->fileRepository
            ->shouldReceive('findByIdWithTrashed')
            ->with('nonexistent')
            ->once()
            ->andReturn(null);

        $this->expectException(NotFoundException::class);

        $this->fileService->forceDeleteFile('nonexistent');
    }

    // ========================================
    // updateVisibility 테스트
    // ========================================

    public function test_update_visibility_changes_file_visibility(): void
    {
        $file = $this->createFileMock('test-id');
        $updatedFile = $this->createFileMock('test-id');

        $this->fileRepository
            ->shouldReceive('findById')
            ->with('test-id')
            ->once()
            ->andReturn($file);

        $this->fileRepository
            ->shouldReceive('updateVisibility')
            ->with($file, 'public')
            ->once()
            ->andReturn($updatedFile);

        $result = $this->fileService->updateVisibility('test-id', 'public');

        $this->assertSame($updatedFile, $result);
    }

    public function test_update_visibility_throws_bad_request_for_invalid_value(): void
    {
        $this->expectException(BadRequestException::class);

        $this->fileService->updateVisibility('test-id', 'invalid');
    }

    // ========================================
    // getTemporaryUrl 테스트
    // ========================================

    public function test_get_temporary_url_returns_url(): void
    {
        $file = $this->createFileMock('test-id');

        $this->fileRepository
            ->shouldReceive('findById')
            ->with('test-id')
            ->once()
            ->andReturn($file);

        $this->fileRepository
            ->shouldReceive('temporaryUrl')
            ->with($file, 60)
            ->once()
            ->andReturn('https://minio.example.com/temp-url');

        $result = $this->fileService->getTemporaryUrl('test-id');

        $this->assertEquals('https://minio.example.com/temp-url', $result);
    }

    public function test_get_temporary_url_with_custom_expiration(): void
    {
        $file = $this->createFileMock('test-id');

        $this->fileRepository
            ->shouldReceive('findById')
            ->with('test-id')
            ->once()
            ->andReturn($file);

        $this->fileRepository
            ->shouldReceive('temporaryUrl')
            ->with($file, 120)
            ->once()
            ->andReturn('https://minio.example.com/temp-url');

        $result = $this->fileService->getTemporaryUrl('test-id', 120);

        $this->assertEquals('https://minio.example.com/temp-url', $result);
    }

    public function test_get_temporary_url_throws_not_found_when_url_is_null(): void
    {
        $file = $this->createFileMock('test-id');

        $this->fileRepository
            ->shouldReceive('findById')
            ->with('test-id')
            ->once()
            ->andReturn($file);

        $this->fileRepository
            ->shouldReceive('temporaryUrl')
            ->with($file, 60)
            ->once()
            ->andReturn(null);

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('파일 데이터를 찾을 수 없습니다.');

        $this->fileService->getTemporaryUrl('test-id');
    }

    // ========================================
    // fileExists 테스트
    // ========================================

    public function test_file_exists_returns_true_when_exists(): void
    {
        $file = $this->createFileMock('test-id');

        $this->fileRepository
            ->shouldReceive('findById')
            ->with('test-id')
            ->once()
            ->andReturn($file);

        $this->fileRepository
            ->shouldReceive('exists')
            ->with($file)
            ->once()
            ->andReturn(true);

        $result = $this->fileService->fileExists('test-id');

        $this->assertTrue($result);
    }

    public function test_file_exists_returns_false_when_file_not_in_db(): void
    {
        $this->fileRepository
            ->shouldReceive('findById')
            ->with('nonexistent')
            ->once()
            ->andReturn(null);

        $result = $this->fileService->fileExists('nonexistent');

        $this->assertFalse($result);
    }

    // ========================================
    // Helper Methods
    // ========================================

    private function createFileMock(string $id): MockInterface
    {
        $file = Mockery::mock(File::class);
        $file->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn($id);

        return $file;
    }
}
