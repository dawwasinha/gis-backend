<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\KaryaController;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::get('/users/export', [ExportController::class, 'export']);

Route::middleware(['jwt'])->group(function () {
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
        // Route::apiResource('invoices', InvoiceController::class);
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