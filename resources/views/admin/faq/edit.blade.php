@extends('layouts.admin')

@php
    $isExisting = (bool) $faqItem->id;
    $title = $isExisting ? __('Bewerk: :title', ['title' => $faqItem->question]) : __('Toevoegen');
    $linkedCategoryIds = old('categories', $isExisting ? $faqItem->categories->pluck('id')->all() : []);
    $storedMoreInfoLinks = collect(data_get($faqItem->metadata, 'more_info_links', []));

    if ($storedMoreInfoLinks->isEmpty() && filled(data_get($faqItem->metadata, 'more_info.navigation_item_id'))) {
        $storedMoreInfoLinks = collect([data_get($faqItem->metadata, 'more_info')]);
    }

    $oldMoreInfoLinks = old('more_info_links');
    $moreInfoRows = collect(is_array($oldMoreInfoLinks) ? $oldMoreInfoLinks : $storedMoreInfoLinks->all())
        ->filter(fn ($link) => is_array($link))
        ->map(fn (array $link) => [
            'navigation_item_id' => data_get($link, 'navigation_item_id'),
            'label' => data_get($link, 'label'),
        ])
        ->values();

    if ($moreInfoRows->isEmpty()) {
        $moreInfoRows = collect([[
            'navigation_item_id' => null,
            'label' => null,
        ]]);
    }
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <button class="btn btn-save" form="faq-form" type="submit">
                    <x-admin.material-icon name="save" />
                    {{ __('Opslaan') }}
                </button>
                <button class="btn btn-save-and-stay" form="faq-form" name="saveAndStay" type="submit" value="1">
                    <x-admin.material-icon name="save" />
                    {{ __('Opslaan en blijven') }}
                </button>
                @if ($isExisting)
                    <form method="post" action="{{ route($routeNames['duplicate']) }}">
                        @csrf
                        <input type="hidden" name="itemId" value="{{ $faqItem->id }}">
                        <button class="btn btn-duplicate" type="submit">
                            <x-admin.material-icon name="content_copy" />
                            {{ __('Dupliceren') }}
                        </button>
                    </form>
                    <form method="post" action="{{ $deleteAction }}">
                        @csrf
                        @method('delete')
                        <button class="btn btn-remove" type="submit">
                            <x-admin.material-icon name="delete" />
                            {{ __('Verwijderen') }}
                        </button>
                    </form>
                @endif
                <a href="{{ $backUrl }}" class="btn btn-cancel">
                    <x-admin.material-icon name="undo" />
                    {{ __('Terug') }}
                </a>
            </div>

            <form id="faq-form" name="edit-form" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $faqItem->id])) }}" accept-charset="UTF-8">
                @csrf
                <input type="hidden" name="id" value="{{ $faqItem->id }}">
                <input type="hidden" name="saveAndStay" value="0">

                <div class="main-section faq-edit-section">
                    @include('admin.faq.partials.page-header', [
                        'title' => $title,
                        'section' => $pageName,
                    ])

                    <span class="content-admin-screen-label">{{ $pageName }}</span>

                    <div class="grid faq-edit-grid">
                        <div class="grid-row">
                            <div class="col-8">
                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="question">{{ __('Vraag') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="question" name="question" type="text" value="{{ old('question', $faqItem->question) }}" required>
                                        @include('admin.content.partials.field-error', ['field' => 'question'])
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="status">{{ __('Status') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <select id="status" name="status">
                                            <option value="published" @selected(old('status', $faqItem->status) === 'published')>{{ __('Online') }}</option>
                                            <option value="draft" @selected(old('status', $faqItem->status) === 'draft')>{{ __('Offline') }}</option>
                                            <option value="archived" @selected(old('status', $faqItem->status) === 'archived')>{{ __('Archived') }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label id="faq-answer-label" for="body">{{ __('Antwoord') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <div class="wysiwyg-editor" data-wysiwyg-editor>
                                            <div class="wysiwyg-toolbar" role="toolbar" aria-label="{{ __('Tekstopmaak') }}">
                                                <button type="button" data-wysiwyg-command="bold" aria-label="{{ __('Vet') }}" title="{{ __('Vet') }}">
                                                    <x-admin.material-icon name="format_bold" />
                                                </button>
                                                <button type="button" data-wysiwyg-command="italic" aria-label="{{ __('Cursief') }}" title="{{ __('Cursief') }}">
                                                    <x-admin.material-icon name="format_italic" />
                                                </button>
                                                <button type="button" data-wysiwyg-command="insertUnorderedList" aria-label="{{ __('Opsomming') }}" title="{{ __('Opsomming') }}">
                                                    <x-admin.material-icon name="format_list_bulleted" />
                                                </button>
                                                <button type="button" data-wysiwyg-command="insertOrderedList" aria-label="{{ __('Genummerde lijst') }}" title="{{ __('Genummerde lijst') }}">
                                                    <x-admin.material-icon name="format_list_numbered" />
                                                </button>
                                                <button type="button" data-wysiwyg-command="createLink" data-wysiwyg-prompt="{{ __('Link URL') }}" aria-label="{{ __('Link invoegen') }}" title="{{ __('Link invoegen') }}">
                                                    <x-admin.material-icon name="link" />
                                                </button>
                                            </div>
                                            <div class="wysiwyg-surface" contenteditable="true" role="textbox" aria-labelledby="faq-answer-label" aria-multiline="true" data-wysiwyg-surface>{!! old('body', $faqItem->body) !!}</div>
                                            <textarea class="wysiwyg-hidden-input" id="body" name="body" hidden data-wysiwyg-input>{{ old('body', $faqItem->body) }}</textarea>
                                        </div>
                                        @include('admin.content.partials.field-error', ['field' => 'body'])
                                    </div>
                                </div>

                                <div class="form-section-toolbar">
                                    <h2 class="title">{{ __('Meer informatie') }}</h2>
                                    <div class="form-section-actions">
                                        <button class="btn btn-add" type="button" data-form-managed-list-add="faq-more-info-links">
                                            <x-admin.material-icon name="add" />
                                            {{ __('Regel toevoegen') }}
                                        </button>
                                    </div>
                                </div>

                                <input type="hidden" name="more_info_links_present" value="1">
                                <div class="overview-container form-listing-container faq-more-info-container" data-form-managed-list="faq-more-info-links" data-form-managed-list-next-index="{{ $moreInfoRows->count() }}">
                                    <div class="overview-row header">
                                        <div class="overview-item target">{{ __('Meer informatie link') }}</div>
                                        <div class="overview-item label">{{ __('Linktekst') }}</div>
                                        <div class="overview-item options">{{ __('Opties') }}</div>
                                    </div>

                                    @foreach ($moreInfoRows as $index => $moreInfoLink)
                                        <div class="overview-row form-listing-row" data-form-managed-list-row>
                                            <div class="overview-item target">
                                                <span class="u-sr-only" id="more_info_link_target_{{ $index }}">{{ __('Meer informatie link') }}</span>
                                                @include('admin.partials.navigation-item-picker', [
                                                    'navigationItems' => $navigationItems,
                                                    'selectedNavigationItemId' => $moreInfoLink['navigation_item_id'],
                                                    'inputName' => "more_info_links[{$index}][navigation_item_id]",
                                                    'emptyLabel' => __('Geen extra link'),
                                                ])
                                                @include('admin.content.partials.field-error', ['field' => "more_info_links.{$index}.navigation_item_id"])
                                            </div>
                                            <div class="overview-item label">
                                                <label class="u-sr-only" for="more_info_link_label_{{ $index }}">{{ __('Linktekst') }}</label>
                                                <input id="more_info_link_label_{{ $index }}" name="more_info_links[{{ $index }}][label]" type="text" value="{{ $moreInfoLink['label'] }}">
                                                @include('admin.content.partials.field-error', ['field' => "more_info_links.{$index}.label"])
                                            </div>
                                            <div class="overview-item options">
                                                <button class="btn btn-remove btn-icon-only" type="button" title="{{ __('Verwijderen') }}" data-form-managed-list-delete>
                                                    <x-admin.material-icon name="delete" />
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach

                                    <template data-form-managed-list-template="faq-more-info-links">
                                        <div class="overview-row form-listing-row" data-form-managed-list-row>
                                            <div class="overview-item target">
                                                <span class="u-sr-only" id="more_info_link_target___INDEX__">{{ __('Meer informatie link') }}</span>
                                                @include('admin.partials.navigation-item-picker', [
                                                    'navigationItems' => $navigationItems,
                                                    'selectedNavigationItemId' => null,
                                                    'inputName' => 'more_info_links[__INDEX__][navigation_item_id]',
                                                    'emptyLabel' => __('Geen extra link'),
                                                ])
                                            </div>
                                            <div class="overview-item label">
                                                <label class="u-sr-only" for="more_info_link_label___INDEX__">{{ __('Linktekst') }}</label>
                                                <input id="more_info_link_label___INDEX__" name="more_info_links[__INDEX__][label]" type="text">
                                            </div>
                                            <div class="overview-item options">
                                                <button class="btn btn-remove btn-icon-only" type="button" title="{{ __('Verwijderen') }}" data-form-managed-list-delete>
                                                    <x-admin.material-icon name="delete" />
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                @include('admin.content.partials.field-error', ['field' => 'more_info_links'])
                            </div>

                            <div class="col-4">
                                <h2 class="title">{{ __('Categorie') }}</h2>
                                <div class="categories-tree">
                                    @include('admin.faq.partials.category-tree', [
                                        'categoriesByParent' => $categories->groupBy(fn ($category) => $category->parent_id ?: 0),
                                        'parentId' => 0,
                                        'linkedIds' => $linkedCategoryIds,
                                        'mode' => 'select',
                                        'routeNames' => $routeNames,
                                    ])
                                </div>
                                @include('admin.content.partials.field-error', ['field' => 'categories'])
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            @if ($isExisting)
                <div class="author-container">
                    <span><strong>{{ __('Gemaakt door') }}:</strong> {{ $faqItem->creator?->fullName() ?? '-' }}</span>
                    <span><strong>{{ __('Gemaakt op') }}:</strong> {{ optional($faqItem->created_at)->format('d-m-Y H:i') }}</span>
                </div>
            @endif
        </div>
    </div>
@endsection
