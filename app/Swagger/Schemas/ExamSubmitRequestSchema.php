<?php

namespace App\Swagger\Schemas;

/**
 * @OA\Schema(
 *     schema="ExamSubmitRequest",
 *     type="object",
 *     required={"userId", "durationInMinutes", "totalViolations", "isAutoSubmit"},
 *     @OA\Property(property="userId", type="string", example="123", description="ID user yang mengerjakan exam"),
 *     @OA\Property(property="durationInMinutes", type="integer", minimum=0, example=45, description="Durasi pengerjaan dalam menit"),
 *     @OA\Property(property="totalViolations", type="integer", minimum=0, example=2, description="Total pelanggaran yang terdeteksi"),
 *     @OA\Property(property="isAutoSubmit", type="boolean", example=false, description="Apakah submit otomatis karena waktu habis")
 * )
 */
class ExamSubmitRequestSchema {}
