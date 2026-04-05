@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="card custom--card overflow-hidden">
        <div class="card-header">
            <div class="d-flex flex-wrap gap-2 justify-content-end">
                <select id="status" class="form--control w-auto">
                    <option @selected(request()->status == '') value="">@lang('All')</option>
                    <option @selected(request()->status == 'active') value="active">@lang('Active')</option>
                    <option @selected(request()->status == 'inactive') value="inactive">@lang('Inactive')</option>
                </select>
                <a href="{{ route('user.vcard.issue') }}" class="btn btn--base" style="white-space: nowrap"><i class="las la-plus"></i> @lang('New Card')</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table--responsive--md">
                    <thead>
                        <tr>
                            <th>@lang('Name')</th>
                            <th>@lang('Card Number')</th>
                            <th>@lang('Expire Date')</th>
                            <th>@lang('Status')</th>
                            <th>@lang('Action')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($cards as $virtualCard)
                            <tr>
                                <td>
                                    <div>
                                        <strong>{{ $virtualCard->name }}</strong>
                                        <br>
                                        <small>{{ $virtualCard->label }}</small>
                                    </div>
                                </td>
                                <td>**** **** **** {{ $virtualCard->last4 }}</td>
                                <td>{{ $virtualCard->exp_month }}/{{ $virtualCard->exp_year }}</td>
                                <td>@php echo $virtualCard->statusBadge @endphp</td>
                                <td>
                                    <div>
                                        <button type="button" class="btn btn-outline--secondary btn--sm topupBtn" @if ($virtualCard->status != 'active') disabled @endif data-card_id="{{ $virtualCard->id }}"><i class="las la-money-bill-wave"></i> @lang('Topup')</button>

                                        <a href="{{ route('user.vcard.details', encrypt($virtualCard->id)) }}" class="btn btn--sm btn-outline--base"><i class="la la-desktop"></i> @lang('Details')</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="100%" class="text-center">@lang('No data found')</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($cards->hasPages())
                <div class="card-footer">
                    {{ paginateLinks($cards) }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('modal')
    @include($activeTemplate . 'partials.gateway_modal')
@endpush

@push('script')
    <script>
        (function($) {
            'use strict';

            $('.topupBtn').on('click', function() {
                let cardId = $(this).data('card_id');
                let action = "{{ route('user.vcard.topup', ':cardId') }}".replace(':cardId', cardId);
                $('#topupModalForm').attr('action', action);
                $('#addMoneyModal').modal('show');
            });

            $('#status').on('change', function() {
                let search = $(this).val();
                window.location.href = "{{ route('user.vcard.index') }}?status=" + search;
            });
        })(jQuery);
    </script>
@endpush

@push('style')
    <style>
        .btn.btn--md {
            padding: 11px 20px;
            font-size: 15px;
        }

        .credit-card-wrapper__right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        @media screen and (max-width: 375px) {
            .credit-card-wrapper__right {
                flex-wrap: wrap;
            }
        }

        .credit-card-wrapper__right .form--control {
            background: hsl(var(--white));
            padding: 8px;
        }

        .deposit-info__input-group {
            border: 1px solid hsl(var(--black)/.1) !important;
        }


    </style>
@endpush
