@php
    $legacySessionLevels = [
        'success' => 'success',
        'status' => 'success',
        'error' => 'danger',
        'danger' => 'danger',
        'warning' => 'warning',
        'info' => 'info',
        'message' => 'info',
    ];

    $laracastsMessages = collect(session('flash_notification', []))
        ->map(fn ($message) => [
            'message' => data_get($message, 'message'),
            'level' => data_get($message, 'level', 'info'),
            'title' => data_get($message, 'title'),
            'overlay' => (bool) data_get($message, 'overlay', false),
            'important' => (bool) data_get($message, 'important', false),
        ]);

    $sessionMessages = collect($legacySessionLevels)
        ->filter(fn (string $level, string $key) => session()->has($key))
        ->map(fn (string $level, string $key) => [
            'message' => session($key),
            'level' => $level,
            'title' => null,
            'overlay' => false,
            'important' => false,
        ])
        ->values();

    $messages = $laracastsMessages
        ->merge($sessionMessages)
        ->filter(fn (array $message) => filled($message['message']))
        ->map(function (array $message) {
            $message['level'] = in_array($message['level'], ['danger', 'info', 'success', 'warning'], true)
                ? $message['level']
                : 'info';

            return $message;
        })
        ->values();
@endphp

@if ($messages->isNotEmpty())
    <div class="flash-messages" data-flash-messages aria-live="polite">
        @foreach ($messages as $message)
            <div class="flash-message flash-message-{{ $message['level'] }}" data-flash-message role="alert">
                <div class="flash-message-content">
                    @if ($message['title'])
                        <strong class="flash-message-title">{{ $message['title'] }}</strong>
                    @endif
                    <span>{{ $message['message'] }}</span>
                </div>
                <button class="flash-message-close" type="button" data-flash-close aria-label="{{ __('Close message') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endforeach
    </div>
@endif

@php(session()->forget(array_merge(['flash_notification'], array_keys($legacySessionLevels))))
