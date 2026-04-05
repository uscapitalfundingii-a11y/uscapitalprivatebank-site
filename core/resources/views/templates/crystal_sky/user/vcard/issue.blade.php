@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="row justify-content-center gy-4">
        <div class="col-lg-8">
            <div class="text-end">
                <a href="{{ route('user.vcard.index') }}" class="btn btn--base btn--sm">
                    <i class="las la-list"></i> @lang('All cards')
                </a>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card custom--card">
                <div class="card-body">
                    <form action="#" class="no-validate issueForm">
                        <div class="form-group">
                            <label class="form-label">@lang('Custom Label') <i class="fas fa-info-circle text--info" data-bs-toggle="tooltip" data-bs-title="@lang("Give your card a custom label to help you recognize its purpose easily. For example: 'Shopping', 'Bills', or 'Travel'.")"></i></label>
                            <input type="text" class="form--control" name="custom_label" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">@lang('Amount')</label>
                            <div class="input-group">
                                <input type="number" step="any" min="0" class="form-control form--control" name="issue_amount" value="{{ old('issue_amount') }}" required />
                                <span class="input-group-text">{{ __(gs('cur_text')) }}</span>
                            </div>
                        </div>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between flex-wrap gap-2 px-0">
                                <p class="balance__title">@lang('Add money')</p>
                                <p class="text--success">{{ gs('cur_sym') }}<span class="getting-amount">0.00</span> {{ gs('cur_text') }}</p>
                            </li>

                            <li class="list-group-item d-flex justify-content-between flex-wrap gap-2 px-0">
                                <span class="balance__title">@lang('Card issue fee')</span>

                                <p class="balance__number text-danger">{{ showAmount(gs('card_issue_fee')) }}</p>
                            </li>
                            <li class="list-group-item d-flex justify-content-between flex-wrap gap-2 px-0">
                                <p class="balance__title">@lang('Total Payment')</p>

                                <p class="balance__number">{{ gs('cur_sym') }}<span class="total-payment">0.00</span> {{ gs('cur_text') }}</p>
                            </li>
                        </ul>
                        <button type="submit" class="btn btn--base mt-3 w-100">@lang('Pay & Issue')</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <form class="hidden-form" method="POST" action="{{ route('user.vcard.issue.store') }}">
        @csrf
        <input type="hidden" name="gateway" />
        <input type="hidden" name="from_wallet" />
        <input type="hidden" name="label" />
        <input type="hidden" name="currency" />
        <input type="hidden" name="amount" />
    </form>
@endsection

@push('modal')
    @include($activeTemplate . 'partials.gateway_modal')
@endpush

@push('script')
    <script>
        "use strict";
        (function($) {
            let totalPayment = 0;

            $('[name=issue_amount]').on('input', function(e) {
                const cardIssueFee = @json(getAmount(gs('card_issue_fee')));
                totalPayment = Number($(this).val() * 1 + cardIssueFee).toFixed(2);

                $('.getting-amount').text($(this).val());
                $('[name="amount"]').val(totalPayment);
                $('.total-payment').text(totalPayment);
            });

            $('#addMoneyModal').on('show.bs.modal', function(e) {
                const amount = Number($('span.total-payment').text());
                let notHidden = 1;
                let gatewayRadio = null;

                // filter out gateways based on amount input
                $('#addMoneyModal').find('.payment-item input').not('.wallet-wrapper input').each(function(index, element) {
                    const data = JSON.parse($(element).attr('data-gateway'));

                    if (Number(data.max_amount) < amount || Number(data.min_amount) > amount) {
                        $(this).closest('.payment-item').addClass('hidden');
                    } else {
                        if (!gatewayRadio) {
                            gatewayRadio = $(this);
                        }

                        if (notHidden <= 4) {
                            $(this).closest('.payment-item').removeClass('d-none');
                            notHidden++;
                        }
                    }

                    if (gatewayRadio) {
                        gatewayRadio.trigger('click');
                    }
                });

                $('.gateway-modal').find('.amount').val(amount).trigger('input');
                $('.amount-wrapper').find('span').addClass('d-none');
                $('.amount-wrapper').find('input').addClass('d-none');
                $('.amount-wrapper').append(`<p class="text ms-auto">${amount} {{ __(gs('cur_text')) }}</p>`);
            });

            $('.hidden-form').on('submit', function(e) {
                let amount = $('[name=issue_amount]').val();
                let label = $('[name=custom_label]').val();

                $(this).find('[name="amount"]').val(amount);
                $(this).find('[name="label"]').val(label);
            });

            $('.deposit-form').on('submit', function(e) {
                e.preventDefault();

                if ($(this).find('[name=from_wallet]').prop('checked')) {
                    $('.hidden-form').find('[name=from_wallet]').val(1);
                } else {
                    $('.hidden-form').find('[name=from_wallet]').val('');
                }

                $('[name="gateway"]').val($('.gateway-input:checked').val());
                $('.hidden-form').submit();
            });

            $('.issueForm').on('submit', function(e) {
                e.preventDefault();
                let label = $(this).find('[name=custom_label]').val();
                let amount = $(this).find('[name=issue_amount]').val();

                if (!label) {
                    notify('error', '@lang('The label field is required.')');
                    $(this).find('[name=custom_label]').focus();
                    return;
                }

                if (!amount) {
                    notify('error', '@lang('The amount field is required.')');
                    $(this).find('[name=issue_amount]').focus();
                    return;
                }

                if (amount <= 0) {
                    notify('error', '@lang('Amount should be greather than zero.')');
                    $(this).find('[name=issue_amount]').focus();
                    return;
                }

                $('#addMoneyModal').find('.amount').val(totalPayment).attr('readonly', true);
                $('#addMoneyModal').modal('show');
            });
        })(jQuery);
    </script>
@endpush
