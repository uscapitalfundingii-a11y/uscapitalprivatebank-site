@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="row gy-4 justify-content-center">
        <div class="col-xl-4">
            <div class="card custom--card">
                <div class="card-body">
                    <h6 class="card-title text-center">@lang('Transfer Limit')</h6>
                    <ul class="caption-list-two">
                        <li>
                            <span class="caption">@lang('Minimum Per Transaction')</span>
                            <span class="value">{{ showAmount(@$setting->minimum_limit) }}</span>
                        </li>

                        <li>
                            <span class="caption">@lang('Maximum Per Transaction')</span>
                            <span class="value">{{ showAmount(@$setting->maximum_limit) }}</span>
                        </li>

                        <li>
                            <span class="caption">@lang('Daily Maximum')</span>
                            <span class="value">{{ showAmount(@$setting->daily_maximum_limit) }}</span>
                        </li>

                        <li>
                            <span class="caption">@lang('Monthly Maximum')</span>
                            <span class="value">{{ showAmount(@$setting->monthly_maximum_limit) }}</span>
                        </li>

                        <li>
                            <span class="caption">@lang('Daily Maximum Transaction')</span>
                            <span class="value">{{ @$setting->daily_total_transaction }}</span>
                        </li>

                        <li>
                            <span class="caption"> @lang('Monthly Maximum Transaction')</span>
                            <span class="value">{{ @$setting->monthly_total_transaction }}</span>
                        </li>
                    </ul>

                    @php $transferCharge = $setting->chargeText(); @endphp

                    @if ($transferCharge)
                        <small class="text--danger">* @lang('Charge') {{ $transferCharge }}</small>
                    @endif
                </div>
            </div>

            @if ($setting->instruction)
                <div class="card custom--card mt-3">
                    <div class="card-body">
                        <h6 class="card-title text-center">@lang('Instruction')</h6>
                        <p>@php echo $setting->instruction; @endphp</p>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-xl-8">
            <div class="card custom--card">
                <div class="card-body">
                    @if (@$setting->instruction)
                        <div class="text-center">
                            @php echo @$setting->instruction;  @endphp
                        </div>
                    @endif
                    <form method="POST" action="{{ route('user.transfer.wire.request') }}">
                        @csrf
                        <div class="form-group">
                            <label class="form-label">@lang('Amount')</label>
                            <div class="input-group">
                                <input type="number" step="any" class="form--control" name="amount">
                                <span class="input-group-text">{{ __(gs()->cur_text) }}</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">@lang('Receiving Bank Coordinates')</label>
                            <select class="form--control select2 wire-bank-coordinate">
                                <option value="" selected>@lang('Type bank name or SWIFT/BIC')</option>
                                @foreach ($bankCoordinates as $coordinate)
                                    @php
                                        $searchText = trim(($coordinate['name'] ?? '') . ' ' . ($coordinate['swift_code'] ?? '') . ' ' . ($coordinate['country'] ?? '') . ' ' . ($coordinate['city'] ?? '') . ' ' . ($coordinate['address'] ?? ''));
                                    @endphp
                                    <option value="{{ $coordinate['swift_code'] }}" data-search="{{ $searchText }}" data-coordinate='@json($coordinate)'>
                                        {{ $coordinate['name'] }} ({{ $coordinate['swift_code'] }}) - {{ $coordinate['country'] }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-2">
                                @lang('If your receiving bank is not listed, please open a CRM ticket or message a representative in chat so our team can add or verify the bank coordinates.')
                            </small>
                        </div>
                        <x-viser-form identifier="act" identifierValue="wire_transfer" />
                        @include($activeTemplate . 'partials.otp_field')
                        <button type="submit" class="btn btn--base w-100 ">@lang('Submit')</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        'use strict';
        (function($) {
            const bankSelect = $('.wire-bank-coordinate');

            if ($.fn.select2) {
                bankSelect.select2({
                    width: '100%',
                    matcher: function(params, data) {
                        if ($.trim(params.term) === '') {
                            return data;
                        }

                        let search = ($(data.element).data('search') || data.text || '').toString().toLowerCase();
                        return search.includes(params.term.toLowerCase()) ? data : null;
                    }
                });
            }

            bankSelect.on('change', function() {
                let coordinate = $(this).find(':selected').data('coordinate');

                if (!coordinate) {
                    return;
                }

                const values = {
                    swift_bic: coordinate.swift_code,
                    bank_country: coordinate.country,
                    bank_city: coordinate.city,
                    bank_address: coordinate.address,
                    bank_phone: coordinate.phone,
                };

                Object.keys(values).forEach(function(name) {
                    let field = $(`[name="${name}"]`);

                    if (field.length && !field.val()) {
                        field.val(values[name]);
                    }
                });
            });
        })(jQuery);
    </script>
@endpush

<x-transfer-bottom-menu />
