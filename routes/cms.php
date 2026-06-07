<?php

use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\Banners\BannerCategoryController;
use App\Http\Controllers\Admin\Banners\BannerController;
use App\Http\Controllers\Admin\Catalog\CatalogBrandController;
use App\Http\Controllers\Admin\Catalog\CatalogCategoryController;
use App\Http\Controllers\Admin\Catalog\CatalogCouponController;
use App\Http\Controllers\Admin\Catalog\CatalogProductController;
use App\Http\Controllers\Admin\Catalog\CatalogPromotionController;
use App\Http\Controllers\Admin\Catalog\CatalogReviewController;
use App\Http\Controllers\Admin\CmsModuleController;
use App\Http\Controllers\Admin\Content\ContentCategoryController;
use App\Http\Controllers\Admin\Content\ContentItemController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Downloads\DownloadCategoryController;
use App\Http\Controllers\Admin\Downloads\DownloadController;
use App\Http\Controllers\Admin\Events\EventCategoryController;
use App\Http\Controllers\Admin\Events\EventController;
use App\Http\Controllers\Admin\Faq\FaqCategoryController;
use App\Http\Controllers\Admin\Faq\FaqItemController;
use App\Http\Controllers\Admin\Forms\FormCategoryController;
use App\Http\Controllers\Admin\Forms\FormController;
use App\Http\Controllers\Admin\Localization\CountryController;
use App\Http\Controllers\Admin\Localization\LanguageController;
use App\Http\Controllers\Admin\Locations\LocationCategoryController;
use App\Http\Controllers\Admin\Locations\LocationController;
use App\Http\Controllers\Admin\QuickStatusController;
use App\Http\Controllers\Admin\Redirects\RedirectController;
use App\Http\Controllers\Admin\Roles\RoleController;
use App\Http\Controllers\Admin\Translations\TranslationController;
use App\Http\Controllers\Admin\Users\UserCategoryController;
use App\Http\Controllers\Admin\Users\UserController;
use App\Http\Controllers\Admin\Vacancies\VacancyCategoryController;
use App\Http\Controllers\Admin\Vacancies\VacancyController;
use Illuminate\Support\Facades\Route;

Route::prefix('cms')->name('cms.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('login', [AdminLoginController::class, 'create'])->name('login');
        Route::post('login', [AdminLoginController::class, 'store'])->name('login.store');
        Route::get('index.php', fn () => redirect()->route('cms.login'));
    });

    Route::middleware(['auth', 'can:access-admin'])->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('dashboard.php', DashboardController::class)->name('dashboard.legacy');
        Route::patch('quick-status', QuickStatusController::class)->name('quick-status.update');
        Route::match(['get', 'post'], 'delete.php', [CmsModuleController::class, 'legacyDelete'])->name('delete');
        Route::get('{page}.php', [CmsModuleController::class, 'legacyRootPage'])
            ->where('page', 'firstLogin|resetpass|token')
            ->name('root-page');

        Route::get('index.php', [CmsModuleController::class, 'index'])->name('index');

        Route::prefix('banner')->name('banners.')->group(function (): void {
            Route::get('index.php', [BannerController::class, 'index'])->name('index');
            Route::post('index.php', [BannerController::class, 'store'])->name('store');
            Route::get('edit.php', [BannerController::class, 'edit'])->name('edit');
            Route::post('edit.php', [BannerController::class, 'save'])->name('save');
            Route::post('{id}/images', [BannerController::class, 'uploadImages'])->whereNumber('id')->name('images.upload');
            Route::get('bulkUploader.php', [BannerController::class, 'bulkUploader'])->name('bulk');
            Route::post('bulkUploader.php', [BannerController::class, 'uploadBulk'])->name('bulk.upload');
            Route::post('ajax/duplicateItem.php', [BannerController::class, 'duplicate'])->name('duplicate');
            Route::post('ajax/deleteAfbeelding.php', [BannerController::class, 'deleteImage'])->name('image.delete');
            Route::post('ajax/uploadBulkBanner.php', [BannerController::class, 'uploadBulk'])->name('bulk.ajax-upload');

            Route::prefix('categorieen')->name('categories.')->group(function (): void {
                Route::get('index.php', [BannerCategoryController::class, 'index'])->name('index');
                Route::post('index.php', [BannerCategoryController::class, 'store'])->name('store');
                Route::get('edit.php', [BannerCategoryController::class, 'edit'])->name('edit');
                Route::post('edit.php', [BannerCategoryController::class, 'save'])->name('save');
            });

            Route::delete('categorieen/{bannerCategory}', [BannerCategoryController::class, 'destroy'])
                ->whereNumber('bannerCategory')
                ->name('categories.destroy');
            Route::put('{banner}', [BannerController::class, 'update'])
                ->whereNumber('banner')
                ->name('update');
            Route::delete('{banner}', [BannerController::class, 'destroy'])
                ->whereNumber('banner')
                ->name('destroy');
        });

        Route::prefix('form')->name('forms.')->group(function (): void {
            Route::get('index.php', [FormController::class, 'index'])->name('index');
            Route::post('index.php', [FormController::class, 'store'])->name('store');
            Route::get('edit.php', [FormController::class, 'edit'])->name('edit');
            Route::post('edit.php', [FormController::class, 'save'])->name('save');
            Route::post('builder.php', [FormController::class, 'saveBuilder'])->name('builder.save');
            Route::get('editMessages.php', [FormController::class, 'submissions'])->name('submissions');
            Route::post('ajax/duplicateItem.php', [FormController::class, 'duplicate'])->name('duplicate');

            Route::prefix('categorieen')->name('categories.')->group(function (): void {
                Route::get('index.php', [FormCategoryController::class, 'index'])->name('index');
                Route::post('index.php', [FormCategoryController::class, 'store'])->name('store');
                Route::get('edit.php', [FormCategoryController::class, 'edit'])->name('edit');
                Route::post('edit.php', [FormCategoryController::class, 'save'])->name('save');
            });

            Route::delete('categorieen/{formCategory}', [FormCategoryController::class, 'destroy'])
                ->whereNumber('formCategory')
                ->name('categories.destroy');
            Route::put('{form}', [FormController::class, 'update'])
                ->whereNumber('form')
                ->name('update');
            Route::delete('{form}', [FormController::class, 'destroy'])
                ->whereNumber('form')
                ->name('destroy');
        });

        Route::prefix('catalogus')->name('catalog.')->group(function (): void {
            Route::get('index.php', [CatalogProductController::class, 'index'])->name('index');
            Route::post('index.php', [CatalogProductController::class, 'store'])->name('store');
            Route::get('edit.php', [CatalogProductController::class, 'edit'])->name('edit');
            Route::post('edit.php', [CatalogProductController::class, 'save'])->name('save');

            Route::get('editAfbeeldingen.php', [CatalogProductController::class, 'images'])->name('images');
            Route::post('editAfbeeldingen.php', [CatalogProductController::class, 'uploadImage'])->name('images.upload');
            Route::get('editOptions.php', [CatalogProductController::class, 'options'])->name('options');
            Route::post('editOptions.php', [CatalogProductController::class, 'saveOptions'])->name('options.save');
            Route::get('editVertalingen.php', [CatalogProductController::class, 'translations'])->name('translations');
            Route::post('editVertalingen.php', [CatalogProductController::class, 'saveTranslations'])->name('translations.save');
            Route::get('editVideo.php', [CatalogProductController::class, 'videos'])->name('videos');
            Route::post('editVideo.php', [CatalogProductController::class, 'saveVideos'])->name('videos.save');
            Route::get('editVoorraad.php', [CatalogProductController::class, 'stock'])->name('stock');
            Route::post('editVoorraad.php', [CatalogProductController::class, 'saveStock'])->name('stock.save');
            Route::get('editCombinaties.php', [CatalogProductController::class, 'combinations'])->name('combinations');
            Route::post('editCombinaties.php', [CatalogProductController::class, 'saveCombinations'])->name('combinations.save');
            Route::get('resetSortIndex.php', [CatalogProductController::class, 'resetSortIndex'])->name('reset-sort');

            Route::post('ajax/duplicateItem.php', [CatalogProductController::class, 'duplicate'])->name('duplicate');
            Route::post('ajax/deleteAfbeelding.php', [CatalogProductController::class, 'deleteImage'])->name('image.delete');
            Route::post('ajax/updateAfbeeldingnaam.php', [CatalogProductController::class, 'updateImageName'])->name('image.update-name');
            Route::post('ajax/updateSortIndex.php', [CatalogProductController::class, 'updateImageSort'])->name('image.update-sort');
            Route::post('ajax/uploadAfbeelding.php', [CatalogProductController::class, 'uploadImage'])->name('image.upload');

            Route::prefix('categorieen')->name('categories.')->group(function (): void {
                Route::get('index.php', [CatalogCategoryController::class, 'index'])->name('index');
                Route::post('index.php', [CatalogCategoryController::class, 'store'])->name('store');
                Route::get('edit.php', [CatalogCategoryController::class, 'edit'])->name('edit');
                Route::post('edit.php', [CatalogCategoryController::class, 'save'])->name('save');
            });

            Route::prefix('merken')->name('brands.')->group(function (): void {
                Route::get('index.php', [CatalogBrandController::class, 'index'])->name('index');
                Route::post('index.php', [CatalogBrandController::class, 'store'])->name('store');
                Route::get('edit.php', [CatalogBrandController::class, 'edit'])->name('edit');
                Route::post('edit.php', [CatalogBrandController::class, 'save'])->name('save');
                Route::delete('{record}', [CatalogBrandController::class, 'destroy'])->whereNumber('record')->name('destroy');
            });

            Route::prefix('promotie')->name('promotions.')->group(function (): void {
                Route::get('index.php', [CatalogPromotionController::class, 'index'])->name('index');
                Route::post('index.php', [CatalogPromotionController::class, 'store'])->name('store');
                Route::get('edit.php', [CatalogPromotionController::class, 'edit'])->name('edit');
                Route::post('edit.php', [CatalogPromotionController::class, 'save'])->name('save');
                Route::delete('{record}', [CatalogPromotionController::class, 'destroy'])->whereNumber('record')->name('destroy');
            });

            Route::prefix('actiecodes')->name('coupons.')->group(function (): void {
                Route::get('index.php', [CatalogCouponController::class, 'index'])->name('index');
                Route::post('index.php', [CatalogCouponController::class, 'store'])->name('store');
                Route::get('edit.php', [CatalogCouponController::class, 'edit'])->name('edit');
                Route::post('edit.php', [CatalogCouponController::class, 'save'])->name('save');
                Route::delete('{catalogCoupon}', [CatalogCouponController::class, 'destroy'])->whereNumber('catalogCoupon')->name('destroy');
            });

            Route::prefix('review')->name('reviews.')->group(function (): void {
                Route::get('index.php', [CatalogReviewController::class, 'index'])->name('index');
                Route::post('index.php', [CatalogReviewController::class, 'store'])->name('store');
                Route::get('edit.php', [CatalogReviewController::class, 'edit'])->name('edit');
                Route::post('edit.php', [CatalogReviewController::class, 'save'])->name('save');
                Route::delete('{catalogReview}', [CatalogReviewController::class, 'destroy'])->whereNumber('catalogReview')->name('destroy');
            });

            Route::delete('categorieen/{catalogCategory}', [CatalogCategoryController::class, 'destroy'])
                ->whereNumber('catalogCategory')
                ->name('categories.destroy');
            Route::put('{catalogProduct}', [CatalogProductController::class, 'update'])
                ->whereNumber('catalogProduct')
                ->name('update');
            Route::delete('{catalogProduct}', [CatalogProductController::class, 'destroy'])
                ->whereNumber('catalogProduct')
                ->name('destroy');
        });

        Route::prefix('faq')->name('faq.')->group(function (): void {
            Route::get('index.php', [FaqItemController::class, 'index'])->name('index');
            Route::post('index.php', [FaqItemController::class, 'store'])->name('store');
            Route::get('edit.php', [FaqItemController::class, 'edit'])->name('edit');
            Route::post('edit.php', [FaqItemController::class, 'save'])->name('save');

            Route::post('ajax/duplicateItem.php', [FaqItemController::class, 'duplicate'])->name('duplicate');

            Route::prefix('categorieen')->name('categories.')->group(function (): void {
                Route::get('index.php', [FaqCategoryController::class, 'index'])->name('index');
                Route::post('index.php', [FaqCategoryController::class, 'store'])->name('store');
                Route::get('edit.php', [FaqCategoryController::class, 'edit'])->name('edit');
                Route::post('edit.php', [FaqCategoryController::class, 'save'])->name('save');
            });

            Route::delete('categorieen/{faqCategory}', [FaqCategoryController::class, 'destroy'])
                ->whereNumber('faqCategory')
                ->name('categories.destroy');
            Route::put('{faqItem}', [FaqItemController::class, 'update'])
                ->whereNumber('faqItem')
                ->name('update');
            Route::delete('{faqItem}', [FaqItemController::class, 'destroy'])
                ->whereNumber('faqItem')
                ->name('destroy');
        });

        Route::prefix('download')->name('downloads.')->group(function (): void {
            Route::get('index.php', [DownloadController::class, 'index'])->name('index');
            Route::post('index.php', [DownloadController::class, 'store'])->name('store');
            Route::get('edit.php', [DownloadController::class, 'edit'])->name('edit');
            Route::post('edit.php', [DownloadController::class, 'save'])->name('save');
            Route::post('ajax/deleteBestand.php', [DownloadController::class, 'deleteFile'])->name('file.delete');
            Route::post('ajax/generateLink.php', [DownloadController::class, 'generateLink'])->name('link.generate');

            Route::prefix('categorieen')->name('categories.')->group(function (): void {
                Route::get('index.php', [DownloadCategoryController::class, 'index'])->name('index');
                Route::post('index.php', [DownloadCategoryController::class, 'store'])->name('store');
                Route::get('edit.php', [DownloadCategoryController::class, 'edit'])->name('edit');
                Route::post('edit.php', [DownloadCategoryController::class, 'save'])->name('save');
            });

            Route::delete('categorieen/{downloadCategory}', [DownloadCategoryController::class, 'destroy'])
                ->whereNumber('downloadCategory')
                ->name('categories.destroy');
            Route::put('{download}', [DownloadController::class, 'update'])
                ->whereNumber('download')
                ->name('update');
            Route::delete('{download}', [DownloadController::class, 'destroy'])
                ->whereNumber('download')
                ->name('destroy');
        });

        Route::prefix('vacatures')->name('vacancies.')->group(function (): void {
            Route::get('index.php', [VacancyController::class, 'index'])->name('index');
            Route::post('index.php', [VacancyController::class, 'store'])->name('store');
            Route::get('edit.php', [VacancyController::class, 'edit'])->name('edit');
            Route::post('edit.php', [VacancyController::class, 'save'])->name('save');
            Route::get('{id}/edit/{tab}', [VacancyController::class, 'edit'])
                ->whereNumber('id')
                ->whereIn('tab', ['seo', 'form'])
                ->name('edit.tab');

            Route::prefix('categorieen')->name('categories.')->group(function (): void {
                Route::get('index.php', [VacancyCategoryController::class, 'index'])->name('index');
                Route::post('index.php', [VacancyCategoryController::class, 'store'])->name('store');
                Route::get('edit.php', [VacancyCategoryController::class, 'edit'])->name('edit');
                Route::post('edit.php', [VacancyCategoryController::class, 'save'])->name('save');
            });

            Route::delete('categorieen/{vacancyCategory}', [VacancyCategoryController::class, 'destroy'])
                ->whereNumber('vacancyCategory')
                ->name('categories.destroy');
            Route::put('{vacancy}', [VacancyController::class, 'update'])
                ->whereNumber('vacancy')
                ->name('update');
            Route::delete('{vacancy}', [VacancyController::class, 'destroy'])
                ->whereNumber('vacancy')
                ->name('destroy');
        });

        Route::prefix('landen')->name('countries.')->group(function (): void {
            Route::get('index.php', [CountryController::class, 'index'])->name('index');
            Route::post('index.php', [CountryController::class, 'store'])->name('store');
            Route::get('edit.php', [CountryController::class, 'edit'])->name('edit');
            Route::post('edit.php', [CountryController::class, 'save'])->name('save');

            Route::prefix('talen')->name('languages.')->group(function (): void {
                Route::get('index.php', [LanguageController::class, 'index'])->name('index');
                Route::post('index.php', [LanguageController::class, 'updateSettings'])->name('save-settings');
                Route::get('edit.php', [LanguageController::class, 'edit'])->name('edit');
                Route::post('edit.php', [LanguageController::class, 'save'])->name('save');
                Route::put('{language}', [LanguageController::class, 'update'])->whereNumber('language')->name('update');
            });

            Route::put('{country}', [CountryController::class, 'update'])
                ->whereNumber('country')
                ->name('update');
            Route::delete('{country}', [CountryController::class, 'destroy'])
                ->whereNumber('country')
                ->name('destroy');
        });

        Route::prefix('vestigingen')->name('locations.')->group(function (): void {
            Route::get('index.php', [LocationController::class, 'index'])->name('index');
            Route::post('index.php', [LocationController::class, 'store'])->name('store');
            Route::get('edit.php', [LocationController::class, 'edit'])->name('edit');
            Route::post('edit.php', [LocationController::class, 'save'])->name('save');
            Route::get('{id}/edit/{tab}', [LocationController::class, 'edit'])
                ->whereNumber('id')
                ->whereIn('tab', ['location'])
                ->name('edit.tab');
            Route::get('editAfbeeldingen.php', [LocationController::class, 'images'])->name('images');
            Route::post('editAfbeeldingen.php', [LocationController::class, 'uploadImage'])->name('images.upload');
            Route::get('editOpeningstijden.php', [LocationController::class, 'openingHours'])->name('opening-hours');
            Route::post('editOpeningstijden.php', [LocationController::class, 'saveOpeningHours'])->name('opening-hours.save');

            Route::post('ajax/duplicateItem.php', [LocationController::class, 'duplicate'])->name('duplicate');
            Route::post('ajax/deleteAfbeelding.php', [LocationController::class, 'deleteImage'])->name('image.delete');
            Route::post('ajax/updateAfbeeldingnaam.php', [LocationController::class, 'updateImageName'])->name('image.update-name');
            Route::post('ajax/updateSortIndex.php', [LocationController::class, 'updateImageSort'])->name('image.update-sort');
            Route::post('ajax/uploadAfbeelding.php', [LocationController::class, 'uploadImage'])->name('image.upload');
            Route::post('ajax/uploadFotoalbumAfbeelding.php', [LocationController::class, 'uploadImage'])->name('image.album-upload');
            Route::post('ajax/deleteOpeningstijd.php', [LocationController::class, 'deleteOpeningHour'])->name('opening-hour.delete');

            Route::prefix('categorieen')->name('categories.')->group(function (): void {
                Route::get('index.php', [LocationCategoryController::class, 'index'])->name('index');
                Route::post('index.php', [LocationCategoryController::class, 'store'])->name('store');
                Route::get('edit.php', [LocationCategoryController::class, 'edit'])->name('edit');
                Route::post('edit.php', [LocationCategoryController::class, 'save'])->name('save');
            });

            Route::delete('categorieen/{locationCategory}', [LocationCategoryController::class, 'destroy'])
                ->whereNumber('locationCategory')
                ->name('categories.destroy');
            Route::put('{location}', [LocationController::class, 'update'])
                ->whereNumber('location')
                ->name('update');
            Route::delete('{location}', [LocationController::class, 'destroy'])
                ->whereNumber('location')
                ->name('destroy');
        });

        Route::prefix('redirect')->name('redirects.')->group(function (): void {
            Route::get('index.php', [RedirectController::class, 'index'])->name('index');
            Route::post('index.php', [RedirectController::class, 'store'])->name('store');
            Route::get('edit.php', [RedirectController::class, 'edit'])->name('edit');
            Route::post('edit.php', [RedirectController::class, 'save'])->name('save');
            Route::post('ajax/editRedirect.php', [RedirectController::class, 'updateSource'])->name('source.update');
            Route::post('ajax/deleteRedirect.php', [RedirectController::class, 'destroyAjax'])->name('ajax.destroy');

            Route::put('{redirect}', [RedirectController::class, 'update'])
                ->whereNumber('redirect')
                ->name('update');
            Route::delete('{redirect}', [RedirectController::class, 'destroy'])
                ->whereNumber('redirect')
                ->name('destroy');
        });

        Route::prefix('content')->name('content.')->group(function (): void {
            Route::get('index.php', [ContentItemController::class, 'index'])->name('index');
            Route::post('index.php', [ContentItemController::class, 'store'])->name('store');
            Route::get('edit.php', [ContentItemController::class, 'edit'])->name('edit');
            Route::post('edit.php', [ContentItemController::class, 'save'])->name('save');
            Route::get('editAfbeeldingen.php', [ContentItemController::class, 'images'])->name('images');
            Route::post('editAfbeeldingen.php', [ContentItemController::class, 'uploadImage'])->name('images.upload');
            Route::post('{id}/preview', [ContentItemController::class, 'preview'])->whereNumber('id')->name('preview');

            Route::post('ajax/duplicateItem.php', [ContentItemController::class, 'duplicate'])->name('duplicate');
            Route::post('ajax/deleteAfbeelding.php', [ContentItemController::class, 'deleteImage'])->name('image.delete');
            Route::post('ajax/updateAfbeeldingnaam.php', [ContentItemController::class, 'updateImageName'])->name('image.update-name');
            Route::post('ajax/updateSortIndex.php', [ContentItemController::class, 'updateImageSort'])->name('image.update-sort');
            Route::post('ajax/uploadFotoalbumAfbeelding.php', [ContentItemController::class, 'uploadImage'])->name('image.upload');
        Route::prefix('categorieen')->name('categories.')->group(function (): void {
                Route::get('index.php', [ContentCategoryController::class, 'index'])->name('index');
                Route::post('index.php', [ContentCategoryController::class, 'store'])->name('store');
                Route::get('edit.php', [ContentCategoryController::class, 'edit'])->name('edit');
                Route::post('edit.php', [ContentCategoryController::class, 'save'])->name('save');
                Route::post('ajax/deleteAfbeelding.php', [ContentCategoryController::class, 'deleteImage'])->name('image.delete');
                Route::post('ajax/updateAfbeeldingnaam.php', [ContentCategoryController::class, 'updateImageName'])->name('image.update-name');
                Route::post('ajax/updateSortIndex.php', [ContentCategoryController::class, 'updateImageSort'])->name('image.update-sort');
                Route::post('ajax/uploadAfbeelding.php', [ContentCategoryController::class, 'uploadImage'])->name('image.upload');
            });

            Route::delete('categorieen/{contentCategory}', [ContentCategoryController::class, 'destroy'])
                ->whereNumber('contentCategory')
                ->name('categories.destroy');
            Route::delete('{contentItem}', [ContentItemController::class, 'destroy'])
                ->whereNumber('contentItem')
                ->name('destroy');
        });

        Route::prefix('evenementen')->name('events.')->group(function (): void {
            Route::get('index.php', [EventController::class, 'index'])->name('index');
            Route::post('index.php', [EventController::class, 'store'])->name('store');
            Route::get('edit.php', [EventController::class, 'edit'])->name('edit');
            Route::post('edit.php', [EventController::class, 'save'])->name('save');

            Route::post('ajax/deleteAfbeelding.php', [EventController::class, 'deleteImage'])->name('image.delete');
            Route::post('ajax/deleteImage.php', [EventController::class, 'deleteImage'])->name('image.delete-file');
            Route::post('ajax/updateAfbeeldingnaam.php', [EventController::class, 'updateImageName'])->name('image.update-name');
            Route::post('ajax/updateSortIndex.php', [EventController::class, 'updateImageSort'])->name('image.update-sort');
            Route::post('ajax/uploadAfbeelding.php', [EventController::class, 'uploadImage'])->name('image.upload');
            Route::post('ajax/deleteOnderdeel.php', [EventController::class, 'deletePart'])->name('part.delete');

            Route::prefix('categorieen')->name('categories.')->group(function (): void {
                Route::get('index.php', [EventCategoryController::class, 'index'])->name('index');
                Route::post('index.php', [EventCategoryController::class, 'store'])->name('store');
                Route::get('edit.php', [EventCategoryController::class, 'edit'])->name('edit');
                Route::post('edit.php', [EventCategoryController::class, 'save'])->name('save');
            });

            Route::delete('categorieen/{eventCategory}', [EventCategoryController::class, 'destroy'])
                ->whereNumber('eventCategory')
                ->name('categories.destroy');
            Route::put('{event}', [EventController::class, 'update'])
                ->whereNumber('event')
                ->name('update');
            Route::delete('{event}', [EventController::class, 'destroy'])
                ->whereNumber('event')
                ->name('destroy');
        });

        Route::prefix('users')->name('users.')->group(function (): void {
            Route::get('index.php', [UserController::class, 'index'])->name('index');
            Route::post('index.php', [UserController::class, 'store'])->name('store');
            Route::get('edit.php', [UserController::class, 'edit'])->name('edit');
            Route::get('{id}/edit/{tab}', [UserController::class, 'edit'])
                ->whereNumber('id')
                ->whereIn('tab', ['access', 'roles', 'image', 'two-factor'])
                ->name('edit.tab');
            Route::post('{user}/invitation/{area}', [UserController::class, 'sendInvitation'])
                ->whereNumber('user')
                ->whereIn('area', ['frontend', 'backend'])
                ->name('invitation');
            Route::post('{user}/two-factor/generate', [UserController::class, 'generateTwoFactor'])
                ->whereNumber('user')
                ->name('two-factor.generate');
            Route::delete('{user}/two-factor', [UserController::class, 'disableTwoFactor'])
                ->whereNumber('user')
                ->name('two-factor.disable');
            Route::post('edit.php', [UserController::class, 'save'])->name('save');
            Route::post('ajax/deleteAfbeelding.php', [UserController::class, 'deleteImage'])->name('image.delete');

            Route::prefix('categorieen')->name('categories.')->group(function (): void {
                Route::get('index.php', [UserCategoryController::class, 'index'])->name('index');
                Route::post('index.php', [UserCategoryController::class, 'store'])->name('store');
                Route::get('edit.php', [UserCategoryController::class, 'edit'])->name('edit');
                Route::post('edit.php', [UserCategoryController::class, 'save'])->name('save');
            });

            Route::delete('categorieen/{userCategory}', [UserCategoryController::class, 'destroy'])
                ->whereNumber('userCategory')
                ->name('categories.destroy');
            Route::put('{user}', [UserController::class, 'update'])
                ->whereNumber('user')
                ->name('update');
            Route::delete('{user}', [UserController::class, 'destroy'])
                ->whereNumber('user')
                ->name('destroy');
        });

        Route::prefix('roles')->name('roles.')->group(function (): void {
            Route::get('/', [RoleController::class, 'index'])->name('index-clean');
            Route::get('index.php', [RoleController::class, 'index'])->name('index');
            Route::post('/', [RoleController::class, 'store'])->name('store-clean');
            Route::post('index.php', [RoleController::class, 'store'])->name('store');
            Route::get('edit', [RoleController::class, 'edit'])->name('edit-clean');
            Route::get('edit.php', [RoleController::class, 'edit'])->name('edit');
            Route::post('edit', [RoleController::class, 'save'])->name('save-clean');
            Route::post('edit.php', [RoleController::class, 'save'])->name('save');
            Route::put('{role}', [RoleController::class, 'update'])->whereNumber('role')->name('update');
            Route::delete('{role}', [RoleController::class, 'destroy'])->whereNumber('role')->name('destroy');
        });

        Route::prefix('translations')->name('translations.')->group(function (): void {
            Route::get('/', [TranslationController::class, 'index'])->name('index-clean');
            Route::get('index.php', [TranslationController::class, 'index'])->name('index');
            Route::post('bulk', [TranslationController::class, 'bulkUpdate'])->name('bulk');
            Route::post('sync', [TranslationController::class, 'sync'])->name('sync-clean');
            Route::post('sync.php', [TranslationController::class, 'sync'])->name('sync');
            Route::post('/', [TranslationController::class, 'store'])->name('store-clean');
            Route::post('index.php', [TranslationController::class, 'store'])->name('store');
            Route::get('create', [TranslationController::class, 'create'])->name('create');
            Route::get('edit', [TranslationController::class, 'edit'])->name('edit-clean');
            Route::get('edit.php', [TranslationController::class, 'edit'])->name('edit');
            Route::post('edit', [TranslationController::class, 'save'])->name('save-clean');
            Route::post('edit.php', [TranslationController::class, 'save'])->name('save');
            Route::put('{translationKey}', [TranslationController::class, 'update'])
                ->whereNumber('translationKey')
                ->name('update');
            Route::delete('{translationKey}', [TranslationController::class, 'destroy'])
                ->whereNumber('translationKey')
                ->name('destroy');
        });

        Route::get('{modulePath}/index.php', [CmsModuleController::class, 'legacyIndex'])
            ->where('modulePath', '.*')
            ->name('modules.index');
        Route::post('{modulePath}/index.php', [CmsModuleController::class, 'store'])
            ->where('modulePath', '.*')
            ->name('modules.store');

        Route::get('{modulePath}/create', [CmsModuleController::class, 'legacyCreate'])
            ->where('modulePath', '.*')
            ->name('modules.create');
        Route::get('{modulePath}/edit.php', [CmsModuleController::class, 'legacyEdit'])
            ->where('modulePath', '.*')
            ->name('modules.legacy-edit');
        Route::post('{modulePath}/edit.php', [CmsModuleController::class, 'legacySave'])
            ->where('modulePath', '.*')
            ->name('modules.legacy-save');

        Route::get('{modulePath}/{page}.php', [CmsModuleController::class, 'legacyPage'])
            ->where('modulePath', '.*')
            ->where('page', '[A-Za-z0-9_-]+')
            ->name('modules.page');
        Route::post('{modulePath}/{page}.php', [CmsModuleController::class, 'legacyPageSave'])
            ->where('modulePath', '.*')
            ->where('page', '[A-Za-z0-9_-]+')
            ->name('modules.page-save');

        Route::get('{modulePath}/{record}/edit', [CmsModuleController::class, 'edit'])
            ->where('modulePath', '.*')
            ->whereNumber('record')
            ->name('modules.edit');
        Route::put('{modulePath}/{record}', [CmsModuleController::class, 'update'])
            ->where('modulePath', '.*')
            ->whereNumber('record')
            ->name('modules.update');
        Route::delete('{modulePath}/{record}', [CmsModuleController::class, 'destroy'])
            ->where('modulePath', '.*')
            ->whereNumber('record')
            ->name('modules.destroy');

        Route::get('{modulePath}', [CmsModuleController::class, 'legacyIndex'])
            ->where('modulePath', '.*')
            ->name('modules.show');
    });
});
