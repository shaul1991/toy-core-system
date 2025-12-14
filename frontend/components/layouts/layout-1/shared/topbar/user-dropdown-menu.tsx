'use client';

import { ReactNode, useState } from 'react';
import { LogOut, User } from 'lucide-react';
import Link from 'next/link';
import { useAuth } from '@/components/providers/auth-provider';
import { authApi } from '@/lib/api/client';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

function getInitial(email: string): string {
  return email.charAt(0).toUpperCase();
}

export function UserDropdownMenu({ trigger }: { trigger: ReactNode }) {
  const { isLoggedIn, user } = useAuth();
  const [isLoggingOut, setIsLoggingOut] = useState(false);

  // 비회원은 노출되지 않음
  if (!isLoggedIn || !user) {
    return null;
  }

  const handleLogout = async () => {
    setIsLoggingOut(true);
    try {
      await authApi.logout();
      window.location.href = '/';
    } catch (error) {
      console.error('로그아웃 실패:', error);
      setIsLoggingOut(false);
    }
  };

  const initial = getInitial(user.email);

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>{trigger}</DropdownMenuTrigger>
      <DropdownMenuContent className="w-64" side="bottom" align="end">
        {/* Header - 사용자 정보 */}
        <div className="flex items-center gap-3 p-3">
          <div className="flex size-10 items-center justify-center rounded-full bg-primary text-primary-foreground font-semibold text-lg">
            {initial}
          </div>
          <div className="flex flex-col min-w-0">
            {user.name && (
              <span className="text-sm font-semibold text-foreground truncate">
                {user.name}
              </span>
            )}
            <span className="text-xs text-muted-foreground truncate">
              {user.email}
            </span>
          </div>
        </div>

        <DropdownMenuSeparator />

        {/* 마이페이지 */}
        <DropdownMenuItem asChild>
          <Link href="/mypage" className="flex items-center gap-2">
            <User className="size-4" />
            마이페이지
          </Link>
        </DropdownMenuItem>

        <DropdownMenuSeparator />

        {/* 로그아웃 */}
        <div className="p-2">
          <Button
            variant="outline"
            size="sm"
            className="w-full"
            onClick={handleLogout}
            disabled={isLoggingOut}
          >
            <LogOut className="size-4" />
            {isLoggingOut ? '로그아웃 중...' : '로그아웃'}
          </Button>
        </div>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
