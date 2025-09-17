<?php

namespace App\Http\Controllers;

use App\Models\ExamResult;
use App\Models\User;
use App\Http\Requests\ExamSubmitRequest;
use Illuminate\Http\JsonResponse;

class ExamController extends Controller
{
    /**
     * Submit exam result
     *
     * @param ExamSubmitRequest $request
     * @return JsonResponse
     */
    public function submit(ExamSubmitRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Cek apakah user sudah pernah submit exam hari ini
            $existingResult = ExamResult::where('user_id', $validated['userId'])
                ->whereDate('submitted_at', today())
                ->first();

            if ($existingResult) {
                return response()->json([
                    'success' => false,
                    'message' => 'User sudah melakukan submit exam hari ini',
                    'data' => null
                ], 422);
            }

            // Simpan hasil exam
            $examResult = ExamResult::create([
                'user_id' => $validated['userId'],
                'duration_in_minutes' => $validated['durationInMinutes'],
                'total_violations' => $validated['totalViolations'],
                'is_auto_submit' => $validated['isAutoSubmit'],
                'submitted_at' => now()
            ]);

            // Load relasi user untuk response
            $examResult->load('user:id,name,email');

            return response()->json([
                'success' => true,
                'message' => 'Hasil exam berhasil disimpan',
                'data' => [
                    'id' => $examResult->id,
                    'user' => $examResult->user,
                    'duration_in_minutes' => $examResult->duration_in_minutes,
                    'total_violations' => $examResult->total_violations,
                    'is_auto_submit' => $examResult->is_auto_submit,
                    'submitted_at' => $examResult->submitted_at
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server',
                'error' => app()->isLocal() ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
