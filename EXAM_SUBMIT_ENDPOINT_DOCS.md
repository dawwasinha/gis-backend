# Endpoint Exam Submit & Retrieve - Dokumentasi

## Overview
Endpoint lengkap untuk mengelola hasil pengerjaan CBT (Computer Based Test), termasuk submit dan mengambil data exam results.

## Endpoint Details

### 1. Submit Exam Result

#### URL
```
POST /api/exam/submit
```

#### Middleware
- `jwt` - Requires JWT authentication

#### Request Headers
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer <JWT_TOKEN>
```

#### Request Body
```json
{
  "userId": "123",
  "durationInMinutes": 45,
  "totalViolations": 2,
  "isAutoSubmit": false
}
```

#### Success Response (201 Created)
```json
{
  "success": true,
  "message": "Hasil exam berhasil disimpan",
  "data": {
    "id": 1,
    "user": {
      "id": 123,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "duration_in_minutes": 45,
    "total_violations": 2,
    "is_auto_submit": false,
    "submitted_at": "2025-09-17T10:44:05.000000Z"
  }
}
```

### 2. Get All Exam Results

#### URL
```
GET /api/exam/exam-results
```

#### Query Parameters
- `per_page` (optional): Number of items per page (default: 15)
- `sort_by` (optional): Field to sort by (default: submitted_at)
- `sort_order` (optional): asc or desc (default: desc)

#### Example Request
```
GET /api/exam/exam-results?per_page=10&sort_by=duration_in_minutes&sort_order=asc
```

#### Success Response (200 OK)
```json
{
  "success": true,
  "message": "Data exam results berhasil diambil",
  "data": [
    {
      "id": 1,
      "user_id": 123,
      "duration_in_minutes": 45,
      "total_violations": 2,
      "is_auto_submit": false,
      "submitted_at": "2025-09-17T10:44:05.000000Z",
      "created_at": "2025-09-17T10:44:05.000000Z",
      "updated_at": "2025-09-17T10:44:05.000000Z",
      "user": {
        "id": 123,
        "name": "John Doe",
        "email": "john@example.com"
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 50,
    "last_page": 4,
    "from": 1,
    "to": 15
  }
}
```

### 3. Get Exam Result by ID

#### URL
```
GET /api/exam/exam-results/{id}
```

#### Example Request
```
GET /api/exam/exam-results/1
```

#### Success Response (200 OK)
```json
{
  "success": true,
  "message": "Data exam result berhasil diambil",
  "data": {
    "id": 1,
    "user_id": 123,
    "duration_in_minutes": 45,
    "total_violations": 2,
    "is_auto_submit": false,
    "submitted_at": "2025-09-17T10:44:05.000000Z",
    "created_at": "2025-09-17T10:44:05.000000Z",
    "updated_at": "2025-09-17T10:44:05.000000Z",
    "user": {
      "id": 123,
      "name": "John Doe",
      "email": "john@example.com"
    }
  }
}
```

#### Not Found Response (404)
```json
{
  "success": false,
  "message": "Exam result tidak ditemukan",
  "data": null
}
```

### 4. Get Exam Results by User ID

#### URL
```
GET /api/exam/exam-results/user/{userId}
```

#### Query Parameters
- `per_page` (optional): Number of items per page (default: 15)
- `sort_order` (optional): asc or desc (default: desc)

#### Example Request
```
GET /api/exam/exam-results/user/123?per_page=10&sort_order=asc
```

#### Success Response (200 OK)
```json
{
  "success": true,
  "message": "Data exam results untuk user berhasil diambil",
  "data": {
    "user": {
      "id": 123,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "exam_results": [
      {
        "id": 1,
        "user_id": 123,
        "duration_in_minutes": 45,
        "total_violations": 2,
        "is_auto_submit": false,
        "submitted_at": "2025-09-17T10:44:05.000000Z",
        "user": {
          "id": 123,
          "name": "John Doe",
          "email": "john@example.com"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 5,
      "last_page": 1,
      "from": 1,
      "to": 5
    }
  }
}
```

### 5. Get Exam Statistics

#### URL
```
GET /api/exam/exam-results/statistics/overview
```

#### Success Response (200 OK)
```json
{
  "success": true,
  "message": "Statistik exam berhasil diambil",
  "data": {
    "total_exams": 150,
    "total_users": 75,
    "average_duration_minutes": 42.5,
    "total_violations": 25,
    "auto_submit_count": 30,
    "manual_submit_count": 120,
    "recent_activity": [
      {
        "date": "2025-09-17",
        "count": 15
      },
      {
        "date": "2025-09-16",
        "count": 22
      }
    ]
  }
}
```

## Database Schema

### Table: `exam_results`
```sql
CREATE TABLE exam_results (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    duration_in_minutes INT NOT NULL,
    total_violations INT DEFAULT 0 NOT NULL,
    is_auto_submit BOOLEAN DEFAULT FALSE NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_submitted (user_id, submitted_at)
);
```

## Model Relationships

### ExamResult Model
- **Belongs To**: User (`user_id`)
- **Fillable**: `user_id`, `duration_in_minutes`, `total_violations`, `is_auto_submit`, `submitted_at`
- **Casts**: `is_auto_submit` => boolean, `submitted_at` => datetime

### User Model
- **Has Many**: ExamResult (`examResults()`)

## Business Logic

### Duplicate Prevention
- System prevents users from submitting multiple exam results on the same day
- Checks for existing submission using `whereDate('submitted_at', today())`

### Auto Submit Detection
- `isAutoSubmit` field tracks whether submission was automatic (due to time limit) or manual

### Violations Tracking
- `totalViolations` field records any exam violations detected during the test

## File Structure

### Created Files:
1. `app/Models/ExamResult.php` - Main model
2. `app/Http/Controllers/ExamController.php` - Controller with submit method
3. `app/Http/Requests/ExamSubmitRequest.php` - Form request validation
4. `database/migrations/2025_09_17_104405_create_exam_results_table.php` - Migration

### Modified Files:
1. `app/Models/User.php` - Added examResults() relationship
2. `routes/api.php` - Added POST /api/exam/submit route

## Usage Example

### cURL Example:
```bash
curl -X POST http://your-domain.com/api/exam/submit \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "userId": "123",
    "durationInMinutes": 45,
    "totalViolations": 2,
    "isAutoSubmit": false
  }'
```

### JavaScript/Fetch Example:
```javascript
const response = await fetch('/api/exam/submit', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'Authorization': `Bearer ${jwtToken}`
  },
  body: JSON.stringify({
    userId: "123",
    durationInMinutes: 45,
    totalViolations: 2,
    isAutoSubmit: false
  })
});

const result = await response.json();
```

## Testing

### Manual Testing via Tinker:
```php
// Create exam result
$examResult = App\Models\ExamResult::create([
    'user_id' => 8,
    'duration_in_minutes' => 45,
    'total_violations' => 2,
    'is_auto_submit' => false,
    'submitted_at' => now()
]);

// Test relationships
$user = App\Models\User::find(8);
$userResults = $user->examResults;
```

### Route Verification:
```bash
php artisan route:list --path=api/exam
```

## Notes

- All exam submissions are stored permanently for audit purposes
- All endpoints require JWT authentication
- Duplicate submissions on the same day are prevented
- Foreign key constraints ensure data integrity
- Indexes are optimized for user-based queries by date
- All GET endpoints include user information via relationship loading

## Available Endpoints Summary

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/exam/submit` | Submit exam result |
| GET | `/api/exam/exam-results` | Get all exam results with pagination |
| GET | `/api/exam/exam-results/{id}` | Get specific exam result by ID |
| GET | `/api/exam/exam-results/user/{userId}` | Get exam results by user ID |
| GET | `/api/exam/exam-results/statistics/overview` | Get exam statistics |

## Usage Examples

### Get All Exam Results
```bash
curl -X GET "http://your-domain.com/api/exam/exam-results?per_page=10&sort_by=submitted_at&sort_order=desc" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Get Exam Result by ID
```bash
curl -X GET "http://your-domain.com/api/exam/exam-results/1" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Get User's Exam Results
```bash
curl -X GET "http://your-domain.com/api/exam/exam-results/user/123" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Get Exam Statistics
```bash
curl -X GET "http://your-domain.com/api/exam/exam-results/statistics/overview" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

---

## Migration Commands Used:
```bash
php artisan make:migration create_exam_results_table
php artisan make:model ExamResult
php artisan make:controller ExamController
php artisan make:request ExamSubmitRequest
php artisan migrate
```
