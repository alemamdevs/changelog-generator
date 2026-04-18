<?php

use App\Http\Controllers\Admin\ReleaseController;
use App\Http\Controllers\Admin\WebhookConfigurationController;
use App\Http\Controllers\Admin\WebhookDeliveryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.releases.index');
});

// Admin releases dashboard
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('releases', [ReleaseController::class, 'index'])->name('releases.index');
    Route::get('releases/{release}', [ReleaseController::class, 'show'])->name('releases.show');
    Route::get('webhooks/configuration', [WebhookConfigurationController::class, 'index'])->name('webhooks.configuration');
    Route::get('webhooks', [WebhookDeliveryController::class, 'index'])->name('webhooks.index');
    Route::get('webhooks/{webhookDelivery}', [WebhookDeliveryController::class, 'show'])->name('webhooks.show');
});
