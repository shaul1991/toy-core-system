import { NextResponse } from 'next/server';
import type { NextRequest } from 'next/server';

/**
 * 인증 상태에 따른 라우트 보호 미들웨어
 *
 * - 게스트 전용 경로: 로그인한 사용자는 메인 페이지로 리다이렉트
 * - 인증 필요 경로: 비로그인 사용자는 로그인 페이지로 리다이렉트
 */

/**
 * 게스트 전용 경로 (로그인한 사용자 접근 불가)
 */
const GUEST_ONLY_PATHS = ['/login'];

/**
 * 인증 필요 경로 (비로그인 사용자 접근 불가)
 * 패턴 매칭을 위해 정규식으로 정의
 */
const AUTH_REQUIRED_PATTERNS: RegExp[] = [
  /^\/mypage(\/.*)?$/, // /mypage 및 하위 경로
  /^\/settings(\/.*)?$/, // /settings 및 하위 경로
  /^\/dashboard(\/.*)?$/, // /dashboard 및 하위 경로
];

/**
 * 미들웨어에서 제외할 경로
 */
const EXCLUDED_PATTERNS = [
  /^\/_next/, // Next.js 내부 경로
  /^\/api/, // API 경로
  /^\/static/, // 정적 파일
  /\.(ico|png|jpg|jpeg|gif|svg|css|js|woff|woff2|ttf|eot)$/, // 정적 리소스
];

/**
 * 쿠키에서 인증 상태 확인
 * access_token 또는 token_type 쿠키가 있으면 로그인 상태로 판단
 */
function isAuthenticated(request: NextRequest): boolean {
  const accessToken = request.cookies.get('access_token')?.value;
  const tokenType = request.cookies.get('token_type')?.value;
  return !!(accessToken || tokenType);
}

/**
 * 경로가 제외 패턴에 해당하는지 확인
 */
function isExcludedPath(pathname: string): boolean {
  return EXCLUDED_PATTERNS.some((pattern) => pattern.test(pathname));
}

/**
 * 경로가 게스트 전용인지 확인
 */
function isGuestOnlyPath(pathname: string): boolean {
  return GUEST_ONLY_PATHS.includes(pathname);
}

/**
 * 경로가 인증 필요 경로인지 확인
 */
function isAuthRequiredPath(pathname: string): boolean {
  return AUTH_REQUIRED_PATTERNS.some((pattern) => pattern.test(pathname));
}

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // 제외 경로는 미들웨어 통과
  if (isExcludedPath(pathname)) {
    return NextResponse.next();
  }

  const authenticated = isAuthenticated(request);

  // 게스트 전용 경로 체크 (로그인한 사용자 접근 불가)
  if (isGuestOnlyPath(pathname) && authenticated) {
    const url = request.nextUrl.clone();
    url.pathname = '/';
    return NextResponse.redirect(url);
  }

  // 인증 필요 경로 체크 (비로그인 사용자 접근 불가)
  if (isAuthRequiredPath(pathname) && !authenticated) {
    const url = request.nextUrl.clone();
    url.pathname = '/login';
    // 로그인 후 원래 페이지로 돌아갈 수 있도록 redirect 파라미터 추가
    url.searchParams.set('redirect', pathname);
    return NextResponse.redirect(url);
  }

  return NextResponse.next();
}

export const config = {
  matcher: [
    /*
     * 모든 경로에 대해 미들웨어 실행
     * 단, 내부적으로 제외 패턴 체크
     */
    '/((?!_next/static|_next/image|favicon.ico).*)',
  ],
};
