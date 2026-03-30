<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GitWebhookController;

/**
 * API routes for the application.
 */
Route::post('git/webhook', [GitWebhookController::class, 'handle']);
