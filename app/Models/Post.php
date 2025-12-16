<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'content',
        'excerpt',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    /**
     * Model boot 메서드 - 자동 slug 생성
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Post $post): void {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);

                // slug 중복 방지 (최대 100회 시도)
                $originalSlug = $post->slug;
                $count = 1;
                $maxAttempts = 100;

                while (static::where('slug', $post->slug)->exists() && $count <= $maxAttempts) {
                    $post->slug = "{$originalSlug}-{$count}";
                    $count++;
                }

                // 최대 시도 횟수 초과 시 랜덤 접미사 추가
                if ($count > $maxAttempts) {
                    $post->slug = "{$originalSlug}-".Str::random(8);
                }
            }

            // 발행 상태로 변경 시 published_at 자동 설정
            if ($post->status === 'published' && $post->published_at === null) {
                $post->published_at = now();
            }
        });

        static::updating(function (Post $post): void {
            // draft → published 전환 시 published_at 자동 설정
            if ($post->isDirty('status') && $post->status === 'published' && $post->published_at === null) {
                $post->published_at = now();
            }
        });
    }

    /**
     * 작성자 관계
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 발행된 게시물만 조회
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at');
    }

    /**
     * 임시저장 게시물만 조회
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * 특정 사용자의 게시물 조회
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 게시물이 발행 상태인지 확인
     */
    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at !== null;
    }

    /**
     * 게시물이 임시저장 상태인지 확인
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * 게시물을 발행 상태로 변경
     */
    public function publish(): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => $this->published_at ?? now(),
        ]);
    }

    /**
     * 게시물을 임시저장 상태로 변경
     */
    public function unpublish(): void
    {
        $this->update([
            'status' => 'draft',
        ]);
    }
}
