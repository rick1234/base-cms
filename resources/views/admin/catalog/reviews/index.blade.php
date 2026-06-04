@extends('layouts.admin')

@section('title', __('Catalog Reviews'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <a class="btn btn-add" href="{{ route($routeNames['create']) }}">
                    <x-admin.material-icon name="add" />
                    {{ __('Toevoegen') }}
                </a>
                @include('admin.catalog.partials.toolbar-links')
            </div>

            <div class="main-section">
                @include('admin.catalog.partials.page-header', [
                    'title' => __('Catalog Reviews'),
                    'section' => __('Reviews overzicht'),
                ])

                <div class="overview-container reviews-overview-container">
                    <div class="overview-row header">
                        <div class="overview-item id">Id</div>
                        <div class="overview-item title">{{ __('Product') }}</div>
                        <div class="overview-item user">{{ __('Reviewer') }}</div>
                        <div class="overview-item rating">{{ __('Rating') }}</div>
                        <div class="overview-item status">{{ __('Status') }}</div>
                        <div class="overview-item options">{{ __('Opties') }}</div>
                    </div>

                    <form method="get" action="{{ route($routeNames['index']) }}">
                        <div class="overview-row filters">
                            <div class="overview-item id">
                                <input name="id" type="text" value="{{ request('id') }}">
                            </div>
                            <div class="overview-item title">
                                <input name="product" type="text" value="{{ request('product', request('artikel')) }}">
                            </div>
                            <div class="overview-item user"></div>
                            <div class="overview-item rating"></div>
                            <div class="overview-item status">
                                <select name="status">
                                    <option value="">{{ __('Selecteer') }}</option>
                                    <option value="pending" @selected(request('status') === 'pending')>{{ __('Pending') }}</option>
                                    <option value="published" @selected(request('status') === 'published' || request('status') === '1')>{{ __('Published') }}</option>
                                    <option value="rejected" @selected(request('status') === 'rejected' || request('status') === '0')>{{ __('Rejected') }}</option>
                                </select>
                            </div>
                            <div class="overview-item options">
                                <button type="submit" title="{{ __('Zoeken') }}">
                                    <x-admin.material-icon name="search" />
                                </button>
                            </div>
                        </div>
                    </form>

                    @forelse ($reviews as $review)
                        <div class="overview-row">
                            <div class="overview-item id">{{ $review->id }}</div>
                            <div class="overview-item title">{{ $review->product?->name ?? '-' }}</div>
                            <div class="overview-item user">{{ $review->author_name ?: $review->author_email ?: '-' }}</div>
                            <div class="overview-item rating">{{ $review->rating ?: '-' }}</div>
                            <div class="overview-item status">
                                <span class="{{ $review->status === 'published' ? 'active-item' : 'inactive-item' }}"></span>
                            </div>
                            <div class="overview-item options">
                                <a href="{{ route($routeNames['edit'], ['id' => $review->id]) }}" title="{{ __('Bewerken') }}">
                                    <x-admin.material-icon name="edit" />
                                </a>
                                <form method="post" action="{{ route($routeNames['destroy'], $review) }}">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" title="{{ __('Verwijderen') }}">
                                        <x-admin.material-icon name="delete" />
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="overview-row">
                            <div class="overview-item title">{{ __('Geen reviews gevonden.') }}</div>
                        </div>
                    @endforelse
                </div>

                @include('admin.partials.pagination', ['paginator' => $reviews])
            </div>
        </div>
    </div>
@endsection
