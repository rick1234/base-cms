<?php

use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\WmsModuleController;
use Illuminate\Support\Facades\Route;

Route::prefix('wms')->name('wms.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('login', [AdminLoginController::class, 'create'])->name('login');
        Route::post('login', [AdminLoginController::class, 'store'])->name('login.store');
        Route::get('index.php', fn () => redirect()->route('wms.login'));
    });

    Route::middleware(['auth', 'can:access-admin'])->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('dashboard.php', DashboardController::class)->name('dashboard.legacy');
        Route::match(['get', 'post'], 'delete.php', [WmsModuleController::class, 'legacyDelete'])->name('delete');
        Route::get('{page}.php', [WmsModuleController::class, 'legacyRootPage'])
            ->where('page', 'firstLogin|resetpass|token')
            ->name('root-page');

        Route::get('index.php', [WmsModuleController::class, 'index'])->name('index');

        Route::get('{modulePath}/index.php', [WmsModuleController::class, 'legacyIndex'])
            ->where('modulePath', '.*')
            ->name('modules.index');
        Route::post('{modulePath}/index.php', [WmsModuleController::class, 'store'])
            ->where('modulePath', '.*')
            ->name('modules.store');

        Route::get('{modulePath}/create', [WmsModuleController::class, 'legacyCreate'])
            ->where('modulePath', '.*')
            ->name('modules.create');
        Route::get('{modulePath}/edit.php', [WmsModuleController::class, 'legacyEdit'])
            ->where('modulePath', '.*')
            ->name('modules.legacy-edit');
        Route::post('{modulePath}/edit.php', [WmsModuleController::class, 'legacySave'])
            ->where('modulePath', '.*')
            ->name('modules.legacy-save');

        Route::get('{modulePath}/{page}.php', [WmsModuleController::class, 'legacyPage'])
            ->where('modulePath', '.*')
            ->where('page', '[A-Za-z0-9_-]+')
            ->name('modules.page');
        Route::post('{modulePath}/{page}.php', [WmsModuleController::class, 'legacyPageSave'])
            ->where('modulePath', '.*')
            ->where('page', '[A-Za-z0-9_-]+')
            ->name('modules.page-save');

        Route::get('{modulePath}/{record}/edit', [WmsModuleController::class, 'edit'])
            ->where('modulePath', '.*')
            ->whereNumber('record')
            ->name('modules.edit');
        Route::put('{modulePath}/{record}', [WmsModuleController::class, 'update'])
            ->where('modulePath', '.*')
            ->whereNumber('record')
            ->name('modules.update');
        Route::delete('{modulePath}/{record}', [WmsModuleController::class, 'destroy'])
            ->where('modulePath', '.*')
            ->whereNumber('record')
            ->name('modules.destroy');

        Route::get('{modulePath}', [WmsModuleController::class, 'legacyIndex'])
            ->where('modulePath', '.*')
            ->name('modules.show');
    });
});
