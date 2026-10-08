<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Parking\FavoriteParkingController;
use App\Http\Controllers\Api\V1\Parking\ListFavoritesController;
use App\Http\Controllers\Api\V1\Parking\NearbyParkingController;
use App\Http\Controllers\Api\V1\Parking\ParkingAvailabilityHistoryController;
use App\Http\Controllers\Api\V1\Parking\ParkingDetailController;
use App\Http\Controllers\Api\V1\Parking\ParkingIndexController;
use App\Http\Controllers\Api\V1\Parking\ReportParkingAvailabilityController;
use App\Http\Controllers\Api\V1\Parking\UnfavoriteParkingController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    /**
     * Register
     *
     * Creates a new user account.
     *
     * @response 201 {
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com"
     *     },
     *     "token": "1|example-token"
     *   }
     * }
     */
    Route::post(
        'auth/register',
        [AuthController::class, 'register'],
    );

    /**
     * Login
     *
     * Authenticates a user and returns an access token.
     */
    Route::post(
        'auth/login',
        [AuthController::class, 'login'],
    );

    Route::middleware('auth:sanctum')->group(function () {

        /**
         * Get authenticated user
         *
         * Returns the currently authenticated user.
         */
        Route::get(
            'auth/me',
            [AuthController::class, 'me'],
        );

        /**
         * Logout
         *
         * Revokes the authenticated user's current access token.
         */
        Route::post(
            'auth/logout',
            [AuthController::class, 'logout'],
        );

        /**
         * List favorite parking
         *
         * Returns the parking locations saved as favorites by the
         * authenticated user.
         */
        Route::get(
            'favorites',
            ListFavoritesController::class,
        );

        /**
         * Add favorite parking
         *
         * Adds a parking location to the authenticated user's favorites.
         */
        Route::post(
            'favorites/{parking}',
            FavoriteParkingController::class,
        );

        /**
         * Remove favorite parking
         *
         * Removes a parking location from the authenticated user's favorites.
         */
        Route::delete(
            'favorites/{parking}',
            UnfavoriteParkingController::class,
        );

        /**
         * Report parking availability
         *
         * Reports the current availability of a parking location.
         */
        Route::post(
            'parking/{parking}/availability',
            ReportParkingAvailabilityController::class,
        );
    });

    /**
     * Get parking availability history
     *
     * Returns the availability history reported for a parking location.
     */
    Route::get(
        'parking/{parking}/availability/history',
        ParkingAvailabilityHistoryController::class,
    );

    /**
     * List parking
     *
     * Returns a list of parking locations.
     */
    Route::get(
        'parking',
        ParkingIndexController::class,
    );

    /**
     * Find nearby parking
     *
     * Returns parking locations near the specified coordinates.
     */
    Route::get(
        'parking/nearby',
        NearbyParkingController::class,
    );

    /**
     * Get parking details
     *
     * Returns detailed information about a parking location.
     */
    Route::get(
        'parking/{parking}',
        ParkingDetailController::class,
    );
});