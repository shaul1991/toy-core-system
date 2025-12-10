<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    use HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'original_name',
        'stored_name',
        'path',
        'disk',
        'mime_type',
        'size',
        'visibility',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'metadata' => 'array',
        ];
    }

    /**
     * 파일이 공개 상태인지 확인
     */
    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    /**
     * 파일이 비공개 상태인지 확인
     */
    public function isPrivate(): bool
    {
        return $this->visibility === 'private';
    }

    /**
     * 파일의 전체 경로 반환
     */
    public function getFullPathAttribute(): string
    {
        return $this->path.'/'.$this->stored_name;
    }

    /**
     * 파일의 공개 URL 반환 (public 파일만)
     */
    public function getUrlAttribute(): ?string
    {
        if ($this->isPrivate()) {
            return null;
        }

        return Storage::disk($this->disk)->url($this->full_path);
    }

    /**
     * 파일 크기를 사람이 읽기 쉬운 형식으로 반환
     */
    public function getHumanReadableSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
