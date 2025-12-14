import { ReactNode, Suspense } from 'react';
import { Inter } from 'next/font/google';
import { cn } from '@/lib/utils';
import { TooltipProvider } from '@/components/ui/tooltip';
import { Toaster } from '@/components/ui/sonner';
import { Metadata } from 'next';
import { ThemeProvider } from 'next-themes';
import { Layout7 } from '@/components/layouts/layout-7';
import { LayoutProvider } from '@/components/layouts/layout-1/components/context';
import { AuthProvider } from '@/components/providers/auth-provider';
import { getServerUser } from '@/lib/api/server';

import '@/styles/globals.css';
const inter = Inter({ subsets: ['latin'] });

export const metadata: Metadata = {
  title: {
    template: '%s | Toy',
    default: 'Toy Domain Service',
  },
};

export default async function RootLayout({
  children,
}: {
  children: ReactNode;
}) {
  const user = await getServerUser();
  const isLoggedIn = user !== null;

  return (
    <html className="h-full" suppressHydrationWarning>
      <body
        className={cn(
          'antialiased flex h-full text-base text-foreground bg-background',
          inter.className,
        )}
      >
        <ThemeProvider
          attribute="class"
          defaultTheme="system"
          storageKey="nextjs-theme"
          enableSystem
          disableTransitionOnChange
          enableColorScheme
        >
          <AuthProvider initialIsLoggedIn={isLoggedIn} initialUser={user}>
            <TooltipProvider delayDuration={0}>
              <LayoutProvider>
                <Suspense>
                  <Layout7>{children}</Layout7>
                </Suspense>
              </LayoutProvider>
              <Toaster />
            </TooltipProvider>
          </AuthProvider>
        </ThemeProvider>
      </body>
    </html>
  );
}
