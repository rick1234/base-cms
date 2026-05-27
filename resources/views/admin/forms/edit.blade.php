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
    $recipientRows[] = ['name' => '', 'email' => '', 'type' => 'to', 'is_active' => true, 'sort_order' => count($recipientRows) + 1];
    $messageRows = $isExisting
        ? $form->messages->map(fn ($message) => [
            'id' => $message->id,
            'name' => $message->name,
            'subject' => $message->subject,
            'body' => $message->body,
            'type' => $message->type,
            'is_active' => $message->is_active,
            'sort_order' => $message->sort_order,
            'layout' => $message->settings['layout'] ?? null,
            'preheader' => $message->settings['preheader'] ?? null,
        ])->values()->all()
        : [
            ['name' => __('Admin notification'), 'subject' => __('New form submission: {form_name}'), 'body' => "{{summary}}", 'type' => 'notification', 'is_active' => true, 'sort_order' => 1, 'layout' => 'default'],
            ['name' => __('Submitter confirmation'), 'subject' => __('We received your message'), 'body' => __('Thank you. We received your message.')."\n\n{{summary}}", 'type' => 'confirmation', 'is_active' => false, 'sort_order' => 2, 'layout' => 'default'],
        ];
    $messageRows[] = ['name' => '', 'subject' => '', 'body' => '', 'type' => 'notification', 'is_active' => true, 'sort_order' => count($messageRows) + 1, 'layout' => 'default'];
    $formFields = $isExisting
        ? $form->blocks->flatMap(fn ($block) => $block->rows)->flatMap(fn ($row) => $row->fields)
        : collect();
    $activeTab = request('tab') === 'builder' ? 'builder' : 'edit';
@endphp

@section('title', $title)

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <button class="btn btn-save" form="form-general-form" type="submit">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan') }}
                </button>
                <button class="btn btn-save-and-stay" form="form-general-form" name="saveAndStay" type="submit" value="1">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan en blijven') }}
                </button>
                @if ($isExisting)
                    <form method="post" action="{{ route($routeNames['duplicate']) }}">
                        @csrf
                        <input type="hidden" name="itemId" value="{{ $form->id }}">
                        <button class="btn btn-duplicate" type="submit">
                            <span class="flaticon-add-to-queue-button"></span>
                            {{ __('Dupliceren') }}
                        </button>
                    </form>
                    <form method="post" action="{{ $deleteAction }}">
                        @csrf
                        @method('delete')
                        <button class="btn btn-remove" type="submit">
                            <span class="flaticon-close-button"></span>
                            {{ __('Verwijderen') }}
                        </button>
                    </form>
                @endif
                <a href="{{ $backUrl }}" class="btn btn-cancel">
                    <span class="flaticon-undo-button"></span>
                    {{ __('Annuleren') }}
                </a>
            </div>

            <form id="form-general-form" name="edit-form" method="post" action="{{ route($routeNames['save'], array_filter(['id' => $form->id])) }}" accept-charset="UTF-8">
                @csrf
                <input type="hidden" name="id" value="{{ $form->id }}">
                <input type="hidden" name="saveAndStay" value="0">

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
                                    <h2 class="title">{{ __('Algemeen') }}</h2>

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
                                            <label for="slug">{{ __('Slug') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="slug" name="slug" type="text" value="{{ old('slug', $form->slug) }}">
                                            @include('admin.content.partials.field-error', ['field' => 'slug'])
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="locale">{{ __('Taal') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <select id="locale" name="locale">
                                                @foreach (['nl', 'en', 'de', 'fr'] as $locale)
                                                    <option value="{{ $locale }}" @selected(old('locale', $form->locale) === $locale)>{{ strtoupper($locale) }}</option>
                                                @endforeach
                                            </select>
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
                                            <label for="submit_text">{{ __('Verzend knop') }}</label>
                                        </div>
                                        <div class="form-item-input">
                                            <input id="submit_text" name="submit_text" type="text" value="{{ old('submit_text', $form->submit_text ?: __('Versturen')) }}">
                                        </div>
                                    </div>

                                    <div class="form-item">
                                        <div class="form-item-label">
                                            <label for="success_message">{{ __('Succes bericht') }}</label>
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

                <div class="main-section">
                    <h2 class="title">{{ __('Instellingen') }}</h2>
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
                                        <input id="mail_template" name="mail_template" type="text" value="{{ old('mail_template', $settings['mail_template'] ?? 'forms.default') }}">
                                    </div>
                                </div>

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
                                    <input type="hidden" name="honeypot_enabled" value="0">
                                    <label>
                                        <input name="honeypot_enabled" type="checkbox" value="1" @checked(old('honeypot_enabled', $settings['honeypot_enabled'] ?? true))>
                                        <span class="checkbox"></span>
                                        {{ __('Spam honeypot') }}
                                    </label>
                                    <input type="hidden" name="honeypot_field" value="{{ old('honeypot_field', $settings['honeypot_field'] ?? 'website') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="main-section">
                    <h2 class="title">{{ __('Ontvangers') }}</h2>
                    <input type="hidden" name="recipient_email" value="{{ old('recipient_email', $form->recipient_email) }}">
                    <div class="form-recipient-list">
                        @foreach ($recipientRows as $index => $recipient)
                            <div class="form-recipient-row">
                                <input type="hidden" name="recipients[{{ $index }}][id]" value="{{ $recipient['id'] ?? '' }}">
                                <input type="hidden" name="recipients[{{ $index }}][sort_order]" value="{{ $recipient['sort_order'] ?? $index + 1 }}">
                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="recipient_name_{{ $index }}">{{ __('Naam') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="recipient_name_{{ $index }}" name="recipients[{{ $index }}][name]" type="text" value="{{ $recipient['name'] ?? '' }}">
                                    </div>
                                </div>
                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="recipient_email_{{ $index }}">{{ __('E-mail') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="recipient_email_{{ $index }}" name="recipients[{{ $index }}][email]" type="email" value="{{ $recipient['email'] ?? '' }}">
                                    </div>
                                </div>
                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="recipient_type_{{ $index }}">{{ __('Type') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <select id="recipient_type_{{ $index }}" name="recipients[{{ $index }}][type]">
                                            @foreach (['to' => 'To', 'cc' => 'CC', 'bcc' => 'BCC', 'reply-to' => 'Reply-to'] as $type => $label)
                                                <option value="{{ $type }}" @selected(($recipient['type'] ?? 'to') === $type)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="form-recipient-options">
                                    <input type="hidden" name="recipients[{{ $index }}][is_active]" value="0">
                                    <label>
                                        <input name="recipients[{{ $index }}][is_active]" type="checkbox" value="1" @checked($recipient['is_active'] ?? true)>
                                        <span class="checkbox"></span>
                                        {{ __('Actief') }}
                                    </label>
                                    @if (! empty($recipient['id']))
                                        <label>
                                            <input name="recipients[{{ $index }}][delete]" type="checkbox" value="1">
                                            <span class="checkbox"></span>
                                            {{ __('Verwijderen') }}
                                        </label>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @include('admin.content.partials.field-error', ['field' => 'recipients'])
                </div>

                <div class="main-section">
                    <h2 class="title">{{ __('Mail layout builder') }}</h2>
                    <div class="form-placeholder-list">
                        <strong>{{ __('Placeholders') }}:</strong>
                        <span>{{ '{form_name}' }}</span>
                        <span>{{ '{summary}' }}</span>
                        @foreach ($formFields as $field)
                            <span>{{ '{'.$field->name.'}' }}</span>
                        @endforeach
                    </div>
                    <div class="form-message-list">
                        @foreach ($messageRows as $index => $message)
                            <div class="form-message-row">
                                <input type="hidden" name="messages[{{ $index }}][id]" value="{{ $message['id'] ?? '' }}">
                                <input type="hidden" name="messages[{{ $index }}][sort_order]" value="{{ $message['sort_order'] ?? $index + 1 }}">
                                <div class="grid">
                                    <div class="grid-row">
                                        <div class="col-4">
                                            <div class="form-item">
                                                <div class="form-item-label">
                                                    <label for="message_name_{{ $index }}">{{ __('Naam') }}</label>
                                                </div>
                                                <div class="form-item-input">
                                                    <input id="message_name_{{ $index }}" name="messages[{{ $index }}][name]" type="text" value="{{ $message['name'] ?? '' }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="form-item">
                                                <div class="form-item-label">
                                                    <label for="message_type_{{ $index }}">{{ __('Type') }}</label>
                                                </div>
                                                <div class="form-item-input">
                                                    <select id="message_type_{{ $index }}" name="messages[{{ $index }}][type]">
                                                        @foreach (['notification' => __('Notificatie'), 'confirmation' => __('Bevestiging'), 'internal' => __('Intern')] as $type => $label)
                                                            <option value="{{ $type }}" @selected(($message['type'] ?? 'notification') === $type)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="form-item">
                                                <div class="form-item-label">
                                                    <label for="message_layout_{{ $index }}">{{ __('Layout') }}</label>
                                                </div>
                                                <div class="form-item-input">
                                                    <select id="message_layout_{{ $index }}" name="messages[{{ $index }}][layout]">
                                                        @foreach (['default' => __('Default'), 'compact' => __('Compact'), 'plain' => __('Plain text')] as $layout => $label)
                                                            <option value="{{ $layout }}" @selected(($message['layout'] ?? 'default') === $layout)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="message_subject_{{ $index }}">{{ __('Onderwerp') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="message_subject_{{ $index }}" name="messages[{{ $index }}][subject]" type="text" value="{{ $message['subject'] ?? '' }}">
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="message_preheader_{{ $index }}">{{ __('Preheader') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <input id="message_preheader_{{ $index }}" name="messages[{{ $index }}][preheader]" type="text" value="{{ $message['preheader'] ?? '' }}">
                                    </div>
                                </div>

                                <div class="form-item">
                                    <div class="form-item-label">
                                        <label for="message_body_{{ $index }}">{{ __('Bericht') }}</label>
                                    </div>
                                    <div class="form-item-input">
                                        <textarea id="message_body_{{ $index }}" name="messages[{{ $index }}][body]">{{ $message['body'] ?? '' }}</textarea>
                                    </div>
                                </div>

                                <div class="form-message-options">
                                    <input type="hidden" name="messages[{{ $index }}][is_active]" value="0">
                                    <label>
                                        <input name="messages[{{ $index }}][is_active]" type="checkbox" value="1" @checked($message['is_active'] ?? true)>
                                        <span class="checkbox"></span>
                                        {{ __('Actief') }}
                                    </label>
                                    @if (! empty($message['id']))
                                        <label>
                                            <input name="messages[{{ $index }}][delete]" type="checkbox" value="1">
                                            <span class="checkbox"></span>
                                            {{ __('Verwijderen') }}
                                        </label>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </form>

            <div class="main-section" id="form-builder">
                <div class="form-builder-header">
                    <h2 class="title">{{ __('Form builder') }}</h2>
                    @if ($isExisting)
                        <button class="btn btn-save" form="form-builder-form" type="submit">
                            <span class="flaticon-save-button"></span>
                            {{ __('Builder opslaan') }}
                        </button>
                    @endif
                </div>

                @if (! $isExisting)
                    <div class="attachment-message">
                        <span class="flaticon-rounded-info-button"></span>
                        <em>{{ __('Sla het formulier eerst op. Daarna kun je blokken, rijen, velden en opties beheren.') }}</em>
                    </div>
                @else
                    <form id="form-builder-form" method="post" action="{{ route($routeNames['builder.save'], ['id' => $form->id]) }}">
                        @csrf
                        <input type="hidden" name="id" value="{{ $form->id }}">

                        <div class="form-builder-block-list">
                            @foreach ($form->blocks as $blockIndex => $block)
                                <div class="form-builder-block">
                                    <input type="hidden" name="blocks[{{ $blockIndex }}][id]" value="{{ $block->id }}">
                                    <div class="form-builder-block-heading">
                                        <div class="form-item">
                                            <div class="form-item-label">
                                                <label for="block_title_{{ $blockIndex }}">{{ __('Blok titel') }}</label>
                                            </div>
                                            <div class="form-item-input">
                                                <input id="block_title_{{ $blockIndex }}" name="blocks[{{ $blockIndex }}][title]" type="text" value="{{ $block->title }}">
                                            </div>
                                        </div>
                                        <input name="blocks[{{ $blockIndex }}][sort_order]" type="number" value="{{ $block->sort_order }}">
                                        <label>
                                            <input name="blocks[{{ $blockIndex }}][delete]" type="checkbox" value="1">
                                            <span class="checkbox"></span>
                                            {{ __('Verwijderen') }}
                                        </label>
                                    </div>

                                    @foreach ($block->rows as $rowIndex => $row)
                                        <div class="form-builder-row">
                                            <input type="hidden" name="blocks[{{ $blockIndex }}][rows][{{ $rowIndex }}][id]" value="{{ $row->id }}">
                                            <div class="form-builder-row-heading">
                                                <strong>{{ __('Rij') }} {{ $rowIndex + 1 }}</strong>
                                                <input name="blocks[{{ $blockIndex }}][rows][{{ $rowIndex }}][sort_order]" type="number" value="{{ $row->sort_order }}">
                                                <input name="blocks[{{ $blockIndex }}][rows][{{ $rowIndex }}][width]" type="number" min="10" max="100" value="{{ $row->settings['width'] ?? 100 }}">
                                                <label>
                                                    <input name="blocks[{{ $blockIndex }}][rows][{{ $rowIndex }}][delete]" type="checkbox" value="1">
                                                    <span class="checkbox"></span>
                                                    {{ __('Verwijderen') }}
                                                </label>
                                            </div>

                                            <div class="form-builder-field-list">
                                                @foreach ($row->fields as $fieldIndex => $field)
                                                    @php $fieldSettings = $field->settings ?? []; @endphp
                                                    <div class="form-builder-field">
                                                        <input type="hidden" name="blocks[{{ $blockIndex }}][rows][{{ $rowIndex }}][fields][{{ $fieldIndex }}][id]" value="{{ $field->id }}">
                                                        <div class="grid">
                                                            <div class="grid-row">
                                                                <div class="col-4">
                                                                    <div class="form-item">
                                                                        <div class="form-item-label">
                                                                            <label>{{ __('Naam') }}</label>
                                                                        </div>
                                                                        <div class="form-item-input">
                                                                            <input name="blocks[{{ $blockIndex }}][rows][{{ $rowIndex }}][fields][{{ $fieldIndex }}][name]" type="text" value="{{ $field->name }}">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-4">
                                                                    <div class="form-item">
                                                                        <div class="form-item-label">
                                                                            <label>{{ __('Label') }}</label>
                                                                        </div>
                                                                        <div class="form-item-input">
                                                                            <input name="blocks[{{ $blockIndex }}][rows][{{ $rowIndex }}][fields][{{ $fieldIndex }}][label]" type="text" value="{{ $field->label }}">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-4">
                                                                    <div class="form-item">
                                                                        <div class="form-item-label">
                                                                            <label>{{ __('Type') }}</label>
                                                                        </div>
                                                                        <div class="form-item-input">
                                                                            <select name="blocks[{{ $blockIndex }}][rows][{{ $rowIndex }}][fields][{{ $fieldIndex }}][type]">
                                                                                @foreach ($fieldTypes as $type => $label)
                                                                                    <option value="{{ $type }}" @selected($field->type === $type)>{{ $label }}</option>
                                                                                @endforeach
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="grid">
                                                            <div class="grid-row">
                                                                <div class="col-3">
                                                                    <input name="blocks[{{ $blockIndex }}][rows][{{ $rowIndex }}][fields][{{ $fieldIndex }}][sort_order]" type="number" value="{{ $field->sort_order }}">
                                                                </div>
                                                                <div class="col-3">
                                                                    <input name="blocks[{{ $blockIndex }}][rows][{{ $rowIndex }}][fields][{{ $fieldIndex }}][width]" type="number" min="10" max="100" value="{{ $fieldSettings['width'] ?? 100 }}">
                                                                </div>
                                                                <div class="col-3">
                                                                    <label>
                                                                        <input name="blocks[{{ $blockIndex }}][rows][{{ $rowIndex }}][fields][{{ $fieldIndex }}][is_required]" type="checkbox" value="1" @checked($field->is_required)>
                                                                        <span class="checkbox"></span>
                                                                        {{ __('Verplicht') }}
                                                                    </label>
                                                                </div>
                                                                <div class="col-3">
                                                                    <label>
                                                                        <input name="blocks[{{ $blockIndex }}][rows][{{ $rowIndex }}][fields][{{ $fieldIndex }}][delete]" type="checkbox" value="1">
                                                                        <span class="checkbox"></span>
                                                                        {{ __('Verwijderen') }}
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="form-item">
                                                            <div class="form-item-label">
                                                                <label>{{ __('Placeholder') }}</label>
                                                            </div>
                                                            <div class="form-item-input">
                                                                <input name="blocks[{{ $blockIndex }}][rows][{{ $rowIndex }}][fields][{{ $fieldIndex }}][placeholder]" type="text" value="{{ $fieldSettings['placeholder'] ?? '' }}">
                                                            </div>
                                                        </div>
                                                        <div class="form-item">
                                                            <div class="form-item-label">
                                                                <label>{{ __('Helptekst') }}</label>
                                                            </div>
                                                            <div class="form-item-input">
                                                                <textarea name="blocks[{{ $blockIndex }}][rows][{{ $rowIndex }}][fields][{{ $fieldIndex }}][help_text]">{{ $field->help_text }}</textarea>
                                                            </div>
                                                        </div>
                                                        <div class="form-item">
                                                            <div class="form-item-label">
                                                                <label>{{ __('Validatie') }}</label>
                                                            </div>
                                                            <div class="form-item-input">
                                                                <input name="blocks[{{ $blockIndex }}][rows][{{ $rowIndex }}][fields][{{ $fieldIndex }}][validation_rules]" type="text" value="{{ collect($field->validation_rules ?? [])->implode('|') }}">
                                                            </div>
                                                        </div>

                                                        <div class="form-builder-option-list">
                                                            @foreach ($field->options as $optionIndex => $option)
                                                                <div class="form-builder-option">
                                                                    <input type="hidden" name="blocks[{{ $blockIndex }}][rows][{{ $rowIndex }}][fields][{{ $fieldIndex }}][options][{{ $optionIndex }}][id]" value="{{ $option->id }}">
                                                                    <input name="blocks[{{ $blockIndex }}][rows][{{ $rowIndex }}][fields][{{ $fieldIndex }}][options][{{ $optionIndex }}][label]" type="text" value="{{ $option->label }}" placeholder="{{ __('Optie label') }}">
                                                                    <input name="blocks[{{ $blockIndex }}][rows][{{ $rowIndex }}][fields][{{ $fieldIndex }}][options][{{ $optionIndex }}][value]" type="text" value="{{ $option->value }}" placeholder="{{ __('Waarde') }}">
                                                                    <input name="blocks[{{ $blockIndex }}][rows][{{ $rowIndex }}][fields][{{ $fieldIndex }}][options][{{ $optionIndex }}][image_path]" type="text" value="{{ $option->settings['image_path'] ?? '' }}" placeholder="{{ __('Afbeelding pad') }}">
                                                                    <label>
                                                                        <input name="blocks[{{ $blockIndex }}][rows][{{ $rowIndex }}][fields][{{ $fieldIndex }}][options][{{ $optionIndex }}][delete]" type="checkbox" value="1">
                                                                        <span class="checkbox"></span>
                                                                        {{ __('Verwijderen') }}
                                                                    </label>
                                                                </div>
                                                            @endforeach
                                                            @for ($optionIndex = $field->options->count(); $optionIndex < $field->options->count() + 2; $optionIndex++)
                                                                <div class="form-builder-option">
                                                                    <input name="blocks[{{ $blockIndex }}][rows][{{ $rowIndex }}][fields][{{ $fieldIndex }}][options][{{ $optionIndex }}][label]" type="text" placeholder="{{ __('Nieuwe optie') }}">
                                                                    <input name="blocks[{{ $blockIndex }}][rows][{{ $rowIndex }}][fields][{{ $fieldIndex }}][options][{{ $optionIndex }}][value]" type="text" placeholder="{{ __('Waarde') }}">
                                                                    <input name="blocks[{{ $blockIndex }}][rows][{{ $rowIndex }}][fields][{{ $fieldIndex }}][options][{{ $optionIndex }}][image_path]" type="text" placeholder="{{ __('Afbeelding pad') }}">
                                                                </div>
                                                            @endfor
                                                        </div>
                                                    </div>
                                                @endforeach

                                                @php $newFieldIndex = $row->fields->count(); @endphp
                                                <div class="form-builder-field is-new">
                                                    <div class="grid">
                                                        <div class="grid-row">
                                                            <div class="col-4">
                                                                <input name="blocks[{{ $blockIndex }}][rows][{{ $rowIndex }}][fields][{{ $newFieldIndex }}][name]" type="text" placeholder="{{ __('Nieuwe veldnaam') }}">
                                                            </div>
                                                            <div class="col-4">
                                                                <input name="blocks[{{ $blockIndex }}][rows][{{ $rowIndex }}][fields][{{ $newFieldIndex }}][label]" type="text" placeholder="{{ __('Nieuw veld label') }}">
                                                            </div>
                                                            <div class="col-4">
                                                                <select name="blocks[{{ $blockIndex }}][rows][{{ $rowIndex }}][fields][{{ $newFieldIndex }}][type]">
                                                                    @foreach ($fieldTypes as $type => $label)
                                                                        <option value="{{ $type }}">{{ $label }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach

                                    @php $newRowIndex = $block->rows->count(); @endphp
                                    <div class="form-builder-row is-new">
                                        <div class="form-builder-row-heading">
                                            <strong>{{ __('Nieuwe rij') }}</strong>
                                            <input name="blocks[{{ $blockIndex }}][rows][{{ $newRowIndex }}][sort_order]" type="number" value="{{ $newRowIndex + 1 }}">
                                            <input name="blocks[{{ $blockIndex }}][rows][{{ $newRowIndex }}][width]" type="number" min="10" max="100" value="100">
                                        </div>
                                        <input name="blocks[{{ $blockIndex }}][rows][{{ $newRowIndex }}][fields][0][label]" type="text" placeholder="{{ __('Eerste veld label') }}">
                                        <select name="blocks[{{ $blockIndex }}][rows][{{ $newRowIndex }}][fields][0][type]">
                                            @foreach ($fieldTypes as $type => $label)
                                                <option value="{{ $type }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endforeach

                            @php $newBlockIndex = $form->blocks->count(); @endphp
                            <div class="form-builder-block is-new">
                                <div class="form-builder-block-heading">
                                    <input name="blocks[{{ $newBlockIndex }}][title]" type="text" placeholder="{{ __('Nieuw blok') }}">
                                    <input name="blocks[{{ $newBlockIndex }}][sort_order]" type="number" value="{{ $newBlockIndex + 1 }}">
                                </div>
                                <input name="blocks[{{ $newBlockIndex }}][rows][0][fields][0][label]" type="text" placeholder="{{ __('Eerste veld label') }}">
                                <select name="blocks[{{ $newBlockIndex }}][rows][0][fields][0][type]">
                                    @foreach ($fieldTypes as $type => $label)
                                        <option value="{{ $type }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </form>
                @endif
            </div>

            @if ($isExisting)
                <div class="author-container">
                    <span><strong>{{ __('Auteur') }}:</strong> {{ $form->created_by ?? '-' }}</span>
                    <span><strong>{{ __('Gemaakt op') }}:</strong> {{ optional($form->created_at)->format('d-m-Y H:i') }}</span>
                </div>
            @endif
        </div>
    </div>
@endsection
