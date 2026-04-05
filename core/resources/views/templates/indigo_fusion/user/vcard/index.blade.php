@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="row gy-4">
        <div class="col-12">
            <div class="d-flex justify-content-end align-items-center flex-wrap gap-2">
                <div class="credit-card-wrapper__right gap-3">
                    <select id="status" class="form--control">
                        <option @selected(request()->status == '') value="">@lang('All')</option>
                        <option @selected(request()->status == 'active') value="active">@lang('Active')</option>
                        <option @selected(request()->status == 'inactive') value="inactive">@lang('Inactive')</option>
                    </select>
                    <a href="{{ route('user.vcard.issue') }}" class="btn btn--dark btn--md" style="white-space: nowrap"><i class="las la-plus"></i> @lang('New Card')</a>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card custom--card">
                <div class="card-body p-0">
                    <div class="table-responsive--md">
                        <table class="custom--table table">
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
                                            <strong>{{ $virtualCard->name }}</strong>
                                            <br>
                                            <small>{{ $virtualCard->label }}</small>
                                        </td>
                                        <td>**** **** **** {{ $virtualCard->last4 }}</td>
                                        <td>{{ $virtualCard->exp_month }}/{{ $virtualCard->exp_year }}</td>
                                        <td>@php echo $virtualCard->statusBadge @endphp</td>
                                        <td>
                                            <div class="d-flex justify-content-end flex-wrap gap-2">
                                                <button type="button" class="btn btn-outline--dark btn-sm topupBtn" @if($virtualCard->status != 'active') disabled @endif data-card_id="{{ $virtualCard->id }}"><i class="las la-money-bill-wave"></i> @lang('Topup')</button>

                                                <a href="{{ route('user.vcard.details', encrypt($virtualCard->id)) }}" class="btn btn-sm btn-outline--base"><i class="la la-desktop"></i> @lang('Details')</a>
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
                </div>
                @if ($cards->hasPages())
                    <div class="card-footer">
                        {{ paginateLinks($cards) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @include($activeTemplate. 'partials.gateway_modal')
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

@push('script')
    <script>
        (function($) {
            'use strict';

            $('.topupBtn').on('click', function(){
                let cardId = $(this).data('card_id');
                let action = "{{ route('user.vcard.topup', ':cardId') }}".replace(':cardId', cardId);
                $('#topupModalForm').attr('action', action);
                $('#addMoneyModal').modal('show');
            });

            $('#status').on('change', function(){
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

        .slick-initialized.slick-slider .slick-track {
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
        }

        .slick-initialized.slick-slider .slick-slide {
            height: auto;
            padding: 0 10px;
        }
    </style>
@endpush
