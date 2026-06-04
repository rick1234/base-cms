@php
    $eventImageUploadRoute = route(
        request()->routeIs('cms.*') ? 'cms.events.image.upload' : 'admin.events.image.upload',
        ['id' => $event->id],
    );
@endphp

<div class="event-image-album">
    @include('livewire.admin.content.content-image-album', [
        'contentItem' => $event,
        'contentImageUploadRoute' => $eventImageUploadRoute,
    ])
</div>
