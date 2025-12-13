import Link from 'next/link';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';

// 소셜 로그인 제공자 타입 정의 (확장 가능)
interface SocialProvider {
  id: string;
  name: string;
  icon: React.ReactNode;
  className: string;
  url: string;
}

// 소셜 로그인 제공자 목록 (추후 확장 가능)
const socialProviders: SocialProvider[] = [
  {
    id: 'github',
    name: 'GitHub',
    icon: (
      <svg className="size-5" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
      </svg>
    ),
    className: 'bg-[#24292e] hover:bg-[#24292e]/90 text-white',
    url: '/api/auth/github',
  },
  {
    id: 'naver',
    name: '네이버',
    icon: (
      <svg className="size-5" viewBox="0 0 24 24" fill="currentColor">
        <path d="M16.273 12.845L7.376 0H0v24h7.726V11.156L16.624 24H24V0h-7.727v12.845z"/>
      </svg>
    ),
    className: 'bg-[#03C75A] hover:bg-[#03C75A]/90 text-white',
    url: '/api/auth/naver',
  },
  {
    id: 'kakao',
    name: '카카오',
    icon: (
      <svg className="size-5" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 3c5.799 0 10.5 3.664 10.5 8.185 0 4.52-4.701 8.184-10.5 8.184a13.5 13.5 0 01-1.727-.11l-4.408 2.883c-.501.265-.678.236-.472-.413l.892-3.678c-2.88-1.46-4.785-3.99-4.785-6.866C1.5 6.665 6.201 3 12 3zm5.907 8.06l1.47-1.424a.472.472 0 00-.656-.678l-1.928 1.866V9.282a.472.472 0 00-.944 0v2.557a.471.471 0 000 .222v2.218a.472.472 0 00.944 0v-1.58l.478-.464 1.596 2.232a.472.472 0 00.764-.547l-1.724-2.36zm-4.678-1.832l-1.286 3.863c-.14.421.628.61.765.192l.233-.725h1.593l.232.725c.137.418.905.229.766-.192l-1.286-3.863c-.18-.543-.836-.543-1.017 0zm.284 2.403l.508-1.584.508 1.584h-1.016zM8.992 9.754a.472.472 0 00-.472.472v1.619L6.545 9.847a.472.472 0 00-.756.378v4.054a.472.472 0 00.944 0v-1.984l1.975 2.363a.472.472 0 00.756-.378V10.226a.472.472 0 00-.472-.472zm-4.63 0a.472.472 0 00-.472.472v4.054a.472.472 0 00.944 0V10.226a.472.472 0 00-.472-.472z"/>
      </svg>
    ),
    className: 'bg-[#FEE500] hover:bg-[#FEE500]/90 text-[#000000]',
    url: '/api/auth/kakao',
  },
];

export default function LoginPage() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-background p-4">
      <Card className="w-full max-w-md">
        <CardHeader className="text-center">
          <CardTitle className="text-2xl font-bold">로그인</CardTitle>
          <CardDescription>
            소셜 계정으로 간편하게 로그인하세요
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-3">
            {socialProviders.map((provider) => (
              <Button
                key={provider.id}
                className={`w-full h-12 text-base font-medium ${provider.className}`}
                asChild
              >
                <Link href={provider.url}>
                  {provider.icon}
                  <span className="ml-2">{provider.name}로 계속하기</span>
                </Link>
              </Button>
            ))}
          </div>

          <div className="relative my-6">
            <Separator />
            <span className="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 bg-background px-2 text-xs text-muted-foreground">
              또는
            </span>
          </div>

          <p className="text-center text-sm text-muted-foreground">
            계속 진행하면{' '}
            <Link href="/terms" className="text-primary hover:underline">
              이용약관
            </Link>
            {' '}및{' '}
            <Link href="/privacy" className="text-primary hover:underline">
              개인정보처리방침
            </Link>
            에 동의하는 것으로 간주됩니다.
          </p>

          <div className="text-center pt-4">
            <Link
              href="/"
              className="text-sm text-muted-foreground hover:text-foreground"
            >
              ← 홈으로 돌아가기
            </Link>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
