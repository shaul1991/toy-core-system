'use client';

import { cn } from '@/lib/utils';
import { useMenu } from '@/hooks/use-menu';
import { MENU_MEGA } from '@/config/layout-7.config';
import {
  NavigationMenu,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
} from '@/components/ui/navigation-menu';
import Link from 'next/link';
import { usePathname } from 'next/navigation';

export function MegaMenu() {
  const pathname = usePathname();
  const { isActive } = useMenu(pathname);

  const linkClass = `
    text-sm text-secondary-foreground font-medium rounded-none px-0 border-b border-transparent
    hover:text-primary hover:bg-transparent
    focus:text-primary focus:bg-transparent
    data-[active=true]:text-mono data-[active=true]:bg-transparent data-[active=true]:border-mono
    data-[state=open]:text-mono data-[state=open]:bg-transparent
  `;

  return (
    <NavigationMenu>
      <NavigationMenuList className="gap-7.5">
        {MENU_MEGA.map((item, index) => (
          <NavigationMenuItem key={index}>
            <NavigationMenuLink asChild>
              <Link
                href={item.path || '/'}
                className={cn(linkClass)}
                data-active={isActive(item.path) || undefined}
              >
                {item.title}
              </Link>
            </NavigationMenuLink>
          </NavigationMenuItem>
        ))}
      </NavigationMenuList>
    </NavigationMenu>
  );
}
