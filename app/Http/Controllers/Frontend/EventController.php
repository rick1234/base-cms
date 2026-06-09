<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Cms\Event;
use App\Models\Cms\EventCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $activeCategorySlug = trim((string) $request->query('category'));
        $activeCategory = $activeCategorySlug === ''
            ? null
            : EventCategory::query()
                ->where('slug', $activeCategorySlug)
                ->where('status', 'active')
                ->first();

        $events = Event::query()
            ->online()
            ->with(['categories', 'images'])
            ->where(function (Builder $query): void {
                $query->whereNull('locale')
                    ->orWhere('locale', app()->getLocale());
            })
            ->when($activeCategory instanceof EventCategory, function (Builder $query) use ($activeCategory): void {
                $query->whereHas('categories', fn (Builder $query): Builder => $query->whereKey($activeCategory->id));
            })
            ->frontendOrdered()
            ->paginate(12)
            ->withQueryString();

        $categories = EventCategory::query()
            ->where('status', 'active')
            ->where('is_hidden_from_navigation', false)
            ->whereHas('events', function (Builder $query): void {
                $query->online()
                    ->where(function (Builder $query): void {
                        $query->whereNull('locale')
                            ->orWhere('locale', app()->getLocale());
                    });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('frontend.events.index', [
            'page' => [
                'title' => __('Events'),
                'meta_description' => __('Discover upcoming events.'),
            ],
            'events' => $events,
            'categories' => $categories,
            'activeCategory' => $activeCategory,
        ]);
    }

    public function show(Request $request): View
    {
        $eventSlug = (string) $request->route('event');

        $event = Event::query()
            ->online()
            ->with(['attachments', 'categories', 'form.blocks.rows.fields.options', 'images', 'parts', 'scheduleGroups.parts'])
            ->where('slug', $eventSlug)
            ->where(function (Builder $query): void {
                $query->whereNull('locale')
                    ->orWhere('locale', app()->getLocale());
            })
            ->firstOrFail();

        return view('frontend.events.show', [
            'event' => $event,
            'page' => $event,
        ]);
    }
}
