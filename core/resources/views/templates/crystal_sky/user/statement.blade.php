@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="show-filter mb-3 text-end">
                <button class="btn btn--base showFilterBtn btn-sm" type="button"><i class="las la-filter"></i> @lang('Filter')</button>
            </div>
            <div class="card custom--card responsive-filter-card mb-4">
                <div class="card-body">
                    <form action="" id="filterformData">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-label">@lang('Filter By Period')</label>
                                    <x-date-picker class="form--control" required />
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-label">@lang('Type')</label>
                                    <select class="form--control" name="trx_type">
                                        <option value="">@lang('All')</option>
                                        <option value="+" @selected(request()->trx_type == '+')>@lang('Plus')</option>
                                        <option value="-" @selected(request()->trx_type == '-')>@lang('Minus')</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-label">@lang('Form Amount')</label>
                                    <input type="number" class="form-control form--control" name="range_filter[amount][min]" placeholder="Enter Amount" value="{{ old('range_filter.amount.min', isset(request()->range_filter['amount']['min']) ? request()->range_filter['amount']['min'] : '') }}">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-label">@lang('To Amount')</label>
                                    <input type="number" class="form-control form--control" name="range_filter[amount][max]" placeholder="Enter Amount" placeholder="Enter Amount" value="{{ old('range_filter.amount.max', isset(request()->range_filter['amount']['max']) ? request()->range_filter['amount']['max'] : '') }}">
                                </div>
                            </div>
                        </div>
                        <div class="col-12 text-end">
                            <button class="btn btn--base"><i class="las la-filter"></i> @lang('Filter')</button>
                        </div>
                    </form>
                </div>
            </div>

            @if ($transactions->count())
                <div class="card custom--card">
                    <div class="card-body p-0">
                        <div class="card-header d-flex justify-content-end">
                            <button class="btn btn--base btn--sm" id="exportButton" type="button"><i class="las la-cloud-download-alt"></i> @lang('Download Statement')</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table--responsive--md has-search-form" id="viserTable">
                                <thead>
                                    <tr>
                                        <th>@lang('TRX No.')</th>
                                        <th>@lang('Time')</th>
                                        <th>@lang('Amount')</th>
                                        <th>@lang('Post Balance')</th>
                                        <th>@lang('Details')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($transactions as $trx)
                                        <tr>
                                            <td>
                                                #{{ $trx->trx }}
                                            </td>
                                            <td>
                                                {{ showDateTime($trx->created_at) }}
                                            </td>
                                            <td>
                                                <span class="@if ($trx->trx_type == '+') text--success @else text--danger @endif">
                                                    {{ $trx->trx_type }} {{ showAmount($trx->amount) }}
                                                </span>
                                            </td>
                                            <td>
                                                {{ showAmount($trx->post_balance) }}
                                            </td>
                                            <td>{{ $trx->details }}</td>
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
            @endif
        </div>
    </div>
@endsection

@push('script-lib')
    <script src="{{ asset('assets/admin/js/vendor/jspdf.umd.min.js') }} "></script>
    <script src="{{ asset('assets/admin/js/vendor/jspdf.plugin.autotable.min.js') }} "></script>
    <script src="{{ asset('assets/admin/js/visertable.js') }}" type="module"></script>
@endpush

@push('script')
    <script>
        (function($) {
            "use strict"

            $("#exportButton").on('click', function(e) {
                $("#filterformData").attr('action', "{{ route('user.statement.download') }}").submit().attr('action', '').find('button[disabled]').removeAttr('disabled');
            });

            const datePicker = $('.date-range').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear'
                },
                maxSpan: {
                    "days": 365
                },
                showDropdowns: true,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 15 Days': [moment().subtract(14, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(30, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'Last 6 Months': [moment().subtract(6, 'months').startOf('month'), moment().endOf('month')],
                    'This Year': [moment().startOf('year'), moment().endOf('year')],
                },
                maxDate: moment()
            });
            const changeDatePickerText = (event, startDate, endDate) => {
                $(event.target).val(startDate.format('MMMM DD, YYYY') + ' - ' + endDate.format('MMMM DD, YYYY'));
            }


            $('.date-range').on('apply.daterangepicker', (event, picker) => changeDatePickerText(event, picker.startDate, picker.endDate));


            if ($('.date-range').val()) {
                let dateRange = $('.date-range').val().split(' - ');
                $('.date-range').data('daterangepicker').setStartDate(new Date(dateRange[0]));
                $('.date-range').data('daterangepicker').setEndDate(new Date(dateRange[1]));
            }

        })(jQuery)
    </script>
@endpush
