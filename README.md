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
| Monitoring | Sentry |

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
composer setup
```

### Run Development Server

```bash
composer dev
```

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
│   ├── Http/Controllers/    # API Controllers
│   ├── Services/            # Domain Logic (예정)
│   ├── Models/              # Eloquent Models
│   └── Providers/           # Service Providers
├── config/                  # Configuration
├── database/                # Migrations, Seeders
├── routes/
│   ├── api.php              # API Routes (예정)
│   └── web.php              # Web Routes
└── tests/                   # PHPUnit Tests
```

## License

MIT License
