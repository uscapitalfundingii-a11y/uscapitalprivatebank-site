@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <h5 class="card-title mb-0">@lang('Pending Account Approvals')</h5>
                    <form method="GET" class="d-flex gap-2">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="@lang('Search name, username, email, account, currency')">
                        <button type="submit" class="btn btn--primary">@lang('Search')</button>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table--light mb-0">
                            <thead>
                                <tr>
                                    <th>@lang('Client')</th>
                                    <th>@lang('Request')</th>
                                    <th>@lang('Currency')</th>
                                    <th>@lang('Requested')</th>
                                    <th>@lang('Referral')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $requestItem)
                                    <tr>
                                        <td>
                                            <div>
                                                <strong>{{ $requestItem->user->fullname }}</strong>
                                                <div class="small text-muted">{{ '@' . $requestItem->user->username }}</div>
                                                <div class="small text-muted">{{ $requestItem->user->account_number }}</div>
                                            </div>
                                        </td>
                                        <td>{{ $requestItem->type_label }}</td>
                                        <td>{{ $requestItem->currency_code }} - {{ $requestItem->currency_name }}</td>
                                        <td>{{ showDateTime($requestItem->created_at, 'd M, Y h:i A') }}</td>
                                        <td>
                                            @if($requestItem->user->referrer)
                                                <a href="{{ route('admin.users.detail', $requestItem->user->referrer->id) }}">{{ $requestItem->user->referrer->username }}</a>
                                            @else
                                                <span class="text-muted">@lang('Direct')</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-2">
                                                <a href="{{ route('admin.users.detail', $requestItem->user_id) }}" class="btn btn-sm btn-outline--primary">@lang('View User')</a>
                                                <form action="{{ route('admin.users.account.requests.approve', [$requestItem->user_id, $requestItem->id]) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="queue" value="1">
                                                    <button type="submit" class="btn btn-sm btn--success">@lang('Approve')</button>
                                                </form>
                                                <form action="{{ route('admin.users.account.requests.reject', [$requestItem->user_id, $requestItem->id]) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="queue" value="1">
                                                    <button type="submit" class="btn btn-sm btn--danger">@lang('Reject')</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">@lang('No pending account requests found')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($requests->hasPages())
                    <div class="card-footer">
                        {{ paginateLinks($requests) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
