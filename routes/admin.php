<?php

use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\WmsModuleController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('login', [AdminLoginController::class, 'create'])->name('login');
        Route::post('login', [AdminLoginController::class, 'store'])->name('login.store');
    });

    Route::middleware(['auth', 'can:access-admin'])->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::post('logout', [AdminLoginController::class, 'destroy'])->name('logout');
        Route::get('wms', [WmsModuleController::class, 'index'])->name('wms.index');
        Route::get('wms/{module}/create', [WmsModuleController::class, 'create'])->name('wms.records.create');
        Route::post('wms/{module}', [WmsModuleController::class, 'store'])->name('wms.records.store');
        Route::get('wms/{module}/{record}/edit', [WmsModuleController::class, 'edit'])->name('wms.records.edit');
        Route::put('wms/{module}/{record}', [WmsModuleController::class, 'update'])->name('wms.records.update');
        Route::delete('wms/{module}/{record}', [WmsModuleController::class, 'destroy'])->name('wms.records.destroy');
        Route::get('wms/{module}', [WmsModuleController::class, 'show'])->name('wms.show');

        Route::resource('pages', PageController::class)
            ->except(['show', 'destroy'])
            ->parameters(['pages' => 'page']);
    });
});
