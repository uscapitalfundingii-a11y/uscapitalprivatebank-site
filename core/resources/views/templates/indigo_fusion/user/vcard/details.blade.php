@extends($activeTemplate . 'layouts.master')
@section('content')
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
                                            <h6>@lang('Label')</h6>
                                            <h6>{{ __($card->label) }}</h6>
                                        </div>
                                        <div class="card-details__item">
                                            <h6>@lang('Issued At')</h6>
                                            <h6>{{ showDateTime($card->created_at, 'd M, Y') }}</h6>
                                        </div>
                                        <div class="card-details__item">
                                            <h6>@lang('Status')</h6>
                                            @php echo $card->status_badge @endphp
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card custom--card mt-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-center align-items-center spend-box">
                                        <div class="spend-item">
                                            <h6 class="title">@lang('Spend Limit')</h6>
                                            <p class="spend_value">{{ showAmount($card->spending_limit ?? 0) }}</p>
                                        </div>
                                        <div class="divider"></div>
                                        <div class="spend-item">
                                            <h6 class="title">@lang('Spent')</h6>
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
                                                <button type="button" class="btn btn-sm btn--base topup-btn"><i class="las la-plus"></i> @lang('Topup')</button>
                                        @endif
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="card custom--card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">@lang('Transaction History')</h5>
                    <form method="GET">
                        <div class="input-group">
                            <input class="form-control form--control" placeholder="@lang('TRX No.')" name="search" type="text" value="{{ request()->search }}">
                            <button type="submit" class="input-group-text"><i class="la la-search"></i></button>
                        </div>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive--md ">
                        <table class="custom--table table has-search-form">
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
                                @forelse($transactions as $transaction)
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
                                        <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
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
@endsection

@push('bottom-menu')
    <li>
        <a href="{{ route('user.profile.setting') }}">@lang('Profile')</a>
    </li>

    @if (gs()->modules->referral_system)
        <li><a href="{{ route('user.referral.users') }}">@lang('Referral')</a></li>
    @endif

    @if (gs()->modules->virtual_card)
        <li><a class="active" href="{{ route('user.vcard.index') }}">@lang('Virtual Cards')</a></li>
    @endif

    <li><a href="{{ route('user.twofactor') }}">@lang('2FA Security')</a></li>
    <li><a href="{{ route('user.change.password') }}">@lang('Change Password')</a></li>
    <li><a href="{{ route('user.transaction.history') }}">@lang('Transactions')</a></li>
    <li><a href="{{ route('user.statement') }}">@lang('Statement')</a></li>
    <li><a href="{{ route('ticket.index') }}">@lang('Support Tickets')</a></li>
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

        .balance-card .title{
            font-size: 1rem;
            font-weight: 500;
        }

        .balance-card .balance_value{
            font-size: 36px;
            font-weight: 600;
        }

        .v--card {
            width: 100% !important;
        }

        .card-details__item h6 {
            font-size: 0.875rem !important;
        }

        @media (max-width: 1399px) {
            .card-details-wrapper {
                gap: 8px !important;
            }

            .card-details__item {
                padding: 0px 0px 8px 0px !important;
                width: 100% !important;
            }
        }

        @media screen and (max-width: 991px) {
            .card-details-wrapper {
                gap: 6px;
            }

            .card-details__item {
                padding: 0px 0px 6px 0px !important;
            }
        }

        @media screen and (max-width: 424px) {
            .v--card__number {
                font-size: 14px !important;
            }

            .show-full-card i {
                font-size: 14px;
            }
        }

        @media screen and (max-width: 991px) {
            .card-wrapper {
                flex-direction: column
            }
        }
    </style>
@endpush
