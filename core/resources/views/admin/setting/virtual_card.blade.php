@extends('admin.layouts.app')
@section('panel')
    <form method="POST" action="{{ route('admin.virtualcard.configuration.update') }}" enctype="multipart/form-data">
        @csrf
        <div class="row gy-3">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">@lang('Stripe Configuration')</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-xl-6 col-sm-6">
                                <label> @lang('Stripe Secret Key')</label>
                                <input class="form-control" type="text" name="stripe_secret_key" required value="{{ gs('stripe_secret_key') }}">
                            </div>

                            <div class="form-group col-xl-6 col-sm-6">
                                <label> @lang('Stripe Publishable Key')</label>
                                <input class="form-control" type="text" name="stripe_publishable_key" required value="{{ gs('stripe_publishable_key') }}">
                            </div>

                            <div class="form-group col-xl-6 col-sm-6">
                                <label> @lang('Stripe Webhook Endpoint Secret')</label>
                                <input class="form-control" type="text" name="webhook_endpoint_secret" value="{{ gs('webhook_endpoint_secret') }}" required />
                            </div>

                            <div class="form-group col-xl-6 col-sm-6">
                                <label> @lang('Stripe Webhook URL')<small>(@lang('Copy and paste in Stripe Dashboard'))</small></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="{{ route('stripe.webhook') }}" readonly>
                                    <button type="button" class="copyInput input-group-text" title="@lang('Copy')"><i class="fas fa-copy"></i></button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="row gy-3">
                    <div class="col-xxl-9 col-xl-8">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="card-title">@lang('Card Configuration')</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="form-group col-xl-6">
                                        <label class="required"> @lang('Text Color')</label>
                                        <div class="input-group">
                                            <span class="input-group-text p-0 border-0">
                                                <input type='text' class="form-control colorPicker" value="{{ @gs('branding_config')->text_color }}">
                                            </span>
                                            <input type="text" class="form-control colorCode" name="text_color" value="{{ @gs('branding_config')->text_color }}">
                                        </div>
                                    </div>
                                    <div class="form-group col-xl-6">
                                        <label> @lang('Card Background')</label>
                                        <div class="input-group">
                                            <input type="file" class="form-control" name="card_background" accept="image/png,image/jpg,image/jpeg" />
                                        </div>
                                        <small class="text-muted">@lang('Supported Files'):<strong>@lang('.png, .jpg, .jpeg')</strong>, @lang('Suggested Size:')<strong>{{ getFileSize('cardBackground') }}px</strong></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-xl-4">
                        <div class="card">
                            <div class="card-body p-2 center-on-sm">
                                <x-v-card :hide_status="true" :hide_eye="true" expire_month="{{ date('m') }}" expire_year="{{ date('Y') + 3 }}" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">@lang('Card Issue Fee & Charge')</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="form-group col-xl-6 col-sm-6">
                                <div class="form-group">
                                    <label> @lang('Card Issue Fee')</label>
                                    <div class="input-group">
                                        <input class="form-control" type="number" min="0" step="any" name="card_issue_fee" required value="{{ getAmount(gs('card_issue_fee')) }}">
                                        <div class="input-group-text">{{ __(gs('cur_text')) }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group col-xl-6 col-sm-6">
                                <label> @lang('Yearly Card Charge')</label>
                                <div class="input-group">
                                    <input class="form-control" type="number" min="0" step="any" name="yearly_card_charge" required value="{{ getAmount(gs('yearly_card_charge')) }}">
                                    <div class="input-group-text">{{ __(gs('cur_text')) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn--primary h-45 w-100">@lang('Submit')</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('style')
    <style>
        @media (max-width: 772px) {
            .center-on-sm {
                display: flex;
                justify-content: center;
                align-items: center;
                padding-bottom: 1rem;
            }

            .center-on-sm .v--card {
                width: 100%;
            }
        }
    </style>
@endpush

@push('script-lib')
    <script src="{{ asset('assets/admin/js/spectrum.js') }}"></script>
@endpush

@push('style-lib')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/spectrum.css') }}">
@endpush

@push('script')
    <script>
        "use strict";
        $(document).ready(function() {
            $('.copyInput').on('click', function(e) {
                var copybtn = $(this);
                var input = copybtn.closest('.input-group').find('input');
                if (input && input.select) {
                    input.select();
                    try {
                        document.execCommand('SelectAll')
                        document.execCommand('Copy', false, null);
                        input.blur();
                        notify('success', `Copied: ${copybtn.closest('.input-group').find('input').val()}`);
                    } catch (err) {
                        alert('Please press Ctrl/Cmd + C to copy');
                    }
                }
            });
        });

        (function($) {
            $('[name="card_background"]').on('change', function() {
                var file = this.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('.v--card').css('--card-background', `url(${e.target.result})`);
                    }
                    reader.readAsDataURL(file);
                }
            });

            $('.colorPicker').spectrum({
                color: $(this).data('color'),
                move: function(color) {
                    var colorHex = color.toHexString().replace(/^#?/, '');
                    $(this).parent().siblings('.colorCode').val(colorHex);
                    var textColor = $('input[name="text_color"]').val();
                    $('.v--card').css({
                        '--color-code': '#' + textColor
                    });
                }
            });

            $('.colorCode').on('input', function() {
                var clr = $(this).val();
                $(this).parents('.input-group').find('.colorPicker').spectrum({
                    color: clr,
                });

                var textColor = $('input[name="text_color"]').val();
                $('.v--card').css({
                    '--color-code': '#' + textColor,
                });
            });
        })(jQuery);
    </script>
@endpush
