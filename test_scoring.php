<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';

// Boot the application
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ExamResult;

// Test the scoring calculation
$examResult = ExamResult::with(['user', 'userAnswers.answer'])->first();

if ($examResult) {
    echo "Exam Result ID: " . $examResult->id . PHP_EOL;
    echo "User: " . $examResult->user->name . PHP_EOL;
    echo "Total User Answers: " . $examResult->userAnswers->count() . PHP_EOL;
    
    $totalScore = 0;
    $correct = 0;
    $incorrect = 0;
    $unanswered = 0;
    
    foreach ($examResult->userAnswers as $userAnswer) {
        if (!$userAnswer->answer_id || !$userAnswer->answer) {
            // Tidak dijawab = 0 poin
            $totalScore += 0;
            $unanswered++;
        } elseif ($userAnswer->answer->is_correct) {
            // Jawaban benar = +4 poin
            $totalScore += 4;
            $correct++;
        } else {
            // Jawaban salah = -1 poin
            $totalScore -= 1;
            $incorrect++;
        }
    }
    
    echo "=== SCORING RESULTS ===" . PHP_EOL;
    echo "Calculated Score: " . $totalScore . PHP_EOL;
    echo "Correct Answers (+4 each): " . $correct . " = " . ($correct * 4) . " points" . PHP_EOL;
    echo "Incorrect Answers (-1 each): " . $incorrect . " = " . ($incorrect * -1) . " points" . PHP_EOL;
    echo "Unanswered (0 each): " . $unanswered . " = 0 points" . PHP_EOL;
    echo "Total Questions: " . ($correct + $incorrect + $unanswered) . PHP_EOL;
    echo "Percentage: " . round(($correct / ($correct + $incorrect + $unanswered)) * 100, 2) . "%" . PHP_EOL;
} else {
    echo "No exam results found." . PHP_EOL;
}
