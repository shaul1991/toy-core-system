# Core Service Architecture

Core Service Layer의 상세 아키텍처 문서입니다.

## 개요

**Core Service**는 비즈니스 로직과 도메인 규칙을 제공하는 마이크로서비스의 핵심 레이어입니다. DDD(Domain-Driven Design) 원칙을 따르며, 재사용 가능한 도메인 서비스를 제공합니다.

| 항목 | 설명 |
|------|------|
| **기술 스택** | Laravel 12.x, PHP 8.4+ |
| **위치** | `app/Domain/`, `app/Http/`, `app/Services/`, `app/Repositories/` |
| **경로** | `/internal/*` (내부 전용 API) |
| **호출자** | BFF Layer |
| **인증** | `X-User-Id` 헤더 (BFF에서 전달) |
| **응답 포맷** | JSON (ApiResponse 표준) |

---

## 아키텍처

### Layered Architecture

```
┌─────────────────────────────────────────────────────┐
│                  Presentation Layer                  │
│              (Controllers, HTTP Layer)               │
│          routes/internal.php → Controllers/          │
└────────────────────┬────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────┐
│                   Service Layer                      │
│              (Business Logic, Domain Rules)          │
│                    app/Services/                     │
│                    app/Domain/*/Services/            │
└────────────────────┬────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────┐
│                  Repository Layer                    │
│              (Data Access Abstraction)               │
│                  app/Repositories/                   │
│              app/Domain/*/Repositories/              │
└────────────────────┬────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────┐
│                   Data Layer                         │
│             (Models, Database, Cache)                │
│         app/Models/ → PostgreSQL, Redis, MongoDB     │
└─────────────────────────────────────────────────────┘
```

---

## 디렉토리 구조

### Domain-Driven Design 구조

```
app/
├── Domain/                           # 도메인 레이어 (DDD)
│   ├── Auth/                         # 인증 도메인
│   │   ├── Controllers/              # HTTP 컨트롤러
│   │   │   ├── AuthController.php
│   │   │   └── SocialAuthController.php
│   │   ├── Services/                 # 도메인 서비스
│   │   │   ├── JwtService.php
│   │   │   ├── SocialAuthService.php
│   │   │   ├── UserCacheService.php
│   │   │   └── AuthEventService.php
│   │   ├── DTOs/                     # Data Transfer Objects
│   │   │   ├── TokenDTO.php
│   │   │   ├── SocialUserDTO.php
│   │   │   └── AuthEventDTO.php
│   │   ├── Repositories/             # Repository 인터페이스 및 구현
│   │   │   ├── RefreshTokenRepositoryInterface.php
│   │   │   ├── RedisRefreshTokenRepository.php
│   │   │   ├── AuthEventRepositoryInterface.php
│   │   │   └── MongoAuthEventRepository.php
│   │   ├── Exceptions/               # 도메인 예외
│   │   │   ├── TokenException.php
│   │   │   └── SocialAuthException.php
│   │   └── Observers/                # Model Observers
│   │       └── UserObserver.php
│   │
│   ├── Health/                       # Health Check 도메인
│   │   ├── Controllers/
│   │   ├── Services/
│   │   ├── Checkers/                 # Health Checker 구현체
│   │   ├── DTOs/
│   │   └── Contracts/
│   │
│   └── UserActivity/                 # 사용자 활동 로그 도메인
│       ├── Controllers/
│       ├── Services/
│       ├── Repositories/
│       ├── DTOs/
│       └── Contracts/
│
├── Http/                             # HTTP 레이어
│   ├── Controllers/                  # 일반 컨트롤러
│   │   ├── TimerController.php
│   │   ├── FileController.php
│   │   └── NotificationController.php
│   ├── Middleware/                   # 미들웨어
│   │   └── ExtractUserId.php         # X-User-Id 헤더 추출
│   └── Requests/                     # Form Requests
│       └── Notification/
│           └── StoreNotificationRequest.php
│
├── Services/                         # 비즈니스 서비스
│   ├── TimerService.php
│   ├── FileService.php
│   └── Notification/                 # 알림 서비스
│       ├── NotificationService.php
│       ├── NotificationDispatcher.php
│       ├── BatchAggregator.php
│       └── Channels/
│           ├── NotificationChannelInterface.php
│           ├── EmailChannel.php
│           ├── SmsChannel.php
│           └── SlackChannel.php
│
├── Repositories/                     # Repository 구현체
│   ├── TimerRepositoryInterface.php
│   ├── EloquentTimerRepository.php
│   ├── CachedTimerRepository.php     # Decorator 패턴 (캐싱)
│   ├── FileRepositoryInterface.php
│   ├── MinioFileRepository.php
│   ├── CachedFileRepository.php
│   ├── NotificationQueueRepositoryInterface.php
│   ├── EloquentNotificationQueueRepository.php
│   ├── NotificationLogRepositoryInterface.php
│   └── EloquentNotificationLogRepository.php
│
├── Models/                           # Eloquent Models
│   ├── User.php
│   ├── SocialAccount.php
│   ├── Timer.php
│   ├── File.php
│   ├── NotificationQueue.php
│   └── NotificationLog.php
│
├── Jobs/                             # Queue Jobs
│   ├── DispatchNotificationJob.php
│   └── ProcessBatchedNotificationsJob.php
│
├── Console/Commands/                 # Artisan Commands
│   ├── FileStorageStats.php
│   ├── CleanupOrphanFiles.php
│   ├── ProcessBatchedNotifications.php
│   └── ProcessScheduledNotifications.php
│
├── Enums/                            # Enum 클래스
│   └── Notification/
│       ├── ChannelType.php
│       ├── ChannelResultStatus.php
│       ├── DispatchType.php
│       └── NotificationStatus.php
│
├── Providers/                        # Service Providers
│   ├── AppServiceProvider.php
│   ├── HealthServiceProvider.php
│   ├── UserActivityServiceProvider.php
│   └── SwaggerUiServiceProvider.php
│
└── Shared/                           # 공유 컴포넌트
    ├── Exceptions/                   # 도메인 예외 클래스
    │   ├── DomainException.php       # 베이스 예외
    │   ├── NotFoundException.php
    │   ├── BadRequestException.php
    │   ├── UnauthorizedException.php
    │   ├── ForbiddenException.php
    │   ├── ConflictException.php
    │   ├── DomainValidationException.php
    │   ├── BusinessException.php
    │   ├── ServiceUnavailableException.php
    │   └── Handler.php               # Exception Handler
    │
    ├── Http/                         # HTTP 공통 모듈
    │   ├── ApiResponse.php           # 응답 빌더
    │   ├── ApiResponseCode.php       # 에러 코드 Enum
    │   ├── Pagination/
    │   │   ├── OffsetPagination.php
    │   │   └── CursorPagination.php
    │   └── Traits/
    │       ├── ApiResponsable.php    # Controller Trait
    │       └── HasAuthenticatedUserId.php
    │
    └── Database/
        └── MongoDB/
            └── MongoConnection.php   # MongoDB 연결 관리
```

---

## 도메인 서비스

Core Service는 다음 도메인 서비스를 제공합니다:

### 1. Auth (인증/인가)

| 항목 | 설명 |
|------|------|
| **경로** | `/internal/auth/*` |
| **문서** | [AUTH.md](./AUTH.md) |
| **주요 기능** | JWT 발급/검증/갱신, 소셜 로그인, 사용자 관리 |
| **데이터베이스** | PostgreSQL (users, social_accounts), Redis (refresh_tokens), MongoDB (auth_events) |

**엔드포인트:**
- `GET /internal/auth/{provider}/redirect` - OAuth URL 반환
- `GET /internal/auth/{provider}/callback` - OAuth 콜백 처리
- `POST /internal/auth/refresh` - 토큰 갱신
- `POST /internal/auth/validate` - 토큰 검증
- `GET /internal/auth/me` - 사용자 정보 조회
- `POST /internal/auth/logout` - 로그아웃
- `POST /internal/auth/logout-all` - 전체 로그아웃
- `GET /internal/auth/social-accounts` - 소셜 계정 목록
- `POST /internal/auth/{provider}/link` - 소셜 계정 연동
- `DELETE /internal/auth/{provider}/unlink` - 소셜 계정 연동 해제

---

### 2. Timer (타이머 관리)

| 항목 | 설명 |
|------|------|
| **경로** | `/internal/timers/*` |
| **문서** | [TIMER.md](./TIMER.md) |
| **주요 기능** | 목표 시점까지의 남은 시간 계산 및 관리 |
| **데이터베이스** | PostgreSQL (timers), Redis (cache) |

**엔드포인트:**
- `GET /internal/timers/{key}` - 타이머 조회
- `PUT /internal/timers/{key}` - 타이머 생성/수정
- `DELETE /internal/timers/{key}` - 타이머 삭제

**캐싱 전략:**
- 조회 시 Redis 캐시 우선 확인 (TTL: 60초)
- 쓰기 시 캐시 무효화 (Cache-Aside 패턴)

---

### 3. File (파일 저장소)

| 항목 | 설명 |
|------|------|
| **경로** | `/internal/files/*` |
| **문서** | [FILE.md](./FILE.md) |
| **주요 기능** | MinIO 기반 파일 업로드/다운로드/삭제 |
| **데이터베이스** | PostgreSQL (files), MinIO (object storage), Redis (cache) |

**엔드포인트:**
- `GET /internal/files` - 파일 목록 조회
- `POST /internal/files` - 파일 업로드
- `GET /internal/files/{id}` - 파일 메타데이터 조회
- `GET /internal/files/{id}/download` - 파일 다운로드
- `DELETE /internal/files/{id}` - 파일 Soft Delete
- `DELETE /internal/files/{id}/force` - 파일 완전 삭제
- `PATCH /internal/files/{id}/visibility` - 파일 공개 범위 변경
- `POST /internal/files/{id}/temporary-url` - 임시 다운로드 URL 생성

**캐싱 전략:**
- 메타데이터 캐싱 (TTL: 300초)
- 임시 URL 캐싱 (TTL: URL 만료 시간)

---

### 4. Notification (알림 발송)

| 항목 | 설명 |
|------|------|
| **경로** | `/internal/notifications/*` |
| **문서** | [NOTIFICATION.md](./NOTIFICATION.md) |
| **주요 기능** | Email, SMS, Slack 다채널 알림 발송 |
| **데이터베이스** | PostgreSQL (notification_queue, notification_logs) |

**엔드포인트:**
- `GET /internal/notifications/channels` - 사용 가능한 채널 목록
- `POST /internal/notifications` - 알림 대기열 등록
- `GET /internal/notifications/queue` - 대기열 조회
- `GET /internal/notifications/queue/{id}` - 대기열 상세
- `DELETE /internal/notifications/queue/{id}` - 알림 취소
- `POST /internal/notifications/queue/{id}/retry` - 재발송
- `GET /internal/notifications/logs` - 발송 로그 조회
- `GET /internal/notifications/logs/{id}` - 로그 상세

**발송 유형:**
- **즉시 발송 (immediate):** Queue Job으로 즉시 처리
- **예약 발송 (scheduled):** 특정 시각에 발송
- **배치 발송 (batched):** 같은 사용자의 알림을 묶어서 발송

---

### 5. User Activity (사용자 활동 로그)

| 항목 | 설명 |
|------|------|
| **경로** | `/internal/user-activity/*` |
| **문서** | [USER_ACTIVITY.md](./USER_ACTIVITY.md) |
| **주요 기능** | 사용자 활동 로그 저장 및 조회 |
| **데이터베이스** | MongoDB (user_activities) |

**엔드포인트:**
- `GET /internal/user-activity` - 활동 로그 조회 (필터링, 페이지네이션)
- `POST /internal/user-activity` - 활동 로그 생성
- `GET /internal/user-activity/{id}` - 로그 상세 조회
- `PUT /internal/user-activity/{id}` - 로그 수정
- `DELETE /internal/user-activity/{id}` - 로그 삭제
- `GET /internal/user-activity/user/{userId}` - 특정 사용자 로그
- `GET /internal/user-activity/user/{userId}/stats` - 사용자 통계
- `DELETE /internal/user-activity/user/{userId}` - 사용자 로그 전체 삭제

**MongoDB 사용 이유:**
- Schema-less로 유연한 데이터 저장
- 대용량 로그 데이터 처리 최적화
- 빠른 쓰기 성능

---

### 6. Health Check (서비스 상태 확인)

| 항목 | 설명 |
|------|------|
| **경로** | `/internal/health/*` |
| **문서** | [HEALTH.md](./HEALTH.md) |
| **주요 기능** | 인프라 연결 상태 확인 (PostgreSQL, Redis, MongoDB, MinIO) |
| **데이터베이스** | 각 서비스에 연결 테스트 |

**엔드포인트:**
- `GET /internal/health` - 전체 서비스 상태
- `GET /internal/health/{service}` - 특정 서비스 상태

**Health Checkers:**
- `PostgresHealthChecker` - DB 연결 확인
- `RedisHealthChecker` - Redis 연결 확인
- `MongoDbHealthChecker` - MongoDB 연결 확인
- `MinioHealthChecker` - MinIO 연결 확인

---

## API 인증 방식

### X-User-Id 헤더

BFF에서 JWT 검증 후 사용자 ID를 `X-User-Id` 헤더로 전달합니다.

**예시:**
```http
GET /internal/auth/me HTTP/1.1
Host: localhost:8000
X-User-Id: 123
```

**미들웨어:**
```php
// app/Http/Middleware/ExtractUserId.php
Route::middleware('user.id')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
});
```

**Controller에서 사용:**
```php
use App\Shared\Http\Traits\HasAuthenticatedUserId;

class AuthController extends Controller
{
    use HasAuthenticatedUserId;

    public function me(Request $request): JsonResponse
    {
        $userId = $this->getUserId($request); // X-User-Id에서 추출
        $user = User::find($userId);
        return $this->successResponse($user);
    }
}
```

---

## 공통 컴포넌트

### 1. HTTP Response (ApiResponse)

모든 API 응답은 `ApiResponse`를 통해 통일된 포맷으로 반환됩니다.

**위치:** `app/Shared/Http/ApiResponse.php`

**사용법:**
```php
use App\Shared\Http\Traits\ApiResponsable;

class TimerController extends Controller
{
    use ApiResponsable;

    public function show(string $key): JsonResponse
    {
        $timer = $this->timerService->getByKey($key);
        return $this->successResponse($timer);
    }

    public function store(Request $request): JsonResponse
    {
        $timer = $this->timerService->create($request->all());
        return $this->createdResponse($timer);
    }
}
```

**메서드:**
- `successResponse($data, $message = null)` - 200 응답
- `createdResponse($data, $message = null)` - 201 응답
- `errorResponse($code, $message, $details = null)` - 에러 응답
- `notFoundResponse($message)` - 404 응답
- `paginatedResponse($paginator)` - 페이지네이션 응답

**응답 포맷:**
```json
{
    "success": true,
    "data": { ... }
}
```

---

### 2. Exception Handling

도메인 예외를 자동으로 API 응답으로 변환합니다.

**위치:** `app/Shared/Exceptions/`

**예외 클래스:**
| 클래스 | HTTP Status | 용도 |
|--------|-------------|------|
| `NotFoundException` | 404 | 리소스를 찾을 수 없음 |
| `BadRequestException` | 400 | 잘못된 요청 |
| `UnauthorizedException` | 401 | 인증 필요 |
| `ForbiddenException` | 403 | 권한 없음 |
| `ConflictException` | 409 | 리소스 충돌 (중복 등) |
| `DomainValidationException` | 400 | 도메인 규칙 검증 실패 |
| `BusinessException` | 400 | 일반 비즈니스 로직 예외 |
| `ServiceUnavailableException` | 503 | 서비스 이용 불가 |

**사용법:**
```php
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ConflictException;

public function show(int $id): JsonResponse
{
    $timer = Timer::find($id);

    if (!$timer) {
        throw NotFoundException::forResource('Timer', $id);
        // 자동으로 {"success": false, "error": {...}} 응답 반환
    }

    return $this->successResponse($timer);
}

public function create(array $data): Timer
{
    if (Timer::where('key', $data['key'])->exists()) {
        throw ConflictException::duplicateField('key', $data['key']);
    }

    return Timer::create($data);
}
```

**팩토리 메서드:**
```php
// NotFoundException
NotFoundException::forResource('Timer', 123);
NotFoundException::forCriteria('Timer', ['key' => 'my-timer']);

// ConflictException
ConflictException::duplicateField('email', 'user@example.com');
ConflictException::resourceExists('Timer', 'my-timer');

// DomainValidationException
DomainValidationException::forField('target_time', '목표 시간은 미래여야 합니다.');
DomainValidationException::withErrors([
    'email' => '이메일 형식이 올바르지 않습니다.',
    'name' => '이름은 필수입니다.',
]);
```

---

### 3. Repository Pattern

데이터 접근 로직을 추상화하여 테스트 가능성과 유지보수성을 향상시킵니다.

**인터페이스:**
```php
// app/Repositories/TimerRepositoryInterface.php
interface TimerRepositoryInterface
{
    public function findByKey(string $key): ?Timer;
    public function create(array $data): Timer;
    public function update(Timer $timer, array $data): Timer;
    public function delete(Timer $timer): bool;
}
```

**구현체:**
```php
// app/Repositories/EloquentTimerRepository.php
class EloquentTimerRepository implements TimerRepositoryInterface
{
    public function findByKey(string $key): ?Timer
    {
        return Timer::where('key', $key)->first();
    }

    public function create(array $data): Timer
    {
        return Timer::create($data);
    }
}
```

**캐싱 Decorator:**
```php
// app/Repositories/CachedTimerRepository.php
class CachedTimerRepository implements TimerRepositoryInterface
{
    public function __construct(
        private TimerRepositoryInterface $repository,
        private Cache $cache
    ) {}

    public function findByKey(string $key): ?Timer
    {
        return $this->cache->remember("timer:{$key}", 60, function () use ($key) {
            return $this->repository->findByKey($key);
        });
    }

    public function create(array $data): Timer
    {
        $timer = $this->repository->create($data);
        $this->cache->forget("timer:{$timer->key}");
        return $timer;
    }
}
```

**Service Provider 바인딩:**
```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->app->singleton(TimerRepositoryInterface::class, function ($app) {
        $eloquent = new EloquentTimerRepository();
        return new CachedTimerRepository($eloquent, $app->make(Cache::class));
    });
}
```

---

### 4. DTO (Data Transfer Object)

레이어 간 데이터 전송을 위한 불변 객체입니다.

**예시:**
```php
// app/Domain/Auth/DTOs/TokenDTO.php
final readonly class TokenDTO
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public string $tokenType,
        public int $expiresIn,
    ) {}

    public static function fromTokenPair(string $accessToken, string $refreshToken): self
    {
        return new self(
            accessToken: $accessToken,
            refreshToken: $refreshToken,
            tokenType: 'bearer',
            expiresIn: 3600,
        );
    }

    public function toArray(): array
    {
        return [
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'token_type' => $this->tokenType,
            'expires_in' => $this->expiresIn,
        ];
    }
}
```

---

## 캐싱 전략

### Redis 캐싱

| 도메인 | 캐시 키 패턴 | TTL | 무효화 시점 |
|--------|-------------|-----|------------|
| **Timer** | `timer:{key}` | 60초 | 생성/수정/삭제 |
| **File** | `file:{id}` | 300초 | 수정/삭제 |
| **User** | `user:{id}` | 300초 | 수정 |
| **Refresh Token** | `refresh_token:{token}` | 7일 | 로그아웃 |

### Cache-Aside 패턴

```php
public function getByKey(string $key): ?Timer
{
    // 1. 캐시 확인
    $cached = Cache::get("timer:{$key}");
    if ($cached) {
        return $cached;
    }

    // 2. DB 조회
    $timer = Timer::where('key', $key)->first();

    // 3. 캐시 저장
    if ($timer) {
        Cache::put("timer:{$key}", $timer, 60);
    }

    return $timer;
}
```

---

## 데이터베이스

### PostgreSQL (Primary DB)

**테이블 목록:**
| 테이블 | 용도 | 관련 도메인 |
|--------|------|-------------|
| `users` | 사용자 정보 | Auth |
| `social_accounts` | 소셜 계정 연동 | Auth |
| `timers` | 타이머 데이터 | Timer |
| `files` | 파일 메타데이터 | File |
| `notification_queue` | 알림 대기열 | Notification |
| `notification_logs` | 알림 발송 로그 | Notification |

**Migration 위치:** `database/migrations/`

---

### Redis (Cache & Session)

**용도:**
- Query Result Cache
- Session Storage
- Refresh Token Storage
- Queue Backend

**키 네이밍 규칙:**
- `{domain}:{identifier}` (예: `user:123`, `timer:my-timer`)

---

### MongoDB (Logs)

**컬렉션:**
| 컬렉션 | 용도 | 관련 도메인 |
|--------|------|-------------|
| `user_activities` | 사용자 활동 로그 | User Activity |
| `auth_events` | 인증 이벤트 로그 | Auth |

**연결 관리:** `app/Shared/Database/MongoDB/MongoConnection.php`

---

### MinIO (Object Storage)

**버킷 구조:**
| 버킷 | 용도 | 관련 도메인 |
|------|------|-------------|
| `uploads` | 사용자 업로드 파일 | File |

**접근 제어:**
- Public: 공개 파일
- Private: 인증 필요
- Temporary URL: 시간 제한 접근

---

## Queue & Jobs

### Queue 구성

| Queue | 용도 | Worker 수 |
|-------|------|----------|
| `default` | 일반 작업 | 2 |
| `notifications` | 알림 발송 | 3 |
| `high` | 우선순위 높음 | 2 |

### Job 클래스

**위치:** `app/Jobs/`

| Job | 용도 | Queue |
|-----|------|-------|
| `DispatchNotificationJob` | 알림 즉시 발송 | `notifications` |
| `ProcessBatchedNotificationsJob` | 배치 알림 발송 | `notifications` |

**실행:**
```bash
php artisan queue:work --queue=high,notifications,default
```

---

## Artisan Commands

**위치:** `app/Console/Commands/`

| Command | 용도 | 스케줄 |
|---------|------|--------|
| `notifications:process-batched` | 배치 알림 처리 | 매 5분 |
| `notifications:process-scheduled` | 예약 알림 처리 | 매 1분 |
| `files:storage-stats` | 파일 저장소 통계 | 수동 |
| `files:cleanup-orphan` | 고아 파일 정리 | 매일 03:00 |

**스케줄 설정:** `app/Console/Kernel.php`

---

## 테스트

### Unit Tests

**위치:** `tests/Unit/`

**대상:**
- Service 로직
- Repository 메서드
- DTO 변환
- Helper 함수

**실행:**
```bash
php artisan test --testsuite=Unit
```

### Feature Tests

**위치:** `tests/Feature/`

**대상:**
- API 엔드포인트
- 통합 흐름
- 데이터베이스 작업

**실행:**
```bash
php artisan test --testsuite=Feature
```

### 테스트 커버리지

| 도메인 | Unit Tests | Feature Tests | 문서 |
|--------|-----------|---------------|------|
| Auth | 15개 | 20개 | [AUTH.md](./AUTH.md) |
| Timer | 8개 | 5개 | [TIMER.md](./TIMER.md) |
| File | 12개 | 10개 | [FILE.md](./FILE.md) |
| Notification | 10개 | 8개 | [NOTIFICATION.md](./NOTIFICATION.md) |
| User Activity | 6개 | 8개 | [USER_ACTIVITY.md](./USER_ACTIVITY.md) |
| Health | 4개 | 4개 | [HEALTH.md](./HEALTH.md) |
| **총합** | **55개** | **55개** | |

---

## 환경 설정

### .env 파일

```env
# Application
APP_NAME="Toy Core System"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

# Frontend URL (CORS, Cookie Domain)
FRONTEND_URL=http://localhost:3002

# Database
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=toy_core_system
DB_USERNAME=postgres
DB_PASSWORD=postgres

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# MongoDB
MONGODB_URI=mongodb://mongodb:27017
MONGODB_DATABASE=toy_logs

# MinIO (S3-compatible)
MINIO_ENDPOINT=http://minio:9000
MINIO_KEY=minioadmin
MINIO_SECRET=minioadmin
MINIO_REGION=us-east-1
MINIO_BUCKET=uploads

# JWT
JWT_SECRET=your-jwt-secret-key
JWT_ACCESS_TTL=3600          # 1시간 (초)
JWT_REFRESH_TTL=604800       # 7일 (초)

# OAuth Providers
GITHUB_CLIENT_ID=your-github-client-id
GITHUB_CLIENT_SECRET=your-github-client-secret
GITHUB_REDIRECT_URI=${APP_URL}/api/auth/github/callback

NAVER_CLIENT_ID=your-naver-client-id
NAVER_CLIENT_SECRET=your-naver-client-secret
NAVER_REDIRECT_URI=${APP_URL}/api/auth/naver/callback

KAKAO_CLIENT_ID=your-kakao-client-id
KAKAO_CLIENT_SECRET=your-kakao-client-secret
KAKAO_REDIRECT_URI=${APP_URL}/api/auth/kakao/callback

# Notification Channels
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025

SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL

# Sentry (Error Tracking)
SENTRY_LARAVEL_DSN=https://your-sentry-dsn

# Swagger UI
SWAGGER_UI_ENABLED=true
```

---

## API 문서 (Swagger)

### 접근

| 환경 | URL |
|------|-----|
| 로컬 | http://localhost:8000/swagger |
| 개발 서버 | https://dev-core.shaul.link/swagger |

### OpenAPI 스펙

**위치:** `resources/swagger/openapi.json`

**포함된 API:**
- Auth (BFF + Core)
- Timer
- File
- Notification
- User Activity
- Health

---

## 배포

### Docker Compose

```yaml
services:
  app:
    build: .
    ports:
      - "8000:8000"
    environment:
      - DB_HOST=postgres
      - REDIS_HOST=redis
    depends_on:
      - postgres
      - redis
      - mongodb
      - minio

  postgres:
    image: postgres:18
    ports:
      - "5432:5432"

  redis:
    image: redis:8
    ports:
      - "6379:6379"

  mongodb:
    image: mongo:8
    ports:
      - "27017:27017"

  minio:
    image: minio/minio
    ports:
      - "9000:9000"
      - "9001:9001"
```

---

## 관련 문서

### 아키텍처 문서

- [전체 아키텍처](./ARCHITECTURE.md)
- [BFF 레이어](./BFF.md)
- [Frontend 레이어](./FRONTEND.md)

### 도메인 문서

- [Auth 도메인](./AUTH.md)
- [Timer 도메인](./TIMER.md)
- [File 도메인](./FILE.md)
- [Notification 도메인](./NOTIFICATION.md)
- [User Activity 도메인](./USER_ACTIVITY.md)
- [Health Check](./HEALTH.md)

### 프로젝트 문서

- [CLAUDE.md](../CLAUDE.md) - 프로젝트 개요

---

## 버전

- **Core Service 버전:** v1.0.0
- **작성일:** 2025-12-15
- **최종 수정일:** 2025-12-15
