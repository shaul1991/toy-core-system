# Frontend

Next.js 기반의 프론트엔드 애플리케이션입니다. Metronic v9.3.8 템플릿을 기반으로 구축되었습니다.

> **📚 전체 아키텍처:** [ARCHITECTURE.md](./ARCHITECTURE.md)
> **🔌 BFF API:** [BFF.md](./BFF.md)
> **⚙️ Core Service:** [CORE.md](./CORE.md)

## 개요

| 항목 | 설명 |
|------|------|
| **템플릿** | Metronic v9.3.8 (Tailwind React Starter Kit) |
| **프레임워크** | Next.js 16.x (App Router) |
| **언어** | TypeScript 5.x |
| **UI 라이브러리** | React 19.x |
| **스타일링** | Tailwind CSS 4.x |
| **상태 관리** | TanStack React Query |
| **폼 관리** | React Hook Form + Zod |

## 레이어 구조

Frontend는 3-Tier 아키텍처의 최상위 레이어입니다:

```
┌─────────────────────────────────────────┐
│      Frontend Layer (Next.js)           │  ← 이 문서
│   UI/UX, User Input, Client Rendering   │
│              /frontend                  │
└────────────────┬────────────────────────┘
                 │ HTTP/JSON
                 │ /api/* (Public API)
                 ▼
┌─────────────────────────────────────────┐
│        BFF Layer (Laravel)              │  ← API Gateway
│    JWT Auth, API Gateway, Transform     │
└────────────────┬────────────────────────┘
                 │ /internal/* (Private)
                 ▼
┌─────────────────────────────────────────┐
│      Core Service Layer (Laravel)       │  ← 비즈니스 로직
│    Domain Logic, Business Rules, DB     │
└─────────────────────────────────────────┘
```

**역할:**
- **Frontend:** 사용자 UI 제공, 입력 처리, 화면 렌더링
- **호출 대상:** BFF Layer (`/api/*`)만 호출
- **보안:** HttpOnly Cookie로 토큰 관리 (XSS 방지)

> 전체 아키텍처 상세: [ARCHITECTURE.md](./ARCHITECTURE.md)

---

## 기술 스택

### Core

| 패키지 | 버전 | 용도 |
|--------|------|------|
| `next` | 16.0.8 | React 풀스택 프레임워크 |
| `react` | 19.2.1 | UI 라이브러리 |
| `typescript` | 5.9.3 | 정적 타입 언어 |
| `tailwindcss` | 4.1.17 | 유틸리티 CSS 프레임워크 |

### 상태 관리 & 데이터

| 패키지 | 용도 |
|--------|------|
| `@tanstack/react-query` | 서버 상태 관리 |
| `@tanstack/react-table` | 테이블 컴포넌트 |
| `react-hook-form` | 폼 상태 관리 |
| `zod` | 스키마 유효성 검증 |

### UI 컴포넌트

| 패키지 | 용도 |
|--------|------|
| `radix-ui` | Headless UI 컴포넌트 |
| `lucide-react` | 아이콘 라이브러리 |
| `@remixicon/react` | 추가 아이콘 |
| `sonner` | 토스트 알림 |
| `vaul` | Drawer 컴포넌트 |
| `cmdk` | Command Palette |
| `motion` | 애니메이션 |

### 차트 & 시각화

| 패키지 | 용도 |
|--------|------|
| `apexcharts` / `react-apexcharts` | 차트 라이브러리 |
| `recharts` | React 차트 |
| `leaflet` / `react-leaflet` | 지도 컴포넌트 |

### 유틸리티

| 패키지 | 용도 |
|--------|------|
| `date-fns` | 날짜 처리 |
| `clsx` / `tailwind-merge` | 클래스명 유틸리티 |
| `class-variance-authority` | 컴포넌트 변형 관리 |

## 페이지 구성

### 현재 구현된 페이지

| URL | 파일 경로 | 설명 | 접근 권한 |
|-----|----------|------|-----------|
| `/` | `app/page.tsx` | 홈/대시보드 페이지 | 모든 사용자 |
| `/login` | `app/login/page.tsx` | 소셜 로그인 페이지 (GitHub, Naver, Kakao) | 게스트 전용 |
| `/mypage` | `app/mypage/page.tsx` | 마이페이지 (프로필, 소셜 계정 관리) | 인증 필요 |
| `/auth/callback` | `app/auth/callback/page.tsx` | OAuth 콜백 처리 페이지 | 모든 사용자 |
| `/auth/error` | `app/auth/error/page.tsx` | 인증 에러 표시 페이지 | 모든 사용자 |

### 접근 권한 유형

| 유형 | 설명 | 비인증 사용자 | 인증 사용자 |
|------|------|--------------|-------------|
| **모든 사용자** | 누구나 접근 가능 | ✅ 허용 | ✅ 허용 |
| **게스트 전용** | 비로그인 사용자만 접근 | ✅ 허용 | ❌ `/`로 리다이렉트 |
| **인증 필요** | 로그인 사용자만 접근 | ❌ `/login`으로 리다이렉트 | ✅ 허용 |

### 예정된 보호 경로

미들웨어에 정의되어 있으나 아직 페이지가 구현되지 않은 경로:

| URL 패턴 | 설명 | 접근 권한 |
|----------|------|-----------|
| `/settings/*` | 설정 페이지 및 하위 경로 | 인증 필요 |
| `/dashboard/*` | 대시보드 및 하위 경로 | 인증 필요 |

---

### 페이지별 상세 설명

#### 홈 페이지 (`/`)

- **파일**: `app/page.tsx`
- **타입**: 클라이언트 컴포넌트 (`'use client'`)
- **용도**: 메인 대시보드 진입점
- **주요 컴포넌트**: `Skeleton`, `Toolbar`, `ToolbarHeading`

#### 로그인 페이지 (`/login`)

- **파일**: `app/login/page.tsx`
- **타입**: 클라이언트 컴포넌트 (`'use client'`)
- **용도**: 소셜 로그인 UI 제공
- **지원 Provider**:
  | Provider | 테마 색상 | API 엔드포인트 |
  |----------|----------|----------------|
  | GitHub | `#24292e` (dark) | `/api/auth/github/redirect` |
  | Naver | `#03C75A` (green) | `/api/auth/naver/redirect` |
  | Kakao | `#FEE500` (yellow) | `/api/auth/kakao/redirect` |
- **특징**:
  - `redirect` 파라미터를 `sessionStorage`에 저장하여 로그인 후 원래 페이지로 복귀
  - 이용약관 및 개인정보처리방침 링크 제공
  - 로그인한 사용자는 자동으로 `/`로 리다이렉트

#### OAuth 콜백 페이지 (`/auth/callback`)

- **파일**: `app/auth/callback/page.tsx`
- **타입**: 클라이언트 컴포넌트 (`'use client'`)
- **용도**: OAuth 인증 완료 후 처리
- **흐름**:
  1. URL 파라미터에서 `error` 확인
  2. `/api/auth/me` API 호출로 인증 상태 확인
  3. 성공 시: 1.5초 후 저장된 redirect 경로 또는 `/`로 이동
  4. 실패 시: 3초 후 `/login`으로 이동
- **상태 표시**: 로딩 → 성공/실패 애니메이션

#### 인증 에러 페이지 (`/auth/error`)

- **파일**: `app/auth/error/page.tsx`
- **타입**: 클라이언트 컴포넌트 (`'use client'`)
- **용도**: OAuth 인증 실패 메시지 표시
- **지원 에러 코드**:
  | 코드 | 설명 |
  |------|------|
  | `SOCIAL_AUTH_FAILED` | 소셜 인증 실패 |
  | `SOCIAL_ALREADY_LINKED` | 이미 다른 계정에 연동됨 |
  | `SOCIAL_EMAIL_REQUIRED` | 이메일 권한 필요 |
  | `UNKNOWN_ERROR` | 알 수 없는 오류 |

#### 마이페이지 (`/mypage`)

- **파일**: `app/mypage/page.tsx`, `app/mypage/layout.tsx`
- **타입**: 클라이언트 컴포넌트 (`'use client'`)
- **접근 권한**: 인증 필요 (비로그인 시 `/login?redirect=/mypage`로 리다이렉트)
- **용도**: 사용자 계정 정보 확인 및 관리
- **주요 기능**:
  1. **프로필 섹션**
     - 사용자 아바타 (이미지 또는 이니셜)
     - 이름, 이메일 표시
     - 이메일 인증 배지
     - 가입일 정보
  2. **소셜 계정 연동 관리**
     - 연동된 소셜 계정 목록 (GitHub, Naver, Kakao)
     - 각 계정의 이메일 및 연동일 표시
     - 소셜 계정 연동 해제 기능
     - 최소 1개 계정 유지 제약
     - 확인 다이얼로그로 안전한 연동 해제
  3. **계정 관리**
     - 현재 기기에서 로그아웃
     - 모든 기기에서 로그아웃
     - 계정 삭제 (UI 준비, 기능은 향후 구현)
- **사용 API**:
  | API | 용도 |
  |-----|------|
  | `GET /api/auth/me` | 사용자 정보 조회 |
  | `GET /api/auth/social-accounts` | 연동된 소셜 계정 목록 |
  | `DELETE /api/auth/{provider}/unlink` | 소셜 계정 연동 해제 |
  | `POST /api/auth/logout` | 로그아웃 |
  | `POST /api/auth/logout-all` | 전체 로그아웃 |
- **UI 컴포넌트**: `Card`, `Avatar`, `Button`, `Badge`, `Separator`, `Skeleton`, `AlertDialog`, Toast (Sonner)
- **특징**:
  - 반응형 디자인 (모바일/데스크톱 대응)
  - 로딩 스켈레톤 UI
  - Toast 알림으로 사용자 피드백
  - 다크모드 지원
  - 네비게이션: 헤더 사용자 드롭다운 메뉴에서 접근

---

## 디렉토리 구조

```
frontend/
├── app/                          # Next.js App Router
│   ├── (layouts)/                # 레이아웃 그룹 (39개 레이아웃)
│   │   ├── layout-1/             # 레이아웃 1
│   │   ├── layout-2/             # 레이아웃 2
│   │   └── ...
│   ├── auth/                     # 인증 관련 페이지
│   │   ├── callback/page.tsx     # OAuth 콜백 처리
│   │   └── error/page.tsx        # 인증 에러 페이지
│   ├── login/page.tsx            # 로그인 페이지
│   ├── mypage/                   # 마이페이지
│   │   ├── page.tsx              # 마이페이지 메인
│   │   └── layout.tsx            # 메타데이터
│   ├── layout.tsx                # 루트 레이아웃
│   ├── page.tsx                  # 홈 페이지
│   └── favicon.ico               # 파비콘
│
├── components/                   # 공통 컴포넌트
│   ├── layouts/                  # 레이아웃 컴포넌트 (39개)
│   │   ├── layout-1/
│   │   │   ├── components/       # 레이아웃별 컴포넌트
│   │   │   ├── shared/           # 공유 컴포넌트
│   │   │   └── index.tsx
│   │   └── ...
│   ├── providers/                # Context Providers
│   │   └── auth-provider.tsx     # 인증 상태 Provider
│   ├── ui/                       # UI 컴포넌트 (ReUI 기반)
│   └── screen-loader.tsx         # 화면 로더
│
├── config/                       # 설정 파일
│   ├── general.config.ts         # 일반 설정
│   ├── layout-*.config.tsx       # 레이아웃별 설정
│   └── types.ts                  # 타입 정의
│
├── hooks/                        # 커스텀 훅
│   ├── use-body-class.ts         # Body 클래스 관리
│   ├── use-copy-to-clipboard.ts  # 클립보드 복사
│   ├── use-menu.ts               # 메뉴 상태
│   ├── use-mobile.tsx            # 모바일 감지
│   ├── use-mounted.ts            # 마운트 상태
│   ├── use-scroll-position.ts    # 스크롤 위치
│   ├── use-slider-input.ts       # 슬라이더 입력
│   └── use-viewport.ts           # 뷰포트 크기
│
├── lib/                          # 유틸리티 함수
│   ├── api/                      # API 클라이언트
│   │   ├── client.ts             # 클라이언트 사이드 API
│   │   └── server.ts             # 서버 사이드 API (SSR)
│   ├── dom.ts                    # DOM 유틸리티
│   ├── helpers.ts                # 헬퍼 함수
│   └── utils.ts                  # 일반 유틸리티
│
├── styles/                       # 스타일 파일
│   ├── globals.css               # 전역 스타일
│   ├── config.metronic.css       # Metronic 설정 스타일
│   ├── components/               # 컴포넌트 스타일
│   └── demos/                    # 데모 스타일
│
├── public/                       # 정적 파일
├── middleware.ts                 # Next.js 라우트 보호 미들웨어
├── next.config.mjs               # Next.js 설정
├── tsconfig.json                 # TypeScript 설정
├── postcss.config.cjs            # PostCSS 설정
├── eslint.config.mjs             # ESLint 설정
└── package.json                  # 의존성 관리
```

## 레이아웃 시스템

Metronic은 39개의 사전 정의된 레이아웃을 제공합니다:

| 레이아웃 | 설명 |
|----------|------|
| `layout-1` ~ `layout-10` | 사이드바 기반 대시보드 |
| `layout-11` ~ `layout-20` | 탑바 + 사이드바 조합 |
| `layout-21` ~ `layout-30` | 복합 레이아웃 |
| `layout-31` ~ `layout-39` | 특수 목적 레이아웃 |

### 레이아웃 변경

`app/(layouts)/` 내 원하는 레이아웃 폴더에서 페이지를 구성합니다.

## 명령어

### 개발

```bash
cd frontend

# 의존성 설치 (React 19 호환성을 위해 --force 사용)
npm install --force

# 개발 서버 실행 (http://localhost:3002)
npm run dev
```

### 빌드

```bash
# 프로덕션 빌드
npm run build

# 프로덕션 서버 실행
npm start

# 스테이징 빌드
npm run build:staging
```

### 코드 품질

```bash
# ESLint 검사
npm run lint

# Prettier 포맷팅
npm run format
```

## 환경 변수

### .env.example

```env
# Base URL (프록시 배포 시 경로 설정)
NEXT_PUBLIC_BASE_PATH=/
```

### 환경별 설정

| 파일 | 용도 |
|------|------|
| `.env` | 기본 환경 변수 |
| `.env.local` | 로컬 개발 (gitignore) |
| `.env.staging` | 스테이징 환경 |
| `.env.production` | 프로덕션 환경 |

## 인증 시스템

### 인증 상태 관리

프론트엔드에서 인증 상태는 다음과 같이 관리됩니다:

| 구성 요소 | 위치 | 역할 |
|-----------|------|------|
| `AuthProvider` | `components/providers/auth-provider.tsx` | React Context로 인증 상태 제공 |
| `getServerUser()` | `lib/api/server.ts` | SSR에서 사용자 정보 조회 |
| `authApi` | `lib/api/client.ts` | 클라이언트 인증 API |
| `middleware.ts` | `frontend/middleware.ts` | 라우트 보호 미들웨어 |

### 인증 Provider 사용

```tsx
// 컴포넌트에서 인증 상태 접근
import { useAuth } from '@/components/providers/auth-provider';

function MyComponent() {
  const { isLoggedIn, user, isAdmin } = useAuth();

  if (!isLoggedIn) {
    return <LoginPrompt />;
  }

  return <div>환영합니다, {user?.name}님!</div>;
}
```

### 토큰 관리

인증 토큰은 HttpOnly 쿠키로 관리됩니다:

| 쿠키 | 타입 | 용도 |
|------|------|------|
| `access_token` | HttpOnly | JWT 액세스 토큰 |
| `refresh_token` | HttpOnly | 리프레시 토큰 (7일) |
| `token_type` | 일반 | 인증 상태 확인용 (클라이언트 접근 가능) |

---

## 라우트 보호 (Middleware)

Next.js Middleware를 사용하여 인증 상태에 따른 라우트 접근을 제어합니다.

### 파일 위치

```
frontend/middleware.ts
```

### 보호 유형

| 유형 | 경로 | 동작 |
|------|------|------|
| **게스트 전용** | `/login` | 로그인한 사용자는 `/`로 리다이렉트 |
| **인증 필요** | `/mypage/*`, `/settings/*`, `/dashboard/*` | 비로그인 사용자는 `/login`으로 리다이렉트 |

### 작동 방식

```typescript
// 게스트 전용 경로 (로그인한 사용자 접근 불가)
const GUEST_ONLY_PATHS = ['/login'];

// 인증 필요 경로 (비로그인 사용자 접근 불가)
const AUTH_REQUIRED_PATTERNS: RegExp[] = [
  /^\/mypage(\/.*)?$/,    // /mypage 및 하위 경로
  /^\/settings(\/.*)?$/,  // /settings 및 하위 경로
  /^\/dashboard(\/.*)?$/, // /dashboard 및 하위 경로
];
```

### 리다이렉트 후 원래 페이지로 돌아가기

비로그인 사용자가 보호된 경로에 접근하면:

1. `/login?redirect=/original-path`로 리다이렉트
2. 로그인 페이지에서 `redirect` 파라미터를 `sessionStorage`에 저장
3. OAuth 인증 완료 후 콜백 페이지에서 저장된 경로로 리다이렉트

```typescript
// 로그인 페이지에서 redirect 저장
useEffect(() => {
  const redirect = searchParams.get('redirect');
  if (redirect && redirect.startsWith('/')) {
    sessionStorage.setItem('auth_redirect', redirect);
  }
}, [searchParams]);
```

### 새로운 보호 경로 추가

`middleware.ts`에서 패턴을 추가합니다:

```typescript
// 예: /profile 경로 보호 추가
const AUTH_REQUIRED_PATTERNS: RegExp[] = [
  /^\/mypage(\/.*)?$/,
  /^\/settings(\/.*)?$/,
  /^\/dashboard(\/.*)?$/,
  /^\/profile(\/.*)?$/,  // 추가
];
```

### 보안 고려사항

- **외부 URL 리다이렉트 방지**: `redirect` 파라미터는 `/`로 시작하고 `//`로 시작하지 않는 상대 경로만 허용
- **쿠키 기반 인증 확인**: `access_token` 또는 `token_type` 쿠키 존재 여부로 인증 상태 판단

---

## 백엔드 연동

### API 베이스 URL 설정

```typescript
// lib/api.ts (예시)
const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';
```

### React Query 설정

```typescript
// app/providers.tsx (예시)
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 60 * 1000, // 1분
      retry: 1,
    },
  },
});
```

## 스타일링 가이드

### Tailwind CSS 사용

```tsx
// 기본 사용법
<div className="flex items-center justify-between p-4 bg-white rounded-lg shadow">
  <h1 className="text-xl font-semibold text-gray-900">제목</h1>
</div>
```

### 클래스 병합 (cn 유틸리티)

```typescript
// lib/utils.ts
import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}
```

### 다크모드

`next-themes`를 사용하여 다크모드를 지원합니다:

```tsx
import { useTheme } from 'next-themes';

function ThemeToggle() {
  const { theme, setTheme } = useTheme();
  return (
    <button onClick={() => setTheme(theme === 'dark' ? 'light' : 'dark')}>
      테마 변경
    </button>
  );
}
```

## 주요 컴포넌트 (UI)

`components/ui/` 디렉토리에는 ReUI 기반의 재사용 가능한 컴포넌트가 포함되어 있습니다:

- **Button** - 버튼 컴포넌트
- **Input** - 입력 필드
- **Select** - 선택 드롭다운
- **Dialog** - 모달 대화상자
- **Table** - 데이터 테이블
- **Card** - 카드 컴포넌트
- **Tabs** - 탭 네비게이션
- **Toast** - 토스트 알림
- **Dropdown** - 드롭다운 메뉴
- 그 외 다수...

## SEO 최적화

Next.js App Router의 메타데이터 API를 활용합니다:

```typescript
// app/layout.tsx
import type { Metadata } from 'next';

export const metadata: Metadata = {
  title: 'Toy Domain Service',
  description: '도메인 핵심 로직을 제공하는 서비스',
  openGraph: {
    title: 'Toy Domain Service',
    description: '도메인 핵심 로직을 제공하는 서비스',
  },
};
```

## 참고 자료

- [Next.js 공식 문서](https://nextjs.org/docs)
- [Tailwind CSS 공식 문서](https://tailwindcss.com/docs)
- [Metronic 문서](https://docs.keenthemes.com/metronic-nextjs)
- [ReUI 컴포넌트](https://reui.io)
- [TanStack React Query](https://tanstack.com/query)