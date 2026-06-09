@extends('layouts.admin')

@section('title', __('Base convention categories'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container">
                <div class="buttons-container align-right">
                    <button class="btn btn-add" type="button">
                        <x-admin.material-icon name="add" />
                        {{ __('Toevoegen') }}
                    </button>
                    <a class="btn btn-cancel" href="{{ route($routeNames['index']) }}">
                        <x-admin.material-icon name="undo" />
                        {{ __('Terug') }}
                    </a>
                </div>
            </div>

            <div class="main-section">
                @include('admin.content.partials.page-header', [
                    'title' => __('Base convention categories'),
                    'section' => __('Base convention category overview'),
                    'icon' => 'folder',
                ])

                <div class="overview-container content-overview-container listing-overview-container">
                    <div class="overview-row header">
                        <div class="overview-item id">Id</div>
                        <div class="overview-item title">{{ __('Titel') }}</div>
                        <div class="overview-item status">{{ __('Status') }}</div>
                        <div class="overview-item options">{{ __('Opties') }}</div>
                    </div>

                    @foreach ($categories as $category)
                        <div class="overview-row">
                            <div class="overview-item id">{{ $category['id'] }}</div>
                            <div class="overview-item title">
                                {{ $category['name'] }}
                                <span class="status-note">{{ trans_choice('{0} Geen items|{1} :count item|[2,*] :count items', $category['count'], ['count' => $category['count']]) }}</span>
                            </div>
                            <div class="overview-item status">
                                <span class="base-conventions-status is-online">{{ __('Active') }}</span>
                            </div>
                            <div class="overview-item options">
                                <button type="button" title="{{ __('Bewerken') }}">
                                    <x-admin.material-icon name="edit" />
                                </button>
                                <button type="button" title="{{ __('Verwijderen') }}">
                                    <x-admin.material-icon name="delete" />
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
