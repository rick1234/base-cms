<?php

use App\Http\Controllers\Frontend\ContentPreviewController;
use App\Http\Controllers\Frontend\DownloadController;
use App\Http\Controllers\Frontend\FormSubmissionController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\RedirectController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::redirect('/login', '/admin/login')->name('login');

Route::post('/locale/{locale}', LocaleController::class)
    ->where('locale', '[A-Za-z]{2}(?:[-_][A-Za-z]{2})?')
    ->name('locale.update');

Route::get('/', HomeController::class)->name('frontend.home');
Route::get('/{locale}', HomeController::class)
    ->where('locale', '[A-Za-z]{2}(?:[-_][A-Za-z]{2})?')
    ->name('frontend.locale.home');

Route::post('/forms/{form:slug}', [FormSubmissionController::class, 'store'])
    ->name('frontend.forms.submit');

Route::get('/preview/content/{token}', [ContentPreviewController::class, 'show'])
    ->where('token', '[A-Fa-f0-9]{64}')
    ->name('frontend.content.preview');

Route::get('/downloads/file/{token}', [DownloadController::class, 'file'])
    ->where('token', '[A-Za-z0-9]+')
    ->name('frontend.downloads.file');
Route::get('/downloads/{download}', [DownloadController::class, 'show'])
    ->where('download', '[A-Za-z0-9-]+')
    ->name('frontend.downloads.show');
Route::post('/downloads/{download}', [DownloadController::class, 'unlock'])
    ->where('download', '[A-Za-z0-9-]+')
    ->name('frontend.downloads.unlock');

Route::get('/{slug}', PageController::class)
    ->where('slug', '^(?!admin$|api$|up$)[A-Za-z0-9-]+$')
    ->name('frontend.pages.show');
Route::get('/{locale}/{slug}', PageController::class)
    ->where('locale', '[A-Za-z]{2}(?:[-_][A-Za-z]{2})?')
    ->where('slug', '[A-Za-z0-9-]+')
    ->name('frontend.locale.pages.show');

Route::fallback(RedirectController::class)->name('frontend.redirects.fallback');
