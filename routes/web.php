<?php

use App\Http\Controllers\Frontend\ContentPreviewController;
use App\Http\Controllers\Frontend\DomainAssetController;
use App\Http\Controllers\Frontend\DomainPreviewController;
use App\Http\Controllers\Frontend\DownloadController;
use App\Http\Controllers\Frontend\EventController;
use App\Http\Controllers\Frontend\FormController;
use App\Http\Controllers\Frontend\FormSubmissionController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\RedirectController;
use App\Http\Controllers\Frontend\SearchController;
use App\Http\Controllers\Frontend\VacancyController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::redirect('/login', '/admin/login')->name('login');

Route::post('/locale/{locale}', LocaleController::class)
    ->where('locale', '[A-Za-z]{2}(?:[-_][A-Za-z]{2})?')
    ->name('locale.update');

Route::get('/__domain/theme.css', [DomainAssetController::class, 'theme'])->name('frontend.domains.theme');
Route::post('/__domain-preview/{domain}', [DomainPreviewController::class, 'switch'])
    ->whereNumber('domain')
    ->name('frontend.domains.preview');
Route::delete('/__domain-preview', [DomainPreviewController::class, 'clear'])
    ->name('frontend.domains.preview.clear');

Route::get('/', HomeController::class)->name('frontend.home');
Route::get('/{locale}', HomeController::class)
    ->where('locale', '[A-Za-z]{2}(?:[-_][A-Za-z]{2})?')
    ->name('frontend.locale.home');

Route::get('/forms', [FormController::class, 'index'])
    ->name('frontend.forms.index');
Route::get('/forms/{form}', [FormController::class, 'show'])
    ->where('form', '[A-Za-z0-9-]+')
    ->name('frontend.forms.show');
Route::get('/{locale}/forms', [FormController::class, 'index'])
    ->where('locale', '[A-Za-z]{2}(?:[-_][A-Za-z]{2})?')
    ->name('frontend.locale.forms.index');
Route::get('/{locale}/forms/{form}', [FormController::class, 'show'])
    ->where('locale', '[A-Za-z]{2}(?:[-_][A-Za-z]{2})?')
    ->where('form', '[A-Za-z0-9-]+')
    ->name('frontend.locale.forms.show');
Route::post('/forms/{form}', [FormSubmissionController::class, 'store'])
    ->where('form', '[A-Za-z0-9-]+')
    ->name('frontend.forms.submit');
Route::post('/{locale}/forms/{form}', [FormSubmissionController::class, 'store'])
    ->where('locale', '[A-Za-z]{2}(?:[-_][A-Za-z]{2})?')
    ->where('form', '[A-Za-z0-9-]+')
    ->name('frontend.locale.forms.submit');

Route::get('/preview/content/{token}', [ContentPreviewController::class, 'show'])
    ->where('token', '[A-Fa-f0-9]{64}')
    ->name('frontend.content.preview');

Route::get('/search', SearchController::class)
    ->name('frontend.search');

Route::get('/downloads/file/{token}', [DownloadController::class, 'file'])
    ->where('token', '[A-Za-z0-9]+')
    ->name('frontend.downloads.file');
Route::get('/downloads/{download}', [DownloadController::class, 'show'])
    ->where('download', '[A-Za-z0-9-]+')
    ->name('frontend.downloads.show');
Route::post('/downloads/{download}', [DownloadController::class, 'unlock'])
    ->where('download', '[A-Za-z0-9-]+')
    ->name('frontend.downloads.unlock');

Route::get('/events', [EventController::class, 'index'])
    ->name('frontend.events.index');
Route::get('/events/{event}', [EventController::class, 'show'])
    ->where('event', '[A-Za-z0-9-]+')
    ->name('frontend.events.show');
Route::get('/{locale}/events', [EventController::class, 'index'])
    ->where('locale', '[A-Za-z]{2}(?:[-_][A-Za-z]{2})?')
    ->name('frontend.locale.events.index');
Route::get('/{locale}/events/{event}', [EventController::class, 'show'])
    ->where('locale', '[A-Za-z]{2}(?:[-_][A-Za-z]{2})?')
    ->where('event', '[A-Za-z0-9-]+')
    ->name('frontend.locale.events.show');

Route::get('/vacancies', [VacancyController::class, 'index'])
    ->name('frontend.vacancies.index');
Route::get('/vacancies/{vacancy}', [VacancyController::class, 'show'])
    ->where('vacancy', '[A-Za-z0-9-]+')
    ->name('frontend.vacancies.show');
Route::get('/{locale}/vacancies', [VacancyController::class, 'index'])
    ->where('locale', '[A-Za-z]{2}(?:[-_][A-Za-z]{2})?')
    ->name('frontend.locale.vacancies.index');
Route::get('/{locale}/vacancies/{vacancy}', [VacancyController::class, 'show'])
    ->where('locale', '[A-Za-z]{2}(?:[-_][A-Za-z]{2})?')
    ->where('vacancy', '[A-Za-z0-9-]+')
    ->name('frontend.locale.vacancies.show');

Route::get('/{slug}', PageController::class)
    ->where('slug', '^(?!admin$|api$|up$)[A-Za-z0-9-]+$')
    ->name('frontend.pages.show');
Route::get('/{locale}/{slug}', PageController::class)
    ->where('locale', '[A-Za-z]{2}(?:[-_][A-Za-z]{2})?')
    ->where('slug', '[A-Za-z0-9-]+')
    ->name('frontend.locale.pages.show');

Route::fallback(RedirectController::class)->name('frontend.redirects.fallback');
