@extends('admin.layouts.app')
@section('panel')
    <div class="submitRequired bg--warning form-change-alert d-none"><i class="fas fa-exclamation-triangle"></i> @lang('You\'ve to click on the submit button to apply the changes')</div>
    <div class="row mb-none-30">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5>@lang('KYC Form for Account Holder')</h5>
                    <button type="button" class="btn btn-sm btn-outline--primary float-end form-generate-btn"> <i class="la la-fw la-plus"></i>@lang('Add New')</button>
                </div>
                <div class="card-body">
                    <form method="post">
                        @csrf
                        <x-generated-form :form=$form />

                        @can('admin.kyc.setting.submit')
                            <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')</button>
                        @endcan
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5>@lang('KYC Download Documents')</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">@lang('Upload or replace the customer-facing KYC download files here. The document name stays stable on the site even when you upload a newer yearly version.')</p>
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle">
                            <thead>
                                <tr>
                                    <th>@lang('Document')</th>
                                    <th>@lang('Current File')</th>
                                    <th>@lang('Upload / Replace')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($fileFields as $field)
                                    @php $document = $documents[$field->label] ?? null; @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ __($field->name) }}</div>
                                            @if ($field->instruction)
                                                <small class="text-muted">{{ __($field->instruction) }}</small>
                                            @endif
                                            <div><small class="text--base">@lang('Accepted uploads from clients'): {{ $field->extensions ?: __('Any supported file') }}</small></div>
                                        </td>
                                        <td>
                                            @if ($document)
                                                <div class="mb-1">{{ $document->original_name ?: $document->download_name }}</div>
                                                <a href="{{ route('admin.download.attachment', encrypt($document->absolute_path)) }}" class="btn btn-sm btn-outline--primary">
                                                    <i class="las la-download"></i> @lang('Download Current')
                                                </a>
                                            @else
                                                <span class="badge badge--warning">@lang('Not uploaded yet')</span>
                                            @endif
                                        </td>
                                        <td>
                                            <form method="post" enctype="multipart/form-data">
                                                @csrf
                                                <input type="hidden" name="kyc_document_action" value="upload">
                                                <input type="hidden" name="field_label" value="{{ $field->label }}">
                                                <div class="input-group">
                                                    <input type="file" class="form-control" name="document" required>
                                                    <button type="submit" class="btn btn-outline--primary">
                                                        @lang($document ? 'Replace' : 'Upload')
                                                    </button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">@lang('No file upload fields are configured on the KYC form yet.')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <x-form-generator-modal />
@endsection
