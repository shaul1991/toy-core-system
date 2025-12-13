/**
 * API 클라이언트
 *
 * Backend API와 통신하는 클라이언트입니다.
 * - 토큰 관리 (저장, 갱신)
 * - 인증 헤더 자동 추가
 * - 에러 핸들링
 */

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

// 토큰 저장소 키
const ACCESS_TOKEN_KEY = 'access_token';
const REFRESH_TOKEN_KEY = 'refresh_token';

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
 * 토큰 저장
 */
export function saveTokens(accessToken: string, refreshToken: string): void {
  if (typeof window === 'undefined') return;
  localStorage.setItem(ACCESS_TOKEN_KEY, accessToken);
  localStorage.setItem(REFRESH_TOKEN_KEY, refreshToken);
}

/**
 * 토큰 조회
 */
export function getTokens(): { accessToken: string | null; refreshToken: string | null } {
  if (typeof window === 'undefined') return { accessToken: null, refreshToken: null };
  return {
    accessToken: localStorage.getItem(ACCESS_TOKEN_KEY),
    refreshToken: localStorage.getItem(REFRESH_TOKEN_KEY),
  };
}

/**
 * 토큰 삭제
 */
export function clearTokens(): void {
  if (typeof window === 'undefined') return;
  localStorage.removeItem(ACCESS_TOKEN_KEY);
  localStorage.removeItem(REFRESH_TOKEN_KEY);
}

/**
 * Access Token 조회
 */
export function getAccessToken(): string | null {
  if (typeof window === 'undefined') return null;
  return localStorage.getItem(ACCESS_TOKEN_KEY);
}

/**
 * Refresh Token 조회
 */
export function getRefreshToken(): string | null {
  if (typeof window === 'undefined') return null;
  return localStorage.getItem(REFRESH_TOKEN_KEY);
}

/**
 * API 요청 함수
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

  // Access Token이 있으면 헤더에 추가
  const accessToken = getAccessToken();
  if (accessToken) {
    (headers as Record<string, string>)['Authorization'] = `Bearer ${accessToken}`;
  }

  const response = await fetch(url, {
    ...options,
    headers,
  });

  const data = await response.json();

  // 401 에러이고 refresh token이 있으면 토큰 갱신 시도
  if (response.status === 401 && !endpoint.includes('/auth/refresh')) {
    const refreshToken = getRefreshToken();
    if (refreshToken) {
      const refreshed = await refreshAccessToken(refreshToken);
      if (refreshed) {
        // 토큰 갱신 성공 시 원래 요청 재시도
        return request<T>(endpoint, options);
      }
    }
    // 토큰 갱신 실패 시 토큰 삭제
    clearTokens();
  }

  return data;
}

/**
 * Access Token 갱신
 */
async function refreshAccessToken(refreshToken: string): Promise<boolean> {
  try {
    const response = await fetch(`${API_URL}/auth/refresh`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ refresh_token: refreshToken }),
    });

    const data: ApiResponse<TokenResponse> = await response.json();

    if (data.success && data.data) {
      saveTokens(data.data.access_token, data.data.refresh_token);
      return true;
    }

    return false;
  } catch {
    return false;
  }
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
   * 토큰 갱신
   */
  refresh: (refreshToken: string) =>
    apiClient.post<TokenResponse>('/auth/refresh', { refresh_token: refreshToken }),

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
  logout: () => {
    const refreshToken = getRefreshToken();
    return apiClient.post('/auth/logout', { refresh_token: refreshToken });
  },

  /**
   * 전체 세션 로그아웃
   */
  logoutAll: () => apiClient.post('/auth/logout-all'),

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
