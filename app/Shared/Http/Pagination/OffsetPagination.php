<?php

declare(strict_types=1);

namespace App\Shared\Http\Pagination;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class OffsetPagination
{
    public function __construct(
        public int $page,
        public int $perPage,
        public int $total,
        public int $lastPage,
        public bool $hasMorePages,
    ) {}

    public static function fromLengthAwarePaginator(LengthAwarePaginator $paginator): self
    {
        return new self(
            page: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
            lastPage: $paginator->lastPage(),
            hasMorePages: $paginator->hasMorePages(),
        );
    }

    public function toArray(): array
    {
        return [
            'type' => 'offset',
            'page' => $this->page,
            'per_page' => $this->perPage,
            'total' => $this->total,
            'last_page' => $this->lastPage,
            'has_more_pages' => $this->hasMorePages,
        ];
    }
}
