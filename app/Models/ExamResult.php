<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamResult extends Model
{
    protected $fillable = [
        'user_id',
        'duration_in_minutes',
        'total_violations',
        'is_auto_submit',
        'submitted_at'
    ];

    protected $casts = [
        'is_auto_submit' => 'boolean',
        'submitted_at' => 'datetime'
    ];

    /**
     * Relasi ke User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke UserAnswer berdasarkan user_id dan waktu exam
     */
    public function userAnswers(): HasMany
    {
        return $this->hasMany(UserAnswer::class, 'user_id', 'user_id')
            ->whereBetween('answered_at', [
                $this->submitted_at->startOfDay(),
                $this->submitted_at->endOfDay()
            ]);
    }

    /**
     * Hitung skor berdasarkan jawaban yang benar
     */
    public function getScoreAttribute()
    {
        $correctAnswers = $this->userAnswers()
            ->join('answers', 'user_answers.answer_id', '=', 'answers.id')
            ->where('answers.is_correct', true)
            ->count();

        $totalQuestions = $this->userAnswers()->count();

        if ($totalQuestions == 0) {
            return 0;
        }

        return round(($correctAnswers / $totalQuestions) * 100, 2);
    }

    /**
     * Get detail jawaban untuk review
     */
    public function getAnswerDetailsAttribute()
    {
        return $this->userAnswers()
            ->with(['question', 'answer'])
            ->get()
            ->map(function ($userAnswer) {
                return [
                    'question_id' => $userAnswer->question_id,
                    'question_text' => $userAnswer->question->question_text,
                    'selected_answer' => $userAnswer->answer->answer_text,
                    'is_correct' => $userAnswer->answer->is_correct,
                    'is_doubtful' => $userAnswer->is_doubtful
                ];
            });
    }
}
