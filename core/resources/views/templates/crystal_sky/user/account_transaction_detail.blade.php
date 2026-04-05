@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="dashboard-table">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h5 class="dashboard-table__title card-header__title text-dark mb-0">@lang('Transaction Details')</h5>
                    <a href="{{ route('user.accounts.show', $account->id) }}" class="btn btn--base btn-sm">@lang('Back to Account')</a>
                </div>
                <table class="table">
                    <tbody>
                        <tr><th>@lang('Account')</th><td>{{ $account->account_name }} ({{ $account->account_number }})</td></tr>
                        <tr><th>@lang('Transaction No.')</th><td>#{{ $transaction->trx }}</td></tr>
                        <tr><th>@lang('Date')</th><td>{{ showDateTime($transaction->created_at) }}</td></tr>
                        <tr><th>@lang('Direction')</th><td>{{ $transaction->trx_type == '+' ? __('Incoming') : __('Outgoing') }}</td></tr>
                        <tr><th>@lang('Amount')</th><td>{{ $transaction->trx_type }} {{ showAmount($transaction->amount) }}</td></tr>
                        <tr><th>@lang('Charge')</th><td>{{ showAmount($transaction->charge) }}</td></tr>
                        <tr><th>@lang('Post Balance')</th><td>{{ showAmount($transaction->post_balance) }}</td></tr>
                        <tr><th>@lang('Reference')</th><td>{{ __(keyToTitle($transaction->remark)) }}</td></tr>
                        <tr><th>@lang('Notes')</th><td style="white-space: pre-wrap;">{{ $transaction->details }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

