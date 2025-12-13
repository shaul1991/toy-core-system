'use client';

import { useEffect, useState } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

/**
 * 쿠키에서 값 읽기 (클라이언트 사이드)
 */
function getCookie(name: string): string | null {
  if (typeof document === 'undefined') return null;
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return parts.pop()?.split(';').shift() || null;
  return null;
}

/**
 * OAuth 콜백 페이지
 *
 * BFF에서 OAuth 인증 완료 후 리다이렉트되는 페이지입니다.
 * 서버에서 설정한 HttpOnly 쿠키를 통해 토큰이 저장됩니다.
 */
export default function AuthCallbackPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [status, setStatus] = useState<'loading' | 'success' | 'error'>('loading');
  const [message, setMessage] = useState('로그인 처리 중...');

  useEffect(() => {
    const processCallback = async () => {
      // URL 파라미터에서 에러 확인
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

      // 토큰 쿠키 확인 (token_type은 httpOnly가 아니므로 읽을 수 있음)
      const tokenType = getCookie('token_type');

      if (!tokenType) {
        // 쿠키가 없으면 에러
        setStatus('error');
        setMessage('인증 정보가 올바르지 않습니다.');
        setTimeout(() => {
          router.push('/login');
        }, 3000);
        return;
      }

      // 로그인 성공
      setStatus('success');
      setMessage('로그인 성공! 잠시 후 이동합니다...');

      // 메인 페이지로 이동
      setTimeout(() => {
        router.push('/');
      }, 1500);
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
