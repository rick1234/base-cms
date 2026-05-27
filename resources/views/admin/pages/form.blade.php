<form class="form-stack" method="post" action="{{ $action }}">
    @csrf
    @if ($method !== 'post')
        @method($method)
    @endif

    <div class="form-grid">
        <div class="field">
            <label class="field-label" for="title">{{ __('Title') }}</label>
            <input class="field-input" id="title" name="title" type="text" value="{{ old('title', $page->title) }}" required>
            @error('title')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label class="field-label" for="slug">{{ __('Slug') }}</label>
            <input class="field-input" id="slug" name="slug" type="text" value="{{ old('slug', $page->slug) }}">
            @error('slug')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="field">
        <label class="field-label" for="excerpt">{{ __('Excerpt') }}</label>
        <textarea class="field-textarea" id="excerpt" name="excerpt">{{ old('excerpt', $page->excerpt) }}</textarea>
        @error('excerpt')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="field">
        <label class="field-label" for="body">{{ __('Body') }}</label>
        <textarea class="field-textarea" id="body" name="body">{{ old('body', $page->body) }}</textarea>
        @error('body')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="form-grid">
        <div class="field">
            <label class="field-label" for="meta_title">{{ __('Meta title') }}</label>
            <input class="field-input" id="meta_title" name="meta_title" type="text" value="{{ old('meta_title', $page->meta_title) }}">
            @error('meta_title')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label class="field-label" for="meta_description">{{ __('Meta description') }}</label>
            <textarea class="field-textarea" id="meta_description" name="meta_description">{{ old('meta_description', $page->meta_description) }}</textarea>
            @error('meta_description')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="form-grid">
        <div class="field">
            <label class="field-label" for="template">{{ __('Template') }}</label>
            <input class="field-input" id="template" name="template" type="text" value="{{ old('template', $page->template ?? 'default') }}" required>
            @error('template')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label class="field-label" for="status">{{ __('Status') }}</label>
            <select class="field-select" id="status" name="status" required>
                @foreach (config('cms.page_statuses') as $status)
                    <option value="{{ $status }}" @selected(old('status', $page->status) === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            @error('status')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label class="field-label" for="sort_order">{{ __('Sort order') }}</label>
            <input class="field-input" id="sort_order" name="sort_order" type="number" min="0" value="{{ old('sort_order', $page->sort_order ?? 0) }}" required>
            @error('sort_order')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="field">
            <label class="field-label" for="published_at">{{ __('Published at') }}</label>
            <input class="field-input" id="published_at" name="published_at" type="datetime-local" value="{{ old('published_at', $page->published_at?->format('Y-m-d\TH:i')) }}">
            @error('published_at')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="form-actions">
        <button class="button" type="submit">{{ __('Save page') }}</button>
    </div>
</form>
