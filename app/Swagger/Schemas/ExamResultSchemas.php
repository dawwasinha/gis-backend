<?php

namespace App\Swagger\Schemas;

/**
 * @OA\Schema(
 *     schema="ExamResult",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1, description="ID exam result"),
 *     @OA\Property(property="user_id", type="integer", example=123, description="ID user"),
 *     @OA\Property(property="duration_in_minutes", type="integer", example=45, description="Durasi pengerjaan dalam menit"),
 *     @OA\Property(property="total_violations", type="integer", example=2, description="Total pelanggaran"),
 *     @OA\Property(property="is_auto_submit", type="boolean", example=false, description="Status auto submit"),
 *     @OA\Property(property="submitted_at", type="string", format="date-time", example="2025-09-17T10:44:05.000000Z", description="Waktu submit"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-17T10:44:05.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-17T10:44:05.000000Z"),
 *     @OA\Property(property="user", type="object",
 *         @OA\Property(property="id", type="integer", example=123),
 *         @OA\Property(property="name", type="string", example="John Doe"),
 *         @OA\Property(property="email", type="string", example="john@example.com")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=123, description="ID user"),
 *     @OA\Property(property="name", type="string", example="John Doe", description="Nama user"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Email user")
 * )
 *
 * @OA\Schema(
 *     schema="ExamStatistics",
 *     type="object",
 *     @OA\Property(property="total_exams", type="integer", example=150, description="Total exam yang telah dikerjakan"),
 *     @OA\Property(property="total_users", type="integer", example=75, description="Total user yang telah mengerjakan exam"),
 *     @OA\Property(property="average_duration_minutes", type="number", format="float", example=42.5, description="Rata-rata durasi pengerjaan"),
 *     @OA\Property(property="total_violations", type="integer", example=25, description="Total pelanggaran keseluruhan"),
 *     @OA\Property(property="auto_submit_count", type="integer", example=30, description="Jumlah auto submit"),
 *     @OA\Property(property="manual_submit_count", type="integer", example=120, description="Jumlah manual submit"),
 *     @OA\Property(property="recent_activity", type="array", 
 *         @OA\Items(type="object",
 *             @OA\Property(property="date", type="string", format="date", example="2025-09-17"),
 *             @OA\Property(property="count", type="integer", example=15)
 *         ), description="Aktivitas terkini"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Pagination",
 *     type="object",
 *     @OA\Property(property="current_page", type="integer", example=1, description="Halaman saat ini"),
 *     @OA\Property(property="per_page", type="integer", example=15, description="Jumlah item per halaman"),
 *     @OA\Property(property="total", type="integer", example=50, description="Total item"),
 *     @OA\Property(property="last_page", type="integer", example=4, description="Halaman terakhir"),
 *     @OA\Property(property="from", type="integer", example=1, description="Item pertama pada halaman ini"),
 *     @OA\Property(property="to", type="integer", example=15, description="Item terakhir pada halaman ini")
 * )
 *
 * @OA\Schema(
 *     schema="ApiResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=true, description="Status keberhasilan"),
 *     @OA\Property(property="message", type="string", example="Data berhasil diambil", description="Pesan response"),
 *     @OA\Property(property="data", description="Data response")
 * )
 *
 * @OA\Schema(
 *     schema="ApiErrorResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=false, description="Status keberhasilan"),
 *     @OA\Property(property="message", type="string", example="Terjadi kesalahan", description="Pesan error"),
 *     @OA\Property(property="errors", type="object", description="Detail error (jika ada)")
 * )
 */
class ExamResultSchemas {}
