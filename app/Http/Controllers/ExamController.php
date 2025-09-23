<?php

namespace App\Http\Controllers;

use App\Models\ExamResult;
use App\Models\User;
use App\Models\UserStatus;
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
     *         @OA\Schema(type="string", default="score", enum={"score", "submitted_at", "duration_in_minutes", "total_violations"})
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Urutan sorting",
     *         required=false,
     *         @OA\Schema(type="string", default="desc", enum={"asc", "desc"})
     *     ),
     *     @OA\Parameter(
     *         name="level",
     *         in="query",
     *         description="Filter berdasarkan jenjang pendidikan",
     *         required=false,
     *         @OA\Schema(type="string", enum={"SD", "SMP", "SMA", "sd", "smp", "sma"})
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
            $currentPage = $request->get('page', 1);
            $level = $request->get('level');

            // Ambil semua user dengan examResults
            $query = User::with(['examResults'])
                ->where('jenis_lomba', 'science-competition')
                ->where('status', 'success');

            // Filter level kalau ada
            if ($level) {
                $query->where('jenjang', strtoupper($level));
            }

            $users = $query->get();



            $examResults = collect();

            // Hitung skor
            foreach ($users as $user) {
                foreach ($user->examResults as $examResult) {
                    $userAnswers = $examResult->userAnswers()->with(['answer'])->get();

                    $totalScore = 0;
                    $correctAnswers = 0;
                    $incorrectAnswers = 0;
                    $unansweredQuestions = 0;

                    foreach ($userAnswers as $userAnswer) {
                        if (!$userAnswer->answer_id || !$userAnswer->answer) {
                            $unansweredQuestions++;
                        } elseif ($userAnswer->answer->is_correct) {
                            $totalScore += 4;
                            $correctAnswers++;
                        } else {
                            $totalScore -= 1;
                            $incorrectAnswers++;
                        }
                    }

                    $totalQuestions = $userAnswers->count();

                    $resultData = (object) [
                        'id' => $examResult->id,
                        'user_id' => $user->id,
                        'user' => (object) [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'jenjang' => $user->jenjang,
                            'jenis_lomba' => $user->jenis_lomba,
                            'asal_sekolah' => $user->asal_sekolah,
                            'kelas' => $user->kelas,
                            'status' => $user->status
                        ],
                        'duration_in_minutes' => $examResult->duration_in_minutes,
                        'total_violations' => $examResult->total_violations,
                        'is_auto_submit' => $examResult->is_auto_submit,
                        'submitted_at' => $examResult->submitted_at,
                        'calculated_score' => $totalScore,
                        'correct_answers' => $correctAnswers,
                        'incorrect_answers' => $incorrectAnswers,
                        'unanswered_questions' => $unansweredQuestions,
                        'total_questions' => $totalQuestions,
                        'percentage' => $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0,
                        'jenjang' => $user->jenjang,
                        'jenis_lomba' => $user->jenis_lomba
                    ];

                    $examResults->push($resultData);
                }
            }

            // Urutkan berdasarkan skor tertinggi
            $examResults = $examResults->sortByDesc('calculated_score')->values();

            // Manual pagination
            $total = $examResults->count();
            $offset = ($currentPage - 1) * $perPage;
            $itemsForCurrentPage = $examResults->slice($offset, $perPage)->values();
            $lastPage = ceil($total / $perPage);
            $from = $total > 0 ? $offset + 1 : null;
            $to = $total > 0 ? min($offset + $perPage, $total) : null;

            // Statistik per jenjang
            $resultsByJenjang = $examResults->groupBy('jenjang');
            $sdResults = $resultsByJenjang->get('SD', collect());
            $smpResults = $resultsByJenjang->get('SMP', collect());

            return response()->json([
                'success' => true,
                'message' => 'Data exam results diurutkan berdasarkan skor tertinggi',
                'data' => $itemsForCurrentPage,
                'statistics' => [
                    'total_participants' => $total,
                    'sd_participants' => $sdResults->count(),
                    'smp_participants' => $smpResults->count(),
                    'highest_score_overall' => $examResults->first()->calculated_score ?? 0,
                    'lowest_score_overall' => $examResults->last()->calculated_score ?? 0,
                    'highest_score_sd' => $sdResults->max('calculated_score') ?? 0,
                    'highest_score_smp' => $smpResults->max('calculated_score') ?? 0,
                ],
                'pagination' => [
                    'current_page' => $currentPage,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => $lastPage,
                    'from' => $from,
                    'to' => $to
                ],
                'filters' => [
                    'jenis_lomba' => 'science-competition',
                    'level' => $level,
                    'sorted_by' => 'highest_score_first'
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
     *     path="/api/exam/exam-results/science-competition/by-jenjang",
     *     summary="Get science competition results grouped by jenjang",
     *     description="Mengambil hasil science competition yang dipisahkan berdasarkan jenjang SD dan SMP",
     *     operationId="getScienceCompetitionByJenjang",
     *     tags={"Exam Results"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Jumlah item per halaman untuk setiap jenjang",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, minimum=1, maximum=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Data hasil science competition berdasarkan jenjang berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Data hasil science competition berdasarkan jenjang berhasil diambil"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="sd_results", type="array", @OA\Items(ref="#/components/schemas/ExamResult")),
     *                 @OA\Property(property="smp_results", type="array", @OA\Items(ref="#/components/schemas/ExamResult"))
     *             )
     *         )
     *     )
     * )
     */
    public function getScienceCompetitionByJenjang(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);

            // Query untuk science competition saja
            $query = ExamResult::with(['user:id,name,email,jenjang,jenis_lomba'])
                ->whereHas('user', function ($q) {
                    $q->where('jenis_lomba', 'science-competition');
                });

            $examResults = $query->get();

            // Transform untuk menghitung skor
            $examResults->transform(function ($examResult) {
                $userAnswers = $examResult->userAnswers()->with(['answer', 'question'])->get();
                
                $totalScore = 0;
                $correctAnswers = 0;
                $incorrectAnswers = 0;
                $unansweredQuestions = 0;
                
                foreach ($userAnswers as $userAnswer) {
                    if (!$userAnswer->answer_id || !$userAnswer->answer) {
                        $totalScore += 0;
                        $unansweredQuestions++;
                    } elseif ($userAnswer->answer->is_correct) {
                        $totalScore += 4;
                        $correctAnswers++;
                    } else {
                        $totalScore -= 1;
                        $incorrectAnswers++;
                    }
                }
                
                $totalQuestions = $userAnswers->count();
                
                $examResult->calculated_score = $totalScore;
                $examResult->correct_answers = $correctAnswers;
                $examResult->incorrect_answers = $incorrectAnswers;
                $examResult->unanswered_questions = $unansweredQuestions;
                $examResult->total_questions = $totalQuestions;
                $examResult->percentage = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;
                
                return $examResult;
            });

            // Pisahkan berdasarkan jenjang dan sort berdasarkan skor tertinggi
            $sdResults = $examResults->filter(function ($result) {
                return strtoupper($result->user->jenjang) === 'SD';
            })->sortByDesc('calculated_score')->take($perPage)->values();

            $smpResults = $examResults->filter(function ($result) {
                return strtoupper($result->user->jenjang) === 'SMP';
            })->sortByDesc('calculated_score')->take($perPage)->values();

            return response()->json([
                'success' => true,
                'message' => 'Data hasil science competition berdasarkan jenjang berhasil diambil',
                'data' => [
                    'sd_results' => $sdResults,
                    'smp_results' => $smpResults
                ],
                'statistics' => [
                    'total_sd_participants' => $examResults->filter(fn($r) => strtoupper($r->user->jenjang) === 'SD')->count(),
                    'total_smp_participants' => $examResults->filter(fn($r) => strtoupper($r->user->jenjang) === 'SMP')->count(),
                    'highest_score_sd' => $sdResults->first()->calculated_score ?? 0,
                    'highest_score_smp' => $smpResults->first()->calculated_score ?? 0,
                    'average_score_sd' => round($sdResults->avg('calculated_score') ?? 0, 2),
                    'average_score_smp' => round($smpResults->avg('calculated_score') ?? 0, 2),
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
            $examResult = ExamResult::with('user:id,name,email,jenjang')->find($id);

            if (!$examResult) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exam result tidak ditemukan',
                    'data' => null
                ], 404);
            }

            // Tambahkan skor dan statistik baru
            $examResult->score = $examResult->score;
            $examResult->score_statistics = $examResult->score_statistics;
            $examResult->answer_details = $examResult->answer_details;

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
                ->with('user:id,name,email,jenjang')
                ->orderBy('submitted_at', $sortOrder)
                ->paginate($perPage);

            // Tambahkan skor berdasarkan sistem: benar +4, salah -1, kosong 0
            $examResults->getCollection()->transform(function ($examResult) {
                $userAnswers = $examResult->userAnswers()->with(['answer'])->get();
                
                $totalScore = 0;
                $correctAnswers = 0;
                $incorrectAnswers = 0;
                $unansweredQuestions = 0;
                
                foreach ($userAnswers as $userAnswer) {
                    if (!$userAnswer->answer_id || !$userAnswer->answer) {
                        $totalScore += 0;
                        $unansweredQuestions++;
                    } elseif ($userAnswer->answer->is_correct) {
                        $totalScore += 4;
                        $correctAnswers++;
                    } else {
                        $totalScore -= 1;
                        $incorrectAnswers++;
                    }
                }
                
                $totalQuestions = $userAnswers->count();
                
                $examResult->calculated_score = $totalScore;
                $examResult->correct_answers = $correctAnswers;
                $examResult->incorrect_answers = $incorrectAnswers;
                $examResult->unanswered_questions = $unansweredQuestions;
                $examResult->total_questions = $totalQuestions;
                $examResult->percentage = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;
                
                return $examResult;
            });

            return response()->json([
                'success' => true,
                'message' => 'Data exam results untuk user berhasil diambil',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'jenjang' => $user->jenjang
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

            // Hitung statistik skor
            $examResults = ExamResult::with(['userAnswers.answer'])->get();
            $scores = $examResults->map(function ($exam) {
                return $exam->score;
            });
            
            $averageScore = $scores->avg();
            $highestScore = $scores->max();
            $lowestScore = $scores->min();

            // Exam results per day (last 7 days)
            $recentStats = ExamResult::selectRaw('DATE(submitted_at) as date, COUNT(*) as count')
                ->where('submitted_at', '>=', now()->subDays(7))
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->get();

            // Distribusi skor
            $scoreRanges = [
                'excellent' => $scores->filter(fn($score) => $score >= 80)->count(), // 80+ poin
                'good' => $scores->filter(fn($score) => $score >= 60 && $score < 80)->count(), // 60-79 poin
                'fair' => $scores->filter(fn($score) => $score >= 40 && $score < 60)->count(), // 40-59 poin
                'poor' => $scores->filter(fn($score) => $score < 40)->count() // < 40 poin
            ];

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
                    'score_statistics' => [
                        'average_score' => round($averageScore, 2),
                        'highest_score' => $highestScore,
                        'lowest_score' => $lowestScore,
                        'score_distribution' => $scoreRanges
                    ],
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

            // Simpan hasil exam
            $examResult = ExamResult::create([
                'user_id' => $validated['userId'],
                'duration_in_minutes' => $validated['durationInMinutes'],
                'total_violations' => $validated['totalViolations'],
                'is_auto_submit' => $validated['isAutoSubmit'],
                'submitted_at' => now()
            ]);

            // Update atau create user status setelah submit exam
            UserStatus::updateOrCreate(
                ['user_id' => $validated['userId']],
                [
                    'status' => 'inactive', // Set ke inactive setelah submit
                    'reason' => 'CBT exam completed and submitted',
                    'last_cbt_submission' => now(),
                    'updated_at' => now()
                ]
            );

            // Load relasi user untuk response
            $examResult->load('user:id,name,email,jenjang');

            return response()->json([
                'success' => true,
                'message' => 'Hasil exam berhasil disimpan dan status user diupdate',
                'data' => [
                    'id' => $examResult->id,
                    'user' => $examResult->user,
                    'duration_in_minutes' => $examResult->duration_in_minutes,
                    'total_violations' => $examResult->total_violations,
                    'is_auto_submit' => $examResult->is_auto_submit,
                    'submitted_at' => $examResult->submitted_at,
                    'user_status_updated' => true
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

    /**
     * @OA\Get(
     *     path="/api/exam/science-competition/all-participants",
     *     summary="Get all science competition participants",
     *     description="Mengambil semua peserta science competition (yang sudah submit + yang belum submit)",
     *     operationId="getAllScienceCompetitionParticipants",
     *     tags={"Exam Results"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Jumlah item per halaman",
     *         required=false,
     *         @OA\Schema(type="integer", default=20, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="level",
     *         in="query",
     *         description="Filter berdasarkan jenjang pendidikan",
     *         required=false,
     *         @OA\Schema(type="string", enum={"SD", "SMP", "sd", "smp"})
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter berdasarkan status submit",
     *         required=false,
     *         @OA\Schema(type="string", enum={"submitted", "not_submitted", "all"}, default="all")
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Pencarian berdasarkan nama peserta (case-insensitive)",
     *         required=false,
     *         @OA\Schema(type="string", example="John Doe")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="Pencarian berdasarkan ID peserta (exact match)",
     *         required=false,
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Data semua peserta science competition berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Data semua peserta science competition berhasil diambil"),
     *             @OA\Property(property="data", type="array"),
     *             @OA\Property(property="pagination", ref="#/components/schemas/Pagination")
     *         )
     *     )
     * )
     */
    public function getAllScienceCompetitionParticipants(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 20);
            $level = $request->get('level');
            $status = $request->get('status', 'all');
            $name = $request->get('name');
            $id = $request->get('id');

            // Base query untuk science competition users dengan status success onboarding
            $baseQuery = User::where('jenis_lomba', 'science-competition')
                ->where('status', 'success'); // Status onboarding success

            // Filter level kalau ada
            if ($level) {
                $baseQuery->where('jenjang', strtoupper($level));
            }

            // Ambil SEMUA users terlebih dahulu, baru filter setelah transform
            $users = $baseQuery->get();

            // Transform data dengan join 3 table: users, user_answers, exam_results
            $transformedUsers = $users->map(function ($user) {
                // Ambil semua user answers untuk user ini dengan relasi
                $userAnswers = $user->userAnswers()
                    ->with(['answer', 'question'])
                    ->get();
                
                // Ambil exam result untuk user ini jika ada
                $examResult = $user->examResults()->first();
                
                $userData = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'jenjang' => $user->jenjang,
                    'jenis_lomba' => $user->jenis_lomba,
                    'asal_sekolah' => $user->asal_sekolah,
                    'kelas' => $user->kelas,
                    'onboarding_status' => $user->status, // Status onboarding
                    'has_submitted' => $userAnswers->count() > 0,
                    'exam_result' => null,
                    'calculated_score' => 0,
                    'submitted_at' => null,
                    'correct_answers' => 0,
                    'incorrect_answers' => 0,
                    'unanswered_questions' => 0,
                    'total_questions' => 0,
                    'percentage' => 0,
                    'total_violations' => 0,
                    'duration_in_minutes' => 0,
                    'is_auto_submit' => false
                ];

                // Jika ada user answers, hitung skor dan statistik dari join 3 table
                if ($userAnswers->count() > 0) {
                    $totalScore = 0;
                    $correctAnswers = 0;
                    $incorrectAnswers = 0;
                    $unansweredQuestions = 0;
                    
                    // Hitung skor dari user_answers dengan join ke answers
                    foreach ($userAnswers as $userAnswer) {
                        if (!$userAnswer->answer_id || !$userAnswer->answer) {
                            $unansweredQuestions++;
                            // Skor 0 untuk yang kosong
                        } elseif ($userAnswer->answer && $userAnswer->answer->is_correct) {
                            $totalScore += 4;
                            $correctAnswers++;
                        } else {
                            $totalScore -= 1;
                            $incorrectAnswers++;
                        }
                    }
                    
                    $totalQuestions = $userAnswers->count();
                    
                    // Update user data dengan hasil perhitungan
                    $userData['calculated_score'] = $totalScore;
                    $userData['correct_answers'] = $correctAnswers;
                    $userData['incorrect_answers'] = $incorrectAnswers;
                    $userData['unanswered_questions'] = $unansweredQuestions;
                    $userData['total_questions'] = $totalQuestions;
                    $userData['percentage'] = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100, 2) : 0;
                    
                    // Ambil data dari exam_results table jika ada
                    if ($examResult) {
                        $userData['exam_result'] = [
                            'id' => $examResult->id,
                            'duration_in_minutes' => $examResult->duration_in_minutes,
                            'total_violations' => $examResult->total_violations,
                            'is_auto_submit' => $examResult->is_auto_submit,
                            'submitted_at' => $examResult->submitted_at,
                        ];
                        $userData['submitted_at'] = $examResult->submitted_at;
                        $userData['total_violations'] = $examResult->total_violations;
                        $userData['duration_in_minutes'] = $examResult->duration_in_minutes;
                        $userData['is_auto_submit'] = $examResult->is_auto_submit;
                    } else {
                        // Jika tidak ada exam result, gunakan timestamp dari user answer terakhir
                        $userData['submitted_at'] = $userAnswers->max('updated_at');
                        $userData['total_violations'] = 0;
                        $userData['duration_in_minutes'] = 0;
                        $userData['is_auto_submit'] = false;
                    }
                }

                return $userData;
            });

            // Filter berdasarkan status SETELAH transform (agar tidak ada yang terlewat)
            if ($status === 'submitted') {
                $transformedUsers = $transformedUsers->filter(function ($user) {
                    return $user['has_submitted'] === true;
                });
            } elseif ($status === 'not_submitted') {
                $transformedUsers = $transformedUsers->filter(function ($user) {
                    return $user['has_submitted'] === false;
                });
            }
            // Jika $status === 'all', tidak perlu filter tambahan

            // Filter berdasarkan nama (pencarian case-insensitive)
            if ($name) {
                $transformedUsers = $transformedUsers->filter(function ($user) use ($name) {
                    return stripos($user['name'], $name) !== false;
                });
            }

            // Filter berdasarkan ID (exact match)
            if ($id) {
                $transformedUsers = $transformedUsers->filter(function ($user) use ($id) {
                    return $user['id'] == $id;
                });
            }

            // Sort berdasarkan skor tertinggi, yang sudah submit dulu, lalu yang belum submit
            $transformedUsers = $transformedUsers->sortBy([
                ['has_submitted', 'desc'], // Yang sudah submit dulu
                ['calculated_score', 'desc'] // Lalu sort berdasarkan skor tertinggi
            ])->values();

            // Manual pagination setelah sorting
            $total = $transformedUsers->count();
            $currentPage = $request->get('page', 1);
            $offset = ($currentPage - 1) * $perPage;
            $itemsForCurrentPage = $transformedUsers->slice($offset, $perPage)->values();
            $lastPage = ceil($total / $perPage);
            $from = $total > 0 ? $offset + 1 : null;
            $to = $total > 0 ? min($offset + $perPage, $total) : null;

            // Statistik berdasarkan join 3 table
            $totalUsers = User::where('jenis_lomba', 'science-competition')
                ->where('status', 'success') // Yang sudah lolos onboarding
                ->count();
            $submittedUsers = User::where('jenis_lomba', 'science-competition')
                ->where('status', 'success')
                ->whereHas('userAnswers') // Yang sudah submit jawaban
                ->count();
            $notSubmittedUsers = $totalUsers - $submittedUsers;
            
            $sdTotal = User::where('jenis_lomba', 'science-competition')
                ->where('status', 'success')
                ->where('jenjang', 'sd')
                ->count();
            $smpTotal = User::where('jenis_lomba', 'science-competition')
                ->where('status', 'success')
                ->where('jenjang', 'smp')
                ->count();
            $sdSubmitted = User::where('jenis_lomba', 'science-competition')
                ->where('status', 'success')
                ->where('jenjang', 'sd')
                ->whereHas('userAnswers')
                ->count();
            $smpSubmitted = User::where('jenis_lomba', 'science-competition')
                ->where('status', 'success')
                ->where('jenjang', 'smp')
                ->whereHas('userAnswers')
                ->count();

            return response()->json([
                'success' => true,
                'message' => 'Data SEMUA peserta science competition berhasil diambil (termasuk yang sudah mengisi user_answers)',
                'data' => $itemsForCurrentPage,
                'statistics' => [
                    'total_participants' => $totalUsers,
                    'submitted_participants' => $submittedUsers,
                    'not_submitted_participants' => $notSubmittedUsers,
                    'submission_rate' => $totalUsers > 0 ? round(($submittedUsers / $totalUsers) * 100, 2) : 0,
                    'sd_statistics' => [
                        'total' => $sdTotal,
                        'submitted' => $sdSubmitted,
                        'not_submitted' => $sdTotal - $sdSubmitted,
                        'submission_rate' => $sdTotal > 0 ? round(($sdSubmitted / $sdTotal) * 100, 2) : 0
                    ],
                    'smp_statistics' => [
                        'total' => $smpTotal,
                        'submitted' => $smpSubmitted,
                        'not_submitted' => $smpTotal - $smpSubmitted,
                        'submission_rate' => $smpTotal > 0 ? round(($smpSubmitted / $smpTotal) * 100, 2) : 0
                    ]
                ],
                'pagination' => [
                    'current_page' => $currentPage,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => $lastPage,
                    'from' => $from,
                    'to' => $to
                ],
                'filters' => [
                    'jenis_lomba' => 'science-competition',
                    'level' => $level,
                    'status' => $status,
                    'name' => $name,
                    'id' => $id,
                    'data_source' => 'join_3_tables'
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
     *     path="/api/exam/exam-results/{id}/answers",
     *     summary="Get detailed answers for specific exam result",
     *     description="Mengambil detail jawaban dari exam result tertentu",
     *     operationId="getExamAnswers",
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
     *         description="Detail jawaban berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Detail jawaban berhasil diambil"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="exam_result", ref="#/components/schemas/ExamResult"),
     *                 @OA\Property(property="score", type="number", example=85.5),
     *                 @OA\Property(property="correct_answers", type="integer", example=17),
     *                 @OA\Property(property="total_questions", type="integer", example=20),
     *                 @OA\Property(property="answer_details", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="question_id", type="string", example="uuid-1234"),
     *                         @OA\Property(property="question_text", type="string", example="Apa ibu kota Indonesia?"),
     *                         @OA\Property(property="selected_answer", type="string", example="Jakarta"),
     *                         @OA\Property(property="is_correct", type="boolean", example=true),
     *                         @OA\Property(property="is_doubtful", type="boolean", example=false)
     *                     )
     *                 )
     *             )
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
     *     )
     * )
     */
    public function getAnswers(int $id): JsonResponse
    {
        try {
            $examResult = ExamResult::with('user:id,name,email,jenjang')->find($id);

            if (!$examResult) {
                return response()->json([
                    'success' => false,
                    'message' => 'Exam result tidak ditemukan',
                    'data' => null
                ], 404);
            }

            $score = $examResult->score;
            $correctAnswers = $examResult->userAnswers()
                ->join('answers', 'user_answers.answer_id', '=', 'answers.id')
                ->where('answers.is_correct', true)
                ->count();
            $totalQuestions = $examResult->userAnswers()->count();
            $answerDetails = $examResult->answer_details;

            return response()->json([
                'success' => true,
                'message' => 'Detail jawaban berhasil diambil',
                'data' => [
                    'exam_result' => $examResult,
                    'score' => $score,
                    'correct_answers' => $correctAnswers,
                    'total_questions' => $totalQuestions,
                    'answer_details' => $answerDetails
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
}
