@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-10">
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
                                <td>{{ $account->account_number }}</td>
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
                                    @if($account->is_primary)
                                        <span class="text-muted">@lang('Current')</span>
                                    @else
                                        <form action="{{ route('user.profile.account.switch', $account->id) }}" method="POST">
                                            @csrf
                                            <button class="btn btn-sm btn--base" type="submit">@lang('Switch')</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
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

