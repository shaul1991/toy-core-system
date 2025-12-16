# Laravel Reverb - 실시간 WebSocket 통신

> **패키지 목적:** Laravel 애플리케이션을 위한 고성능 실시간 WebSocket 서버
> **프로젝트 적용:** 실시간 알림, 채팅, 라이브 대시보드, 협업 도구, 실시간 데이터 업데이트

---

## 개요

| 항목 | 설명 |
|------|------|
| **패키지명** | laravel/reverb |
| **저장소** | [GitHub](https://github.com/laravel/reverb) |
| **공식 문서** | [Laravel Reverb Docs](https://laravel.com/docs/reverb) |
| **라이선스** | MIT |
| **Stars** | 1.5k+ |
| **Contributors** | 46 |
| **Laravel 요구사항** | 11.x, 12.x |
| **PHP 요구사항** | ^8.2+ |

---

## 주요 특징

### 1. 초고속 실시간 통신

**고성능 WebSocket 서버:**
- 단일 서버에서 수천 개의 동시 연결 지원
- HTTP 폴링 방식 대비 지연 시간 및 오버헤드 제거
- 비동기 I/O 기반 아키텍처

**Laravel Echo 완벽 통합:**
- Laravel Echo와 네이티브 통합
- 기존 Broadcasting 이벤트와 호환
- 추가 코드 수정 없이 즉시 사용 가능

### 2. 수평 확장 (Horizontal Scaling)

**Redis 기반 확장:**
- 여러 Reverb 서버 간 연결 및 채널 관리
- Load Balancer를 통한 트래픽 분산
- 무제한 확장성 지원

**클러스터링:**
- 여러 노드 간 자동 동기화
- 채널 구독 정보 공유
- Sticky Session 불필요

### 3. 채널 유형

**Public Channels (공개 채널):**
- 인증 없이 누구나 구독 가능
- 공개 알림, 시스템 메시지 등

**Private Channels (비공개 채널):**
- 사용자 인증 필요
- 개인 알림, 사용자별 데이터 등

**Presence Channels (프레즌스 채널):**
- 채널 구독자 목록 실시간 공유
- "누가 온라인인지" 확인 가능
- 협업 도구, 채팅방 사용자 목록 등

### 4. 모니터링 및 관리

**Laravel Pulse 통합:**
- 실시간 연결 수 모니터링
- 채널별 메시지 처리량 확인
- 성능 메트릭 대시보드

**Laravel Forge 배포:**
- 원클릭 Reverb 서버 배포
- 자동 SSL 인증서 관리
- 프로세스 관리자 통합

---

## 성능 개선 지표

HTTP 폴링 대비 실시간 WebSocket 통신:

| 지표 | 개선율 |
|------|--------|
| **실시간 지연시간** | -95% (폴링 간격 제거) |
| **서버 부하 (CPU/메모리)** | -80% (불필요한 HTTP 요청 제거) |
| **대역폭** | -90% (지속적인 연결 유지) |
| **응답 속도** | 즉시 (< 10ms) |
| **동시 연결 수** | 단일 서버에서 10,000+ 연결 지원 |

---

## 설치 및 요구사항

### Artisan 설치

```bash
# Broadcasting 및 Reverb 자동 설치
php artisan install:broadcasting

# 또는 Reverb만 설치
php artisan reverb:install
```

설치 시 자동으로 수행되는 작업:
1. `config/reverb.php` 설정 파일 생성
2. `.env`에 Reverb 환경 변수 추가
3. `routes/channels.php` 브로드캐스트 채널 파일 생성
4. Laravel Echo 클라이언트 패키지 설치 안내

### 필수 요구사항

| 항목 | 버전 | 필수 여부 |
|------|------|----------|
| **Laravel** | 11.x, 12.x | ✅ 필수 |
| **PHP** | 8.2+ | ✅ 필수 |
| **Redis** | 5.0+ | ⚠️ 확장 시 필요 |
| **Supervisor** | Latest | 🟢 권장 (프로덕션) |

### 환경 변수 설정

`.env` 파일에 Reverb 설정 추가:

```env
# Reverb 서버 설정
REVERB_APP_ID=my-app
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=http

# Broadcasting 드라이버
BROADCAST_DRIVER=reverb

# Redis (수평 확장 시)
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=6379

# 프로덕션 설정
REVERB_ALLOWED_ORIGINS=https://yourdomain.com,https://www.yourdomain.com
```

---

## 설정 파일

`config/reverb.php`:

```php
return [
    'default' => env('REVERB_SERVER', 'reverb'),

    'servers' => [
        'reverb' => [
            'host' => env('REVERB_SERVER_HOST', '0.0.0.0'),
            'port' => env('REVERB_SERVER_PORT', 8080),
            'hostname' => env('REVERB_HOST'),
            'options' => [
                'tls' => [],
            ],
            'scaling' => [
                'enabled' => env('REVERB_SCALING_ENABLED', false),
                'channel' => env('REVERB_SCALING_CHANNEL', 'reverb'),
            ],
            'pulse_ingest_interval' => env('REVERB_PULSE_INGEST_INTERVAL', 15),
            'telescope_ingest_interval' => env('REVERB_TELESCOPE_INGEST_INTERVAL', 15),
        ],
    ],

    'apps' => [
        'providers' => [
            \App\Broadcasting\ReverbServiceProvider::class,
        ],

        'apps' => [
            [
                'id' => env('REVERB_APP_ID'),
                'key' => env('REVERB_APP_KEY'),
                'secret' => env('REVERB_APP_SECRET'),
                'capacity' => null,
                'allowed_origins' => ['*'],
                'ping_interval' => env('REVERB_PING_INTERVAL', 30),
                'max_message_size' => env('REVERB_MAX_MESSAGE_SIZE', 10000),
            ],
        ],
    ],
];
```

---

## Reverb 서버 실행

### 개발 환경

```bash
# Reverb 서버 시작
php artisan reverb:start

# 디버그 모드로 시작
php artisan reverb:start --debug

# 특정 호스트/포트 지정
php artisan reverb:start --host=0.0.0.0 --port=8080
```

### 프로덕션 환경 (Supervisor)

`/etc/supervisor/conf.d/reverb.conf`:

```ini
[program:reverb]
command=php /home/user/toy-core-system/artisan reverb:start
directory=/home/user/toy-core-system
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/supervisor/reverb.log
stopwaitsecs=3600
```

Supervisor 재시작:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start reverb
```

---

## Broadcasting 이벤트 생성

### 이벤트 클래스 정의

```bash
php artisan make:event UserNotification
```

`app/Events/UserNotification.php`:

```php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class UserNotification implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $message,
        public int $userId,
    ) {}

    public function broadcastOn(): array
    {
        // 비공개 채널 (인증 필요)
        return [
            new PrivateChannel('user.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
```

### 채널 인증 (routes/channels.php)

```php
use Illuminate\Support\Facades\Broadcast;

// Private Channel 인증
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Presence Channel 인증 (온라인 사용자 목록)
Broadcast::channel('chat.{roomId}', function ($user, $roomId) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => $user->avatar_url,
    ];
});
```

### 이벤트 발행

```php
use App\Events\UserNotification;

// 특정 사용자에게 알림 전송
event(new UserNotification('새 메시지가 도착했습니다.', $userId));

// 또는 broadcast 헬퍼 사용
broadcast(new UserNotification('주문이 완료되었습니다.', $userId));
```

---

## 클라이언트 측 설정 (Laravel Echo)

### NPM 패키지 설치

```bash
npm install --save-dev laravel-echo pusher-js
```

### Laravel Echo 설정

`resources/js/echo.js`:

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

`.env`:

```env
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### Private Channel 구독

```javascript
// Private Channel 구독 (인증 필요)
Echo.private(`user.${userId}`)
    .listen('.notification', (event) => {
        console.log('새 알림:', event.message);
        showNotification(event.message);
    });
```

### Presence Channel 구독 (온라인 사용자 목록)

```javascript
// Presence Channel 구독 (채팅방 예시)
Echo.join(`chat.${roomId}`)
    .here((users) => {
        // 현재 채팅방에 있는 사용자 목록
        console.log('현재 사용자:', users);
        updateUserList(users);
    })
    .joining((user) => {
        // 새 사용자 입장
        console.log(`${user.name}님이 입장했습니다.`);
        addUserToList(user);
    })
    .leaving((user) => {
        // 사용자 퇴장
        console.log(`${user.name}님이 퇴장했습니다.`);
        removeUserFromList(user);
    })
    .listen('.message', (event) => {
        // 채팅 메시지 수신
        console.log('새 메시지:', event.message);
        addMessageToChat(event);
    });
```

### Public Channel 구독 (인증 불필요)

```javascript
// Public Channel 구독 (공개 알림)
Echo.channel('system-notifications')
    .listen('.maintenance', (event) => {
        alert('시스템 점검 예정: ' + event.message);
    });
```

---

## 실전 예제

### 1. 실시간 알림 시스템

**이벤트 발행 (서버):**

```php
namespace App\Services;

use App\Events\UserNotification;
use App\Models\User;

class NotificationService
{
    public function sendToUser(User $user, string $message): void
    {
        // 알림 이벤트 브로드캐스트
        broadcast(new UserNotification($message, $user->id));

        // DB에도 저장 (읽지 않은 알림 목록)
        $user->notifications()->create([
            'message' => $message,
            'read_at' => null,
        ]);
    }

    public function sendToAll(string $message): void
    {
        // 모든 사용자에게 브로드캐스트
        broadcast(new \App\Events\SystemNotification($message));
    }
}
```

**클라이언트 구독:**

```javascript
// 사용자별 알림 수신
Echo.private(`user.${userId}`)
    .listen('.notification', (event) => {
        // 알림 UI 표시
        showToast(event.message);

        // 알림 카운터 증가
        incrementNotificationBadge();

        // 알림 목록에 추가
        addNotificationToList(event);
    });
```

### 2. 실시간 채팅 (Presence Channel)

**이벤트 정의:**

```php
// app/Events/MessageSent.php
namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MessageSent implements ShouldBroadcast
{
    public function __construct(
        public int $userId,
        public string $userName,
        public string $message,
        public int $roomId,
    ) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel('chat.' . $this->roomId)];
    }

    public function broadcastAs(): string
    {
        return 'message';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'message' => $this->message,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
```

**메시지 전송 (서버):**

```php
namespace App\Http\Controllers;

use App\Events\MessageSent;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function sendMessage(Request $request, int $roomId)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        // 메시지 저장
        $message = auth()->user()->messages()->create([
            'room_id' => $roomId,
            'message' => $validated['message'],
        ]);

        // 실시간 브로드캐스트
        broadcast(new MessageSent(
            auth()->id(),
            auth()->user()->name,
            $validated['message'],
            $roomId
        ));

        return response()->json($message, 201);
    }
}
```

**클라이언트 채팅:**

```javascript
const roomId = 1;

// 채팅방 참여
Echo.join(`chat.${roomId}`)
    .here((users) => {
        // 현재 온라인 사용자 목록
        users.forEach(user => {
            addUserToOnlineList(user);
        });
    })
    .joining((user) => {
        // 새 사용자 입장
        addUserToOnlineList(user);
        addSystemMessage(`${user.name}님이 입장했습니다.`);
    })
    .leaving((user) => {
        // 사용자 퇴장
        removeUserFromOnlineList(user);
        addSystemMessage(`${user.name}님이 퇴장했습니다.`);
    })
    .listen('.message', (event) => {
        // 새 메시지 수신
        addMessageToChat({
            userId: event.user_id,
            userName: event.user_name,
            message: event.message,
            timestamp: event.timestamp,
        });
    });

// 메시지 전송
function sendMessage(message) {
    axios.post(`/api/chat/rooms/${roomId}/messages`, { message })
        .then(response => {
            // 서버가 브로드캐스트하므로 별도 UI 업데이트 불필요
            clearInputField();
        });
}
```

### 3. 라이브 대시보드 (실시간 통계)

**이벤트 정의:**

```php
// app/Events/MetricsUpdated.php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MetricsUpdated implements ShouldBroadcast
{
    public function __construct(
        public array $metrics,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('metrics')];
    }

    public function broadcastAs(): string
    {
        return 'updated';
    }

    public function broadcastWith(): array
    {
        return [
            'metrics' => $this->metrics,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
```

**주기적 메트릭 브로드캐스트:**

```php
// app/Console/Commands/BroadcastMetrics.php
namespace App\Console\Commands;

use App\Events\MetricsUpdated;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BroadcastMetrics extends Command
{
    protected $signature = 'metrics:broadcast';

    public function handle(): void
    {
        $metrics = [
            'active_users' => DB::table('sessions')->count(),
            'total_sales' => DB::table('orders')->sum('total'),
            'pending_orders' => DB::table('orders')->where('status', 'pending')->count(),
            'revenue_today' => DB::table('orders')
                ->whereDate('created_at', today())
                ->sum('total'),
        ];

        broadcast(new MetricsUpdated($metrics));

        $this->info('Metrics broadcasted successfully!');
    }
}
```

**스케줄러 등록:**

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // 매 5분마다 메트릭 브로드캐스트
    $schedule->command('metrics:broadcast')->everyFiveMinutes();
}
```

**클라이언트 대시보드:**

```javascript
// 실시간 메트릭 수신
Echo.channel('metrics')
    .listen('.updated', (event) => {
        // 대시보드 UI 업데이트
        updateMetricCard('active-users', event.metrics.active_users);
        updateMetricCard('total-sales', formatCurrency(event.metrics.total_sales));
        updateMetricCard('pending-orders', event.metrics.pending_orders);
        updateMetricCard('revenue-today', formatCurrency(event.metrics.revenue_today));

        // 차트 업데이트
        updateRevenueChart(event.metrics);

        // 마지막 업데이트 시간 표시
        updateTimestamp(event.updated_at);
    });
```

### 4. 협업 도구 (실시간 공동 편집)

**이벤트 정의:**

```php
// app/Events/DocumentUpdated.php
namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class DocumentUpdated implements ShouldBroadcast
{
    public function __construct(
        public int $documentId,
        public int $userId,
        public string $userName,
        public array $changes,
    ) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel('document.' . $this->documentId)];
    }

    public function broadcastAs(): string
    {
        return 'updated';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'changes' => $this->changes,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
```

**문서 업데이트 (서버):**

```php
namespace App\Http\Controllers;

use App\Events\DocumentUpdated;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function update(Request $request, int $documentId)
    {
        $validated = $request->validate([
            'changes' => 'required|array',
        ]);

        // 변경사항 저장
        $document = Document::findOrFail($documentId);
        $document->update(['content' => $validated['changes']]);

        // 다른 편집자에게 브로드캐스트
        broadcast(new DocumentUpdated(
            $documentId,
            auth()->id(),
            auth()->user()->name,
            $validated['changes']
        ))->toOthers(); // 자신은 제외

        return response()->json(['success' => true]);
    }
}
```

**클라이언트 편집기:**

```javascript
const documentId = 1;
let editor; // 에디터 인스턴스

// 문서 편집 참여
Echo.join(`document.${documentId}`)
    .here((users) => {
        // 현재 편집 중인 사용자 목록 표시
        showActiveEditors(users);
    })
    .joining((user) => {
        addActiveEditor(user);
        showNotification(`${user.name}님이 편집을 시작했습니다.`);
    })
    .leaving((user) => {
        removeActiveEditor(user);
        showNotification(`${user.name}님이 편집을 종료했습니다.`);
    })
    .listen('.updated', (event) => {
        // 다른 사용자의 변경사항 반영
        if (event.user_id !== currentUserId) {
            applyChanges(editor, event.changes);
            showEditIndicator(event.user_name);
        }
    });

// 로컬 변경사항 전송 (디바운스 적용)
editor.on('change', debounce((changes) => {
    axios.post(`/api/documents/${documentId}`, { changes })
        .catch(error => console.error('Failed to sync changes:', error));
}, 500));
```

---

## 수평 확장 (Scaling)

### Redis 기반 확장 활성화

`.env`:

```env
# Redis 연결 설정
REVERB_SCALING_ENABLED=true
REVERB_SCALING_CHANNEL=reverb

# Redis 서버 정보
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_DB=0
```

### Load Balancer 설정 (Nginx)

`/etc/nginx/sites-available/reverb`:

```nginx
upstream reverb_backend {
    # 여러 Reverb 서버 등록
    server reverb1.yourdomain.com:8080;
    server reverb2.yourdomain.com:8080;
    server reverb3.yourdomain.com:8080;

    # Sticky Session 불필요 (Redis 공유)
}

server {
    listen 443 ssl;
    server_name ws.yourdomain.com;

    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;

    location / {
        proxy_pass http://reverb_backend;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # WebSocket 타임아웃 설정
        proxy_read_timeout 3600s;
        proxy_send_timeout 3600s;
    }
}
```

---

## 프로젝트 적용 시 고려사항

### 1. Notification 도메인과 통합

현재 프로젝트의 `Notification` 도메인을 Reverb와 통합:

```php
// app/Services/Notification/NotificationService.php
namespace App\Services\Notification;

use App\Events\UserNotification;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function send(array $data): void
    {
        $channel = $data['channel'];

        match ($channel) {
            'email' => $this->sendEmail($data),
            'sms' => $this->sendSms($data),
            'slack' => $this->sendSlack($data),
            'websocket' => $this->sendWebSocket($data), // 추가
            default => throw new \InvalidArgumentException("Unsupported channel: {$channel}"),
        };
    }

    protected function sendWebSocket(array $data): void
    {
        // Reverb를 통해 실시간 알림 전송
        broadcast(new UserNotification(
            $data['message'],
            $data['user_id']
        ));
    }
}
```

### 2. User Activity 로그와 연동

사용자 활동을 실시간으로 브로드캐스트:

```php
// app/Domain/UserActivity/Services/UserActivityService.php
namespace App\Domain\UserActivity\Services;

use App\Events\UserActivityLogged;

class UserActivityService
{
    public function log(array $activityData): void
    {
        // MongoDB에 로그 저장
        $activity = $this->repository->create($activityData);

        // 실시간 브로드캐스트 (관리자 대시보드)
        broadcast(new UserActivityLogged($activity));
    }
}
```

### 3. Post 도메인 - 실시간 댓글

블로그 게시물에 실시간 댓글 시스템:

```php
// app/Domain/Post/Events/CommentAdded.php
namespace App\Domain\Post\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class CommentAdded implements ShouldBroadcast
{
    public function __construct(
        public int $postId,
        public array $comment,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('post.' . $this->postId)];
    }

    public function broadcastAs(): string
    {
        return 'comment.added';
    }
}
```

**클라이언트:**

```javascript
// 게시물 상세 페이지
Echo.channel(`post.${postId}`)
    .listen('.comment.added', (event) => {
        // 새 댓글 실시간 표시
        addCommentToList(event.comment);
        incrementCommentCount();
    });
```

### 4. Timer 도메인 - 실시간 카운트다운

타이머 상태 변경을 실시간으로 브로드캐스트:

```php
// app/Domain/Timer/Events/TimerExpired.php
namespace App\Domain\Timer\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class TimerExpired implements ShouldBroadcast
{
    public function __construct(
        public int $timerId,
        public int $userId,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'timer.expired';
    }
}
```

**Queue Job에서 브로드캐스트:**

```php
// app/Jobs/CheckExpiredTimers.php
namespace App\Jobs;

use App\Domain\Timer\Events\TimerExpired;
use Illuminate\Contracts\Queue\ShouldQueue;

class CheckExpiredTimers implements ShouldQueue
{
    public function handle(): void
    {
        $expiredTimers = Timer::where('target_time', '<=', now())
            ->where('notified', false)
            ->get();

        foreach ($expiredTimers as $timer) {
            // 실시간 알림
            broadcast(new TimerExpired($timer->id, $timer->user_id));

            // 타이머 상태 업데이트
            $timer->update(['notified' => true]);
        }
    }
}
```

---

## 성능 최적화 팁

### 1. 이벤트 큐잉 (ShouldBroadcastNow vs ShouldBroadcast)

```php
// 즉시 브로드캐스트 (ShouldBroadcastNow)
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class UrgentNotification implements ShouldBroadcastNow
{
    // 즉시 실행 (Queue 사용 안 함)
}

// 큐 사용 브로드캐스트 (ShouldBroadcast)
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class RegularNotification implements ShouldBroadcast
{
    // Queue를 통해 비동기 브로드캐스트
}
```

### 2. 특정 사용자 제외 (toOthers)

```php
// 이벤트를 발생시킨 사용자는 제외하고 브로드캐스트
broadcast(new DocumentUpdated($data))->toOthers();
```

### 3. Private 브로드캐스트 (특정 사용자만)

```php
// 특정 사용자들에게만 브로드캐스트
broadcast(new OrderUpdated($order))->to($users);
```

### 4. 메시지 크기 제한

```env
# 메시지 최대 크기 (바이트)
REVERB_MAX_MESSAGE_SIZE=10000
```

대용량 데이터는 브로드캐스트하지 말고 ID만 전송:

```php
// ❌ 나쁜 예: 대용량 데이터 브로드캐스트
public function broadcastWith(): array
{
    return [
        'user' => $this->user->toArray(), // 큰 객체
        'posts' => $this->user->posts->toArray(), // 대량 데이터
    ];
}

// ✅ 좋은 예: ID만 전송
public function broadcastWith(): array
{
    return [
        'user_id' => $this->user->id,
    ];
}
```

클라이언트에서 ID로 데이터 요청:

```javascript
Echo.channel('notifications')
    .listen('.new', (event) => {
        // ID로 상세 데이터 요청
        axios.get(`/api/notifications/${event.id}`)
            .then(response => showNotification(response.data));
    });
```

### 5. Ping Interval 조정

```env
# 연결 유지 Ping 간격 (초)
REVERB_PING_INTERVAL=30
```

---

## 디버깅 및 트러블슈팅

### 1. Reverb 서버 로그 확인

```bash
# 디버그 모드로 실행
php artisan reverb:start --debug

# Supervisor 로그 확인
sudo tail -f /var/log/supervisor/reverb.log
```

### 2. 연결 테스트

```bash
# WebSocket 연결 테스트 (wscat 사용)
npm install -g wscat
wscat -c ws://localhost:8080/app/my-app?protocol=7

# 또는 cURL 테스트
curl -i -N -H "Connection: Upgrade" \
     -H "Upgrade: websocket" \
     -H "Host: localhost:8080" \
     -H "Origin: http://localhost" \
     http://localhost:8080/app/my-app
```

### 3. Laravel Pulse 모니터링

```bash
# Pulse 설치
composer require laravel/pulse

# Pulse 대시보드 접속
http://localhost:8000/pulse
```

Reverb 메트릭 확인:
- 실시간 연결 수
- 채널별 구독자 수
- 메시지 처리량

### 4. 브라우저 개발자 도구

```javascript
// Echo 연결 상태 확인
console.log(window.Echo.connector.pusher.connection.state);

// 이벤트 로깅
window.Echo.private('user.1')
    .listen('.notification', (event) => {
        console.log('Received event:', event);
    })
    .error((error) => {
        console.error('WebSocket error:', error);
    });
```

### 5. 일반적인 오류 해결

**오류: "Connection refused"**

```bash
# Reverb 서버가 실행 중인지 확인
php artisan reverb:start

# 포트가 사용 중인지 확인
lsof -i :8080
```

**오류: "Unauthorized"**

```env
# .env의 APP_KEY가 일치하는지 확인
REVERB_APP_KEY=your-app-key
VITE_REVERB_APP_KEY=your-app-key
```

**오류: "CORS policy"**

```php
// config/reverb.php
'apps' => [
    [
        'allowed_origins' => ['http://localhost:5173', 'https://yourdomain.com'],
    ],
],
```

---

## 관련 패키지

| 패키지 | 용도 | 저장소 |
|--------|------|--------|
| **laravel/echo** | Laravel Echo 클라이언트 (필수) | [GitHub](https://github.com/laravel/echo) |
| **pusher/pusher-php-server** | Pusher PHP SDK | [GitHub](https://github.com/pusher/pusher-http-php) |
| **beyondcode/laravel-websockets** | 대체 WebSocket 서버 (자체 호스팅) | [GitHub](https://github.com/beyondcode/laravel-websockets) |
| **laravel/pulse** | Laravel 모니터링 대시보드 | [GitHub](https://github.com/laravel/pulse) |

---

## 참고 링크

### 공식 문서 및 저장소

- [GitHub Repository](https://github.com/laravel/reverb)
- [Laravel Reverb Documentation](https://laravel.com/docs/reverb)
- [Laravel Broadcasting Documentation](https://laravel.com/docs/broadcasting)
- [Laravel Echo Documentation](https://github.com/laravel/echo)

### 관련 아티클

- [Real-Time Laravel: A Complete Guide to WebSockets with Laravel Reverb](https://masteryoflaravel.medium.com/real-time-laravel-a-complete-practical-guide-to-websockets-with-laravel-reverb-2025-edition-bae825c0e9ce)
- [The Ultimate Guide to Laravel Reverb](https://novu.co/blog/the-ultimate-guide-to-laravel-reverb)
- [WebSockets for Laravel Cloud, Powered by Laravel Reverb](https://laravel.com/blog/introducing-websockets-for-laravel-cloud-powered-by-laravel-reverb)

---

## 프로젝트 적용 로드맵

### Phase 1: Reverb 설치 및 기본 설정

- [ ] Laravel Reverb 패키지 설치 (`php artisan install:broadcasting`)
- [ ] 환경 변수 설정 (`.env`)
- [ ] Reverb 서버 실행 테스트
- [ ] Laravel Echo 클라이언트 설치 및 설정

### Phase 2: Notification 도메인 통합

- [ ] `UserNotification` 이벤트 생성
- [ ] `NotificationService`에 WebSocket 채널 추가
- [ ] Private Channel 인증 설정 (`routes/channels.php`)
- [ ] 클라이언트 알림 수신 UI 구현

### Phase 3: Post 도메인 - 실시간 댓글

- [ ] `CommentAdded` 이벤트 생성
- [ ] 댓글 작성 시 브로드캐스트 추가
- [ ] 게시물 상세 페이지에 실시간 댓글 구독
- [ ] UI 업데이트 로직 구현

### Phase 4: User Activity 실시간 모니터링

- [ ] `UserActivityLogged` 이벤트 생성
- [ ] 관리자 대시보드 채널 설정
- [ ] 실시간 활동 로그 스트림 구현
- [ ] Laravel Pulse 통합 (선택)

### Phase 5: 프로덕션 배포

- [ ] Supervisor 설정 파일 작성
- [ ] SSL/TLS 인증서 적용 (wss://)
- [ ] CORS 및 Allowed Origins 설정
- [ ] Load Balancer 설정 (필요 시)
- [ ] Redis 기반 확장 활성화 (필요 시)
- [ ] 성능 모니터링 대시보드 구축

---

## 테스트 예시

```php
// tests/Feature/Broadcasting/NotificationTest.php
namespace Tests\Feature\Broadcasting;

use App\Events\UserNotification;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    public function test_user_notification_is_broadcast(): void
    {
        // Given: 이벤트 Fake
        Event::fake([UserNotification::class]);

        // When: 알림 전송
        $user = User::factory()->create();
        event(new UserNotification('테스트 메시지', $user->id));

        // Then: 이벤트가 브로드캐스트됨
        Event::assertDispatched(UserNotification::class, function ($event) use ($user) {
            return $event->userId === $user->id
                && $event->message === '테스트 메시지';
        });
    }

    public function test_private_channel_requires_authentication(): void
    {
        // Given: 인증되지 않은 사용자
        $response = $this->postJson('/broadcasting/auth', [
            'channel_name' => 'private-user.1',
        ]);

        // Then: 401 Unauthorized
        $response->assertStatus(401);
    }

    public function test_user_can_subscribe_to_own_channel(): void
    {
        // Given: 인증된 사용자
        $user = User::factory()->create();
        $this->actingAs($user);

        // When: 자신의 채널 구독 시도
        $response = $this->postJson('/broadcasting/auth', [
            'channel_name' => 'private-user.' . $user->id,
        ]);

        // Then: 200 OK
        $response->assertStatus(200);
    }

    public function test_user_cannot_subscribe_to_other_user_channel(): void
    {
        // Given: 인증된 사용자
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($user);

        // When: 다른 사용자의 채널 구독 시도
        $response = $this->postJson('/broadcasting/auth', [
            'channel_name' => 'private-user.' . $otherUser->id,
        ]);

        // Then: 403 Forbidden
        $response->assertStatus(403);
    }
}
```

```javascript
// tests/js/echo.test.js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

describe('Laravel Echo', () => {
    let echo;

    beforeEach(() => {
        window.Pusher = Pusher;

        echo = new Echo({
            broadcaster: 'reverb',
            key: 'test-key',
            wsHost: 'localhost',
            wsPort: 8080,
            forceTLS: false,
            enabledTransports: ['ws', 'wss'],
        });
    });

    afterEach(() => {
        echo.disconnect();
    });

    it('connects to Reverb server', (done) => {
        echo.connector.pusher.connection.bind('connected', () => {
            expect(echo.connector.pusher.connection.state).toBe('connected');
            done();
        });
    });

    it('subscribes to public channel', (done) => {
        const channel = echo.channel('test-channel');

        channel.listen('.test-event', (event) => {
            expect(event.message).toBe('Test message');
            done();
        });

        // 이벤트 트리거 (테스트용)
        channel.trigger('client-test-event', { message: 'Test message' });
    });

    it('subscribes to private channel with authentication', (done) => {
        const channel = echo.private('user.1');

        channel.subscription.bind('pusher:subscription_succeeded', () => {
            expect(channel.subscription.subscribed).toBe(true);
            done();
        });
    });
});
```

---

## 주의사항

### 1. 인증 토큰 보안

```javascript
// ✅ 좋은 예: CSRF 토큰 자동 전송
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    authorizer: (channel, options) => {
        return {
            authorize: (socketId, callback) => {
                axios.post('/broadcasting/auth', {
                    socket_id: socketId,
                    channel_name: channel.name
                })
                .then(response => {
                    callback(false, response.data);
                })
                .catch(error => {
                    callback(true, error);
                });
            }
        };
    },
});
```

### 2. 메모리 누수 방지

```javascript
// ✅ 좋은 예: 컴포넌트 언마운트 시 구독 해제
let channel;

function setupEcho() {
    channel = Echo.channel('notifications');
    channel.listen('.new', handleNotification);
}

function cleanupEcho() {
    if (channel) {
        Echo.leave('notifications');
        channel = null;
    }
}

// React 예시
useEffect(() => {
    setupEcho();
    return () => cleanupEcho();
}, []);
```

### 3. 에러 핸들링

```javascript
// ✅ 좋은 예: 재연결 로직
Echo.connector.pusher.connection.bind('error', (error) => {
    console.error('WebSocket connection error:', error);

    // 재연결 시도
    setTimeout(() => {
        Echo.connector.pusher.connect();
    }, 5000);
});

Echo.connector.pusher.connection.bind('disconnected', () => {
    console.warn('WebSocket disconnected. Attempting to reconnect...');
});
```

### 4. 프로덕션 환경 설정

```env
# ✅ 프로덕션 환경
REVERB_SCHEME=https
REVERB_HOST=ws.yourdomain.com
REVERB_PORT=443
REVERB_ALLOWED_ORIGINS=https://yourdomain.com,https://www.yourdomain.com
```

---

## 결론

**Laravel Reverb**는 Laravel 애플리케이션에 실시간 WebSocket 통신을 손쉽게 추가할 수 있는 강력한 공식 패키지입니다.

현재 프로젝트의 **Notification, Post, Timer, User Activity** 도메인에 적용하면, 실시간 알림, 라이브 댓글, 즉시 타이머 알림, 실시간 활동 모니터링 등의 기능을 구현할 수 있습니다.

### 주요 장점

✅ Laravel Echo와 완벽한 통합
✅ 단일 서버에서 10,000+ 동시 연결 지원
✅ Redis 기반 수평 확장 지원
✅ Private/Presence 채널 인증
✅ Laravel Pulse 모니터링 통합
✅ Laravel Forge 원클릭 배포
✅ MIT 라이선스 (상업적 사용 가능)

### 다음 단계

- Laravel Reverb 설치 및 기본 설정
- Notification 도메인에 WebSocket 채널 추가
- Post 도메인에 실시간 댓글 구현
- 프로덕션 배포 및 모니터링 설정

---

**문서 버전:** 1.0
**작성일:** 2025-12-16
**마지막 업데이트:** 2025-12-16
