<?php

namespace App\Http\Controllers;

use App\Domain\Post\Services\PostService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostViewController extends Controller
{
    public function __construct(
        private readonly PostService $postService,
    ) {}

    /**
     * 게시물 목록 (웹)
     */
    public function index(): View
    {
        return view('posts.index');
    }

    /**
     * 새 게시물 작성 (웹)
     */
    public function create(): View
    {
        return view('posts.editor');
    }

    /**
     * 게시물 수정 (웹)
     */
    public function edit(int $id): View
    {
        $post = $this->postService->getPostById($id);

        // 권한 확인: 작성자만 수정 가능
        if ($post->user_id !== auth()->id()) {
            abort(403, '게시물 수정 권한이 없습니다.');
        }

        return view('posts.editor', ['post' => $post]);
    }

    /**
     * 게시물 상세 (웹)
     */
    public function show(string $slug): View
    {
        $post = $this->postService->getPublishedPostBySlug($slug);

        return view('posts.show', ['post' => $post]);
    }
}
