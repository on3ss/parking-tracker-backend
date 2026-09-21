<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Parking\NearbyParkingController;
use App\Http\Controllers\Api\V1\Parking\ParkingDetailController;
use App\Http\Controllers\Api\V1\Parking\ReportParkingAvailabilityController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::post(
        'auth/register',
        [AuthController::class, 'register'],
    );

    Route::post(
        'auth/login',
        [AuthController::class, 'login'],
    );

    Route::middleware('auth:sanctum')->group(function () {

        Route::get(
            'auth/me',
            [AuthController::class, 'me'],
        );

        Route::post(
            'auth/logout',
            [AuthController::class, 'logout'],
        );
    });

    Route::get(
        '/parking/nearby',
        NearbyParkingController::class,
    );

    Route::get(
        '/parking/{parking}',
        ParkingDetailController::class,
    );

    Route::post(
        '/parking/{parking}/availability',
        ReportParkingAvailabilityController::class,
    );
});
