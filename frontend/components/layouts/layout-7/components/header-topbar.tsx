'use client';

import Link from 'next/link';
import { LogIn } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { UserDropdownMenu } from '../../layout-1/shared/topbar/user-dropdown-menu';
import { useAuth } from '@/components/providers/auth-provider';

const HeaderTopbar = () => {
  const { isLoggedIn } = useAuth();

  return (
    <div className="flex items-center flex-wrap gap-2 lg:gap-3.5">
      {!isLoggedIn && (
        <Button variant="outline" size="sm" asChild>
          <Link href="/login">
            <LogIn className="size-4" />
            회원가입 / 로그인
          </Link>
        </Button>
      )}

      <UserDropdownMenu />
    </div>
  );
};

export { HeaderTopbar };
