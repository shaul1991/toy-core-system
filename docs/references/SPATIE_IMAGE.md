# Spatie Image - PHP Image Manipulation Package

> **패키지 목적:** 표현력 있는 API로 이미지 조작을 간소화하는 PHP 패키지
> **프로젝트 적용:** File 도메인 확장 시 이미지 처리 기능 구현 참고

---

## 개요

| 항목 | 설명 |
|------|------|
| **패키지명** | spatie/image |
| **저장소** | [GitHub](https://github.com/spatie/image) |
| **공식 문서** | [Spatie Docs](https://spatie.be/docs/image/v3/introduction) |
| **Packagist** | [packagist.org](https://packagist.org/packages/spatie/image) |
| **라이선스** | MIT |
| **Stars** | 1.3k+ |
| **최신 버전** | 3.8.6 (2025-09-25) |
| **PHP 요구사항** | ^8.2 |

---

## 주요 특징

### 1. 표현력 있는 Fluent API

메서드 체이닝을 통해 직관적이고 읽기 쉬운 코드 작성:

```php
Image::load('photo.jpg')
    ->width(500)
    ->height(300)
    ->greyscale()
    ->sharpen(10)
    ->save('output.jpg');
```

### 2. 다양한 이미지 포맷 지원

- JPEG
- PNG
- GIF
- WEBP

### 3. 강력한 Intervention Image 기반

내부적으로 [Intervention Image](http://image.intervention.io/) 라이브러리를 사용하여 안정성과 성능 보장.

### 4. GD 및 Imagick 드라이버 지원

시스템 환경에 따라 GD 또는 Imagick 라이브러리 사용 가능.

---

## 설치 및 요구사항

### Composer 설치

```bash
composer require spatie/image
```

### 필수 PHP 확장

| 확장 | 버전 | 필수 여부 |
|------|------|----------|
| **exif** | - | ✅ 필수 (v1.5.3+) |
| **json** | - | ✅ 필수 |
| **mbstring** | - | ✅ 필수 |
| **GD** 또는 **Imagick** | - | ✅ 둘 중 하나 필수 |

### EXIF 확장 활성화 확인

```bash
php -m | grep exif
```

---

## 기본 사용법

### 1. 이미지 로드 및 저장

```php
use Spatie\Image\Image;

// 이미지 로드 후 다른 경로에 저장
Image::load('input.jpg')->save('output.jpg');

// 기존 파일 덮어쓰기
Image::load('photo.jpg')
    ->width(500)
    ->save(); // 원본 파일에 저장
```

### 2. 크기 조정 (Resize)

```php
// 너비만 지정 (비율 유지)
Image::load('photo.jpg')
    ->width(500)
    ->save('resized.jpg');

// 높이만 지정 (비율 유지)
Image::load('photo.jpg')
    ->height(300)
    ->save('resized.jpg');

// 너비와 높이 모두 지정
Image::load('photo.jpg')
    ->width(500)
    ->height(300)
    ->save('resized.jpg');
```

### 3. 크롭 및 핏 (Crop & Fit)

```php
// 지정된 크기에 맞게 자르기
Image::load('photo.jpg')
    ->fit(500, 300)
    ->save('fitted.jpg');

// 수동 크롭 (x, y, width, height)
Image::load('photo.jpg')
    ->manualCrop(100, 100, 400, 300)
    ->save('cropped.jpg');

// 일반 크롭
Image::load('photo.jpg')
    ->crop(500, 300)
    ->save('cropped.jpg');
```

---

## 주요 메서드

### 크기 조정

| 메서드 | 파라미터 | 설명 |
|--------|----------|------|
| `width(int $width)` | 너비 (픽셀) | 너비 조정 (비율 유지) |
| `height(int $height)` | 높이 (픽셀) | 높이 조정 (비율 유지) |
| `fit(int $width, int $height)` | 너비, 높이 | 지정 크기에 맞게 자르기 |
| `crop(int $width, int $height)` | 너비, 높이 | 크롭 |
| `manualCrop(int $x, int $y, int $width, int $height)` | x, y, 너비, 높이 | 수동 크롭 |

### 필터 및 효과

| 메서드 | 파라미터 | 설명 |
|--------|----------|------|
| `greyscale()` | - | 흑백 변환 |
| `sepia()` | - | 세피아 톤 적용 |
| `blur(int $amount)` | 블러 강도 (1-100) | 블러 효과 |
| `sharpen(int $amount)` | 샤픈 강도 (1-100) | 샤픈 효과 |
| `brightness(int $amount)` | 밝기 (-100 ~ 100) | 밝기 조정 |

### 품질 및 회전

| 메서드 | 파라미터 | 설명 |
|--------|----------|------|
| `quality(int $quality)` | 품질 (1-100) | 이미지 품질 설정 |
| `orientation(int $degrees)` | 각도 | 이미지 회전 (90, 180, 270) |

### 테두리 및 배경

| 메서드 | 파라미터 | 설명 |
|--------|----------|------|
| `border(int $width, string $color)` | 너비, 색상 | 테두리 추가 |
| `background(string $color)` | 색상 (hex) | 배경색 설정 |

### 저장

| 메서드 | 파라미터 | 설명 |
|--------|----------|------|
| `save(?string $path)` | 저장 경로 (optional) | 이미지 저장 (경로 생략 시 원본 덮어쓰기) |

---

## 실전 예제

### 썸네일 생성

```php
use Spatie\Image\Image;

// 정사각형 썸네일 (500x500)
Image::load('uploads/photo.jpg')
    ->fit(500, 500)
    ->quality(80)
    ->save('thumbs/photo_thumb.jpg');
```

### 프로필 이미지 최적화

```php
// 원형 프로필 이미지 (300x300, 고품질)
Image::load('profile.jpg')
    ->fit(300, 300)
    ->sharpen(5)
    ->quality(90)
    ->save('profile_optimized.jpg');
```

### 배치 이미지 처리

```php
$images = glob('uploads/*.jpg');

foreach ($images as $image) {
    $filename = basename($image);

    Image::load($image)
        ->width(800)
        ->quality(75)
        ->save("optimized/{$filename}");
}
```

### 워터마크 및 필터 적용

```php
Image::load('photo.jpg')
    ->width(1200)
    ->sepia()
    ->brightness(-10)
    ->quality(85)
    ->save('vintage_photo.jpg');
```

---

## Laravel 통합

### Laravel Storage와 함께 사용

```php
use Illuminate\Support\Facades\Storage;
use Spatie\Image\Image;

// Storage에서 파일 가져오기
$tempPath = Storage::disk('local')->path('uploads/photo.jpg');

Image::load($tempPath)
    ->width(500)
    ->save();

// 처리 후 다시 Storage에 저장
Storage::disk('public')->put('thumbs/photo_thumb.jpg', file_get_contents($tempPath));
```

### File 도메인과 통합 (예시)

```php
// app/Services/ImageProcessingService.php
namespace App\Services;

use App\Models\File;
use Spatie\Image\Image;
use Illuminate\Support\Facades\Storage;

class ImageProcessingService
{
    /**
     * 이미지 파일에 대한 썸네일 생성
     */
    public function generateThumbnail(File $file, int $width = 300, int $height = 300): File
    {
        // 원본 파일 경로
        $originalPath = Storage::disk($file->disk)->path($file->full_path);

        // 썸네일 저장 경로
        $thumbnailPath = str_replace($file->stored_name, 'thumb_' . $file->stored_name, $originalPath);

        // 썸네일 생성
        Image::load($originalPath)
            ->fit($width, $height)
            ->quality(80)
            ->save($thumbnailPath);

        // 썸네일을 새 File 레코드로 저장
        $thumbnailFile = File::create([
            'original_name' => 'thumb_' . $file->original_name,
            'stored_name' => 'thumb_' . $file->stored_name,
            'path' => $file->path,
            'disk' => $file->disk,
            'mime_type' => $file->mime_type,
            'size' => filesize($thumbnailPath),
            'visibility' => $file->visibility,
            'metadata' => [
                'type' => 'thumbnail',
                'parent_id' => $file->id,
                'width' => $width,
                'height' => $height,
            ],
        ]);

        return $thumbnailFile;
    }

    /**
     * 이미지 최적화 (크기 축소 + 품질 조정)
     */
    public function optimizeImage(File $file, int $maxWidth = 1920, int $quality = 85): void
    {
        $imagePath = Storage::disk($file->disk)->path($file->full_path);

        Image::load($imagePath)
            ->width($maxWidth)
            ->quality($quality)
            ->save();

        // 파일 크기 업데이트
        $file->update([
            'size' => filesize($imagePath),
        ]);
    }
}
```

---

## File 도메인 확장 시 고려사항

현재 프로젝트의 [File 도메인](../FILE.md)에 이미지 처리 기능을 추가할 경우:

### 1. 이미지 타입 검증

```php
// FileService에 이미지 검증 메서드 추가
public function isImage(File $file): bool
{
    return str_starts_with($file->mime_type, 'image/');
}
```

### 2. 자동 썸네일 생성 (Event Listener)

```php
// app/Domain/File/Listeners/GenerateThumbnail.php
namespace App\Domain\File\Listeners;

use App\Domain\File\Events\FileUploaded;
use App\Services\ImageProcessingService;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenerateThumbnail implements ShouldQueue
{
    public function __construct(
        private ImageProcessingService $imageProcessing
    ) {}

    public function handle(FileUploaded $event): void
    {
        $file = $event->file;

        // 이미지 파일인 경우에만 썸네일 생성
        if (str_starts_with($file->mime_type, 'image/')) {
            $this->imageProcessing->generateThumbnail($file);
        }
    }
}
```

### 3. API 엔드포인트 추가

```php
// POST /internal/files/{id}/thumbnail
// POST /internal/files/{id}/optimize
// GET /internal/files/{id}/versions (원본, 썸네일, 최적화 버전 목록)
```

### 4. 메타데이터 활용

```php
// 이미지 처리 시 메타데이터에 정보 저장
'metadata' => [
    'original_width' => 1920,
    'original_height' => 1080,
    'processed' => true,
    'thumbnail_id' => 'uuid-of-thumbnail',
    'optimized' => true,
]
```

---

## 성능 고려사항

### 1. 드라이버 선택

| 드라이버 | 장점 | 단점 |
|---------|------|------|
| **Imagick** | 더 빠름, 더 많은 포맷 지원 | 별도 설치 필요 |
| **GD** | PHP 기본 제공 | 상대적으로 느림 |

### 2. 대용량 이미지 처리

```php
// 메모리 제한 증가
ini_set('memory_limit', '512M');

// 큰 이미지는 단계적으로 축소
Image::load('huge_image.jpg')
    ->width(2000)  // 1차 축소
    ->save('temp.jpg');

Image::load('temp.jpg')
    ->width(800)   // 2차 축소
    ->save('final.jpg');
```

### 3. 비동기 처리

```php
// Queue Job으로 이미지 처리
dispatch(new ProcessImageJob($fileId));
```

---

## 자주 사용하는 패턴

### 1. 반응형 이미지 세트 생성

```php
$sizes = [
    'thumb' => 150,
    'small' => 300,
    'medium' => 600,
    'large' => 1200,
];

foreach ($sizes as $name => $width) {
    Image::load('original.jpg')
        ->width($width)
        ->quality(85)
        ->save("variants/{$name}.jpg");
}
```

### 2. 이미지 품질 자동 최적화

```php
public function autoOptimize(string $path): void
{
    $fileSize = filesize($path);

    // 파일 크기에 따라 품질 조정
    $quality = match (true) {
        $fileSize > 5 * 1024 * 1024 => 70,  // 5MB 이상 → 70%
        $fileSize > 2 * 1024 * 1024 => 80,  // 2MB 이상 → 80%
        default => 90,                       // 그 외 → 90%
    };

    Image::load($path)
        ->quality($quality)
        ->save();
}
```

### 3. 이미지 메타데이터 추출

```php
use Spatie\Image\Image;

$image = Image::load('photo.jpg');

// Intervention Image 인스턴스 접근
$intervention = $image->getImage();

$width = $intervention->width();
$height = $intervention->height();
$mime = $intervention->mime();
```

---

## 관련 Spatie 패키지

| 패키지 | 용도 | 저장소 |
|--------|------|--------|
| **spatie/image-optimizer** | 이미지 파일 최적화 (jpegoptim, optipng 등) | [GitHub](https://github.com/spatie/image-optimizer) |
| **spatie/laravel-medialibrary** | Laravel용 미디어 라이브러리 (파일 + 변환) | [GitHub](https://github.com/spatie/laravel-medialibrary) |
| **spatie/pdf-to-image** | PDF를 이미지로 변환 | [GitHub](https://github.com/spatie/pdf-to-image) |

---

## 참고 링크

### 공식 문서 및 저장소

- [GitHub Repository](https://github.com/spatie/image)
- [Official Documentation](https://spatie.be/docs/image/v3/introduction)
- [Packagist](https://packagist.org/packages/spatie/image)

### 관련 아티클

- [5 levels of handling images in Laravel](https://docs.spatie.be/blog/five-levels-of-handling-images-in-laravel) - Spatie 공식 블로그
- [A package to easily manipulate images in PHP](https://freek.dev/684-a-package-to-easily-manipulate-images-in-php) - Freek Van der Herten
- [Laravel Spatie PDF Package Tutorial](https://laraveldaily.com/post/laravel-spatie-pdf-package-invoice-images-css) - Laravel Daily

### 기반 라이브러리

- [Intervention Image](http://image.intervention.io/) - 내부 사용 라이브러리

---

## 프로젝트 적용 로드맵

### Phase 1: 기본 이미지 처리

- [ ] `spatie/image` 패키지 설치
- [ ] `ImageProcessingService` 생성
- [ ] 이미지 타입 검증 로직 추가
- [ ] 썸네일 생성 API 엔드포인트 추가

### Phase 2: 자동화

- [ ] `FileUploaded` 이벤트에 썸네일 생성 리스너 추가
- [ ] Queue Job으로 비동기 처리
- [ ] 이미지 최적화 자동화

### Phase 3: 고급 기능

- [ ] 반응형 이미지 세트 생성
- [ ] 이미지 크롭/편집 API
- [ ] 메타데이터 자동 추출 (EXIF)

---

## 테스트 예시

```php
// tests/Unit/Services/ImageProcessingServiceTest.php
namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ImageProcessingService;
use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageProcessingServiceTest extends TestCase
{
    protected ImageProcessingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ImageProcessingService::class);
        Storage::fake('local');
    }

    public function test_generate_thumbnail(): void
    {
        // Given: 이미지 파일 업로드
        $uploadedFile = UploadedFile::fake()->image('photo.jpg', 1000, 1000);
        $file = File::factory()->create([
            'mime_type' => 'image/jpeg',
        ]);

        // When: 썸네일 생성
        $thumbnail = $this->service->generateThumbnail($file, 300, 300);

        // Then: 썸네일 파일이 생성됨
        $this->assertInstanceOf(File::class, $thumbnail);
        $this->assertEquals('thumbnail', $thumbnail->metadata['type']);
        $this->assertEquals($file->id, $thumbnail->metadata['parent_id']);
    }

    public function test_optimize_image(): void
    {
        // Given: 큰 이미지 파일
        $file = File::factory()->create([
            'mime_type' => 'image/jpeg',
            'size' => 5 * 1024 * 1024, // 5MB
        ]);

        $originalSize = $file->size;

        // When: 이미지 최적화
        $this->service->optimizeImage($file, 1920, 80);

        // Then: 파일 크기가 감소함
        $this->assertLessThan($originalSize, $file->fresh()->size);
    }
}
```

---

## 결론

**Spatie Image**는 Laravel 프로젝트에서 이미지 처리를 간단하고 직관적으로 구현할 수 있는 강력한 도구입니다.

현재 프로젝트의 **File 도메인** 확장 시, 썸네일 생성, 이미지 최적화, 반응형 이미지 세트 생성 등의 기능을 손쉽게 추가할 수 있습니다.

### 주요 장점

✅ 표현력 있는 Fluent API
✅ Laravel과 자연스러운 통합
✅ 안정적인 Intervention Image 기반
✅ 다양한 이미지 포맷 지원
✅ MIT 라이선스 (상업적 사용 가능)

### 다음 단계

- File 도메인에 이미지 타입 검증 추가
- Event-Driven Architecture로 자동 썸네일 생성
- Queue Job으로 비동기 이미지 처리

---

**문서 버전:** 1.0
**작성일:** 2025-12-16
**마지막 업데이트:** 2025-12-16
