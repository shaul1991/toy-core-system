'use client';

import { useEffect, useState, useRef } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

/**
 * API URL - 환경 변수 또는 상대 경로 사용
 * 프로덕션에서는 반드시 NEXT_PUBLIC_API_URL이 설정되어야 함
 * 미설정 시 같은 origin의 /api로 요청
 */
const API_URL = process.env.NEXT_PUBLIC_API_URL || '/api';

/**
 * OAuth 콜백 페이지
 *
 * BFF에서 OAuth 인증 완료 후 리다이렉트되는 페이지입니다.
 * 서버 API 호출로 인증 성공 여부를 확인합니다.
 * 토큰은 HttpOnly 쿠키로 관리되어 직접 접근하지 않습니다.
 */
export default function AuthCallbackPage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [status, setStatus] = useState<'loading' | 'success' | 'error'>('loading');
  const [message, setMessage] = useState('로그인 처리 중...');
  const timeoutRef = useRef<NodeJS.Timeout | null>(null);

  useEffect(() => {
    // 이전 타이머 정리
    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current);
      timeoutRef.current = null;
    }

    const processCallback = async () => {
      // URL 파라미터에서 에러 확인
      const error = searchParams.get('error');
      const errorMessage = searchParams.get('message');

      // 에러 처리
      if (error) {
        setStatus('error');
        setMessage(errorMessage || '로그인 중 오류가 발생했습니다.');
        timeoutRef.current = setTimeout(() => {
          router.push('/login');
        }, 3000);
        return;
      }

      // 서버 API 호출로 인증 확인 (쿠키가 다른 도메인에 설정된 경우에도 동작)
      try {
        const response = await fetch(`${API_URL}/auth/me`, {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
          },
          credentials: 'include', // 쿠키 자동 전송
        });

        if (response.ok) {
          // 로그인 성공
          setStatus('success');
          setMessage('로그인 성공! 잠시 후 이동합니다...');

          // redirect 경로 결정: URL 파라미터 → sessionStorage → 기본값
          const urlRedirect = searchParams.get('redirect');
          const storedRedirect = typeof window !== 'undefined' ? sessionStorage.getItem('auth_redirect') : null;
          const redirectPath = urlRedirect || storedRedirect || '/';

          // sessionStorage 정리
          if (typeof window !== 'undefined') {
            sessionStorage.removeItem('auth_redirect');
          }

          // 외부 URL 리다이렉트 방지 (상대 경로만 허용)
          const safeRedirect = redirectPath.startsWith('/') && !redirectPath.startsWith('//') ? redirectPath : '/';

          timeoutRef.current = setTimeout(() => {
            router.push(safeRedirect);
          }, 1500);
        } else {
          // 인증 실패
          setStatus('error');
          setMessage('인증 정보가 올바르지 않습니다.');
          timeoutRef.current = setTimeout(() => {
            router.push('/login');
          }, 3000);
        }
      } catch {
        // 네트워크 에러
        setStatus('error');
        setMessage('서버와 통신 중 오류가 발생했습니다.');
        timeoutRef.current = setTimeout(() => {
          router.push('/login');
        }, 3000);
      }
    };

    processCallback();

    // 클린업: 컴포넌트 언마운트 시 타이머 정리
    return () => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
        timeoutRef.current = null;
      }
    };
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
