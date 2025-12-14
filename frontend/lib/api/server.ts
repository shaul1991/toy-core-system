/**
 * 서버 컴포넌트용 API 클라이언트
 *
 * SSR에서 사용자 정보를 가져오기 위한 유틸리티입니다.
 */

import { cookies } from 'next/headers';
import type { User } from './client';

const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

interface ApiResponse<T> {
  success: boolean;
  data?: T;
  error?: {
    code: string;
    message: string;
  };
}

/**
 * 서버 컴포넌트에서 현재 사용자 정보를 가져옵니다.
 *
 * 쿠키의 access_token을 사용하여 /auth/me API를 호출합니다.
 * 인증되지 않은 경우 null을 반환합니다.
 */
export async function getServerUser(): Promise<User | null> {
  try {
    const cookieStore = await cookies();
    const accessToken = cookieStore.get('access_token')?.value;

    if (!accessToken) {
      return null;
    }

    const response = await fetch(`${API_URL}/auth/me`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        Authorization: `Bearer ${accessToken}`,
      },
      cache: 'no-store',
    });

    if (!response.ok) {
      return null;
    }

    const result: ApiResponse<User> = await response.json();

    if (result.success && result.data) {
      return result.data;
    }

    return null;
  } catch {
    return null;
  }
}
