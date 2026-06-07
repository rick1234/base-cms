<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cms\Banner;
use App\Models\Cms\CatalogBrand;
use App\Models\Cms\CatalogCoupon;
use App\Models\Cms\CatalogProduct;
use App\Models\Cms\CatalogPromotion;
use App\Models\Cms\CatalogReview;
use App\Models\Cms\CmsLanguage;
use App\Models\Cms\CmsRedirect;
use App\Models\Cms\CmsRole;
use App\Models\Cms\ContentItem;
use App\Models\Cms\Country;
use App\Models\Cms\Download;
use App\Models\Cms\Event;
use App\Models\Cms\FaqItem;
use App\Models\Cms\Form;
use App\Models\Cms\Location;
use App\Models\Cms\NavigationMenu;
use App\Models\Cms\Vacancy;
use App\Models\Cms\WebsiteTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class QuickStatusController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $modelAliases = array_keys($this->models());

        $data = $request->validate([
            'model' => ['required', 'string', Rule::in($modelAliases)],
            'id' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'string'],
        ]);

        $config = $this->models()[$data['model']];
        $statusOptions = $this->statuses($config['status_set']);

        $request->validate([
            'status' => ['required', 'string', Rule::in(array_keys($statusOptions))],
        ]);

        /** @var class-string<Model> $modelClass */
        $modelClass = $config['class'];
        $model = $modelClass::query()->findOrFail($data['id']);
        $field = $config['field'];
        $value = $statusOptions[$data['status']]['value'];

        $model->forceFill([$field => $value]);

        if ($request->user() && Schema::hasColumn($model->getTable(), 'updated_by')) {
            $model->forceFill(['updated_by' => $request->user()->id]);
        }

        $model->save();

        flash(__('Status updated.'))->success();

        return back();
    }

    /**
     * @return array<string, array{class: class-string<Model>, field: string, status_set: string}>
     */
    private function models(): array
    {
        return [
            'banner' => ['class' => Banner::class, 'field' => 'status', 'status_set' => 'publishing'],
            'catalog-brand' => ['class' => CatalogBrand::class, 'field' => 'status', 'status_set' => 'active'],
            'catalog-coupon' => ['class' => CatalogCoupon::class, 'field' => 'is_active', 'status_set' => 'boolean_active'],
            'catalog-product' => ['class' => CatalogProduct::class, 'field' => 'status', 'status_set' => 'publishing'],
            'catalog-promotion' => ['class' => CatalogPromotion::class, 'field' => 'status', 'status_set' => 'active'],
            'catalog-review' => ['class' => CatalogReview::class, 'field' => 'status', 'status_set' => 'review'],
            'content' => ['class' => ContentItem::class, 'field' => 'status', 'status_set' => 'publishing'],
            'country' => ['class' => Country::class, 'field' => 'status', 'status_set' => 'active'],
            'country-enabled' => ['class' => Country::class, 'field' => 'is_enabled', 'status_set' => 'enabled'],
            'download' => ['class' => Download::class, 'field' => 'status', 'status_set' => 'active'],
            'event' => ['class' => Event::class, 'field' => 'status', 'status_set' => 'publishing'],
            'faq' => ['class' => FaqItem::class, 'field' => 'status', 'status_set' => 'publishing'],
            'form' => ['class' => Form::class, 'field' => 'status', 'status_set' => 'publishing'],
            'language' => ['class' => CmsLanguage::class, 'field' => 'status', 'status_set' => 'active'],
            'location' => ['class' => Location::class, 'field' => 'status', 'status_set' => 'active'],
            'navigation-menu' => ['class' => NavigationMenu::class, 'field' => 'is_active', 'status_set' => 'boolean_active'],
            'redirect' => ['class' => CmsRedirect::class, 'field' => 'is_active', 'status_set' => 'boolean_active'],
            'role' => ['class' => CmsRole::class, 'field' => 'status', 'status_set' => 'active'],
            'user' => ['class' => User::class, 'field' => 'is_active', 'status_set' => 'boolean_active'],
            'vacancy' => ['class' => Vacancy::class, 'field' => 'status', 'status_set' => 'publishing'],
            'website-template' => ['class' => WebsiteTemplate::class, 'field' => 'is_active', 'status_set' => 'boolean_active'],
        ];
    }

    /**
     * @return array<string, array{label: string, value: mixed, class: string}>
     */
    private function statuses(string $statusSet): array
    {
        return match ($statusSet) {
            'publishing' => [
                'published' => ['label' => __('Online'), 'value' => 'published', 'class' => 'is-published'],
                'draft' => ['label' => __('Offline'), 'value' => 'draft', 'class' => 'is-draft'],
                'archived' => ['label' => __('Archived'), 'value' => 'archived', 'class' => 'is-archived'],
            ],
            'review' => [
                'pending' => ['label' => __('Pending'), 'value' => 'pending', 'class' => 'is-pending'],
                'published' => ['label' => __('Published'), 'value' => 'published', 'class' => 'is-published'],
                'rejected' => ['label' => __('Rejected'), 'value' => 'rejected', 'class' => 'is-rejected'],
            ],
            'enabled' => [
                'enabled' => ['label' => __('Enabled'), 'value' => true, 'class' => 'is-active'],
                'disabled' => ['label' => __('Disabled'), 'value' => false, 'class' => 'is-inactive'],
            ],
            'boolean_active' => [
                'active' => ['label' => __('Active'), 'value' => true, 'class' => 'is-active'],
                'inactive' => ['label' => __('Inactive'), 'value' => false, 'class' => 'is-inactive'],
            ],
            default => [
                'active' => ['label' => __('Actief'), 'value' => 'active', 'class' => 'is-active'],
                'inactive' => ['label' => __('Inactief'), 'value' => 'inactive', 'class' => 'is-inactive'],
            ],
        };
    }
}
