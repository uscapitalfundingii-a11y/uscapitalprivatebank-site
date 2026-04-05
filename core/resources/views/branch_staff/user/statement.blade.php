@extends('branch_staff.layouts.app')
@section('panel')
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">@lang('Filter')</h5>
                </div>
                <div class="card-body">
                    <form action="" id="filterForm">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>@lang('Filter By Period')</label>
                                    <x-date-picker class="form--control w-100" required />
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>@lang('Type')</label>
                                    <select class="form-select select2" name="trx_type" data-minimum-results-for-search="-1">
                                        <option value="">@lang('All')</option>
                                        <option value="+" @selected(request()->trx_type == '+')>@lang('Plus')</option>
                                        <option value="-" @selected(request()->trx_type == '-')>@lang('Minus')</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-label">@lang('Form Amount')</label>
                                    <input type="number" class="form-control amount" name="range_filter[amount][min]" placeholder="Enter Amount" value="{{ old('range_filter.amount.min', isset(request()->range_filter['amount']['min']) ? request()->range_filter['amount']['min'] : '') }}">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label class="form-label">@lang('To Amount')</label>
                                    <input type="number" class="form-control amount" name="range_filter[amount][max]" placeholder="Enter Amount" placeholder="Enter Amount" value="{{ old('range_filter.amount.max', isset(request()->range_filter['amount']['max']) ? request()->range_filter['amount']['max'] : '') }}">
                                </div>
                            </div>

                            <div class="col-lg-12 text-end">
                                <button type="submit" class="btn btn--primary w-100 h-45"><i class="las la-filter"></i> @lang('Filter')</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @if ($transactions->count())
            <div class="col-md-12">
                <div class="d-flex justify-content-end mt-4 mb-3">
                    <button class="btn btn-primary btn--sm" id="exportButton" type="button"><i class="las la-cloud-download-alt"></i> @lang('Download Statement')</button>
                </div>
                <div class="card b-radius--10">
                    <div class="card-body p-0">
                        @include('branch_staff.partials.transaction_table')
                    </div>
                    @if ($transactions->hasPages())
                        <div class="card-footer py-4">
                            {{ paginateLinks($transactions) }}
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection

{{-- @push('breadcrumb-plugins')
    <div>
        <form action="" id="filterForm">
            <div class="d-flex flex-wrap gap-4">
                <div class="flex-grow-1">
                    <label class="form-label required">@lang('Filter By Period')</label>
                    <x-date-picker class="form--control" required />
                </div>
                <div class="flex-grow-1">
                    <label class="form-label">@lang('Type')</label>
                    <select class="form-select select2" name="trx_type" data-minimum-results-for-search="-1">
                        <option value="">@lang('All')</option>
                        <option value="+" @selected(request()->trx_type == '+')>@lang('Plus')</option>
                        <option value="-" @selected(request()->trx_type == '-')>@lang('Minus')</option>
                    </select>
                </div>
                <div class="flex-grow-1">
                    <label class="form-label">@lang('Form Amount')</label>
                    <input type="number" class="form-control amount" name="range_filter[amount][min]" placeholder="Enter Amount" value="{{ old('range_filter.amount.min', isset(request()->range_filter['amount']['min']) ? request()->range_filter['amount']['min'] : '') }}">
                </div>
                <div class="flex-grow-1">
                    <label class="form-label">@lang('To Amount')</label>
                    <input type="number" class="form-control amount" name="range_filter[amount][max]" placeholder="Enter Amount" placeholder="Enter Amount" value="{{ old('range_filter.amount.max', isset(request()->range_filter['amount']['max']) ? request()->range_filter['amount']['max'] : '') }}">
                </div>
                <div class="align-self-end">
                    <button class="btn btn--primary w-100"><i class="las la-filter"></i> @lang('Filter')</button>
                </div>
            </div>
        </form>
    </div>
@endpush --}}

@push('script')
    <script>
        (function($) {
            "use strict"

            $("#exportButton").on('click', function(e) {
                $("#filterForm").attr('action', "{{ route('staff.account.statement.download', $user->account_number) }}").submit().attr('action', '').find('button[disabled]').removeAttr('disabled');
            });

            const datePicker = $('.date-range').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear'
                },
                maxSpan: {
                    "days": 3
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
