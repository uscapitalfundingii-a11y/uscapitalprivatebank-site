@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-10">
            @if($user->accounts->contains(fn($account) => $account->account_type === 'crypto_wallet'))
                <div class="alert alert--warning mb-4" role="alert">
                    <h6 class="mb-2">@lang('Crypto Wallet Usage')</h6>
                    <p class="mb-0">@lang('To fund or operate a crypto wallet correctly, switch it to Active first. Deposits, withdrawals, and transaction activity will apply to the account that is currently active.')</p>
                </div>
            @endif
            <div class="dashboard-table">
                <h5 class="dashboard-table__title card-header__title text-dark">@lang('My Accounts')</h5>
                <table class="table table--responsive--md">
                    <thead>
                        <tr>
                            <th>@lang('Account Number')</th>
                            <th>@lang('Type')</th>
                            <th>@lang('Currency')</th>
                            <th>@lang('Balance')</th>
                            <th>@lang('Status')</th>
                            <th>@lang('Action')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($user->accounts as $account)
                            <tr>
                                <td><a href="{{ route('user.accounts.show', $account->id) }}">{{ $account->account_number }}</a></td>
                                <td>{{ ucwords(str_replace('_', ' ', $account->account_type)) }}</td>
                                <td>{{ $account->currency_code ?? gs('cur_text') }}</td>
                                <td>{{ showAmount($account->balance) }}</td>
                                <td>
                                    @if($account->is_primary)
                                        <span class="badge badge--success">@lang('Active')</span>
                                    @else
                                        <span class="badge badge--dark">@lang('Secondary')</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        @if($account->is_primary)
                                            <span class="text-muted align-self-center">@lang('Current')</span>
                                        @else
                                            <form action="{{ route('user.profile.account.switch', $account->id) }}" method="POST">
                                                @csrf
                                                <button class="btn btn-sm btn--base" type="submit">@lang('Switch')</button>
                                            </form>
                                        @endif
                                        <a href="{{ route('user.deposit.index') }}" class="btn btn-sm btn-outline--base">@lang('Deposit')</a>
                                        <a href="{{ route('user.withdraw') }}" class="btn btn-sm btn-outline--base">@lang('Withdraw')</a>
                                        <a href="{{ route('user.accounts.show', $account->id) }}" class="btn btn-sm btn-outline--base">@lang('Transactions')</a>
                                    </div>
                                </td>
                            </tr>
                            @if($account->account_type === 'crypto_wallet')
                                <tr>
                                    <td colspan="6" class="bg-light">
                                        <small class="text-muted">@lang('Crypto wallet operations use your selected payment gateways on the deposit page. Make this wallet active before funding it so the credited balance is applied to this wallet.')</small>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">{{ __($emptyMessage ?? 'No accounts found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
