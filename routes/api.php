<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\KaryaController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\UserAnswerController;
use App\Models\User;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Support\Facades\Hash;

Route::get('/users/export', [ExportController::class, 'export']);

Route::middleware(['jwt'])->group(function () {
    // Tambahan fitur soal dan jawaban
    Route::prefix('exam')->group(function () {
        // Manajemen soal
        Route::get('/questions', [QuestionController::class, 'index']);
        Route::post('/questions', [QuestionController::class, 'store']);
        Route::get('/questions/{id}', [QuestionController::class, 'show']);
        Route::put('/questions/{id}', [QuestionController::class, 'update']);
        Route::delete('/questions/{id}', [QuestionController::class, 'destroy']);

        // Jawaban user
        Route::post('/user-answers', [UserAnswerController::class, 'store']);
        Route::get('/user-answers/{user_id}', [UserAnswerController::class, 'listByUser']);
        Route::delete('/user-answers/{id}', [UserAnswerController::class, 'destroy']);
        Route::patch('/user-answers/{id}/toggle-doubt', [UserAnswerController::class, 'toggleDoubt']);
        Route::patch('/exam/user-answers/{id}/unset-doubt', [UserAnswerController::class, 'unsetDoubt']);
        Route::get('/results/{user_id}', [UserAnswerController::class, 'result']);
        
        // Exam Results Management
        Route::get('/exam-results', [ExamController::class, 'index']);
        Route::get('/exam-results/{id}', [ExamController::class, 'show']);
        Route::get('/exam-results/user/{userId}', [ExamController::class, 'getByUser']);
        Route::get('/exam-results/statistics/overview', [ExamController::class, 'statistics']);
        
        // Submit hasil exam
        Route::post('/submit', [ExamController::class, 'submit']);
    });

    // Route lainnya (dari kamu sebelumnya)
    Route::get('/invoices/byAuth', [InvoiceController::class, 'byAuth']);
    Route::get('/users/byAuth', [UserController::class, 'byAuth']);
    Route::post('/participants', [UserController::class, 'participants']);
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');

    Route::get('/karya/{id}', [KaryaController::class, 'show']);
    Route::apiResource('karya', KaryaController::class);

    // Grup khusus admin
    Route::middleware(['admin'])->group(function () {
        Route::put('/users/verifSuccess/{id}', [UserController::class, 'verifSuccess']);

        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');

        Route::patch('/invoices/{invoice}', [InvoiceController::class, 'update']);                      
        Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
        Route::apiResource('users', UserController::class);
    });
});

// Route::get('/users/byAuth', [UserController::class, 'byAuth']);
// Route::apiResource('users', UserController::class);


Route::post('/login', [AuthController::class, 'login']);
// Route::post('/register', function (Request $request) {
//     return response()->json(['message' => 'Register endpoint']);
// });
Route::post('/register', [AuthController::class, 'register']);

Route::post('/forgot-password', [AuthController::class, 'sendResetCode']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::get('/preview-email', function () {
    return view('emails.reset-password-code', [
        'token' => '123456',
    ]); 
});


Route::get('/download/karya/{filename}', function ($filename) {
    $path = 'karya/' . $filename;

    if (!Storage::disk('public')->exists($path)) {
        abort(404);
    }

    return Storage::disk('public')->download($path);
});

Route::get('/ganti-password-gis2025sukses', function () {
    $admin = User::where('email', 'admin@gis.com')->first(); // Ganti sesuai email admin kamu

    if (!$admin) {
        return 'Admin tidak ditemukan.';
    }

    $admin->password = Hash::make('gis2025sukses');
    $admin->save();

    return 'Password admin berhasil diubah ke "gis2025sukses".';
});

Route::get('/phpinfo', function () {
    phpinfo();
});