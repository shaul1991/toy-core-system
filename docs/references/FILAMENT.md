# Laravel Filament - Admin Panel & UI Framework

> **패키지 목적:** Laravel를 위한 강력한 오픈소스 UI 프레임워크로 관리자 패널, 폼 빌더, 테이블 빌더 제공
> **프로젝트 적용:** 관리자 대시보드, CRUD 인터페이스, 데이터 관리 시스템, CMS, 백오피스 애플리케이션

---

## 개요

| 항목 | 설명 |
|------|------|
| **패키지명** | filament/filament |
| **저장소** | [GitHub](https://github.com/filamentphp/filament) |
| **공식 문서** | [Filament Docs](https://filamentphp.com/docs) |
| **라이선스** | MIT |
| **Stars** | 28.1k+ |
| **Contributors** | 1,171+ |
| **Laravel 요구사항** | 11.x, 12.x |
| **PHP 요구사항** | ^8.2+ |
| **다운로드** | 10M+ (전체 패키지 합계) |

---

## 주요 특징

### 1. 빠른 개발 속도

**Zero Configuration 시작:**
- Eloquent 모델 기반 자동 CRUD 생성
- 단 몇 줄의 코드로 완전한 관리자 패널 구축
- 즉시 사용 가능한 인증 및 권한 시스템

**코드 우선 접근 방식:**
- PHP만으로 선언적 UI 정의
- 빌드 프로세스 불필요 (No JS Fatigue)
- Livewire 기반 실시간 반응형 인터페이스

### 2. Panel Builder (관리자 패널)

**멀티 테넌트 패널:**
- 여러 독립적인 관리자 패널 생성 (Admin, Staff, Customer 등)
- 각 패널별 독립적인 라우트, 리소스, 인증
- 패널별 테마 및 브랜딩 커스터마이징

**내장 기능:**
- 인증 (로그인, 비밀번호 재설정, 이메일 인증)
- 권한 관리 (Roles & Permissions)
- 다크 모드 지원
- 글로벌 검색
- 알림 시스템
- 사용자 프로필 관리

### 3. Form Builder (폼 빌더)

**25+ 사전 제작 컴포넌트:**
- Text Input, Textarea, Select, Checkbox, Radio
- Rich Editor (Markdown, WYSIWYG)
- File Upload (이미지 미리보기, 다중 파일)
- Date/Time Picker
- Color Picker
- Key-Value (동적 필드 쌍)
- Repeater (동적 반복 필드)
- Relationship Select (Eloquent 관계 자동 로드)

**고급 기능:**
- 조건부 필드 표시/숨김
- 실시간 유효성 검증
- 의존 필드 (Dependent Fields)
- Wizard (다단계 폼)
- 섹션 및 탭 레이아웃

### 4. Table Builder (테이블 빌더)

**강력한 데이터 테이블:**
- 정렬, 필터링, 검색
- 페이지네이션 (Offset/Cursor)
- 대량 작업 (Bulk Actions)
- 인라인 편집
- 컬럼 숨기기/보이기
- 엑셀/CSV 내보내기

**컬럼 타입:**
- Text, Badge, Boolean, Color, Image
- Icon, Select, Tags
- 관계 컬럼 (Relationship)
- 집계 컬럼 (Sum, Avg, Count)
- 커스텀 렌더링

### 5. Actions & Modals

**컨텍스트 액션:**
- 버튼, 드롭다운, 아이콘 버튼
- 모달 폼 내장
- 확인 대화상자
- 알림 통합

**액션 유형:**
- Create, Edit, Delete, View
- Bulk Actions (일괄 작업)
- Custom Actions (커스텀 액션)
- Async Actions (비동기 처리)

### 6. Widgets & Dashboards

**대시보드 위젯:**
- Stats Cards (통계 카드)
- Chart Widgets (차트 - Chart.js 통합)
- Table Widgets (테이블)
- Custom Livewire Widgets

**실시간 데이터:**
- 폴링 기반 자동 갱신
- 웹소켓 통합 (Laravel Echo)
- 실시간 통계 업데이트

### 7. Notifications (알림)

**알림 타입:**
- 토스트 알림 (Toast)
- 데이터베이스 알림
- 브로드캐스트 알림
- 액션 버튼 포함 알림

**위치 및 스타일:**
- 상단/하단, 좌/우 배치
- Success, Warning, Danger, Info 스타일
- 지속 시간 설정
- 닫기 버튼 옵션

---

## 개발 생산성 지표

전통적인 Laravel 수동 개발 대비 Filament 사용 시:

| 지표 | 개선율 |
|------|--------|
| **CRUD 개발 시간** | -80% (자동 생성 + 사전 제작 컴포넌트) |
| **UI 일관성** | +100% (통일된 디자인 시스템) |
| **코드 유지보수** | -60% (선언적 코드, 적은 보일러플레이트) |
| **반응형 UI** | 기본 제공 (모바일/태블릿 최적화) |
| **접근성** | WCAG 2.1 준수 (키보드 네비게이션, ARIA) |

---

## 설치 및 요구사항

### Artisan 설치

```bash
# Composer로 Filament Panel Builder 설치
composer require filament/filament:"^4.0"

# 관리자 패널 설치
php artisan filament:install --panels

# 관리자 사용자 생성
php artisan make:filament-user
```

설치 시 자동으로 수행되는 작업:
1. `app/Providers/Filament/AdminPanelProvider.php` 생성
2. `app/Filament/Resources/` 디렉토리 생성
3. `app/Filament/Pages/` 디렉토리 생성
4. `app/Filament/Widgets/` 디렉토리 생성
5. 기본 라우트 등록 (`/admin`)

### 필수 요구사항

| 항목 | 버전 | 필수 여부 |
|------|------|----------|
| **Laravel** | 11.x, 12.x | ✅ 필수 |
| **PHP** | 8.2+ | ✅ 필수 |
| **Livewire** | 3.x | ✅ 필수 (자동 설치) |
| **Tailwind CSS** | 3.x+ | ✅ 필수 (자동 설정) |
| **Alpine.js** | 3.x+ | ✅ 필수 (자동 설정) |

### 추가 패키지 (선택)

```bash
# Spatie Media Library 통합 (고급 파일 관리)
composer require filament/spatie-laravel-media-library-plugin

# Spatie Tags 통합
composer require filament/spatie-laravel-tags-plugin

# Spatie Settings 통합 (설정 관리)
composer require filament/spatie-laravel-settings-plugin

# 엑셀 내보내기
composer require pxlrbt/filament-excel
```

---

## Panel Provider 설정

`app/Providers/Filament/AdminPanelProvider.php`:

```php
namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->darkMode(true)
            ->brandName('Toy Core System')
            ->brandLogo(asset('images/logo.svg'))
            ->favicon(asset('favicon.ico'))
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full');
    }
}
```

---

## Resource 생성 (CRUD)

### Artisan 명령어

```bash
# Resource 생성 (Model 기반)
php artisan make:filament-resource Post

# 간단한 Resource (Create, Edit 통합)
php artisan make:filament-resource Post --simple

# 뷰 전용 Resource (읽기 전용)
php artisan make:filament-resource Post --view

# Soft Deletes 지원
php artisan make:filament-resource Post --soft-deletes

# 생성 옵션:
# --generate: 기존 Model에서 필드 자동 생성
```

### Resource 클래스 예시

`app/Filament/Resources/PostResource.php`:

```php
namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) =>
                        $set('slug', \Str::slug($state))
                    ),

                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'archived' => 'Archived',
                    ])
                    ->default('draft')
                    ->required(),

                Forms\Components\MarkdownEditor::make('content')
                    ->required()
                    ->columnSpan('full')
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'link',
                        'heading',
                        'bulletList',
                        'orderedList',
                        'codeBlock',
                    ]),

                Forms\Components\FileUpload::make('featured_image')
                    ->image()
                    ->imageEditor()
                    ->maxSize(2048)
                    ->directory('posts/images'),

                Forms\Components\Select::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                    ]),

                Forms\Components\TagsInput::make('tags')
                    ->separator(','),

                Forms\Components\DateTimePicker::make('published_at')
                    ->label('Publish Date')
                    ->default(now()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('featured_image')
                    ->size(60)
                    ->circular(),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'success' => 'published',
                        'warning' => 'archived',
                    ]),

                Tables\Columns\TextColumn::make('category.name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('views_count')
                    ->label('Views')
                    ->sortable()
                    ->counts('views'),

                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->published_at?->format('Y-m-d H:i')),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'archived' => 'Archived',
                    ]),

                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),

                Tables\Filters\Filter::make('published')
                    ->query(fn ($query) => $query->whereNotNull('published_at')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('publish')
                        ->label('Publish')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => 'published'])),
                ]),
            ])
            ->defaultSort('published_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers 추가
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
```

---

## Dashboard & Widgets

### Stats Widget 생성

```bash
php artisan make:filament-widget StatsOverview --stats-overview
```

`app/Filament/Widgets/StatsOverview.php`:

```php
namespace App\Filament\Widgets;

use App\Models\Post;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Posts', Post::count())
                ->description('Published posts: ' . Post::where('status', 'published')->count())
                ->descriptionIcon('heroicon-o-document-text')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

            Stat::make('Total Users', User::count())
                ->description('New users this month')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Total Views', Post::sum('views_count'))
                ->description('7% increase')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('warning'),
        ];
    }
}
```

### Chart Widget 생성

```bash
php artisan make:filament-widget PostsChart --chart
```

`app/Filament/Widgets/PostsChart.php`:

```php
namespace App\Filament\Widgets;

use App\Models\Post;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PostsChart extends ChartWidget
{
    protected static ?string $heading = 'Posts per Month';

    protected function getData(): array
    {
        $data = Post::select(
            DB::raw('COUNT(*) as count'),
            DB::raw('MONTH(created_at) as month')
        )
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Posts created',
                    'data' => array_values($data),
                    'backgroundColor' => '#36A2EB',
                    'borderColor' => '#9BD0F5',
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
```

---

## Custom Pages

### 커스텀 페이지 생성

```bash
php artisan make:filament-page Settings
```

`app/Filament/Pages/Settings.php`:

```php
namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;

class Settings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.settings';

    protected static ?string $navigationGroup = 'System';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'site_name' => config('app.name'),
            'site_description' => config('app.description'),
            'maintenance_mode' => app()->isDownForMaintenance(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Settings')
                    ->schema([
                        Forms\Components\TextInput::make('site_name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('site_description')
                            ->maxLength(500),

                        Forms\Components\Toggle::make('maintenance_mode')
                            ->label('Maintenance Mode'),
                    ]),

                Forms\Components\Section::make('Email Settings')
                    ->schema([
                        Forms\Components\TextInput::make('mail_from_address')
                            ->email()
                            ->required(),

                        Forms\Components\TextInput::make('mail_from_name')
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // 설정 저장 로직
        config(['app.name' => $data['site_name']]);

        $this->notify('success', 'Settings saved successfully!');
    }
}
```

---

## 실전 예제

### 1. Post 도메인 관리자 인터페이스

**Resource 자동 생성:**

```bash
php artisan make:filament-resource Post --generate
```

**Relations Manager (댓글 관리):**

```bash
php artisan make:filament-relation-manager PostResource comments content
```

`app/Filament/Resources/PostResource/RelationManagers/CommentsRelationManager.php`:

```php
namespace App\Filament\Resources\PostResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('author_name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('content')
                    ->required()
                    ->maxLength(1000),

                Forms\Components\Toggle::make('is_approved')
                    ->default(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('author_name'),
                Tables\Columns\TextColumn::make('content')->limit(50),
                Tables\Columns\IconColumn::make('is_approved')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_approved')
                    ->label('Approved')
                    ->placeholder('All comments')
                    ->trueLabel('Approved only')
                    ->falseLabel('Not approved only'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
```

### 2. User Activity 로그 뷰어

```bash
php artisan make:filament-resource UserActivity --view
```

`app/Filament/Resources/UserActivityResource.php`:

```php
namespace App\Filament\Resources;

use App\Filament\Resources\UserActivityResource\Pages;
use App\Models\UserActivity;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserActivityResource extends Resource
{
    protected static ?string $model = UserActivity::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Monitoring';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('action')
                    ->badge()
                    ->colors([
                        'success' => 'login',
                        'warning' => 'update',
                        'danger' => 'delete',
                    ]),

                Tables\Columns\TextColumn::make('ip_address'),

                Tables\Columns\TextColumn::make('user_agent')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->user_agent),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->options([
                        'login' => 'Login',
                        'logout' => 'Logout',
                        'create' => 'Create',
                        'update' => 'Update',
                        'delete' => 'Delete',
                    ]),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('10s'); // 10초마다 자동 갱신
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserActivities::route('/'),
        ];
    }
}
```

### 3. File 관리 (MinIO 통합)

```bash
php artisan make:filament-resource File
```

`app/Filament/Resources/FileResource.php`:

```php
namespace App\Filament\Resources;

use App\Filament\Resources\FileResource\Pages;
use App\Models\File;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FileResource extends Resource
{
    protected static ?string $model = File::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('path')
                    ->label('File')
                    ->disk('minio')
                    ->directory('uploads')
                    ->visibility('private')
                    ->maxSize(10240)
                    ->acceptedFileTypes([
                        'image/*',
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ])
                    ->imageEditor()
                    ->imageEditorAspectRatios([
                        null,
                        '16:9',
                        '4:3',
                        '1:1',
                    ])
                    ->required(),

                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Textarea::make('description')
                    ->maxLength(500),

                Forms\Components\TagsInput::make('tags')
                    ->separator(','),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->label('Preview')
                    ->disk('minio')
                    ->size(60)
                    ->defaultImageUrl('/images/file-placeholder.png'),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('mime_type')
                    ->badge(),

                Tables\Columns\TextColumn::make('size')
                    ->formatStateUsing(fn ($state) =>
                        $state < 1024 ? $state . ' B' :
                        ($state < 1048576 ? round($state / 1024, 2) . ' KB' :
                        round($state / 1048576, 2) . ' MB')
                    ),

                Tables\Columns\TextColumn::make('downloads_count')
                    ->counts('downloads')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('mime_type')
                    ->options([
                        'image/jpeg' => 'JPEG',
                        'image/png' => 'PNG',
                        'application/pdf' => 'PDF',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record) => route('files.download', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFiles::route('/'),
            'create' => Pages\CreateFile::route('/create'),
            'edit' => Pages\EditFile::route('/{record}/edit'),
        ];
    }
}
```

### 4. Timer 도메인 모니터링

```bash
php artisan make:filament-widget ActiveTimersWidget --table
```

`app/Filament/Widgets/ActiveTimersWidget.php`:

```php
namespace App\Filament\Widgets;

use App\Models\Timer;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ActiveTimersWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Timer::query()
                    ->where('target_time', '>', now())
                    ->orderBy('target_time', 'asc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User'),

                Tables\Columns\TextColumn::make('title'),

                Tables\Columns\TextColumn::make('target_time')
                    ->dateTime()
                    ->since()
                    ->description(fn ($record) => $record->target_time->diffForHumans()),

                Tables\Columns\TextColumn::make('remaining')
                    ->label('Time Remaining')
                    ->getStateUsing(fn ($record) =>
                        now()->diffInSeconds($record->target_time)
                    )
                    ->formatStateUsing(fn ($state) =>
                        gmdate('H:i:s', $state)
                    )
                    ->color('warning'),
            ])
            ->poll('1s'); // 1초마다 갱신
    }
}
```

---

## 멀티 패널 설정

### Staff Panel 추가

```bash
php artisan make:filament-panel staff
```

`app/Providers/Filament/StaffPanelProvider.php`:

```php
namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;

class StaffPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('staff')
            ->path('staff')
            ->login()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Staff/Resources'), for: 'App\\Filament\\Staff\\Resources')
            ->discoverPages(in: app_path('Filament/Staff/Pages'), for: 'App\\Filament\\Staff\\Pages')
            ->middleware([
                // Staff middleware
            ])
            ->authMiddleware([
                // Staff auth middleware
            ]);
    }
}
```

---

## 권한 및 인증

### Policy 통합

`app/Policies/PostPolicy.php`:

```php
namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_posts');
    }

    public function view(User $user, Post $post): bool
    {
        return $user->can('view_posts');
    }

    public function create(User $user): bool
    {
        return $user->can('create_posts');
    }

    public function update(User $user, Post $post): bool
    {
        return $user->can('update_posts') || $post->user_id === $user->id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->can('delete_posts');
    }
}
```

### Resource에 Policy 적용

Filament은 자동으로 Laravel Policy를 인식합니다:

```php
// app/Filament/Resources/PostResource.php
public static function canViewAny(): bool
{
    return auth()->user()->can('viewAny', Post::class);
}

// 또는 Policy가 정의되어 있으면 자동으로 적용됨
```

---

## 프로젝트 적용 시 고려사항

### 1. 기존 도메인과 통합

**Post 도메인:**
- Filament Resource로 게시물 CRUD 관리
- Toast UI Editor를 Filament Form에 커스텀 컴포넌트로 통합
- 카테고리, 태그 관리 인터페이스 구축

**User Activity 도메인:**
- MongoDB 데이터를 Filament Table로 표시
- 실시간 로그 스트리밍 (폴링 또는 Livewire Echo)
- 필터링 및 검색 기능

**Timer 도메인:**
- 타이머 생성/수정 폼
- 활성 타이머 위젯 (대시보드)
- 만료된 타이머 아카이브

**File 도메인:**
- MinIO 파일 업로드/다운로드 인터페이스
- 이미지 에디터 통합
- 파일 메타데이터 관리

### 2. Notification 도메인과 통합

```php
// Filament 알림 → Notification Service 통합
use Filament\Notifications\Notification;

Notification::make()
    ->title('Order created successfully')
    ->success()
    ->send();

// 데이터베이스 알림
Notification::make()
    ->title('New comment on your post')
    ->icon('heroicon-o-chat-bubble-left-right')
    ->body('John Doe commented on your post.')
    ->actions([
        \Filament\Notifications\Actions\Action::make('view')
            ->button()
            ->url(route('posts.show', $post)),
    ])
    ->sendToDatabase($user);
```

### 3. BFF와 분리

Filament은 **관리자 전용 인터페이스**로 사용:
- BFF 레이어: 최종 사용자 API (API Gateway)
- Filament Panel: 관리자/운영자 전용 UI
- Core Service: 비즈니스 로직 공유

**분리 전략:**
```
┌─────────────┐     ┌─────────────┐
│   BFF API   │     │  Filament   │
│  (External) │     │  (Internal) │
└──────┬──────┘     └──────┬──────┘
       │                   │
       └───────┬───────────┘
               │
        ┌──────▼──────┐
        │ Core Service│
        └─────────────┘
```

### 4. 성능 최적화

**Eager Loading:**
```php
// Resource에서 관계 미리 로드
protected static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->with(['category', 'user', 'tags']);
}
```

**Caching:**
```php
// 통계 위젯 캐싱
protected function getStats(): array
{
    return cache()->remember('dashboard-stats', 300, function () {
        return [
            Stat::make('Total Posts', Post::count()),
            // ...
        ];
    });
}
```

---

## 커스터마이징

### 커스텀 테마

```bash
php artisan make:filament-theme
```

`tailwind.config.js`:

```javascript
export default {
    content: [
        './resources/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                primary: {
                    50: '#fef2f2',
                    100: '#fee2e2',
                    // ...
                },
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
}
```

### 커스텀 Form 컴포넌트

```bash
php artisan make:filament-form-component JsonEditor
```

`app/Filament/Forms/Components/JsonEditor.php`:

```php
namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

class JsonEditor extends Field
{
    protected string $view = 'filament.forms.components.json-editor';

    public function validate(): static
    {
        $this->rule('json');
        return $this;
    }
}
```

`resources/views/filament/forms/components/json-editor.blade.php`:

```blade
<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div wire:ignore>
        <textarea
            id="{{ $getId() }}"
            {{ $attributes->merge($getExtraAttributes())->class(['form-textarea']) }}
        >{{ $getState() }}</textarea>
    </div>

    <script>
        // JSON Editor 초기화 로직
        const editor = CodeMirror.fromTextArea(
            document.getElementById('{{ $getId() }}'),
            { mode: 'application/json' }
        );

        editor.on('change', () => {
            @this.set('{{ $getStatePath() }}', editor.getValue());
        });
    </script>
</x-dynamic-component>
```

---

## 테스트 예시

```php
// tests/Feature/Filament/PostResourceTest.php
namespace Tests\Feature\Filament;

use App\Filament\Resources\PostResource;
use App\Models\Post;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class PostResourceTest extends TestCase
{
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'is_admin' => true,
        ]);
    }

    public function test_can_list_posts(): void
    {
        $this->actingAs($this->admin);

        Post::factory()->count(10)->create();

        Livewire::test(PostResource\Pages\ListPosts::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(Post::all());
    }

    public function test_can_create_post(): void
    {
        $this->actingAs($this->admin);

        $newData = Post::factory()->make();

        Livewire::test(PostResource\Pages\CreatePost::class)
            ->fillForm([
                'title' => $newData->title,
                'slug' => $newData->slug,
                'content' => $newData->content,
                'status' => 'draft',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(Post::class, [
            'title' => $newData->title,
            'slug' => $newData->slug,
        ]);
    }

    public function test_can_edit_post(): void
    {
        $this->actingAs($this->admin);

        $post = Post::factory()->create();

        Livewire::test(PostResource\Pages\EditPost::class, [
            'record' => $post->getRouteKey(),
        ])
            ->fillForm([
                'title' => 'Updated Title',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $post->refresh();

        $this->assertEquals('Updated Title', $post->title);
    }

    public function test_can_delete_post(): void
    {
        $this->actingAs($this->admin);

        $post = Post::factory()->create();

        Livewire::test(PostResource\Pages\EditPost::class, [
            'record' => $post->getRouteKey(),
        ])
            ->callAction('delete');

        $this->assertModelMissing($post);
    }

    public function test_validates_required_fields(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(PostResource\Pages\CreatePost::class)
            ->fillForm([
                'title' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required']);
    }
}
```

---

## 플러그인 생태계

| 플러그인 | 기능 | 저장소 |
|---------|------|--------|
| **Filament Excel** | 테이블 엑셀 내보내기 | [GitHub](https://github.com/pxlrbt/filament-excel) |
| **Filament Breezy** | 사용자 프로필, 2FA | [GitHub](https://github.com/jeffgreco13/filament-breezy) |
| **Filament Shield** | 권한 관리 (Spatie Permission) | [GitHub](https://github.com/bezhanSalleh/filament-shield) |
| **Filament Curator** | 미디어 라이브러리 | [GitHub](https://github.com/awcodes/filament-curator) |
| **Filament Tiptap Editor** | WYSIWYG 에디터 | [GitHub](https://github.com/awcodes/filament-tiptap-editor) |
| **Filament Approval** | 승인 워크플로우 | [GitHub](https://github.com/eightynine/filament-approvals) |
| **Filament Activity Log** | Spatie Activity Log 통합 | [GitHub](https://github.com/ralphjsmit/laravel-filament-activitylog) |

---

## 프로젝트 적용 로드맵

### Phase 1: 기본 설정 및 설치

- [ ] Filament 패키지 설치 (`composer require filament/filament`)
- [ ] Admin Panel 설정 (`php artisan filament:install --panels`)
- [ ] 관리자 사용자 생성 (`php artisan make:filament-user`)
- [ ] 기본 테마 및 브랜딩 설정

### Phase 2: 도메인별 Resource 구축

- [ ] Post Resource 생성 (CRUD + 댓글 관리)
- [ ] User Activity Viewer (읽기 전용)
- [ ] File Resource (MinIO 통합)
- [ ] Timer Resource (생성/수정/모니터링)
- [ ] User Management Resource

### Phase 3: Dashboard & Widgets

- [ ] Stats Overview Widget (통계 카드)
- [ ] Active Timers Widget (실시간 타이머)
- [ ] Recent Activities Widget (최근 활동)
- [ ] Posts Chart Widget (월별 게시물 차트)

### Phase 4: 권한 및 보안

- [ ] Policy 정의 (PostPolicy, FilePolicy 등)
- [ ] Filament Shield 설치 (권한 관리)
- [ ] 역할별 리소스 접근 제어
- [ ] 감사 로그 (Activity Log)

### Phase 5: 커스터마이징

- [ ] 커스텀 테마 적용
- [ ] Toast UI Editor 통합 (Post 도메인)
- [ ] 커스텀 액션 및 버튼
- [ ] 알림 시스템 통합

### Phase 6: 프로덕션 배포

- [ ] 성능 최적화 (Eager Loading, Caching)
- [ ] 프로덕션 환경 설정
- [ ] 백업 및 복구 전략
- [ ] 모니터링 대시보드

---

## 주의사항

### 1. 라우트 충돌 방지

```php
// Filament 패널은 독립적인 라우트 사용
// /admin - Filament Admin Panel
// /api/* - BFF API (충돌 없음)
// /internal/* - Core Service API (충돌 없음)
```

### 2. 인증 분리

```php
// Filament은 별도의 인증 가드 사용 가능
'auth' => [
    'guard' => 'admin',
    'pages' => [
        'login' => \Filament\Pages\Auth\Login::class,
    ],
],
```

### 3. N+1 쿼리 방지

```php
// Resource에서 Eager Loading 필수
protected static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->with(['user', 'category', 'tags']);
}
```

### 4. 대용량 테이블 처리

```php
// 큰 테이블은 Cursor Pagination 사용
public static function table(Table $table): Table
{
    return $table
        ->paginated([10, 25, 50, 100])
        ->defaultPaginationPageOption(25)
        ->simplePagination(); // 또는 Cursor
}
```

---

## 참고 링크

### 공식 문서 및 저장소

- [GitHub Repository](https://github.com/filamentphp/filament)
- [Filament Documentation](https://filamentphp.com/docs)
- [Filament Demo](https://demo.filamentphp.com)
- [Filament Plugin Directory](https://filamentphp.com/plugins)

### 관련 아티클

- [From Zero to Admin Panel in 60 Seconds: Laravel 12 + Filament 3.3](https://medium.com/@ashot.bes/from-zero-to-admin-panel-in-60-seconds-laravel-12-filament-3-3-is-a-game-changer-0608cbdf1d84)
- [Filament: The Ultimate Laravel UI Framework](https://www.blog.brightcoding.dev/2025/09/19/filament-the-ultimate-laravel-ui-framework-for-admin-panels-and-apps/)
- [Filament Crash-Course](https://tighten.com/insights/filament-crash-course-create-a-customizable-admin-panel-in-minutes/)

### 커뮤니티

- [Discord Community](https://discord.gg/filamentphp)
- [GitHub Discussions](https://github.com/filamentphp/filament/discussions)
- [Stack Overflow Tag](https://stackoverflow.com/questions/tagged/filament)

---

## 결론

**Laravel Filament**은 Laravel 애플리케이션의 관리자 패널 및 백오피스 구축을 위한 가장 강력하고 현대적인 솔루션입니다.

현재 프로젝트의 **Post, User Activity, Timer, File** 도메인에 적용하면, 직관적이고 강력한 관리자 인터페이스를 빠르게 구축할 수 있습니다.

### 주요 장점

✅ 빠른 개발 속도 (80% 개발 시간 단축)
✅ 풍부한 사전 제작 컴포넌트 (25+ Form, Table)
✅ Livewire 기반 실시간 반응형 UI
✅ 확장 가능한 플러그인 생태계
✅ 접근성 및 반응형 디자인 기본 제공
✅ 멀티 패널 지원 (Admin, Staff, Customer)
✅ MIT 라이선스 (상업적 사용 가능)

### 다음 단계

- Filament 설치 및 Admin Panel 설정
- Post 도메인 Resource 생성
- Dashboard 및 Widgets 구축
- 권한 및 인증 시스템 통합
- 프로덕션 배포 및 최적화

---

**문서 버전:** 1.0
**작성일:** 2025-12-16
**마지막 업데이트:** 2025-12-16
