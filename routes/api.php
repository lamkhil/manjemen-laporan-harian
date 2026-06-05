<?php

use App\Http\Controllers\AktivitasController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BidangController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\JenisLayananController;
use App\Http\Controllers\LokasiController;
use App\Http\Controllers\LoketController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportPdfController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok', 'app' => config('app.name')]));

Route::post('/login', [AuthController::class, 'login']);

Route::get('/users/example-csv', [UserController::class, 'exampleCsv']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/stats/summary', [StatsController::class, 'summary']);

    Route::apiResource('categories', CategoryController::class);

    Route::get('bidangs', [BidangController::class, 'index']);
    Route::post('bidangs', [BidangController::class, 'store']);
    Route::patch('bidangs/{bidang}', [BidangController::class, 'update']);
    Route::delete('bidangs/{bidang}', [BidangController::class, 'destroy']);

    Route::get('jenis-layanans', [JenisLayananController::class, 'index']);
    Route::post('jenis-layanans', [JenisLayananController::class, 'store']);
    Route::patch('jenis-layanans/{jenis_layanan}', [JenisLayananController::class, 'update']);
    Route::delete('jenis-layanans/{jenis_layanan}', [JenisLayananController::class, 'destroy']);

    Route::get('reports-rekap/pdf', [ReportPdfController::class, 'rekapDownload']);
    Route::get('reports-rekap/preview', [ReportPdfController::class, 'rekapPreview']);
    Route::get('reports-rekap/bidang/{bidang}/pdf', [ReportPdfController::class, 'rekapBidangDownload']);
    Route::get('reports-rekap/bidang/{bidang}/preview', [ReportPdfController::class, 'rekapBidangPreview']);

    Route::get('aktivitas', [AktivitasController::class, 'index']);
    Route::get('aktivitas/{aktivita}', [AktivitasController::class, 'show']);
    Route::post('aktivitas', [AktivitasController::class, 'store']);
    Route::patch('aktivitas/{aktivita}', [AktivitasController::class, 'update']);
    Route::delete('aktivitas/{aktivita}', [AktivitasController::class, 'destroy']);
    Route::post('aktivitas/{aktivita}/photos', [AktivitasController::class, 'uploadPhoto']);
    Route::delete('aktivitas/{aktivita}/photos/{photo}', [AktivitasController::class, 'deletePhoto']);

    Route::get('lokasis', [LokasiController::class, 'index']);
    Route::post('lokasis', [LokasiController::class, 'store']);
    Route::patch('lokasis/{lokasi}', [LokasiController::class, 'update']);
    Route::delete('lokasis/{lokasi}', [LokasiController::class, 'destroy']);

    Route::get('lokets', [LoketController::class, 'index']);
    Route::post('lokets', [LoketController::class, 'store']);
    Route::patch('lokets/{loket}', [LoketController::class, 'update']);
    Route::delete('lokets/{loket}', [LoketController::class, 'destroy']);

    Route::get('users', [UserController::class, 'index']);
    Route::post('users', [UserController::class, 'store']);
    Route::patch('users/{user}', [UserController::class, 'update']);
    Route::delete('users/{user}', [UserController::class, 'destroy']);
    Route::post('users/import', [UserController::class, 'import']);

    Route::apiResource('reports', ReportController::class);
    Route::post('reports/{report}/items', [ReportController::class, 'storeItem']);
    Route::patch('reports/{report}/items/{item}', [ReportController::class, 'updateItem']);
    Route::delete('reports/{report}/items/{item}', [ReportController::class, 'destroyItem']);
    Route::post('reports/{report}/items/{item}/photos', [ReportController::class, 'uploadPhoto']);
    Route::delete('reports/{report}/items/{item}/photos/{photo}', [ReportController::class, 'deletePhoto']);

    Route::get('reports/{report}/pdf', [ReportPdfController::class, 'download']);
    Route::get('reports/{report}/preview', [ReportPdfController::class, 'preview']);
});
