@extends($activeTemplate . 'layouts.frontend')
@section('content')
    <div class="container pt-100 pb-100">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card custom--card onboarding-card">
                    <div class="card-body p-sm-5 p-4">
                        <div class="onboarding-intro">
                            <span class="onboarding-badge">@lang('Profile Onboarding')</span>
                            <h3 class="mt-3 mb-2">@lang("We're helping you complete your profile")</h3>
                            <p class="mb-2">@lang('Please answer a few quick questions so we can finish setting up your account. We will create your username automatically for you.')</p>
                            <p class="mb-0 text-muted">@lang('Once this is done, you can enter your dashboard and complete KYC afterward if needed.')</p>
                        </div>

                        <div class="onboarding-progress mt-4">
                            <div class="onboarding-progress__bar">
                                <span class="onboarding-progress__fill"></span>
                            </div>
                            <div class="onboarding-progress__labels">
                                <span class="active" data-step-label="1">@lang('Contact')</span>
                                <span data-step-label="2">@lang('Location')</span>
                                <span data-step-label="3">@lang('Photo')</span>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('user.data.submit') }}" enctype="multipart/form-data" id="profileOnboardingForm">
                            @csrf

                            <div class="wizard-step active" data-step="1">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <h5 class="mb-1">@lang('Let’s start with your contact details')</h5>
                                        <p class="text-muted mb-0">@lang('Tell us where you live and the best phone number to use for your account.')</p>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label required">@lang('Country')</label>
                                            <select name="country" class="form--control select2 wizard-required">
                                                @foreach ($countries as $key => $country)
                                                    <option data-mobile_code="{{ $country->dial_code }}" value="{{ $country->country }}" data-code="{{ $key }}">
                                                        {{ __($country->country) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label required">@lang('Phone Number')</label>
                                            <div class="input-group">
                                                <span class="input-group-text mobile-code border-right-0"></span>
                                                <input type="number" name="mobile" value="{{ old('mobile') }}" class="form--control checkUser ps-0 wizard-required" required>
                                            </div>
                                            <small class="text-danger mobileExist"></small>
                                        </div>
                                        <input type="hidden" name="mobile_code">
                                        <input type="hidden" name="country_code">
                                    </div>
                                </div>
                            </div>

                            <div class="wizard-step" data-step="2">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <h5 class="mb-1">@lang('Now your location')</h5>
                                        <p class="text-muted mb-0">@lang('This helps us finish your account setup and prepare your profile correctly.')</p>
                                    </div>

                                    <div class="form-group col-sm-12">
                                        <label class="form-label required">@lang('Address')</label>
                                        <input type="text" class="form--control wizard-required" name="address" value="{{ old('address') }}" required>
                                    </div>

                                    <div class="form-group col-sm-6">
                                        <label class="form-label">@lang('State')</label>
                                        <input type="text" class="form--control wizard-required" name="state" value="{{ old('state') }}" required>
                                    </div>

                                    <div class="form-group col-sm-6">
                                        <label class="form-label">@lang('City')</label>
                                        <input type="text" class="form--control wizard-required" name="city" value="{{ old('city') }}" required>
                                    </div>

                                    <div class="form-group col-sm-6">
                                        <label class="form-label">@lang('Zip Code')</label>
                                        <input type="text" class="form--control" name="zip" value="{{ old('zip') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="wizard-step" data-step="3">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <h5 class="mb-1">@lang('Add a profile photo')</h5>
                                        <p class="text-muted mb-0">@lang('This is optional, but it helps personalize your account.')</p>
                                    </div>

                                    <div class="form-group col-12">
                                        <label class="form-label">@lang('Photo')</label>
                                        <input type="file" class="form--control" name="image" id="imageUpload" accept=".png, .jpg, .jpeg">
                                        <small class="text-muted d-block mt-2">@lang('Optional: upload a clear profile image in JPG or PNG format.')</small>
                                        <div class="profile-image-preview d-none"><img src="" alt="profile-image"></div>
                                    </div>

                                    <div class="col-12">
                                        <div class="onboarding-note">
                                            <strong>@lang('Good to know:')</strong>
                                            @lang('Your account number is already assigned. After this step, you can enter the dashboard and complete KYC whenever you are ready.')
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="wizard-actions">
                                <button type="button" class="btn btn-outline--dark wizard-back d-none">@lang('Back')</button>
                                <button type="button" class="btn btn-md btn--base wizard-next">@lang('Continue')</button>
                                <button type="submit" class="btn btn-md btn--base wizard-submit d-none">@lang('Complete Profile')</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    <style>
        .onboarding-card {
            border-radius: 24px;
            overflow: hidden;
        }

        .onboarding-intro {
            padding: 1.75rem;
            border-radius: 20px;
            background: linear-gradient(135deg, rgba(70, 52, 255, 0.12), rgba(15, 121, 194, 0.08));
        }

        .onboarding-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.45rem 0.85rem;
            border-radius: 999px;
            background: rgba(70, 52, 255, 0.14);
            color: #4634ff;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            font-size: 0.78rem;
        }

        .onboarding-progress__bar {
            width: 100%;
            height: 10px;
            border-radius: 999px;
            background: rgba(36, 54, 75, 0.08);
            overflow: hidden;
        }

        .onboarding-progress__fill {
            display: block;
            height: 100%;
            width: 33.333%;
            border-radius: 999px;
            background: linear-gradient(90deg, #4634ff, #1ea7ff);
            transition: width 0.25s ease;
        }

        .onboarding-progress__labels {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-top: 0.75rem;
            font-weight: 600;
            color: #7c8aa5;
        }

        .onboarding-progress__labels span.active {
            color: #24364b;
        }

        .wizard-step {
            display: none;
            margin-top: 2rem;
        }

        .wizard-step.active {
            display: block;
        }

        .wizard-actions {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            margin-top: 2rem;
        }

        .wizard-actions .btn {
            min-width: 180px;
        }

        .profile-image-preview {
            margin-top: 15px;
        }

        .profile-image-preview img {
            width: 200px;
            height: 160px;
            object-fit: cover;
            border-radius: 18px;
        }

        .onboarding-note {
            margin-top: 0.5rem;
            padding: 1rem 1.1rem;
            border-radius: 16px;
            background: rgba(36, 54, 75, 0.04);
            color: #24364b;
        }

        @media (max-width: 575px) {
            .wizard-actions {
                flex-direction: column;
            }

            .wizard-actions .btn {
                width: 100%;
            }
        }
    </style>
@endpush

@push('style-lib')
    <link rel="stylesheet" href="{{ asset('assets/global/css/select2.min.css') }}">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/global/js/select2.min.js') }}"></script>
@endpush

@push('script')
    <script>
        "use strict";
        (function($) {
            const $steps = $('.wizard-step');
            const $backButton = $('.wizard-back');
            const $nextButton = $('.wizard-next');
            const $submitButton = $('.wizard-submit');
            const $progressFill = $('.onboarding-progress__fill');
            const totalSteps = $steps.length;
            let currentStep = 1;

            function showStep(step) {
                currentStep = step;
                $steps.removeClass('active');
                $steps.filter(`[data-step="${step}"]`).addClass('active');
                $('[data-step-label]').removeClass('active');
                $(`[data-step-label="${step}"]`).addClass('active');
                $progressFill.css('width', `${(step / totalSteps) * 100}%`);
                $backButton.toggleClass('d-none', step === 1);
                $nextButton.toggleClass('d-none', step === totalSteps);
                $submitButton.toggleClass('d-none', step !== totalSteps);
            }

            function validateCurrentStep() {
                let valid = true;

                $steps.filter('.active').find('.wizard-required').each(function() {
                    if (!$(this).val()) {
                        this.reportValidity();
                        valid = false;
                        return false;
                    }
                });

                if (!valid) {
                    return false;
                }

                const mobileError = $('.mobileExist').text().trim();
                if (mobileError) {
                    $('[name="mobile"]')[0].reportValidity();
                    return false;
                }

                return true;
            }

            $("#imageUpload").on('change', function() {
                if (this.files && this.files[0]) {
                    let reader = new FileReader();
                    reader.onload = function(e) {
                        $('.profile-image-preview').removeClass('d-none');
                        $('.profile-image-preview img').attr('src', e.target.result)
                    }
                    reader.readAsDataURL(this.files[0]);
                }
            });

            @if ($mobileCode)
                $(`option[data-code={{ $mobileCode }}]`).attr('selected', '');
            @endif

            $('.select2').select2();

            $('select[name=country]').on('change', function() {
                $('input[name=mobile_code]').val($('select[name=country] :selected').data('mobile_code'));
                $('input[name=country_code]').val($('select[name=country] :selected').data('code'));
                $('.mobile-code').text('+' + $('select[name=country] :selected').data('mobile_code'));
                checkUser($('[name=mobile]').val(), 'mobile');
            });

            $('input[name=mobile_code]').val($('select[name=country] :selected').data('mobile_code'));
            $('input[name=country_code]').val($('select[name=country] :selected').data('code'));
            $('.mobile-code').text('+' + $('select[name=country] :selected').data('mobile_code'));

            $('.checkUser').on('focusout', function() {
                checkUser($(this).val(), $(this).attr('name'));
            });

            $('.checkUser').on('input', function() {
                $('.mobileExist').text('');
            });

            function checkUser(value, name) {
                if (name !== 'mobile') {
                    return;
                }

                $.post('{{ route('user.checkUser') }}', {
                    mobile: `${value}`,
                    mobile_code: $('.mobile-code').text().substr(1),
                    _token: '{{ csrf_token() }}'
                }, function(response) {
                    if (response.data != false) {
                        $('.mobileExist').text(`${response.field} already exist`);
                    } else {
                        $('.mobileExist').text('');
                    }
                });
            }

            $nextButton.on('click', function() {
                if (!validateCurrentStep()) {
                    return;
                }

                showStep(Math.min(currentStep + 1, totalSteps));
            });

            $backButton.on('click', function() {
                showStep(Math.max(currentStep - 1, 1));
            });

            $('#profileOnboardingForm').on('submit', function(e) {
                if (!validateCurrentStep()) {
                    e.preventDefault();
                }
            });

            showStep(currentStep);
        })(jQuery);
    </script>
@endpush
