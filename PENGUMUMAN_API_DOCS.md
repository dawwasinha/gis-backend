# Dokumentasi API Pengumuman User Lolos

## Overview
Fitur ini memungkinkan admin untuk mengumumkan status lolos/tidak lolos untuk user yang mengikuti lomba. Setelah login, user akan mendapatkan informasi pengumuman mereka (jika ada).

## Database Schema

### Tabel `user_announcements`
```sql
- id (primary key)
- user_id (foreign key to users table)
- status_lolos (enum: 'lolos', 'tidak_lolos')
- kategori_lomba (string, nullable)
- skor_akhir (integer, nullable)
- ranking (integer, nullable)
- keterangan (text, nullable)
- tanggal_pengumuman (timestamp)
- diumumkan_oleh (string, nullable)
- created_at, updated_at (timestamps)
```

## API Endpoints

### 1. Login User (Updated)
**POST** `/api/login`

Request Body:
```json
{
  "email": "user@example.com",
  "password": "password"
}
```

Response sekarang includes informasi pengumuman:
```json
{
  "success": true,
  "message": "Login berhasil",
  "token": "jwt_token_here",
  "token_type": "Bearer",
  "expires_in": 3600,
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "participant",
    "nisn": "1234567890",
    "nomor_wa": "081234567890",
    "jenis_lomba": "Science Competition",
    "jenjang": "SMA",
    "kelas": "12",
    "asal_sekolah": "SMA Negeri 1",
    "status": "success"
  },
  "pengumuman": {
    "id": 1,
    "status_lolos": "lolos",
    "is_lolos": true,
    "is_tidak_lolos": false,
    "kategori_lomba": "Science Competition",
    "skor_akhir": 85,
    "ranking": 1,
    "keterangan": "Selamat! Anda berhasil lolos ke tahap selanjutnya.",
    "tanggal_pengumuman": "2025-09-23 07:38:06",
    "diumumkan_oleh": "Admin System",
    "created_at": "2025-09-23 07:38:06",
    "updated_at": "2025-09-23 07:38:06"
  },
  "has_announcement": true
}
```

**Jika user tidak memiliki pengumuman:**
```json
{
  "success": true,
  "message": "Login berhasil",
  "token": "jwt_token_here",
  "token_type": "Bearer",
  "expires_in": 3600,
  "user": {
    // ... user data
  },
  "pengumuman": null,
  "has_announcement": false
}
```

### 2. Lihat Pengumuman Saya
**GET** `/api/my-announcement`
**Auth Required:** Yes

Response:
```json
{
  "announcement": {
    "id": 1,
    "user_id": 1,
    "status_lolos": "lolos",
    "kategori_lomba": "Science Competition",
    "skor_akhir": 85,
    "ranking": 1,
    "keterangan": "Selamat! Anda berhasil lolos ke tahap selanjutnya.",
    "tanggal_pengumuman": "2025-09-23T07:38:06.000000Z",
    "diumumkan_oleh": "Admin System",
    "created_at": "2025-09-23T07:38:06.000000Z",
    "updated_at": "2025-09-23T07:38:06.000000Z"
  },
  "has_announcement": true
}
```

### 3. Admin: Umumkan Status User
**POST** `/api/users/{userId}/announce`
**Auth Required:** Yes (Admin only)

Request Body:
```json
{
  "status_lolos": "lolos", // required: "lolos" or "tidak_lolos"
  "kategori_lomba": "Science Competition", // optional
  "skor_akhir": 85, // optional
  "ranking": 1, // optional
  "keterangan": "Selamat! Anda berhasil lolos ke tahap selanjutnya.", // optional
  "tanggal_pengumuman": "2025-09-23" // optional, default: now()
}
```

### 4. Admin: Lihat Pengumuman User Tertentu
**GET** `/api/users/{userId}/announcement`
**Auth Required:** Yes (Admin only)

### 5. Admin: Lihat Semua Pengumuman
**GET** `/api/announcements`
**Auth Required:** Yes (Admin only)

Query Parameters:
- `status_lolos`: filter by status ("lolos" or "tidak_lolos")
- `kategori_lomba`: filter by kategori lomba
- `order_by`: "ranking" untuk sort by ranking, default sort by tanggal_pengumuman
- `per_page`: jumlah data per halaman (default: 15)

### 6. Admin: Hapus Pengumuman
**DELETE** `/api/users/{userId}/announcement`
**Auth Required:** Yes (Admin only)

## Model Relationships

### User Model
```php
// User memiliki satu pengumuman
public function userAnnouncement()
{
    return $this->hasOne(UserAnnouncement::class);
}

// Check if user has announcement
public function hasAnnouncement(): bool
{
    return $this->userAnnouncement !== null;
}

// Get user's status lolos
public function getStatusLolos(): ?string
{
    return $this->userAnnouncement?->status_lolos;
}
```

### UserAnnouncement Model
```php
// UserAnnouncement belongs to User
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}

// Helper methods
public function isLolos(): bool
public function isTidakLolos(): bool

// Scopes
public function scopeLolos($query)
public function scopeTidakLolos($query)
public function scopeByKategori($query, $kategori)
public function scopeOrderByRanking($query)
```

## Usage Example

### Frontend Implementation
```javascript
// Setelah login berhasil
const loginResponse = await fetch('/api/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ email: 'user@example.com', password: 'password' })
});

const data = await loginResponse.json();

if (data.success) {
  // Simpan token untuk requests selanjutnya
  localStorage.setItem('token', data.token);
  localStorage.setItem('user', JSON.stringify(data.user));
  
  // Cek pengumuman
  if (data.has_announcement && data.pengumuman) {
    const pengumuman = data.pengumuman;
    
    if (pengumuman.is_lolos) {
      // Tampilkan pesan selamat
      showSuccessMessage(`🎉 Selamat ${data.user.name}! Anda LOLOS!`, {
        kategori: pengumuman.kategori_lomba,
        ranking: pengumuman.ranking,
        skor: pengumuman.skor_akhir,
        keterangan: pengumuman.keterangan
      });
    } else if (pengumuman.is_tidak_lolos) {
      // Tampilkan pesan tidak lolos
      showInfoMessage('Pengumuman Hasil', {
        message: 'Mohon maaf, Anda belum berhasil lolos pada tahap ini.',
        keterangan: pengumuman.keterangan,
        encourage: 'Tetap semangat untuk kesempatan berikutnya!'
      });
    }
  } else {
    // Belum ada pengumuman
    console.log('Pengumuman belum tersedia');
    showInfoMessage('Pengumuman belum keluar. Harap bersabar menunggu pengumuman resmi.');
  }
  
  // Redirect ke dashboard
  window.location.href = '/dashboard';
} else {
  // Handle login error
  showErrorMessage(data.error || 'Login gagal');
}

// Helper functions untuk UI
function showSuccessMessage(title, details) {
  const message = `
    ${title}
    
    📚 Kategori: ${details.kategori}
    🏆 Ranking: ${details.ranking || 'N/A'}
    📊 Skor: ${details.skor || 'N/A'}
    
    ${details.keterangan}
  `;
  
  // Gunakan library notifikasi favorit Anda
  // Contoh dengan SweetAlert2:
  Swal.fire({
    title: '🎉 Selamat!',
    text: message,
    icon: 'success',
    confirmButtonText: 'Terima kasih!'
  });
}

function showInfoMessage(title, details) {
  // Implementation untuk menampilkan info message
}

function showErrorMessage(message) {
  // Implementation untuk menampilkan error message
}
```

### Admin Panel - Mengumumkan Status
```javascript
// Admin mengumumkan status user
const announceStatus = async (userId, statusData) => {
  const response = await fetch(`/api/users/${userId}/announce`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${adminToken}`
    },
    body: JSON.stringify({
      status_lolos: 'lolos',
      kategori_lomba: 'Science Competition',
      skor_akhir: 85,
      ranking: 1,
      keterangan: 'Selamat! Anda berhasil lolos ke tahap selanjutnya.'
    })
  });
  
  return await response.json();
};
```

## Migration Command
```bash
# Migrate the new table
php artisan migrate

# Seed sample data (optional)
php artisan db:seed --class=UserAnnouncementSeeder
```

## Notes
1. Setiap user hanya bisa memiliki satu pengumuman (unique constraint pada user_id)
2. Jika admin mengumumkan status user yang sudah ada pengumumannya, data akan di-update (updateOrCreate)
3. Response login sekarang selalu include informasi pengumuman jika ada
4. User biasa hanya bisa melihat pengumuman mereka sendiri
5. Admin bisa mengelola semua pengumuman
