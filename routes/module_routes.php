<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // Module Installation
    Route::get('/modules/install-partnership', [App\Http\Controllers\Admin\ModuleController::class, 'showInstallPartnership'])
                    ->name('admin.modules.install-partnership');
    Route::post('/modules/install-partnership', [App\Http\Controllers\Admin\ModuleController::class, 'installPartnership'])
                    ->name('admin.modules.install-partnership.submit');
    Route::post('/partnership/uninstall', [App\Http\Controllers\Admin\ModuleController::class, 'uninstall'])->name('admin.modules.uninstall-partnership.submit');
    Route::post('/partnership/activate', [App\Http\Controllers\Admin\ModuleController::class, 'activate'])->name('admin.modules.activate-partnership.submit');
    Route::post('/partnership/deactivate', [App\Http\Controllers\Admin\ModuleController::class, 'deactivate'])->name('admin.modules.deactivate-partnership.submit');
});
