@extends('layouts.admin')

@section('title', __('FAQ videos'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <button class="btn btn-save" form="faq-videos-form" type="submit">
                    <span class="flaticon-save-button"></span>
                    {{ __('Opslaan') }}
                </button>
                <a href="{{ $backUrl }}" class="btn btn-cancel">
                    <span class="flaticon-undo-button"></span>
                    {{ __('Annuleren') }}
                </a>
            </div>

            <div class="main-section">
                @include('admin.faq.partials.page-header', [
                    'title' => $faqItem->question,
                    'section' => $pageName,
                ])

                @include('admin.faq.partials.tabs', [
                    'faqItem' => $faqItem,
                    'routeNames' => $routeNames,
                    'activeTab' => 'videos',
                ])

                <form id="faq-videos-form" method="post" action="{{ route($routeNames['videos.save'], ['id' => $faqItem->id]) }}">
                    @csrf
                    <input type="hidden" name="id" value="{{ $faqItem->id }}">

                    <h2 class="title">{{ __('Video') }}</h2>
                    <div class="grid">
                        <div class="grid-row">
                            <div class="col-3"><strong>{{ __('Titel') }}</strong></div>
                            <div class="col-5"><strong>{{ __('Video url') }}</strong></div>
                            <div class="col-2"><strong>{{ __('Provider') }}</strong></div>
                            <div class="col-2"><strong>{{ __('Verwijderen') }}</strong></div>
                        </div>
                        @foreach ($faqItem->videos as $index => $video)
                            <div class="grid-row">
                                <div class="col-3">
                                    <input type="hidden" name="videos[{{ $index }}][id]" value="{{ $video->id }}">
                                    <input name="videos[{{ $index }}][title]" type="text" value="{{ old("videos.{$index}.title", $video->title) }}">
                                </div>
                                <div class="col-5">
                                    <input name="videos[{{ $index }}][url]" type="url" value="{{ old("videos.{$index}.url", $video->url) }}">
                                </div>
                                <div class="col-2">
                                    <input name="videos[{{ $index }}][provider]" type="text" value="{{ old("videos.{$index}.provider", $video->provider) }}">
                                </div>
                                <div class="col-2">
                                    <label>
                                        <input name="videos[{{ $index }}][delete]" type="checkbox" value="1">
                                        <span class="checkbox"></span>
                                        {{ __('Verwijderen') }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                        @for ($newIndex = 0; $newIndex < 2; $newIndex++)
                            @php $index = $faqItem->videos->count() + $newIndex; @endphp
                            <div class="grid-row">
                                <div class="col-3">
                                    <input name="videos[{{ $index }}][title]" type="text" value="{{ old("videos.{$index}.title") }}">
                                </div>
                                <div class="col-5">
                                    <input name="videos[{{ $index }}][url]" type="url" value="{{ old("videos.{$index}.url") }}">
                                </div>
                                <div class="col-2">
                                    <input name="videos[{{ $index }}][provider]" type="text" value="{{ old("videos.{$index}.provider") }}">
                                </div>
                                <div class="col-2"></div>
                            </div>
                        @endfor
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
