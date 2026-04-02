<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ShiftController;
use App\Http\Controllers\Api\UnavailabilityRequestController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AbsenceController;
use App\Http\Controllers\Api\Operator\ShiftController as OperatorShiftController;
use App\Http\Controllers\Api\Operator\UnavailabilityRequestController as OperatorUnavailabilityRequestController;

Route::middleware('auth:sanctum')->group(function () {
    // Admin Routes
    Route::apiResource('users', UserController::class);
    Route::apiResource('shifts', ShiftController::class);
    Route::apiResource('unavailability-requests', UnavailabilityRequestController::class)->only([
        'index', 'show', 'update'
    ]);
    Route::post('unavailability-requests/{unavailability_request}/update-status', [UnavailabilityRequestController::class, 'updateStatus']);
    Route::get('dashboard/totals', [DashboardController::class, 'totals']);
    Route::apiResource('absences', AbsenceController::class)->only(['index', 'store', 'destroy']);

    // Operator Routes
    Route::prefix('operator')->group(function () {
        Route::get('shifts', [OperatorShiftController::class, 'index']);
        Route::apiResource('unavailability-requests', OperatorUnavailabilityRequestController::class)->only(['index', 'store']);
    });
});
