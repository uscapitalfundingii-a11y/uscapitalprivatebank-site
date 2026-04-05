@extends('admin.layouts.app')
@section('panel')
    <div class="row gy-4 mb-4">
        <div class="col-xxl-3 col-sm-6 ">
            <x-widget style="1" link="admin.card.transaction" query_string="filter[virtual_card_id]={{ $card->id }}" title="Total Transactions" icon="las la-exchange-alt" value="{{ $totalTransactions }}" bg="primary" type="2" />
        </div>
        <div class="col-xxl-3 col-sm-6 ">
            <x-widget style="1" link="admin.card.transaction" query_string="filter[virtual_card_id]={{ $card->id }}&filter[transaction_type]=Credited" title="Credit" icon="las la-money-bill-wave-alt" value="{{ showAmount($creditedAmount) }}" bg="success" type="2" />
        </div>
        <div class="col-xxl-3 col-sm-6 ">
            <x-widget style="1" link="admin.card.transaction" query_string="filter[virtual_card_id]={{ $card->id }}&filter[transaction_type]=Debited" title="Debit" icon="las la-money-bill-wave-alt" value="{{ showAmount($debitedAmount) }}" bg="warning" />
        </div>
        <div class="col-xxl-3 col-sm-6 ">
            <x-widget style="1" link="" title="Stripe Transaction" icon="las la-exchange-alt" value="{{ showAmount($totalStripeTransaction) }}" bg="13" />
        </div>
    </div>

    <div class="issue-card-row">
        <div class="issue-card-form">
            <div class="card">
                <div class="card-body p-0">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-bold">@lang('Account Holder')</span>
                            @can('admin.users.detail')
                                <a href="{{ route('admin.users.detail', $card->user_id) }}" class="fw-bold">{{ @$card->user->fullname }}</a>
                            @else
                                <span>{{ @$card->user->username }}</span>
                            @endcan
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-bold">@lang('Label')</span> {{ $card->label }}
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-bold">@lang('Last Four Digit')</span>{{ $card->last4 }}
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-bold">@lang('Exp Month')</span> {{ $card->exp_month }}
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-bold">@lang('Exp Year')</span> {{ $card->exp_year }}
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-bold">@lang('Brand')</span>
                            <span>{{ __($card->brand) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-bold">@lang('Current Balance')</span> <span class="fw-bold" class="text--success">{{ showAmount($card->spending_limit - $card->current_spend) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-bold">@lang('Status')</span>
                            @php echo $card->statusBadge @endphp
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-bold">@lang('Action')</span>
                            @if (@$card->status == 'inactive')
                                <button type="button" class="btn btn-sm btn-outline--success notify-delete-btn confirmationBtn" data-question="@lang('Are you sure to activate the card?')" data-action="{{ route('admin.card.change.status', $card->id) }}"><i class="las la-check-double"></i>@lang('Activate')</button>
                            @else
                                <button type="button" class="btn btn-sm btn-outline--danger notify-delete-btn" data-bs-toggle="modal" data-bs-target="#cardDeactivateModal"><i class="las la-times-circle"></i>@lang('Deactivate')</button>
                            @endif
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <x-confirmation-modal />

    <div id="cardDeactivateModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Deactivate Card')</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('admin.card.change.status', $card->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <h6 class="mb-2">@lang('If you deactivate this card, the cardholder won\'t be able to use it.')</h6>
                        <div class="form-group">
                            <label>@lang('Reason')</label>
                            <textarea class="form-control" name="reason" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn--primary h-45 w-100">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
