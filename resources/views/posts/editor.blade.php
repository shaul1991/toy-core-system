@extends('layouts.app')

@section('title', isset($post) ? '게시물 수정' : '새 게시물')

@push('styles')
<style>
    .editor-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
    }

    .editor-header {
        margin-bottom: 2rem;
    }

    .editor-actions {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .btn {
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-primary {
        background-color: #3b82f6;
        color: white;
    }

    .btn-primary:hover {
        background-color: #2563eb;
    }

    .btn-secondary {
        background-color: #6b7280;
        color: white;
    }

    .btn-secondary:hover {
        background-color: #4b5563;
    }

    .btn-success {
        background-color: #10b981;
        color: white;
    }

    .btn-success:hover {
        background-color: #059669;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }

    .form-input {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 1rem;
    }

    .form-input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .form-select {
        width: 200px;
        padding: 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
    }

    #editor {
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
    }

    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 0.25rem;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .status-draft {
        background-color: #fef3c7;
        color: #92400e;
    }

    .status-published {
        background-color: #d1fae5;
        color: #065f46;
    }

    .status-private {
        background-color: #e5e7eb;
        color: #374151;
    }
</style>
@endpush

@section('content')
<div class="editor-container">
    <div class="editor-header">
        <h1 class="text-3xl font-bold mb-4">
            {{ isset($post) ? '게시물 수정' : '새 게시물' }}
        </h1>

        @if(isset($post))
        <div class="mb-4">
            <span class="status-badge status-{{ $post->status }}">
                {{ ucfirst($post->status) }}
            </span>
            @if($post->published_at)
                <span class="text-gray-600 ml-2">
                    발행일: {{ $post->published_at->format('Y-m-d H:i') }}
                </span>
            @endif
        </div>
        @endif

        <div class="form-group">
            <label class="form-label" for="title">제목</label>
            <input
                type="text"
                id="title"
                class="form-input"
                placeholder="게시물 제목을 입력하세요"
                value="{{ $post->title ?? '' }}"
                required
            >
        </div>

        <div class="form-group">
            <label class="form-label" for="slug">슬러그 (URL)</label>
            <input
                type="text"
                id="slug"
                class="form-input"
                placeholder="자동 생성 (선택사항)"
                value="{{ $post->slug ?? '' }}"
            >
            <small class="text-gray-600">비워두면 제목에서 자동으로 생성됩니다</small>
        </div>

        <div class="editor-actions">
            <button type="button" id="save-draft" class="btn btn-secondary">
                임시저장
            </button>
            <button type="button" id="save-publish" class="btn btn-success">
                발행하기
            </button>
            <select id="status" class="form-select">
                <option value="draft" {{ isset($post) && $post->status === 'draft' ? 'selected' : '' }}>
                    임시저장
                </option>
                <option value="published" {{ isset($post) && $post->status === 'published' ? 'selected' : '' }}>
                    발행됨
                </option>
                <option value="private" {{ isset($post) && $post->status === 'private' ? 'selected' : '' }}>
                    비공개
                </option>
            </select>
        </div>
    </div>

    <div id="editor"></div>
</div>
@endsection

@push('scripts')
<script type="module">
    import { initPostEditor, savePost, generateExcerpt } from '/resources/js/post-editor.js';

    // 게시물 데이터 (서버에서 전달)
    const postData = @json($post ?? null);
    const isUpdate = postData !== null;

    // 에디터 초기화
    const editor = initPostEditor({
        elementId: 'editor',
        initialValue: postData?.content || '',
        height: '600px',
        imageUploadUrl: '/internal/files',
        placeholder: 'Markdown 형식으로 내용을 입력하세요...',
    });

    // 임시저장 버튼
    document.getElementById('save-draft').addEventListener('click', async () => {
        await handleSave('draft');
    });

    // 발행하기 버튼
    document.getElementById('save-publish').addEventListener('click', async () => {
        await handleSave('published');
    });

    // 저장 처리
    async function handleSave(status) {
        const title = document.getElementById('title').value.trim();
        if (!title) {
            alert('제목을 입력하세요.');
            return;
        }

        const content = editor.getMarkdown();
        if (!content.trim()) {
            alert('내용을 입력하세요.');
            return;
        }

        const slug = document.getElementById('slug').value.trim();
        const excerpt = generateExcerpt(content);

        const data = {
            title,
            content,
            excerpt,
            status,
        };

        if (slug) {
            data.slug = slug;
        }

        if (isUpdate) {
            data.id = postData.id;
        }

        try {
            const savedPost = await savePost(data, isUpdate);
            alert(isUpdate ? '게시물이 수정되었습니다.' : '게시물이 저장되었습니다.');

            // 목록으로 이동 또는 편집 페이지로 이동
            if (!isUpdate) {
                window.location.href = `/posts/${savedPost.id}/edit`;
            }
        } catch (error) {
            alert('저장에 실패했습니다: ' + error.message);
        }
    }

    // 상태 선택 변경 이벤트
    document.getElementById('status').addEventListener('change', (e) => {
        const status = e.target.value;
        if (status === 'published') {
            handleSave('published');
        } else if (status === 'draft') {
            handleSave('draft');
        } else if (status === 'private') {
            handleSave('private');
        }
    });
</script>
@endpush
