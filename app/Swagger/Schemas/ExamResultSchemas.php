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
 *     @OA\Property(property="score", type="integer", example=76, description="Total skor dengan sistem poin (Benar: +4, Salah: -1, Kosong: 0)"),
 *     @OA\Property(property="score_statistics", type="object",
 *         @OA\Property(property="total_questions", type="integer", example=20, description="Total jumlah soal"),
 *         @OA\Property(property="correct_answers", type="integer", example=17, description="Jumlah jawaban benar"),
 *         @OA\Property(property="incorrect_answers", type="integer", example=2, description="Jumlah jawaban salah"),
 *         @OA\Property(property="unanswered", type="integer", example=1, description="Jumlah soal tidak dijawab"),
 *         @OA\Property(property="total_score", type="integer", example=76, description="Total skor"),
 *         @OA\Property(property="percentage", type="number", format="float", example=85.0, description="Persentase jawaban benar")
 *     ),
 *     @OA\Property(property="answer_details", type="array",
 *         @OA\Items(type="object",
 *             @OA\Property(property="question_id", type="integer", example=1),
 *             @OA\Property(property="question_text", type="string", example="Apa ibu kota Indonesia?"),
 *             @OA\Property(property="selected_answer", type="string", example="Jakarta"),
 *             @OA\Property(property="is_correct", type="boolean", example=true),
 *             @OA\Property(property="is_doubtful", type="boolean", example=false),
 *             @OA\Property(property="score", type="integer", example=4),
 *             @OA\Property(property="status", type="string", example="benar", enum={"benar", "salah", "tidak_dijawab"})
 *         ), description="Detail jawaban per soal"
 *     ),
 *     @OA\Property(property="user", type="object",
 *         @OA\Property(property="id", type="integer", example=123),
 *         @OA\Property(property="name", type="string", example="John Doe"),
 *         @OA\Property(property="email", type="string", example="john@example.com"),
 *         @OA\Property(property="jenjang", type="string", example="SMP")
 *     )
 * )
 */

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=123, description="ID user"),
 *     @OA\Property(property="name", type="string", example="John Doe", description="Nama user"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com", description="Email user"),
 *     @OA\Property(property="jenjang", type="string", example="SMP", description="Jenjang pendidikan user")
 * )
 */

/**
 * @OA\Schema(
 *     schema="ExamStatistics",
 *     type="object",
 *     @OA\Property(property="total_exams", type="integer", example=150, description="Total exam yang telah dikerjakan"),
 *     @OA\Property(property="total_users", type="integer", example=75, description="Total user yang telah mengerjakan exam"),
 *     @OA\Property(property="average_duration_minutes", type="number", format="float", example=42.5, description="Rata-rata durasi pengerjaan"),
 *     @OA\Property(property="total_violations", type="integer", example=25, description="Total pelanggaran keseluruhan"),
 *     @OA\Property(property="auto_submit_count", type="integer", example=30, description="Jumlah auto submit"),
 *     @OA\Property(property="manual_submit_count", type="integer", example=120, description="Jumlah manual submit"),
 *     @OA\Property(property="score_statistics", type="object",
 *         @OA\Property(property="average_score", type="number", format="float", example=65.5, description="Rata-rata skor"),
 *         @OA\Property(property="highest_score", type="integer", example=96, description="Skor tertinggi"),
 *         @OA\Property(property="lowest_score", type="integer", example=12, description="Skor terendah"),
 *         @OA\Property(property="score_distribution", type="object",
 *             @OA\Property(property="excellent", type="integer", example=25, description="Skor 80+ (Excellent)"),
 *             @OA\Property(property="good", type="integer", example=35, description="Skor 60-79 (Good)"),
 *             @OA\Property(property="fair", type="integer", example=20, description="Skor 40-59 (Fair)"),
 *             @OA\Property(property="poor", type="integer", example=10, description="Skor <40 (Poor)")
 *         )
 *     ),
 *     @OA\Property(property="recent_activity", type="array", 
 *         @OA\Items(type="object",
 *             @OA\Property(property="date", type="string", format="date", example="2025-09-17"),
 *             @OA\Property(property="count", type="integer", example=15)
 *         ), description="Aktivitas terkini"
 *     )
 * )
 */

/**
 * @OA\Schema(
 *     schema="Pagination",
 *     type="object",
 *     @OA\Property(property="current_page", type="integer", example=1, description="Halaman saat ini"),
 *     @OA\Property(property="per_page", type="integer", example=15, description="Jumlah item per halaman"),
 *     @OA\Property(property="total", type="integer", example=100, description="Total jumlah data"),
 *     @OA\Property(property="last_page", type="integer", example=7, description="Halaman terakhir"),
 *     @OA\Property(property="from", type="integer", example=1, description="Item pertama di halaman ini"),
 *     @OA\Property(property="to", type="integer", example=15, description="Item terakhir di halaman ini")
 * )
 */

/**
 * @OA\Schema(
 *     schema="ApiErrorResponse",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=false, description="Status response"),
 *     @OA\Property(property="message", type="string", example="Terjadi kesalahan", description="Pesan error"),
 *     @OA\Property(property="error", type="string", example="Error details", description="Detail error (hanya di development)")
 * )
 */

class ExamResultSchemas
{
    // This class is used only for Swagger documentation
}
