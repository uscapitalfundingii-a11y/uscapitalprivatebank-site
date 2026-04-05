@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="custom--card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">@lang('Account Transactions')</h5>
                    <a href="{{ route('user.accounts.index') }}" class="btn btn--base btn-sm">@lang('Back to My Accounts')</a>
                </div>
                <div class="card-body p-0">
                    <div class="px-3 pt-3 text-muted">{{ $account->account_name }} | {{ $account->account_number }} | {{ $account->currency_code ?? gs('cur_text') }}</div>
                    <div class="table-responsive--md">
                        <table class="table custom--table mb-0">
                            <thead>
                                <tr>
                                    <th>@lang('TRX No.')</th>
                                    <th>@lang('Date')</th>
                                    <th>@lang('Type')</th>
                                    <th>@lang('Amount')</th>
                                    <th>@lang('Post Balance')</th>
                                    <th>@lang('Notes')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $trx)
                                    <tr>
                                        <td><a href="{{ route('user.accounts.transactions.show', [$account->id, $trx->id]) }}">#{{ $trx->trx }}</a></td>
                                        <td>{{ showDateTime($trx->created_at) }}</td>
                                        <td><span class="{{ $trx->trx_type == '+' ? 'text--success' : 'text--danger' }}">{{ $trx->trx_type == '+' ? __('Incoming') : __('Outgoing') }}</span></td>
                                        <td>{{ $trx->trx_type }} {{ showAmount($trx->amount) }}</td>
                                        <td>{{ showAmount($trx->post_balance) }}</td>
                                        <td style="white-space: pre-wrap;">{{ \Illuminate\Support\Str::limit($trx->details, 80) }}</td>
                                        <td><a href="{{ route('user.accounts.transactions.show', [$account->id, $trx->id]) }}" class="btn btn-sm btn-outline--base">@lang('View Detail')</a></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">@lang('No transactions found for this account yet.')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($transactions->hasPages())
                    <div class="card-footer">
                        {{ paginateLinks($transactions) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
