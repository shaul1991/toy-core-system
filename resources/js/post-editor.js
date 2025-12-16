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
        imageUploadUrl = '/api/files',  // ✅ BFF API 사용
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
                    const token = getAuthToken();
                    if (!token) {
                        alert('로그인이 필요합니다.');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('file', blob);
                    formData.append('visibility', 'public');

                    const response = await fetch(imageUploadUrl, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${token}`,  // ✅ JWT 인증
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
    // ✅ BFF API 사용 (/api/*)
    const url = isUpdate
        ? `/api/posts/${postData.id}`
        : '/api/posts';

    const method = isUpdate ? 'PUT' : 'POST';

    try {
        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${getAuthToken()}`,  // ✅ JWT 인증
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
        // ✅ BFF API 사용 (/api/*)
        const response = await fetch(`/api/posts/${postId}`, {
            headers: {
                'Authorization': `Bearer ${getAuthToken()}`,  // ✅ JWT 인증
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
 * 현재 로그인한 사용자 인증 토큰 가져오기
 *
 * ⚠️ 중요: X-User-Id 헤더로 사용자 ID를 직접 전달하는 방식은 보안 취약점입니다.
 * JWT 토큰을 사용하여 BFF에서 검증하도록 해야 합니다.
 */
function getAuthToken() {
    // JWT 토큰을 localStorage에서 가져오기
    // 실제 프로덕션에서는 HttpOnly 쿠키 사용 권장
    return localStorage.getItem('authToken') || '';
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
