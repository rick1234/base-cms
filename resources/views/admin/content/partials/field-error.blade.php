@php
    $messages = collect($errors->get($field));

    if (! str_contains($field, '*')) {
        $childMessages = collect($errors->messages())
            ->filter(fn (array $messages, string $key): bool => str_starts_with($key, $field.'.'))
            ->flatten();

        $messages = $messages->merge($childMessages)->unique();
    }
@endphp

@foreach ($messages as $message)
    <span class="error">{{ $message }}</span>
@endforeach
