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
│   ├── Services/            # Domain Logic
│   ├── Models/              # Eloquent Models
│   └── Providers/           # Service Providers
├── config/                  # Configuration
├── database/                # Migrations, Seeders
├── routes/
│   ├── api.php              # API Routes
│   └── web.php              # Web Routes
└── tests/                   # PHPUnit Tests
```

### Testing

테스트는 인메모리 SQLite 데이터베이스를 사용합니다 (`phpunit.xml` 참조).
