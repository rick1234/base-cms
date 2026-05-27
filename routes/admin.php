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
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\Redirects\RedirectController;
use App\Http\Controllers\Admin\Roles\RoleController;
use App\Http\Controllers\Admin\Translations\TranslationController;
use App\Http\Controllers\Admin\Users\UserCategoryController;
use App\Http\Controllers\Admin\Users\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('login', [AdminLoginController::class, 'create'])->name('login');
        Route::post('login', [AdminLoginController::class, 'store'])->name('login.store');
    });

    Route::middleware(['auth', 'can:access-admin'])->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::post('logout', [AdminLoginController::class, 'destroy'])->name('logout');

        Route::resource('pages', PageController::class)
            ->except(['show', 'destroy'])
            ->parameters(['pages' => 'page']);

        Route::prefix('banner')->name('banners.')->group(function (): void {
            Route::get('/', [BannerController::class, 'index'])->name('index');
            Route::get('index.php', [BannerController::class, 'index'])->name('legacy-index');
            Route::post('/', [BannerController::class, 'store'])->name('store');
            Route::post('index.php', [BannerController::class, 'store'])->name('legacy-store');

            Route::get('create', [BannerController::class, 'create'])->name('create');
            Route::get('{id}/edit', [BannerController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::post('{id?}', [BannerController::class, 'save'])->whereNumber('id')->name('save');
            Route::get('edit', [BannerController::class, 'edit'])->name('legacy-edit-clean');
            Route::post('edit', [BannerController::class, 'save'])->name('legacy-save-clean');
            Route::get('edit.php', [BannerController::class, 'edit'])->name('legacy-edit');
            Route::post('edit.php', [BannerController::class, 'save'])->name('legacy-save');

            Route::get('bulkUploader', [BannerController::class, 'bulkUploader'])->name('bulk');
            Route::get('bulkUploader.php', [BannerController::class, 'bulkUploader'])->name('legacy-bulk');
            Route::post('bulkUploader', [BannerController::class, 'uploadBulk'])->name('bulk.upload');
            Route::post('bulkUploader.php', [BannerController::class, 'uploadBulk'])->name('legacy-bulk.upload');

            Route::post('ajax/duplicateItem', [BannerController::class, 'duplicate'])->name('duplicate');
            Route::post('ajax/duplicateItem.php', [BannerController::class, 'duplicate'])->name('legacy-duplicate');
            Route::post('ajax/deleteAfbeelding', [BannerController::class, 'deleteImage'])->name('image.delete');
            Route::post('ajax/deleteAfbeelding.php', [BannerController::class, 'deleteImage'])->name('legacy-image.delete');
            Route::post('ajax/uploadBulkBanner', [BannerController::class, 'uploadBulk'])->name('bulk.ajax-upload');
            Route::post('ajax/uploadBulkBanner.php', [BannerController::class, 'uploadBulk'])->name('legacy-bulk.ajax-upload');

            Route::prefix('categorieen')->name('categories.')->group(function (): void {
                Route::get('/', [BannerCategoryController::class, 'index'])->name('index');
                Route::get('index.php', [BannerCategoryController::class, 'index'])->name('legacy-index');
                Route::post('/', [BannerCategoryController::class, 'store'])->name('store');
                Route::post('index.php', [BannerCategoryController::class, 'store'])->name('legacy-store');
                Route::get('create', [BannerCategoryController::class, 'create'])->name('create');
                Route::get('{id}/edit', [BannerCategoryController::class, 'edit'])->whereNumber('id')->name('edit');
                Route::post('{id?}', [BannerCategoryController::class, 'save'])->whereNumber('id')->name('save');
                Route::get('edit', [BannerCategoryController::class, 'edit'])->name('legacy-edit-clean');
                Route::post('edit', [BannerCategoryController::class, 'save'])->name('legacy-save-clean');
                Route::get('edit.php', [BannerCategoryController::class, 'edit'])->name('legacy-edit');
                Route::post('edit.php', [BannerCategoryController::class, 'save'])->name('legacy-save');
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
            Route::get('/', [FormController::class, 'index'])->name('index');
            Route::get('index.php', [FormController::class, 'index'])->name('legacy-index');
            Route::post('/', [FormController::class, 'store'])->name('store');
            Route::post('index.php', [FormController::class, 'store'])->name('legacy-store');

            Route::get('create', [FormController::class, 'create'])->name('create');
            Route::get('{id}/edit', [FormController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::post('{id?}', [FormController::class, 'save'])->whereNumber('id')->name('save');
            Route::get('edit', [FormController::class, 'edit'])->name('legacy-edit-clean');
            Route::post('edit', [FormController::class, 'save'])->name('legacy-save-clean');
            Route::get('edit.php', [FormController::class, 'edit'])->name('legacy-edit');
            Route::post('edit.php', [FormController::class, 'save'])->name('legacy-save');

            Route::post('{id}/builder', [FormController::class, 'saveBuilder'])->whereNumber('id')->name('builder.save');
            Route::post('builder', [FormController::class, 'saveBuilder'])->name('legacy-builder.save-clean');
            Route::post('builder.php', [FormController::class, 'saveBuilder'])->name('legacy-builder.save');

            Route::get('{id}/submissions', [FormController::class, 'submissions'])->whereNumber('id')->name('submissions');
            Route::get('editMessages', [FormController::class, 'submissions'])->name('legacy-submissions-clean');
            Route::get('editMessages.php', [FormController::class, 'submissions'])->name('legacy-submissions');

            Route::post('ajax/duplicateItem', [FormController::class, 'duplicate'])->name('duplicate');
            Route::post('ajax/duplicateItem.php', [FormController::class, 'duplicate'])->name('legacy-duplicate');

            Route::prefix('categorieen')->name('categories.')->group(function (): void {
                Route::get('/', [FormCategoryController::class, 'index'])->name('index');
                Route::get('index.php', [FormCategoryController::class, 'index'])->name('legacy-index');
                Route::post('/', [FormCategoryController::class, 'store'])->name('store');
                Route::post('index.php', [FormCategoryController::class, 'store'])->name('legacy-store');
                Route::get('create', [FormCategoryController::class, 'create'])->name('create');
                Route::get('{id}/edit', [FormCategoryController::class, 'edit'])->whereNumber('id')->name('edit');
                Route::post('{id?}', [FormCategoryController::class, 'save'])->whereNumber('id')->name('save');
                Route::get('edit', [FormCategoryController::class, 'edit'])->name('legacy-edit-clean');
                Route::post('edit', [FormCategoryController::class, 'save'])->name('legacy-save-clean');
                Route::get('edit.php', [FormCategoryController::class, 'edit'])->name('legacy-edit');
                Route::post('edit.php', [FormCategoryController::class, 'save'])->name('legacy-save');
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
            Route::get('/', [CatalogProductController::class, 'index'])->name('index');
            Route::get('index.php', [CatalogProductController::class, 'index'])->name('legacy-index');
            Route::post('/', [CatalogProductController::class, 'store'])->name('store');
            Route::post('index.php', [CatalogProductController::class, 'store'])->name('legacy-store');

            Route::get('create', [CatalogProductController::class, 'create'])->name('create');
            Route::get('{id}/edit', [CatalogProductController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::post('{id?}', [CatalogProductController::class, 'save'])->whereNumber('id')->name('save');
            Route::get('edit', [CatalogProductController::class, 'edit'])->name('legacy-edit-clean');
            Route::post('edit', [CatalogProductController::class, 'save'])->name('legacy-save-clean');
            Route::get('edit.php', [CatalogProductController::class, 'edit'])->name('legacy-edit');
            Route::post('edit.php', [CatalogProductController::class, 'save'])->name('legacy-save');

            Route::get('{id}/images', [CatalogProductController::class, 'images'])->whereNumber('id')->name('images');
            Route::post('{id}/images', [CatalogProductController::class, 'uploadImage'])->whereNumber('id')->name('images.upload');
            Route::get('editAfbeeldingen', [CatalogProductController::class, 'images'])->name('legacy-images-clean');
            Route::get('editAfbeeldingen.php', [CatalogProductController::class, 'images'])->name('legacy-images');
            Route::post('editAfbeeldingen', [CatalogProductController::class, 'uploadImage'])->name('legacy-images.upload-clean');
            Route::post('editAfbeeldingen.php', [CatalogProductController::class, 'uploadImage'])->name('legacy-images.upload');

            Route::get('{id}/options', [CatalogProductController::class, 'options'])->whereNumber('id')->name('options');
            Route::post('{id}/options', [CatalogProductController::class, 'saveOptions'])->whereNumber('id')->name('options.save');
            Route::get('editOptions', [CatalogProductController::class, 'options'])->name('legacy-options-clean');
            Route::post('editOptions', [CatalogProductController::class, 'saveOptions'])->name('legacy-options.save-clean');
            Route::get('editOptions.php', [CatalogProductController::class, 'options'])->name('legacy-options');
            Route::post('editOptions.php', [CatalogProductController::class, 'saveOptions'])->name('legacy-options.save');

            Route::get('{id}/translations', [CatalogProductController::class, 'translations'])->whereNumber('id')->name('translations');
            Route::post('{id}/translations', [CatalogProductController::class, 'saveTranslations'])->whereNumber('id')->name('translations.save');
            Route::get('editVertalingen', [CatalogProductController::class, 'translations'])->name('legacy-translations-clean');
            Route::post('editVertalingen', [CatalogProductController::class, 'saveTranslations'])->name('legacy-translations.save-clean');
            Route::get('editVertalingen.php', [CatalogProductController::class, 'translations'])->name('legacy-translations');
            Route::post('editVertalingen.php', [CatalogProductController::class, 'saveTranslations'])->name('legacy-translations.save');

            Route::get('{id}/videos', [CatalogProductController::class, 'videos'])->whereNumber('id')->name('videos');
            Route::post('{id}/videos', [CatalogProductController::class, 'saveVideos'])->whereNumber('id')->name('videos.save');
            Route::get('editVideo', [CatalogProductController::class, 'videos'])->name('legacy-videos-clean');
            Route::post('editVideo', [CatalogProductController::class, 'saveVideos'])->name('legacy-videos.save-clean');
            Route::get('editVideo.php', [CatalogProductController::class, 'videos'])->name('legacy-videos');
            Route::post('editVideo.php', [CatalogProductController::class, 'saveVideos'])->name('legacy-videos.save');

            Route::get('{id}/stock', [CatalogProductController::class, 'stock'])->whereNumber('id')->name('stock');
            Route::post('{id}/stock', [CatalogProductController::class, 'saveStock'])->whereNumber('id')->name('stock.save');
            Route::get('editVoorraad', [CatalogProductController::class, 'stock'])->name('legacy-stock-clean');
            Route::post('editVoorraad', [CatalogProductController::class, 'saveStock'])->name('legacy-stock.save-clean');
            Route::get('editVoorraad.php', [CatalogProductController::class, 'stock'])->name('legacy-stock');
            Route::post('editVoorraad.php', [CatalogProductController::class, 'saveStock'])->name('legacy-stock.save');

            Route::get('{id}/combinations', [CatalogProductController::class, 'combinations'])->whereNumber('id')->name('combinations');
            Route::post('{id}/combinations', [CatalogProductController::class, 'saveCombinations'])->whereNumber('id')->name('combinations.save');
            Route::get('editCombinaties', [CatalogProductController::class, 'combinations'])->name('legacy-combinations-clean');
            Route::post('editCombinaties', [CatalogProductController::class, 'saveCombinations'])->name('legacy-combinations.save-clean');
            Route::get('editCombinaties.php', [CatalogProductController::class, 'combinations'])->name('legacy-combinations');
            Route::post('editCombinaties.php', [CatalogProductController::class, 'saveCombinations'])->name('legacy-combinations.save');

            Route::get('resetSortIndex', [CatalogProductController::class, 'resetSortIndex'])->name('reset-sort');
            Route::get('resetSortIndex.php', [CatalogProductController::class, 'resetSortIndex'])->name('legacy-reset-sort');

            Route::post('ajax/duplicateItem', [CatalogProductController::class, 'duplicate'])->name('duplicate');
            Route::post('ajax/duplicateItem.php', [CatalogProductController::class, 'duplicate'])->name('legacy-duplicate');
            Route::post('ajax/deleteAfbeelding', [CatalogProductController::class, 'deleteImage'])->name('image.delete');
            Route::post('ajax/deleteAfbeelding.php', [CatalogProductController::class, 'deleteImage'])->name('legacy-image.delete');
            Route::post('ajax/updateAfbeeldingnaam', [CatalogProductController::class, 'updateImageName'])->name('image.update-name');
            Route::post('ajax/updateAfbeeldingnaam.php', [CatalogProductController::class, 'updateImageName'])->name('legacy-image.update-name');
            Route::post('ajax/updateSortIndex', [CatalogProductController::class, 'updateImageSort'])->name('image.update-sort');
            Route::post('ajax/updateSortIndex.php', [CatalogProductController::class, 'updateImageSort'])->name('legacy-image.update-sort');
            Route::post('ajax/uploadAfbeelding', [CatalogProductController::class, 'uploadImage'])->name('image.upload');
            Route::post('ajax/uploadAfbeelding.php', [CatalogProductController::class, 'uploadImage'])->name('legacy-image.upload');

            Route::prefix('categorieen')->name('categories.')->group(function (): void {
                Route::get('/', [CatalogCategoryController::class, 'index'])->name('index');
                Route::get('index.php', [CatalogCategoryController::class, 'index'])->name('legacy-index');
                Route::post('/', [CatalogCategoryController::class, 'store'])->name('store');
                Route::post('index.php', [CatalogCategoryController::class, 'store'])->name('legacy-store');
                Route::get('create', [CatalogCategoryController::class, 'create'])->name('create');
                Route::get('{id}/edit', [CatalogCategoryController::class, 'edit'])->whereNumber('id')->name('edit');
                Route::post('{id?}', [CatalogCategoryController::class, 'save'])->whereNumber('id')->name('save');
                Route::get('edit', [CatalogCategoryController::class, 'edit'])->name('legacy-edit-clean');
                Route::post('edit', [CatalogCategoryController::class, 'save'])->name('legacy-save-clean');
                Route::get('edit.php', [CatalogCategoryController::class, 'edit'])->name('legacy-edit');
                Route::post('edit.php', [CatalogCategoryController::class, 'save'])->name('legacy-save');
            });

            Route::prefix('merken')->name('brands.')->group(function (): void {
                Route::get('/', [CatalogBrandController::class, 'index'])->name('index');
                Route::get('index.php', [CatalogBrandController::class, 'index'])->name('legacy-index');
                Route::post('/', [CatalogBrandController::class, 'store'])->name('store');
                Route::post('index.php', [CatalogBrandController::class, 'store'])->name('legacy-store');
                Route::get('create', [CatalogBrandController::class, 'create'])->name('create');
                Route::get('{id}/edit', [CatalogBrandController::class, 'edit'])->whereNumber('id')->name('edit');
                Route::post('{id?}', [CatalogBrandController::class, 'save'])->whereNumber('id')->name('save');
                Route::get('edit', [CatalogBrandController::class, 'edit'])->name('legacy-edit-clean');
                Route::post('edit', [CatalogBrandController::class, 'save'])->name('legacy-save-clean');
                Route::get('edit.php', [CatalogBrandController::class, 'edit'])->name('legacy-edit');
                Route::post('edit.php', [CatalogBrandController::class, 'save'])->name('legacy-save');
                Route::delete('{record}', [CatalogBrandController::class, 'destroy'])->whereNumber('record')->name('destroy');
            });

            Route::prefix('promotie')->name('promotions.')->group(function (): void {
                Route::get('/', [CatalogPromotionController::class, 'index'])->name('index');
                Route::get('index.php', [CatalogPromotionController::class, 'index'])->name('legacy-index');
                Route::post('/', [CatalogPromotionController::class, 'store'])->name('store');
                Route::post('index.php', [CatalogPromotionController::class, 'store'])->name('legacy-store');
                Route::get('create', [CatalogPromotionController::class, 'create'])->name('create');
                Route::get('{id}/edit', [CatalogPromotionController::class, 'edit'])->whereNumber('id')->name('edit');
                Route::post('{id?}', [CatalogPromotionController::class, 'save'])->whereNumber('id')->name('save');
                Route::get('edit', [CatalogPromotionController::class, 'edit'])->name('legacy-edit-clean');
                Route::post('edit', [CatalogPromotionController::class, 'save'])->name('legacy-save-clean');
                Route::get('edit.php', [CatalogPromotionController::class, 'edit'])->name('legacy-edit');
                Route::post('edit.php', [CatalogPromotionController::class, 'save'])->name('legacy-save');
                Route::delete('{record}', [CatalogPromotionController::class, 'destroy'])->whereNumber('record')->name('destroy');
            });

            Route::prefix('actiecodes')->name('coupons.')->group(function (): void {
                Route::get('/', [CatalogCouponController::class, 'index'])->name('index');
                Route::get('index.php', [CatalogCouponController::class, 'index'])->name('legacy-index');
                Route::post('/', [CatalogCouponController::class, 'store'])->name('store');
                Route::post('index.php', [CatalogCouponController::class, 'store'])->name('legacy-store');
                Route::get('create', [CatalogCouponController::class, 'create'])->name('create');
                Route::get('{id}/edit', [CatalogCouponController::class, 'edit'])->whereNumber('id')->name('edit');
                Route::post('{id?}', [CatalogCouponController::class, 'save'])->whereNumber('id')->name('save');
                Route::get('edit', [CatalogCouponController::class, 'edit'])->name('legacy-edit-clean');
                Route::post('edit', [CatalogCouponController::class, 'save'])->name('legacy-save-clean');
                Route::get('edit.php', [CatalogCouponController::class, 'edit'])->name('legacy-edit');
                Route::post('edit.php', [CatalogCouponController::class, 'save'])->name('legacy-save');
                Route::delete('{catalogCoupon}', [CatalogCouponController::class, 'destroy'])->whereNumber('catalogCoupon')->name('destroy');
            });

            Route::prefix('review')->name('reviews.')->group(function (): void {
                Route::get('/', [CatalogReviewController::class, 'index'])->name('index');
                Route::get('index.php', [CatalogReviewController::class, 'index'])->name('legacy-index');
                Route::post('/', [CatalogReviewController::class, 'store'])->name('store');
                Route::post('index.php', [CatalogReviewController::class, 'store'])->name('legacy-store');
                Route::get('create', [CatalogReviewController::class, 'create'])->name('create');
                Route::get('{id}/edit', [CatalogReviewController::class, 'edit'])->whereNumber('id')->name('edit');
                Route::post('{id?}', [CatalogReviewController::class, 'save'])->whereNumber('id')->name('save');
                Route::get('edit', [CatalogReviewController::class, 'edit'])->name('legacy-edit-clean');
                Route::post('edit', [CatalogReviewController::class, 'save'])->name('legacy-save-clean');
                Route::get('edit.php', [CatalogReviewController::class, 'edit'])->name('legacy-edit');
                Route::post('edit.php', [CatalogReviewController::class, 'save'])->name('legacy-save');
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
            Route::get('/', [FaqItemController::class, 'index'])->name('index');
            Route::get('index.php', [FaqItemController::class, 'index'])->name('legacy-index');
            Route::post('/', [FaqItemController::class, 'store'])->name('store');
            Route::post('index.php', [FaqItemController::class, 'store'])->name('legacy-store');

            Route::get('create', [FaqItemController::class, 'create'])->name('create');
            Route::get('{id}/edit', [FaqItemController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::post('{id?}', [FaqItemController::class, 'save'])->whereNumber('id')->name('save');
            Route::get('edit', [FaqItemController::class, 'edit'])->name('legacy-edit-clean');
            Route::post('edit', [FaqItemController::class, 'save'])->name('legacy-save-clean');
            Route::get('edit.php', [FaqItemController::class, 'edit'])->name('legacy-edit');
            Route::post('edit.php', [FaqItemController::class, 'save'])->name('legacy-save');

            Route::get('{id}/images', [FaqItemController::class, 'images'])->whereNumber('id')->name('images');
            Route::post('{id}/images', [FaqItemController::class, 'uploadImage'])->whereNumber('id')->name('images.upload');
            Route::get('editAfbeeldingen', [FaqItemController::class, 'images'])->name('legacy-images-clean');
            Route::get('editAfbeeldingen.php', [FaqItemController::class, 'images'])->name('legacy-images');
            Route::post('editAfbeeldingen', [FaqItemController::class, 'uploadImage'])->name('legacy-images.upload-clean');
            Route::post('editAfbeeldingen.php', [FaqItemController::class, 'uploadImage'])->name('legacy-images.upload');

            Route::get('{id}/videos', [FaqItemController::class, 'videos'])->whereNumber('id')->name('videos');
            Route::post('{id}/videos', [FaqItemController::class, 'saveVideos'])->whereNumber('id')->name('videos.save');
            Route::get('editVideo', [FaqItemController::class, 'videos'])->name('legacy-videos-clean');
            Route::post('editVideo', [FaqItemController::class, 'saveVideos'])->name('legacy-videos.save-clean');
            Route::get('editVideo.php', [FaqItemController::class, 'videos'])->name('legacy-videos');
            Route::post('editVideo.php', [FaqItemController::class, 'saveVideos'])->name('legacy-videos.save');

            Route::post('ajax/duplicateItem', [FaqItemController::class, 'duplicate'])->name('duplicate');
            Route::post('ajax/duplicateItem.php', [FaqItemController::class, 'duplicate'])->name('legacy-duplicate');
            Route::post('ajax/deleteAfbeelding', [FaqItemController::class, 'deleteImage'])->name('image.delete');
            Route::post('ajax/deleteAfbeelding.php', [FaqItemController::class, 'deleteImage'])->name('legacy-image.delete');
            Route::post('ajax/updateAfbeeldingnaam', [FaqItemController::class, 'updateImageName'])->name('image.update-name');
            Route::post('ajax/updateAfbeeldingnaam.php', [FaqItemController::class, 'updateImageName'])->name('legacy-image.update-name');
            Route::post('ajax/updateSortIndex', [FaqItemController::class, 'updateImageSort'])->name('image.update-sort');
            Route::post('ajax/updateSortIndex.php', [FaqItemController::class, 'updateImageSort'])->name('legacy-image.update-sort');
            Route::post('ajax/uploadFotoalbumAfbeelding', [FaqItemController::class, 'uploadImage'])->name('image.upload');
            Route::post('ajax/uploadFotoalbumAfbeelding.php', [FaqItemController::class, 'uploadImage'])->name('legacy-image.upload');
            Route::post('ajax/deleteVideo', [FaqItemController::class, 'deleteVideo'])->name('video.delete');
            Route::post('ajax/deleteVideo.php', [FaqItemController::class, 'deleteVideo'])->name('legacy-video.delete');

            Route::prefix('categorieen')->name('categories.')->group(function (): void {
                Route::get('/', [FaqCategoryController::class, 'index'])->name('index');
                Route::get('index.php', [FaqCategoryController::class, 'index'])->name('legacy-index');
                Route::post('/', [FaqCategoryController::class, 'store'])->name('store');
                Route::post('index.php', [FaqCategoryController::class, 'store'])->name('legacy-store');
                Route::get('create', [FaqCategoryController::class, 'create'])->name('create');
                Route::get('{id}/edit', [FaqCategoryController::class, 'edit'])->whereNumber('id')->name('edit');
                Route::post('{id?}', [FaqCategoryController::class, 'save'])->whereNumber('id')->name('save');
                Route::get('edit', [FaqCategoryController::class, 'edit'])->name('legacy-edit-clean');
                Route::post('edit', [FaqCategoryController::class, 'save'])->name('legacy-save-clean');
                Route::get('edit.php', [FaqCategoryController::class, 'edit'])->name('legacy-edit');
                Route::post('edit.php', [FaqCategoryController::class, 'save'])->name('legacy-save');
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
            Route::get('/', [DownloadController::class, 'index'])->name('index');
            Route::get('index.php', [DownloadController::class, 'index'])->name('legacy-index');
            Route::post('/', [DownloadController::class, 'store'])->name('store');
            Route::post('index.php', [DownloadController::class, 'store'])->name('legacy-store');

            Route::get('create', [DownloadController::class, 'create'])->name('create');
            Route::get('{id}/edit', [DownloadController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::post('{id?}', [DownloadController::class, 'save'])->whereNumber('id')->name('save');
            Route::get('edit', [DownloadController::class, 'edit'])->name('legacy-edit-clean');
            Route::post('edit', [DownloadController::class, 'save'])->name('legacy-save-clean');
            Route::get('edit.php', [DownloadController::class, 'edit'])->name('legacy-edit');
            Route::post('edit.php', [DownloadController::class, 'save'])->name('legacy-save');

            Route::post('ajax/deleteBestand', [DownloadController::class, 'deleteFile'])->name('file.delete');
            Route::post('ajax/deleteBestand.php', [DownloadController::class, 'deleteFile'])->name('legacy-file.delete');
            Route::post('ajax/generateLink', [DownloadController::class, 'generateLink'])->name('link.generate');
            Route::post('ajax/generateLink.php', [DownloadController::class, 'generateLink'])->name('legacy-link.generate');

            Route::prefix('categorieen')->name('categories.')->group(function (): void {
                Route::get('/', [DownloadCategoryController::class, 'index'])->name('index');
                Route::get('index.php', [DownloadCategoryController::class, 'index'])->name('legacy-index');
                Route::post('/', [DownloadCategoryController::class, 'store'])->name('store');
                Route::post('index.php', [DownloadCategoryController::class, 'store'])->name('legacy-store');
                Route::get('create', [DownloadCategoryController::class, 'create'])->name('create');
                Route::get('{id}/edit', [DownloadCategoryController::class, 'edit'])->whereNumber('id')->name('edit');
                Route::post('{id?}', [DownloadCategoryController::class, 'save'])->whereNumber('id')->name('save');
                Route::get('edit', [DownloadCategoryController::class, 'edit'])->name('legacy-edit-clean');
                Route::post('edit', [DownloadCategoryController::class, 'save'])->name('legacy-save-clean');
                Route::get('edit.php', [DownloadCategoryController::class, 'edit'])->name('legacy-edit');
                Route::post('edit.php', [DownloadCategoryController::class, 'save'])->name('legacy-save');
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

        Route::prefix('isolanden')->name('countries.')->group(function (): void {
            Route::get('/', [CountryController::class, 'index'])->name('index');
            Route::get('index.php', [CountryController::class, 'index'])->name('legacy-index');
            Route::post('/', [CountryController::class, 'store'])->name('store');
            Route::post('index.php', [CountryController::class, 'store'])->name('legacy-store');
            Route::post('sync', [CountryController::class, 'sync'])->name('sync');
            Route::post('sync.php', [CountryController::class, 'sync'])->name('legacy-sync');

            Route::get('create', [CountryController::class, 'create'])->name('create');
            Route::get('{id}/edit', [CountryController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::post('{id?}', [CountryController::class, 'save'])->whereNumber('id')->name('save');
            Route::get('edit', [CountryController::class, 'edit'])->name('legacy-edit-clean');
            Route::post('edit', [CountryController::class, 'save'])->name('legacy-save-clean');
            Route::get('edit.php', [CountryController::class, 'edit'])->name('legacy-edit');
            Route::post('edit.php', [CountryController::class, 'save'])->name('legacy-save');

            Route::prefix('talen')->name('languages.')->group(function (): void {
                Route::get('/', [LanguageController::class, 'index'])->name('index');
                Route::get('index.php', [LanguageController::class, 'index'])->name('legacy-index');
                Route::post('/', [LanguageController::class, 'updateSettings'])->name('save-settings');
                Route::post('index.php', [LanguageController::class, 'updateSettings'])->name('legacy-save-settings');
                Route::get('create', [LanguageController::class, 'create'])->name('create');
                Route::get('{id}/edit', [LanguageController::class, 'edit'])->whereNumber('id')->name('edit');
                Route::post('{id?}', [LanguageController::class, 'save'])->whereNumber('id')->name('save');
                Route::get('edit', [LanguageController::class, 'edit'])->name('legacy-edit-clean');
                Route::post('edit', [LanguageController::class, 'save'])->name('legacy-save-clean');
                Route::get('edit.php', [LanguageController::class, 'edit'])->name('legacy-edit');
                Route::post('edit.php', [LanguageController::class, 'save'])->name('legacy-save');
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
            Route::get('/', [LocationController::class, 'index'])->name('index');
            Route::get('index.php', [LocationController::class, 'index'])->name('legacy-index');
            Route::post('/', [LocationController::class, 'store'])->name('store');
            Route::post('index.php', [LocationController::class, 'store'])->name('legacy-store');

            Route::get('create', [LocationController::class, 'create'])->name('create');
            Route::get('{id}/edit', [LocationController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::post('{id?}', [LocationController::class, 'save'])->whereNumber('id')->name('save');
            Route::get('edit', [LocationController::class, 'edit'])->name('legacy-edit-clean');
            Route::post('edit', [LocationController::class, 'save'])->name('legacy-save-clean');
            Route::get('edit.php', [LocationController::class, 'edit'])->name('legacy-edit');
            Route::post('edit.php', [LocationController::class, 'save'])->name('legacy-save');

            Route::get('{id}/images', [LocationController::class, 'images'])->whereNumber('id')->name('images');
            Route::post('{id}/images', [LocationController::class, 'uploadImage'])->whereNumber('id')->name('images.upload');
            Route::get('editAfbeeldingen', [LocationController::class, 'images'])->name('legacy-images-clean');
            Route::get('editAfbeeldingen.php', [LocationController::class, 'images'])->name('legacy-images');
            Route::post('editAfbeeldingen', [LocationController::class, 'uploadImage'])->name('legacy-images.upload-clean');
            Route::post('editAfbeeldingen.php', [LocationController::class, 'uploadImage'])->name('legacy-images.upload');

            Route::get('{id}/opening-hours', [LocationController::class, 'openingHours'])->whereNumber('id')->name('opening-hours');
            Route::post('{id}/opening-hours', [LocationController::class, 'saveOpeningHours'])->whereNumber('id')->name('opening-hours.save');
            Route::get('editOpeningstijden', [LocationController::class, 'openingHours'])->name('legacy-opening-hours-clean');
            Route::post('editOpeningstijden', [LocationController::class, 'saveOpeningHours'])->name('legacy-opening-hours.save-clean');
            Route::get('editOpeningstijden.php', [LocationController::class, 'openingHours'])->name('legacy-opening-hours');
            Route::post('editOpeningstijden.php', [LocationController::class, 'saveOpeningHours'])->name('legacy-opening-hours.save');

            Route::post('ajax/duplicateItem', [LocationController::class, 'duplicate'])->name('duplicate');
            Route::post('ajax/duplicateItem.php', [LocationController::class, 'duplicate'])->name('legacy-duplicate');
            Route::post('ajax/deleteAfbeelding', [LocationController::class, 'deleteImage'])->name('image.delete');
            Route::post('ajax/deleteAfbeelding.php', [LocationController::class, 'deleteImage'])->name('legacy-image.delete');
            Route::post('ajax/updateAfbeeldingnaam', [LocationController::class, 'updateImageName'])->name('image.update-name');
            Route::post('ajax/updateAfbeeldingnaam.php', [LocationController::class, 'updateImageName'])->name('legacy-image.update-name');
            Route::post('ajax/updateSortIndex', [LocationController::class, 'updateImageSort'])->name('image.update-sort');
            Route::post('ajax/updateSortIndex.php', [LocationController::class, 'updateImageSort'])->name('legacy-image.update-sort');
            Route::post('ajax/uploadAfbeelding', [LocationController::class, 'uploadImage'])->name('image.upload');
            Route::post('ajax/uploadAfbeelding.php', [LocationController::class, 'uploadImage'])->name('legacy-image.upload');
            Route::post('ajax/uploadFotoalbumAfbeelding', [LocationController::class, 'uploadImage'])->name('image.album-upload');
            Route::post('ajax/uploadFotoalbumAfbeelding.php', [LocationController::class, 'uploadImage'])->name('legacy-image.album-upload');
            Route::post('ajax/deleteOpeningstijd', [LocationController::class, 'deleteOpeningHour'])->name('opening-hour.delete');
            Route::post('ajax/deleteOpeningstijd.php', [LocationController::class, 'deleteOpeningHour'])->name('legacy-opening-hour.delete');

            Route::prefix('categorieen')->name('categories.')->group(function (): void {
                Route::get('/', [LocationCategoryController::class, 'index'])->name('index');
                Route::get('index.php', [LocationCategoryController::class, 'index'])->name('legacy-index');
                Route::post('/', [LocationCategoryController::class, 'store'])->name('store');
                Route::post('index.php', [LocationCategoryController::class, 'store'])->name('legacy-store');
                Route::get('create', [LocationCategoryController::class, 'create'])->name('create');
                Route::get('{id}/edit', [LocationCategoryController::class, 'edit'])->whereNumber('id')->name('edit');
                Route::post('{id?}', [LocationCategoryController::class, 'save'])->whereNumber('id')->name('save');
                Route::get('edit', [LocationCategoryController::class, 'edit'])->name('legacy-edit-clean');
                Route::post('edit', [LocationCategoryController::class, 'save'])->name('legacy-save-clean');
                Route::get('edit.php', [LocationCategoryController::class, 'edit'])->name('legacy-edit');
                Route::post('edit.php', [LocationCategoryController::class, 'save'])->name('legacy-save');
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
            Route::get('/', [RedirectController::class, 'index'])->name('index');
            Route::get('index.php', [RedirectController::class, 'index'])->name('legacy-index');
            Route::post('/', [RedirectController::class, 'store'])->name('store');
            Route::post('index.php', [RedirectController::class, 'store'])->name('legacy-store');

            Route::get('create', [RedirectController::class, 'create'])->name('create');
            Route::get('{id}/edit', [RedirectController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::post('{id?}', [RedirectController::class, 'save'])->whereNumber('id')->name('save');
            Route::get('edit', [RedirectController::class, 'edit'])->name('legacy-edit-clean');
            Route::post('edit', [RedirectController::class, 'save'])->name('legacy-save-clean');
            Route::get('edit.php', [RedirectController::class, 'edit'])->name('legacy-edit');
            Route::post('edit.php', [RedirectController::class, 'save'])->name('legacy-save');

            Route::post('ajax/editRedirect', [RedirectController::class, 'updateSource'])->name('source.update');
            Route::post('ajax/editRedirect.php', [RedirectController::class, 'updateSource'])->name('legacy-source.update');
            Route::post('ajax/deleteRedirect', [RedirectController::class, 'destroyAjax'])->name('ajax.destroy');
            Route::post('ajax/deleteRedirect.php', [RedirectController::class, 'destroyAjax'])->name('legacy-ajax.destroy');

            Route::put('{redirect}', [RedirectController::class, 'update'])
                ->whereNumber('redirect')
                ->name('update');
            Route::delete('{redirect}', [RedirectController::class, 'destroy'])
                ->whereNumber('redirect')
                ->name('destroy');
        });

        Route::prefix('content')->name('content.')->group(function (): void {
            Route::get('/', [ContentItemController::class, 'index'])->name('index');
            Route::get('index.php', [ContentItemController::class, 'index'])->name('legacy-index');
            Route::post('/', [ContentItemController::class, 'store'])->name('store');

            Route::get('create', [ContentItemController::class, 'create'])->name('create');
            Route::get('{id}/edit', [ContentItemController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::post('{id?}', [ContentItemController::class, 'save'])->whereNumber('id')->name('save');
            Route::get('edit', [ContentItemController::class, 'edit'])->name('legacy-edit-clean');
            Route::post('edit', [ContentItemController::class, 'save'])->name('legacy-save-clean');
            Route::get('edit.php', [ContentItemController::class, 'edit'])->name('legacy-edit');
            Route::post('edit.php', [ContentItemController::class, 'save'])->name('legacy-save');

            Route::get('{id}/images', [ContentItemController::class, 'images'])->whereNumber('id')->name('images');
            Route::post('{id}/images', [ContentItemController::class, 'uploadImage'])->whereNumber('id')->name('images.upload');
            Route::get('editAfbeeldingen', [ContentItemController::class, 'images'])->name('legacy-images-clean');
            Route::get('editAfbeeldingen.php', [ContentItemController::class, 'images'])->name('legacy-images');
            Route::post('editAfbeeldingen', [ContentItemController::class, 'uploadImage'])->name('legacy-images.upload-clean');

            Route::get('{id}/slider', [ContentItemController::class, 'slider'])->whereNumber('id')->name('slider');
            Route::post('{id}/slider', [ContentItemController::class, 'saveSlider'])->whereNumber('id')->name('slider.save');
            Route::get('editSlider', [ContentItemController::class, 'slider'])->name('legacy-slider-clean');
            Route::post('editSlider', [ContentItemController::class, 'saveSlider'])->name('legacy-slider-save-clean');
            Route::get('editSlider.php', [ContentItemController::class, 'slider'])->name('legacy-slider');
            Route::post('editSlider.php', [ContentItemController::class, 'saveSlider'])->name('legacy-slider-save');
            Route::post('{id}/preview', [ContentItemController::class, 'preview'])->whereNumber('id')->name('preview');

            Route::post('ajax/duplicateItem', [ContentItemController::class, 'duplicate'])->name('duplicate');
            Route::post('ajax/deleteAfbeelding', [ContentItemController::class, 'deleteImage'])->name('image.delete');
            Route::post('ajax/updateAfbeeldingnaam', [ContentItemController::class, 'updateImageName'])->name('image.update-name');
            Route::post('ajax/updateSortIndex', [ContentItemController::class, 'updateImageSort'])->name('image.update-sort');
            Route::post('ajax/uploadFotoalbumAfbeelding', [ContentItemController::class, 'uploadImage'])->name('image.upload');
        Route::prefix('categorieen')->name('categories.')->group(function (): void {
                Route::get('/', [ContentCategoryController::class, 'index'])->name('index');
                Route::get('index.php', [ContentCategoryController::class, 'index'])->name('legacy-index');
                Route::post('/', [ContentCategoryController::class, 'store'])->name('store');

                Route::get('create', [ContentCategoryController::class, 'create'])->name('create');
                Route::get('{id}/edit', [ContentCategoryController::class, 'edit'])->whereNumber('id')->name('edit');
                Route::post('{id?}', [ContentCategoryController::class, 'save'])->whereNumber('id')->name('save');
                Route::get('edit', [ContentCategoryController::class, 'edit'])->name('legacy-edit-clean');
                Route::post('edit', [ContentCategoryController::class, 'save'])->name('legacy-save-clean');
                Route::get('edit.php', [ContentCategoryController::class, 'edit'])->name('legacy-edit');
                Route::post('edit.php', [ContentCategoryController::class, 'save'])->name('legacy-save');

                Route::get('{id}/slider', [ContentCategoryController::class, 'slider'])->whereNumber('id')->name('slider');
                Route::post('{id}/slider', [ContentCategoryController::class, 'saveSlider'])->whereNumber('id')->name('slider.save');
                Route::get('editSlider', [ContentCategoryController::class, 'slider'])->name('legacy-slider-clean');
                Route::post('editSlider', [ContentCategoryController::class, 'saveSlider'])->name('legacy-slider-save-clean');
                Route::get('editSlider.php', [ContentCategoryController::class, 'slider'])->name('legacy-slider');
                Route::post('editSlider.php', [ContentCategoryController::class, 'saveSlider'])->name('legacy-slider-save');

                Route::post('ajax/deleteAfbeelding', [ContentCategoryController::class, 'deleteImage'])->name('image.delete');
                Route::post('ajax/updateAfbeeldingnaam', [ContentCategoryController::class, 'updateImageName'])->name('image.update-name');
                Route::post('ajax/updateSortIndex', [ContentCategoryController::class, 'updateImageSort'])->name('image.update-sort');
                Route::post('ajax/uploadAfbeelding', [ContentCategoryController::class, 'uploadImage'])->name('image.upload');
            });

            Route::delete('categorieen/{contentCategory}', [ContentCategoryController::class, 'destroy'])
                ->whereNumber('contentCategory')
                ->name('categories.destroy');

            Route::delete('{contentItem}', [ContentItemController::class, 'destroy'])
                ->whereNumber('contentItem')
                ->name('destroy');
        });

        Route::prefix('evenementen')->name('events.')->group(function (): void {
            Route::get('/', [EventController::class, 'index'])->name('index');
            Route::get('index.php', [EventController::class, 'index'])->name('legacy-index');
            Route::post('/', [EventController::class, 'store'])->name('store');
            Route::post('index.php', [EventController::class, 'store'])->name('legacy-store');

            Route::get('create', [EventController::class, 'create'])->name('create');
            Route::get('{id}/edit', [EventController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::post('{id?}', [EventController::class, 'save'])->whereNumber('id')->name('save');
            Route::get('edit', [EventController::class, 'edit'])->name('legacy-edit-clean');
            Route::post('edit', [EventController::class, 'save'])->name('legacy-save-clean');
            Route::get('edit.php', [EventController::class, 'edit'])->name('legacy-edit');
            Route::post('edit.php', [EventController::class, 'save'])->name('legacy-save');

            Route::post('ajax/duplicateItem', [EventController::class, 'duplicate'])->name('duplicate');
            Route::post('ajax/duplicateItem.php', [EventController::class, 'duplicate'])->name('legacy-duplicate');
            Route::post('ajax/deleteAfbeelding', [EventController::class, 'deleteImage'])->name('image.delete');
            Route::post('ajax/deleteAfbeelding.php', [EventController::class, 'deleteImage'])->name('legacy-image.delete');
            Route::post('ajax/deleteImage', [EventController::class, 'deleteImage'])->name('image.delete-file');
            Route::post('ajax/deleteImage.php', [EventController::class, 'deleteImage'])->name('legacy-image.delete-file');
            Route::post('ajax/updateAfbeeldingnaam', [EventController::class, 'updateImageName'])->name('image.update-name');
            Route::post('ajax/updateAfbeeldingnaam.php', [EventController::class, 'updateImageName'])->name('legacy-image.update-name');
            Route::post('ajax/updateSortIndex', [EventController::class, 'updateImageSort'])->name('image.update-sort');
            Route::post('ajax/updateSortIndex.php', [EventController::class, 'updateImageSort'])->name('legacy-image.update-sort');
            Route::post('ajax/uploadAfbeelding', [EventController::class, 'uploadImage'])->name('image.upload');
            Route::post('ajax/uploadAfbeelding.php', [EventController::class, 'uploadImage'])->name('legacy-image.upload');
            Route::post('ajax/deleteOnderdeel', [EventController::class, 'deletePart'])->name('part.delete');
            Route::post('ajax/deleteOnderdeel.php', [EventController::class, 'deletePart'])->name('legacy-part.delete');

            Route::prefix('categorieen')->name('categories.')->group(function (): void {
                Route::get('/', [EventCategoryController::class, 'index'])->name('index');
                Route::get('index.php', [EventCategoryController::class, 'index'])->name('legacy-index');
                Route::post('/', [EventCategoryController::class, 'store'])->name('store');
                Route::post('index.php', [EventCategoryController::class, 'store'])->name('legacy-store');

                Route::get('create', [EventCategoryController::class, 'create'])->name('create');
                Route::get('{id}/edit', [EventCategoryController::class, 'edit'])->whereNumber('id')->name('edit');
                Route::post('{id?}', [EventCategoryController::class, 'save'])->whereNumber('id')->name('save');
                Route::get('edit', [EventCategoryController::class, 'edit'])->name('legacy-edit-clean');
                Route::post('edit', [EventCategoryController::class, 'save'])->name('legacy-save-clean');
                Route::get('edit.php', [EventCategoryController::class, 'edit'])->name('legacy-edit');
                Route::post('edit.php', [EventCategoryController::class, 'save'])->name('legacy-save');
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
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('index.php', [UserController::class, 'index'])->name('legacy-index');
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::post('index.php', [UserController::class, 'store'])->name('legacy-store');

            Route::get('create', [UserController::class, 'create'])->name('create');
            Route::get('{id}/edit', [UserController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::post('{id?}', [UserController::class, 'save'])->whereNumber('id')->name('save');
            Route::get('edit', [UserController::class, 'edit'])->name('legacy-edit-clean');
            Route::post('edit', [UserController::class, 'save'])->name('legacy-save-clean');
            Route::get('edit.php', [UserController::class, 'edit'])->name('legacy-edit');
            Route::post('edit.php', [UserController::class, 'save'])->name('legacy-save');

            Route::post('ajax/deleteAfbeelding', [UserController::class, 'deleteImage'])->name('image.delete');
            Route::post('ajax/deleteAfbeelding.php', [UserController::class, 'deleteImage'])->name('legacy-image.delete');

            Route::prefix('categorieen')->name('categories.')->group(function (): void {
                Route::get('/', [UserCategoryController::class, 'index'])->name('index');
                Route::get('index.php', [UserCategoryController::class, 'index'])->name('legacy-index');
                Route::post('/', [UserCategoryController::class, 'store'])->name('store');
                Route::post('index.php', [UserCategoryController::class, 'store'])->name('legacy-store');

                Route::get('create', [UserCategoryController::class, 'create'])->name('create');
                Route::get('{id}/edit', [UserCategoryController::class, 'edit'])->whereNumber('id')->name('edit');
                Route::post('{id?}', [UserCategoryController::class, 'save'])->whereNumber('id')->name('save');
                Route::get('edit', [UserCategoryController::class, 'edit'])->name('legacy-edit-clean');
                Route::post('edit', [UserCategoryController::class, 'save'])->name('legacy-save-clean');
                Route::get('edit.php', [UserCategoryController::class, 'edit'])->name('legacy-edit');
                Route::post('edit.php', [UserCategoryController::class, 'save'])->name('legacy-save');
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
            Route::get('/', [RoleController::class, 'index'])->name('index');
            Route::get('index.php', [RoleController::class, 'index'])->name('legacy-index');
            Route::post('/', [RoleController::class, 'store'])->name('store');
            Route::post('index.php', [RoleController::class, 'store'])->name('legacy-store');
            Route::get('create', [RoleController::class, 'create'])->name('create');
            Route::get('{id}/edit', [RoleController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::post('{id?}', [RoleController::class, 'save'])->whereNumber('id')->name('save');
            Route::get('edit', [RoleController::class, 'edit'])->name('legacy-edit-clean');
            Route::post('edit', [RoleController::class, 'save'])->name('legacy-save-clean');
            Route::get('edit.php', [RoleController::class, 'edit'])->name('legacy-edit');
            Route::post('edit.php', [RoleController::class, 'save'])->name('legacy-save');
            Route::put('{role}', [RoleController::class, 'update'])->whereNumber('role')->name('update');
            Route::delete('{role}', [RoleController::class, 'destroy'])->whereNumber('role')->name('destroy');
        });

        Route::prefix('translations')->name('translations.')->group(function (): void {
            Route::get('/', [TranslationController::class, 'index'])->name('index');
            Route::get('index.php', [TranslationController::class, 'index'])->name('legacy-index');
            Route::post('bulk', [TranslationController::class, 'bulkUpdate'])->name('bulk');
            Route::post('sync', [TranslationController::class, 'sync'])->name('sync');
            Route::post('sync.php', [TranslationController::class, 'sync'])->name('legacy-sync');
            Route::post('/', [TranslationController::class, 'store'])->name('store');
            Route::post('index.php', [TranslationController::class, 'store'])->name('legacy-store');
            Route::get('create', [TranslationController::class, 'create'])->name('create');
            Route::get('{id}/edit', [TranslationController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::post('{id?}', [TranslationController::class, 'save'])->whereNumber('id')->name('save');
            Route::get('edit', [TranslationController::class, 'edit'])->name('legacy-edit-clean');
            Route::post('edit', [TranslationController::class, 'save'])->name('legacy-save-clean');
            Route::get('edit.php', [TranslationController::class, 'edit'])->name('legacy-edit');
            Route::post('edit.php', [TranslationController::class, 'save'])->name('legacy-save');
            Route::put('{translationKey}', [TranslationController::class, 'update'])
                ->whereNumber('translationKey')
                ->name('update');
            Route::delete('{translationKey}', [TranslationController::class, 'destroy'])
                ->whereNumber('translationKey')
                ->name('destroy');
        });

        Route::get('modules', [CmsModuleController::class, 'index'])->name('modules.index');
        Route::match(['get', 'post'], 'delete', [CmsModuleController::class, 'legacyDelete'])->name('modules.delete');

        Route::get('{modulePath}/index.php', [CmsModuleController::class, 'legacyIndex'])
            ->where('modulePath', '.*')
            ->name('modules.legacy-index');
        Route::post('{modulePath}/index.php', [CmsModuleController::class, 'store'])
            ->where('modulePath', '.*')
            ->name('modules.legacy-store');

        Route::get('{modulePath}/create', [CmsModuleController::class, 'legacyCreate'])
            ->where('modulePath', '.*')
            ->name('modules.create');
        Route::get('{modulePath}/{record}/edit', [CmsModuleController::class, 'edit'])
            ->where('modulePath', '.*')
            ->whereNumber('record')
            ->name('modules.edit');
        Route::get('{modulePath}/edit.php', [CmsModuleController::class, 'legacyEdit'])
            ->where('modulePath', '.*')
            ->name('modules.legacy-edit');
        Route::post('{modulePath}/edit.php', [CmsModuleController::class, 'legacySave'])
            ->where('modulePath', '.*')
            ->name('modules.legacy-save');
        Route::get('{modulePath}/edit', [CmsModuleController::class, 'legacyEdit'])
            ->where('modulePath', '.*')
            ->name('modules.legacy-edit-clean');
        Route::post('{modulePath}/edit', [CmsModuleController::class, 'legacySave'])
            ->where('modulePath', '.*')
            ->name('modules.legacy-save-clean');

        Route::get('{modulePath}/{page}.php', [CmsModuleController::class, 'legacyPage'])
            ->where('modulePath', '.*')
            ->where('page', '[A-Za-z0-9_-]+')
            ->name('modules.legacy-page');
        Route::post('{modulePath}/{page}.php', [CmsModuleController::class, 'legacyPageSave'])
            ->where('modulePath', '.*')
            ->where('page', '[A-Za-z0-9_-]+')
            ->name('modules.legacy-page-save');
        Route::get('{modulePath}/{page}', [CmsModuleController::class, 'legacyPage'])
            ->where('modulePath', '.*')
            ->where('page', '[A-Za-z][A-Za-z0-9_-]*')
            ->name('modules.page');
        Route::post('{modulePath}/{page}', [CmsModuleController::class, 'legacyPageSave'])
            ->where('modulePath', '.*')
            ->where('page', '[A-Za-z][A-Za-z0-9_-]*')
            ->name('modules.page-save');

        Route::put('{modulePath}/{record}', [CmsModuleController::class, 'update'])
            ->where('modulePath', '.*')
            ->whereNumber('record')
            ->name('modules.update');
        Route::delete('{modulePath}/{record}', [CmsModuleController::class, 'destroy'])
            ->where('modulePath', '.*')
            ->whereNumber('record')
            ->name('modules.destroy');
        Route::post('{modulePath}', [CmsModuleController::class, 'store'])
            ->where('modulePath', '.*')
            ->name('modules.store');
        Route::get('{modulePath}', [CmsModuleController::class, 'legacyIndex'])
            ->where('modulePath', '.*')
            ->name('modules.show');
    });
});
