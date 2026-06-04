@extends('layouts.admin')

@php
    $isExisting = (bool) $form->id;
    $title = $isExisting ? __('Bewerk: :title', ['title' => $form->name]) : __('Toevoegen');
    $settings = $form->settings ?? [];
    $linkedCategoryIds = old('categories', $isExisting ? $form->categories->pluck('id')->all() : []);
    $recipientRows = $isExisting
        ? $form->recipients->map(fn ($recipient) => [
            'id' => $recipient->id,
            'name' => $recipient->name,
            'email' => $recipient->email,
            'type' => $recipient->type,
            'is_active' => $recipient->is_active,
            'sort_order' => $recipient->sort_order,
        ])->values()->all()
        : [];
    if ($recipientRows === [] && filled($form->recipient_email)) {
        $recipientRows[] = ['name' => __('Primary recipient'), 'email' => $form->recipient_email, 'type' => 'to', 'is_active' => true, 'sort_order' => 1];
    }
    $formFields = $isExisting
        ? $form->blocks->flatMap(fn ($block) => $block->rows)->flatMap(fn ($row) => $row->fields)
        : collect();
    $requestedTab = request()->route('tab') ?: request()->query('tab');
    $activeTab = $isExisting && in_array($requestedTab, ['template', 'recipients', 'response', 'builder'], true) ? $requestedTab : 'edit';
    $templateModuleUrl = route('admin.templates.index');
    $mailTemplateOptions = [
        'mail.forms.submission' => __('Form submission template'),
    ];
    $fieldElementIcons = [
        'input' => 'short_text',
        'textarea' => 'notes',
        'email' => 'alternate_email',
        'file' => 'upload_file',
        'radio' => 'radio_button_checked',
        'select' => 'arrow_drop_down_circle',
        'checkbox' => 'check_box',
        'image-set-choice' => 'image',
        'title' => 'title',
        'paragraph' => 'subject',
        'horizontal-rule' => 'horizontal_rule',
        'date' => 'calendar_month',
        'number' => 'pin',
        'phone' => 'call',
    ];
    $builderBlocks = $isExisting
        ? $form->blocks->values()->map(fn ($block) => [
            'id' => $block->id,
            'title' => $block->title,
            'sort_order' => $block->sort_order,
            'css_class' => $block->settings['css_class'] ?? null,
            'rows' => $block->rows->values()->map(fn ($row) => [
                'id' => $row->id,
                'sort_order' => $row->sort_order,
                'width' => $row->settings['width'] ?? 100,
                'css_class' => $row->settings['css_class'] ?? null,
                'fields' => $row->fields->values()->map(fn ($field) => [
                    'id' => $field->id,
                    'name' => $field->name,
                    'label' => $field->label,
                    'type' => $field->type,
                    'help_text' => $field->help_text,
                    'is_required' => $field->is_required,
                    'sort_order' => $field->sort_order,
                    'validation_rules' => collect($field->validation_rules ?? [])->implode('|'),
                    'placeholder' => $field->settings['placeholder'] ?? null,
                    'default_value' => $field->settings['default_value'] ?? null,
                    'label_visible' => $field->settings['label_visible'] ?? true,
                    'width' => $field->settings['width'] ?? 100,
                    'custom_error_message' => $field->settings['custom_error_message'] ?? null,
                    'information' => $field->settings['information'] ?? null,
                    'css_class' => $field->settings['css_class'] ?? null,
                    'options' => $field->options->values()->map(fn ($option) => [
                        'id' => $option->id,
                        'label' => $option->label,
                        'value' => $option->value,
                        'sort_order' => $option->sort_order,
                        'image_path' => $option->settings['image_path'] ?? null,
                        'description' => $option->settings['description'] ?? null,
                    ])->all(),
                ])->all(),
            ])->all(),
        ])->all()
        : [];
    $builderLabels = [
        'addField' => __('Veld toevoegen'),
        'canvasTitle' => __('Formulieropbouw'),
        'defaultBlockTitle' => __('Formulier'),
        'defaultFieldLabel' => __('Nieuw veld'),
        'deleteField' => __('Veld verwijderen'),
        'decreaseFieldWidth' => __('Veld smaller'),
        'dropFields' => __('Nog geen velden'),
        'editField' => __('Veld bewerken'),
        'fieldWidth' => __('Veldbreedte'),
        'increaseFieldWidth' => __('Veld breder'),
        'fieldSettings' => __('Veld instellingen'),
        'moveField' => __('Veld verplaatsen'),
        'optionOne' => __('Optie 1'),
        'optionTwo' => __('Optie 2'),
        'optionsPlaceholder' => __('Een optie per regel'),
        'saveSettings' => __('Instellingen opslaan'),
    ];
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                @if ($activeTab === 'builder' && $isExisting)
                    <button class="btn btn-save" form="form-builder-form" type="submit">
                        <x-admin.material-icon name="save" />
                        {{ __('Formulier opslaan') }}
                    </button>
                @elseif ($activeTab === 'response' && $isExisting)
                    <button class="btn btn-save" form="form-response-mail-builder-form" type="submit">
                        <x-admin.material-icon name="save" />
                        {{ __('Bevestigingsmail opslaan') }}
                    </button>
                @else
                    <button class="btn btn-save" form="form-general-form" type="submit">
                        <x-admin.material-icon name="save" />
                        {{ __('Opslaan') }}
                    </button>
                    <button class="btn btn-save-and-stay" form="form-general-form" name="saveAndStay" type="submit" value="1">
                        <x-admin.material-icon name="save" />
                        {{ __('Opslaan en blijven') }}
                    </button>
                @endif
                @if ($isExisting)
                    <form method="post" action="{{ route($routeNames['duplicate']) }}">
                        @csrf
                        <input type="hidden" name="itemId" value="{{ $form->id }}">
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

            @if (! in_array($activeTab, ['builder', 'response'], true))
                <form id="form-general-form" name="edit-form" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $form->id])) }}" accept-charset="UTF-8">
                    @csrf
                    <input type="hidden" name="id" value="{{ $form->id }}">
                    <input type="hidden" name="saveAndStay" value="0">
                    <input type="hidden" name="active_tab" value="{{ $activeTab }}">
                    <input type="hidden" name="slug" value="{{ old('slug', $form->slug) }}">
                    @if ($activeTab !== 'edit')
                        <input type="hidden" name="name" value="{{ old('name', $form->name) }}">
                        <input type="hidden" name="locale" value="{{ old('locale', $form->locale) }}">
                        <input type="hidden" name="status" value="{{ old('status', $form->status ?: 'published') }}">
                    @endif

                @if ($activeTab === 'edit')
                <div class="main-section">
                    @include('admin.forms.partials.page-header', [
                        'title' => $title,
                        'section' => $pageName,
                    ])

                    @include('admin.forms.partials.tabs', [
                        'form' => $form,
                        'routeNames' => $routeNames,
                        'activeTab' => $activeTab,
                    ])

                    <span class="content-admin-screen-label">{{ $pageName }}</span>

                    <div class="grid">
                        <div class="grid-row">
                            <div class="col-8">
                                <div class="content-section">
                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="name">{{ __('Naam') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="name" name="name" type="text" value="{{ old('name', $form->name) }}" required>
                                            @include('admin.content.partials.field-error', ['field' => 'name'])
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="locale">{{ __('Taal') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <x-admin.locale-radio-group name="locale" :selected="old('locale', $form->locale)" id-prefix="locale" />
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="status">{{ __('Status') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <select id="status" name="status">
                                                <option value="published" @selected(old('status', $form->status) === 'published')>{{ __('Online') }}</option>
                                                <option value="draft" @selected(old('status', $form->status) === 'draft')>{{ __('Offline') }}</option>
                                                <option value="archived" @selected(old('status', $form->status) === 'archived')>{{ __('Archived') }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="description">{{ __('Omschrijving') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <textarea id="description" name="description">{{ old('description', $form->description) }}</textarea>
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="submit_text">{{ __('Tekst op verzendknop') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="submit_text" name="submit_text" type="text" value="{{ old('submit_text', $form->submit_text ?: __('Versturen')) }}">
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="success_message">{{ __('Bericht na succesvol verzenden formulier') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <textarea id="success_message" name="success_message">{{ old('success_message', $form->success_message) }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-4">
                                <h2 class="title">{{ __('Categorie') }}</h2>
                                <div class="categories-tree">
                                    @include('admin.forms.partials.category-tree', [
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

                @endif

                @if ($activeTab === 'template')
                <div class="main-section">
                    @include('admin.forms.partials.page-header', [
                        'title' => $title,
                        'section' => $pageName,
                    ])

                    @include('admin.forms.partials.tabs', [
                        'form' => $form,
                        'routeNames' => $routeNames,
                        'activeTab' => $activeTab,
                    ])

                    <span class="content-admin-screen-label">{{ $pageName }}</span>

                    <div class="grid">
                        <div class="grid-row">
                            <div class="col-6">
                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="layout">{{ __('Layout') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <select id="layout" name="layout">
                                            @foreach (['default' => __('Default'), 'send-a-card' => __('Send a card'), 'compact' => __('Compact')] as $layout => $label)
                                                <option value="{{ $layout }}" @selected(old('layout', $settings['layout'] ?? 'default') === $layout)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="mail_template">{{ __('Mail template') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <select id="mail_template" name="mail_template">
                                            @foreach ($mailTemplateOptions as $template => $label)
                                                <option value="{{ $template }}" @selected(old('mail_template', $settings['mail_template'] ?? 'mail.forms.submission') === $template)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <p class="form-help-text">
                                    <a class="btn btn-duplicate" href="{{ $templateModuleUrl }}">
                                        <x-admin.material-icon name="dashboard_customize" />
                                        {{ __('Template module openen') }}
                                    </a>
                                </p>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="redirect_url">{{ __('Redirect URL') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="redirect_url" name="redirect_url" type="text" value="{{ old('redirect_url', $settings['redirect_url'] ?? '') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="col-6">
                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="confirmation_email_field">{{ __('Bevestiging e-mail veld') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <select id="confirmation_email_field" name="confirmation_email_field">
                                            <option value="">{{ __('Geen') }}</option>
                                            @foreach ($formFields as $field)
                                                <option value="{{ $field->name }}" @selected(old('confirmation_email_field', $settings['confirmation_email_field'] ?? null) === $field->name)>{{ $field->label ?: $field->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="from_email">{{ __('Afzender e-mail') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="from_email" name="from_email" type="email" value="{{ old('from_email', $settings['from_email'] ?? '') }}">
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="from_name">{{ __('Afzender naam') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="from_name" name="from_name" type="text" value="{{ old('from_name', $settings['from_name'] ?? '') }}">
                                    </div>
                                </div>

                                <div class="form-item form-builder-checks">
                                    <input type="hidden" name="show_title" value="0">
                                    <label>
                                        <input name="show_title" type="checkbox" value="1" @checked(old('show_title', $settings['show_title'] ?? true))>
                                        <span class="checkbox"></span>
                                        {{ __('Titel tonen') }}
                                    </label>
                                    <input type="hidden" name="store_submissions" value="0">
                                    <label>
                                        <input name="store_submissions" type="checkbox" value="1" @checked(old('store_submissions', $settings['store_submissions'] ?? true))>
                                        <span class="checkbox"></span>
                                        {{ __('Berichten bewaren') }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @if ($activeTab === 'recipients')
                <div class="main-section">
                    @include('admin.forms.partials.page-header', [
                        'title' => $title,
                        'section' => $pageName,
                    ])

                    @include('admin.forms.partials.tabs', [
                        'form' => $form,
                        'routeNames' => $routeNames,
                        'activeTab' => $activeTab,
                    ])

                    <span class="content-admin-screen-label">{{ $pageName }}</span>

                    <div class="form-section-toolbar form-section-toolbar-actions-only">
                        <div class="form-section-actions">
                            <button class="btn btn-save" form="form-general-form" type="submit">
                                <x-admin.material-icon name="save" />
                                {{ __('Opslaan') }}
                            </button>
                            <button class="btn btn-add" type="button" data-form-managed-list-add="recipients">
                                <x-admin.material-icon name="add" />
                                {{ __('Regel toevoegen') }}
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="recipient_email" value="{{ old('recipient_email', $form->recipient_email) }}">
                    <div class="overview-container form-listing-container" data-form-managed-list="recipients" data-form-managed-list-next-index="{{ count($recipientRows) }}">
                        <div class="overview-row header">
                            <div class="overview-item name">{{ __('Naam') }}</div>
                            <div class="overview-item email">{{ __('E-mail') }}</div>
                            <div class="overview-item type">{{ __('Type') }}</div>
                            <div class="overview-item status">{{ __('Actief') }}</div>
                            <div class="overview-item options">{{ __('Opties') }}</div>
                        </div>
                        @foreach ($recipientRows as $index => $recipient)
                            <div class="overview-row form-listing-row" data-form-managed-list-row>
                                <input type="hidden" name="recipients[{{ $index }}][id]" value="{{ $recipient['id'] ?? '' }}">
                                <input type="hidden" name="recipients[{{ $index }}][sort_order]" value="{{ $recipient['sort_order'] ?? $index + 1 }}">
                                <input type="hidden" name="recipients[{{ $index }}][delete]" value="0" data-form-managed-list-delete-input>
                                <div class="overview-item name">
                                    <label class="u-sr-only" for="recipient_name_{{ $index }}">{{ __('Naam') }}</label>
                                    <input id="recipient_name_{{ $index }}" name="recipients[{{ $index }}][name]" type="text" value="{{ $recipient['name'] ?? '' }}">
                                </div>
                                <div class="overview-item email">
                                    <label class="u-sr-only" for="recipient_email_{{ $index }}">{{ __('E-mail') }}</label>
                                    <input id="recipient_email_{{ $index }}" name="recipients[{{ $index }}][email]" type="email" value="{{ $recipient['email'] ?? '' }}">
                                </div>
                                <div class="overview-item type">
                                    <label class="u-sr-only" for="recipient_type_{{ $index }}">{{ __('Type') }}</label>
                                    <select id="recipient_type_{{ $index }}" name="recipients[{{ $index }}][type]">
                                        @foreach (['to' => 'To', 'cc' => 'CC', 'bcc' => 'BCC', 'reply-to' => 'Reply-to'] as $type => $label)
                                            <option value="{{ $type }}" @selected(($recipient['type'] ?? 'to') === $type)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="overview-item status">
                                    <input type="hidden" name="recipients[{{ $index }}][is_active]" value="0">
                                    <label>
                                        <input name="recipients[{{ $index }}][is_active]" type="checkbox" value="1" @checked($recipient['is_active'] ?? true)>
                                        <span class="checkbox"></span>
                                        {{ __('Actief') }}
                                    </label>
                                </div>
                                <div class="overview-item options">
                                    <button class="btn btn-remove btn-icon-only" type="button" title="{{ __('Verwijderen') }}" data-form-managed-list-delete>
                                        <x-admin.material-icon name="delete" />
                                    </button>
                                </div>
                            </div>
                        @endforeach
                        <template data-form-managed-list-template="recipients">
                            <div class="overview-row form-listing-row" data-form-managed-list-row>
                                <input type="hidden" name="recipients[__INDEX__][sort_order]" value="__SORT__">
                                <input type="hidden" name="recipients[__INDEX__][delete]" value="0" data-form-managed-list-delete-input>
                                <div class="overview-item name">
                                    <label class="u-sr-only" for="recipient_name___INDEX__">{{ __('Naam') }}</label>
                                    <input id="recipient_name___INDEX__" name="recipients[__INDEX__][name]" type="text">
                                </div>
                                <div class="overview-item email">
                                    <label class="u-sr-only" for="recipient_email___INDEX__">{{ __('E-mail') }}</label>
                                    <input id="recipient_email___INDEX__" name="recipients[__INDEX__][email]" type="email">
                                </div>
                                <div class="overview-item type">
                                    <label class="u-sr-only" for="recipient_type___INDEX__">{{ __('Type') }}</label>
                                    <select id="recipient_type___INDEX__" name="recipients[__INDEX__][type]">
                                        @foreach (['to' => 'To', 'cc' => 'CC', 'bcc' => 'BCC', 'reply-to' => 'Reply-to'] as $type => $label)
                                            <option value="{{ $type }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="overview-item status">
                                    <input type="hidden" name="recipients[__INDEX__][is_active]" value="0">
                                    <label>
                                        <input name="recipients[__INDEX__][is_active]" type="checkbox" value="1" checked>
                                        <span class="checkbox"></span>
                                        {{ __('Actief') }}
                                    </label>
                                </div>
                                <div class="overview-item options">
                                    <button class="btn btn-remove btn-icon-only" type="button" title="{{ __('Verwijderen') }}" data-form-managed-list-delete>
                                        <x-admin.material-icon name="delete" />
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                    @include('admin.content.partials.field-error', ['field' => 'recipients'])
                </div>
                @endif

                </form>
            @endif

            @if ($activeTab === 'response' && $isExisting)
                <div class="main-section" id="response-mail-builder">
                    @include('admin.forms.partials.page-header', [
                        'title' => $title,
                        'section' => $pageName,
                    ])

                    @include('admin.forms.partials.tabs', [
                        'form' => $form,
                        'routeNames' => $routeNames,
                        'activeTab' => $activeTab,
                    ])

                    <span class="content-admin-screen-label">{{ $pageName }}</span>

                    <livewire:admin.forms.response-mail-builder :form-id="$form->id" />
                </div>
            @endif

            @if ($activeTab === 'builder')
                <div class="main-section" id="form-builder">
                    @include('admin.forms.partials.page-header', [
                        'title' => $title,
                        'section' => $pageName,
                    ])

                    @include('admin.forms.partials.tabs', [
                        'form' => $form,
                        'routeNames' => $routeNames,
                        'activeTab' => $activeTab,
                    ])

                    <span class="content-admin-screen-label">{{ $pageName }}</span>

                    @include('admin.forms.partials.builder', [
                        'builderBlocks' => $builderBlocks,
                        'builderLabels' => $builderLabels,
                        'fieldElementIcons' => $fieldElementIcons,
                        'fieldTypes' => $fieldTypes,
                        'form' => $form,
                        'isExisting' => $isExisting,
                        'routeNames' => $routeNames,
                    ])
                </div>
            @endif

            @if ($isExisting)
                <div class="author-container">
                    <span><strong>{{ __('Gemaakt door') }}:</strong> {{ $form->creator?->fullName() ?? '-' }}</span>
                    <span><strong>{{ __('Gemaakt op') }}:</strong> {{ optional($form->created_at)->format('d-m-Y H:i') }}</span>
                </div>
            @endif
        </div>
    </div>
@endsection
