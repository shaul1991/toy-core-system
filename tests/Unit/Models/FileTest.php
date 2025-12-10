<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\File;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    // ========================================
    // isPublic / isPrivate 테스트
    // ========================================

    public function test_is_public_returns_true_when_visibility_is_public(): void
    {
        $file = new File(['visibility' => 'public']);

        $this->assertTrue($file->isPublic());
        $this->assertFalse($file->isPrivate());
    }

    public function test_is_private_returns_true_when_visibility_is_private(): void
    {
        $file = new File(['visibility' => 'private']);

        $this->assertTrue($file->isPrivate());
        $this->assertFalse($file->isPublic());
    }

    // ========================================
    // full_path 속성 테스트
    // ========================================

    public function test_full_path_combines_path_and_stored_name(): void
    {
        $file = new File([
            'path' => '2025/01/01',
            'stored_name' => 'abc123.jpg',
        ]);

        $this->assertEquals('2025/01/01/abc123.jpg', $file->full_path);
    }

    public function test_full_path_with_root_path(): void
    {
        $file = new File([
            'path' => '',
            'stored_name' => 'test.pdf',
        ]);

        $this->assertEquals('/test.pdf', $file->full_path);
    }

    // ========================================
    // human_readable_size 속성 테스트
    // ========================================

    public function test_human_readable_size_for_bytes(): void
    {
        $file = new File(['size' => 500]);

        $this->assertEquals('500 B', $file->human_readable_size);
    }

    public function test_human_readable_size_for_kilobytes(): void
    {
        $file = new File(['size' => 1536]); // 1.5 KB

        $this->assertEquals('1.5 KB', $file->human_readable_size);
    }

    public function test_human_readable_size_for_megabytes(): void
    {
        $file = new File(['size' => 5242880]); // 5 MB

        $this->assertEquals('5 MB', $file->human_readable_size);
    }

    public function test_human_readable_size_for_gigabytes(): void
    {
        $file = new File(['size' => 2147483648]); // 2 GB

        $this->assertEquals('2 GB', $file->human_readable_size);
    }

    // ========================================
    // url 속성 테스트
    // ========================================

    public function test_url_returns_null_for_private_file(): void
    {
        $file = new File(['visibility' => 'private']);

        $this->assertNull($file->url);
    }

    // ========================================
    // fillable 속성 테스트
    // ========================================

    public function test_fillable_attributes(): void
    {
        $attributes = [
            'original_name' => 'test.jpg',
            'stored_name' => 'abc123.jpg',
            'path' => '2025/01/01',
            'disk' => 'minio-public',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'visibility' => 'public',
            'metadata' => ['key' => 'value'],
        ];

        $file = new File($attributes);

        $this->assertEquals('test.jpg', $file->original_name);
        $this->assertEquals('abc123.jpg', $file->stored_name);
        $this->assertEquals('2025/01/01', $file->path);
        $this->assertEquals('minio-public', $file->disk);
        $this->assertEquals('image/jpeg', $file->mime_type);
        $this->assertEquals(1024, $file->size);
        $this->assertEquals('public', $file->visibility);
        $this->assertEquals(['key' => 'value'], $file->metadata);
    }

    // ========================================
    // casts 테스트
    // ========================================

    public function test_size_is_cast_to_integer(): void
    {
        $file = new File(['size' => '1024']);

        $this->assertIsInt($file->size);
        $this->assertEquals(1024, $file->size);
    }

    public function test_metadata_is_cast_to_array(): void
    {
        $file = new File;
        $file->metadata = ['key' => 'value'];

        $this->assertIsArray($file->metadata);
    }
}
