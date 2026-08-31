<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmailReviewController;
use App\Http\Controllers\Api\FlightSegmentController;
use App\Http\Controllers\Api\ForwardingAddressController;
use App\Http\Controllers\Api\LoungeController;
use App\Http\Controllers\Api\OAuthConnectionController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\TripController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });

    Route::prefix('oauth/{provider}')->group(function () {
        Route::middleware('auth:sanctum')->get('connect', [OAuthConnectionController::class, 'connect']);
        Route::get('callback', [OAuthConnectionController::class, 'callback']);
    });
});

Route::middleware('auth:sanctum')->delete('auth/oauth/{provider}', [OAuthConnectionController::class, 'destroy']);

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('trips', TripController::class);

    Route::patch('segments/{segment}', [FlightSegmentController::class, 'update']);

    Route::get('emails/pending-review', [EmailReviewController::class, 'index']);

    Route::get('stats/lifetime', [StatsController::class, 'lifetime']);
    Route::get('stats/recap', [StatsController::class, 'recap']);

    Route::get('lounges', [LoungeController::class, 'index']);

    Route::get('me/forwarding-address', [ForwardingAddressController::class, 'show']);
});
