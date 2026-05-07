<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
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
