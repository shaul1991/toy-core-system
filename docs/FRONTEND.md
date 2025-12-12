# Frontend

Next.js 기반의 프론트엔드 애플리케이션입니다. Metronic v9.3.8 템플릿을 기반으로 구축되었습니다.

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

## 디렉토리 구조

```
frontend/
├── app/                          # Next.js App Router
│   ├── (layouts)/                # 레이아웃 그룹 (39개 레이아웃)
│   │   ├── layout-1/             # 레이아웃 1
│   │   ├── layout-2/             # 레이아웃 2
│   │   └── ...
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

# 개발 서버 실행 (http://localhost:3000)
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