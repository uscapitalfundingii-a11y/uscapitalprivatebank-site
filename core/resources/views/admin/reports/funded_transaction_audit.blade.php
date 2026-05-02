@extends('admin.layouts.app')

@section('panel')
    <div class="row gy-4">
        <div class="col-12">
            <div class="card b-radius--10">
                <div class="card-body">
                    <form method="GET" class="row gy-3 align-items-end">
                        <div class="col-xl-2 col-md-4">
                            <label class="form-label">@lang('Start Date')</label>
                            <input type="date" name="start_date" value="{{ request('start_date', $startDate->toDateString()) }}" class="form-control">
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <label class="form-label">@lang('End Date')</label>
                            <input type="date" name="end_date" value="{{ request('end_date', $endDate->toDateString()) }}" class="form-control">
                        </div>
                        <div class="col-xl-3 col-md-4">
                            <label class="form-label">@lang('Credit Source')</label>
                            <select name="remark" class="form-control">
                                <option value="all">@lang('All Credit Sources')</option>
                                @foreach ($remarks as $remark)
                                    <option value="{{ $remark }}" @selected(request('remark') === $remark)>
                                        {{ __(ucwords(str_replace('_', ' ', $remark))) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <label class="form-label">@lang('Actor Trail')</label>
                            <select name="actor" class="form-control">
                                <option value="all" @selected(request('actor', 'all') === 'all')>@lang('All Actor Trails')</option>
                                <option value="no_staff" @selected(request('actor') === 'no_staff')>@lang('No Staff/Admin Actor')</option>
                                <option value="staff" @selected(request('actor') === 'staff')>@lang('Staff Recorded')</option>
                                <option value="sender_user" @selected(request('actor') === 'sender_user')>@lang('Sending User Recorded')</option>
                                <option value="admin_adjustment" @selected(request('actor') === 'admin_adjustment')>@lang('Admin Balance Adjustment')</option>
                                <option value="gateway_or_manual" @selected(request('actor') === 'gateway_or_manual')>@lang('Gateway/Manual Deposit')</option>
                            </select>
                        </div>
                        <div class="col-xl-2 col-md-6">
                            <button type="submit" class="btn btn--primary w-100 h-45">
                                <i class="las la-filter"></i> @lang('Filter')
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card b-radius--10">
                <div class="card-body">
                    <span class="text-muted">@lang('Credit Rows')</span>
                    <h4 class="mb-0">{{ $summary['transactions'] }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card b-radius--10">
                <div class="card-body">
                    <span class="text-muted">@lang('Credit Amount')</span>
                    <h4 class="mb-0">{{ showAmount($summary['amount']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-12 col-xl-6">
            <div class="card b-radius--10">
                <div class="card-body">
                    <span class="text-muted">@lang('Audit Window')</span>
                    <h4 class="mb-0">{{ showDateTime($startDate, 'M d, Y') }} - {{ showDateTime($endDate, 'M d, Y') }}</h4>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card b-radius--10">
                <div class="card-body p-0">
                    <div class="table-responsive--lg table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Date')</th>
                                    <th>@lang('Client')</th>
                                    <th>@lang('Account')</th>
                                    <th>@lang('Credit')</th>
                                    <th>@lang('Current Balance')</th>
                                    <th>@lang('Source')</th>
                                    <th>@lang('Actor Trail')</th>
                                    <th>@lang('TRX')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($transactions as $transaction)
                                    <tr>
                                        <td>
                                            <span class="d-block">{{ showDateTime($transaction->created_at, 'M d, Y') }}</span>
                                            <small class="text-muted">{{ showDateTime($transaction->created_at, 'h:i A') }}</small>
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.users.detail', $transaction->user_id) }}" class="fw-bold">
                                                {{ $transaction->username ?? __('Unknown') }}
                                            </a>
                                            <small class="d-block text-muted">{{ trim(($transaction->firstname ?? '') . ' ' . ($transaction->lastname ?? '')) ?: $transaction->email }}</small>
                                        </td>
                                        <td>
                                            <span class="fw-bold">{{ $transaction->audited_account_number ?? __('Not recorded') }}</span>
                                            <small class="d-block text-muted">
                                                {{ $transaction->account_name ?? __('Primary Account') }}
                                                @if ($transaction->currency_code)
                                                    - {{ $transaction->currency_code }}
                                                @endif
                                            </small>
                                        </td>
                                        <td class="fw-bold text--success">{{ showAmount($transaction->amount) }}</td>
                                        <td>{{ showAmount($transaction->account_balance ?? $transaction->user_balance ?? 0) }}</td>
                                        <td>
                                            <span class="badge badge--{{ $transaction->remark === 'balance_add' ? 'danger' : ($transaction->remark === 'received_money' ? 'warning' : 'info') }}">
                                                {{ __(ucwords(str_replace('_', ' ', $transaction->remark ?? 'unknown'))) }}
                                            </span>
                                            <small class="d-block text-muted">{{ __($transaction->details) }}</small>
                                        </td>
                                        <td>
                                            @php
                                                $actorIsWeak = str_contains((string) $transaction->actor_summary, 'not recorded') || str_contains((string) $transaction->actor_summary, 'No staff');
                                            @endphp
                                            <span class="badge badge--{{ $actorIsWeak ? 'danger' : 'success' }}">{{ __($actorIsWeak ? 'Review' : 'Recorded') }}</span>
                                            <span class="d-block mt-1">{{ __($transaction->actor_summary) }}</span>
                                            @if ($transaction->sender_username)
                                                <small class="text-muted">@lang('Sender'): {{ $transaction->sender_username }}</small>
                                            @elseif ($transaction->audit_branch_name)
                                                <small class="text-muted">@lang('Branch'): {{ $transaction->audit_branch_name }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fw-bold">{{ $transaction->trx }}</span>
                                            @if ($transaction->audit_deposit_id)
                                                <a href="{{ route('admin.deposit.details', $transaction->audit_deposit_id) }}" class="d-block text--small">@lang('Deposit File')</a>
                                            @elseif ($transaction->audit_transfer_id)
                                                <a href="{{ route('admin.transfers.details', $transaction->audit_transfer_id) }}" class="d-block text--small">@lang('Transfer File')</a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">{{ __($emptyMessage ?? 'No funded account credits found for this window') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($transactions->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($transactions) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
