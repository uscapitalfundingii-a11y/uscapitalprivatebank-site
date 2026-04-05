@extends('admin.layouts.app')
@section('panel')
    <div class="row gy-3">
        <div class="col-xl-9">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">@lang('Config Card')</h4>
                    <form action="{{ route('admin.branding.card.update') }}" method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="form-group col-xl-6 col-sm-6">
                                <label class="required"> @lang('Text Color')</label>
                                <div class="input-group">
                                    <span class="input-group-text p-0 border-0">
                                        <input type='text' class="form-control colorPicker" value="{{@$branding->text_color}}">
                                    </span>
                                    <input type="text" class="form-control colorCode" name="text_color" value="{{ @$branding->text_color }}">
                                </div>
                            </div>
                            <div class="form-group col-xl-6 col-sm-6">
                                <label> @lang('Card Background')</label>
                                <div class="input-group">
                                    <input type="file" class="form-control" name="card_background" accept="image/png,image/jpg,image/jpeg" />
                                </div>
                                <small class="text-muted">@lang('Supported Files'):<strong>@lang('.png, .jpg, .jpeg')</strong>, @lang('Suggested Size:')<strong>{{ getFileSize('cardBackground') }}</strong></small>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn--primary w-100 mt-3 h-45">@lang('Submit')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-3">
            <div class="card">
                <div class="card-body p-2 center-on-sm">
                    <x-v-card :hide_status="true" :hide_eye="true" />
                </div>
            </div>
        </div>
    </div>
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
        (function ($) {
            "use strict";
            $('[name="card_background"]').on('change', function () {
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
                move: function (color) {
                    var colorHex = color.toHexString().replace(/^#?/, '');
                    $(this).parent().siblings('.colorCode').val(colorHex);
                    var textColor = $('input[name="text_color"]').val();
                    $('.v--card').css({'--color-code': '#' + textColor});
                }
            });

            $('.colorCode').on('input', function () {
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
