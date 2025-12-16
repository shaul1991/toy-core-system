# n8n - Workflow Automation Platform

> **프로젝트 목적:** 코드의 유연성과 노코드의 속도를 결합한 워크플로우 자동화 플랫폼
> **프로젝트 적용:** Domain Event 기반 자동화, 외부 서비스 통합, 알림 확장

---

## 개요

| 항목 | 설명 |
|------|------|
| **프로젝트명** | n8n |
| **저장소** | [GitHub](https://github.com/n8n-io/n8n) |
| **공식 문서** | [docs.n8n.io](https://docs.n8n.io) |
| **공식 사이트** | [n8n.io](https://n8n.io) |
| **라이선스** | Fair-code (Sustainable Use License) |
| **Stars** | 49k+ |
| **통합 수** | 400+ |
| **주요 언어** | TypeScript (90.5%), Vue (7.8%) |
| **런타임** | Node.js 18+ |

---

## 주요 특징

### 1. 코드와 노코드의 결합

시각적 워크플로우 빌더와 JavaScript/Python 코드를 함께 사용 가능:

```javascript
// Code Node에서 JavaScript 실행
const items = $input.all();
return items.map(item => ({
  json: {
    ...item.json,
    processed: true,
    timestamp: new Date().toISOString()
  }
}));
```

### 2. 400개+ 통합 지원

- **커뮤니케이션**: Slack, Discord, Telegram, Email
- **클라우드**: AWS, Google Cloud, Azure
- **데이터베이스**: PostgreSQL, MySQL, MongoDB, Redis
- **CRM**: Salesforce, HubSpot
- **개발 도구**: GitHub, GitLab, Jira
- **AI**: OpenAI, Anthropic, Google AI
- **Custom Webhook**: HTTP Request/Webhook 노드

### 3. AI 네이티브 (LangChain 기반)

AI 에이전트 워크플로우 구축:

```
User Input → AI Agent → Tool Selection → Execute → Response
```

### 4. 자체 호스팅 (Self-Hosted)

- Docker, NPM, Kubernetes 배포 지원
- 데이터 완전 제어
- 엔터프라이즈 기능 (SSO, RBAC, 에어갭)

### 5. Fair-code 라이선스

- ✅ 소스 코드 공개
- ✅ 자체 호스팅 무료
- ✅ 무제한 워크플로우
- ❌ 상업적 재판매 제한

---

## 설치 및 요구사항

### Docker로 설치 (권장)

```bash
docker run -it --rm \
  --name n8n \
  -p 5678:5678 \
  -v ~/.n8n:/home/node/.n8n \
  n8nio/n8n
```

### Docker Compose

```yaml
# docker-compose.yml
version: '3.8'
services:
  n8n:
    image: n8nio/n8n
    restart: always
    ports:
      - "5678:5678"
    environment:
      - N8N_BASIC_AUTH_ACTIVE=true
      - N8N_BASIC_AUTH_USER=admin
      - N8N_BASIC_AUTH_PASSWORD=password
      - N8N_HOST=localhost
      - N8N_PORT=5678
      - N8N_PROTOCOL=http
      - NODE_ENV=production
      - WEBHOOK_URL=http://localhost:5678/
    volumes:
      - n8n_data:/home/node/.n8n

volumes:
  n8n_data:
```

```bash
docker-compose up -d
```

### NPM 설치

```bash
npm install n8n -g
n8n start
```

### 필수 요구사항

| 항목 | 버전 | 필수 여부 |
|------|------|----------|
| **Node.js** | 18+ | ✅ 필수 |
| **Docker** | 20+ | 🔵 선택 (Docker 배포 시) |
| **PostgreSQL** | 12+ | 🔵 선택 (Production) |
| **Redis** | 6+ | 🔵 선택 (큐 사용 시) |

---

## 기본 사용법

### 1. 워크플로우 생성

1. 웹 UI 접속: `http://localhost:5678`
2. **Create Workflow** 클릭
3. 노드 추가 (Trigger + Action)

### 2. Webhook 트리거

```
Webhook → Filter → HTTP Request → Slack Notification
```

**Webhook 노드 설정:**
- Method: POST
- Path: `webhook/user-created`
- Response: Immediately

**요청 예시:**
```bash
curl -X POST http://localhost:5678/webhook/user-created \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 123,
    "email": "test@example.com",
    "name": "John Doe"
  }'
```

### 3. Code Node (JavaScript)

```javascript
// 입력 데이터 가공
const input = $input.item.json;

return {
  json: {
    user_id: input.user_id,
    email: input.email,
    full_name: input.name,
    created_at: new Date().toISOString(),
    notification_sent: true
  }
};
```

### 4. 조건 분기 (IF Node)

```
Webhook → IF (Check Email Domain)
  ├─ True → Send to Slack #internal
  └─ False → Send to Slack #customers
```

---

## 실전 예제

### 1. 사용자 가입 시 환영 이메일 발송

**워크플로우:**
```
Webhook (User Created) → Email Node → Slack Notification
```

**Email 노드 설정:**
```json
{
  "to": "{{ $json.email }}",
  "subject": "Welcome to Our Service!",
  "text": "Hi {{ $json.name }}, welcome aboard!"
}
```

### 2. GitHub 이슈 생성 시 Slack 알림

**워크플로우:**
```
Webhook (GitHub) → Filter (Action = opened) → Slack
```

**Slack 메시지:**
```
New Issue: {{ $json.issue.title }}
Created by: {{ $json.issue.user.login }}
Link: {{ $json.issue.html_url }}
```

### 3. 일정 시간마다 데이터베이스 백업

**워크플로우:**
```
Schedule (Cron) → Postgres (Export) → AWS S3 (Upload) → Slack
```

**Cron 설정:**
```
0 2 * * * (매일 새벽 2시)
```

### 4. AI 기반 고객 문의 자동 분류

**워크플로우:**
```
Webhook (Support Ticket) → OpenAI (Classify) → Route by Category
  ├─ Technical → Assign to Dev Team
  ├─ Billing → Assign to Finance
  └─ General → Assign to CS Team
```

---

## Laravel 프로젝트 통합

### 1. Webhook 발송 (Domain Event)

```php
// app/Domain/Auth/Listeners/NotifyN8n.php
namespace App\Domain\Auth\Listeners;

use App\Domain\Auth\Events\UserCreated;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyN8n implements ShouldQueue
{
    public function handle(UserCreated $event): void
    {
        Http::post(config('services.n8n.webhook_url') . '/user-created', [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'name' => $event->user->name,
            'provider' => $event->user->provider,
            'created_at' => $event->user->created_at->toISOString(),
        ]);
    }
}
```

**EventServiceProvider 등록:**
```php
protected $listen = [
    UserCreated::class => [
        SendWelcomeEmail::class,
        CreateAuthLog::class,
        NotifyN8n::class, // ← n8n 웹훅 발송
    ],
];
```

### 2. n8n Config 설정

```php
// config/services.php
return [
    'n8n' => [
        'webhook_url' => env('N8N_WEBHOOK_URL', 'http://localhost:5678/webhook'),
        'enabled' => env('N8N_ENABLED', false),
    ],
];
```

```bash
# .env
N8N_ENABLED=true
N8N_WEBHOOK_URL=http://n8n.example.com/webhook
```

### 3. Webhook Controller (n8n → Laravel)

```php
// app/Http/Controllers/Internal/N8nWebhookController.php
namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class N8nWebhookController extends Controller
{
    public function handle(Request $request, string $event): JsonResponse
    {
        // n8n에서 처리 결과 수신
        \Log::info("n8n webhook received: {$event}", $request->all());

        return response()->json([
            'success' => true,
            'received_at' => now()->toISOString(),
        ]);
    }
}
```

**Route 등록:**
```php
// routes/internal.php
Route::post('/webhooks/n8n/{event}', [N8nWebhookController::class, 'handle']);
```

---

## 프로젝트 도메인 확장 시 활용

### 1. Auth 도메인 - 사용자 가입 자동화

**Event:**
```php
event(new UserCreated($user));
```

**n8n 워크플로우:**
```
Webhook (UserCreated)
  → Send Welcome Email
  → Create CRM Contact (HubSpot)
  → Update Analytics (Google Sheets)
  → Notify Team (Slack)
```

### 2. Post 도메인 - 블로그 게시물 발행 자동화

**Event:**
```php
event(new PostPublished($post));
```

**n8n 워크플로우:**
```
Webhook (PostPublished)
  → Generate Summary (OpenAI)
  → Post to Twitter
  → Post to LinkedIn
  → Notify Subscribers (Email)
  → Update RSS Feed
```

### 3. File 도메인 - 이미지 처리 파이프라인

**Event:**
```php
event(new FileUploaded($file));
```

**n8n 워크플로우:**
```
Webhook (FileUploaded)
  → Download Image (HTTP Request)
  → Resize (ImageMagick)
  → Upload to S3
  → Update Database (PostgreSQL)
  → Generate Thumbnail (Spatie Image)
  → Notify User (Email)
```

### 4. Timer 도메인 - 목표 만료 알림

**Event:**
```php
event(new TimerExpired($timer));
```

**n8n 워크플로우:**
```
Webhook (TimerExpired)
  → Check User Preference
  → IF (Email Enabled) → Send Email
  → IF (SMS Enabled) → Send SMS (Twilio)
  → IF (Push Enabled) → Send Push (Firebase)
  → Log Activity (MongoDB)
```

### 5. Notification 도메인 - 다채널 알림 확장

현재 프로젝트의 Notification 도메인을 n8n으로 대체/확장:

**기존 방식:**
```php
// app/Services/Notification/NotificationService.php
$this->notificationService->send([
    'channels' => ['email', 'sms', 'slack'],
    'to' => $user,
    'message' => 'Your order has been shipped!',
]);
```

**n8n 통합 방식:**
```php
// n8n Webhook으로 전송
Http::post(config('services.n8n.webhook_url') . '/notification', [
    'user_id' => $user->id,
    'channels' => ['email', 'sms', 'slack'],
    'message' => 'Your order has been shipped!',
    'metadata' => [
        'order_id' => $order->id,
        'tracking_number' => $order->tracking_number,
    ],
]);
```

**n8n 워크플로우:**
```
Webhook (Notification)
  → Get User Preferences (Database)
  → IF (Email in channels)
      → Send Email (SendGrid/SMTP)
  → IF (SMS in channels)
      → Send SMS (Twilio)
  → IF (Slack in channels)
      → Post to Slack
  → Log Notification (MongoDB)
  → Update Stats (Redis)
```

**장점:**
- ✅ 채널 추가 시 코드 수정 불필요 (n8n 워크플로우만 수정)
- ✅ 시각적 워크플로우로 복잡한 로직 파악 용이
- ✅ 재시도, 에러 핸들링 등 n8n이 자동 처리
- ✅ 실시간 모니터링 및 디버깅

---

## Event-Driven Architecture와 통합

**CLAUDE.md**에서 우선순위로 언급된 Domain Event와 n8n 연계:

### 1. 이벤트 발행 → n8n Webhook

```php
// app/Shared/Events/DomainEvent.php (예정)
abstract class DomainEvent
{
    public function toN8nPayload(): array
    {
        return [
            'event_type' => class_basename($this),
            'occurred_at' => now()->toISOString(),
            'data' => $this->toArray(),
        ];
    }
}
```

### 2. 공통 Listener

```php
// app/Shared/Listeners/DispatchToN8n.php
namespace App\Shared\Listeners;

use App\Shared\Events\DomainEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Queue\ShouldQueue;

class DispatchToN8n implements ShouldQueue
{
    public function handle(DomainEvent $event): void
    {
        if (!config('services.n8n.enabled')) {
            return;
        }

        $eventType = class_basename($event);

        Http::post(
            config('services.n8n.webhook_url') . '/' . kebab_case($eventType),
            $event->toN8nPayload()
        );
    }
}
```

### 3. 모든 도메인 이벤트 자동 전송

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    '*' => [
        DispatchToN8n::class, // 모든 이벤트를 n8n으로 전송
    ],
];
```

---

## 성능 고려사항

### 1. Webhook 타임아웃 설정

```php
// n8n Webhook은 비동기로 호출
Http::timeout(3)
    ->retry(2, 100)
    ->post($webhookUrl, $data);
```

### 2. Queue 사용 (필수)

```php
// Listener는 반드시 ShouldQueue 구현
class NotifyN8n implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $timeout = 30;
}
```

### 3. n8n Queue Mode 활성화

```bash
# Production 환경에서는 Queue Mode 사용
N8N_EXECUTIONS_MODE=queue
N8N_QUEUE_BULL_REDIS_HOST=redis
N8N_QUEUE_BULL_REDIS_PORT=6379
```

### 4. Webhook 인증

```php
// HMAC 서명 검증
$signature = hash_hmac('sha256', $request->getContent(), config('services.n8n.secret'));

if (!hash_equals($signature, $request->header('X-N8N-Signature'))) {
    abort(401, 'Invalid signature');
}
```

---

## 자주 사용하는 패턴

### 1. Retry 로직

```javascript
// n8n Code Node - Retry with Exponential Backoff
const maxRetries = 3;
const retryDelay = 1000; // 1초

for (let i = 0; i < maxRetries; i++) {
  try {
    const response = await $http.request({
      url: 'https://api.example.com/endpoint',
      method: 'POST',
      body: $json,
    });
    return response;
  } catch (error) {
    if (i === maxRetries - 1) throw error;
    await new Promise(resolve => setTimeout(resolve, retryDelay * Math.pow(2, i)));
  }
}
```

### 2. 배치 처리

```
Schedule (Every 5 min)
  → Database (SELECT pending items)
  → Split In Batches (100 items)
  → Process Each Batch
  → Update Status
```

### 3. 에러 알림

```
Webhook → Try
  ├─ Success → Continue Workflow
  └─ Error → Slack Error Notification
```

### 4. 조건부 실행

```javascript
// IF Node - JavaScript 표현식
{{ $json.user.role === 'admin' && $json.amount > 1000 }}
```

---

## 보안 고려사항

### 1. Webhook Secret 검증

```php
// Laravel Middleware
class VerifyN8nSignature
{
    public function handle(Request $request, Closure $next)
    {
        $signature = $request->header('X-N8N-Signature');
        $secret = config('services.n8n.secret');

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        if (!hash_equals($expected, $signature)) {
            abort(401, 'Invalid webhook signature');
        }

        return $next($request);
    }
}
```

### 2. IP 화이트리스트

```php
// Middleware
class RestrictN8nIp
{
    protected $allowedIps = [
        '10.0.0.0/8',
        '172.16.0.0/12',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (!in_array($request->ip(), $this->allowedIps)) {
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}
```

### 3. Rate Limiting

```php
// routes/internal.php
Route::middleware(['throttle:100,1'])->group(function () {
    Route::post('/webhooks/n8n/{event}', [N8nWebhookController::class, 'handle']);
});
```

---

## 모니터링 및 디버깅

### 1. n8n Execution Logs

웹 UI에서 모든 워크플로우 실행 기록 확인:
- 입력/출력 데이터
- 실행 시간
- 에러 스택 추적

### 2. Laravel 로깅

```php
// n8n 호출 전후 로깅
\Log::info('Dispatching to n8n', [
    'event' => $eventType,
    'payload' => $payload,
]);

Http::post($webhookUrl, $payload);

\Log::info('n8n dispatch completed');
```

### 3. Sentry 통합

```javascript
// n8n Code Node - Sentry 에러 추적
const Sentry = require('@sentry/node');

try {
  // 작업 수행
} catch (error) {
  Sentry.captureException(error);
  throw error;
}
```

---

## 프로젝트 적용 로드맵

### Phase 1: 기본 통합 (1-2일)

- [x] n8n Docker 설치 및 실행
- [x] Webhook 엔드포인트 설정
- [x] 테스트 워크플로우 생성 (UserCreated → Slack)
- [x] Laravel에서 n8n Webhook 호출 테스트

### Phase 2: Domain Event 연동 (3-5일)

- [ ] `DispatchToN8n` 공통 Listener 구현
- [ ] Auth 도메인 이벤트 연동
  - [ ] UserCreated → Welcome Email
  - [ ] UserLoggedIn → Activity Log
- [ ] Post 도메인 이벤트 연동
  - [ ] PostPublished → Social Media
  - [ ] PostUpdated → Notify Subscribers
- [ ] File 도메인 이벤트 연동
  - [ ] FileUploaded → Image Processing

### Phase 3: 고급 기능 (1주)

- [ ] AI 기반 워크플로우
  - [ ] 게시물 자동 요약 (OpenAI)
  - [ ] 이미지 태깅 (Vision API)
  - [ ] 스팸 감지 (AI Classification)
- [ ] 배치 처리
  - [ ] 일일 리포트 생성
  - [ ] 주간 통계 발송
- [ ] 외부 서비스 연동
  - [ ] CRM (HubSpot)
  - [ ] Analytics (Google Analytics)
  - [ ] Social Media (Twitter, LinkedIn)

### Phase 4: Production 준비 (3-5일)

- [ ] Queue Mode 활성화 (Redis)
- [ ] Webhook 서명 검증
- [ ] Rate Limiting 설정
- [ ] 에러 알림 (Slack, PagerDuty)
- [ ] 모니터링 대시보드 (Grafana)
- [ ] 문서화 (Workflow 목록, Webhook 스펙)

---

## 테스트 예시

### 1. Feature Test - n8n Webhook 발송

```php
// tests/Feature/Domain/Auth/N8nWebhookTest.php
namespace Tests\Feature\Domain\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Domain\Auth\Events\UserCreated;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class N8nWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_created_event_dispatches_to_n8n(): void
    {
        // Given: n8n 활성화
        config(['services.n8n.enabled' => true]);
        Http::fake();

        // When: 사용자 생성
        $user = User::factory()->create();
        event(new UserCreated($user));

        // Then: n8n Webhook 호출됨
        Http::assertSent(function ($request) use ($user) {
            return $request->url() === config('services.n8n.webhook_url') . '/user-created'
                && $request['user_id'] === $user->id
                && $request['email'] === $user->email;
        });
    }

    public function test_n8n_disabled_does_not_send_webhook(): void
    {
        // Given: n8n 비활성화
        config(['services.n8n.enabled' => false]);
        Http::fake();

        // When: 사용자 생성
        $user = User::factory()->create();
        event(new UserCreated($user));

        // Then: Webhook 호출되지 않음
        Http::assertNothingSent();
    }
}
```

### 2. Integration Test - n8n Workflow

```bash
# n8n CLI를 사용한 워크플로우 테스트
n8n execute --id=<workflow-id> --data='{"user_id": 123}'
```

---

## 대안 및 비교

| 플랫폼 | 오픈소스 | 자체 호스팅 | 통합 수 | AI 지원 | 가격 |
|--------|----------|-------------|---------|---------|------|
| **n8n** | ✅ Fair-code | ✅ | 400+ | ✅ LangChain | Free (Self) |
| Zapier | ❌ | ❌ | 7000+ | ❌ | $19.99~/mo |
| Make (Integromat) | ❌ | ❌ | 1500+ | 🔵 Limited | $9~/mo |
| Pipedream | 🔵 Partial | 🔵 | 2000+ | ✅ | Free tier |
| Apache Airflow | ✅ | ✅ | Custom | ❌ | Free |

**n8n 선택 이유:**
- ✅ 자체 호스팅으로 데이터 제어
- ✅ Laravel과 자연스러운 Webhook 통합
- ✅ AI/LLM 네이티브 지원
- ✅ 시각적 워크플로우 + 코드 유연성
- ✅ Fair-code 라이선스 (무료 사용 가능)

---

## 참고 링크

### 공식 문서 및 저장소

- [GitHub Repository](https://github.com/n8n-io/n8n)
- [Official Documentation](https://docs.n8n.io)
- [n8n Community](https://community.n8n.io)
- [Workflow Templates](https://n8n.io/workflows)

### 한국어 가이드

- [n8n 완벽가이드 | 초보자도 1시간 만에 업무 자동화 시작하기](https://www.magicaiprompts.com/docs/automation/n8n-usage-guide/)
- [N8n: 강력한 워크플로 자동화 도구, 활용법 및 실전 예제](https://www.jiniai.biz/2025/02/02/n8n-강력한-워크플로-자동화-도구-활용법-및-실전-예제/)
- [워크플로 자동화로 업무 효율 향상하기(with n8n)](https://insight.infograb.net/blog/2024/07/31/workflow-n8n/)

### 영문 가이드

- [The Top 15 n8n Use Cases That Are Revolutionizing Workflow Automation in 2025](https://medium.com/@aminsiddique95/the-top-15-n8n-use-cases-that-are-revolutionizing-workflow-automation-in-2025-cbe08df08702)
- [15 N8N Workflow Examples 2025](https://latenode.com/blog/15-n8n-workflow-examples-2025-real-automation-templates-implementation-analysis)

### 통합 예제

- [n8n + Laravel Integration](https://docs.n8n.io/integrations/builtin/app-nodes/n8n-nodes-base.webhook/)
- [n8n + PostgreSQL](https://docs.n8n.io/integrations/builtin/app-nodes/n8n-nodes-base.postgres/)
- [n8n + Redis](https://docs.n8n.io/integrations/builtin/app-nodes/n8n-nodes-base.redis/)

---

## 결론

**n8n**은 Laravel 기반 Domain Service 프로젝트에 워크플로우 자동화를 추가할 수 있는 강력한 플랫폼입니다.

현재 프로젝트의 **Event-Driven Architecture** 우선순위와 완벽하게 부합하며, Domain Event를 n8n Webhook으로 전달하여 복잡한 비즈니스 로직을 시각적으로 구성할 수 있습니다.

### 주요 장점

✅ **자체 호스팅으로 데이터 완전 제어**
✅ **400개+ 통합 (Slack, Email, SMS, AI, DB 등)**
✅ **Event-Driven Architecture와 자연스러운 통합**
✅ **코드 + 노코드 유연성**
✅ **AI/LLM 네이티브 지원 (LangChain)**
✅ **Fair-code 라이선스 (무료 자체 호스팅)**

### toy-core-system 프로젝트 적용 시나리오

1. **Auth 도메인**: UserCreated → Welcome Email + CRM + Analytics
2. **Post 도메인**: PostPublished → Social Media + Newsletter
3. **File 도메인**: FileUploaded → Image Processing + S3 Upload
4. **Timer 도메인**: TimerExpired → Multi-Channel Notification
5. **Notification 도메인**: n8n으로 채널 확장 (Telegram, Discord 등)

### Notification 도메인 대체 vs 확장

- **대체**: n8n이 모든 알림 로직 처리 (Laravel은 Webhook만 발송)
- **확장**: Laravel에서 기본 알림 + n8n으로 추가 채널/복잡한 로직

**권장:** 확장 방식 (Laravel의 간단한 알림 + n8n의 복잡한 워크플로우)

### 다음 단계

1. Docker Compose로 n8n 로컬 환경 구축
2. 테스트 워크플로우 생성 (UserCreated → Slack)
3. Laravel에서 n8n Webhook 호출 구현
4. Domain Event 공통 Listener 추가
5. Production 환경 배포 (Queue Mode, Webhook 서명)

---

**문서 버전:** 1.0
**작성일:** 2025-12-16
**마지막 업데이트:** 2025-12-16
