@extends('admin.layouts.app')

@section('panel')
    <div class="row justify-content-center">
        <div class="col-xxl-10">
            <div class="card mb-4">
                <div class="card-header d-flex flex-wrap justify-content-between gap-2 align-items-center">
                    <h5 class="mb-0">@lang('Notification Details')</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('admin.notifications') }}" class="btn btn-sm btn-outline--primary">
                            <i class="las la-arrow-left"></i> @lang('Back to Notifications')
                        </a>
                        @if ($user)
                            <a href="{{ route('admin.users.detail', $user->id) }}" class="btn btn-sm btn-outline--info">
                                <i class="las la-user"></i> @lang('Open User')
                            </a>
                            <a href="{{ route('admin.users.notification.single', $user->id) }}" class="btn btn-sm btn-outline--warning">
                                <i class="las la-redo"></i> @lang('Resend Manually')
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if ($notification->isMailFailure())
                        <div class="alert alert-warning mb-4">
                            <h6 class="mb-2">@lang('Email Delivery Failure')</h6>
                            <p class="mb-0">
                                {{ $notification->extractedFailureSummary() ?: __('This notification came from the email delivery system and contains the exact SMTP reject message below.') }}
                            </p>
                        </div>
                    @endif

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted d-block mb-1">@lang('Recipient')</small>
                                <strong>{{ $notification->extractedRecipient() ?: __('Not detected') }}</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted d-block mb-1">@lang('Created')</small>
                                <strong>{{ showDateTime($notification->created_at) }}</strong>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="border rounded p-3">
                                <small class="text-muted d-block mb-1">@lang('Reject Reason')</small>
                                <div>{{ $notification->extractedFailureReason() ?: __('No structured reason was detected, see raw message below.') }}</div>
                            </div>
                        </div>
                        @if ($notification->extractedHelpUrl())
                            <div class="col-md-12">
                                <div class="border rounded p-3">
                                    <small class="text-muted d-block mb-1">@lang('Provider Help Link')</small>
                                    <a href="{{ $notification->extractedHelpUrl() }}" target="_blank" rel="noopener">
                                        {{ $notification->extractedHelpUrl() }}
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">@lang('Raw Message')</label>
                        <pre class="bg--light p-3 rounded" style="white-space: pre-wrap;">{{ $notification->title }}</pre>
                    </div>

                    @if ($recentEmailLog)
                        <div class="border rounded p-3">
                            <h6 class="mb-3">@lang('Latest Stored Email For This User')</h6>
                            <p class="mb-2"><strong>@lang('Subject'):</strong> {{ $recentEmailLog->subject ?: __('No subject stored') }}</p>
                            <div>
                                <strong>@lang('Message'):</strong>
                                <div class="mt-2 p-3 bg--light rounded" style="white-space: pre-wrap;">{!! $recentEmailLog->message !!}</div>
                            </div>
                        </div>
                    @elseif ($user)
                        <div class="alert alert-info mb-0">
                            @lang('We found the user for this email address, but there is no stored successful email body to replay automatically. Use the manual resend button to send the message again.')
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
