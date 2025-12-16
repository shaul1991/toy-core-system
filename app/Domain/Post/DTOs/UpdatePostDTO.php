<?php

declare(strict_types=1);

namespace App\Domain\Post\DTOs;

use Illuminate\Http\Request;

final readonly class UpdatePostDTO
{
    public function __construct(
        public ?string $title = null,
        public ?string $content = null,
        public ?string $excerpt = null,
        public ?string $slug = null,
        public ?string $status = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            title: $request->input('title'),
            content: $request->input('content'),
            excerpt: $request->input('excerpt'),
            slug: $request->input('slug'),
            status: $request->input('status'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'title' => $this->title,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'slug' => $this->slug,
            'status' => $this->status,
        ], fn ($value) => $value !== null);
    }
}
