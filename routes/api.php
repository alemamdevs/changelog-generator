<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\GitWebhookController;
use Illuminate\Support\Facades\Route;

/**
 * API routes for the application.
 */
Route::prefix('auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register'])->name('api.auth.register');
    Route::post('login', [AuthController::class, 'login'])->name('api.auth.login');
});

Route::post('webhooks/push', [GitWebhookController::class, 'handle'])->name('api.webhooks.push');
Route::post('github/webhooks/push', [GitWebhookController::class, 'handle'])->name('api.github.webhooks.push');
Route::post('gitlab/webhooks/push', [GitWebhookController::class, 'handle'])->name('api.gitlab.webhooks.push');
Route::post('git/webhook', [GitWebhookController::class, 'handle'])->name('api.git.webhook');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::get('me', [AuthController::class, 'me'])->name('api.auth.me');
        Route::post('logout', [AuthController::class, 'logout'])->name('api.auth.logout');
    });

    Route::prefix('projects')->name('api.projects.')->group(function (): void {
        Route::get('/', [ProjectController::class, 'index'])->name('index');
        Route::post('/', [ProjectController::class, 'store'])->name('store');
        Route::get('{project}', [ProjectController::class, 'show'])->name('show');
    });
});
