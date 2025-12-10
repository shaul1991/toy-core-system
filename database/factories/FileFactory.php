<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\File;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\File>
 */
class FileFactory extends Factory
{
    protected $model = File::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $visibility = fake()->randomElement(['public', 'private']);
        $extension = fake()->randomElement(['jpg', 'png', 'pdf', 'txt', 'doc']);
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'doc' => 'application/msword',
        ];

        return [
            'original_name' => fake()->word().'.'.$extension,
            'stored_name' => Str::uuid()->toString().'.'.$extension,
            'path' => now()->format('Y/m/d'),
            'disk' => $visibility === 'public' ? 'minio-public' : 'minio-private',
            'mime_type' => $mimeTypes[$extension],
            'size' => fake()->numberBetween(1024, 10 * 1024 * 1024), // 1KB - 10MB
            'visibility' => $visibility,
            'metadata' => null,
        ];
    }

    /**
     * 공개 파일 상태로 설정
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'public',
            'disk' => 'minio-public',
        ]);
    }

    /**
     * 비공개 파일 상태로 설정
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'private',
            'disk' => 'minio-private',
        ]);
    }

    /**
     * 메타데이터 포함
     */
    public function withMetadata(array $metadata): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => $metadata,
        ]);
    }

    /**
     * 특정 MIME 타입으로 설정
     */
    public function mimeType(string $mimeType): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => $mimeType,
        ]);
    }
}
