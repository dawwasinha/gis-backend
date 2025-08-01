<?php

namespace App\Http\Controllers;

use App\Models\Question;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Question",
 *     description="Manajemen soal ujian untuk SD dan SMP"
 * )
 */
class QuestionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/exam/questions",
     *     summary="Ambil semua soal (opsional filter level)",
     *     tags={"Question"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="level",
     *         in="query",
     *         description="Level soal (SD atau SMP)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"SD", "SMP"})
     *     ),
     *     @OA\Response(response=200, description="Daftar soal berhasil diambil")
     * )
     */
    public function index(Request $request)
    {
        $questions = Question::with('answers')
            ->when($request->level, function ($query) use ($request) {
                $query->where('level', $request->level);
            })
            ->get();

        return response()->json($questions);
    }

    /**
     * @OA\Get(
     *     path="/api/exam/questions/{id}",
     *     summary="Ambil detail soal berdasarkan ID",
     *     tags={"Question"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID soal",
     *         required=true,
     *         @OA\Schema(type="string", example="1")
     *     ),
     *     @OA\Response(response=200, description="Detail soal berhasil diambil"),
     *     @OA\Response(response=404, description="Soal tidak ditemukan")
     * )
     */
    public function show($id)
    {
        $question = Question::with('answers')->findOrFail($id);
        return response()->json($question);
    }

    /**
     * @OA\Post(
     *     path="/api/exam/questions",
     *     summary="Buat soal baru beserta 4 jawaban",
     *     tags={"Question"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"level","type","answers"},
     *             @OA\Property(property="level", type="string", enum={"SD", "SMP"}),
     *             @OA\Property(property="type", type="string", enum={"text", "image"}),
     *             @OA\Property(property="question_text", type="string", nullable=true),
     *             @OA\Property(property="question_img", type="string", nullable=true),
     *             @OA\Property(
     *                 property="answers",
     *                 type="array",
     *                 minItems=4,
     *                 maxItems=4,
     *                 @OA\Items(
     *                     type="object",
     *                     required={"type"},
     *                     @OA\Property(property="type", type="string", enum={"text", "image"}),
     *                     @OA\Property(property="answer_text", type="string", nullable=true),
     *                     @OA\Property(property="answer_img", type="string", nullable=true),
     *                     @OA\Property(property="is_correct", type="boolean")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Soal berhasil disimpan"),
     *     @OA\Response(response=422, description="Validasi gagal")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'level' => 'required|in:SD,SMP',
            'type' => 'required|in:text,image',
            'question_text' => 'nullable|string',
            'question_img' => 'nullable|string',
            'answers' => 'required|array|size:4',
            'answers.*.type' => 'required|in:text,image',
            'answers.*.answer_text' => 'nullable|string',
            'answers.*.answer_img' => 'nullable|string',
            'answers.*.is_correct' => 'boolean'
        ]);

        $correctCount = collect($request->answers)->where('is_correct', true)->count();
        if ($correctCount !== 1) {
            return response()->json(['message' => 'Harus ada tepat satu jawaban yang benar.'], 422);
        }

        $question = Question::create([
            'level' => $request->level,
            'type' => $request->type,
            'question_text' => $request->question_text,
            'question_img' => $request->question_img
        ]);

        foreach ($request->answers as $a) {
            $question->answers()->create([
                'type' => $a['type'],
                'answer_text' => $a['answer_text'] ?? null,
                'answer_img' => $a['answer_img'] ?? null,
                'is_correct' => $a['is_correct'] ?? false
            ]);
        }

        return response()->json([
            'message' => 'Soal berhasil disimpan',
            'question' => $question->load('answers')
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/exam/questions/{id}",
     *     summary="Perbarui soal dan jawabannya",
     *     tags={"Question"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID soal",
     *         @OA\Schema(type="string", example="1")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"level", "type", "answers"},
     *             @OA\Property(property="level", type="string", enum={"SD", "SMP"}, example="SD"),
     *             @OA\Property(property="type", type="string", enum={"text", "image"}, example="text"),
     *             @OA\Property(property="question_text", type="string", nullable=true, example="Apa ibukota Indonesia?"),
     *             @OA\Property(property="question_img", type="string", nullable=true, example="http://example.com/image.png"),
     *             @OA\Property(
     *                 property="answers",
     *                 type="array",
     *                 minItems=4,
     *                 maxItems=4,
     *                 @OA\Items(
     *                     type="object",
     *                     required={"type", "is_correct"},
     *                     @OA\Property(property="type", type="string", enum={"text", "image"}, example="text"),
     *                     @OA\Property(property="answer_text", type="string", nullable=true, example="Jakarta"),
     *                     @OA\Property(property="answer_img", type="string", nullable=true, example=null),
     *                     @OA\Property(property="is_correct", type="boolean", example=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Soal berhasil diperbarui"),
     *     @OA\Response(response=422, description="Validasi gagal")
     * )
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'level' => 'required|in:SD,SMP',
            'type' => 'required|in:text,image',
            'question_text' => 'nullable|string',
            'question_img' => 'nullable|string',
            'answers' => 'required|array|size:4',
            'answers.*.type' => 'required|in:text,image',
            'answers.*.answer_text' => 'nullable|string',
            'answers.*.answer_img' => 'nullable|string',
            'answers.*.is_correct' => 'boolean'
        ]);

        $correctCount = collect($request->answers)->where('is_correct', true)->count();
        if ($correctCount !== 1) {
            return response()->json(['message' => 'Harus ada tepat satu jawaban yang benar.'], 422);
        }

        $question = Question::findOrFail($id);
        $question->update([
            'level' => $request->level,
            'type' => $request->type,
            'question_text' => $request->question_text,
            'question_img' => $request->question_img
        ]);

        $question->answers()->delete(); // hapus semua jawaban lama

        foreach ($request->answers as $a) {
            $question->answers()->create([
                'type' => $a['type'],
                'answer_text' => $a['answer_text'] ?? null,
                'answer_img' => $a['answer_img'] ?? null,
                'is_correct' => $a['is_correct'] ?? false
            ]);
        }

        return response()->json([
            'message' => 'Soal berhasil diperbarui',
            'question' => $question->load('answers')
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/exam/questions/{id}",
     *     summary="Hapus soal berdasarkan ID",
     *     tags={"Question"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID soal",
     *         required=true,
     *         @OA\Schema(type="string", example="1")
     *     ),
     *     @OA\Response(response=200, description="Soal berhasil dihapus"),
     *     @OA\Response(response=404, description="Soal tidak ditemukan")
     * )
     */
    public function destroy($id)
    {
        $question = Question::findOrFail($id);
        $question->delete();

        return response()->json(['message' => 'Soal berhasil dihapus']);
    }
}