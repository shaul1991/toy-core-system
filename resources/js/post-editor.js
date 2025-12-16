import Editor from '@toast-ui/editor';
import '@toast-ui/editor/dist/toastui-editor.css';

/**
 * Toast UI Editor 초기화
 */
export function initPostEditor(options = {}) {
    const {
        elementId = 'editor',
        initialValue = '',
        height = '600px',
        imageUploadUrl = '/internal/files',
        onSave = null,
        placeholder = '내용을 입력하세요...',
    } = options;

    const editorElement = document.querySelector(`#${elementId}`);
    if (!editorElement) {
        console.error(`Editor element #${elementId} not found`);
        return null;
    }

    const editor = new Editor({
        el: editorElement,
        height,
        initialEditType: 'markdown',
        previewStyle: 'vertical',
        initialValue,
        placeholder,
        usageStatistics: false,
        toolbarItems: [
            ['heading', 'bold', 'italic', 'strike'],
            ['hr', 'quote'],
            ['ul', 'ol', 'task', 'indent', 'outdent'],
            ['table', 'image', 'link'],
            ['code', 'codeblock'],
            ['scrollSync'],
        ],
        hooks: {
            addImageBlobHook: async (blob, callback) => {
                try {
                    const formData = new FormData();
                    formData.append('file', blob);
                    formData.append('visibility', 'public');

                    const response = await fetch(imageUploadUrl, {
                        method: 'POST',
                        headers: {
                            'X-User-Id': getUserId(),
                        },
                        body: formData,
                    });

                    if (!response.ok) {
                        throw new Error('Failed to upload image');
                    }

                    const result = await response.json();

                    if (result.success && result.data) {
                        // MinIO public URL 사용
                        const imageUrl = result.data.public_url || result.data.url;
                        callback(imageUrl, blob.name);
                    } else {
                        throw new Error(result.error?.message || 'Upload failed');
                    }
                } catch (error) {
                    console.error('Image upload error:', error);
                    alert('이미지 업로드에 실패했습니다.');
                }
            },
        },
    });

    // 저장 버튼 이벤트
    if (onSave) {
        const saveButton = document.querySelector('#save-post');
        if (saveButton) {
            saveButton.addEventListener('click', () => {
                const markdown = editor.getMarkdown();
                onSave(markdown);
            });
        }
    }

    return editor;
}

/**
 * 게시물 저장
 */
export async function savePost(postData, isUpdate = false) {
    const url = isUpdate
        ? `/internal/posts/${postData.id}`
        : '/internal/posts';

    const method = isUpdate ? 'PUT' : 'POST';

    try {
        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-User-Id': getUserId(),
            },
            body: JSON.stringify(postData),
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.error?.message || 'Failed to save post');
        }

        return result.data;
    } catch (error) {
        console.error('Save post error:', error);
        throw error;
    }
}

/**
 * 게시물 불러오기
 */
export async function loadPost(postId) {
    try {
        const response = await fetch(`/internal/posts/${postId}`, {
            headers: {
                'X-User-Id': getUserId(),
            },
        });

        const result = await response.json();

        if (!response.ok || !result.success) {
            throw new Error(result.error?.message || 'Failed to load post');
        }

        return result.data;
    } catch (error) {
        console.error('Load post error:', error);
        throw error;
    }
}

/**
 * 현재 로그인한 사용자 ID 가져오기 (임시)
 */
function getUserId() {
    // TODO: 실제 인증 구현 시 JWT 토큰에서 추출하거나 세션에서 가져오기
    const userId = localStorage.getItem('userId') || '1';
    return userId;
}

/**
 * Excerpt 자동 생성
 */
export function generateExcerpt(markdown, maxLength = 200) {
    // Markdown 문법 제거
    const plainText = markdown
        .replace(/^#{1,6}\s+/gm, '') // 헤딩
        .replace(/\*\*([^*]+)\*\*/g, '$1') // Bold
        .replace(/\*([^*]+)\*/g, '$1') // Italic
        .replace(/\[([^\]]+)\]\([^)]+\)/g, '$1') // Link
        .replace(/!\[([^\]]*)\]\([^)]+\)/g, '') // Image
        .replace(/`([^`]+)`/g, '$1') // Inline code
        .replace(/```[\s\S]*?```/g, '') // Code block
        .replace(/^\s*[-*+]\s+/gm, '') // List
        .replace(/^\s*\d+\.\s+/gm, '') // Ordered list
        .replace(/^\s*>\s+/gm, '') // Blockquote
        .replace(/\n{2,}/g, ' ') // Multiple newlines
        .trim();

    return plainText.length > maxLength
        ? plainText.substring(0, maxLength) + '...'
        : plainText;
}
