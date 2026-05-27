@php
    $settings = $form->settings ?? [];
@endphp

<form class="form-builder-form" method="post" action="{{ route('frontend.forms.submit', $form->slug) }}" enctype="multipart/form-data">
    @csrf
    @if (($settings['honeypot_enabled'] ?? true) === true)
        <div class="form-builder-honeypot" aria-hidden="true">
            <label for="form_{{ $form->id }}_honeypot">{{ __('Website') }}</label>
            <input id="form_{{ $form->id }}_honeypot" name="{{ $settings['honeypot_field'] ?? 'website' }}" type="text" tabindex="-1" autocomplete="off">
        </div>
    @endif

    @if (($settings['show_title'] ?? true) === true)
        <h2>{{ $form->name }}</h2>
    @endif

    @foreach ($form->blocks as $block)
        <section class="form-builder-block">
            @if (filled($block->title))
                <h3>{{ $block->title }}</h3>
            @endif
            @foreach ($block->rows as $row)
                <div class="form-builder-row">
                    @foreach ($row->fields as $field)
                        @php $fieldSettings = $field->settings ?? []; @endphp
                        <div class="form-builder-field">
                            @if ($field->type === 'title')
                                <h4>{{ $field->label }}</h4>
                            @elseif ($field->type === 'paragraph')
                                <p>{{ $fieldSettings['information'] ?? $field->help_text }}</p>
                            @elseif ($field->type === 'horizontal-rule')
                                <hr>
                            @else
                                <label for="field_{{ $field->id }}">
                                    {{ $field->label ?: $field->name }}
                                    @if ($field->is_required)
                                        <span aria-hidden="true">*</span>
                                    @endif
                                </label>
                                @if ($field->type === 'textarea')
                                    <textarea id="field_{{ $field->id }}" name="{{ $field->name }}" placeholder="{{ $fieldSettings['placeholder'] ?? '' }}" @required($field->is_required)>{{ old($field->name, $fieldSettings['default_value'] ?? '') }}</textarea>
                                @elseif ($field->type === 'select')
                                    <select id="field_{{ $field->id }}" name="{{ $field->name }}" @required($field->is_required)>
                                        <option value="">{{ __('Selecteer') }}</option>
                                        @foreach ($field->options as $option)
                                            <option value="{{ $option->value }}" @selected(old($field->name) === $option->value)>{{ $option->label }}</option>
                                        @endforeach
                                    </select>
                                @elseif ($field->type === 'radio' || $field->type === 'image-set-choice' || $field->type === 'image_set_choice')
                                    <div class="form-builder-option-group">
                                        @foreach ($field->options as $option)
                                            <label>
                                                <input name="{{ $field->name }}" type="radio" value="{{ $option->value }}" @checked(old($field->name) === $option->value) @required($field->is_required && $loop->first)>
                                                @if (filled($option->settings['image_path'] ?? null))
                                                    <img src="{{ asset($option->settings['image_path']) }}" alt="">
                                                @endif
                                                {{ $option->label }}
                                            </label>
                                        @endforeach
                                    </div>
                                @elseif ($field->type === 'checkbox')
                                    <div class="form-builder-option-group">
                                        @foreach ($field->options as $option)
                                            <label>
                                                <input name="{{ $field->name }}[]" type="checkbox" value="{{ $option->value }}" @checked(in_array($option->value, (array) old($field->name, []), true))>
                                                {{ $option->label }}
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <input id="field_{{ $field->id }}" name="{{ $field->name }}" type="{{ $field->type === 'input' ? 'text' : $field->type }}" value="{{ old($field->name, $fieldSettings['default_value'] ?? '') }}" placeholder="{{ $fieldSettings['placeholder'] ?? '' }}" @required($field->is_required)>
                                @endif
                                @error($field->name)
                                    <span class="form-builder-error">{{ $message }}</span>
                                @enderror
                                @if (filled($field->help_text))
                                    <small>{{ $field->help_text }}</small>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </section>
    @endforeach

    <button type="submit">{{ $form->submit_text ?: __('Versturen') }}</button>
</form>
