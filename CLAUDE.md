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

## Commands

### Development

```bash
# 전체 개발 서버 실행 (Laravel 서버, Queue, Pail 로그, Vite 동시 실행)
composer dev

# 프로젝트 초기 설정
composer setup
```

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
│   ├── Http/Controllers/    # API Controllers
│   ├── Models/              # Eloquent Models
│   ├── Providers/           # Service Providers
│   └── Shared/              # 공유 컴포넌트
│       └── Http/            # HTTP 공통 모듈
├── config/                  # Configuration
├── database/                # Migrations, Seeders
├── routes/
│   ├── api.php              # API Routes
│   └── web.php              # Web Routes
└── tests/                   # PHPUnit Tests
```

### Testing

테스트는 인메모리 SQLite 데이터베이스를 사용합니다 (`phpunit.xml` 참조).

## Application Architecture 공통화

애플리케이션 전반에서 일관성을 유지하기 위한 공통화 영역입니다.

### 공통화 현황

| 영역 | 상태 | 위치 | 설명 |
|------|------|------|------|
| HTTP Response | ✅ 완료 | `app/Shared/Http/` | API 응답 포맷 통일 |
| Exception Handling | ✅ 완료 | `app/Shared/Exceptions/` | 예외 → API 응답 자동 변환 |
| Form Request | ⏳ 예정 | `app/Shared/Http/Requests/` | 입력 검증 + 에러 응답 통합 |
| DTO | ⏳ 예정 | `app/Shared/DTO/` | 레이어 간 데이터 전송 객체 |
| Repository | ⏳ 예정 | `app/Shared/Repositories/` | 데이터 접근 추상화 인터페이스 |
| Domain Event | ⏳ 예정 | `app/Shared/Events/` | 도메인 이벤트 기반 구조 |
| Value Object | ⏳ 예정 | `app/Shared/ValueObjects/` | 불변 값 객체 베이스 클래스 |

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

### Domain Event (예정)

도메인 이벤트 기반의 느슨한 결합을 지원합니다.

```php
// 도메인 이벤트 발행
$user->raise(new UserCreated($user->id));

// 이벤트 리스너에서 처리
class SendWelcomeEmail
{
    public function handle(UserCreated $event): void
    {
        // 이메일 발송
    }
}
```

**구현 예정 파일:**
- `app/Shared/Events/DomainEvent.php` - 도메인 이벤트 베이스
- `app/Shared/Events/HasDomainEvents.php` - 이벤트 발행 Trait

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
