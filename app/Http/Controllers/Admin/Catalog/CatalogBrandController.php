<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Controllers\Admin\Catalog\Concerns\ManagesCatalogRecords;
use App\Http\Controllers\Controller;
use App\Models\Cms\CatalogBrand;

class CatalogBrandController extends Controller
{
    use ManagesCatalogRecords;

    protected function modelClass(): string
    {
        return CatalogBrand::class;
    }

    protected function routePrefix(): string
    {
        return 'brands';
    }

    protected function titlePlural(): string
    {
        return __('Catalog Brands');
    }

    protected function titleSingular(): string
    {
        return __('catalog brand');
    }

    protected function legacyOverviewClass(): string
    {
        return 'brands-overview-container';
    }

    protected function sectionTitle(): string
    {
        return __('Merken overzicht');
    }
}
