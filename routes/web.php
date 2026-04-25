<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ReleaseController;
use App\Http\Controllers\Admin\WebhookConfigurationController;
use App\Http\Controllers\Admin\WebhookDeliveryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.projects.index');
});

Route::view('login', 'welcome')->name('login');
Route::view('register', 'welcome')->name('register');

// Admin dashboard (private)
Route::middleware('auth:sanctum')->prefix('admin')->name('admin.')->group(function () {
    Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('projects/{project}', [ProjectController::class, 'show'])->name('projects.show');

    Route::get('releases', [ReleaseController::class, 'index'])->name('releases.index');
    Route::get('releases/{release}', [ReleaseController::class, 'show'])->name('releases.show');
    Route::get('webhooks/configuration', [WebhookConfigurationController::class, 'index'])->name('webhooks.configuration');
    Route::get('webhooks', [WebhookDeliveryController::class, 'index'])->name('webhooks.index');
    Route::get('webhooks/{webhookDelivery}', [WebhookDeliveryController::class, 'show'])->name('webhooks.show');
});
