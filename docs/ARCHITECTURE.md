# Application Architecture

Toy Domain Service의 전체 아키텍처 문서입니다.

## 개요

**3-Tier 레이어 아키텍처**로 구성된 마이크로서비스 기반 시스템입니다. 각 레이어는 명확한 책임을 가지며, 독립적으로 개발 및 배포 가능합니다.

```
┌─────────────────────────────────────────────────────┐
│                    Frontend Layer                    │
│                     (Next.js)                        │
│                  UI/UX, User Input                   │
└────────────────────┬────────────────────────────────┘
                     │ HTTP/JSON (Public API)
                     │ /api/*
                     ▼
┌─────────────────────────────────────────────────────┐
│                      BFF Layer                       │
│           (Backend For Frontend - Laravel)           │
│     JWT Auth, API Gateway, Response Transform       │
└────────────────────┬────────────────────────────────┘
                     │ Internal Call (Private API)
                     │ /internal/*
                     ▼
┌─────────────────────────────────────────────────────┐
│                  Core Service Layer                  │
│                    (Laravel)                         │
│       Domain Logic, Business Rules, Data Access      │
└────────────────────┬────────────────────────────────┘
                     │
                     ▼
         ┌───────────────────────────┐
         │   Infrastructure Layer    │
         │  PostgreSQL, Redis, MinIO │
         │        MongoDB            │
         └───────────────────────────┘
```

## 레이어 구성

### 1. Frontend Layer

**기술 스택:** Next.js 16.x, React 19.x, TypeScript, Tailwind CSS

| 항목 | 설명 |
|------|------|
| **위치** | `/frontend` |
| **역할** | 사용자 UI 제공, 사용자 입력 처리, 화면 렌더링 |
| **호출 대상** | BFF Layer (`/api/*`) |
| **인증 방식** | HttpOnly Cookie (access_token, refresh_token) |
| **문서** | [FRONTEND.md](./FRONTEND.md) |

**주요 책임:**
- 사용자 인터페이스 렌더링
- 클라이언트 사이드 상태 관리 (React Query)
- 사용자 입력 검증 (React Hook Form + Zod)
- BFF API 호출 (쿠키 자동 전송)
- 라우트 보호 (Next.js Middleware)

**특징:**
- BFF만 호출하며, Core Service에 직접 접근하지 않음
- HttpOnly Cookie로 토큰 관리 (XSS 방지)
- SSR/CSR 하이브리드 렌더링
- 39개의 Metronic 레이아웃 제공

---

### 2. BFF Layer (Backend For Frontend)

**기술 스택:** Laravel 12.x, PHP 8.4+

| 항목 | 설명 |
|------|------|
| **위치** | `app/Bff/`, `routes/api.php` |
| **경로** | `/api/*` (외부 공개) |
| **역할** | JWT 인증/인가, Core Service 호출, 응답 변환 |
| **호출 대상** | Core Service (`/internal/*`) |
| **인증 방식** | JWT (Bearer Token 또는 Cookie) |
| **문서** | [BFF.md](./BFF.md) |

**주요 책임:**
- JWT 토큰 검증 및 관리 (발급, 갱신, 무효화)
- OAuth 소셜 로그인 흐름 처리 (리다이렉트, 콜백)
- Core Service 내부 API 호출
- 프론트엔드 친화적 응답 포맷 변환
- Rate Limiting

**특징:**
- 프론트엔드와 Core Service 사이의 게이트웨이 역할
- JWT 검증 후 `X-User-Id` 헤더로 사용자 ID 전달
- HttpOnly 쿠키 설정으로 보안 강화
- Core Service를 직접 호출하는 유일한 외부 인터페이스

**파일 구조:**
```
app/Bff/
├── Controllers/
│   └── AuthController.php         # BFF 인증 컨트롤러
├── Middleware/
│   └── JwtAuthenticate.php        # JWT 검증 미들웨어
└── Services/
    └── CoreAuthService.php        # Core Service 클라이언트
```

---

### 3. Core Service Layer

**기술 스택:** Laravel 12.x, PHP 8.4+, PostgreSQL, Redis, MongoDB, MinIO

| 항목 | 설명 |
|------|------|
| **위치** | `app/Domain/`, `app/Http/`, `app/Services/` |
| **경로** | `/internal/*` (내부 전용) |
| **역할** | 비즈니스 로직, 도메인 규칙, 데이터 관리 |
| **호출자** | BFF Layer |
| **인증 방식** | `X-User-Id` 헤더 (BFF에서 전달) |
| **문서** | [CORE.md](./CORE.md) |

**주요 책임:**
- 도메인 비즈니스 로직 실행
- 데이터베이스 CRUD 작업
- 캐싱 전략 (Redis)
- 파일 저장소 관리 (MinIO)
- 알림 발송 (Email, SMS, Slack)
- 사용자 활동 로그 (MongoDB)
- Queue 작업 처리

**특징:**
- 재사용 가능한 도메인 서비스 제공
- DDD(Domain-Driven Design) 기반 구조
- Repository 패턴으로 데이터 추상화
- 도메인 예외 처리 (자동 API 응답 변환)
- 공통 HTTP 응답 포맷 (ApiResponse)

**파일 구조:**
```
app/
├── Domain/                        # 도메인 레이어 (DDD)
│   ├── Auth/                      # 인증 도메인
│   │   ├── Controllers/
│   │   ├── Services/
│   │   ├── DTOs/
│   │   ├── Repositories/
│   │   └── Exceptions/
│   ├── Health/                    # Health Check
│   └── UserActivity/              # 사용자 활동 로그
│
├── Http/Controllers/              # HTTP 컨트롤러
│   ├── TimerController.php
│   ├── FileController.php
│   └── NotificationController.php
│
├── Services/                      # 비즈니스 서비스
│   ├── TimerService.php
│   ├── FileService.php
│   └── Notification/
│
├── Repositories/                  # Repository 구현체
│   ├── CachedTimerRepository.php
│   ├── EloquentTimerRepository.php
│   └── ...
│
├── Models/                        # Eloquent Models
│   ├── User.php
│   ├── Timer.php
│   └── ...
│
└── Shared/                        # 공유 레이어
    ├── Exceptions/                # 도메인 예외
    ├── Http/                      # API 응답 공통화
    └── Database/                  # MongoDB 연결
```

---

## 레이어 간 통신

### Frontend → BFF

| 항목 | 설명 |
|------|------|
| **프로토콜** | HTTP/HTTPS |
| **경로** | `/api/*` |
| **인증** | HttpOnly Cookie (`access_token`) 또는 `Authorization: Bearer` 헤더 |
| **응답 포맷** | JSON (ApiResponse 표준 포맷) |

**예시 요청:**
```http
GET /api/auth/me HTTP/1.1
Host: api.example.com
Cookie: access_token=eyJhbGc...
```

**예시 응답:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "홍길동",
        "email": "hong@example.com"
    }
}
```

---

### BFF → Core Service

| 항목 | 설명 |
|------|------|
| **프로토콜** | Internal Call (동일 Laravel 앱) |
| **경로** | `/internal/*` |
| **인증** | `X-User-Id` 헤더 (JWT 검증 후 전달) |
| **응답 포맷** | DTO 또는 JSON |

**예시 호출:**
```php
// BFF Controller
$response = Http::asJson()
    ->withHeaders(['X-User-Id' => $userId])
    ->get('/internal/auth/me');

return $this->successResponse($response->json());
```

---

## 인증 흐름

### 소셜 로그인 (OAuth)

```mermaid
sequenceDiagram
    participant U as User
    participant F as Frontend
    participant B as BFF
    participant C as Core Service
    participant O as OAuth Provider

    U->>F: 로그인 버튼 클릭
    F->>B: GET /api/auth/github/redirect
    B->>O: Redirect to GitHub OAuth
    U->>O: 인증 승인
    O->>B: GET /api/auth/github/callback?code=...
    B->>C: POST /internal/auth/github/callback
    C->>C: 사용자 생성/조회
    C->>C: JWT 토큰 생성
    C-->>B: TokenDTO
    B->>B: Set HttpOnly Cookies
    B->>F: Redirect to /auth/callback
    F->>B: GET /api/auth/me (verify)
    B->>C: GET /internal/auth/me
    C-->>B: User Data
    B-->>F: User Data
    F->>F: Redirect to Dashboard
```

### API 인증 (JWT)

```mermaid
sequenceDiagram
    participant F as Frontend
    participant B as BFF
    participant C as Core Service

    Note over F: 쿠키에 access_token 존재
    F->>B: GET /api/auth/me (Cookie auto-sent)
    B->>B: JWT 검증
    B->>B: Extract User ID from JWT
    B->>C: GET /internal/auth/me (X-User-Id: 123)
    C->>C: Fetch User from DB
    C-->>B: User Data
    B-->>F: User Data (JSON)
```

### 토큰 갱신

```mermaid
sequenceDiagram
    participant F as Frontend
    participant B as BFF
    participant C as Core Service
    participant R as Redis

    F->>B: POST /api/auth/refresh (refresh_token cookie)
    B->>C: POST /internal/auth/refresh
    C->>R: Validate Refresh Token
    R-->>C: Valid
    C->>C: Generate New Token Pair
    C->>R: Store New Refresh Token
    C-->>B: New TokenDTO
    B->>B: Set New HttpOnly Cookies
    B-->>F: User Data (with Set-Cookie headers)
    Note over F: Browser updates cookies automatically
```

---

## 데이터 흐름

### 읽기 요청 (Read)

```
User → Frontend → BFF → Core Service → Repository → Cache/DB
                                                          ↓
User ← Frontend ← BFF ← Core Service ← Repository ← Cache Hit
                                                          ↓
                                                      Cache Miss → DB
```

### 쓰기 요청 (Write)

```
User → Frontend → BFF → Core Service → Repository → DB
                                                      ↓
                                                  Cache Invalidate
                                                      ↓
User ← Frontend ← BFF ← Core Service ← Repository ← Success
```

---

## Event-Driven Architecture

시스템은 **Event-Driven Architecture(EDA)**를 지향하며, 도메인 이벤트를 통해 느슨한 결합과 확장성을 달성합니다.

### 아키텍처 원칙

```
┌─────────────────────────────────────────────────────┐
│              Domain Layer (Core Service)             │
│                                                       │
│  ┌──────────┐      발행      ┌──────────────┐       │
│  │  Service │ ─────────────> │ Domain Event │       │
│  └──────────┘                └──────┬───────┘       │
│                                      │               │
│                                      ▼               │
│                              ┌──────────────┐       │
│                              │  Event Bus   │       │
│                              │  (Laravel)   │       │
│                              └──────┬───────┘       │
│                                      │               │
│                    ┌─────────────────┼─────────────┐│
│                    ▼                 ▼             ▼││
│              ┌──────────┐      ┌──────────┐  ┌────────┐
│              │ Listener │      │ Listener │  │ Queue  │
│              │ (Sync)   │      │ (Async)  │  │  Job   │
│              └──────────┘      └──────────┘  └────────┘
└─────────────────────────────────────────────────────┘
```

**핵심 원칙:**
1. **느슨한 결합**: 도메인 서비스는 이벤트만 발행하고, 처리는 리스너가 담당
2. **단일 책임**: 각 리스너는 하나의 명확한 책임을 가짐
3. **비동기 처리**: 중요하지 않은 작업은 Queue Job으로 비동기 처리
4. **확장성**: 새로운 기능 추가 시 기존 코드 수정 없이 리스너 추가

---

### 도메인 이벤트 패턴

#### 이벤트 발행

```php
// app/Domain/Auth/Services/SocialAuthService.php
public function handleCallback(string $provider, string $code): TokenDTO
{
    $socialUser = $this->getSocialUser($provider, $code);
    $user = $this->findOrCreateUser($socialUser);

    // 도메인 이벤트 발행
    if ($user->wasRecentlyCreated) {
        event(new UserCreated($user));
    }

    event(new UserLoggedIn($user, $provider));

    return $this->generateTokenPair($user);
}
```

#### 이벤트 리스너 (동기)

```php
// app/Domain/Auth/Listeners/CreateAuthEventLog.php
class CreateAuthEventLog
{
    public function handle(UserLoggedIn $event): void
    {
        $this->authEventService->log([
            'user_id' => $event->user->id,
            'event_type' => 'login',
            'provider' => $event->provider,
            'ip_address' => request()->ip(),
        ]);
    }
}
```

#### 이벤트 리스너 (비동기)

```php
// app/Domain/Auth/Listeners/SendWelcomeEmail.php
class SendWelcomeEmail implements ShouldQueue
{
    use Queueable;

    public function handle(UserCreated $event): void
    {
        Mail::to($event->user->email)
            ->send(new WelcomeEmail($event->user));
    }
}
```

---

### 이벤트 흐름

#### 사용자 생성 이벤트

```mermaid
sequenceDiagram
    participant S as Service
    participant E as Event Bus
    participant L1 as CreateAuthLog (Sync)
    participant L2 as SendWelcomeEmail (Queue)
    participant L3 as UpdateUserCache (Sync)
    participant Q as Queue Worker

    S->>E: event(new UserCreated($user))

    par 동기 리스너 실행
        E->>L1: handle(UserCreated)
        L1->>L1: Create auth event log
        L1-->>E: Complete
    and
        E->>L3: handle(UserCreated)
        L3->>L3: Cache user data
        L3-->>E: Complete
    end

    E->>Q: Dispatch SendWelcomeEmail
    Note over S,E: Service는 즉시 응답 반환

    Q->>L2: handle(UserCreated)
    L2->>L2: Send welcome email
    L2-->>Q: Complete
```

#### 파일 업로드 이벤트

```mermaid
sequenceDiagram
    participant S as FileService
    participant E as Event Bus
    participant L1 as CreateFileLog (Sync)
    participant L2 as GenerateThumbnail (Queue)
    participant L3 as ScanVirus (Queue)
    participant Q as Queue Worker

    S->>E: event(new FileUploaded($file))

    E->>L1: handle(FileUploaded)
    L1->>L1: Create file activity log
    L1-->>E: Complete

    par 비동기 작업 큐잉
        E->>Q: Dispatch GenerateThumbnail
        E->>Q: Dispatch ScanVirus
    end

    Note over S,E: Service는 즉시 응답 반환

    par Queue Workers
        Q->>L2: handle(FileUploaded)
        L2->>L2: Generate thumbnail
        L2->>E: event(new ThumbnailGenerated($file))
    and
        Q->>L3: handle(FileUploaded)
        L3->>L3: Scan for virus
        L3->>E: event(new VirusScanCompleted($file))
    end
```

---

### 이벤트 종류

#### 도메인 이벤트

| 도메인 | 이벤트 | 발행 시점 | 리스너 |
|--------|--------|----------|--------|
| **Auth** | `UserCreated` | 사용자 생성 | SendWelcomeEmail, CreateAuthLog, UpdateUserCache |
| **Auth** | `UserLoggedIn` | 로그인 성공 | CreateAuthLog, UpdateLoginStats |
| **Auth** | `UserLoggedOut` | 로그아웃 | CreateAuthLog, InvalidateCache |
| **Auth** | `SocialAccountLinked` | 소셜 계정 연동 | CreateAuthLog, SendLinkNotification |
| **File** | `FileUploaded` | 파일 업로드 | GenerateThumbnail, ScanVirus, CreateActivityLog |
| **File** | `FileDeleted` | 파일 삭제 | DeleteFromStorage, CreateActivityLog |
| **Timer** | `TimerCreated` | 타이머 생성 | InvalidateCache, CreateActivityLog |
| **Timer** | `TimerExpired` | 타이머 만료 | SendNotification, UpdateStatus |
| **Notification** | `NotificationSent` | 알림 발송 완료 | CreateNotificationLog, UpdateStats |
| **Notification** | `NotificationFailed` | 알림 발송 실패 | RetryNotification, AlertAdmin |

#### 시스템 이벤트

| 이벤트 | 발행 시점 | 리스너 |
|--------|----------|--------|
| `CacheMissed` | 캐시 미스 발생 | WarmupCache, LogCacheMiss |
| `HealthCheckFailed` | Health Check 실패 | AlertAdmin, CreateIncident |
| `QueueJobFailed` | Queue Job 실패 | RetryJob, AlertDeveloper |

---

### 이벤트 사용 사례

#### 1. 사용자 가입 시나리오

**요구사항:**
- 사용자 생성
- 환영 이메일 발송
- 인증 로그 기록
- 사용자 캐시 생성

**Event-Driven 구현:**

```php
// Service Layer - 이벤트만 발행
class SocialAuthService
{
    public function createUser(SocialUserDTO $dto): User
    {
        $user = User::create([...]);

        // 단일 이벤트 발행
        event(new UserCreated($user));

        return $user;
    }
}

// Event Listeners - 각자의 책임을 처리
class SendWelcomeEmail implements ShouldQueue
{
    public function handle(UserCreated $event): void
    {
        Mail::to($event->user)->send(new WelcomeEmail($event->user));
    }
}

class CreateUserAuthLog
{
    public function handle(UserCreated $event): void
    {
        $this->authEventService->createLog('user_created', $event->user);
    }
}

class WarmupUserCache
{
    public function handle(UserCreated $event): void
    {
        $this->userCacheService->set($event->user);
    }
}
```

**장점:**
- Service는 비즈니스 로직에만 집중
- 이메일 발송 실패 시에도 사용자 생성은 성공
- 새로운 기능 추가 시 리스너만 추가 (기존 코드 수정 불필요)

---

#### 2. 파일 업로드 시나리오

**요구사항:**
- 파일 저장 (MinIO)
- 썸네일 생성 (이미지인 경우)
- 바이러스 스캔
- 활동 로그 기록

**Event-Driven 구현:**

```php
// Service Layer
class FileService
{
    public function upload(UploadedFile $file, int $userId): File
    {
        // 1. 파일 저장
        $path = $this->minioRepository->store($file);

        // 2. DB 레코드 생성
        $fileModel = File::create([...]);

        // 3. 이벤트 발행 (모든 후처리는 리스너가 담당)
        event(new FileUploaded($fileModel));

        return $fileModel;
    }
}

// Event Listeners
class GenerateThumbnail implements ShouldQueue
{
    public function handle(FileUploaded $event): void
    {
        if (! $this->isImage($event->file)) {
            return; // 이미지가 아니면 스킵
        }

        $thumbnail = $this->generateThumbnail($event->file);
        $event->file->update(['thumbnail_path' => $thumbnail]);

        event(new ThumbnailGenerated($event->file));
    }
}

class ScanFileForVirus implements ShouldQueue
{
    public function handle(FileUploaded $event): void
    {
        $result = $this->virusScanner->scan($event->file->path);

        if ($result->infected) {
            $event->file->delete(); // 감염된 파일 삭제
            event(new VirusDetected($event->file));
        } else {
            event(new VirusScanPassed($event->file));
        }
    }
}
```

---

### 구현 상태

> **현재 상태:** Event-Driven 아키텍처 설계 완료, 구현 예정

| 구성 요소 | 상태 | 위치 | 설명 |
|----------|------|------|------|
| Domain Events | ⏳ 예정 | `app/Shared/Events/` | 도메인 이벤트 베이스 클래스 |
| Event Listeners | ⏳ 예정 | `app/Domain/*/Listeners/` | 도메인별 이벤트 리스너 |
| Queue Jobs | ✅ 일부 구현 | `app/Jobs/` | 비동기 Queue Job (알림 발송) |
| Event Service Provider | ⏳ 예정 | `app/Providers/EventServiceProvider.php` | 이벤트 리스너 등록 |

**다음 구현 단계:**
1. Domain Event 베이스 클래스 구현 (`app/Shared/Events/DomainEvent.php`)
2. `HasDomainEvents` Trait 구현 (Model에서 이벤트 발행)
3. 도메인별 이벤트 클래스 정의 (UserCreated, FileUploaded 등)
4. 이벤트 리스너 구현 (동기/비동기)
5. EventServiceProvider에 이벤트-리스너 매핑 등록

**참고 문서:**
- [CORE.md](./CORE.md) - Core Service 아키텍처
- [CLAUDE.md](../CLAUDE.md) - Domain Event 구현 계획

---

## 도메인 서비스

Core Service는 다음 도메인 서비스를 제공합니다:

| 도메인 | 경로 | 문서 | 설명 |
|--------|------|------|------|
| **Auth** | `/internal/auth/*` | [AUTH.md](./AUTH.md) | JWT 인증, 소셜 로그인 |
| **Timer** | `/internal/timers/*` | [TIMER.md](./TIMER.md) | 목표 시점 타이머 관리 |
| **File** | `/internal/files/*` | [FILE.md](./FILE.md) | MinIO 파일 저장소 |
| **Notification** | `/internal/notifications/*` | [NOTIFICATION.md](./NOTIFICATION.md) | 다채널 알림 발송 |
| **User Activity** | `/internal/user-activity/*` | [USER_ACTIVITY.md](./USER_ACTIVITY.md) | MongoDB 활동 로그 |
| **Health** | `/internal/health/*` | [HEALTH.md](./HEALTH.md) | 인프라 Health Check |

---

## 공통 컴포넌트

### HTTP Response 표준 포맷

모든 API 응답은 `ApiResponse` 클래스를 통해 통일된 포맷으로 반환됩니다.

**성공 응답:**
```json
{
    "success": true,
    "data": { ... },
    "message": "optional message"
}
```

**에러 응답:**
```json
{
    "success": false,
    "error": {
        "code": "ERROR_CODE",
        "message": "에러 메시지",
        "details": { ... }
    }
}
```

**위치:** `app/Shared/Http/ApiResponse.php`

---

### Exception Handling

도메인 예외가 자동으로 API 응답으로 변환됩니다.

**예외 클래스:**
- `NotFoundException` → 404
- `BadRequestException` → 400
- `UnauthorizedException` → 401
- `ForbiddenException` → 403
- `ConflictException` → 409
- `DomainValidationException` → 400

**위치:** `app/Shared/Exceptions/`

---

## 인프라 구성

### 데이터베이스

| 종류 | 용도 | 사용 도메인 |
|------|------|-------------|
| **PostgreSQL** | 관계형 데이터 저장 | Auth, Timer, File, Notification |
| **Redis** | 캐싱, 세션, Refresh Token | 모든 도메인 |
| **MongoDB** | 로그 저장 (Schema-less) | User Activity, Auth Events |
| **MinIO** | 파일 저장소 (S3 호환) | File |

### 환경 변수

**Backend (.env):**
```env
# Database
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_DATABASE=toy_core_system

# Cache
REDIS_HOST=redis
REDIS_PORT=6379

# MongoDB
MONGODB_URI=mongodb://mongodb:27017
MONGODB_DATABASE=toy_logs

# MinIO
MINIO_ENDPOINT=http://minio:9000
MINIO_KEY=minioadmin
MINIO_SECRET=minioadmin

# Frontend URL
FRONTEND_URL=http://localhost:3002

# JWT
JWT_SECRET=your-secret-key
JWT_ACCESS_TTL=3600          # 1시간
JWT_REFRESH_TTL=604800       # 7일
```

**Frontend (.env):**
```env
# Backend API URL
NEXT_PUBLIC_API_URL=http://localhost:8000/api
```

---

## 개발 워크플로우

### 새 기능 추가 시

1. **Core Service에 도메인 로직 구현**
   ```bash
   app/Domain/{DomainName}/
   ├── Controllers/
   ├── Services/
   ├── DTOs/
   ├── Repositories/
   └── Exceptions/
   ```

2. **Internal API 라우트 추가** (`routes/internal.php`)
   ```php
   Route::prefix('new-domain')->group(function () {
       Route::get('/', [NewController::class, 'index']);
   });
   ```

3. **BFF에서 Core Service 호출** (필요시)
   ```php
   // app/Bff/Services/CoreNewService.php
   public function getData(): array
   {
       return Http::get('/internal/new-domain')->json();
   }
   ```

4. **BFF API 라우트 추가** (`routes/api.php`)
   ```php
   Route::get('new-feature', [BffController::class, 'index'])
       ->middleware('bff.auth');
   ```

5. **Frontend에서 API 호출**
   ```typescript
   // lib/api/client.ts
   export const newApi = {
       getData: async () => {
           return fetch(`${API_URL}/new-feature`, {
               credentials: 'include',
           });
       },
   };
   ```

6. **도메인 문서 작성** (`docs/{DOMAIN}.md`)

---

## 배포 아키텍처

```
                    ┌─────────────┐
                    │   Cloudflare │
                    │     (CDN)     │
                    └──────┬────────┘
                           │
              ┌────────────┴────────────┐
              │                         │
    ┌─────────▼─────────┐   ┌──────────▼──────────┐
    │  Frontend (Next.js) │   │   Backend (Laravel)  │
    │   Static/SSR        │   │    BFF + Core       │
    │   Port: 3002        │   │    Port: 8000       │
    └─────────────────────┘   └──────────┬──────────┘
                                          │
                    ┌─────────────────────┴─────────────────────┐
                    │                                           │
        ┌───────────▼──────────┐              ┌────────────────▼────────────┐
        │   PostgreSQL          │              │   Redis                     │
        │   (Primary DB)        │              │   (Cache, Session, Queue)   │
        └───────────────────────┘              └─────────────────────────────┘
                    │                                           │
        ┌───────────▼──────────┐              ┌────────────────▼────────────┐
        │   MongoDB             │              │   MinIO                     │
        │   (Activity Logs)     │              │   (Object Storage)          │
        └───────────────────────┘              └─────────────────────────────┘
```

---

## 보안 고려사항

### 인증/인가

| 레이어 | 방법 | 설명 |
|--------|------|------|
| Frontend → BFF | JWT (HttpOnly Cookie) | XSS 방지를 위한 HttpOnly 설정 |
| BFF → Core | `X-User-Id` 헤더 | JWT 검증 후 사용자 ID 전달 |

### CORS

```php
// config/cors.php
'allowed_origins' => [
    env('FRONTEND_URL', 'http://localhost:3002'),
],
'supports_credentials' => true, // Cookie 전송 허용
```

### Rate Limiting

| 엔드포인트 | 제한 |
|-----------|------|
| OAuth Callback | 10회/분 |
| Token Refresh | 30회/분 |
| 일반 API | 60회/분 |

### 환경별 설정

- **로컬:** `SESSION_SECURE_COOKIE=false`, `SESSION_DOMAIN=null`
- **프로덕션:** `SESSION_SECURE_COOKIE=true`, `SESSION_DOMAIN=.example.com`

---

## 테스트 전략

### Unit Tests

- **위치:** `tests/Unit/`
- **대상:** Service, Repository, DTO, Helper
- **실행:** `php artisan test --testsuite=Unit`

### Feature Tests

- **위치:** `tests/Feature/`
- **대상:** API 엔드포인트, 통합 흐름
- **실행:** `php artisan test --testsuite=Feature`

### E2E Tests (Frontend)

- **도구:** Playwright (예정)
- **대상:** 사용자 시나리오 (로그인, CRUD 등)

---

## 모니터링

### 에러 추적

| 레이어 | 도구 | 설정 |
|--------|------|------|
| Frontend | Sentry | `NEXT_PUBLIC_SENTRY_DSN` |
| Backend | Sentry | `SENTRY_LARAVEL_DSN` |

### 로그

| 레이어 | 저장소 | 포맷 |
|--------|--------|------|
| Frontend | Browser Console | JSON |
| Backend | Laravel Logs | `storage/logs/laravel.log` |
| Activity | MongoDB | JSON Document |

---

## 참고 문서

### 레이어별 문서

- [Frontend 문서](./FRONTEND.md)
- [BFF 문서](./BFF.md)
- [Core Service 문서](./CORE.md)

### 도메인별 문서

- [Auth 도메인](./AUTH.md)
- [Timer 도메인](./TIMER.md)
- [File 도메인](./FILE.md)
- [Notification 도메인](./NOTIFICATION.md)
- [User Activity 도메인](./USER_ACTIVITY.md)
- [Health Check](./HEALTH.md)

### 프로젝트 문서

- [CLAUDE.md](../CLAUDE.md) - 프로젝트 개요 및 가이드

---

## 버전

- **아키텍처 버전:** v1.0.0
- **작성일:** 2025-12-15
- **최종 수정일:** 2025-12-15
