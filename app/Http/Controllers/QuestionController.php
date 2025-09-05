<?php

namespace App\Http\Controllers;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
     *     summary="Buat soal baru beserta 4 jawaban dengan upload file gambar",
     *     tags={"Question"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"level","type","answers"},
     *                 @OA\Property(property="level", type="string", enum={"SD", "SMP"}),
     *                 @OA\Property(property="type", type="string", enum={"text", "image"}),
     *                 @OA\Property(property="question_text", type="string", nullable=true),
     *                 @OA\Property(property="question_img", type="string", format="binary", nullable=true, description="Upload file gambar soal"),
     *                 @OA\Property(property="answers[0][type]", type="string", enum={"text", "image"}),
     *                 @OA\Property(property="answers[0][answer_text]", type="string", nullable=true),
     *                 @OA\Property(property="answers[0][answer_img]", type="string", format="binary", nullable=true, description="Upload file gambar jawaban"),
     *                 @OA\Property(property="answers[0][is_correct]", type="boolean"),
     *                 @OA\Property(property="answers[1][type]", type="string", enum={"text", "image"}),
     *                 @OA\Property(property="answers[1][answer_text]", type="string", nullable=true),
     *                 @OA\Property(property="answers[1][answer_img]", type="string", format="binary", nullable=true),
     *                 @OA\Property(property="answers[1][is_correct]", type="boolean"),
     *                 @OA\Property(property="answers[2][type]", type="string", enum={"text", "image"}),
     *                 @OA\Property(property="answers[2][answer_text]", type="string", nullable=true),
     *                 @OA\Property(property="answers[2][answer_img]", type="string", format="binary", nullable=true),
     *                 @OA\Property(property="answers[2][is_correct]", type="boolean"),
     *                 @OA\Property(property="answers[3][type]", type="string", enum={"text", "image"}),
     *                 @OA\Property(property="answers[3][answer_text]", type="string", nullable=true),
     *                 @OA\Property(property="answers[3][answer_img]", type="string", format="binary", nullable=true),
     *                 @OA\Property(property="answers[3][is_correct]", type="boolean")
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
            'question_img' => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:2048',
            'answers' => 'required|array|size:4',
            'answers.*.type' => 'required|in:text,image',
            'answers.*.answer_text' => 'nullable|string',
            'answers.*.answer_img' => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:2048',
            'answers.*.is_correct' => 'boolean'
        ]);

        $correctCount = collect($request->answers)->where('is_correct', true)->count();
        if ($correctCount !== 1) {
            return response()->json(['message' => 'Harus ada tepat satu jawaban yang benar.'], 422);
        }

        // Handle question image upload
        $questionImgPath = null;
        if ($request->hasFile('question_img')) {
            $questionImgPath = $request->file('question_img')->store('questions', 'public');
        }

        $question = Question::create([
            'level' => $request->level,
            'type' => $request->type,
            'question_text' => $request->question_text,
            'question_img' => $questionImgPath
        ]);

        foreach ($request->answers as $index => $a) {
            // Handle answer image upload
            $answerImgPath = null;
            if ($request->hasFile("answers.{$index}.answer_img")) {
                $answerImgPath = $request->file("answers.{$index}.answer_img")->store('answers', 'public');
            }

            $question->answers()->create([
                'type' => $a['type'],
                'answer_text' => $a['answer_text'] ?? null,
                'answer_img' => $answerImgPath,
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
     *     summary="Perbarui soal dan jawabannya dengan upload file gambar",
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
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"level", "type", "answers"},
     *                 @OA\Property(property="level", type="string", enum={"SD", "SMP"}, example="SD"),
     *                 @OA\Property(property="type", type="string", enum={"text", "image"}, example="text"),
     *                 @OA\Property(property="question_text", type="string", nullable=true, example="Apa ibukota Indonesia?"),
     *                 @OA\Property(property="question_img", type="string", format="binary", nullable=true, description="Upload file gambar soal"),
     *                 @OA\Property(property="answers[0][type]", type="string", enum={"text", "image"}),
     *                 @OA\Property(property="answers[0][answer_text]", type="string", nullable=true),
     *                 @OA\Property(property="answers[0][answer_img]", type="string", format="binary", nullable=true),
     *                 @OA\Property(property="answers[0][is_correct]", type="boolean"),
     *                 @OA\Property(property="answers[1][type]", type="string", enum={"text", "image"}),
     *                 @OA\Property(property="answers[1][answer_text]", type="string", nullable=true),
     *                 @OA\Property(property="answers[1][answer_img]", type="string", format="binary", nullable=true),
     *                 @OA\Property(property="answers[1][is_correct]", type="boolean"),
     *                 @OA\Property(property="answers[2][type]", type="string", enum={"text", "image"}),
     *                 @OA\Property(property="answers[2][answer_text]", type="string", nullable=true),
     *                 @OA\Property(property="answers[2][answer_img]", type="string", format="binary", nullable=true),
     *                 @OA\Property(property="answers[2][is_correct]", type="boolean"),
     *                 @OA\Property(property="answers[3][type]", type="string", enum={"text", "image"}),
     *                 @OA\Property(property="answers[3][answer_text]", type="string", nullable=true),
     *                 @OA\Property(property="answers[3][answer_img]", type="string", format="binary", nullable=true),
     *                 @OA\Property(property="answers[3][is_correct]", type="boolean")
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
            'question_img' => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:2048',
            'existing_question_img_url' => 'nullable|string', // URL gambar existing
            'answers' => 'required|array|size:4',
            'answers.*.id' => 'nullable', // ID jawaban untuk update existing
            'answers.*.type' => 'required|in:text,image',
            'answers.*.answer_text' => 'nullable|string',
            'answers.*.answer_img' => 'nullable|file|image|mimes:jpeg,png,jpg,gif|max:2048',
            'answers.*.existing_img_url' => 'nullable|string', // URL gambar jawaban existing
            'answers.*.is_correct' => 'boolean'
        ]);

        $correctCount = collect($request->answers)->where('is_correct', true)->count();
        if ($correctCount !== 1) {
            return response()->json(['message' => 'Harus ada tepat satu jawaban yang benar.'], 422);
        }

        $question = Question::findOrFail($id);
        
        // Handle question image update
        $questionImgPath = $question->question_img; // Keep existing by default
        if ($request->hasFile('question_img')) {
            // Ada file baru - hapus yang lama dan upload yang baru
            if ($questionImgPath && Storage::disk('public')->exists($questionImgPath)) {
                Storage::disk('public')->delete($questionImgPath);
            }
            $questionImgPath = $request->file('question_img')->store('questions', 'public');
        }
        // Jika tidak ada file baru, tetap gunakan gambar existing (tidak berubah)
        
        // Update question data
        $question->update([
            'level' => $request->level,
            'type' => $request->type,
            'question_text' => $request->question_text,
            'question_img' => $questionImgPath
        ]);

        // Get existing answers untuk tracking perubahan
        $existingAnswers = $question->answers()->get()->keyBy('id');
        $updatedAnswerIds = [];

        foreach ($request->answers as $index => $answerData) {
            // Cari jawaban existing berdasarkan ID (jika ada)
            $answerId = $answerData['id'] ?? null;
            $existingAnswer = $answerId ? $existingAnswers->get($answerId) : null;
            
            // Handle answer image
            $answerImgPath = $existingAnswer ? $existingAnswer->answer_img : null; // Default ke existing
            
            if (isset($answerData['answer_img']) && $request->hasFile("answers.{$index}.answer_img")) {
                // Ada file baru - hapus yang lama dan upload yang baru
                if ($answerImgPath && Storage::disk('public')->exists($answerImgPath)) {
                    Storage::disk('public')->delete($answerImgPath);
                }
                $answerImgPath = $request->file("answers.{$index}.answer_img")->store('answers', 'public');
            }
            // Jika tidak ada file baru, tetap gunakan gambar existing (tidak berubah)
            
            if ($existingAnswer) {
                // Update jawaban existing
                $existingAnswer->update([
                    'type' => $answerData['type'],
                    'answer_text' => $answerData['answer_text'] ?? null,
                    'answer_img' => $answerImgPath,
                    'is_correct' => $answerData['is_correct'] ?? false
                ]);
                $updatedAnswerIds[] = $existingAnswer->id;
            } else {
                // Buat jawaban baru
                $newAnswer = $question->answers()->create([
                    'type' => $answerData['type'],
                    'answer_text' => $answerData['answer_text'] ?? null,
                    'answer_img' => $answerImgPath,
                    'is_correct' => $answerData['is_correct'] ?? false
                ]);
                $updatedAnswerIds[] = $newAnswer->id;
            }
        }

        // Hapus jawaban yang tidak ada dalam request (jika ada yang dihapus)
        $answersToDelete = $existingAnswers->whereNotIn('id', $updatedAnswerIds);
        foreach ($answersToDelete as $answerToDelete) {
            // Hapus file gambar jika ada
            if ($answerToDelete->answer_img && Storage::disk('public')->exists($answerToDelete->answer_img)) {
                Storage::disk('public')->delete($answerToDelete->answer_img);
            }
            $answerToDelete->delete();
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
        $question = Question::with('answers')->findOrFail($id);
        
        // Delete question image if exists
        if ($question->question_img && Storage::disk('public')->exists($question->question_img)) {
            Storage::disk('public')->delete($question->question_img);
        }
        
        // Delete answer images if exist
        foreach ($question->answers as $answer) {
            if ($answer->answer_img && Storage::disk('public')->exists($answer->answer_img)) {
                Storage::disk('public')->delete($answer->answer_img);
            }
        }
        
        $question->delete();

        return response()->json(['message' => 'Soal berhasil dihapus']);
    }
}