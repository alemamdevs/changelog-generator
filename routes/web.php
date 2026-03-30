<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ReleaseController;

Route::get('/', function () {
    return view('welcome');
});

// Admin releases dashboard
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('releases', [ReleaseController::class, 'index'])->name('releases.index');
    Route::get('releases/{release}', [ReleaseController::class, 'show'])->name('releases.show');
});
