# Toy Domain Service

도메인 핵심 로직을 제공하는 Domain Service API입니다.

## Overview

Domain Service는 마이크로서비스 아키텍처에서 **도메인 핵심 기능**만을 담당하는 Primitive Service입니다.
상위 서비스(Business Service)에서 조합하여 사용하며, 비즈니스 로직의 원자적 단위를 제공합니다.

## Architecture

### Service Layer Structure

```
Front → BFF → Business → Domain
```

### System Context

```
┌─────────────────────────────────────────────────────────────────┐
│                        Frontend Layer                           │
│                   (Web, Mobile, App)                            │
└─────────────────────────────────┬───────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                      BFF (Backend For Frontend)                 │
│                                                                 │
│  • 프론트엔드 전용 API                                            │
│  • 응답 포맷 변환                                                 │
│  • 인증/인가 처리                                                 │
└─────────────────────────────────┬───────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                     Business Service Layer                      │
│                                                                 │
│  • 비즈니스 워크플로우 조합                                        │
│  • 여러 Domain Service 호출 및 오케스트레이션                       │
│  • 비즈니스 규칙 적용                                             │
│  • 트랜잭션 경계 관리                                             │
└─────────────────────────────────┬───────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                    ⭐ Domain Service Layer                      │
│                      (Primitive Service)                        │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                   toy-core-system                        │   │
│  │                                                          │   │
│  │              도메인 핵심 로직 제공 (TBD)                    │   │
│  │                                                          │   │
│  └─────────────────────────────────────────────────────────┘   │
└─────────────────────────────────┬───────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Infrastructure Layer                         │
│                                                                 │
│  ┌──────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐  │
│  │ Postgres │ │  Redis  │ │ MongoDB │ │  MinIO  │ │  Queue  │  │
│  └──────────┘ └─────────┘ └─────────┘ └─────────┘ └─────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

### Domain Service 역할

```
┌────────────────────────────────────────────────────────────────┐
│                       Domain Service                           │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│  ✅ 담당하는 것 (IN SCOPE)                                      │
│  ─────────────────────────                                     │
│  • 원자적 도메인 로직                                            │
│  • 재사용 가능한 기능 단위                                        │
│  • Race Condition 방지                                         │
│  • 데이터 정합성 보장                                            │
│                                                                │
│  ❌ 담당하지 않는 것 (OUT OF SCOPE)                              │
│  ─────────────────────────────                                 │
│  • 비즈니스 워크플로우 조합                                       │
│  • 사용자 인증/인가                                              │
│  • UI/UX 관련 로직                                              │
│  • 트랜잭션 오케스트레이션                                        │
│                                                                │
└────────────────────────────────────────────────────────────────┘
```

### Business vs Domain Service

```
┌─────────────────────────────────────────────────────────────────┐
│                      Business Service                           │
│                  (워크플로우 조합/오케스트레이션)                    │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│   • 여러 Domain Service 호출 및 조합                             │
│   • 비즈니스 규칙/판정 로직                                       │
│   • 트랜잭션 경계 관리                                           │
│   • 결과 반환                                                   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Domain Service                             │
│                    (원자적 도메인 로직)                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│   • 단일 책임의 원자적 연산                                       │
│   • 비즈니스 판정 로직 없음                                       │
│   • 재사용 가능한 빌딩 블록                                       │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

## Tech Stack

| Category | Technology |
|----------|------------|
| Framework | Laravel 12.x |
| Language | PHP 8.4+ |
| Database | PostgreSQL 18 |
| Cache | Redis 8 |
| NoSQL | MongoDB 8 |
| Object Storage | MinIO |
| Admin Panel | Filament 4.x |
| Frontend | Tailwind CSS 4.x (Vite) |
| Testing | PHPUnit 11.x |
| Monitoring | Sentry |
| API Docs | Swagger UI (OpenAPI 3.0) |

## Development

### Requirements

- PHP 8.4+
- Composer
- PostgreSQL 18
- Redis 8
- MongoDB 8
- MinIO

### Setup

```bash
# 프로젝트 초기 설정
composer setup

# Filament 에셋 생성 (필수)
php artisan filament:assets
```

### Run Development Server

```bash
composer dev
```

### Admin Panel

Filament 기반 관리자 패널이 제공됩니다.

```bash
# Admin Panel 접속
http://localhost:8000/admin

# 관리자 사용자 생성
php artisan make:filament-user
```

**관련 문서:**
- [Filament 설치 가이드](docs/FILAMENT_SETUP.md)
- [Filament 참고 문서](docs/references/FILAMENT.md)

### Run Tests

```bash
composer test
```

### Code Style

```bash
./vendor/bin/pint
```

## Project Structure

```
toy-core-system/
├── app/
│   ├── Domain/                     # Domain Services (DDD)
│   │   ├── Auth/                   # 인증 도메인
│   │   ├── Post/                   # 블로그 게시물 도메인
│   │   ├── File/                   # 파일 관리 도메인
│   │   └── Timer/                  # 타이머 도메인
│   ├── Filament/                   # Admin Panel (Filament)
│   │   ├── Resources/              # CRUD Resources
│   │   ├── Pages/                  # Custom Pages
│   │   └── Widgets/                # Dashboard Widgets
│   ├── Http/Controllers/           # API Controllers
│   ├── Services/                   # Business Services
│   ├── Models/                     # Eloquent Models
│   ├── Shared/                     # Shared Components
│   │   ├── Http/                   # HTTP Response, Pagination
│   │   └── Exceptions/             # Domain Exceptions
│   └── Providers/                  # Service Providers
│       └── Filament/               # Filament Panel Providers
├── config/                         # Configuration
├── database/                       # Migrations, Seeders
├── docs/                           # Documentation
│   ├── FILAMENT_SETUP.md           # Filament 설치 가이드
│   └── references/                 # Package References
│       └── FILAMENT.md             # Filament 참고 문서
├── routes/
│   ├── api.php                     # BFF API Routes (/api/*)
│   ├── internal.php                # Core Service Routes (/internal/*)
│   └── web.php                     # Web Routes
└── tests/                          # PHPUnit Tests
```

## License

MIT License
