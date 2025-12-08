# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel 12 기반 웹 애플리케이션 프로젝트입니다.

- PHP 8.4+
- Laravel Framework 12.x
- Tailwind CSS 4.x (Vite 플러그인)
- PHPUnit 11.x

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

### Directory Structure

- `app/` - 애플리케이션 코어 코드 (Models, Http/Controllers, Providers)
- `bootstrap/app.php` - 애플리케이션 부트스트랩 및 미들웨어/예외처리 설정
- `routes/web.php` - 웹 라우트 정의
- `routes/console.php` - Artisan 콘솔 명령어 정의
- `database/migrations/` - 데이터베이스 마이그레이션
- `database/factories/` - 모델 팩토리 (테스트용)
- `tests/Feature/` - 기능 테스트
- `tests/Unit/` - 유닛 테스트

### Testing

테스트는 인메모리 SQLite 데이터베이스를 사용합니다 (`phpunit.xml` 참조).