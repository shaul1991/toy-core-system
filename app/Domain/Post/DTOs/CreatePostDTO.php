<?php

declare(strict_types=1);

namespace App\Domain\Post\DTOs;

use Illuminate\Http\Request;

final readonly class CreatePostDTO
{
    public function __construct(
        public int $userId,
        public string $title,
        public string $content,
        public ?string $excerpt = null,
        public ?string $slug = null,
        public string $status = 'draft',
    ) {}

    public static function fromRequest(Request $request, int $userId): self
    {
        return new self(
            userId: $userId,
            title: $request->input('title'),
            content: $request->input('content'),
            excerpt: $request->input('excerpt'),
            slug: $request->input('slug'),
            status: $request->input('status', 'draft'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'title' => $this->title,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'slug' => $this->slug,
            'status' => $this->status,
        ];
    }
}
