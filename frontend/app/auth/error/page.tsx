'use client';

import { useSearchParams } from 'next/navigation';
import Link from 'next/link';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

/**
 * OAuth 에러 페이지
 *
 * BFF에서 OAuth 인증 실패 시 리다이렉트되는 페이지입니다.
 */
export default function AuthErrorPage() {
  const searchParams = useSearchParams();
  const error = searchParams.get('error') || 'UNKNOWN_ERROR';
  const message = searchParams.get('message') || '알 수 없는 오류가 발생했습니다.';

  // 에러 코드별 메시지
  const errorMessages: Record<string, { title: string; description: string }> = {
    SOCIAL_AUTH_FAILED: {
      title: '소셜 로그인 실패',
      description: '소셜 계정 인증에 실패했습니다. 다시 시도해 주세요.',
    },
    SOCIAL_ALREADY_LINKED: {
      title: '이미 연동된 계정',
      description: '해당 소셜 계정은 이미 다른 계정에 연동되어 있습니다.',
    },
    SOCIAL_EMAIL_REQUIRED: {
      title: '이메일 필요',
      description: '소셜 계정에서 이메일 정보를 가져올 수 없습니다. 이메일 접근 권한을 허용해 주세요.',
    },
    UNKNOWN_ERROR: {
      title: '오류 발생',
      description: message,
    },
  };

  const errorInfo = errorMessages[error] || errorMessages.UNKNOWN_ERROR;

  return (
    <div className="min-h-screen flex items-center justify-center bg-background p-4">
      <Card className="w-full max-w-md">
        <CardHeader className="text-center">
          <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
            <svg
              className="h-8 w-8 text-red-600"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.072 16.5c-.77.833.192 2.5 1.732 2.5z"
              />
            </svg>
          </div>
          <CardTitle className="text-2xl font-bold">{errorInfo.title}</CardTitle>
          <CardDescription>{errorInfo.description}</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <Button className="w-full" asChild>
            <Link href="/login">다시 로그인하기</Link>
          </Button>
          <div className="text-center">
            <Link
              href="/"
              className="text-sm text-muted-foreground hover:text-foreground"
            >
              홈으로 돌아가기
            </Link>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
