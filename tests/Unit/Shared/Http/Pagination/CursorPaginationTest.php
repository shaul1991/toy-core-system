<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Http\Pagination;

use App\Shared\Http\Pagination\CursorPagination;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Pagination\Cursor;
use PHPUnit\Framework\TestCase;

class CursorPaginationTest extends TestCase
{
    public function test_constructor_sets_all_properties(): void
    {
        $pagination = new CursorPagination(
            perPage: 15,
            nextCursor: 'eyJpZCI6MjB9',
            prevCursor: 'eyJpZCI6NX0=',
            hasMorePages: true
        );

        $this->assertEquals(15, $pagination->perPage);
        $this->assertEquals('eyJpZCI6MjB9', $pagination->nextCursor);
        $this->assertEquals('eyJpZCI6NX0=', $pagination->prevCursor);
        $this->assertTrue($pagination->hasMorePages);
    }

    public function test_constructor_with_null_cursors(): void
    {
        $pagination = new CursorPagination(
            perPage: 15,
            nextCursor: null,
            prevCursor: null,
            hasMorePages: false
        );

        $this->assertNull($pagination->nextCursor);
        $this->assertNull($pagination->prevCursor);
        $this->assertFalse($pagination->hasMorePages);
    }

    public function test_from_cursor_paginator_with_next_cursor(): void
    {
        $nextCursor = $this->createMock(Cursor::class);
        $nextCursor->method('encode')->willReturn('eyJpZCI6MjB9');

        $paginator = $this->createMock(CursorPaginator::class);
        $paginator->method('perPage')->willReturn(15);
        $paginator->method('nextCursor')->willReturn($nextCursor);
        $paginator->method('previousCursor')->willReturn(null);
        $paginator->method('hasMorePages')->willReturn(true);

        $pagination = CursorPagination::fromCursorPaginator($paginator);

        $this->assertEquals(15, $pagination->perPage);
        $this->assertEquals('eyJpZCI6MjB9', $pagination->nextCursor);
        $this->assertNull($pagination->prevCursor);
        $this->assertTrue($pagination->hasMorePages);
    }

    public function test_from_cursor_paginator_with_both_cursors(): void
    {
        $nextCursor = $this->createMock(Cursor::class);
        $nextCursor->method('encode')->willReturn('eyJpZCI6MzB9');

        $prevCursor = $this->createMock(Cursor::class);
        $prevCursor->method('encode')->willReturn('eyJpZCI6MTB9');

        $paginator = $this->createMock(CursorPaginator::class);
        $paginator->method('perPage')->willReturn(10);
        $paginator->method('nextCursor')->willReturn($nextCursor);
        $paginator->method('previousCursor')->willReturn($prevCursor);
        $paginator->method('hasMorePages')->willReturn(true);

        $pagination = CursorPagination::fromCursorPaginator($paginator);

        $this->assertEquals('eyJpZCI6MzB9', $pagination->nextCursor);
        $this->assertEquals('eyJpZCI6MTB9', $pagination->prevCursor);
    }

    public function test_from_cursor_paginator_first_page(): void
    {
        $nextCursor = $this->createMock(Cursor::class);
        $nextCursor->method('encode')->willReturn('eyJpZCI6MTV9');

        $paginator = $this->createMock(CursorPaginator::class);
        $paginator->method('perPage')->willReturn(15);
        $paginator->method('nextCursor')->willReturn($nextCursor);
        $paginator->method('previousCursor')->willReturn(null);
        $paginator->method('hasMorePages')->willReturn(true);

        $pagination = CursorPagination::fromCursorPaginator($paginator);

        $this->assertNotNull($pagination->nextCursor);
        $this->assertNull($pagination->prevCursor);
        $this->assertTrue($pagination->hasMorePages);
    }

    public function test_from_cursor_paginator_last_page(): void
    {
        $prevCursor = $this->createMock(Cursor::class);
        $prevCursor->method('encode')->willReturn('eyJpZCI6ODV9');

        $paginator = $this->createMock(CursorPaginator::class);
        $paginator->method('perPage')->willReturn(15);
        $paginator->method('nextCursor')->willReturn(null);
        $paginator->method('previousCursor')->willReturn($prevCursor);
        $paginator->method('hasMorePages')->willReturn(false);

        $pagination = CursorPagination::fromCursorPaginator($paginator);

        $this->assertNull($pagination->nextCursor);
        $this->assertNotNull($pagination->prevCursor);
        $this->assertFalse($pagination->hasMorePages);
    }

    public function test_from_cursor_paginator_single_page(): void
    {
        $paginator = $this->createMock(CursorPaginator::class);
        $paginator->method('perPage')->willReturn(15);
        $paginator->method('nextCursor')->willReturn(null);
        $paginator->method('previousCursor')->willReturn(null);
        $paginator->method('hasMorePages')->willReturn(false);

        $pagination = CursorPagination::fromCursorPaginator($paginator);

        $this->assertNull($pagination->nextCursor);
        $this->assertNull($pagination->prevCursor);
        $this->assertFalse($pagination->hasMorePages);
    }

    public function test_to_array_returns_correct_structure(): void
    {
        $pagination = new CursorPagination(
            perPage: 15,
            nextCursor: 'eyJpZCI6MjB9',
            prevCursor: 'eyJpZCI6NX0=',
            hasMorePages: true
        );

        $array = $pagination->toArray();

        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('per_page', $array);
        $this->assertArrayHasKey('next_cursor', $array);
        $this->assertArrayHasKey('prev_cursor', $array);
        $this->assertArrayHasKey('has_more_pages', $array);

        $this->assertEquals('cursor', $array['type']);
        $this->assertEquals(15, $array['per_page']);
        $this->assertEquals('eyJpZCI6MjB9', $array['next_cursor']);
        $this->assertEquals('eyJpZCI6NX0=', $array['prev_cursor']);
        $this->assertTrue($array['has_more_pages']);
    }

    public function test_to_array_with_null_cursors(): void
    {
        $pagination = new CursorPagination(
            perPage: 15,
            nextCursor: null,
            prevCursor: null,
            hasMorePages: false
        );

        $array = $pagination->toArray();

        $this->assertNull($array['next_cursor']);
        $this->assertNull($array['prev_cursor']);
        $this->assertFalse($array['has_more_pages']);
    }

    public function test_to_array_is_json_serializable(): void
    {
        $pagination = new CursorPagination(
            perPage: 15,
            nextCursor: 'eyJpZCI6MjB9',
            prevCursor: null,
            hasMorePages: true
        );

        $json = json_encode($pagination->toArray());
        $decoded = json_decode($json, true);

        $this->assertEquals($pagination->toArray(), $decoded);
    }

    public function test_readonly_properties(): void
    {
        $pagination = new CursorPagination(
            perPage: 15,
            nextCursor: 'eyJpZCI6MjB9',
            prevCursor: null,
            hasMorePages: true
        );

        $reflection = new \ReflectionClass($pagination);
        $this->assertTrue($reflection->isReadOnly());
    }

    public function test_different_per_page_values(): void
    {
        foreach ([5, 10, 15, 25, 50, 100] as $perPage) {
            $pagination = new CursorPagination(
                perPage: $perPage,
                nextCursor: 'cursor_string',
                prevCursor: null,
                hasMorePages: true
            );

            $this->assertEquals($perPage, $pagination->perPage);
            $this->assertEquals($perPage, $pagination->toArray()['per_page']);
        }
    }

    public function test_cursor_encoding_preservation(): void
    {
        $encodedCursor = base64_encode('{"id":20,"_pointsToNextItems":true}');

        $pagination = new CursorPagination(
            perPage: 15,
            nextCursor: $encodedCursor,
            prevCursor: null,
            hasMorePages: true
        );

        $this->assertEquals($encodedCursor, $pagination->nextCursor);
        $this->assertEquals($encodedCursor, $pagination->toArray()['next_cursor']);
    }
}
