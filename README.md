# Toy Core Service

도메인 핵심 로직을 제공하는 Core Service API입니다.

## Overview

Core Service는 마이크로서비스 아키텍처에서 **도메인 핵심 기능**만을 담당하는 Primitive Service입니다.
상위 서비스(Aggregate/Orchestration)에서 조합하여 사용하며, 비즈니스 로직의 원자적 단위를 제공합니다.

## Architecture

### System Context

```
┌─────────────────────────────────────────────────────────────────┐
│                         Client Layer                            │
│                   (Web, Mobile, External API)                   │
└─────────────────────────────────┬───────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                      BFF / API Gateway                          │
│                  (Backend For Frontend)                         │
└─────────────────────────────────┬───────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                   Aggregate Service Layer                       │
│         (비즈니스 워크플로우, 서비스 조합/오케스트레이션)            │
│                                                                 │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐              │
│  │ 이벤트 서비스 │  │ 보상 서비스  │  │ 알림 서비스  │  ...          │
│  └─────────────┘  └─────────────┘  └─────────────┘              │
└─────────────────────────────────┬───────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                     ⭐ Core Service Layer                       │
│              (도메인 핵심 로직, Primitive Service)                │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │                   toy-core-system                        │   │
│  │                                                          │   │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐      │   │
│  │  │  Sequence   │  │   Counter   │  │    Rate     │      │   │
│  │  │  Generator  │  │   Service   │  │   Limiter   │ ...  │   │
│  │  └─────────────┘  └─────────────┘  └─────────────┘      │   │
│  └─────────────────────────────────────────────────────────┘   │
└─────────────────────────────────┬───────────────────────────────┘
                                  │
                                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Infrastructure Layer                         │
│                                                                 │
│      ┌─────────┐    ┌─────────┐    ┌─────────┐                 │
│      │  Redis  │    │  MySQL  │    │  Queue  │                 │
│      └─────────┘    └─────────┘    └─────────┘                 │
└─────────────────────────────────────────────────────────────────┘
```

### Core Service 역할

```
┌────────────────────────────────────────────────────────────────┐
│                        Core Service                            │
├────────────────────────────────────────────────────────────────┤
│                                                                │
│  ✅ 담당하는 것 (IN SCOPE)                                      │
│  ─────────────────────────                                     │
│  • 순번 발급 (Sequence Generator)                               │
│  • 원자적 카운터 (Atomic Counter)                                │
│  • 중복 방지 로직                                                │
│  • Race Condition 방지                                         │
│                                                                │
│  ❌ 담당하지 않는 것 (OUT OF SCOPE)                              │
│  ─────────────────────────────                                 │
│  • 보상 지급 로직                                                │
│  • 사용자 인증/인가                                              │
│  • UI/UX 관련 로직                                              │
│  • 비즈니스 워크플로우 조합                                       │
│                                                                │
└────────────────────────────────────────────────────────────────┘
```

## Core Features

### 1. Sequence Generator (순번 발급)

선착순 이벤트를 위한 원자적 순번 발급 서비스

```
┌──────────────────────────────────────────────────────────────┐
│                    Sequence Generator                        │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│   Request                     Redis                Response  │
│  ─────────                   ───────              ─────────  │
│                                                              │
│  ┌─────────┐    INCR      ┌─────────┐              ┌─────┐  │
│  │ Client  │ ──────────▶  │ event:1 │ ──────────▶  │ 42  │  │
│  └─────────┘   (atomic)   │  = 42   │   (return)   └─────┘  │
│                           └─────────┘                        │
│                                                              │
│   • Lua Script로 원자적 연산 보장                              │
│   • Race Condition 완벽 방지                                  │
│   • 순서 보장 (1, 2, 3, ... N)                                │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

**사용 시나리오:**
- 선착순 100명 이벤트
- 대기열 번호표 발급
- 주문 번호 생성

### 2. Counter Service (카운터)

분산 환경에서의 정확한 카운팅

```
┌──────────────────────────────────────────────────────────────┐
│                      Counter Service                         │
├──────────────────────────────────────────────────────────────┤
│                                                              │
│   Operations:                                                │
│   ───────────                                                │
│   • INCREMENT  : +1 증가                                     │
│   • DECREMENT  : -1 감소                                     │
│   • GET        : 현재 값 조회                                 │
│   • RESET      : 초기화                                      │
│                                                              │
│   Use Cases:                                                 │
│   ───────────                                                │
│   • 재고 수량 관리                                            │
│   • 좋아요/조회수 카운팅                                       │
│   • 동시 접속자 수 추적                                        │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

## Sequence Diagram

### 선착순 이벤트 참여 흐름

```
┌────────┐     ┌──────────────┐     ┌──────────────┐     ┌───────┐
│ Client │     │  Aggregate   │     │ Core Service │     │ Redis │
└───┬────┘     │   Service    │     │              │     └───┬───┘
    │          └──────┬───────┘     └──────┬───────┘         │
    │                 │                    │                 │
    │  1. 이벤트 참여  │                    │                 │
    │ ───────────────▶│                    │                 │
    │                 │                    │                 │
    │                 │  2. 순번 요청       │                 │
    │                 │───────────────────▶│                 │
    │                 │                    │                 │
    │                 │                    │  3. INCR        │
    │                 │                    │────────────────▶│
    │                 │                    │                 │
    │                 │                    │  4. return 42   │
    │                 │                    │◀────────────────│
    │                 │                    │                 │
    │                 │  5. 순번: 42       │                 │
    │                 │◀───────────────────│                 │
    │                 │                    │                 │
    │                 │  6. 42 ≤ 100 ?     │                 │
    │                 │     → 당첨 처리     │                 │
    │                 │                    │                 │
    │  7. 당첨 결과    │                    │                 │
    │ ◀───────────────│                    │                 │
    │                 │                    │                 │
```

## Tech Stack

| Category | Technology |
|----------|------------|
| Framework | Laravel 12.x |
| Language | PHP 8.4+ |
| Cache/Store | Redis |
| Database | MySQL 8.x |
| Monitoring | Sentry |

## API Design (Planned)

### Sequence API

```
POST /api/v1/sequence/{key}/next
→ 다음 순번 발급

GET /api/v1/sequence/{key}/current
→ 현재 순번 조회

DELETE /api/v1/sequence/{key}
→ 순번 초기화
```

### Counter API

```
POST /api/v1/counter/{key}/increment
→ 카운터 증가

POST /api/v1/counter/{key}/decrement
→ 카운터 감소

GET /api/v1/counter/{key}
→ 현재 값 조회
```

## Development

### Requirements

- PHP 8.4+
- Composer
- Redis
- MySQL 8.x

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
│   ├── Services/            # Core Business Logic (예정)
│   │   ├── Sequence/        # Sequence Generator
│   │   └── Counter/         # Counter Service
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
