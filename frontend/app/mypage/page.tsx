'use client';

import { useCallback, useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/components/providers/auth-provider';
import { authApi, User } from '@/lib/api/client';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import { toast } from 'sonner';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { AlertCircle, RefreshCw } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

// 타입 안정성을 위한 Provider 타입 정의
type SocialProvider = 'github' | 'naver' | 'kakao';

interface SocialAccount {
  provider: SocialProvider;
  provider_email: string;
  created_at: string;
}

// Provider 타입 가드
function isValidProvider(provider: string): provider is SocialProvider {
  return ['github', 'naver', 'kakao'].includes(provider);
}

const PROVIDER_INFO: Record<SocialProvider, { name: string; color: string; icon: React.ReactNode }> = {
  github: {
    name: 'GitHub',
    color: 'bg-[#24292e]',
    icon: (
      <svg className="size-4" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
      </svg>
    ),
  },
  naver: {
    name: '네이버',
    color: 'bg-[#03C75A]',
    icon: (
      <svg className="size-4" viewBox="0 0 24 24" fill="currentColor">
        <path d="M16.273 12.845L7.376 0H0v24h7.726V11.156L16.624 24H24V0h-7.727v12.845z"/>
      </svg>
    ),
  },
  kakao: {
    name: '카카오',
    color: 'bg-[#FEE500]',
    icon: (
      <svg className="size-4" viewBox="0 0 24 24" fill="currentColor">
        <path d="M12 3c5.799 0 10.5 3.664 10.5 8.185 0 4.52-4.701 8.184-10.5 8.184a13.5 13.5 0 01-1.727-.11l-4.408 2.883c-.501.265-.678.236-.472-.413l.892-3.678c-2.88-1.46-4.785-3.99-4.785-6.866C1.5 6.665 6.201 3 12 3zm5.907 8.06l1.47-1.424a.472.472 0 00-.656-.678l-1.928 1.866V9.282a.472.472 0 00-.944 0v2.557a.471.471 0 000 .222v2.218a.472.472 0 00.944 0v-1.58l.478-.464 1.596 2.232a.472.472 0 00.764-.547l-1.724-2.36zm-4.678-1.832l-1.286 3.863c-.14.421.628.61.765.192l.233-.725h1.593l.232.725c.137.418.905.229.766-.192l-1.286-3.863c-.18-.543-.836-.543-1.017 0zm.284 2.403l.508-1.584.508 1.584h-1.016zM8.992 9.754a.472.472 0 00-.472.472v1.619L6.545 9.847a.472.472 0 00-.756.378v4.054a.472.472 0 00.944 0v-1.984l1.975 2.363a.472.472 0 00.756-.378V10.226a.472.472 0 00-.472-.472zm-4.63 0a.472.472 0 00-.472.472v4.054a.472.472 0 00.944 0V10.226a.472.472 0 00-.472-.472z"/>
      </svg>
    ),
  },
};

export default function MyPage() {
  const router = useRouter();
  const { user: contextUser, isLoggedIn } = useAuth();

  // 초기 캐시 데이터(contextUser)를 보여주고, API로 최신 데이터를 가져옴
  // contextUser는 SSR 시점의 스냅샷이므로 클라이언트에서 최신화 필요
  const [user, setUser] = useState<User | null>(contextUser);
  const [socialAccounts, setSocialAccounts] = useState<SocialAccount[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [unlinkDialogOpen, setUnlinkDialogOpen] = useState(false);
  const [selectedProvider, setSelectedProvider] = useState<SocialProvider | null>(null);
  const [isUnlinking, setIsUnlinking] = useState(false);

  const fetchData = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      // 사용자 정보 가져오기
      const userResponse = await authApi.me();
      if (userResponse.success && userResponse.data) {
        setUser(userResponse.data);
      } else {
        throw new Error(userResponse.error?.message || '사용자 정보를 불러올 수 없습니다.');
      }

      // 연동된 소셜 계정 가져오기
      const socialResponse = await authApi.socialAccounts();
      if (socialResponse.success && socialResponse.data) {
        // 타입 검증: API에서 유효하지 않은 provider가 올 수 있으므로 필터링
        const validAccounts = socialResponse.data.filter((account): account is SocialAccount =>
          isValidProvider(account.provider)
        );
        setSocialAccounts(validAccounts);
      } else {
        throw new Error(socialResponse.error?.message || '소셜 계정 정보를 불러올 수 없습니다.');
      }
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : '데이터를 불러오는데 실패했습니다.';
      console.error('Failed to fetch user data:', err);
      setError(errorMessage);
      toast.error(errorMessage);
    } finally {
      setLoading(false);
    }
  }, []); // authApi는 안정적인 참조이므로 의존성에 포함 불필요

  useEffect(() => {
    if (!isLoggedIn) {
      router.push('/login?redirect=/mypage');
      return;
    }

    fetchData();
  }, [isLoggedIn, router, fetchData]);

  const handleLogout = async () => {
    const response = await authApi.logout();
    if (response.success) {
      toast.success('로그아웃되었습니다.');
      router.push('/');
      router.refresh();
    } else {
      toast.error('로그아웃에 실패했습니다.');
    }
  };

  const handleLogoutAll = async () => {
    const response = await authApi.logoutAll();
    if (response.success) {
      toast.success('모든 기기에서 로그아웃되었습니다.');
      router.push('/');
      router.refresh();
    } else {
      toast.error('로그아웃에 실패했습니다.');
    }
  };

  const openUnlinkDialog = (provider: SocialProvider) => {
    setSelectedProvider(provider);
    setUnlinkDialogOpen(true);
  };

  const handleUnlinkSocialAccount = async () => {
    if (!selectedProvider) return;

    setIsUnlinking(true);
    try {
      const response = await authApi.unlinkSocialAccount(selectedProvider);

      if (response.success) {
        toast.success(`${PROVIDER_INFO[selectedProvider].name} 연동이 해제되었습니다.`);
        // 소셜 계정 목록 새로고침
        const socialResponse = await authApi.socialAccounts();
        if (socialResponse.success && socialResponse.data) {
          const validAccounts = socialResponse.data.filter((account): account is SocialAccount =>
            isValidProvider(account.provider)
          );
          setSocialAccounts(validAccounts);
        }
      } else {
        toast.error(response.error?.message || '연동 해제에 실패했습니다.');
      }
    } catch (error) {
      console.error('Failed to unlink social account:', error);
      toast.error('연동 해제에 실패했습니다.');
    } finally {
      setIsUnlinking(false);
      setUnlinkDialogOpen(false);
      setSelectedProvider(null);
    }
  };

  const getInitials = (name: string) => {
    return name
      .split(' ')
      .map((n) => n[0])
      .join('')
      .toUpperCase()
      .slice(0, 2);
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('ko-KR', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });
  };

  if (loading) {
    return (
      <div className="container max-w-4xl mx-auto py-8 px-4">
        <div className="space-y-6">
          <Skeleton className="h-12 w-48" />
          <Card>
            <CardHeader>
              <Skeleton className="h-6 w-32" />
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center space-x-4">
                <Skeleton className="size-20 rounded-full" />
                <div className="space-y-2">
                  <Skeleton className="h-6 w-40" />
                  <Skeleton className="h-4 w-60" />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    );
  }

  // 에러 상태 UI
  if (error) {
    return (
      <div className="container max-w-4xl mx-auto py-8 px-4">
        <div className="space-y-6">
          <div>
            <h1 className="text-3xl font-bold tracking-tight">마이페이지</h1>
            <p className="text-muted-foreground mt-2">계정 정보를 확인하고 관리하세요.</p>
          </div>

          <Alert variant="destructive">
            <AlertCircle className="size-4" />
            <AlertTitle>오류 발생</AlertTitle>
            <AlertDescription className="mt-2">
              {error}
              <div className="mt-4">
                <Button onClick={fetchData} variant="outline" size="sm">
                  <RefreshCw className="size-4 mr-2" />
                  다시 시도
                </Button>
              </div>
            </AlertDescription>
          </Alert>
        </div>
      </div>
    );
  }

  if (!user) {
    return null;
  }

  return (
    <div className="container max-w-4xl mx-auto py-8 px-4">
      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">마이페이지</h1>
          <p className="text-muted-foreground mt-2">계정 정보를 확인하고 관리하세요.</p>
        </div>

        {/* 프로필 섹션 */}
        <Card>
          <CardHeader>
            <CardTitle>프로필</CardTitle>
            <CardDescription>계정의 기본 정보입니다.</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="flex items-start space-x-4">
              <Avatar className="size-20">
                <AvatarImage src={user.avatar || undefined} alt={user.name} />
                <AvatarFallback className="text-xl">
                  {user.name ? getInitials(user.name) : user.email[0].toUpperCase()}
                </AvatarFallback>
              </Avatar>
              <div className="space-y-1 flex-1">
                <div className="flex items-center gap-2">
                  <h3 className="text-xl font-semibold">{user.name || '사용자'}</h3>
                  {user.email_verified_at && (
                    <Badge variant="secondary" className="text-xs">
                      인증됨
                    </Badge>
                  )}
                </div>
                <p className="text-muted-foreground">{user.email}</p>
                <p className="text-sm text-muted-foreground">
                  가입일: {formatDate(user.created_at)}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* 연동된 소셜 계정 */}
        <Card>
          <CardHeader>
            <CardTitle>소셜 계정 연동</CardTitle>
            <CardDescription>
              연동된 소셜 계정을 관리하세요. 최소 1개의 계정은 유지되어야 합니다.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {socialAccounts.length === 0 ? (
                <p className="text-sm text-muted-foreground">연동된 소셜 계정이 없습니다.</p>
              ) : (
                socialAccounts.map((account) => {
                  const providerInfo = PROVIDER_INFO[account.provider];
                  return (
                    <div
                      key={account.provider}
                      className="flex items-center justify-between p-4 border rounded-lg"
                    >
                      <div className="flex items-center space-x-3">
                        <div className={`p-2 rounded-lg ${providerInfo.color} text-white`}>
                          {providerInfo.icon}
                        </div>
                        <div>
                          <p className="font-medium">{providerInfo.name}</p>
                          <p className="text-sm text-muted-foreground">
                            {account.provider_email}
                          </p>
                          <p className="text-xs text-muted-foreground">
                            연동일: {formatDate(account.created_at)}
                          </p>
                        </div>
                      </div>
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => openUnlinkDialog(account.provider)}
                        disabled={socialAccounts.length === 1}
                      >
                        연동 해제
                      </Button>
                    </div>
                  );
                })
              )}
            </div>
          </CardContent>
        </Card>

        {/* 계정 관리 */}
        <Card>
          <CardHeader>
            <CardTitle>계정 관리</CardTitle>
            <CardDescription>계정 보안 및 세션 관리</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-3">
              <div>
                <h4 className="font-medium mb-2">로그아웃</h4>
                <div className="flex gap-3">
                  <Button onClick={handleLogout} variant="outline">
                    현재 기기에서 로그아웃
                  </Button>
                  <Button onClick={handleLogoutAll} variant="outline">
                    모든 기기에서 로그아웃
                  </Button>
                </div>
              </div>

              <Separator />

              <div>
                <h4 className="font-medium mb-2 text-destructive">위험 영역</h4>
                <p className="text-sm text-muted-foreground mb-3">
                  계정을 삭제하면 모든 데이터가 영구적으로 삭제됩니다.
                </p>
                <Button variant="destructive" disabled>
                  계정 삭제 (준비 중)
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* 연동 해제 확인 다이얼로그 */}
      <AlertDialog open={unlinkDialogOpen} onOpenChange={setUnlinkDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>소셜 계정 연동 해제</AlertDialogTitle>
            <AlertDialogDescription>
              {selectedProvider && PROVIDER_INFO[selectedProvider].name} 계정 연동을 해제하시겠습니까?
              {socialAccounts.length === 1 && (
                <span className="block mt-2 text-destructive font-medium">
                  마지막 연동 계정은 해제할 수 없습니다.
                </span>
              )}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={isUnlinking}>취소</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleUnlinkSocialAccount}
              disabled={isUnlinking || socialAccounts.length === 1}
            >
              {isUnlinking ? '처리 중...' : '연동 해제'}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
