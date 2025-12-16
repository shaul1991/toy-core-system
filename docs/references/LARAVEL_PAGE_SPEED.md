# Laravel Page Speed - 웹 성능 최적화 미들웨어

> **패키지 목적:** Laravel 애플리케이션의 페이지 로딩 속도와 API 성능을 자동으로 최적화하는 미들웨어 패키지
> **프로젝트 적용:** 프론트엔드 렌더링 성능 개선, API 응답 압축 및 캐싱 최적화

---

## 개요

| 항목 | 설명 |
|------|------|
| **패키지명** | vinkius-labs/laravel-page-speed |
| **저장소** | [GitHub](https://github.com/vinkius-labs/laravel-page-speed) |
| **Packagist** | [packagist.org](https://packagist.org/packages/vinkius-labs/laravel-page-speed) |
| **라이선스** | MIT |
| **Stars** | 2.5k+ |
| **Contributors** | 23 |
| **Laravel 요구사항** | 10.x, 11.x, 12.x |
| **PHP 요구사항** | ^8.1+ |

---

## 주요 특징

### 1. 이중 최적화 범위 (Dual-Scope Optimization)

**웹 레이어 (Web Layer):**
- Blade 템플릿 렌더링 결과(HTML) 최적화
- 비즈니스 로직에 영향 없이 전송 포맷만 최적화

**API 레이어 (API Layer):**
- REST API 응답 압축 및 캐싱
- 성능 헤더 자동 추가

### 2. HTML 최적화

- 구조화된 HTML 축소화 (Minification)
- 주석 제거 (Comment Removal)
- Bootstrap, Tailwind CSS, Livewire와 호환
- 공백 제거 (Whitespace Collapse)
- 불필요한 HTML 속성 제거 (Elide Attributes)

### 3. CSS/JavaScript 최적화

- **Critical CSS Inlining**: 렌더 블로킹 네트워크 요청 감소
- **JavaScript 지연 로딩**: 실행 순서를 보장하는 `data-attribute` 가드
- **DNS Prefetching**: 외부 리소스 사전 연결

### 4. API 성능 최적화

**압축 (Compression):**
- Brotli 압축 우선 사용
- Gzip 폴백 지원
- 설정 가능한 압축 임계값 (작은 페이로드는 압축 제외)

**캐싱 (Caching):**
- HTTP 메서드 인식 캐시 무효화 로직
- URL 경로 세그먼트 기반 동적 캐시 태그 생성
- Redis, Memcached, DynamoDB, File, Array 캐시 드라이버 지원

**보안 헤더 (Security Headers):**
- HSTS (HTTP Strict Transport Security)
- CSP (Content Security Policy)
- Permissions-Policy

**고가용성 (High Availability):**
- 자동 Circuit Breaker (설정 가능한 실패 임계값)
- Kubernetes 통합을 위한 Health Check 미들웨어

---

## 성능 개선 지표

65% 캐시 히트율 기준 (100만 요청/일 시나리오):

| 지표 | 개선율 |
|------|--------|
| **페이지 크기 (HTML)** | -35% |
| **First Paint Time** | -33% |
| **API 페이로드** | -82% |
| **평균 API 지연시간** | -99.6% |
| **SQL 쿼리 (100개 아이템 리스트)** | -100% (캐시됨) |
| **월간 대역폭** | -80% |

---

## 설치 및 요구사항

### Composer 설치

```bash
composer require vinkius-labs/laravel-page-speed
php artisan vendor:publish --provider="VinkiusLabs\\LaravelPageSpeed\\ServiceProvider"
```

### 필수 요구사항

| 항목 | 버전 | 필수 여부 |
|------|------|----------|
| **Laravel** | 10.x, 11.x, 12.x | ✅ 필수 |
| **PHP** | 8.1+ | ✅ 필수 |
| **Cache Driver** | Redis, Memcached, DynamoDB, File, Array | ✅ 하나 이상 필수 (API 캐싱 시) |

### 환경 변수 설정 (권장)

```env
# Laravel Page Speed 활성화
LARAVEL_PAGE_SPEED_ENABLE=true

# API 캐싱 설정
API_CACHE_ENABLED=true
API_CACHE_DRIVER=redis
API_CACHE_TTL=300
API_CACHE_DYNAMIC_TAGS=true

# 압축 설정
API_COMPRESSION_ENABLED=true
API_COMPRESSION_MIN_SIZE=1024  # 1KB 이상만 압축

# Circuit Breaker 설정
API_CIRCUIT_BREAKER_ENABLED=true
API_CIRCUIT_BREAKER_THRESHOLD=10
API_CIRCUIT_BREAKER_TIMEOUT=60
```

---

## 미들웨어 구성

### Laravel 10.x 설정

`app/Http/Kernel.php`의 미들웨어 그룹에 등록:

```php
protected $middlewareGroups = [
    'web' => [
        // ... 기존 미들웨어
        \VinkiusLabs\LaravelPageSpeed\Middleware\InlineCss::class,
        \VinkiusLabs\LaravelPageSpeed\Middleware\ElideAttributes::class,
        \VinkiusLabs\LaravelPageSpeed\Middleware\InsertDNSPrefetch::class,
        \VinkiusLabs\LaravelPageSpeed\Middleware\CollapseWhitespace::class,
        \VinkiusLabs\LaravelPageSpeed\Middleware\DeferJavascript::class,
    ],

    'api' => [
        // ... 기존 미들웨어
        \VinkiusLabs\LaravelPageSpeed\Middleware\ApiSecurityHeaders::class,
        \VinkiusLabs\LaravelPageSpeed\Middleware\ApiResponseCache::class,
        \VinkiusLabs\LaravelPageSpeed\Middleware\ApiETag::class,
        \VinkiusLabs\LaravelPageSpeed\Middleware\ApiResponseCompression::class,
        \VinkiusLabs\LaravelPageSpeed\Middleware\ApiPerformanceHeaders::class,
        \VinkiusLabs\LaravelPageSpeed\Middleware\ApiCircuitBreaker::class,
        \VinkiusLabs\LaravelPageSpeed\Middleware\ApiHealthCheck::class,
    ],
];
```

### Laravel 11.x & 12.x 설정

`bootstrap/app.php`에서 설정:

```php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Web 미들웨어 그룹
        $middleware->appendToGroup('web', [
            \VinkiusLabs\LaravelPageSpeed\Middleware\InlineCss::class,
            \VinkiusLabs\LaravelPageSpeed\Middleware\ElideAttributes::class,
            \VinkiusLabs\LaravelPageSpeed\Middleware\InsertDNSPrefetch::class,
            \VinkiusLabs\LaravelPageSpeed\Middleware\CollapseWhitespace::class,
            \VinkiusLabs\LaravelPageSpeed\Middleware\DeferJavascript::class,
        ]);

        // API 미들웨어 그룹
        $middleware->appendToGroup('api', [
            \VinkiusLabs\LaravelPageSpeed\Middleware\ApiSecurityHeaders::class,
            \VinkiusLabs\LaravelPageSpeed\Middleware\ApiResponseCache::class,
            \VinkiusLabs\LaravelPageSpeed\Middleware\ApiETag::class,
            \VinkiusLabs\LaravelPageSpeed\Middleware\ApiResponseCompression::class,
            \VinkiusLabs\LaravelPageSpeed\Middleware\ApiPerformanceHeaders::class,
            \VinkiusLabs\LaravelPageSpeed\Middleware\ApiCircuitBreaker::class,
            \VinkiusLabs\LaravelPageSpeed\Middleware\ApiHealthCheck::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

---

## 미들웨어 상세 설명

### Web 레이어 미들웨어

| 미들웨어 | 기능 | 효과 |
|---------|------|------|
| **InlineCss** | Critical CSS를 `<style>` 태그로 인라인 | 렌더 블로킹 CSS 제거 |
| **ElideAttributes** | 불필요한 HTML 속성 제거 | HTML 크기 축소 |
| **InsertDNSPrefetch** | `<link rel="dns-prefetch">` 자동 삽입 | 외부 리소스 사전 연결 |
| **CollapseWhitespace** | HTML 공백 제거 | HTML 크기 축소 (35%) |
| **DeferJavascript** | `<script>` 태그에 `defer` 속성 추가 | 페이지 로딩 속도 개선 |

### API 레이어 미들웨어

| 미들웨어 | 기능 | 효과 |
|---------|------|------|
| **ApiSecurityHeaders** | HSTS, CSP, Permissions-Policy 헤더 추가 | 보안 강화 |
| **ApiResponseCache** | 응답 캐싱 (메서드별 무효화) | API 지연시간 99.6% 감소 |
| **ApiETag** | ETag 헤더 자동 생성 | 브라우저 캐싱 최적화 |
| **ApiResponseCompression** | Brotli/Gzip 압축 | 페이로드 82% 감소 |
| **ApiPerformanceHeaders** | `X-Response-Time`, `X-Memory-Usage` 등 | 모니터링 지원 |
| **ApiCircuitBreaker** | 실패 임계값 초과 시 서비스 차단 | 고가용성 보장 |
| **ApiHealthCheck** | DB, Redis, Disk, Queue 상태 확인 | Kubernetes 통합 |

---

## 설정 파일

`config/page-speed.php` (게시 후 생성됨):

```php
return [
    // 전역 활성화/비활성화
    'enable' => env('LARAVEL_PAGE_SPEED_ENABLE', true),

    // Web 최적화 설정
    'web' => [
        'inline_css' => true,
        'elide_attributes' => true,
        'dns_prefetch' => true,
        'collapse_whitespace' => true,
        'defer_javascript' => true,

        // 예외 경로 (최적화 제외)
        'skip' => [
            'debugbar/*',
            'telescope/*',
            'horizon/*',
        ],
    ],

    // API 최적화 설정
    'api' => [
        'cache' => [
            'enabled' => env('API_CACHE_ENABLED', true),
            'driver' => env('API_CACHE_DRIVER', 'redis'),
            'ttl' => env('API_CACHE_TTL', 300), // 5분
            'dynamic_tags' => env('API_CACHE_DYNAMIC_TAGS', true),
        ],

        'compression' => [
            'enabled' => env('API_COMPRESSION_ENABLED', true),
            'min_size' => env('API_COMPRESSION_MIN_SIZE', 1024), // 1KB
            'prefer_brotli' => true,
        ],

        'circuit_breaker' => [
            'enabled' => env('API_CIRCUIT_BREAKER_ENABLED', true),
            'threshold' => env('API_CIRCUIT_BREAKER_THRESHOLD', 10),
            'timeout' => env('API_CIRCUIT_BREAKER_TIMEOUT', 60), // 1분
        ],

        'performance_headers' => [
            'X-Response-Time' => true,
            'X-Memory-Usage' => true,
            'X-Cache-Status' => true,
            'X-Circuit-Breaker-State' => true,
        ],
    ],
];
```

---

## 실전 예제

### 1. 선택적 미들웨어 적용

특정 라우트에만 최적화 적용:

```php
// routes/web.php
Route::get('/blog', [BlogController::class, 'index'])
    ->middleware([
        'web',
        \VinkiusLabs\LaravelPageSpeed\Middleware\CollapseWhitespace::class,
        \VinkiusLabs\LaravelPageSpeed\Middleware\DeferJavascript::class,
    ]);
```

### 2. 특정 라우트에서 캐싱 제외

```php
// routes/api.php
Route::get('/real-time-data', [DataController::class, 'show'])
    ->withoutMiddleware(\VinkiusLabs\LaravelPageSpeed\Middleware\ApiResponseCache::class);
```

### 3. 커스텀 캐시 태그 사용

```php
use Illuminate\Support\Facades\Cache;

class ProductController extends Controller
{
    public function index()
    {
        $products = Cache::tags(['products', 'api'])
            ->remember('products.all', 300, function () {
                return Product::all();
            });

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $product = Product::create($request->validated());

        // 캐시 무효화
        Cache::tags(['products'])->flush();

        return response()->json($product, 201);
    }
}
```

### 4. Circuit Breaker 수동 제어

```php
use VinkiusLabs\LaravelPageSpeed\Facades\CircuitBreaker;

class PaymentController extends Controller
{
    public function process(Request $request)
    {
        if (CircuitBreaker::isOpen('payment-gateway')) {
            return response()->json([
                'error' => 'Payment service temporarily unavailable',
            ], 503);
        }

        try {
            $result = $this->paymentService->process($request->all());
            CircuitBreaker::recordSuccess('payment-gateway');

            return response()->json($result);
        } catch (\Exception $e) {
            CircuitBreaker::recordFailure('payment-gateway');

            throw $e;
        }
    }
}
```

---

## 성능 모니터링

### 성능 헤더 활용

미들웨어가 자동으로 추가하는 성능 헤더:

```http
HTTP/1.1 200 OK
Content-Type: application/json
X-Response-Time: 15ms
X-Memory-Usage: 2.5MB
X-Cache-Status: HIT
X-Circuit-Breaker-State: CLOSED
Content-Encoding: br
ETag: "abc123def456"
```

### Datadog/New Relic 통합

```php
// app/Http/Middleware/PerformanceMonitoring.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PerformanceMonitoring
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // 성능 헤더를 Datadog에 전송
        if ($response->headers->has('X-Response-Time')) {
            \Datadog::histogram('api.response_time',
                (float) str_replace('ms', '', $response->headers->get('X-Response-Time'))
            );
        }

        if ($response->headers->has('X-Cache-Status')) {
            \Datadog::increment('api.cache.' . strtolower($response->headers->get('X-Cache-Status')));
        }

        return $response;
    }
}
```

---

## 캐싱 전략

### 1. 동적 캐시 태그

URL 경로 세그먼트를 자동으로 캐시 태그로 사용:

```php
// GET /api/users/123/posts
// 자동 캐시 태그: ['api', 'users', '123', 'posts']

// 특정 사용자의 모든 캐시 무효화
Cache::tags(['users', '123'])->flush();
```

### 2. HTTP 메서드별 캐시 무효화

```php
// GET 요청: 캐시 사용
// POST, PUT, PATCH, DELETE: 캐시 무효화

// 예시: POST /api/posts
public function store(Request $request)
{
    $post = Post::create($request->validated());

    // ApiResponseCache 미들웨어가 자동으로 'posts' 태그 캐시 무효화
    return response()->json($post, 201);
}
```

### 3. 조건부 캐싱

특정 조건에서만 캐싱:

```php
// config/page-speed.php
'api' => [
    'cache' => [
        'enabled' => true,
        'skip_query_strings' => ['nocache', 'debug'],
    ],
],

// GET /api/products?nocache=1 → 캐시 제외
// GET /api/products → 캐시 사용
```

---

## 프로젝트 적용 시 고려사항

### 1. BFF 레이어에 적용

현재 프로젝트는 BFF (Backend For Frontend) 레이어를 가지고 있으므로:

```php
// routes/api.php (BFF Routes)
Route::middleware(['api', 'jwt.auth'])->prefix('api')->group(function () {
    // BFF 레이어에 API 최적화 미들웨어 적용
    Route::get('/posts', [PostController::class, 'index']); // 캐싱 + 압축
    Route::get('/timers', [TimerController::class, 'index']); // 캐싱 + 압축
});
```

### 2. Core Service Layer는 캐싱 제외

Core Service (Internal API)는 캐싱하지 않고 BFF에서만 캐싱:

```php
// routes/internal.php (Core Service Routes)
Route::prefix('internal')->group(function () {
    // Core Service는 ApiResponseCache 미들웨어 제외
    Route::get('/posts/{id}', [PostController::class, 'show'])
        ->withoutMiddleware(\VinkiusLabs\LaravelPageSpeed\Middleware\ApiResponseCache::class);
});
```

### 3. Swagger UI는 최적화 제외

```php
// config/page-speed.php
'web' => [
    'skip' => [
        'swagger/*',
        'api-docs/*',
        'debugbar/*',
    ],
],
```

### 4. Health Check 엔드포인트

Kubernetes/Docker 헬스 체크 통합:

```php
// routes/api.php
Route::get('/health', function () {
    // ApiHealthCheck 미들웨어가 자동으로 DB, Redis, Queue 상태 확인
    return response()->json(['status' => 'healthy']);
})->middleware(\VinkiusLabs\LaravelPageSpeed\Middleware\ApiHealthCheck::class);
```

---

## 성능 최적화 팁

### 1. Redis 캐시 드라이버 사용

```env
# .env
API_CACHE_DRIVER=redis
REDIS_CLIENT=phpredis  # predis보다 빠름
```

### 2. Brotli 압축 우선 사용

Brotli는 Gzip보다 15-20% 압축률이 높음:

```env
API_COMPRESSION_ENABLED=true
API_COMPRESSION_MIN_SIZE=1024  # 1KB 이상만 압축
```

### 3. 캐시 TTL 조정

데이터 특성에 따라 TTL 조정:

```php
// 자주 변경되지 않는 데이터: 긴 TTL
Cache::tags(['categories'])->remember('categories.all', 3600, function () {
    return Category::all();
});

// 실시간 데이터: 짧은 TTL 또는 캐싱 제외
Cache::tags(['real-time'])->remember('stock.price', 10, function () {
    return StockPrice::latest();
});
```

### 4. 압축 임계값 최적화

작은 응답은 압축 오버헤드가 더 큼:

```env
# 1KB 미만은 압축하지 않음 (기본값)
API_COMPRESSION_MIN_SIZE=1024
```

---

## 자주 사용하는 패턴

### 1. 페이지네이션 캐싱

```php
class PostController extends Controller
{
    public function index(Request $request)
    {
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 15);

        $cacheKey = "posts.page.{$page}.per_page.{$perPage}";

        $posts = Cache::tags(['posts', 'api'])->remember($cacheKey, 300, function () use ($perPage) {
            return Post::paginate($perPage);
        });

        return response()->json($posts);
    }
}
```

### 2. 캐시 워밍 (Cache Warming)

```php
// app/Console/Commands/WarmCache.php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WarmCache extends Command
{
    protected $signature = 'cache:warm';

    public function handle()
    {
        // 자주 사용되는 API 응답 사전 캐싱
        $this->warmPostsCache();
        $this->warmCategoriesCache();

        $this->info('Cache warmed successfully!');
    }

    private function warmPostsCache(): void
    {
        Cache::tags(['posts'])->remember('posts.all', 300, function () {
            return \App\Models\Post::all();
        });
    }

    private function warmCategoriesCache(): void
    {
        Cache::tags(['categories'])->remember('categories.all', 3600, function () {
            return \App\Models\Category::all();
        });
    }
}
```

### 3. Circuit Breaker 패턴

```php
class ExternalApiService
{
    public function call(string $endpoint)
    {
        $circuitKey = "circuit.{$endpoint}";

        if (CircuitBreaker::isOpen($circuitKey)) {
            throw new ServiceUnavailableException('Service temporarily unavailable');
        }

        try {
            $response = Http::get($endpoint);
            CircuitBreaker::recordSuccess($circuitKey);

            return $response->json();
        } catch (\Exception $e) {
            CircuitBreaker::recordFailure($circuitKey);

            throw $e;
        }
    }
}
```

---

## 디버깅 및 트러블슈팅

### 1. 캐시 상태 확인

```bash
# Redis 캐시 키 확인
redis-cli KEYS "*api*"

# 특정 캐시 태그 확인
redis-cli KEYS "*posts*"

# 캐시 플러시
php artisan cache:clear
php artisan cache:clear --tags=posts,api
```

### 2. 성능 헤더 확인

```bash
# cURL로 성능 헤더 확인
curl -I http://localhost:8000/api/posts

# 압축 확인
curl -H "Accept-Encoding: br, gzip" -I http://localhost:8000/api/posts
```

### 3. Circuit Breaker 상태 확인

```bash
# Tinker에서 확인
php artisan tinker

>>> \VinkiusLabs\LaravelPageSpeed\Facades\CircuitBreaker::getState('payment-gateway');
=> "CLOSED"
```

---

## 관련 패키지

| 패키지 | 용도 | 저장소 |
|--------|------|--------|
| **spatie/laravel-responsecache** | Laravel 응답 캐싱 패키지 | [GitHub](https://github.com/spatie/laravel-responsecache) |
| **intervention/httpauth** | HTTP 인증 미들웨어 | [GitHub](https://github.com/Intervention/httpauth) |
| **barryvdh/laravel-debugbar** | Laravel 디버그바 (성능 모니터링) | [GitHub](https://github.com/barryvdh/laravel-debugbar) |

---

## 참고 링크

### 공식 문서 및 저장소

- [GitHub Repository](https://github.com/vinkius-labs/laravel-page-speed)
- [Packagist](https://packagist.org/packages/vinkius-labs/laravel-page-speed)

### 관련 아티클

- [Web Performance Optimization Best Practices](https://web.dev/performance/)
- [Brotli vs Gzip Compression](https://paulcalvano.com/2018-07-25-brotli-compression-how-much-will-it-reduce-your-content/)
- [Circuit Breaker Pattern](https://martinfowler.com/bliki/CircuitBreaker.html)
- [Laravel Performance Optimization](https://laravel-news.com/performance-optimization)

### 성능 측정 도구

- [Google PageSpeed Insights](https://pagespeed.web.dev/)
- [GTmetrix](https://gtmetrix.com/)
- [WebPageTest](https://www.webpagetest.org/)

---

## 프로젝트 적용 로드맵

### Phase 1: BFF API 최적화

- [ ] `vinkius-labs/laravel-page-speed` 패키지 설치
- [ ] BFF 레이어에 API 미들웨어 적용
- [ ] Redis 캐시 드라이버 설정
- [ ] 환경 변수 설정 (`.env`)

### Phase 2: 캐싱 전략 구현

- [ ] 자주 사용되는 API 엔드포인트 식별
- [ ] 캐시 TTL 및 태그 전략 수립
- [ ] 캐시 무효화 로직 구현
- [ ] Cache Warming 스케줄러 추가

### Phase 3: 성능 모니터링

- [ ] 성능 헤더를 Datadog/New Relic에 통합
- [ ] Circuit Breaker 패턴 적용 (외부 API 호출)
- [ ] Health Check 엔드포인트 추가
- [ ] 성능 대시보드 구축

### Phase 4: 프론트엔드 최적화 (Optional)

- [ ] Blade 템플릿이 있는 경우 Web 미들웨어 적용
- [ ] Critical CSS 인라인 적용
- [ ] JavaScript 지연 로딩 적용

---

## 테스트 예시

```php
// tests/Feature/Api/PerformanceTest.php
namespace Tests\Feature\Api;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class PerformanceTest extends TestCase
{
    public function test_api_response_is_compressed(): void
    {
        // Given: Brotli 압축 지원 헤더
        $response = $this->withHeaders([
            'Accept-Encoding' => 'br, gzip',
        ])->getJson('/api/posts');

        // Then: 응답이 압축됨
        $response->assertHeader('Content-Encoding');
        $this->assertContains(
            $response->headers->get('Content-Encoding'),
            ['br', 'gzip']
        );
    }

    public function test_api_response_is_cached(): void
    {
        // Given: 캐시가 비어있음
        Cache::tags(['posts'])->flush();

        // When: 첫 번째 요청
        $response1 = $this->getJson('/api/posts');
        $response1->assertHeader('X-Cache-Status', 'MISS');

        // Then: 두 번째 요청은 캐시에서 반환됨
        $response2 = $this->getJson('/api/posts');
        $response2->assertHeader('X-Cache-Status', 'HIT');
    }

    public function test_performance_headers_are_present(): void
    {
        $response = $this->getJson('/api/posts');

        $response->assertHeader('X-Response-Time');
        $response->assertHeader('X-Memory-Usage');
        $response->assertHeader('X-Cache-Status');
    }

    public function test_etag_header_is_generated(): void
    {
        // Given: ETag 헤더가 생성됨
        $response1 = $this->getJson('/api/posts');
        $etag = $response1->headers->get('ETag');

        $this->assertNotNull($etag);

        // When: 같은 ETag로 재요청
        $response2 = $this->withHeaders([
            'If-None-Match' => $etag,
        ])->getJson('/api/posts');

        // Then: 304 Not Modified 반환
        $response2->assertStatus(304);
    }

    public function test_circuit_breaker_opens_after_threshold(): void
    {
        $this->markTestSkipped('Circuit Breaker 통합 테스트는 수동 테스트 필요');

        // 실패 임계값 초과 시뮬레이션
        for ($i = 0; $i < 11; $i++) {
            try {
                $this->getJson('/api/external-service');
            } catch (\Exception $e) {
                // 예외 무시
            }
        }

        // Circuit Breaker가 열려야 함
        $response = $this->getJson('/api/external-service');
        $response->assertHeader('X-Circuit-Breaker-State', 'OPEN');
        $response->assertStatus(503);
    }
}
```

---

## 주의사항

### 1. 디버깅 도구는 최적화 제외

```php
// config/page-speed.php
'web' => [
    'skip' => [
        'debugbar/*',
        'telescope/*',
        'horizon/*',
        '_ignition/*',
    ],
],
```

### 2. 실시간 데이터는 캐싱 제외

```php
Route::get('/api/real-time-stock', [StockController::class, 'show'])
    ->withoutMiddleware(\VinkiusLabs\LaravelPageSpeed\Middleware\ApiResponseCache::class);
```

### 3. Circuit Breaker 임계값 조정

프로덕션 환경에서는 적절한 임계값 설정:

```env
# 개발 환경: 낮은 임계값 (빠른 테스트)
API_CIRCUIT_BREAKER_THRESHOLD=5

# 프로덕션 환경: 높은 임계값 (안정성)
API_CIRCUIT_BREAKER_THRESHOLD=50
```

### 4. CORS와 함께 사용 시

```php
// CORS 미들웨어가 먼저 실행되어야 함
'api' => [
    \Illuminate\Http\Middleware\HandleCors::class,
    \VinkiusLabs\LaravelPageSpeed\Middleware\ApiSecurityHeaders::class,
    // ... 나머지 미들웨어
],
```

---

## 결론

**Laravel Page Speed**는 Laravel 애플리케이션의 성능을 획기적으로 개선할 수 있는 강력한 미들웨어 패키지입니다.

현재 프로젝트의 **BFF 레이어**에 적용하면, API 응답 속도를 99.6% 개선하고, 대역폭을 80% 절감할 수 있습니다.

### 주요 장점

✅ 비즈니스 로직에 영향 없이 성능 개선
✅ HTML/CSS/JS 자동 최적화
✅ API 응답 압축 및 캐싱
✅ Circuit Breaker 패턴 내장
✅ 성능 모니터링 헤더 자동 추가
✅ Laravel 10.x, 11.x, 12.x 모두 지원
✅ MIT 라이선스 (상업적 사용 가능)

### 다음 단계

- BFF API 레이어에 미들웨어 적용
- Redis 캐시 드라이버 설정
- 성능 모니터링 대시보드 구축
- 캐시 무효화 전략 수립

---

**문서 버전:** 1.0
**작성일:** 2025-12-16
**마지막 업데이트:** 2025-12-16
