/**
 * API 클라이언트
 *
 * Backend API와 통신하는 클라이언트입니다.
 * - 쿠키 기반 토큰 관리 (HttpOnly 쿠키)
 * - 인증 자동 처리 (credentials: include)
 * - 에러 핸들링
 */

/**
 * API URL - 환경 변수 또는 상대 경로 사용
 * 프로덕션에서는 반드시 NEXT_PUBLIC_API_URL이 설정되어야 함
 * 미설정 시 같은 origin의 /api로 요청
 */
const API_URL = process.env.NEXT_PUBLIC_API_URL || '/api';

/**
 * API 응답 타입
 */
interface ApiResponse<T = unknown> {
  success: boolean;
  data?: T;
  message?: string;
  error?: {
    code: string;
    message: string;
    details?: Record<string, unknown>;
  };
}

/**
 * 토큰 응답 타입
 */
export interface TokenResponse {
  access_token: string;
  refresh_token: string;
  token_type: string;
  expires_in: number;
  user: {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
  };
}

/**
 * 사용자 정보 타입
 */
export interface User {
  id: number;
  name: string;
  email: string;
  avatar: string | null;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
}

/**
 * 쿠키에서 값 읽기 (클라이언트 사이드, httpOnly가 아닌 쿠키만)
 */
function getCookie(name: string): string | null {
  if (typeof document === 'undefined') return null;
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return parts.pop()?.split(';').shift() || null;
  return null;
}

/**
 * 쿠키 삭제 (httpOnly가 아닌 쿠키만)
 */
function deleteCookie(name: string): void {
  if (typeof document === 'undefined') return;
  document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
}

/**
 * 인증 상태 확인 (token_type 쿠키로 확인)
 */
export function isAuthenticated(): boolean {
  return getCookie('token_type') !== null;
}

/**
 * 토큰 쿠키 삭제 (로그아웃 시 클라이언트 측에서 호출)
 * 참고: httpOnly 쿠키(access_token, refresh_token)는 서버에서만 삭제 가능
 */
export function clearTokenCookie(): void {
  deleteCookie('token_type');
}

/**
 * 토큰 갱신 중인 Promise를 추적하여 동시 갱신 요청 방지
 */
let refreshPromise: Promise<boolean> | null = null;

/**
 * Access Token 갱신 (서버에서 쿠키 갱신)
 * 동시 요청 시 하나의 갱신 요청만 실행되도록 보장
 */
async function refreshAccessToken(): Promise<boolean> {
  // 이미 갱신 중이면 기존 Promise 반환
  if (refreshPromise) {
    return refreshPromise;
  }

  // 새로운 갱신 요청 시작
  refreshPromise = (async () => {
    try {
      const response = await fetch(`${API_URL}/auth/refresh`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        credentials: 'include', // refresh_token 쿠키 전송
      });

      return response.ok;
    } catch {
      return false;
    } finally {
      // 갱신 완료 후 Promise 초기화
      refreshPromise = null;
    }
  })();

  return refreshPromise;
}

/**
 * 응답 본문을 JSON으로 파싱
 *
 * 비 JSON 응답(HTML, 빈 본문 등)에 대한 에러 처리 포함
 */
async function parseResponseBody<T>(response: Response): Promise<ApiResponse<T>> {
  const contentType = response.headers.get('content-type');

  // 빈 응답 처리 (204 No Content 등)
  if (response.status === 204 || response.headers.get('content-length') === '0') {
    return {
      success: response.ok,
      data: undefined,
    };
  }

  // JSON 응답 시도
  if (contentType?.includes('application/json')) {
    try {
      return await response.json();
    } catch {
      // JSON 파싱 실패
      return {
        success: false,
        error: {
          code: 'PARSE_ERROR',
          message: 'JSON 응답 파싱에 실패했습니다.',
          details: { status: response.status, statusText: response.statusText },
        },
      };
    }
  }

  // 비 JSON 응답 처리 (HTML, 텍스트 등)
  const rawBody = await response.text();
  return {
    success: false,
    error: {
      code: 'UNEXPECTED_RESPONSE',
      message: `서버가 예상하지 못한 응답을 반환했습니다. (${response.status} ${response.statusText})`,
      details: {
        status: response.status,
        statusText: response.statusText,
        contentType: contentType || 'unknown',
        body: rawBody.substring(0, 500), // 긴 HTML 응답 방지
      },
    },
  };
}

/**
 * API 요청 함수
 *
 * credentials: 'include'로 쿠키 자동 전송
 */
async function request<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<ApiResponse<T>> {
  const url = `${API_URL}${endpoint}`;

  const headers: HeadersInit = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    ...options.headers,
  };

  const response = await fetch(url, {
    ...options,
    headers,
    credentials: 'include', // 쿠키 자동 전송
  });

  const data = await parseResponseBody<T>(response);

  // 401 에러 처리 - 토큰 만료
  if (response.status === 401 && !endpoint.includes('/auth/refresh')) {
    // 토큰 갱신 시도 (동시 요청 시 하나만 실행됨)
    const refreshed = await refreshAccessToken();
    if (refreshed) {
      // 토큰 갱신 성공 시 원래 요청 재시도
      return request<T>(endpoint, options);
    }
    // 토큰 갱신 실패 시 쿠키 정리 후 에러 응답 반환
    // 리다이렉트는 호출자가 결정 (폼 상태 보존, 모달 표시 등)
    clearTokenCookie();
    return {
      success: false,
      error: {
        code: 'UNAUTHORIZED',
        message: '인증이 만료되었습니다. 다시 로그인해주세요.',
        details: {
          status: 401,
          requiresLogin: true,
        },
      },
    };
  }

  return data;
}

/**
 * API 클라이언트
 */
export const apiClient = {
  /**
   * GET 요청
   */
  get: <T>(endpoint: string, options?: RequestInit) =>
    request<T>(endpoint, { ...options, method: 'GET' }),

  /**
   * POST 요청
   */
  post: <T>(endpoint: string, body?: unknown, options?: RequestInit) =>
    request<T>(endpoint, {
      ...options,
      method: 'POST',
      body: body ? JSON.stringify(body) : undefined,
    }),

  /**
   * PUT 요청
   */
  put: <T>(endpoint: string, body?: unknown, options?: RequestInit) =>
    request<T>(endpoint, {
      ...options,
      method: 'PUT',
      body: body ? JSON.stringify(body) : undefined,
    }),

  /**
   * DELETE 요청
   */
  delete: <T>(endpoint: string, options?: RequestInit) =>
    request<T>(endpoint, { ...options, method: 'DELETE' }),
};

/**
 * Auth API
 */
export const authApi = {
  /**
   * 소셜 로그인 리다이렉트 URL
   */
  getSocialLoginUrl: (provider: 'github' | 'naver' | 'kakao') =>
    `${API_URL}/auth/${provider}/redirect`,

  /**
   * 토큰 갱신 (서버에서 쿠키 갱신)
   */
  refresh: () => apiClient.post('/auth/refresh'),

  /**
   * 토큰 검증
   */
  validate: () => apiClient.post<{ valid: boolean; user?: User }>('/auth/validate'),

  /**
   * 현재 사용자 정보
   */
  me: () => apiClient.get<User>('/auth/me'),

  /**
   * 로그아웃
   */
  logout: async () => {
    const result = await apiClient.post('/auth/logout');
    clearTokenCookie();
    return result;
  },

  /**
   * 전체 세션 로그아웃
   */
  logoutAll: async () => {
    const result = await apiClient.post('/auth/logout-all');
    clearTokenCookie();
    return result;
  },

  /**
   * 연동된 소셜 계정 목록
   */
  socialAccounts: () => apiClient.get<Array<{
    provider: string;
    provider_email: string;
    created_at: string;
  }>>('/auth/social-accounts'),

  /**
   * 소셜 계정 연동 해제
   */
  unlinkSocialAccount: (provider: 'github' | 'naver' | 'kakao') =>
    apiClient.delete(`/auth/${provider}/unlink`),
};
