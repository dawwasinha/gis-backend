<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\KaryaController;

// Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::middleware(['jwt'])->group(function () {
    Route::get('/invoices/byAuth', [InvoiceController::class, 'byAuth']);
    Route::get('/users/byAuth', [UserController::class, 'byAuth']);

    // Grup khusus admin
    Route::middleware(['admin'])->group(function () {
        Route::put('/users/verifSuccess/{id}', [UserController::class, 'verifSuccess']);
        Route::get('/karya/{id}', [KaryaController::class, 'show']);
        Route::apiResource('karya', KaryaController::class);
        Route::apiResource('invoices', InvoiceController::class);
        Route::apiResource('users', UserController::class);
        Route::post('/participants', [UserController::class, 'participants']);
    });
});

// Route::get('/users/byAuth', [UserController::class, 'byAuth']);
// Route::apiResource('users', UserController::class);


Route::post('/login', [AuthController::class, 'login']);
// Route::post('/register', function (Request $request) {
//     return response()->json(['message' => 'Register endpoint']);
// });
Route::post('/register', [AuthController::class, 'register']);

Route::get('/phpinfo', function () {
    phpinfo();
});