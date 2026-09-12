<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PartController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\PoController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\SuratJalanController;

// Public
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/health', fn() => response()->json(['status' => 'ok']));

// Protected
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Boleh diakses semua role yang login (baca data)
    Route::get('/parts', [PartController::class, 'index']);
    Route::get('/po', [PoController::class, 'index']);
    Route::get('/po/{id}', [PoController::class, 'show']);
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/chart', [TransactionController::class, 'chart']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::post('/transactions/{id}/approve-qc', [TransactionController::class, 'approveQc']);
    Route::post('/surat-jalan', [SuratJalanController::class, 'generate']);
    Route::get('/po/{id}/pending-surat-jalan', [SuratJalanController::class, 'pendingForPo']);

    // Khusus admin & pcd -> kelola master data part (nambah, hapus, import part baru)
    Route::middleware('role:admin,pcd')->group(function () {
        Route::post('/parts', [PartController::class, 'store']);
        Route::delete('/parts/{id}', [PartController::class, 'destroy']);
        Route::post('/parts/import', [PartController::class, 'import']);
        Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);
    });

    // Khusus admin & marketing -> kelola harga & bikin PO baru
    Route::middleware('role:admin,marketing')->group(function () {
        Route::put('/parts/{id}/price', [PartController::class, 'updatePrice']);
        Route::post('/parts/import-price', [PartController::class, 'importPrice']);
        Route::post('/po', [PoController::class, 'store']);
        Route::post('/po/{id}/items', [PoController::class, 'addItem']);
        Route::delete('/po/{id}/items/{itemId}', [PoController::class, 'removeItem']);
    });

    // approve() ga dipasangin middleware role di sini, karena role yang diizinkan
    // beda-beda tergantung TAHAP approval-nya (mkt1 -> pcd -> mkt2) — itu tetap dicek
    // secara dinamis di dalam PoController@approve.
    Route::post('/po/{id}/approve', [PoController::class, 'approve']);

    // Khusus admin -> lihat & download audit trail
    Route::middleware('role:admin')->group(function () {
        Route::get('/activity-logs', [ActivityLogController::class, 'index']);
        Route::get('/activity-logs/export', [ActivityLogController::class, 'export']);
    });

    // Khusus admin & pcd -> lihat & download ulang history surat jalan
    Route::middleware('role:admin,pcd')->group(function () {
        Route::get('/surat-jalan', [SuratJalanController::class, 'index']);
        Route::get('/surat-jalan/{id}/download', [SuratJalanController::class, 'download']);
    });
});