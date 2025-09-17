# Endpoint Exam Submit - Dokumentasi

## Overview
Endpoint untuk menerima hasil submit pengerjaan CBT (Computer Based Test) yang telah berhasil dibuat dan diimplementasikan.

## Endpoint Details

### URL
```
POST /api/exam/submit
```

### Middleware
- `jwt` - Requires JWT authentication
- Located inside `Route::middleware(['jwt'])` group

### Request Headers
```
Content-Type: application/json
Accept: application/json
Authorization: Bearer <JWT_TOKEN>
```

### Request Body
```json
{
  "userId": "123",
  "durationInMinutes": 45,
  "totalViolations": 2,
  "isAutoSubmit": false
}
```

### Request Validation Rules
- `userId`: required, must exist in users table
- `durationInMinutes`: required, integer, minimum 0
- `totalViolations`: required, integer, minimum 0
- `isAutoSubmit`: required, boolean

### Responses

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

#### Validation Error (422 Unprocessable Entity)
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "userId": ["User ID harus diisi"],
    "durationInMinutes": ["Durasi pengerjaan harus diisi"]
  }
}
```

#### Duplicate Submission Error (422 Unprocessable Entity)
```json
{
  "success": false,
  "message": "User sudah melakukan submit exam hari ini",
  "data": null
}
```

#### Server Error (500 Internal Server Error)
```json
{
  "success": false,
  "message": "Terjadi kesalahan server",
  "error": "Error details (only in local environment)"
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
- The endpoint requires JWT authentication
- Duplicate submissions on the same day are prevented
- Foreign key constraints ensure data integrity
- Indexes are optimized for user-based queries by date

---

## Migration Commands Used:
```bash
php artisan make:migration create_exam_results_table
php artisan make:model ExamResult
php artisan make:controller ExamController
php artisan make:request ExamSubmitRequest
php artisan migrate
```
