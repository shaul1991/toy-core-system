'use client';

import { useEffect, useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { saveTokens, clearTokens } from '@/lib/api/client';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

/**
 * OAuth 콜백 페이지
 *
 * BFF에서 OAuth 인증 완료 후 리다이렉트되는 페이지입니다.
 * URL 파라미터로 전달된 토큰을 저장하고 메인 페이지로 이동합니다.
 */
export default function AuthCallbackPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [status, setStatus] = useState<'loading' | 'success' | 'error'>('loading');
  const [message, setMessage] = useState('로그인 처리 중...');

  useEffect(() => {
    const processCallback = async () => {
      // URL 파라미터에서 토큰 추출
      const accessToken = searchParams.get('access_token');
      const refreshToken = searchParams.get('refresh_token');
      const error = searchParams.get('error');
      const errorMessage = searchParams.get('message');

      // 에러 처리
      if (error) {
        setStatus('error');
        setMessage(errorMessage || '로그인 중 오류가 발생했습니다.');
        setTimeout(() => {
          router.push('/login');
        }, 3000);
        return;
      }

      // 토큰 검증
      if (!accessToken || !refreshToken) {
        setStatus('error');
        setMessage('인증 정보가 올바르지 않습니다.');
        setTimeout(() => {
          router.push('/login');
        }, 3000);
        return;
      }

      // 토큰 저장
      try {
        saveTokens(accessToken, refreshToken);
        setStatus('success');
        setMessage('로그인 성공! 잠시 후 이동합니다...');

        // URL에서 토큰 정보 제거 (보안)
        window.history.replaceState({}, '', '/auth/callback');

        // 메인 페이지로 이동
        setTimeout(() => {
          router.push('/');
        }, 1500);
      } catch {
        setStatus('error');
        setMessage('토큰 저장 중 오류가 발생했습니다.');
        clearTokens();
        setTimeout(() => {
          router.push('/login');
        }, 3000);
      }
    };

    processCallback();
  }, [router, searchParams]);

  return (
    <div className="min-h-screen flex items-center justify-center bg-background p-4">
      <Card className="w-full max-w-md">
        <CardHeader className="text-center">
          <CardTitle className="text-2xl font-bold">
            {status === 'loading' && '로그인 중'}
            {status === 'success' && '로그인 성공'}
            {status === 'error' && '로그인 실패'}
          </CardTitle>
          <CardDescription>{message}</CardDescription>
        </CardHeader>
        <CardContent className="flex justify-center">
          {status === 'loading' && (
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary" />
          )}
          {status === 'success' && (
            <svg
              className="h-12 w-12 text-green-500"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M5 13l4 4L19 7"
              />
            </svg>
          )}
          {status === 'error' && (
            <svg
              className="h-12 w-12 text-red-500"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
