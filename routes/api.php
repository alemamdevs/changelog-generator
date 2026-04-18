<?php

declare(strict_types=1);

use App\Http\Controllers\GitWebhookController;
use Illuminate\Support\Facades\Route;

/**
 * API routes for the application.
 */
Route::post('webhooks/push', [GitWebhookController::class, 'handle'])->name('api.webhooks.push');
Route::post('github/webhooks/push', [GitWebhookController::class, 'handle'])->name('api.github.webhooks.push');
Route::post('gitlab/webhooks/push', [GitWebhookController::class, 'handle'])->name('api.gitlab.webhooks.push');
Route::post('git/webhook', [GitWebhookController::class, 'handle']);
