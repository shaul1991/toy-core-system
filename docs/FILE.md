# File 도메인

File 도메인은 MinIO 기반의 파일 저장 및 관리를 담당하는 원자적 도메인 서비스입니다.

## 개요

| 항목 | 설명 |
|------|------|
| **목적** | 파일 업로드, 다운로드, 메타데이터 관리 |
| **스토리지** | MinIO (S3 호환 Object Storage) |
| **주요 기능** | 업로드/다운로드/삭제, 가시성 관리, 임시 URL 생성 |
| **특징** | UUID 기반, Soft Delete, Public/Private 이중 버킷 |

## 아키텍처

### 레이어 구조

```
Controller (FileController)
    ↓
Service (FileService)
    ↓
Repository Interface (FileRepositoryInterface)
    ↓
Repository Implementation (MinioFileRepository)
    ↓
├── Storage Facade (Laravel Filesystem)
│       ↓
│   MinIO (S3 Driver)
│
└── Database (PostgreSQL)
```

### 파일 구조

```
app/
├── Models/File.php                           # Eloquent 모델
├── Http/Controllers/FileController.php       # API 컨트롤러
├── Services/FileService.php                  # 비즈니스 로직
└── Repositories/
    ├── FileRepositoryInterface.php           # Repository 인터페이스
    └── MinioFileRepository.php               # MinIO 구현체

config/
└── filesystems.php                           # MinIO 디스크 설정

database/migrations/
└── 2025_12_10_000001_create_files_table.php  # 테이블 생성

tests/
├── Feature/File/FileControllerTest.php       # Feature 테스트 (32개)
└── Unit/
    ├── Models/FileTest.php                   # Model 테스트 (10개)
    └── Services/FileServiceTest.php          # Service 테스트 (17개)
```

## 데이터베이스 스키마

### files 테이블

| 컬럼 | 타입 | 설명 |
|------|------|------|
| `id` | UUID | Primary Key |
| `original_name` | VARCHAR | 원본 파일명 |
| `stored_name` | VARCHAR | 저장된 파일명 (UUID.확장자) |
| `path` | VARCHAR | 저장 경로 (Y/m/d 형식) |
| `disk` | VARCHAR | 스토리지 디스크 (minio-public/minio-private) |
| `mime_type` | VARCHAR | MIME 타입 |
| `size` | BIGINT | 파일 크기 (bytes) |
| `visibility` | ENUM | 가시성 (public/private) |
| `metadata` | JSON | 추가 메타데이터 (nullable) |
| `created_at` | TIMESTAMP | 생성 시간 |
| `updated_at` | TIMESTAMP | 수정 시간 |
| `deleted_at` | TIMESTAMP | 삭제 시간 (Soft Delete) |

### 인덱스

```sql
CREATE INDEX files_disk_index ON files (disk);
CREATE INDEX files_visibility_index ON files (visibility);
CREATE INDEX files_mime_type_index ON files (mime_type);
CREATE INDEX files_path_index ON files (path);
CREATE INDEX files_created_at_index ON files (created_at);
```

## MinIO 구성

### 이중 버킷 전략

| 버킷 | 디스크명 | 가시성 | 용도 |
|------|---------|--------|------|
| `public` | `minio-public` | Public | 공개 파일 (직접 URL 접근) |
| `private` | `minio-private` | Private | 비공개 파일 (임시 URL 필요) |

### 환경 변수

```env
MINIO_ACCESS_KEY_ID=your-access-key
MINIO_SECRET_ACCESS_KEY=your-secret-key
MINIO_REGION=us-east-1
MINIO_ENDPOINT=http://localhost:9000
MINIO_URL=http://localhost:9000
MINIO_PUBLIC_BUCKET=public
MINIO_PRIVATE_BUCKET=private
```

## API 엔드포인트

### 파일 업로드

```
POST /internal/files
Content-Type: multipart/form-data
```

**요청**
| 필드 | 타입 | 필수 | 설명 |
|------|------|------|------|
| `file` | File | O | 업로드 파일 (최대 100MB) |
| `visibility` | String | X | public/private (기본: private) |
| `path` | String | X | 저장 경로 (기본: Y/m/d) |
| `metadata` | JSON | X | 추가 메타데이터 |

**응답 (201 Created)**
```json
{
    "success": true,
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "original_name": "photo.jpg",
        "mime_type": "image/jpeg",
        "size": 102400,
        "human_readable_size": "100 KB",
        "visibility": "private",
        "url": null,
        "metadata": null,
        "created_at": "2025-12-10T12:00:00Z",
        "updated_at": "2025-12-10T12:00:00Z"
    }
}
```

### 파일 메타데이터 조회

```
GET /internal/files/{id}
```

**응답 (200 OK)**
```json
{
    "success": true,
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "original_name": "photo.jpg",
        "mime_type": "image/jpeg",
        "size": 102400,
        "human_readable_size": "100 KB",
        "visibility": "public",
        "url": "http://localhost:9000/public/2025/12/10/abc123.jpg",
        "metadata": {"category": "profile"},
        "created_at": "2025-12-10T12:00:00Z",
        "updated_at": "2025-12-10T12:00:00Z"
    }
}
```

### 파일 다운로드

```
GET /internal/files/{id}/download
```

**응답 (200 OK)**
```
Content-Type: image/jpeg
Content-Length: 102400
Content-Disposition: attachment; filename=photo.jpg

[바이너리 데이터]
```

### 가시성 변경

```
PATCH /internal/files/{id}/visibility
```

**요청**
```json
{
    "visibility": "public"
}
```

**응답 (200 OK)**
```json
{
    "success": true,
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "visibility": "public",
        "url": "http://localhost:9000/public/2025/12/10/abc123.jpg",
        ...
    }
}
```

### 임시 URL 생성 (Private 파일용)

```
POST /internal/files/{id}/temporary-url
```

**요청**
```json
{
    "expiration_minutes": 120
}
```

| 필드 | 타입 | 필수 | 설명 |
|------|------|------|------|
| `expiration_minutes` | Integer | X | 만료 시간 (분), 1~10080, 기본: 60 |

**응답 (200 OK)**
```json
{
    "success": true,
    "data": {
        "url": "https://minio.example.com/private/...?X-Amz-Signature=...",
        "expires_at": "2025-12-10T14:00:00Z"
    }
}
```

### 파일 삭제 (Soft Delete)

```
DELETE /internal/files/{id}
```

**응답 (200 OK)**
```json
{
    "success": true,
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "deleted_at": "2025-12-10T12:00:00Z"
    }
}
```

### 파일 영구 삭제 (Hard Delete)

```
DELETE /internal/files/{id}/force
```

**응답 (200 OK)**
```json
{
    "success": true,
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "message": "파일이 완전히 삭제되었습니다."
    }
}
```

## Sequence Diagram

### 파일 업로드 (POST)

```mermaid
sequenceDiagram
    autonumber
    participant Client
    participant Controller as FileController
    participant Service as FileService
    participant Repository as MinioFileRepository
    participant Storage as Laravel Storage
    participant MinIO
    participant DB as PostgreSQL

    Client->>Controller: POST /internal/files
    Note right of Client: multipart/form-data<br/>file, visibility, path, metadata

    Controller->>Controller: validate(file, visibility, path, metadata)
    Controller->>Service: uploadFile(file, visibility, path, metadata)

    Service->>Service: validateVisibility(visibility)
    alt 유효하지 않은 visibility
        Service-->>Controller: throw BadRequestException
        Controller-->>Client: 400 BAD_REQUEST
    end

    Service->>Repository: upload(file, visibility, path, metadata)

    Repository->>Repository: getDiskForVisibility(visibility)
    Note right of Repository: public → minio-public<br/>private → minio-private

    Repository->>Repository: generatePath()
    Note right of Repository: 기본값: Y/m/d (2025/12/10)

    Repository->>Repository: generateStoredName(file)
    Note right of Repository: UUID + 확장자

    Repository->>Storage: disk(disk)->putFileAs(path, file, storedName)
    Storage->>MinIO: PUT object
    MinIO-->>Storage: success
    Storage-->>Repository: true

    Repository->>DB: INSERT INTO files
    Note right of Repository: original_name, stored_name,<br/>path, disk, mime_type,<br/>size, visibility, metadata
    DB-->>Repository: File

    Repository-->>Service: File
    Service-->>Controller: File
    Controller-->>Client: 201 Created
    Note left of Client: id, original_name, mime_type,<br/>size, visibility, url, metadata
```

### 파일 다운로드 (GET /download)

```mermaid
sequenceDiagram
    autonumber
    participant Client
    participant Controller as FileController
    participant Service as FileService
    participant Repository as MinioFileRepository
    participant Storage as Laravel Storage
    participant MinIO
    participant DB as PostgreSQL

    Client->>Controller: GET /internal/files/{id}/download
    Controller->>Service: getFile(id)

    Service->>DB: SELECT * FROM files WHERE id = ?
    DB-->>Service: File | null

    alt File 없음
        Service-->>Controller: throw NotFoundException
        Controller-->>Client: 404 NOT_FOUND
    end

    Service-->>Controller: File
    Controller->>Service: downloadFile(file)
    Service->>Repository: download(file)

    Repository->>Repository: file.full_path
    Note right of Repository: path/stored_name

    Repository->>Storage: disk(file.disk)->readStream(fullPath)
    Storage->>MinIO: GET object
    MinIO-->>Storage: Stream
    Storage-->>Repository: Stream | null

    alt Stream 없음 (스토리지에 파일 없음)
        Repository-->>Service: throw NotFoundException
        Service-->>Controller: throw NotFoundException
        Controller-->>Client: 404 NOT_FOUND
    end

    Repository-->>Service: Stream
    Service-->>Controller: Stream

    Controller->>Controller: StreamedResponse 생성
    Note right of Controller: Content-Type: mime_type<br/>Content-Length: size<br/>Content-Disposition: attachment

    Controller-->>Client: 200 OK (Streamed Response)
    Note left of Client: 바이너리 파일 스트림
```

### 가시성 변경 (PATCH /visibility)

```mermaid
sequenceDiagram
    autonumber
    participant Client
    participant Controller as FileController
    participant Service as FileService
    participant Repository as MinioFileRepository
    participant Storage as Laravel Storage
    participant MinIO
    participant DB as PostgreSQL

    Client->>Controller: PATCH /internal/files/{id}/visibility
    Note right of Client: { "visibility": "public" }

    Controller->>Controller: validate(visibility: required|in:public,private)
    Controller->>Service: getFile(id)

    Service->>DB: SELECT * FROM files WHERE id = ?
    DB-->>Service: File | null

    alt File 없음
        Service-->>Controller: throw NotFoundException
        Controller-->>Client: 404 NOT_FOUND
    end

    Service-->>Controller: File
    Controller->>Service: updateVisibility(file, newVisibility)
    Service->>Repository: updateVisibility(file, newVisibility)

    Repository->>Repository: getDiskForVisibility(newVisibility)
    Note right of Repository: 새 디스크 결정

    alt 디스크 변경 필요 (private ↔ public)
        Repository->>Repository: moveFileBetweenDisks(file, newDisk)

        Repository->>Storage: disk(oldDisk)->readStream(fullPath)
        Storage->>MinIO: GET object from old bucket
        MinIO-->>Storage: Stream
        Storage-->>Repository: Stream

        Repository->>Storage: disk(newDisk)->writeStream(fullPath, stream)
        Storage->>MinIO: PUT object to new bucket
        MinIO-->>Storage: success
        Storage-->>Repository: true

        Repository->>Storage: disk(oldDisk)->delete(fullPath)
        Storage->>MinIO: DELETE object from old bucket
        MinIO-->>Storage: success
        Storage-->>Repository: true

        Note right of Repository: 실패 시 롤백 로직 실행
    end

    Repository->>DB: UPDATE files SET disk = ?, visibility = ?
    DB-->>Repository: File

    Repository-->>Service: File
    Service-->>Controller: File
    Controller-->>Client: 200 OK
    Note left of Client: 업데이트된 파일 메타데이터
```

### 임시 URL 생성 (POST /temporary-url)

```mermaid
sequenceDiagram
    autonumber
    participant Client
    participant Controller as FileController
    participant Service as FileService
    participant Repository as MinioFileRepository
    participant Storage as Laravel Storage
    participant MinIO

    Client->>Controller: POST /internal/files/{id}/temporary-url
    Note right of Client: { "expiration_minutes": 120 }

    Controller->>Controller: validate(expiration_minutes: min:1|max:10080)
    Controller->>Service: getFile(id)

    Service->>Service: findById(id)
    alt File 없음
        Service-->>Controller: throw NotFoundException
        Controller-->>Client: 404 NOT_FOUND
    end

    Service-->>Controller: File

    Controller->>Service: getTemporaryUrl(file, expirationMinutes)
    Service->>Repository: getTemporaryUrl(file, expirationMinutes)

    Repository->>Repository: calculateExpiration(minutes)
    Note right of Repository: now() + minutes

    Repository->>Storage: disk(file.disk)->temporaryUrl(fullPath, expiration)
    Storage->>MinIO: Generate presigned URL
    MinIO-->>Storage: Signed URL
    Storage-->>Repository: URL

    Repository-->>Service: {url, expires_at}
    Service-->>Controller: {url, expires_at}
    Controller-->>Client: 200 OK
    Note left of Client: { "url": "...", "expires_at": "..." }
```

### 파일 영구 삭제 (DELETE /force)

```mermaid
sequenceDiagram
    autonumber
    participant Client
    participant Controller as FileController
    participant Service as FileService
    participant Repository as MinioFileRepository
    participant Storage as Laravel Storage
    participant MinIO
    participant DB as PostgreSQL

    Client->>Controller: DELETE /internal/files/{id}/force
    Controller->>Service: getFile(id)

    Service->>DB: SELECT * FROM files WHERE id = ?
    DB-->>Service: File | null

    alt File 없음
        Service-->>Controller: throw NotFoundException
        Controller-->>Client: 404 NOT_FOUND
    end

    Service-->>Controller: File
    Controller->>Service: forceDeleteFile(file)
    Service->>Repository: forceDelete(file)

    Repository->>Storage: disk(file.disk)->delete(fullPath)
    Storage->>MinIO: DELETE object
    MinIO-->>Storage: success
    Storage-->>Repository: true

    Repository->>DB: DELETE FROM files WHERE id = ?
    Note right of Repository: 영구 삭제 (Soft Delete 아님)
    DB-->>Repository: true

    Repository-->>Service: true
    Service-->>Controller: true
    Controller-->>Client: 200 OK
    Note left of Client: { "message": "파일이 완전히 삭제되었습니다." }
```

## Model 속성

### Accessor (계산된 속성)

| 속성 | 설명 | 예시 |
|------|------|------|
| `full_path` | 전체 경로 | `2025/12/10/abc123.jpg` |
| `url` | 공개 URL (private는 null) | `http://minio/public/...` |
| `human_readable_size` | 사람이 읽기 쉬운 크기 | `100 KB`, `1.5 MB` |

### 메서드

| 메서드 | 설명 |
|--------|------|
| `isPublic()` | visibility가 'public'인지 확인 |
| `isPrivate()` | visibility가 'private'인지 확인 |

## 파일 명명 규칙

### 저장 파일명

```
{UUID}.{extension}

예: 550e8400-e29b-41d4-a716-446655440000.jpg
```

### 저장 경로

```
{Y}/{m}/{d}/{stored_name}

예: 2025/12/10/550e8400-e29b-41d4-a716-446655440000.jpg
```

## 테스트 커버리지

| 영역 | 테스트 수 | 파일 |
|------|----------|------|
| Feature (API) | 32개 | `tests/Feature/File/FileControllerTest.php` |
| Service Unit | 17개 | `tests/Unit/Services/FileServiceTest.php` |
| Model Unit | 10개 | `tests/Unit/Models/FileTest.php` |
| **총합** | **59개** | |

## 예외 처리

| 예외 | HTTP | 상황 |
|------|------|------|
| `NotFoundException` | 404 | 파일을 찾을 수 없음 |
| `BadRequestException` | 400 | 잘못된 visibility 값 |
| `ValidationException` | 400 | 요청 유효성 검증 실패 |
