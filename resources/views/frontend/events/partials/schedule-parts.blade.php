<ol class="event-schedule-list">
    @foreach ($parts as $part)
        <li class="event-schedule-item">
            @if ($part->starts_at)
                <time datetime="{{ $part->starts_at->toIso8601String() }}">
                    {{ $part->starts_at->translatedFormat('j F Y H:i') }}
                </time>
            @endif
            <div>
                @if ($part->title)
                    <h4>{{ $part->title }}</h4>
                @endif
                @if ($part->content)
                    <p>{!! nl2br(e($part->content)) !!}</p>
                @endif
            </div>
        </li>
    @endforeach
</ol>
