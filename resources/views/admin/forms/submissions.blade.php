@extends('layouts.admin')

@section('title', __('Form messages'))

@section('body')
    <div class="site-wrapper-container">
        <div class="left">
            @include('admin.partials.navigation')
        </div>

        <div class="main has-buttons">
            <div class="buttons-container align-right">
                <a href="{{ $backUrl }}" class="btn btn-cancel">
                    <x-admin.material-icon name="undo" />
                    {{ __('Terug') }}
                </a>
            </div>

            <div class="main-section">
                @include('admin.forms.partials.page-header', [
                    'title' => __('Form messages'),
                    'section' => $form ? $form->name : __('Berichten'),
                ])

                @if ($form)
                    @include('admin.forms.partials.tabs', [
                        'form' => $form,
                        'routeNames' => $routeNames,
                        'activeTab' => 'submissions',
                    ])
                @endif

                <span class="content-admin-screen-label">{{ $pageName }}</span>

                @if (! $form)
                    <div class="attachment-message">
                        <x-admin.material-icon name="info" />
                        <em>{{ __('Selecteer eerst een formulier.') }}</em>
                    </div>
                @else
                    <div class="form-submission-list">
                        @forelse ($submissions as $submission)
                            <details class="form-submission-item" @if ($loop->first) open @endif>
                                <summary>
                                    <span>{{ optional($submission->created_at)->format('d-m-Y H:i') }}</span>
                                    <span>{{ $submission->source_url ?: '-' }}</span>
                                    <span>{{ $submission->status }}</span>
                                </summary>
                                <dl class="cms-module-details">
                                    <dt>{{ __('IP') }}</dt>
                                    <dd>{{ $submission->remote_ip ?: '-' }}</dd>
                                    <dt>{{ __('Browser') }}</dt>
                                    <dd>{{ $submission->user_agent ?: '-' }}</dd>
                                    @foreach ($submission->answers as $answer)
                                        <dt>{{ $answer->field_name }}</dt>
                                        <dd>{{ $answer->value }}</dd>
                                    @endforeach
                                </dl>
                            </details>
                        @empty
                            <div class="attachment-message">
                                <x-admin.material-icon name="info" />
                                <em>{{ __('No messages found.') }}</em>
                            </div>
                        @endforelse
                    </div>

                    @include('admin.partials.pagination', ['paginator' => $submissions])
                @endif
            </div>
        </div>
    </div>
@endsection
