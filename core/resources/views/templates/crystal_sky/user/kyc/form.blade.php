@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-4">
            <div class="card custom--card h-100 mb-4">
                <div class="card-body">
                    <h4 class="mb-3">@lang('How To Complete These Forms')</h4>
                    <p class="text-muted mb-4">@lang('Please follow these simple steps for each document on this page.')</p>

                    <div class="d-flex mb-3">
                        <div class="me-3">
                            <span class="badge badge--primary rounded-pill px-3 py-2">1</span>
                        </div>
                        <div>
                            <h6 class="mb-1">@lang('Download the file')</h6>
                            <p class="text-muted mb-0">@lang('Click the download button above the file box to save the document to your phone or computer.')</p>
                        </div>
                    </div>

                    <div class="d-flex mb-3">
                        <div class="me-3">
                            <span class="badge badge--primary rounded-pill px-3 py-2">2</span>
                        </div>
                        <div>
                            <h6 class="mb-1">@lang('Print it out')</h6>
                            <p class="text-muted mb-0">@lang('Open the file and print a paper copy if the form needs a handwritten signature.')</p>
                        </div>
                    </div>

                    <div class="d-flex mb-3">
                        <div class="me-3">
                            <span class="badge badge--primary rounded-pill px-3 py-2">3</span>
                        </div>
                        <div>
                            <h6 class="mb-1">@lang('Fill it in and sign it')</h6>
                            <p class="text-muted mb-0">@lang('Write your information clearly, sign where needed, and make sure every page is complete before uploading it back.')</p>
                        </div>
                    </div>

                    <div class="d-flex mb-3">
                        <div class="me-3">
                            <span class="badge badge--primary rounded-pill px-3 py-2">4</span>
                        </div>
                        <div>
                            <h6 class="mb-1">@lang('Scan or take a clear photo')</h6>
                            <p class="text-muted mb-0">@lang('Use a scanner, printer app, or your phone camera. Make sure the full page is visible and easy to read.')</p>
                        </div>
                    </div>

                    <div class="d-flex">
                        <div class="me-3">
                            <span class="badge badge--primary rounded-pill px-3 py-2">5</span>
                        </div>
                        <div>
                            <h6 class="mb-1">@lang('Upload it back here')</h6>
                            <p class="text-muted mb-0">@lang('Come back to this same section, click Choose File, select your completed file, and then press Submit at the bottom of the page.')</p>
                        </div>
                    </div>

                    <div class="alert alert--warning mt-4 mb-0">
                        <strong>@lang('Helpful tip:')</strong>
                        @lang('If you are using a phone, you may need to download the file first, sign it, save the signed copy, and then return here to upload that completed copy.')
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card custom--card">
                <div class="card-body">
                    <form action="{{ route('user.kyc.submit') }}" method="post" enctype="multipart/form-data">
                        @csrf
                        <x-viser-form identifier="act" identifierValue="kyc" />
                        <button type="submit" class="btn btn--base w-100">@lang('Submit')</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        "use strict";
        (function($) {
            $('label').removeClass('form-label fw-bold');
        })(jQuery);
    </script>
@endpush
