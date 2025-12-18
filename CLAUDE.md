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

> **아키텍처 혼용:** 이 프로젝트는 DDD와 전통적 Laravel 구조를 혼용합니다.
> - **DDD 구조**: Auth, Health, Post, UserActivity → `app/Domain/`
> - **전통적 구조**: Timer, File, Notification → `app/Http/Controllers/`, `app/Services/`

```
toy-core-system/
├── app/
│   ├── Domain/                  # 도메인 서비스 (DDD)
│   │   ├── Auth/                # 인증 도메인 (소셜 로그인, JWT)
│   │   │   ├── Controllers/     # BFF 컨트롤러
│   │   │   ├── Services/        # 비즈니스 로직
│   │   │   ├── Repositories/    # 데이터 접근
│   │   │   ├── DTOs/            # 데이터 전송 객체
│   │   │   ├── Observers/       # 모델 옵저버
│   │   │   ├── Exceptions/      # 도메인 예외
│   │   │   └── Contracts/       # 인터페이스
│   │   ├── Health/              # Health Check 도메인
│   │   │   ├── Controllers/     # 헬스체크 컨트롤러
│   │   │   ├── Services/        # 헬스체크 서비스
│   │   │   ├── Checkers/        # 각종 헬스 체커
│   │   │   ├── DTOs/            # 헬스체크 DTO
│   │   │   └── Contracts/       # 헬스체크 인터페이스
│   │   ├── Post/                # 블로그 게시물 도메인
│   │   │   ├── Controllers/     # 게시물 컨트롤러
│   │   │   ├── Services/        # 게시물 서비스
│   │   │   ├── Events/          # 도메인 이벤트
│   │   │   └── DTOs/            # 게시물 DTO
│   │   └── UserActivity/        # 사용자 활동 도메인 (MongoDB)
│   │       ├── Controllers/     # 활동 로그 컨트롤러
│   │       ├── Services/        # 활동 로그 서비스
│   │       └── Repositories/    # MongoDB 리포지토리
│   ├── Bff/                     # BFF 레이어
│   │   ├── Controllers/         # BFF 컨트롤러
│   │   ├── Middleware/          # JWT 인증 미들웨어
│   │   └── Services/            # Core Service 클라이언트
│   ├── Http/Controllers/        # Core Service 컨트롤러 (전통적 구조)
│   │   ├── FileController.php   # 파일 관리 (MinIO)
│   │   ├── TimerController.php  # 타이머 관리 (Redis)
│   │   └── NotificationController.php  # 알림 발송
│   ├── Services/                # 비즈니스 서비스
│   │   ├── FileService.php      # 파일 서비스
│   │   ├── TimerService.php     # 타이머 서비스
│   │   └── Notification/        # 알림 서비스
│   │       ├── NotificationService.php
│   │       └── Channels/        # 알림 채널 (Email, SMS, Slack)
│   ├── Repositories/            # Repository 구현체
│   │   └── CachedFileRepository.php
│   ├── Models/                  # Eloquent Models
│   │   ├── User.php             # 사용자
│   │   ├── Post.php             # 게시물
│   │   ├── File.php             # 파일
│   │   ├── Timer.php            # 타이머
│   │   ├── SocialAccount.php    # 소셜 계정
│   │   ├── NotificationQueue.php # 알림 큐
│   │   └── NotificationLog.php  # 알림 로그
│   ├── Enums/                   # Enum 클래스
│   │   └── Notification/        # 알림 관련 Enum
│   ├── Jobs/                    # Queue Jobs
│   ├── Console/Commands/        # Artisan Commands
│   ├── Providers/               # Service Providers
│   └── Shared/                  # 공유 컴포넌트
│       ├── Exceptions/          # 도메인 예외 클래스
│       │   ├── DomainException.php      # 베이스 예외
│       │   ├── NotFoundException.php    # 404
│       │   ├── BadRequestException.php  # 400
│       │   ├── ConflictException.php    # 409
│       │   └── ...              # 기타 예외들
│       ├── Http/                # HTTP 공통 모듈
│       │   ├── ApiResponse.php  # 응답 빌더
│       │   ├── ApiResponseCode.php # 응답 코드
│       │   ├── Pagination/      # 페이지네이션
│       │   └── Traits/          # HTTP Traits
│       └── Database/MongoDB/    # MongoDB 연결 관리
├── config/                      # Configuration
├── database/                    # Migrations, Seeders
│   ├── migrations/              # DB 마이그레이션
│   └── seeders/                 # 시드 데이터
├── docs/                        # 프로젝트 문서
│   ├── ARCHITECTURE.md          # 전체 아키텍처
│   ├── DEVELOPMENT_PROCESS.md   # 개발 프로세스
│   ├── *.md                     # 도메인별 문서
│   └── references/              # 외부 패키지 참고 문서
├── routes/
│   ├── api.php                  # BFF API Routes (/api/*)
│   ├── internal.php             # Core Service Routes (/internal/*)
│   ├── web.php                  # Web Routes
│   └── console.php              # Console Routes
├── tests/                       # PHPUnit Tests (32+ files)
│   ├── Feature/                 # Feature Tests
│   │   ├── Auth/                # 인증 테스트
│   │   ├── Bff/                 # BFF 테스트
│   │   └── Shared/              # 공통 기능 테스트
│   └── Unit/                    # Unit Tests
│       ├── Auth/                # 인증 유닛 테스트
│       ├── Domain/              # 도메인 유닛 테스트
│       ├── Models/              # 모델 유닛 테스트
│       ├── Services/            # 서비스 유닛 테스트
│       └── Shared/              # 공통 유닛 테스트
└── resources/
    ├── swagger/                 # Swagger OpenAPI 스펙
    └── views/                   # Blade 템플릿
```

### Testing

테스트는 인메모리 SQLite 데이터베이스를 사용합니다 (`phpunit.xml` 참조).

### Development Process

프로젝트의 개발 프로세스 및 레이어별 책임에 대한 자세한 내용은 다음 문서를 참고하세요:

**[docs/DEVELOPMENT_PROCESS.md](docs/DEVELOPMENT_PROCESS.md)** - 개발 작업 프로세스 가이드
- 3-Tier 레이어 구조 상세 설명
- 레이어별 책임 (Frontend, BFF, Core Service)
- API 설계 원칙
- 보안 가이드 (Internal API 격리 원칙)
- 테스트 전략
- 배포 고려사항

> **⚠️ 중요:** Internal API (`/internal/*`)는 절대 외부에 공개되어서는 안 됩니다.
> 모든 외부 요청은 반드시 BFF 레이어를 거쳐야 합니다.

---

## AI Assistant Guidance

Claude Code 또는 다른 AI 어시스턴트가 이 코드베이스 작업 시 준수해야 할 가이드라인입니다.

### 코드 작성 원칙

1. **기존 패턴 따르기**
   - 새 기능 추가 시 기존 도메인의 패턴을 참고
   - DDD 구조 도메인: Auth, Health, Post, UserActivity를 참고
   - 전통적 구조 도메인: Timer, File, Notification을 참고

2. **공통 모듈 활용**
   - HTTP 응답: 반드시 `ApiResponse`와 `ApiResponsable` trait 사용
   - 예외 처리: `app/Shared/Exceptions/` 의 도메인 예외 사용
   - 페이지네이션: `OffsetPagination` 또는 `CursorPagination` 사용

3. **문서화**
   - 새 도메인 추가 시 `docs/DOMAIN_TEMPLATE.md`를 복사하여 문서 생성
   - API 변경 시 해당 도메인 문서의 API 섹션 업데이트
   - `CLAUDE.md`의 도메인 목록 테이블 업데이트

4. **테스트 작성**
   - 모든 새 기능에 대한 테스트 작성 필수
   - Unit 테스트: 비즈니스 로직, 서비스 클래스
   - Feature 테스트: API 엔드포인트, 통합 시나리오

### 코드 스타일

- **PSR-12** 준수 (Laravel Pint 사용)
- **Strict Types**: 모든 PHP 파일에 `declare(strict_types=1);` 선언
- **타입 힌팅**: 파라미터 및 리턴 타입 명시
- **Final 클래스**: DTO, Value Object는 `final` 키워드 사용
- **Readonly 속성**: PHP 8.4+ readonly property 적극 활용

```php
<?php

declare(strict_types=1);

namespace App\Domain\Example;

final readonly class ExampleDTO
{
    public function __construct(
        public string $name,
        public int $age,
    ) {}
}
```

### 파일 참조 규칙

코드나 구현을 참조할 때는 다음 형식을 사용하세요:

- **파일 경로**: `app/Domain/Auth/Services/SocialAuthService.php`
- **특정 라인**: `app/Domain/Auth/Services/SocialAuthService.php:42`
- **메서드**: `SocialAuthService::createUser()` at `app/Domain/Auth/Services/SocialAuthService.php:42`

### 작업 시작 전 체크리스트

1. **문서 읽기**
   - 해당 도메인의 문서 (`docs/{DOMAIN}.md`) 확인
   - 관련 참고 문서 (`docs/references/`) 확인
   - `docs/DEVELOPMENT_PROCESS.md` 리뷰

2. **기존 코드 분석**
   - 유사한 기능의 구현 찾기
   - 사용 중인 패턴 및 컨벤션 파악
   - 테스트 코드 확인

3. **영향 범위 확인**
   - 변경이 다른 도메인에 영향을 주는지 확인
   - API 변경 시 BFF 레이어 영향 검토
   - 데이터베이스 마이그레이션 필요 여부 확인

### 일반적인 작업 플로우

```bash
# 1. 브랜치 생성
git checkout -b feature/your-feature-name

# 2. 코드 작성

# 3. 코드 스타일 검사 및 수정
./vendor/bin/pint

# 4. 테스트 실행
composer test

# 5. 커밋 및 푸시
git add .
git commit -m "feat: Add your feature description"
git push -u origin feature/your-feature-name

# 6. Pull Request 생성
```

### 주의사항 (Common Pitfalls)

#### 1. Internal API 노출 금지

❌ **잘못된 예:**
```php
// routes/web.php 또는 routes/api.php에서
Route::get('/timers/{key}', [TimerController::class, 'show']);
```

✅ **올바른 예:**
```php
// routes/internal.php에서만 정의
Route::get('/timers/{key}', [TimerController::class, 'show']);

// BFF에서 Internal API 호출
// app/Bff/Services/CoreTimerService.php
public function getTimer(string $key): array
{
    $response = Http::get(config('services.core.url') . "/internal/timers/{$key}");
    return $response->json();
}
```

#### 2. ApiResponse 사용하지 않음

❌ **잘못된 예:**
```php
return response()->json(['data' => $user], 200);
```

✅ **올바른 예:**
```php
use App\Shared\Http\Traits\ApiResponsable;

class UserController extends Controller
{
    use ApiResponsable;

    public function show(User $user): JsonResponse
    {
        return $this->successResponse($user);
    }
}
```

#### 3. 예외를 직접 throw하지 않고 수동 에러 응답

❌ **잘못된 예:**
```php
if (!$user) {
    return response()->json(['error' => 'User not found'], 404);
}
```

✅ **올바른 예:**
```php
use App\Shared\Exceptions\NotFoundException;

if (!$user) {
    throw NotFoundException::forResource('User', $id);
}
```

#### 4. 새 도메인 추가 시 문서 누락

❌ **잘못된 예:**
- 도메인 코드만 작성하고 문서 없음

✅ **올바른 예:**
```bash
# 1. 도메인 코드 작성
mkdir -p app/Domain/Payment

# 2. 문서 생성
cp docs/DOMAIN_TEMPLATE.md docs/PAYMENT.md

# 3. CLAUDE.md 도메인 목록 업데이트
# "도메인 문서" 테이블에 Payment 추가
```

#### 5. 하드코딩된 URL 또는 설정값

❌ **잘못된 예:**
```php
$url = 'http://localhost:8000/internal/files';
```

✅ **올바른 예:**
```php
$url = config('services.core.url') . '/internal/files';
```

#### 6. 타입 힌팅 누락

❌ **잘못된 예:**
```php
public function createUser($data)
{
    return User::create($data);
}
```

✅ **올바른 예:**
```php
public function createUser(array $data): User
{
    return User::create($data);
}
```

### Debugging & Troubleshooting

#### 로그 확인

```bash
# 실시간 로그 확인 (Laravel Pail)
php artisan pail

# 특정 로그 레벨만 보기
php artisan pail --filter="level:error"

# 로그 파일 직접 확인
tail -f storage/logs/laravel.log
```

#### 캐시 문제

```bash
# 모든 캐시 클리어
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 전체 최적화 재생성
php artisan optimize:clear
php artisan optimize
```

#### 데이터베이스 이슈

```bash
# 마이그레이션 상태 확인
php artisan migrate:status

# 마이그레이션 롤백
php artisan migrate:rollback

# Fresh 마이그레이션 (테스트 환경)
php artisan migrate:fresh --seed
```

#### Queue 디버깅

```bash
# 큐 작업 실시간 처리 (동기)
php artisan queue:work --tries=1

# 실패한 작업 확인
php artisan queue:failed

# 실패한 작업 재시도
php artisan queue:retry {id}
php artisan queue:retry all
```

#### Redis 확인

```bash
# Redis 연결 테스트
php artisan tinker
>>> Redis::ping()
>>> Redis::get('test-key')

# Redis 캐시 키 확인
>>> Redis::keys('*')
```

#### 일반적인 문제 해결

| 문제 | 원인 | 해결 방법 |
|------|------|----------|
| `Class not found` | Autoload 갱신 필요 | `composer dump-autoload` |
| `.env` 변경 반영 안 됨 | Config 캐시 | `php artisan config:clear` |
| Route 변경 반영 안 됨 | Route 캐시 | `php artisan route:clear` |
| JWT 인증 실패 | Secret 키 미설정 | `.env`에 `JWT_SECRET` 확인 |
| MongoDB 연결 실패 | Extension 미설치 | `pecl install mongodb` |
| MinIO 업로드 실패 | Bucket 없음 | MinIO 콘솔에서 bucket 생성 |

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

---

## Quick Reference

AI 어시스턴트를 위한 빠른 참조 가이드입니다.

### 주요 파일 위치

| 카테고리 | 파일 경로 | 설명 |
|---------|----------|------|
| **라우트** | `routes/api.php` | BFF API 라우트 |
| | `routes/internal.php` | Core Service 라우트 (외부 노출 금지) |
| | `routes/web.php` | 웹 라우트 |
| **공통 HTTP** | `app/Shared/Http/ApiResponse.php` | API 응답 빌더 |
| | `app/Shared/Http/ApiResponseCode.php` | 응답 코드 Enum |
| | `app/Shared/Http/Traits/ApiResponsable.php` | 컨트롤러 Trait |
| **예외 처리** | `app/Shared/Exceptions/DomainException.php` | 도메인 예외 베이스 |
| | `app/Shared/Exceptions/Handler.php` | 글로벌 예외 핸들러 |
| | `app/Shared/Exceptions/NotFoundException.php` | 404 예외 |
| **설정** | `.env.example` | 환경 변수 템플릿 |
| | `config/` | 애플리케이션 설정 |
| | `phpunit.xml` | 테스트 설정 |
| **문서** | `CLAUDE.md` | AI 어시스턴트 가이드 (이 파일) |
| | `README.md` | 프로젝트 README |
| | `docs/ARCHITECTURE.md` | 전체 아키텍처 |
| | `docs/DEVELOPMENT_PROCESS.md` | 개발 프로세스 |
| | `docs/{DOMAIN}.md` | 도메인별 상세 문서 |

### 자주 사용하는 명령어

```bash
# 개발 환경 실행
composer dev                    # 전체 개발 서버 (서버+큐+로그+Vite)
php artisan serve               # Laravel 서버만
php artisan queue:listen        # Queue 워커
php artisan pail                # 실시간 로그

# 테스트
composer test                   # 전체 테스트
php artisan test --filter=...  # 특정 테스트

# 코드 품질
./vendor/bin/pint               # 코드 스타일 수정

# 캐시 클리어
php artisan optimize:clear      # 전체 캐시 클리어
php artisan cache:clear         # 애플리케이션 캐시
php artisan config:clear        # Config 캐시
php artisan route:clear         # Route 캐시

# 데이터베이스
php artisan migrate             # 마이그레이션 실행
php artisan migrate:fresh       # 전체 재생성
php artisan db:seed             # 시드 데이터

# 디버깅
php artisan tinker              # REPL 콘솔
```

### 환경 변수 (.env)

필수 환경 변수:

```bash
# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=toy_core
DB_USERNAME=postgres
DB_PASSWORD=postgres

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# MongoDB
MONGODB_HOST=127.0.0.1
MONGODB_PORT=27017
MONGODB_DATABASE=toy_core

# MinIO
MINIO_ENDPOINT=http://localhost:9000
MINIO_ACCESS_KEY_ID=your-key
MINIO_SECRET_ACCESS_KEY=your-secret
MINIO_PUBLIC_BUCKET=public
MINIO_PRIVATE_BUCKET=private

# JWT
JWT_SECRET=your-jwt-secret

# Swagger
SWAGGER_UI_ENABLED=true
```

### API 엔드포인트 구조

| 경로 패턴 | 레이어 | 인증 | 설명 |
|----------|--------|------|------|
| `/api/*` | BFF | JWT 필요 | 외부 프론트엔드용 API |
| `/internal/*` | Core Service | X-User-Id 헤더 | 내부 서비스 전용 (외부 노출 금지) |
| `/health` | Core Service | 없음 | Health Check |

### 도메인 구조 패턴

**DDD 구조 도메인 (Auth, Health, Post, UserActivity):**
```
app/Domain/{DomainName}/
├── Controllers/       # HTTP 컨트롤러
├── Services/          # 비즈니스 로직
├── Repositories/      # 데이터 접근 레이어
├── DTOs/              # 데이터 전송 객체
├── Events/            # 도메인 이벤트
├── Exceptions/        # 도메인 예외
└── Contracts/         # 인터페이스
```

**전통적 Laravel 구조 (Timer, File, Notification):**
```
app/
├── Http/Controllers/{DomainName}Controller.php
├── Services/{DomainName}Service.php
└── Models/{DomainName}.php
```

### 공통 패턴 예제

**컨트롤러 응답:**
```php
use App\Shared\Http\Traits\ApiResponsable;

class ExampleController extends Controller
{
    use ApiResponsable;

    public function index()
    {
        return $this->successResponse($data);
    }

    public function store()
    {
        return $this->createdResponse($data);
    }

    public function destroy()
    {
        return $this->deletedResponse();
    }
}
```

**예외 throw:**
```php
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\ConflictException;

// 리소스 없음
throw NotFoundException::forResource('User', $id);

// 중복 충돌
throw ConflictException::duplicateField('email', $email);
```

**페이지네이션:**
```php
// Offset 페이지네이션
$users = User::paginate(15);
return $this->paginatedResponse($users);

// Cursor 페이지네이션
$users = User::cursorPaginate(15);
return $this->paginatedResponse($users);
```

### 연락처 및 참고 자료

- **프로젝트**: Toy Core System
- **저장소**: 로컬 개발 환경
- **문서**: `docs/` 디렉토리
- **Swagger UI**: http://localhost:8000/swagger (개발 환경)

---

**마지막 업데이트**: 2025-12-18
**버전**: Laravel 12.x, PHP 8.4+
