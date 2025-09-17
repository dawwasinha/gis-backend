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
     * Hitung skor berdasarkan jawaban dengan sistem poin
     * Benar: +4, Salah: -1, Kosong: 0
     */
    public function getScoreAttribute()
    {
        $totalScore = 0;
        
        $userAnswers = $this->userAnswers()->with(['question', 'answer'])->get();
        
        foreach ($userAnswers as $userAnswer) {
            if (!$userAnswer->answer_id || !$userAnswer->answer) {
                // Tidak dijawab = 0 poin
                $totalScore += 0;
            } elseif ($userAnswer->answer->is_correct) {
                // Jawaban benar = +4 poin
                $totalScore += 4;
            } else {
                // Jawaban salah = -1 poin
                $totalScore -= 1;
            }
        }
        
        return $totalScore;
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
                $score = 0;
                $status = 'tidak_dijawab';
                
                if (!$userAnswer->answer_id || !$userAnswer->answer) {
                    $score = 0;
                    $status = 'tidak_dijawab';
                } elseif ($userAnswer->answer->is_correct) {
                    $score = 4;
                    $status = 'benar';
                } else {
                    $score = -1;
                    $status = 'salah';
                }
                
                return [
                    'question_id' => $userAnswer->question_id,
                    'question_text' => $userAnswer->question->question_text,
                    'selected_answer' => $userAnswer->answer ? $userAnswer->answer->answer_text : null,
                    'is_correct' => $userAnswer->answer ? $userAnswer->answer->is_correct : false,
                    'is_doubtful' => $userAnswer->is_doubtful,
                    'score' => $score,
                    'status' => $status
                ];
            });
    }

    /**
     * Get statistik jawaban
     */
    public function getScoreStatisticsAttribute()
    {
        $userAnswers = $this->userAnswers()->with(['answer'])->get();
        
        $correct = 0;
        $incorrect = 0;
        $unanswered = 0;
        
        foreach ($userAnswers as $userAnswer) {
            if (!$userAnswer->answer_id || !$userAnswer->answer) {
                $unanswered++;
            } elseif ($userAnswer->answer->is_correct) {
                $correct++;
            } else {
                $incorrect++;
            }
        }
        
        $totalQuestions = $userAnswers->count();
        $totalScore = $this->score;
        
        return [
            'total_questions' => $totalQuestions,
            'correct_answers' => $correct,
            'incorrect_answers' => $incorrect,
            'unanswered' => $unanswered,
            'total_score' => $totalScore,
            'percentage' => $totalQuestions > 0 ? round(($correct / $totalQuestions) * 100, 2) : 0
        ];
    }
}
