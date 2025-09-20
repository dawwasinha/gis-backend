<?php

// Method index yang dioptimasi untuk ExamController

public function indexOptimized(Request $request): JsonResponse
{
    try {
        $perPage = $request->get('per_page', 15);
        $sortBy = $request->get('sort_by', 'score');
        $sortOrder = $request->get('sort_order', 'desc');
        $level = $request->get('level');

        // Gunakan pagination database langsung untuk performa optimal
        $query = ExamResult::with(['user:id,name,email,jenjang,jenis_lomba,asal_sekolah,kelas'])
            ->whereHas('user', function ($q) use ($level) {
                $q->where('jenis_lomba', 'science-competition');
                if ($level) {
                    $q->where('jenjang', 'LIKE', strtoupper($level));
                }
            });

        // Untuk sorting selain score, gunakan database langsung
        if ($sortBy !== 'score') {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Paginate langsung dari database (PALING CEPAT)
        $paginatedResults = $query->paginate($perPage);
        
        // Transform hanya data pada page saat ini
        $itemsForCurrentPage = $paginatedResults->getCollection()->map(function ($examResult) {
            // Hitung score untuk data page ini saja
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
            
            return (object) [
                'id' => $examResult->id,
                'user_id' => $examResult->user->id,
                'user' => (object) [
                    'id' => $examResult->user->id,
                    'name' => $examResult->user->name,
                    'email' => $examResult->user->email,
                    'jenjang' => $examResult->user->jenjang,
                    'jenis_lomba' => $examResult->user->jenis_lomba,
                    'asal_sekolah' => $examResult->user->asal_sekolah,
                    'kelas' => $examResult->user->kelas
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
                'jenjang' => $examResult->user->jenjang,
                'jenis_lomba' => $examResult->user->jenis_lomba
            ];
        });

        // Jika sorting berdasarkan score, sort collection hasil transform
        if ($sortBy === 'score') {
            $itemsForCurrentPage = $sortOrder === 'desc' 
                ? $itemsForCurrentPage->sortByDesc('calculated_score')->values()
                : $itemsForCurrentPage->sortBy('calculated_score')->values();
        }

        // Statistik sederhana dari current page saja
        $sdResults = $itemsForCurrentPage->filter(fn($r) => $r->jenjang === 'SD');
        $smpResults = $itemsForCurrentPage->filter(fn($r) => $r->jenjang === 'SMP');

        return response()->json([
            'success' => true,
            'message' => 'Data exam results science-competition berhasil diambil (OPTIMIZED - PAGE ONLY)',
            'data' => $itemsForCurrentPage,
            'statistics' => [
                'current_page_participants' => $itemsForCurrentPage->count(),
                'current_page_sd' => $sdResults->count(),
                'current_page_smp' => $smpResults->count(),
                'top_score_current_page' => $itemsForCurrentPage->max('calculated_score') ?? 0,
                'optimization' => 'Database pagination + current page calculation only',
            ],
            'pagination' => [
                'current_page' => $paginatedResults->currentPage(),
                'per_page' => $paginatedResults->perPage(),
                'total' => $paginatedResults->total(),
                'last_page' => $paginatedResults->lastPage(),
                'from' => $paginatedResults->firstItem(),
                'to' => $paginatedResults->lastItem()
            ],
            'filters' => [
                'jenis_lomba' => 'science-competition',
                'level' => $level
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
