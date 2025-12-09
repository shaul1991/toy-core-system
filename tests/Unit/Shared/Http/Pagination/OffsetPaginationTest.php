<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Http\Pagination;

use App\Shared\Http\Pagination\OffsetPagination;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OffsetPaginationTest extends TestCase
{
    public function test_constructor_sets_all_properties(): void
    {
        $pagination = new OffsetPagination(
            page: 2,
            perPage: 15,
            total: 100,
            lastPage: 7,
            hasMorePages: true
        );

        $this->assertEquals(2, $pagination->page);
        $this->assertEquals(15, $pagination->perPage);
        $this->assertEquals(100, $pagination->total);
        $this->assertEquals(7, $pagination->lastPage);
        $this->assertTrue($pagination->hasMorePages);
    }

    public function test_from_length_aware_paginator(): void
    {
        $paginator = $this->createMock(LengthAwarePaginator::class);
        $paginator->method('currentPage')->willReturn(3);
        $paginator->method('perPage')->willReturn(20);
        $paginator->method('total')->willReturn(150);
        $paginator->method('lastPage')->willReturn(8);
        $paginator->method('hasMorePages')->willReturn(true);

        $pagination = OffsetPagination::fromLengthAwarePaginator($paginator);

        $this->assertEquals(3, $pagination->page);
        $this->assertEquals(20, $pagination->perPage);
        $this->assertEquals(150, $pagination->total);
        $this->assertEquals(8, $pagination->lastPage);
        $this->assertTrue($pagination->hasMorePages);
    }

    public function test_to_array_returns_correct_structure(): void
    {
        $pagination = new OffsetPagination(
            page: 1,
            perPage: 15,
            total: 100,
            lastPage: 7,
            hasMorePages: true
        );

        $array = $pagination->toArray();

        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('page', $array);
        $this->assertArrayHasKey('per_page', $array);
        $this->assertArrayHasKey('total', $array);
        $this->assertArrayHasKey('last_page', $array);
        $this->assertArrayHasKey('has_more_pages', $array);

        $this->assertEquals('offset', $array['type']);
        $this->assertEquals(1, $array['page']);
        $this->assertEquals(15, $array['per_page']);
        $this->assertEquals(100, $array['total']);
        $this->assertEquals(7, $array['last_page']);
        $this->assertTrue($array['has_more_pages']);
    }

    public function test_first_page(): void
    {
        $paginator = $this->createMock(LengthAwarePaginator::class);
        $paginator->method('currentPage')->willReturn(1);
        $paginator->method('perPage')->willReturn(10);
        $paginator->method('total')->willReturn(50);
        $paginator->method('lastPage')->willReturn(5);
        $paginator->method('hasMorePages')->willReturn(true);

        $pagination = OffsetPagination::fromLengthAwarePaginator($paginator);

        $this->assertEquals(1, $pagination->page);
        $this->assertTrue($pagination->hasMorePages);
    }

    public function test_last_page(): void
    {
        $paginator = $this->createMock(LengthAwarePaginator::class);
        $paginator->method('currentPage')->willReturn(5);
        $paginator->method('perPage')->willReturn(10);
        $paginator->method('total')->willReturn(50);
        $paginator->method('lastPage')->willReturn(5);
        $paginator->method('hasMorePages')->willReturn(false);

        $pagination = OffsetPagination::fromLengthAwarePaginator($paginator);

        $this->assertEquals(5, $pagination->page);
        $this->assertEquals(5, $pagination->lastPage);
        $this->assertFalse($pagination->hasMorePages);
    }

    public function test_single_page_result(): void
    {
        $paginator = $this->createMock(LengthAwarePaginator::class);
        $paginator->method('currentPage')->willReturn(1);
        $paginator->method('perPage')->willReturn(15);
        $paginator->method('total')->willReturn(10);
        $paginator->method('lastPage')->willReturn(1);
        $paginator->method('hasMorePages')->willReturn(false);

        $pagination = OffsetPagination::fromLengthAwarePaginator($paginator);

        $this->assertEquals(1, $pagination->page);
        $this->assertEquals(1, $pagination->lastPage);
        $this->assertEquals(10, $pagination->total);
        $this->assertFalse($pagination->hasMorePages);
    }

    public function test_empty_result(): void
    {
        $paginator = $this->createMock(LengthAwarePaginator::class);
        $paginator->method('currentPage')->willReturn(1);
        $paginator->method('perPage')->willReturn(15);
        $paginator->method('total')->willReturn(0);
        $paginator->method('lastPage')->willReturn(1);
        $paginator->method('hasMorePages')->willReturn(false);

        $pagination = OffsetPagination::fromLengthAwarePaginator($paginator);

        $this->assertEquals(0, $pagination->total);
        $this->assertEquals(1, $pagination->lastPage);
        $this->assertFalse($pagination->hasMorePages);
    }

    #[DataProvider('pageCalculationProvider')]
    public function test_page_calculation_scenarios(
        int $page,
        int $perPage,
        int $total,
        int $expectedLastPage,
        bool $expectedHasMorePages
    ): void {
        $paginator = $this->createMock(LengthAwarePaginator::class);
        $paginator->method('currentPage')->willReturn($page);
        $paginator->method('perPage')->willReturn($perPage);
        $paginator->method('total')->willReturn($total);
        $paginator->method('lastPage')->willReturn($expectedLastPage);
        $paginator->method('hasMorePages')->willReturn($expectedHasMorePages);

        $pagination = OffsetPagination::fromLengthAwarePaginator($paginator);

        $this->assertEquals($expectedLastPage, $pagination->lastPage);
        $this->assertEquals($expectedHasMorePages, $pagination->hasMorePages);
    }

    public static function pageCalculationProvider(): array
    {
        return [
            'exact division' => [1, 10, 100, 10, true],
            'with remainder' => [1, 10, 95, 10, true],
            'last page exact' => [10, 10, 100, 10, false],
            'last page with remainder' => [10, 10, 95, 10, false],
            'middle page' => [5, 10, 100, 10, true],
            'large per_page' => [1, 100, 50, 1, false],
        ];
    }

    public function test_to_array_is_json_serializable(): void
    {
        $pagination = new OffsetPagination(
            page: 1,
            perPage: 15,
            total: 100,
            lastPage: 7,
            hasMorePages: true
        );

        $json = json_encode($pagination->toArray());
        $decoded = json_decode($json, true);

        $this->assertEquals($pagination->toArray(), $decoded);
    }

    public function test_readonly_properties(): void
    {
        $pagination = new OffsetPagination(
            page: 1,
            perPage: 15,
            total: 100,
            lastPage: 7,
            hasMorePages: true
        );

        $reflection = new \ReflectionClass($pagination);
        $this->assertTrue($reflection->isReadOnly());
    }
}
