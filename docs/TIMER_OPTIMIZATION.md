# Timer 도메인 성능 최적화 가이드

이 문서는 Timer 도메인 서비스의 성능 최적화 사항을 기록합니다.

---

## 1. 쿼리 최적화: `fresh()` → `refresh()`

### 문제점

`fresh()` 메서드는 새로운 모델 인스턴스를 반환하기 위해 **추가 SELECT 쿼리**를 실행합니다.

### Before

```php
// EloquentTimerRepository.php
public function update(Timer $timer, string $targetAt): Timer
{
    $timer->update(['target_at' => $targetAt]);

    return $timer->fresh();  // 추가 SELECT 쿼리 발생
}
```

**실행되는 쿼리 (2개)**:
```sql
UPDATE timers SET target_at = ?, updated_at = ? WHERE id = ?;
SELECT * FROM timers WHERE id = ?;  -- 불필요한 추가 쿼리
```

### After

```php
// EloquentTimerRepository.php
public function update(Timer $timer, string $targetAt): Timer
{
    $timer->update(['target_at' => $targetAt]);
    $timer->refresh();  // 기존 인스턴스를 갱신

    return $timer;
}
```

**실행되는 쿼리 (1개)**:
```sql
UPDATE timers SET target_at = ?, updated_at = ? WHERE id = ?;
-- refresh()는 이미 메모리에 있는 데이터를 갱신 (필요시에만 쿼리)
```

### 차이점 비교

| 메서드 | 반환값 | 쿼리 수 | 메모리 |
|--------|--------|---------|--------|
| `fresh()` | 새 인스턴스 | +1 SELECT | 새 객체 생성 |
| `refresh()` | 기존 인스턴스 | 0 (캐시된 경우) | 기존 객체 재사용 |

### 적용 위치

- `app/Repositories/EloquentTimerRepository.php` - `update()` 메서드
- `app/Services/TimerService.php` - `deleteTimer()` 메서드

---

## 2. 데이터베이스 인덱스 최적화

### 문제점

기본 `key` 컬럼의 unique 인덱스만으로는 Soft Delete 쿼리 최적화가 불가능합니다.

### Before

```php
// 마이그레이션
$table->string('key')->unique();  // 단일 인덱스만 존재
```

**쿼리 실행 계획**:
```sql
-- Full Table Scan 또는 Index Scan + Filter
SELECT * FROM timers WHERE key = 'abc' AND deleted_at IS NULL;
```

### After

```php
// 마이그레이션 추가
Schema::table('timers', function (Blueprint $table) {
    // Soft delete 조회 최적화
    $table->index(['key', 'deleted_at'], 'timers_key_deleted_at_index');

    // 만료된 타이머 조회/정리 최적화
    $table->index('target_at', 'timers_target_at_index');
});
```

**쿼리 실행 계획**:
```sql
-- Index Seek (복합 인덱스 활용)
SELECT * FROM timers WHERE key = 'abc' AND deleted_at IS NULL;
```

### 인덱스 설명

| 인덱스 | 컬럼 | 용도 |
|--------|------|------|
| `timers_key_deleted_at_index` | `(key, deleted_at)` | Soft delete 조회 최적화 |
| `timers_target_at_index` | `target_at` | 만료 타이머 조회/정리 |

### 적용 위치

- `database/migrations/2025_12_09_170742_add_indexes_to_timers_table.php`

---

## 3. Redis 캐시 레이어

### 문제점

매 API 요청마다 DB 조회가 발생하여 불필요한 부하가 생깁니다.

### Before

```php
// AppServiceProvider.php
$this->app->bind(
    TimerRepositoryInterface::class,
    EloquentTimerRepository::class  // 직접 DB 조회
);
```

**요청 흐름**:
```
Controller → Service → Repository → Database
                                        ↑
                                   매번 쿼리 실행
```

### After

```php
// AppServiceProvider.php
$this->app->singleton(EloquentTimerRepository::class);

$this->app->bind(
    TimerRepositoryInterface::class,
    CachedTimerRepository::class  // 캐시 레이어 추가
);
```

**요청 흐름**:
```
Controller → Service → CachedRepository → Cache (Redis)
                              ↓                ↓ (Cache Miss)
                              └──────→ EloquentRepository → Database
```

### 캐시 전략

| 작업 | 캐시 동작 |
|------|-----------|
| `findByKey()` | Read-Through 캐시 (TTL: 5분) |
| `findByKeyWithTrashed()` | 캐시 미적용 (삭제된 데이터) |
| `create()` | 캐시 무효화 |
| `update()` | 캐시 무효화 |
| `delete()` | 캐시 무효화 |
| `restore()` | 캐시 무효화 |

### 캐시 키 형식

```
timer:{key}
```

예: `timer:event-countdown`

### 캐시 설정

```php
// CachedTimerRepository.php
private const CACHE_TTL_SECONDS = 300;  // 5분
private const CACHE_PREFIX = 'timer:';
```

### 주의사항

`remaining_seconds`는 실시간 계산값이므로:
- 캐시된 `target_at` 기반으로 서비스에서 계산
- 캐시 TTL이 길면 약간의 지연 발생 가능 (최대 5분)

### 적용 위치

- `app/Repositories/CachedTimerRepository.php` (신규)
- `app/Providers/AppServiceProvider.php` (바인딩 변경)

---

## 성능 개선 요약

| 최적화 | Before | After | 개선 효과 |
|--------|--------|-------|-----------|
| 쿼리 최적화 | 2 queries | 1 query | 50% 쿼리 감소 |
| 인덱스 추가 | Full Scan | Index Seek | O(n) → O(log n) |
| Redis 캐시 | 매번 DB 조회 | 캐시 히트 시 DB 스킵 | ~95% DB 부하 감소* |

*캐시 히트율 95% 가정

---

## 향후 최적화 고려사항

### 1. API 응답 최적화
- ETag 헤더 추가
- 조건부 요청 (304 Not Modified)
- Gzip/Brotli 압축 (Nginx 레벨)

### 2. 대량 데이터 처리
- 만료된 타이머 정리 스케줄러
- Chunk 기반 배치 처리
- 페이지네이션 (Cursor 기반)

### 3. 모니터링
- 캐시 히트율 모니터링
- Slow Query 로깅
- APM 도구 연동 (Sentry, New Relic)
