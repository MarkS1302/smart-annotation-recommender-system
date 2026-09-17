<?php

use App\Http\Controllers\Api\AnnotationRecommendationController;
use App\Http\Controllers\Api\ApiLoginController;
use App\Http\Controllers\Api\ShowAnnotationRecommendationController;
use Illuminate\Support\Facades\Route;

Route::post('login', ApiLoginController::class)
    ->middleware('throttle:api-login')
    ->name('api.login');

Route::middleware('auth:sanctum')
    ->prefix('annotations/recommendations')
    ->name('api.annotations.recommendations.')
    ->group(function (): void {
        Route::post('/', AnnotationRecommendationController::class)->name('store');
        Route::get('{requestId}', ShowAnnotationRecommendationController::class)->name('show');
    });
