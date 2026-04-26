<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ReleaseController;
use App\Http\Controllers\Admin\WebhookConfigurationController;
use App\Http\Controllers\Admin\WebhookDeliveryController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin.projects.index')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthController::class, 'createLogin'])->name('login');
    Route::post('login', [AuthController::class, 'storeLogin'])->name('login.store');

    Route::get('register', [AuthController::class, 'createRegister'])->name('register');
    Route::post('register', [AuthController::class, 'storeRegister'])->name('register.store');
});

Route::post('logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::get('dashboard', function () {
    return redirect()->route('admin.projects.index');
})->middleware('auth')->name('dashboard');

// Admin dashboard (private)
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('projects/{project}', [ProjectController::class, 'show'])->name('projects.show');

    Route::get('releases', [ReleaseController::class, 'index'])->name('releases.index');
    Route::get('releases/{release}', [ReleaseController::class, 'show'])->name('releases.show');
    Route::get('webhooks/configuration', [WebhookConfigurationController::class, 'index'])->name('webhooks.configuration');
    Route::post('webhooks/configuration/generate-secrets', [WebhookConfigurationController::class, 'generateMissingSecrets'])->name('webhooks.configuration.generate-secrets');
    Route::get('webhooks', [WebhookDeliveryController::class, 'index'])->name('webhooks.index');
    Route::get('webhooks/{webhookDelivery}', [WebhookDeliveryController::class, 'show'])->name('webhooks.show');
});
