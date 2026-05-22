<?php

use App\Http\Controllers\Api\V1\PageController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware('throttle:60,1')
    ->group(function (): void {
        Route::get('pages', [PageController::class, 'index'])->name('pages.index');
        Route::get('pages/{page:slug}', [PageController::class, 'show'])->name('pages.show');
    });
