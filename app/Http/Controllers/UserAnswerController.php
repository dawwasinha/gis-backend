<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\UserAnswer;
use App\Models\Answer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="UserAnswer",
 *     description="Manajemen jawaban user untuk soal ujian"
 * )
 */
class UserAnswerController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/exam/user-answers",
     *     summary="Submit jawaban user",
     *     tags={"UserAnswer"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id", "question_id", "answer_id"},
     *             @OA\Property(property="user_id", type="string", format="uuid"),
     *             @OA\Property(property="question_id", type="string", format="uuid"),
     *             @OA\Property(property="answer_id", type="string", format="uuid")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Jawaban disimpan"),
     *     @OA\Response(response=422, description="Jawaban tidak sesuai dengan soal")
     * )
     */
    public function store(Request $request)
    {
        Log::info('Menyimpan jawaban', [
            'user_id' => $request->user_id,
            'question_id' => $request->question_id,
            'answer_id' => $request->answer_id
        ]);

        $request->validate([
            'user_id' => 'required',
            'question_id' => 'required|uuid|exists:questions,id',
            'answer_id' => 'required|uuid|exists:answers,id'
        ]);

        $answer = Answer::where('id', $request->answer_id)
            ->where('question_id', $request->question_id)
            ->first();

        if (!$answer) {
            return response()->json([
                'message' => 'Jawaban tidak sesuai dengan soal.'
            ], 422);
        }

        $existing = UserAnswer::where('user_id', $request->user_id)
            ->where('question_id', $request->question_id)
            ->first();

        if ($existing) {
            $existing->update([
                'answer_id' => $request->answer_id,
                'answered_at' => now()
            ]);
        } else {
            UserAnswer::create([
                'user_id' => $request->user_id,
                'question_id' => $request->question_id,
                'answer_id' => $request->answer_id,
                'answered_at' => now()
            ]);
        }

        return response()->json(['message' => 'Jawaban disimpan']);
    }

    /**
     * @OA\Delete(
     *     path="/api/exam/user-answers/{id}",
     *     summary="Batalkan jawaban user",
     *     tags={"UserAnswer"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID jawaban user",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=200, description="Jawaban dibatalkan"),
     *     @OA\Response(response=404, description="Jawaban tidak ditemukan")
     * )
     */
    public function destroy($id)
    {
        $answer = UserAnswer::findOrFail($id);
        $answer->delete();

        return response()->json(['message' => 'Jawaban dibatalkan']);
    }

    /**
     * @OA\Get(
     *     path="/api/exam/user-answers/{user_id}",
     *     summary="List jawaban user berdasarkan user_id",
     *     tags={"UserAnswer"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         description="ID user",
     *         required=true,
     *         @OA\Schema(type="string", format="id")
     *     ),
     *     @OA\Response(response=200, description="Daftar jawaban user")
     * )
     */
    public function listByUser($user_id)
    {
        $answers = UserAnswer::with(['question', 'answer'])
            ->where('user_id', $user_id)
            ->get();

        return response()->json($answers);
    }

    /**
     * @OA\Patch(
     *     path="/api/exam/user-answers/{id}/toggle-doubt",
     *     summary="Toggle status ragu-ragu pada jawaban",
     *     tags={"UserAnswer"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID jawaban user",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=200, description="Status ragu-ragu diperbarui"),
     *     @OA\Response(response=404, description="Jawaban tidak ditemukan")
     * )
     */
    public function toggleDoubt($id)
    {
        $answer = UserAnswer::findOrFail($id);
        $answer->is_doubtful = !$answer->is_doubtful;
        $answer->save();

        return response()->json(['message' => 'Status ragu-ragu diperbarui']);
    }

    /**
     * @OA\Patch(
     *     path="/api/exam/user-answers/{id}/unset-doubt",
     *     summary="Batalkan status ragu-ragu pada jawaban user (set is_doubtful ke false)",
     *     tags={"UserAnswer"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID jawaban user",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=200, description="Status ragu-ragu dibatalkan"),
     *     @OA\Response(response=404, description="Jawaban tidak ditemukan")
     * )
     */
    public function unsetDoubt($id)
    {
        $answer = UserAnswer::findOrFail($id);
        $answer->is_doubtful = false;
        $answer->save();

        return response()->json(['message' => 'Status ragu-ragu dibatalkan']);
    }

    /**
     * @OA\Get(
     *     path="/api/exam/results/{user_id}",
     *     summary="Hitung hasil jawaban user",
     *     tags={"UserAnswer"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         description="ID user",
     *         required=true,
     *         @OA\Schema(type="string", format="id")
     *     ),
     *     @OA\Response(response=200, description="Ringkasan hasil user (benar, salah, kosong)")
     * )
     */
    public function result($user_id)
    {
        // Get user to determine their level
        $user = \App\Models\User::findOrFail($user_id);
        
        // Filter questions based on user level
        if ($user->jenjang === 'sd') {
            $total = Question::where('level', 'sd')->count();
            $userAnswers = UserAnswer::with('answer')
                ->whereHas('question', function ($query) {
                    $query->where('level', 'sd');
                })
                ->where('user_id', $user_id)
                ->get();
        } elseif ($user->jenjang === 'smp') {
            $total = Question::where('level', 'smp')->count();
            $userAnswers = UserAnswer::with('answer')
                ->whereHas('question', function ($query) {
                    $query->where('level', 'smp');
                })
                ->where('user_id', $user_id)
                ->get();
        } else {
            // Default: get all questions if level is not specified
            $total = Question::count();
            $userAnswers = UserAnswer::with('answer')
                ->where('user_id', $user_id)
                ->get();
        }

        $answered = $userAnswers->count();
        $correct = $userAnswers->filter(function ($ua) {
            return $ua->answer && $ua->answer->is_correct;
        })->count();
        
        $marked = $userAnswers->filter(function ($ua) {
            return $ua->is_doubtful;
        })->count();

        $wrong = $answered - $correct;
        $unanswered = $total - $answered;
        
        // Calculate score: correct * 4, wrong * -1, unanswered * 0
        $score = ($correct * 4) + ($wrong * -1) + ($unanswered * 0);

        return response()->json([
            'totalQuestions' => $total,
            'answered' => $answered,
            'correct' => $correct,
            'wrong' => $wrong,
            'unanswered' => $unanswered,
            'marked' => $marked,
            'score' => $score
        ]);
    }
}