<section class="domain-tab-panel" id="domain-step-social-contact">
    <h2 class="title">{{ __('Social and contact') }}</h2>

    <div class="domain-social-grid" data-domain-social-sortable-list data-drag-container data-drag-layout="grid" aria-label="{{ __('Social links order') }}">
        @foreach ($socialLinks as $index => $link)
            <div class="domain-social-card" data-domain-social-item data-drag-item>
                <div class="domain-social-card-header">
                    <button class="domain-social-drag-handle" data-domain-social-handle draggable="true" type="button" aria-label="{{ __('Drag to sort') }}" title="{{ __('Drag to sort') }}">
                        <x-admin.material-icon name="drag_indicator" />
                    </button>
                    <span class="domain-social-card-title">
                        {{ __('Social link') }} <span data-domain-social-index-label>{{ $index + 1 }}</span>
                    </span>
                </div>

                <div class="domain-social-card-fields">
                    <label>
                        <span>{{ __('Platform') }}</span>
                        <select name="social_links[{{ $index }}][platform]" data-domain-social-field="platform">
                            <option value="">{{ __('Platform') }}</option>
                            @foreach (config('cms_domains.social_platforms') as $value => $label)
                                <option value="{{ $value }}" @selected(($link['platform'] ?? '') === $value)>{{ __($label) }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span>{{ __('Label') }}</span>
                        <input name="social_links[{{ $index }}][label]" type="text" value="{{ $link['label'] ?? '' }}" placeholder="{{ __('Label') }}" data-domain-social-field="label">
                    </label>

                    <label>
                        <span>{{ __('Icon') }}</span>
                        <input name="social_links[{{ $index }}][icon]" type="text" value="{{ $link['icon'] ?? '' }}" placeholder="{{ __('Icon') }}" data-domain-social-field="icon">
                    </label>

                    <label>
                        <span>{{ __('URL') }}</span>
                        <input name="social_links[{{ $index }}][url]" type="url" value="{{ $link['url'] ?? '' }}" placeholder="https://example.com" data-domain-social-field="url">
                    </label>

                    @error('social_links.'.$index.'.url')<span class="error">{{ $message }}</span>@enderror
                </div>
            </div>
        @endforeach
    </div>

    <div class="domain-contact-card">
        <div class="domain-contact-card-header">
            <x-admin.material-icon name="contact_mail" />
            <strong>{{ __('Contact form') }}</strong>
        </div>
        <div class="domain-contact-card-body">
            <label for="contact_form_id">{{ __('Contact form') }}</label>
            <select id="contact_form_id" name="contact_form_id">
                <option value="">{{ __('No contact form') }}</option>
                @foreach ($forms as $form)
                    <option value="{{ $form->id }}" @selected((int) old('contact_form_id', $domain->contact_form_id) === $form->id)>{{ $form->name }}</option>
                @endforeach
            </select>
            @error('contact_form_id')<span class="error">{{ $message }}</span>@enderror
        </div>
    </div>

    @include('admin.domains.partials.step-actions')
</section>
