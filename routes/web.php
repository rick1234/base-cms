<?php

use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\RedirectController;
use Illuminate\Support\Facades\Route;

Route::redirect('/login', '/admin/login')->name('login');

Route::get('/', HomeController::class)->name('frontend.home');

Route::get('/{slug}', PageController::class)
    ->where('slug', '^(?!admin$|api$|up$)[A-Za-z0-9-]+$')
    ->name('frontend.pages.show');

Route::fallback(RedirectController::class)->name('frontend.redirects.fallback');
