<?php

namespace App\Http\Controllers;

use App\Models\ExamResult;
use App\Models\User;
use App\Http\Requests\ExamSubmitRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExamController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/exam/exam-results",
     *     summary="Get all exam results",
     *     description="Mengambil semua hasil exam dengan pagination",
     *     operationId="getExamResults",
     *     tags={"Exam Results"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Jumlah item per halaman",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Field untuk sorting",
     *         required=false,
     *         @OA\Schema(type="string", default="submitted_at", enum={"submitted_at", "duration_in_minutes", "total_violations"})
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Urutan sorting",
     *         required=false,
     *         @OA\Schema(type="string", default="desc", enum={"asc", "desc"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Data exam results berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Data exam results berhasil diambil"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ExamResult")),
     *             @OA\Property(property="pagination", ref="#/components/schemas/Pagination")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $sortBy = $request->get('sort_by', 'submitted_at');
            $sortOrder = $request->get('sort_order', 'desc');

            $examResults = ExamResult::with('user:id,name,email')
                ->orderBy($sortBy, $sortOrder)
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Data exam results berhasil diambil',
                'data' => $examResults->items(),
                'pagination' => [
                    'current_page' => $examResults->currentPage(),
                    'per_page' => $examResults->perPage(),
                    'total' => $examResults->total(),
                    'last_page' => $examResults->lastPage(),
                    'from' => $examResults->firstItem(),
                    'to' => $examResults->lastItem()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server',
                'error' => app()->isLocal() ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/exam/exam-results/{id}",
     *     summary="Get exam result by ID",
     *     description="Mengambil detail exam result berdasarkan ID",
     *     operationId="getExamResult",
     *     tags={"Exam Results"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID exam result",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Data exam result berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Data exam result berhasil diambil"),
     *             @OA\Property(property="data", ref="#/components/schemas/ExamResult")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Exam result tidak ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Exam result tidak ditemukan"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $examResult = ExamResult::with('user:id,name,email')->find($id);

            if (!$examResult) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exam result tidak ditemukan',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Data exam result berhasil diambil',
                'data' => $examResult
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server',
                'error' => app()->isLocal() ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/exam/exam-results/user/{userId}",
     *     summary="Get exam results by user ID",
     *     description="Mengambil semua exam results untuk user tertentu",
     *     operationId="getExamResultsByUser",
     *     tags={"Exam Results"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="ID user",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Jumlah item per halaman",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Urutan sorting",
     *         required=false,
     *         @OA\Schema(type="string", default="desc", enum={"asc", "desc"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Data exam results untuk user berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Data exam results untuk user berhasil diambil"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer", example=123),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="email", type="string", example="john@example.com")
     *                 ),
     *                 @OA\Property(property="exam_results", type="array", @OA\Items(ref="#/components/schemas/ExamResult")),
     *                 @OA\Property(property="pagination", ref="#/components/schemas/Pagination")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User tidak ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User tidak ditemukan"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function getByUser(Request $request, int $userId): JsonResponse
    {
        try {
            // Cek apakah user ada
            $user = User::find($userId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak ditemukan',
                    'data' => null
                ], 404);
            }

            $perPage = $request->get('per_page', 15);
            $sortOrder = $request->get('sort_order', 'desc');

            $examResults = ExamResult::where('user_id', $userId)
                ->with('user:id,name,email')
                ->orderBy('submitted_at', $sortOrder)
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Data exam results untuk user berhasil diambil',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email
                    ],
                    'exam_results' => $examResults->items(),
                    'pagination' => [
                        'current_page' => $examResults->currentPage(),
                        'per_page' => $examResults->perPage(),
                        'total' => $examResults->total(),
                        'last_page' => $examResults->lastPage(),
                        'from' => $examResults->firstItem(),
                        'to' => $examResults->lastItem()
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server',
                'error' => app()->isLocal() ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/exam/exam-results/statistics/overview",
     *     summary="Get exam statistics",
     *     description="Mengambil statistik keseluruhan exam",
     *     operationId="getExamStatistics",
     *     tags={"Exam Results"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Statistik exam berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Statistik exam berhasil diambil"),
     *             @OA\Property(property="data", ref="#/components/schemas/ExamStatistics")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
     */
    public function statistics(): JsonResponse
    {
        try {
            $totalExams = ExamResult::count();
            $totalUsers = ExamResult::distinct('user_id')->count();
            $averageDuration = ExamResult::avg('duration_in_minutes');
            $totalViolations = ExamResult::sum('total_violations');
            $autoSubmitCount = ExamResult::where('is_auto_submit', true)->count();
            $manualSubmitCount = ExamResult::where('is_auto_submit', false)->count();

            // Exam results per day (last 7 days)
            $recentStats = ExamResult::selectRaw('DATE(submitted_at) as date, COUNT(*) as count')
                ->where('submitted_at', '>=', now()->subDays(7))
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Statistik exam berhasil diambil',
                'data' => [
                    'total_exams' => $totalExams,
                    'total_users' => $totalUsers,
                    'average_duration_minutes' => round($averageDuration, 2),
                    'total_violations' => $totalViolations,
                    'auto_submit_count' => $autoSubmitCount,
                    'manual_submit_count' => $manualSubmitCount,
                    'recent_activity' => $recentStats
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server',
                'error' => app()->isLocal() ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/exam/submit",
     *     summary="Submit exam result",
     *     description="Submit hasil pengerjaan exam CBT",
     *     operationId="submitExam",
     *     tags={"Exam Results"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Data exam result yang akan disubmit",
     *         @OA\JsonContent(ref="#/components/schemas/ExamSubmitRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Hasil exam berhasil disimpan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Hasil exam berhasil disimpan"),
     *             @OA\Property(property="data", ref="#/components/schemas/ExamResult")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error atau duplicate submission",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="User sudah melakukan submit exam hari ini"),
     *             @OA\Property(property="data", type="null"),
     *             @OA\Property(property="errors", type="object", description="Detail validation errors (jika ada)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiErrorResponse")
     *     )
     * )
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
