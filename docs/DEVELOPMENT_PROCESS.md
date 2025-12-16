# 개발 작업 프로세스

**Toy Core System**의 개발 작업 프로세스 및 아키텍처 가이드입니다.

> **⚠️ 중요 원칙**: Internal API (`/internal/*`)는 **절대 외부에 공개되어서는 안 됩니다**.
> 모든 외부 요청은 **반드시 BFF 레이어**를 거쳐야 합니다.

---

## 목차

1. [아키텍처 개요](#아키텍처-개요)
2. [레이어별 책임](#레이어별-책임)
3. [개발 순서](#개발-순서)
4. [API 설계 원칙](#api-설계-원칙)
5. [보안 가이드](#보안-가이드)
6. [테스트 전략](#테스트-전략)
7. [배포 고려사항](#배포-고려사항)

---

## 아키텍처 개요

### 3-Tier 레이어 구조

```
┌─────────────────────────────────────────────────────────────────┐
│                         EXTERNAL ACCESS                         │
│                    (외부 인터넷, 사용자)                          │
└─────────────────────────────┬───────────────────────────────────┘
                              │
                              │ HTTPS (포트 443)
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Frontend Layer                             │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  Next.js (React)                                           │ │
│  │  - UI/UX 렌더링                                             │ │
│  │  - 사용자 인터랙션                                           │ │
│  │  - JWT 토큰 저장/관리                                        │ │
│  │  - BFF API 호출만 가능                                       │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────┬───────────────────────────────────┘
                              │
                              │ BFF API (/api/*)
                              │ Authorization: Bearer {JWT}
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                       BFF Layer ⭐                              │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  Laravel BFF (routes/api.php)                              │ │
│  │  - JWT 인증/검증 ✅                                          │ │
│  │  - 권한 확인                                                 │ │
│  │  - Rate Limiting                                            │ │
│  │  - X-User-Id 헤더 설정                                       │ │
│  │  - Internal API 오케스트레이션                               │ │
│  │  - 응답 변환/캐싱                                            │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────┬───────────────────────────────────┘
                              │
                              │ Internal API (/internal/*)
                              │ X-User-Id: {user_id}
                              │ 🔒 내부 네트워크 전용
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Core Service Layer                           │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  Laravel Core (routes/internal.php)                        │ │
│  │  - 순수 도메인 로직                                          │ │
│  │  - 데이터 영속성 (PostgreSQL, Redis, MongoDB, MinIO)        │ │
│  │  - 비즈니스 규칙 적용                                         │ │
│  │  - JWT 검증 없음 (X-User-Id 헤더만 신뢰)                     │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                 │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  Databases                                                 │ │
│  │  - PostgreSQL (관계형 데이터)                               │ │
│  │  - Redis (캐시, 세션)                                       │ │
│  │  - MongoDB (활동 로그)                                      │ │
│  │  - MinIO (파일 저장소)                                      │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

### 핵심 원칙

| 원칙 | 설명 |
|------|------|
| **Internal API 격리** | `/internal/*` 는 외부 네트워크에서 **절대 접근 불가** |
| **BFF 게이트웨이** | 모든 외부 요청은 **BFF를 통해서만** Core Service 접근 |
| **JWT 검증 위치** | **BFF에서만** JWT 검증, Core Service는 검증 안 함 |
| **사용자 ID 전달** | BFF가 `X-User-Id` 헤더로 사용자 ID 전달 |
| **단방향 의존성** | Frontend → BFF → Core Service (역방향 호출 금지) |

---

## 레이어별 책임

### 1. Frontend Layer (Next.js)

**위치**: 별도 프로젝트 (외부)

**책임**:
- ✅ UI/UX 렌더링
- ✅ 사용자 입력 처리 및 검증
- ✅ JWT 토큰 저장/관리 (localStorage, sessionStorage)
- ✅ BFF API 호출 (`/api/*`)
- ✅ 클라이언트 사이드 라우팅

**금지**:
- ❌ Internal API 직접 호출 (`/internal/*`)
- ❌ 비즈니스 로직 구현
- ❌ 데이터베이스 직접 접근

**API 호출 예시**:
```javascript
// ✅ 올바른 방법 (BFF 호출)
const response = await fetch('/api/posts', {
  headers: {
    'Authorization': `Bearer ${jwt}`,
  }
});

// ❌ 잘못된 방법 (Internal API 직접 호출)
const response = await fetch('/internal/posts', {
  headers: {
    'X-User-Id': userId,  // 보안 취약점!
  }
});
```

---

### 2. BFF Layer (Backend For Frontend)

**위치**: `toy-core-system` (이 프로젝트)
**경로**: `/api/*` (`routes/api.php`)

**책임**:
- ✅ **JWT 인증/검증** (php-jwt)
- ✅ 권한 확인 (Role-Based Access Control)
- ✅ Rate Limiting (요청 제한)
- ✅ **X-User-Id 헤더 설정** (JWT에서 추출)
- ✅ Internal API 오케스트레이션 (여러 도메인 조합)
- ✅ 응답 변환 (Frontend에 최적화된 포맷)
- ✅ 캐싱 (Redis)
- ✅ 에러 핸들링 (사용자 친화적 메시지)

**파일 구조**:
```
app/Bff/
├── Controllers/
│   ├── AuthController.php       # JWT 인증
│   ├── PostController.php       # Post BFF API
│   └── ...
├── Middleware/
│   ├── JwtAuthMiddleware.php    # JWT 검증
│   └── RateLimitMiddleware.php
└── Services/
    └── CoreServiceClient.php    # Internal API 호출 헬퍼
```

**BFF Controller 예시**:
```php
<?php

namespace App\Bff\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PostController extends Controller
{
    /**
     * BFF: 게시물 목록 조회
     */
    public function index(Request $request): JsonResponse
    {
        // 1. JWT에서 사용자 ID 추출 (미들웨어에서 이미 검증됨)
        $userId = auth()->id();

        // 2. Internal API 호출
        $response = Http::withHeaders([
            'X-User-Id' => $userId,
        ])->get(config('app.internal_api_url') . '/posts', [
            'status' => $request->query('status'),
            'per_page' => $request->query('per_page', 15),
        ]);

        // 3. 응답 반환 (필요 시 변환)
        return response()->json($response->json());
    }

    /**
     * BFF: 게시물 생성
     */
    public function store(Request $request): JsonResponse
    {
        $userId = auth()->id();

        // BFF 레벨 검증
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        // Internal API 호출
        $response = Http::withHeaders([
            'X-User-Id' => $userId,
        ])->post(config('app.internal_api_url') . '/posts', $validated);

        return response()->json($response->json(), $response->status());
    }
}
```

---

### 3. Core Service Layer (Domain Service)

**위치**: `toy-core-system` (이 프로젝트)
**경로**: `/internal/*` (`routes/internal.php`)

**책임**:
- ✅ 순수 도메인 로직 (비즈니스 규칙)
- ✅ 데이터 영속성 (DB CRUD)
- ✅ 도메인 이벤트 발행
- ✅ 데이터 검증 (도메인 규칙)
- ✅ **X-User-Id 헤더 신뢰** (BFF에서 검증됨)

**금지**:
- ❌ JWT 검증 (BFF에서 이미 검증됨)
- ❌ 외부 네트워크 노출
- ❌ Frontend 직접 통신

**파일 구조**:
```
app/Domain/
├── Post/
│   ├── Controllers/
│   │   └── PostController.php   # Internal API
│   ├── Services/
│   │   └── PostService.php      # 비즈니스 로직
│   ├── DTOs/
│   │   ├── CreatePostDTO.php
│   │   └── UpdatePostDTO.php
│   └── Exceptions/
└── Auth/
    └── ...
```

**Internal API Controller 예시**:
```php
<?php

namespace App\Domain\Post\Controllers;

use App\Domain\Post\Services\PostService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PostController extends Controller
{
    use ApiResponsable;
    use HasAuthenticatedUserId;  // X-User-Id 헤더에서 추출

    public function __construct(
        private readonly PostService $postService,
    ) {}

    /**
     * Internal API: 게시물 생성
     *
     * ⚠️ 이 API는 BFF에서만 호출됩니다.
     */
    public function store(Request $request): JsonResponse
    {
        // X-User-Id 헤더에서 사용자 ID 추출 (BFF에서 설정)
        $userId = $this->getAuthenticatedUserId();

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $dto = CreatePostDTO::fromRequest($request, $userId);
        $post = $this->postService->createPost($dto);

        return $this->createdResponse($post);
    }
}
```

---

## 개발 순서

새로운 기능을 추가할 때는 **아래에서 위로** (Core → BFF → Frontend) 순서로 개발합니다.

### Step 1: Core Service 개발 (Domain Layer)

**목표**: 순수 도메인 로직 구현

1. **데이터베이스 설계**
   ```bash
   php artisan make:migration create_posts_table
   ```

2. **Model 생성**
   ```bash
   php artisan make:model Post
   ```

3. **Domain 구조 생성**
   ```
   app/Domain/Post/
   ├── Controllers/PostController.php
   ├── Services/PostService.php
   ├── DTOs/CreatePostDTO.php
   └── DTOs/UpdatePostDTO.php
   ```

4. **Internal API 라우트 등록**
   ```php
   // routes/internal.php
   Route::prefix('posts')->middleware('user.id')->group(function () {
       Route::post('/', [PostController::class, 'store']);
       Route::get('/', [PostController::class, 'index']);
   });
   ```

5. **테스트 작성**
   ```bash
   php artisan make:test Domain/Post/PostControllerTest
   ```

**완료 기준**: Internal API가 정상 동작하고 테스트 통과

---

### Step 2: BFF Layer 개발 (API Gateway)

**목표**: Internal API를 래핑하고 JWT 인증 추가

1. **BFF Controller 생성**
   ```php
   // app/Bff/Controllers/PostController.php
   class PostController extends Controller
   {
       public function index(Request $request): JsonResponse
       {
           $userId = auth()->id();  // JWT에서 추출

           $response = Http::withHeaders([
               'X-User-Id' => $userId,
           ])->get(config('app.internal_api_url') . '/posts');

           return response()->json($response->json());
       }
   }
   ```

2. **BFF 라우트 등록**
   ```php
   // routes/api.php
   Route::middleware('bff.auth')->group(function () {
       Route::get('/posts', [PostController::class, 'index']);
       Route::post('/posts', [PostController::class, 'store']);
   });
   ```

3. **API 문서 작성** (Swagger)
   ```yaml
   /api/posts:
     get:
       summary: 게시물 목록 조회
       security:
         - BearerAuth: []
   ```

**완료 기준**: BFF API가 JWT 인증과 함께 동작

---

### Step 3: Frontend 개발 (UI Layer)

**목표**: 사용자 인터페이스 구현

1. **API 클라이언트 생성**
   ```typescript
   // lib/api/posts.ts
   export async function getPosts() {
     const jwt = localStorage.getItem('jwt');
     const response = await fetch('/api/posts', {
       headers: {
         'Authorization': `Bearer ${jwt}`,
       }
     });
     return response.json();
   }
   ```

2. **컴포넌트 개발**
   ```tsx
   // components/PostList.tsx
   export function PostList() {
     const { data, isLoading } = useQuery('posts', getPosts);
     // ...
   }
   ```

3. **페이지 라우팅**
   ```typescript
   // app/posts/page.tsx
   export default function PostsPage() {
     return <PostList />;
   }
   ```

**완료 기준**: UI에서 BFF API 호출 성공

---

## API 설계 원칙

### 1. URL 설계

| 레이어 | 경로 | 예시 | 접근 가능 대상 |
|--------|------|------|---------------|
| Frontend → BFF | `/api/*` | `/api/posts` | **외부 인터넷** |
| BFF → Core | `/internal/*` | `/internal/posts` | **내부 네트워크만** |
| Web UI | `/posts/*` | `/posts/create` | 외부 인터넷 (뷰만) |

### 2. 헤더 규칙

**Frontend → BFF**:
```http
GET /api/posts HTTP/1.1
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

**BFF → Core Service**:
```http
GET /internal/posts HTTP/1.1
X-User-Id: 123
```

### 3. 응답 포맷

**공통 응답 구조** (모든 레이어 동일):
```json
{
  "success": true,
  "data": { ... },
  "pagination": { ... }
}
```

**에러 응답**:
```json
{
  "success": false,
  "error": {
    "code": "NOT_FOUND",
    "message": "리소스를 찾을 수 없습니다.",
    "details": { ... }
  }
}
```

---

## 보안 가이드

### 1. Internal API 보호 전략

#### ⚠️ 네트워크 레벨 격리 (필수)

**프로덕션 환경**:
```nginx
# Nginx 설정 예시
server {
    listen 80;
    server_name api.example.com;

    # BFF API만 외부 노출
    location /api/ {
        proxy_pass http://bff_backend;
    }

    # Internal API는 외부 접근 차단
    location /internal/ {
        deny all;
        return 403;
    }
}
```

**Docker Compose 네트워크 격리**:
```yaml
version: '3.8'
services:
  frontend:
    networks:
      - public
    ports:
      - "3000:3000"

  bff:
    networks:
      - public
      - internal
    ports:
      - "8000:8000"  # BFF API만 노출

  core_service:
    networks:
      - internal  # 내부 네트워크만
    # 포트 노출 안 함!
```

#### 🔒 애플리케이션 레벨 검증

**Internal API 미들웨어**:
```php
// app/Http/Middleware/InternalApiOnly.php
class InternalApiOnly
{
    public function handle(Request $request, Closure $next)
    {
        // IP 화이트리스트 확인 (BFF 서버 IP만 허용)
        $allowedIps = config('app.bff_ips', []);

        if (!in_array($request->ip(), $allowedIps)) {
            abort(403, 'Internal API - Access Denied');
        }

        return $next($request);
    }
}
```

### 2. X-User-Id 헤더 보안

**잘못된 예시** ❌:
```javascript
// Frontend에서 X-User-Id 직접 설정 (보안 취약!)
fetch('/internal/posts', {
  headers: {
    'X-User-Id': '123',  // 다른 사용자 ID로 위조 가능!
  }
});
```

**올바른 예시** ✅:
```php
// BFF에서만 X-User-Id 설정
class JwtAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $jwt = $request->bearerToken();
        $payload = JWT::decode($jwt, ...);

        // BFF가 JWT 검증 후 사용자 ID 추출
        $request->headers->set('X-User-Id', $payload->user_id);

        return $next($request);
    }
}
```

### 3. Rate Limiting

**BFF 레벨**:
```php
// routes/api.php
Route::middleware(['bff.auth', 'throttle:60,1'])->group(function () {
    Route::get('/posts', [PostController::class, 'index']);
});
```

**Internal API 레벨** (선택):
```php
// routes/internal.php
Route::middleware('throttle:1000,1')->group(function () {
    Route::get('/posts', [PostController::class, 'index']);
});
```

---

## 테스트 전략

### 1. Core Service 테스트

**Feature Test**:
```php
// tests/Feature/Domain/Post/PostControllerTest.php
public function test_creates_post_with_user_id()
{
    $response = $this->postJson('/internal/posts', [
        'title' => 'Test Post',
        'content' => 'Content',
    ], [
        'X-User-Id' => 1,  // 테스트용 헤더
    ]);

    $response->assertCreated();
}
```

### 2. BFF 테스트

**Integration Test**:
```php
// tests/Feature/Bff/PostControllerTest.php
public function test_bff_creates_post_with_jwt()
{
    $jwt = $this->generateTestJwt(['user_id' => 1]);

    $response = $this->postJson('/api/posts', [
        'title' => 'Test Post',
        'content' => 'Content',
    ], [
        'Authorization' => "Bearer $jwt",
    ]);

    $response->assertCreated();
}
```

### 3. Frontend 테스트

**E2E Test** (Playwright):
```typescript
test('사용자가 게시물을 생성할 수 있다', async ({ page }) => {
  await page.goto('/posts/create');
  await page.fill('#title', 'Test Post');
  await page.fill('#content', 'Content');
  await page.click('#submit');

  await expect(page).toHaveURL('/posts/test-post');
});
```

---

## 배포 고려사항

### 1. 환경 변수

**BFF 설정** (`.env`):
```bash
# Internal API URL (내부 네트워크)
INTERNAL_API_URL=http://core_service:8080

# BFF 서버 IP (Core Service 화이트리스트용)
BFF_SERVER_IPS=10.0.1.5,10.0.1.6
```

**Core Service 설정** (`.env`):
```bash
# BFF IP 화이트리스트
BFF_IPS=10.0.1.5,10.0.1.6
```

### 2. 로드 밸런서 설정

**AWS ALB 예시**:
```yaml
# BFF용 ALB (외부 노출)
LoadBalancer:
  Type: application
  Scheme: internet-facing
  SecurityGroups:
    - sg-bff-public

# Core Service용 Internal ALB (내부 전용)
InternalLoadBalancer:
  Type: application
  Scheme: internal  # 내부 전용!
  SecurityGroups:
    - sg-core-internal
```

### 3. 보안 그룹

**AWS Security Group**:
```
┌─────────────────────────────────────────────────┐
│  BFF Security Group (sg-bff)                    │
│  Inbound:                                       │
│    - 443 (HTTPS) from 0.0.0.0/0                 │
│  Outbound:                                      │
│    - 8080 to Core Service (sg-core)             │
└─────────────────────────────────────────────────┘
                    │
                    │ HTTP :8080
                    ▼
┌─────────────────────────────────────────────────┐
│  Core Service Security Group (sg-core)          │
│  Inbound:                                       │
│    - 8080 from BFF (sg-bff) ONLY                │
│  Outbound:                                      │
│    - PostgreSQL, Redis, etc.                    │
└─────────────────────────────────────────────────┘
```

---

## 체크리스트

### 새 도메인 추가 시

- [ ] **Core Service 구현**
  - [ ] Migration 파일 생성
  - [ ] Model 생성
  - [ ] Service/Controller 구현
  - [ ] Internal API 라우트 등록 (`routes/internal.php`)
  - [ ] Feature 테스트 작성
  - [ ] 도메인 문서 작성 (`docs/{DOMAIN}.md`)

- [ ] **BFF Layer 추가**
  - [ ] BFF Controller 생성 (`app/Bff/Controllers/`)
  - [ ] BFF 라우트 등록 (`routes/api.php`)
  - [ ] JWT 미들웨어 적용
  - [ ] Integration 테스트 작성

- [ ] **Frontend 구현**
  - [ ] API 클라이언트 생성
  - [ ] 컴포넌트 개발
  - [ ] E2E 테스트 작성

### 보안 체크리스트

- [ ] Internal API가 외부 노출되지 않는지 확인
- [ ] X-User-Id 헤더는 BFF에서만 설정되는지 확인
- [ ] JWT는 BFF에서만 검증되는지 확인
- [ ] Rate Limiting 적용 확인
- [ ] 네트워크 레벨 격리 확인 (프로덕션)

---

## 참고 문서

- **아키텍처**: [docs/ARCHITECTURE.md](ARCHITECTURE.md)
- **BFF Layer**: [docs/BFF.md](BFF.md)
- **Core Service**: [docs/CORE.md](CORE.md)
- **도메인 목록**: [CLAUDE.md](../CLAUDE.md)

---

## FAQ

### Q1. 왜 Internal API를 직접 호출하면 안 되나요?

**A**: Internal API는 JWT 검증을 하지 않기 때문에, X-User-Id 헤더를 위조하여 다른 사용자의 데이터에 접근할 수 있는 **심각한 보안 취약점**이 발생합니다.

```javascript
// ❌ 악의적인 공격 예시
fetch('/internal/posts', {
  headers: {
    'X-User-Id': '999',  // 관리자 ID로 위조!
  }
});
```

### Q2. BFF를 거치면 성능이 떨어지지 않나요?

**A**: BFF는 단순 프록시가 아니라 다음 역할을 수행합니다:
- JWT 검증 (한 번만)
- 여러 Internal API 오케스트레이션 (N+1 해결)
- 응답 캐싱 (Redis)
- Rate Limiting

오히려 **성능 향상** 및 **보안 강화** 효과가 있습니다.

### Q3. 로컬 개발 시에도 BFF를 거쳐야 하나요?

**A**: 네, 개발 환경에서도 BFF를 사용해야 **프로덕션과 동일한 환경**에서 테스트할 수 있습니다.

```bash
# 로컬 개발
composer dev  # BFF + Core Service 동시 실행
```

### Q4. Web Routes (`/posts/*`)는 어떻게 관리하나요?

**A**: Web Routes는 단순히 **Blade 뷰를 렌더링**하는 용도로만 사용합니다. 실제 데이터는 Frontend JavaScript가 **BFF API**를 호출하여 가져옵니다.

```php
// routes/web.php (뷰만 렌더링)
Route::get('/posts/create', [PostViewController::class, 'create']);

// Frontend JavaScript가 BFF API 호출
fetch('/api/posts', { ... });
```
