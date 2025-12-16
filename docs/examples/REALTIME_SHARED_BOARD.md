# 실시간 공유 보드 (Shared Whiteboard) 구현 가이드

> **기술 스택:** Laravel Reverb + Presence Channel + Canvas API
> **용도:** 실시간 협업 칠판, 온라인 화이트보드, 공유 드로잉

---

## 개요

Laravel Reverb의 Presence Channel을 활용하여 실시간 공유 보드를 구현합니다.

### 주요 기능

✅ 여러 사용자가 동시에 그림 그리기
✅ 실시간 드로잉 동기화 (< 50ms 지연)
✅ 현재 보드에 접속한 사용자 목록 표시
✅ 사용자별 커서 위치 실시간 공유
✅ 그리기 도구 (펜, 지우개, 색상, 두께)
✅ 보드 저장 및 불러오기

---

## 1. 백엔드 구현 (Laravel)

### 1.1 이벤트 정의

```bash
php artisan make:event DrawingUpdated
```

**app/Events/DrawingUpdated.php:**

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DrawingUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $boardId,
        public int $userId,
        public string $userName,
        public array $drawData,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('board.' . $this->boardId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'drawing.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'draw_data' => $this->drawData,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
```

**app/Events/CursorMoved.php:**

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class CursorMoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $boardId,
        public int $userId,
        public float $x,
        public float $y,
    ) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel('board.' . $this->boardId)];
    }

    public function broadcastAs(): string
    {
        return 'cursor.moved';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'x' => $this->x,
            'y' => $this->y,
        ];
    }
}
```

**app/Events/BoardCleared.php:**

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class BoardCleared implements ShouldBroadcast
{
    public function __construct(
        public int $boardId,
        public int $userId,
        public string $userName,
    ) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel('board.' . $this->boardId)];
    }

    public function broadcastAs(): string
    {
        return 'board.cleared';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
```

### 1.2 채널 인증 (routes/channels.php)

```php
use Illuminate\Support\Facades\Broadcast;

// Presence Channel: 보드 참여자 목록 공유
Broadcast::channel('board.{boardId}', function ($user, $boardId) {
    // 보드 접근 권한 확인
    $board = \App\Models\Board::find($boardId);

    if (!$board || !$user->can('view', $board)) {
        return false;
    }

    // 사용자 정보 반환 (다른 참여자들에게 공유됨)
    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => $user->avatar_url,
        'color' => sprintf('#%06X', mt_rand(0, 0xFFFFFF)), // 랜덤 커서 색상
    ];
});
```

### 1.3 컨트롤러 구현

**app/Http/Controllers/BoardController.php:**

```php
<?php

namespace App\Http\Controllers;

use App\Events\BoardCleared;
use App\Events\DrawingUpdated;
use App\Models\Board;
use Illuminate\Http\Request;

class BoardController extends Controller
{
    public function show(int $boardId)
    {
        $board = Board::findOrFail($boardId);
        $this->authorize('view', $board);

        return view('board.show', [
            'board' => $board,
            'boardId' => $boardId,
        ]);
    }

    public function draw(Request $request, int $boardId)
    {
        $board = Board::findOrFail($boardId);
        $this->authorize('edit', $board);

        $validated = $request->validate([
            'draw_data' => 'required|array',
            'draw_data.type' => 'required|string|in:line,erase',
            'draw_data.points' => 'required|array',
            'draw_data.color' => 'nullable|string',
            'draw_data.width' => 'nullable|numeric|min:1|max:50',
        ]);

        // 그림 데이터 저장 (선택적)
        $board->drawings()->create([
            'user_id' => auth()->id(),
            'data' => $validated['draw_data'],
        ]);

        // 실시간 브로드캐스트
        broadcast(new DrawingUpdated(
            $boardId,
            auth()->id(),
            auth()->user()->name,
            $validated['draw_data']
        ))->toOthers(); // 자신은 제외

        return response()->json(['success' => true]);
    }

    public function clear(int $boardId)
    {
        $board = Board::findOrFail($boardId);
        $this->authorize('edit', $board);

        // 보드 초기화
        $board->drawings()->delete();

        // 실시간 브로드캐스트
        broadcast(new BoardCleared(
            $boardId,
            auth()->id(),
            auth()->user()->name
        ));

        return response()->json(['success' => true]);
    }

    public function export(int $boardId)
    {
        $board = Board::findOrFail($boardId);
        $this->authorize('view', $board);

        $drawings = $board->drawings()
            ->orderBy('created_at')
            ->get()
            ->pluck('data');

        return response()->json([
            'success' => true,
            'data' => $drawings,
        ]);
    }
}
```

### 1.4 모델 정의

**app/Models/Board.php:**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Board extends Model
{
    protected $fillable = [
        'name',
        'user_id',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    public function drawings(): HasMany
    {
        return $this->hasMany(BoardDrawing::class);
    }
}
```

**app/Models/BoardDrawing.php:**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoardDrawing extends Model
{
    protected $fillable = [
        'board_id',
        'user_id',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];
}
```

### 1.5 마이그레이션

```bash
php artisan make:migration create_boards_table
php artisan make:migration create_board_drawings_table
```

**database/migrations/xxxx_create_boards_table.php:**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boards');
    }
};
```

**database/migrations/xxxx_create_board_drawings_table.php:**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_drawings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('data');
            $table->timestamps();

            $table->index(['board_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_drawings');
    }
};
```

### 1.6 라우트 정의

**routes/web.php:**

```php
use App\Http\Controllers\BoardController;

Route::middleware(['auth'])->group(function () {
    Route::get('/boards/{boardId}', [BoardController::class, 'show'])->name('board.show');
    Route::post('/boards/{boardId}/draw', [BoardController::class, 'draw'])->name('board.draw');
    Route::post('/boards/{boardId}/clear', [BoardController::class, 'clear'])->name('board.clear');
    Route::get('/boards/{boardId}/export', [BoardController::class, 'export'])->name('board.export');
});
```

---

## 2. 프론트엔드 구현

### 2.1 HTML 구조

**resources/views/board/show.blade.php:**

```blade
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>실시간 공유 보드</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-4">
        <!-- 헤더 -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold">{{ $board->name }}</h1>

                <!-- 도구 모음 -->
                <div class="flex gap-4 items-center">
                    <div class="flex gap-2">
                        <button id="pen-tool" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                            펜
                        </button>
                        <button id="eraser-tool" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                            지우개
                        </button>
                    </div>

                    <input type="color" id="color-picker" value="#000000" class="w-12 h-10 border rounded">

                    <input type="range" id="brush-size" min="1" max="20" value="2" class="w-32">

                    <button id="clear-board" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                        보드 지우기
                    </button>

                    <button id="export-board" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                        내보내기
                    </button>
                </div>
            </div>
        </div>

        <!-- 참여자 목록 -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <h2 class="font-bold mb-2">참여자 (<span id="user-count">0</span>)</h2>
            <div id="user-list" class="flex gap-2 flex-wrap"></div>
        </div>

        <!-- 캔버스 -->
        <div class="bg-white rounded-lg shadow p-4">
            <canvas id="whiteboard" width="1200" height="800"
                    class="border border-gray-300 cursor-crosshair w-full">
            </canvas>

            <!-- 다른 사용자 커서 표시 -->
            <div id="cursors-container" class="relative"></div>
        </div>
    </div>

    <script>
        window.boardId = {{ $boardId }};
        window.currentUser = {
            id: {{ auth()->id() }},
            name: "{{ auth()->user()->name }}"
        };
    </script>
    @vite(['resources/js/board.js'])
</body>
</html>
```

### 2.2 JavaScript 구현

**resources/js/board.js:**

```javascript
import axios from 'axios';
import Echo from 'laravel-echo';

// ============================================
// 1. 캔버스 초기화
// ============================================

const canvas = document.getElementById('whiteboard');
const ctx = canvas.getContext('2d');
const boardId = window.boardId;
const currentUser = window.currentUser;

// 드로잉 상태
let isDrawing = false;
let currentTool = 'pen'; // pen, eraser
let currentColor = '#000000';
let brushSize = 2;
let drawingPoints = [];

// 사용자 커서 추적
const userCursors = new Map();

// ============================================
// 2. 도구 설정
// ============================================

document.getElementById('pen-tool').addEventListener('click', () => {
    currentTool = 'pen';
    document.getElementById('pen-tool').classList.add('bg-blue-700');
    document.getElementById('eraser-tool').classList.remove('bg-gray-700');
});

document.getElementById('eraser-tool').addEventListener('click', () => {
    currentTool = 'eraser';
    document.getElementById('eraser-tool').classList.add('bg-gray-700');
    document.getElementById('pen-tool').classList.remove('bg-blue-700');
});

document.getElementById('color-picker').addEventListener('change', (e) => {
    currentColor = e.target.value;
});

document.getElementById('brush-size').addEventListener('input', (e) => {
    brushSize = parseInt(e.target.value);
});

// ============================================
// 3. 캔버스 이벤트 핸들러
// ============================================

canvas.addEventListener('mousedown', startDrawing);
canvas.addEventListener('mousemove', draw);
canvas.addEventListener('mouseup', stopDrawing);
canvas.addEventListener('mouseout', stopDrawing);

// 터치 이벤트 (모바일 지원)
canvas.addEventListener('touchstart', handleTouchStart);
canvas.addEventListener('touchmove', handleTouchMove);
canvas.addEventListener('touchend', stopDrawing);

function startDrawing(e) {
    isDrawing = true;
    const pos = getMousePos(e);
    drawingPoints = [pos];
}

function draw(e) {
    if (!isDrawing) return;

    const pos = getMousePos(e);
    drawingPoints.push(pos);

    // 로컬 캔버스에 그리기
    drawLine(drawingPoints, currentColor, brushSize, currentTool === 'eraser');

    // 커서 위치 브로드캐스트 (쓰로틀링)
    throttle(() => {
        broadcastCursorPosition(pos.x, pos.y);
    }, 50);
}

function stopDrawing() {
    if (!isDrawing) return;

    isDrawing = false;

    // 그림 데이터 서버로 전송
    if (drawingPoints.length > 0) {
        sendDrawingData({
            type: currentTool === 'eraser' ? 'erase' : 'line',
            points: drawingPoints,
            color: currentColor,
            width: brushSize,
        });
    }

    drawingPoints = [];
}

function getMousePos(e) {
    const rect = canvas.getBoundingClientRect();
    const scaleX = canvas.width / rect.width;
    const scaleY = canvas.height / rect.height;

    return {
        x: (e.clientX - rect.left) * scaleX,
        y: (e.clientY - rect.top) * scaleY,
    };
}

function handleTouchStart(e) {
    e.preventDefault();
    const touch = e.touches[0];
    const mouseEvent = new MouseEvent('mousedown', {
        clientX: touch.clientX,
        clientY: touch.clientY,
    });
    canvas.dispatchEvent(mouseEvent);
}

function handleTouchMove(e) {
    e.preventDefault();
    const touch = e.touches[0];
    const mouseEvent = new MouseEvent('mousemove', {
        clientX: touch.clientX,
        clientY: touch.clientY,
    });
    canvas.dispatchEvent(mouseEvent);
}

// ============================================
// 4. 캔버스 드로잉 함수
// ============================================

function drawLine(points, color, width, isEraser = false) {
    if (points.length < 2) return;

    ctx.save();
    ctx.strokeStyle = isEraser ? '#FFFFFF' : color;
    ctx.lineWidth = width;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';

    if (isEraser) {
        ctx.globalCompositeOperation = 'destination-out';
    }

    ctx.beginPath();
    ctx.moveTo(points[0].x, points[0].y);

    for (let i = 1; i < points.length; i++) {
        ctx.lineTo(points[i].x, points[i].y);
    }

    ctx.stroke();
    ctx.restore();
}

function clearCanvas() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
}

// ============================================
// 5. API 통신
// ============================================

async function sendDrawingData(drawData) {
    try {
        await axios.post(`/boards/${boardId}/draw`, {
            draw_data: drawData,
        });
    } catch (error) {
        console.error('Failed to send drawing data:', error);
    }
}

document.getElementById('clear-board').addEventListener('click', async () => {
    if (!confirm('보드를 모두 지우시겠습니까?')) return;

    try {
        await axios.post(`/boards/${boardId}/clear`);
        clearCanvas();
    } catch (error) {
        console.error('Failed to clear board:', error);
    }
});

document.getElementById('export-board').addEventListener('click', async () => {
    try {
        const response = await axios.get(`/boards/${boardId}/export`);

        // JSON 파일로 다운로드
        const blob = new Blob([JSON.stringify(response.data.data, null, 2)], {
            type: 'application/json',
        });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `board-${boardId}-${Date.now()}.json`;
        a.click();
        URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Failed to export board:', error);
    }
});

// ============================================
// 6. Laravel Echo (WebSocket)
// ============================================

const echo = window.Echo;

echo.join(`board.${boardId}`)
    // 현재 보드에 있는 사용자 목록
    .here((users) => {
        console.log('현재 참여자:', users);
        updateUserList(users);
    })
    // 새 사용자 입장
    .joining((user) => {
        console.log(`${user.name}님이 입장했습니다.`);
        addUserToList(user);
        showNotification(`${user.name}님이 입장했습니다.`, 'info');
    })
    // 사용자 퇴장
    .leaving((user) => {
        console.log(`${user.name}님이 퇴장했습니다.`);
        removeUserFromList(user);
        removeUserCursor(user.id);
        showNotification(`${user.name}님이 퇴장했습니다.`, 'info');
    })
    // 그림 업데이트 수신
    .listen('.drawing.updated', (event) => {
        console.log('Drawing received:', event);

        // 다른 사용자의 그림만 렌더링
        if (event.user_id !== currentUser.id) {
            drawLine(
                event.draw_data.points,
                event.draw_data.color,
                event.draw_data.width,
                event.draw_data.type === 'erase'
            );
        }
    })
    // 커서 위치 수신
    .listen('.cursor.moved', (event) => {
        if (event.user_id !== currentUser.id) {
            updateUserCursor(event.user_id, event.x, event.y);
        }
    })
    // 보드 초기화 수신
    .listen('.board.cleared', (event) => {
        clearCanvas();
        showNotification(`${event.user_name}님이 보드를 지웠습니다.`, 'warning');
    })
    .error((error) => {
        console.error('WebSocket error:', error);
    });

// ============================================
// 7. 사용자 목록 관리
// ============================================

function updateUserList(users) {
    const userListEl = document.getElementById('user-list');
    const userCountEl = document.getElementById('user-count');

    userListEl.innerHTML = '';
    userCountEl.textContent = users.length;

    users.forEach(user => {
        addUserToList(user);
    });
}

function addUserToList(user) {
    const userListEl = document.getElementById('user-list');
    const userCountEl = document.getElementById('user-count');

    const userEl = document.createElement('div');
    userEl.id = `user-${user.id}`;
    userEl.className = 'flex items-center gap-2 bg-gray-100 px-3 py-2 rounded';
    userEl.innerHTML = `
        <div class="w-3 h-3 rounded-full" style="background-color: ${user.color}"></div>
        <span class="text-sm">${user.name}</span>
        ${user.id === currentUser.id ? '<span class="text-xs text-gray-500">(나)</span>' : ''}
    `;

    userListEl.appendChild(userEl);
    userCountEl.textContent = parseInt(userCountEl.textContent) + 1;
}

function removeUserFromList(user) {
    const userEl = document.getElementById(`user-${user.id}`);
    const userCountEl = document.getElementById('user-count');

    if (userEl) {
        userEl.remove();
        userCountEl.textContent = parseInt(userCountEl.textContent) - 1;
    }
}

// ============================================
// 8. 커서 위치 관리
// ============================================

function broadcastCursorPosition(x, y) {
    // 서버로 커서 위치 전송하지 않고 클라이언트 이벤트 사용
    echo.join(`board.${boardId}`)
        .whisper('cursor-moved', {
            user_id: currentUser.id,
            x: x,
            y: y,
        });
}

function updateUserCursor(userId, x, y) {
    let cursorEl = userCursors.get(userId);

    if (!cursorEl) {
        cursorEl = document.createElement('div');
        cursorEl.className = 'absolute w-4 h-4 rounded-full pointer-events-none transition-all duration-100';
        cursorEl.style.backgroundColor = getUserColor(userId);
        document.getElementById('cursors-container').appendChild(cursorEl);
        userCursors.set(userId, cursorEl);
    }

    const rect = canvas.getBoundingClientRect();
    cursorEl.style.left = `${rect.left + (x * rect.width / canvas.width)}px`;
    cursorEl.style.top = `${rect.top + (y * rect.height / canvas.height)}px`;
}

function removeUserCursor(userId) {
    const cursorEl = userCursors.get(userId);
    if (cursorEl) {
        cursorEl.remove();
        userCursors.delete(userId);
    }
}

function getUserColor(userId) {
    const users = echo.join(`board.${boardId}`).subscription.members;
    const user = users.find(u => u.id === userId);
    return user?.color || '#000000';
}

// ============================================
// 9. 유틸리티 함수
// ============================================

let throttleTimer;
function throttle(callback, delay) {
    if (throttleTimer) return;

    throttleTimer = setTimeout(() => {
        callback();
        throttleTimer = null;
    }, delay);
}

function showNotification(message, type = 'info') {
    const colors = {
        info: 'bg-blue-500',
        warning: 'bg-yellow-500',
        error: 'bg-red-500',
        success: 'bg-green-500',
    };

    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-3 rounded shadow-lg z-50`;
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// ============================================
// 10. 초기 보드 데이터 로드
// ============================================

async function loadBoardData() {
    try {
        const response = await axios.get(`/boards/${boardId}/export`);
        const drawings = response.data.data;

        drawings.forEach(drawData => {
            drawLine(
                drawData.points,
                drawData.color,
                drawData.width,
                drawData.type === 'erase'
            );
        });

        console.log('Board data loaded:', drawings.length, 'drawings');
    } catch (error) {
        console.error('Failed to load board data:', error);
    }
}

// 페이지 로드 시 초기 데이터 로드
loadBoardData();
```

---

## 3. 성능 최적화

### 3.1 쓰로틀링 (Throttling)

그림 데이터를 매 이벤트마다 전송하지 않고 일정 간격으로 전송:

```javascript
// 50ms마다 한 번씩만 전송
const throttledDraw = throttle(sendDrawingData, 50);
```

### 3.2 배치 전송 (Batching)

여러 포인트를 모아서 한 번에 전송:

```javascript
let pointBuffer = [];
let batchTimer;

function addPointToBuffer(point) {
    pointBuffer.push(point);

    if (!batchTimer) {
        batchTimer = setTimeout(() => {
            sendDrawingData({
                type: 'line',
                points: pointBuffer,
                color: currentColor,
                width: brushSize,
            });

            pointBuffer = [];
            batchTimer = null;
        }, 100); // 100ms마다 배치 전송
    }
}
```

### 3.3 메시지 압축

대용량 좌표 데이터를 압축:

```javascript
function compressPoints(points) {
    // Ramer-Douglas-Peucker 알고리즘으로 좌표 간소화
    return simplifyPath(points, 1.0);
}

function simplifyPath(points, tolerance) {
    if (points.length <= 2) return points;

    // RDP 알고리즘 구현
    // ... (생략)
}
```

### 3.4 Canvas Offscreen 렌더링

```javascript
// Offscreen Canvas로 성능 개선
const offscreenCanvas = document.createElement('canvas');
const offscreenCtx = offscreenCanvas.getContext('2d');
offscreenCanvas.width = canvas.width;
offscreenCanvas.height = canvas.height;

// 백그라운드에서 렌더링 후 메인 캔버스로 복사
function renderToMainCanvas() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(offscreenCanvas, 0, 0);
}
```

---

## 4. 추가 기능

### 4.1 실행 취소 (Undo/Redo)

```javascript
const drawingHistory = [];
let historyIndex = -1;

function saveToHistory() {
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);

    // 현재 인덱스 이후의 히스토리 제거
    drawingHistory.splice(historyIndex + 1);

    // 새 상태 추가
    drawingHistory.push(imageData);
    historyIndex++;

    // 최대 50개까지만 저장
    if (drawingHistory.length > 50) {
        drawingHistory.shift();
        historyIndex--;
    }
}

function undo() {
    if (historyIndex > 0) {
        historyIndex--;
        ctx.putImageData(drawingHistory[historyIndex], 0, 0);
    }
}

function redo() {
    if (historyIndex < drawingHistory.length - 1) {
        historyIndex++;
        ctx.putImageData(drawingHistory[historyIndex], 0, 0);
    }
}

// 키보드 단축키
document.addEventListener('keydown', (e) => {
    if (e.ctrlKey && e.key === 'z') {
        e.preventDefault();
        undo();
    } else if (e.ctrlKey && e.key === 'y') {
        e.preventDefault();
        redo();
    }
});
```

### 4.2 도형 그리기 (사각형, 원, 화살표)

```javascript
let shapeType = null; // 'rectangle', 'circle', 'arrow'
let shapeStartPos = null;

function startShape(e) {
    if (!shapeType) return;

    shapeStartPos = getMousePos(e);
}

function drawShape(e) {
    if (!shapeType || !shapeStartPos) return;

    const currentPos = getMousePos(e);

    // 임시 캔버스에 도형 미리보기
    clearTempCanvas();

    switch (shapeType) {
        case 'rectangle':
            drawRectangle(shapeStartPos, currentPos);
            break;
        case 'circle':
            drawCircle(shapeStartPos, currentPos);
            break;
        case 'arrow':
            drawArrow(shapeStartPos, currentPos);
            break;
    }
}

function finishShape(e) {
    if (!shapeType || !shapeStartPos) return;

    const endPos = getMousePos(e);

    // 최종 도형 그리기 및 서버로 전송
    sendDrawingData({
        type: shapeType,
        start: shapeStartPos,
        end: endPos,
        color: currentColor,
        width: brushSize,
    });

    shapeStartPos = null;
}
```

### 4.3 텍스트 입력

```javascript
function addText(x, y, text) {
    ctx.font = '20px Arial';
    ctx.fillStyle = currentColor;
    ctx.fillText(text, x, y);

    sendDrawingData({
        type: 'text',
        position: { x, y },
        text: text,
        color: currentColor,
        fontSize: 20,
    });
}

canvas.addEventListener('dblclick', (e) => {
    const pos = getMousePos(e);
    const text = prompt('텍스트를 입력하세요:');

    if (text) {
        addText(pos.x, pos.y, text);
    }
});
```

### 4.4 이미지 업로드

```javascript
document.getElementById('upload-image').addEventListener('change', async (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();

    reader.onload = (event) => {
        const img = new Image();
        img.onload = () => {
            ctx.drawImage(img, 0, 0);

            // 서버로 전송
            sendDrawingData({
                type: 'image',
                imageData: event.target.result,
                width: img.width,
                height: img.height,
            });
        };
        img.src = event.target.result;
    };

    reader.readAsDataURL(file);
});
```

---

## 5. 프로덕션 고려사항

### 5.1 권한 관리

```php
// app/Policies/BoardPolicy.php
class BoardPolicy
{
    public function view(User $user, Board $board): bool
    {
        // 보드 소유자 또는 초대된 사용자만 접근 가능
        return $board->user_id === $user->id
            || $board->invitations()->where('user_id', $user->id)->exists();
    }

    public function edit(User $user, Board $board): bool
    {
        // 읽기 전용 모드 확인
        if ($board->settings['read_only'] ?? false) {
            return false;
        }

        return $this->view($user, $board);
    }
}
```

### 5.2 Rate Limiting

```php
// app/Http/Controllers/BoardController.php
public function draw(Request $request, int $boardId)
{
    // 1초에 최대 20번 그리기 제한
    RateLimiter::attempt(
        'draw:' . auth()->id() . ':' . $boardId,
        20, // 최대 시도 횟수
        function() use ($request, $boardId) {
            // 실제 그리기 로직
        },
        1 // 1초
    );
}
```

### 5.3 데이터 저장 최적화

```php
// 일정 시간마다 배치로 저장
use Illuminate\Support\Facades\Cache;

class BoardController extends Controller
{
    public function draw(Request $request, int $boardId)
    {
        // Redis에 임시 저장
        $cacheKey = "board:{$boardId}:buffer";
        $buffer = Cache::get($cacheKey, []);

        $buffer[] = [
            'user_id' => auth()->id(),
            'data' => $request->input('draw_data'),
            'timestamp' => now(),
        ];

        Cache::put($cacheKey, $buffer, 300); // 5분

        // 버퍼가 100개 이상이면 DB에 저장
        if (count($buffer) >= 100) {
            $this->flushBuffer($boardId, $buffer);
            Cache::forget($cacheKey);
        }

        // 브로드캐스트는 즉시 실행
        broadcast(new DrawingUpdated(...))->toOthers();
    }

    protected function flushBuffer(int $boardId, array $buffer): void
    {
        BoardDrawing::insert($buffer);
    }
}
```

### 5.4 스냅샷 저장

```php
// 주기적으로 보드 전체 이미지 저장
class SaveBoardSnapshot implements ShouldQueue
{
    public function handle(): void
    {
        $boards = Board::where('updated_at', '>', now()->subMinutes(5))->get();

        foreach ($boards as $board) {
            $drawings = $board->drawings()->get();

            // Canvas 이미지로 렌더링 (Node.js canvas 사용)
            $imageData = $this->renderToImage($drawings);

            // MinIO에 저장
            Storage::disk('minio')->put(
                "boards/{$board->id}/snapshot.png",
                $imageData
            );
        }
    }
}
```

---

## 6. 테스트

```php
// tests/Feature/Board/RealtimeBoardTest.php
namespace Tests\Feature\Board;

use App\Events\DrawingUpdated;
use App\Models\Board;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RealtimeBoardTest extends TestCase
{
    public function test_user_can_draw_on_board(): void
    {
        Event::fake([DrawingUpdated::class]);

        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/boards/{$board->id}/draw", [
                'draw_data' => [
                    'type' => 'line',
                    'points' => [
                        ['x' => 10, 'y' => 10],
                        ['x' => 20, 'y' => 20],
                    ],
                    'color' => '#000000',
                    'width' => 2,
                ],
            ])
            ->assertOk();

        Event::assertDispatched(DrawingUpdated::class);
    }

    public function test_user_can_clear_board(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/boards/{$board->id}/clear")
            ->assertOk();

        $this->assertDatabaseMissing('board_drawings', [
            'board_id' => $board->id,
        ]);
    }

    public function test_unauthorized_user_cannot_draw(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $board = Board::factory()->create(['user_id' => $user->id]);

        $this->actingAs($otherUser)
            ->postJson("/boards/{$board->id}/draw", [
                'draw_data' => ['type' => 'line'],
            ])
            ->assertForbidden();
    }
}
```

---

## 7. 배포 체크리스트

- [ ] Reverb 서버 프로덕션 실행 (Supervisor)
- [ ] SSL/TLS 인증서 적용 (wss://)
- [ ] Redis 확장 활성화 (수평 확장)
- [ ] Rate Limiting 설정
- [ ] CORS 및 Allowed Origins 설정
- [ ] 데이터베이스 인덱스 추가
- [ ] 보드 스냅샷 스케줄러 등록
- [ ] 모니터링 대시보드 구축 (Laravel Pulse)

---

## 결론

Laravel Reverb의 **Presence Channel**을 활용하면 실시간 공유 보드를 쉽게 구현할 수 있습니다.

### 핵심 기능

✅ 실시간 드로잉 동기화 (< 50ms)
✅ 참여자 목록 자동 관리
✅ 사용자별 커서 위치 공유
✅ 보드 저장 및 불러오기
✅ 확장 가능한 아키텍처

### 적용 가능한 도메인

- **협업 도구**: 화이트보드, 마인드맵, 다이어그램
- **교육**: 온라인 수업 칠판, 실시간 강의
- **디자인**: 공동 스케치, 프로토타이핑
- **게임**: 그림 퀴즈, Pictionary

---

**문서 버전:** 1.0
**작성일:** 2025-12-16
