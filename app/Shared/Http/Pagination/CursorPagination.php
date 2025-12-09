<?php

declare(strict_types=1);

namespace App\Shared\Http\Pagination;

use Illuminate\Contracts\Pagination\CursorPaginator;

final readonly class CursorPagination
{
    public function __construct(
        public int $perPage,
        public ?string $nextCursor,
        public ?string $prevCursor,
        public bool $hasMorePages,
    ) {}

    public static function fromCursorPaginator(CursorPaginator $paginator): self
    {
        return new self(
            perPage: $paginator->perPage(),
            nextCursor: $paginator->nextCursor()?->encode(),
            prevCursor: $paginator->previousCursor()?->encode(),
            hasMorePages: $paginator->hasMorePages(),
        );
    }

    public function toArray(): array
    {
        return [
            'type' => 'cursor',
            'per_page' => $this->perPage,
            'next_cursor' => $this->nextCursor,
            'prev_cursor' => $this->prevCursor,
            'has_more_pages' => $this->hasMorePages,
        ];
    }
}
