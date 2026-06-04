<p>{{ __('A download has been shared with you: :name', ['name' => $download->name]) }}</p>

@if ($messageText)
    <p>{{ $messageText }}</p>
@endif

<p>
    <a href="{{ $url }}">{{ __('Open download') }}</a>
</p>
