<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Controllers\Admin\Catalog\Concerns\ManagesCatalogRecords;
use App\Http\Controllers\Controller;
use App\Models\Cms\CatalogPromotion;

class CatalogPromotionController extends Controller
{
    use ManagesCatalogRecords;

    protected function modelClass(): string
    {
        return CatalogPromotion::class;
    }

    protected function routePrefix(): string
    {
        return 'promotions';
    }

    protected function titlePlural(): string
    {
        return __('Catalog Promotions');
    }

    protected function titleSingular(): string
    {
        return __('catalog promotion');
    }

    protected function legacyOverviewClass(): string
    {
        return 'catalogus-promotie-overview-container';
    }

    protected function sectionTitle(): string
    {
        return __('Promotie overzicht');
    }
}
