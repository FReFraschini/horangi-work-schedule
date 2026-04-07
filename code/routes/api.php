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
use App\Http\Controllers\Api\Operator\AbsenceController as OperatorAbsenceController;
use App\Http\Controllers\Api\Operator\ScheduleController as OperatorScheduleController;

Route::middleware('auth:sanctum')->group(function () {
    // Admin Routes
    Route::apiResource('users', UserController::class);
    Route::apiResource('shifts', ShiftController::class);
    Route::apiResource('unavailability-requests', UnavailabilityRequestController::class)->only([
        'index', 'show', 'update'
    ]);
    Route::post('unavailability-requests/{unavailability_request}/update-status', [UnavailabilityRequestController::class, 'updateStatus']);
    Route::get('dashboard/totals', [DashboardController::class, 'totals']);
    Route::apiResource('absences', AbsenceController::class)->only(['index', 'store', 'update', 'destroy']);

    // Operator Routes
    Route::prefix('operator')->group(function () {
        Route::get('shifts', [OperatorShiftController::class, 'index']);
        Route::get('absences', [OperatorAbsenceController::class, 'index']);
        Route::get('schedule/shifts', [OperatorScheduleController::class, 'shifts']);
        Route::get('schedule/absences', [OperatorScheduleController::class, 'absences']);
        Route::get('schedule/team', [OperatorScheduleController::class, 'team']);
        Route::get('unavailability-requests', [OperatorUnavailabilityRequestController::class, 'index']);
        Route::post('unavailability-requests', [OperatorUnavailabilityRequestController::class, 'store']);
    });

    Route::delete('operator/unavailability-requests/{id}', [OperatorUnavailabilityRequestController::class, 'destroy']);
    Route::patch('operator/unavailability-requests/{id}/archive', [OperatorUnavailabilityRequestController::class, 'archive']);
});
