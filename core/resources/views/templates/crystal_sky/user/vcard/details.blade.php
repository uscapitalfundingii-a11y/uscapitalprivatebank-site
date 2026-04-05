@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-card">
        <div class="row gy-4">
            <div class="col-md-12">
                <div class="row gy-4">
                    <div class="col-lg-4">
                        <x-v-card :name="$card->name" :last4="$card->last4" :expire_month="$card->exp_month" :expire_year="$card->exp_year" :status="$card->status" :brand="$card->brand" :id="$card->card_id" :hide_status="true" :start_color="$card->start_color" :end_color="$card->end_color" />
                    </div>
                    <div class="col-lg-8">
                        <div class="row gy-4">
                            <div class="col-sm-6">
                                <div class="card custom--card">
                                    <div class="card-body">
                                        <div class="card-details-wrapper">
                                            <div class="card-details__item">
                                                <span class="fw-bold">@lang('Label')</span>
                                                <span class="fw-bold">{{ __($card->label) }}</span>
                                            </div>
                                            <div class="card-details__item">
                                                <span class="fw-bold">@lang('Issued At')</span>
                                                <span class="fw-bold">{{ showDateTime($card->created_at, 'd M, Y') }}</span>
                                            </div>
                                            <div class="card-details__item">
                                                <span class="fw-bold">@lang('Status')</span>
                                                @php echo $card->status_badge @endphp
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card custom--card mt-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-center align-items-center spend-box">
                                            <div class="spend-item">
                                                <span class="title">@lang('Spend Limit')</span>
                                                <p class="spend_value">{{ showAmount($card->spending_limit ?? 0) }}</p>
                                            </div>
                                            <div class="divider"></div>
                                            <div class="spend-item">
                                                <span class="title">@lang('Spent')</span>
                                                <p class="spend_value">{{ showAmount($card->current_spend ?? 0) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="card custom--card balance-card h-100">
                                    <div class="card-body d-flex justify-content-center align-items-center">
                                        <div class="text-center">
                                            <p class="title">@lang('Current Balance')</p>
                                            <p class="balance_value">{{ showAmount(@$card->spending_limit - @$card->current_spend) }}</p>
                                            @if ($card->status == 'active')
                                                <button type="button" class="btn btn--sm btn--base topup-btn mt-2"><i class="las la-plus"></i> @lang('Topup')</button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="card custom--card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="mb-0">@lang('Transaction History')</h5>
                            <x-search-form placeholder="TRX No." btn="btn--base" />
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="dashboard-table  mt-0">
                            <table class="table table--responsive--md">
                                <thead>
                                    <tr>
                                        <th>@lang('Trx')</th>
                                        <th>@lang('Transacted')</th>
                                        <th>@lang('Amount')</th>
                                        <th>@lang('Post Balance')</th>
                                        <th class="text-end">@lang('Details')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($transactions as $transaction)
                                        <tr>
                                            <td>{{ $transaction->trx }}</td>
                                            <td>
                                                <div>
                                                    {{ showDateTime($transaction->created_at) }}
                                                    <br>
                                                    <strong>{{ diffForHumans($transaction->created_at) }}</strong>
                                                </div>
                                            </td>
                                            <td>
                                                <strong class="text--{{ $transaction->trx_type == '-' ? 'danger' : 'success' }}">{{ $transaction->trx_type }}{{ showAmount($transaction->amount) }}</strong>
                                            </td>
                                            <td>{{ showAmount($transaction->post_balance) }}</td>
                                            <td class="text-end">{{ $transaction->details }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="100%">
                                                <x-empty-table title="No transaction found" />
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if ($transactions->hasPages())
                            <div class="pagination-wrapper">
                                {{ $transactions->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('modal')
    @include($activeTemplate . 'partials.gateway_modal')

    <div class="modal custom--modal fade" id="passwordModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">@lang('Password Confirmation')</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row justify-content-center">
                        <div class="col-lg-12">
                            <form method="GET" action="{{ route('user.vcard.secret.reveal', $card->id) }}" class="revealSecretForm">
                                <div class="form-group">
                                    <label class="form-label">@lang('Password')</label>
                                    <input type="text" class="form--control" name="password" required>
                                </div>
                                <button class="btn btn--base w-100" type="submit">@lang('Submit')</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endpush

@include('partials.user.show_card_info')

@push('script')
    <script>
        $(document).ready(function() {
            $('.topup-btn').on('click', function(e) {
                $('#topupModalForm').attr('action', "{{ route('user.vcard.topup', $card->id) }}");
                $('#addMoneyModal').modal('show');
            });
        });
    </script>
@endpush

@push('style')
    <style>
        .spend-item {
            flex: 1;
            text-align: center;
        }

        .spend-item .title {
            font-size: 14px;
        }

        .spend-item .spend_value {
            font-size: 24px;
            font-weight: 600;
        }

        .divider {
            width: 1px;
            background-color: #ccc;
            margin: 0 20px;
            align-self: stretch;
        }

        .balance-card .title {
            font-size: 1rem;
            font-weight: 500;
        }

        .balance-card .balance_value {
            font-size: 36px;
            font-weight: 600;
        }

        .v--card {
            width: 100% !important;
        }

        .v--card i {
            font-size: 18px;
            color: #ffffff;
            line-height: 2;
        }

        .deposit-info__input-group {
            border: 1px solid hsl(var(--black)/.1) !important;
        }
    </style>
@endpush
