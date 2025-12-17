# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Toy Domain Service** - 도메인 핵심 로직을 제공하는 Domain Service API입니다.

> "Toy"는 장난감이 아닌, 쉽게 가지고 놀 수 있는 Side Project라는 의미입니다.

마이크로서비스 아키텍처에서 **도메인 핵심 기능**만을 담당하는 Primitive Service로, 상위 서비스(Business Service)에서 조합하여 사용합니다.

## Tech Stack

| Category | Technology |
|----------|------------|
| Framework | Laravel 12.x |
| Language | PHP 8.4+ |
| Database | PostgreSQL 18 |
| Cache | Redis 8 |
| NoSQL | MongoDB 8 |
| Object Storage | MinIO |
| Frontend | Tailwind CSS 4.x (Vite) |
| Testing | PHPUnit 11.x |
| Monitoring | Sentry |
| API Docs | Swagger UI (OpenAPI 3.0) |

## API Documentation (Swagger)

프로젝트의 모든 API는 OpenAPI 3.0 스펙으로 문서화되어 있습니다.

```bash
# Swagger UI 접속 (로컬)
http://localhost:8000/swagger

# Swagger UI 활성화 (.env)
SWAGGER_UI_ENABLED=true
```

**관련 파일:**
- `resources/swagger/openapi.json` - OpenAPI 스펙 파일
- `config/swagger-ui.php` - Swagger UI 설정

## Commands

### Development

```bash
# 전체 개발 서버 실행 (Laravel 서버, Queue, Pail 로그, Vite 동시 실행)
composer dev

# 프로젝트 초기 설정
composer setup

# Filament Admin Panel 에셋 생성 (처음 설치 시 또는 Filament 업데이트 후 필수)
php artisan filament:assets
```

> **⚠️ 중요**: Filament 에셋 파일은 버전 관리에서 제외되어 있습니다.
> 프로젝트를 처음 클론한 후, 또는 Filament 패키지 업데이트 후에는 반드시 `php artisan filament:assets` 명령을 실행하세요.

### Testing

```bash
# 전체 테스트 실행
composer test

# 단일 테스트 파일 실행
php artisan test tests/Feature/ExampleTest.php

# 특정 테스트 메서드 실행
php artisan test --filter=test_method_name
```

### Code Style

```bash
# Laravel Pint로 코드 스타일 검사 및 수정
./vendor/bin/pint
```

### Build

```bash
# 프론트엔드 에셋 빌드 (프로덕션)
npm run build

# 프론트엔드 개발 서버
npm run dev
```

## Architecture

### Service Layer Structure

```
Front → BFF → Business → Domain
```

- **Domain Service**: 원자적 도메인 로직, 재사용 가능한 기능 단위 (이 프로젝트)
- **Business Service**: 여러 Domain Service 호출 및 오케스트레이션, 비즈니스 규칙 적용

### Directory Structure

```
toy-core-system/
├── app/
│   ├── Domain/                  # 도메인 서비스 (DDD)
│   │   ├── Auth/                # 인증 도메인
│   │   ├── Health/              # Health Check 도메인
│   │   ├── Post/                # 블로그 게시물 도메인
│   │   └── UserActivity/        # 사용자 활동 도메인
│   ├── Bff/                     # BFF 레이어
│   │   ├── Controllers/         # BFF 컨트롤러
│   │   ├── Middleware/          # JWT 인증 미들웨어
│   │   └── Services/            # Core Service 클라이언트
│   ├── Http/Controllers/        # Core Service 컨트롤러
│   ├── Services/                # 비즈니스 서비스
│   │   └── Notification/        # 알림 서비스
│   ├── Repositories/            # Repository 구현체
│   ├── Models/                  # Eloquent Models
│   ├── Enums/                   # Enum 클래스
│   ├── Jobs/                    # Queue Jobs
│   ├── Console/Commands/        # Artisan Commands
│   ├── Providers/               # Service Providers
│   └── Shared/                  # 공유 컴포넌트
│       ├── Exceptions/          # 도메인 예외 클래스
│       ├── Http/                # HTTP 공통 모듈
│       └── Database/MongoDB/    # MongoDB 연결 관리
├── config/                      # Configuration
├── database/                    # Migrations, Seeders
├── routes/
│   ├── api.php                  # BFF API Routes (/api/*)
│   ├── internal.php             # Core Service Routes (/internal/*)
│   └── web.php                  # Web Routes
└── tests/                       # PHPUnit Tests
    ├── Feature/                 # Feature Tests
    └── Unit/                    # Unit Tests
```

### Testing

테스트는 인메모리 SQLite 데이터베이스를 사용합니다 (`phpunit.xml` 참조).

---

## Documentation

프로젝트의 모든 문서는 `docs/` 디렉토리에서 관리됩니다.

### 아키텍처 문서

전체 애플리케이션 아키텍처 및 레이어별 상세 문서입니다.

| 문서 | 설명 |
|------|------|
| **[docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)** | **전체 애플리케이션 아키텍처** - 3-Tier 레이어 구조, 인증 흐름, 데이터 흐름, 배포 아키텍처 |
| [docs/FRONTEND.md](docs/FRONTEND.md) | **Frontend Layer** - Next.js 프론트엔드 (Metronic), 페이지 구성, 라우트 보호 |
| [docs/BFF.md](docs/BFF.md) | **BFF Layer** - Backend For Frontend, JWT 인증, API Gateway |
| [docs/CORE.md](docs/CORE.md) | **Core Service Layer** - 도메인 로직, 비즈니스 규칙, Repository 패턴 |

### 도메인 문서

각 도메인 서비스의 상세 명세 문서입니다.

| 도메인 | 문서 | 설명 |
|--------|------|------|
| Auth | [docs/AUTH.md](docs/AUTH.md) | JWT 인증 및 소셜 로그인 연동 |
| Health | [docs/HEALTH.md](docs/HEALTH.md) | 서비스 연결 상태 확인 (PostgreSQL, Redis 등) |
| Timer | [docs/TIMER.md](docs/TIMER.md) | 목표 시점까지의 남은 시간 관리 |
| Timer 최적화 | [docs/TIMER_OPTIMIZATION.md](docs/TIMER_OPTIMIZATION.md) | Timer 도메인 성능 최적화 가이드 |
| File | [docs/FILE.md](docs/FILE.md) | MinIO 기반 파일 저장 및 관리 |
| Notification | [docs/NOTIFICATION.md](docs/NOTIFICATION.md) | 다채널(Email, SMS, Slack) 알림 발송 |
| User Activity | [docs/USER_ACTIVITY.md](docs/USER_ACTIVITY.md) | MongoDB 기반 사용자 활동 로그 |
| Post | [docs/POST.md](docs/POST.md) | Markdown 기반 블로그 게시물 관리 (Toast UI Editor) |

### 도메인 문서 구조

각 도메인 문서는 다음 섹션을 포함합니다:

1. **개요**: 도메인 목적, 주요 기능, 특징
2. **아키텍처**: 레이어 구조, 파일 구조
3. **데이터베이스 스키마**: 테이블 정의, 인덱스
4. **API 엔드포인트**: 모든 API 명세 및 예제
5. **Sequence Diagram**: 주요 흐름 다이어그램 (Mermaid)
6. **캐싱 전략**: 캐시 키, TTL, 무효화 전략
7. **테스트 커버리지**: 테스트 현황 및 파일 위치
8. **예외 처리**: 도메인별 예외 케이스

### 새 도메인 추가 시 문서화

새로운 도메인을 추가할 때는 반드시 문서를 생성해야 합니다:

1. **템플릿 복사**: `docs/DOMAIN_TEMPLATE.md`를 복사하여 새 문서 생성
2. **파일명 규칙**: `docs/{DOMAIN_NAME}.md` (대문자, 스네이크케이스)
3. **문서 목록 갱신**: 이 섹션의 "도메인 문서 목록" 테이블에 추가
4. **필수 섹션 작성**: 템플릿의 모든 섹션을 도메인에 맞게 작성

```bash
# 새 도메인 문서 생성 예시
cp docs/DOMAIN_TEMPLATE.md docs/USER.md
```

### 문서 관리 원칙

- **동기화 유지**: 코드 변경 시 문서도 함께 업데이트
- **API 우선 문서화**: API 명세는 반드시 문서화
- **시퀀스 다이어그램**: 복잡한 흐름은 Mermaid 다이어그램으로 시각화
- **테스트 현황**: 테스트 수와 파일 경로를 최신 상태로 유지

### 참고 문서 (Reference Packages)

외부 패키지 및 라이브러리 참고 문서는 `docs/references/` 디렉토리에서 관리됩니다.

| 패키지 | 문서 | 설명 |
|--------|------|------|
| Spatie Image | [docs/references/SPATIE_IMAGE.md](docs/references/SPATIE_IMAGE.md) | PHP 이미지 처리 패키지 - File 도메인 확장 시 이미지 처리 참고 |
| Laravel Page Speed | [docs/references/LARAVEL_PAGE_SPEED.md](docs/references/LARAVEL_PAGE_SPEED.md) | Laravel 웹 성능 최적화 미들웨어 - BFF 레이어 성능 개선, API 압축 및 캐싱 |
| Laravel Reverb | [docs/references/LARAVEL_REVERB.md](docs/references/LARAVEL_REVERB.md) | 실시간 WebSocket 서버 - Notification, Post, Timer 도메인 실시간 통신, 라이브 알림, 채팅, 대시보드 |
| Laravel Filament | [docs/references/FILAMENT.md](docs/references/FILAMENT.md) | 관리자 패널 & UI 프레임워크 - Post, File, Timer, User Activity 도메인 백오피스 관리, CRUD 인터페이스, 대시보드 위젯 |

**참고 문서 작성 원칙:**
- 패키지 개요 및 주요 기능
- 설치 및 요구사항
- 프로젝트 적용 시 통합 방법
- 실전 예제 및 사용 패턴
- 프로젝트 도메인과의 연계 가능성

---

## Application Architecture 공통화

애플리케이션 전반에서 일관성을 유지하기 위한 공통화 영역입니다.

### 공통화 현황

> **진행률: 2/7 (29%)**

| 영역 | 상태 | 위치 | 테스트 | 설명 |
|------|------|------|--------|------|
| HTTP Response | ✅ 완료 | `app/Shared/Http/` | ✅ | API 응답 포맷 통일 |
| Exception Handling | ✅ 완료 | `app/Shared/Exceptions/` | ✅ | 예외 → API 응답 자동 변환 |
| Form Request | ⏳ 예정 | `app/Shared/Http/Requests/` | - | 입력 검증 + 에러 응답 통합 |
| DTO | ⏳ 예정 | `app/Shared/DTO/` | - | 레이어 간 데이터 전송 객체 |
| Repository | ⏳ 예정 | `app/Shared/Repositories/` | - | 데이터 접근 추상화 인터페이스 |
| Domain Event | 🎯 **우선순위** | `app/Shared/Events/` | - | 도메인 이벤트 기반 구조 (Event-Driven Architecture) |
| Value Object | ⏳ 예정 | `app/Shared/ValueObjects/` | - | 불변 값 객체 베이스 클래스 |

### Form Request (예정)

Laravel FormRequest와 통합하여 ValidationException 발생 시 공통 에러 포맷을 적용합니다.

```php
// ValidationException 자동 변환
// → {"success": false, "error": {"code": "VALIDATION_ERROR", "details": {...}}}
```

**구현 예정 파일:**
- `app/Shared/Http/Requests/ApiFormRequest.php` - 공통 FormRequest

### DTO (예정)

레이어 간 데이터 전송을 위한 불변 객체입니다.

```php
final readonly class CreateUserDTO
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            name: $request->input('name'),
            email: $request->input('email'),
        );
    }
}
```

**구현 예정 파일:**
- `app/Shared/DTO/DataTransferObject.php` - DTO 베이스 클래스

### Repository (예정)

도메인에서 인프라(DB)를 분리하기 위한 인터페이스입니다.

```php
// Domain Layer - Interface
interface UserRepositoryInterface
{
    public function findById(UserId $id): ?User;
    public function save(User $user): void;
    public function delete(User $user): void;
}

// Infrastructure Layer - Implementation
class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(UserId $id): ?User
    {
        return User::find($id->value);
    }
}
```

**구현 예정 파일:**
- `app/Shared/Repositories/RepositoryInterface.php` - 공통 Repository 인터페이스

### Domain Event (Event-Driven Architecture)

**우선순위:** 🎯 높음 - 시스템의 핵심 아키텍처 패턴

도메인 이벤트 기반의 느슨한 결합을 지원합니다. Event-Driven Architecture(EDA)를 통해 확장 가능하고 유지보수가 용이한 시스템을 구축합니다.

> **상세 문서:** [docs/ARCHITECTURE.md - Event-Driven Architecture](docs/ARCHITECTURE.md#event-driven-architecture)

#### 핵심 원칙

1. **느슨한 결합**: 도메인 서비스는 이벤트만 발행, 처리는 리스너가 담당
2. **단일 책임**: 각 리스너는 하나의 명확한 책임
3. **비동기 처리**: 중요하지 않은 작업은 Queue Job으로 처리
4. **확장성**: 새 기능 추가 시 리스너만 추가 (기존 코드 수정 불필요)

#### 구현 예시

**이벤트 발행:**
```php
// app/Domain/Auth/Services/SocialAuthService.php
public function createUser(SocialUserDTO $dto): User
{
    $user = User::create([...]);

    // 도메인 이벤트 발행
    event(new UserCreated($user));

    return $user;
}
```

**이벤트 리스너 (동기):**
```php
// app/Domain/Auth/Listeners/CreateAuthEventLog.php
class CreateAuthEventLog
{
    public function handle(UserLoggedIn $event): void
    {
        $this->authEventService->log([
            'user_id' => $event->user->id,
            'event_type' => 'login',
        ]);
    }
}
```

**이벤트 리스너 (비동기):**
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

#### 주요 도메인 이벤트

| 도메인 | 이벤트 | 리스너 예시 |
|--------|--------|------------|
| Auth | `UserCreated` | SendWelcomeEmail, CreateAuthLog, UpdateUserCache |
| Auth | `UserLoggedIn` | CreateAuthLog, UpdateLoginStats |
| File | `FileUploaded` | GenerateThumbnail, ScanVirus, CreateActivityLog |
| Timer | `TimerCreated` | InvalidateCache, CreateActivityLog |
| Notification | `NotificationSent` | CreateNotificationLog, UpdateStats |

#### 구현 단계

1. **Domain Event 베이스 클래스** (`app/Shared/Events/DomainEvent.php`)
   - 공통 이벤트 인터페이스
   - 이벤트 메타데이터 (발생 시각, 이벤트 ID 등)

2. **HasDomainEvents Trait** (`app/Shared/Events/HasDomainEvents.php`)
   - Model에서 이벤트 발행 지원
   - 트랜잭션 커밋 후 이벤트 발행

3. **도메인별 이벤트 클래스**
   - `app/Domain/Auth/Events/UserCreated.php`
   - `app/Domain/Auth/Events/UserLoggedIn.php`
   - `app/Domain/File/Events/FileUploaded.php`
   - 등...

4. **이벤트 리스너 구현**
   - 동기 리스너: 즉시 실행 (로깅, 캐시 업데이트)
   - 비동기 리스너: Queue Job으로 실행 (이메일, 외부 API 호출)

5. **EventServiceProvider 등록**
   - 이벤트-리스너 매핑
   - 자동 디스커버리 설정

**구현 예정 파일:**
- `app/Shared/Events/DomainEvent.php` - 도메인 이벤트 베이스
- `app/Shared/Events/HasDomainEvents.php` - 이벤트 발행 Trait
- `app/Domain/*/Events/` - 도메인별 이벤트 클래스
- `app/Domain/*/Listeners/` - 도메인별 리스너
- `app/Providers/EventServiceProvider.php` - 이벤트 등록

### Value Object (예정)

불변 값 객체의 베이스 클래스입니다.

```php
final readonly class Email extends ValueObject
{
    public function __construct(
        public string $value
    ) {
        $this->validate();
    }

    protected function validate(): void
    {
        if (!filter_var($this->value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email format');
        }
    }
}
```

**구현 예정 파일:**
- `app/Shared/ValueObjects/ValueObject.php` - Value Object 베이스

---

## HTTP Response 공통화

모든 API 응답은 `ApiResponse` 클래스를 통해 일관된 포맷으로 반환됩니다.

### 응답 구조

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

**페이지네이션 응답 (Offset):**
```json
{
    "success": true,
    "data": [...],
    "pagination": {
        "type": "offset",
        "page": 1,
        "per_page": 15,
        "total": 100,
        "last_page": 7,
        "has_more_pages": true
    }
}
```

**페이지네이션 응답 (Cursor):**
```json
{
    "success": true,
    "data": [...],
    "pagination": {
        "type": "cursor",
        "per_page": 15,
        "next_cursor": "eyJpZCI6MTUsIl9wb...",
        "prev_cursor": null,
        "has_more_pages": true
    }
}
```

### 사용 방법

Controller에서 `ApiResponsable` trait 메서드 사용:

```php
class UserController extends Controller
{
    // 성공 응답
    public function show(User $user): JsonResponse
    {
        return $this->successResponse($user);
    }

    // 생성 응답 (201)
    public function store(Request $request): JsonResponse
    {
        $user = User::create($request->validated());
        return $this->createdResponse($user);
    }

    // 페이지네이션 (Offset/Cursor 자동 감지)
    public function index(): JsonResponse
    {
        return $this->paginatedResponse(User::paginate(15));
    }

    // 에러 응답
    public function error(): JsonResponse
    {
        return $this->notFoundResponse('사용자를 찾을 수 없습니다.');
    }

    // 커스텀 에러
    public function customError(): JsonResponse
    {
        return $this->errorResponse(
            ApiResponseCode::VALIDATION_ERROR,
            '입력값이 올바르지 않습니다.',
            ['email' => '이메일 형식이 올바르지 않습니다.']
        );
    }
}
```

### 에러 코드 (ApiResponseCode)

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `SUCCESS` | 200 | 요청 성공 |
| `CREATED` | 201 | 리소스 생성 |
| `UPDATED` | 200 | 리소스 수정 |
| `DELETED` | 200 | 리소스 삭제 |
| `BAD_REQUEST` | 400 | 잘못된 요청 |
| `UNAUTHORIZED` | 401 | 인증 필요 |
| `FORBIDDEN` | 403 | 권한 없음 |
| `NOT_FOUND` | 404 | 리소스 없음 |
| `VALIDATION_ERROR` | 400 | 유효성 검증 실패 |
| `CONFLICT` | 409 | 리소스 충돌 |
| `TOO_MANY_REQUESTS` | 429 | 요청 한도 초과 |
| `INTERNAL_ERROR` | 500 | 서버 오류 |
| `SERVICE_UNAVAILABLE` | 503 | 서비스 불가 |

### 관련 파일

- `app/Shared/Http/ApiResponse.php` - 응답 빌더
- `app/Shared/Http/ApiResponseCode.php` - 에러 코드 Enum
- `app/Shared/Http/Pagination/OffsetPagination.php` - Offset 페이지네이션
- `app/Shared/Http/Pagination/CursorPagination.php` - Cursor 페이지네이션
- `app/Shared/Http/Traits/ApiResponsable.php` - Controller Trait

### 테스트 파일

- `tests/Unit/Shared/Http/ApiResponseTest.php` - ApiResponse Unit 테스트
- `tests/Unit/Shared/Http/ApiResponseCodeTest.php` - ApiResponseCode Unit 테스트

---

## Exception Handling 공통화

도메인 예외를 API 응답으로 자동 변환합니다. `api/*` 경로의 요청이나 `Accept: application/json` 헤더가 있는 요청에서 예외가 발생하면 자동으로 JSON 에러 응답을 반환합니다.

### 사용 방법

서비스나 도메인 레이어에서 예외를 throw하면 자동으로 API 응답으로 변환됩니다:

```php
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ConflictException;
use App\Shared\Exceptions\DomainValidationException;

class UserService
{
    public function findUser(int $id): User
    {
        $user = User::find($id);

        if (!$user) {
            // 자동으로 404 JSON 응답 반환
            throw NotFoundException::forResource('User', $id);
        }

        return $user;
    }

    public function createUser(array $data): User
    {
        if (User::where('email', $data['email'])->exists()) {
            // 자동으로 409 JSON 응답 반환
            throw ConflictException::duplicateField('email', $data['email']);
        }

        return User::create($data);
    }

    public function validateAge(int $age): void
    {
        if ($age < 0) {
            // 자동으로 400 JSON 응답 반환
            throw DomainValidationException::forField('age', '나이는 0보다 커야 합니다.');
        }
    }
}
```

### 예외 클래스

| 예외 클래스 | HTTP Status | ApiResponseCode | 용도 |
|------------|-------------|-----------------|------|
| `NotFoundException` | 404 | `NOT_FOUND` | 리소스를 찾을 수 없을 때 |
| `BadRequestException` | 400 | `BAD_REQUEST` | 잘못된 요청 |
| `UnauthorizedException` | 401 | `UNAUTHORIZED` | 인증 필요 |
| `ForbiddenException` | 403 | `FORBIDDEN` | 접근 권한 없음 |
| `ConflictException` | 409 | `CONFLICT` | 리소스 충돌 (중복 등) |
| `DomainValidationException` | 400 | `VALIDATION_ERROR` | 도메인 규칙 검증 실패 |
| `BusinessException` | 400 | `BAD_REQUEST` | 일반 비즈니스 로직 예외 |
| `ServiceUnavailableException` | 503 | `SERVICE_UNAVAILABLE` | 서비스 이용 불가 |

### 팩토리 메서드

각 예외 클래스는 편의를 위한 팩토리 메서드를 제공합니다:

```php
// NotFoundException
NotFoundException::forResource('User', 123);
NotFoundException::forCriteria('User', ['email' => 'test@example.com']);

// ConflictException
ConflictException::duplicateField('email', 'test@example.com');
ConflictException::resourceExists('User', 'test@example.com');

// ForbiddenException
ForbiddenException::forResource('Post', 123);
ForbiddenException::forAction('delete');

// DomainValidationException
DomainValidationException::forField('email', '이메일 형식이 올바르지 않습니다.');
DomainValidationException::withErrors([
    'email' => '이메일 형식이 올바르지 않습니다.',
    'name' => '이름은 필수입니다.',
]);

// ServiceUnavailableException
ServiceUnavailableException::forService('PaymentGateway');
ServiceUnavailableException::maintenance();

// BusinessException (커스텀 코드 사용)
BusinessException::withCode(ApiResponseCode::TOO_MANY_REQUESTS, '요청이 너무 많습니다.');
```

### 상세 정보 추가

예외에 추가 정보를 포함할 수 있습니다:

```php
throw (new NotFoundException('사용자를 찾을 수 없습니다.'))
    ->withDetails(['searched_id' => 123, 'searched_at' => now()]);

// 응답:
// {
//     "success": false,
//     "error": {
//         "code": "NOT_FOUND",
//         "message": "사용자를 찾을 수 없습니다.",
//         "details": {"searched_id": 123, "searched_at": "2024-01-01T00:00:00Z"}
//     }
// }
```

### Laravel 예외 자동 변환

다음 Laravel 예외들도 자동으로 API 응답으로 변환됩니다:

| Laravel 예외 | 변환 결과 |
|-------------|----------|
| `ValidationException` | 400 VALIDATION_ERROR + 필드별 에러 상세 |
| `AuthenticationException` | 401 UNAUTHORIZED |
| `AuthorizationException` | 403 FORBIDDEN |
| `ModelNotFoundException` | 404 NOT_FOUND (모델명 포함) |
| `NotFoundHttpException` | 404 NOT_FOUND |
| 기타 `HttpException` | 해당 HTTP 상태 코드 |

### 관련 파일

- `app/Shared/Exceptions/DomainException.php` - 도메인 예외 베이스 클래스
- `app/Shared/Exceptions/Handler.php` - 예외 핸들러 (bootstrap/app.php에서 등록)
- `app/Shared/Exceptions/NotFoundException.php` - 리소스 없음 예외
- `app/Shared/Exceptions/BadRequestException.php` - 잘못된 요청 예외
- `app/Shared/Exceptions/UnauthorizedException.php` - 인증 필요 예외
- `app/Shared/Exceptions/ForbiddenException.php` - 권한 없음 예외
- `app/Shared/Exceptions/ConflictException.php` - 리소스 충돌 예외
- `app/Shared/Exceptions/DomainValidationException.php` - 도메인 검증 예외
- `app/Shared/Exceptions/BusinessException.php` - 일반 비즈니스 예외
- `app/Shared/Exceptions/ServiceUnavailableException.php` - 서비스 불가 예외

### 테스트 파일

- `tests/Unit/Shared/Exceptions/DomainExceptionTest.php` - DomainException Unit 테스트
- `tests/Feature/Shared/Exceptions/ExceptionHandlerTest.php` - Exception Handler Feature 테스트
