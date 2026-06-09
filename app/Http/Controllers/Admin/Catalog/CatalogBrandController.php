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

    protected function extraFields(): array
    {
        return [
            ['name' => 'website_url', 'label' => __('Website URL'), 'type' => 'url'],
            ['name' => 'logo_path', 'label' => __('Logo path'), 'type' => 'text'],
            ['name' => 'intro', 'label' => __('Intro'), 'type' => 'textarea'],
            ['name' => 'body', 'label' => __('Content'), 'type' => 'textarea'],
            ['name' => 'meta_title', 'label' => __('Meta title'), 'type' => 'text'],
            ['name' => 'meta_description', 'label' => __('Meta omschrijving'), 'type' => 'textarea'],
        ];
    }
}
