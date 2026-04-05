@extends('admin.layouts.app')
@section('panel')
    <div class="row mb-none-30">
        <div class="col-lg-12 col-md-12 mb-30">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.stripe.config.update') }}">
                        @csrf
                        <div class="row">
                            <div class="form-group col-xl-6 col-sm-6">
                                <label> @lang('Stripe Secret Key')</label>
                                <input class="form-control" type="text" name="stripe_secret_key" required
                                    value="{{ gs('stripe_secret_key') }}">
                            </div>

                            <div class="form-group col-xl-6 col-sm-6">
                                <label> @lang('Stripe Publishable Key')</label>
                                <input class="form-control" type="text" name="stripe_publishable_key" required
                                    value="{{ gs('stripe_publishable_key') }}">
                            </div>

                            <div class="form-group col-xl-6 col-sm-6">
                                <label> @lang('Stripe Webhook Endpoint Secret')</label>
                                <input class="form-control" type="text" name="webhook_endpoint_secret"
                                    value="{{ gs('webhook_endpoint_secret') }}" required />
                            </div>

                            <div class="form-group col-xl-6 col-sm-6">
                                <label> @lang('Stripe Webhook URL')<small>(@lang('Copy and paste in Stripe Dashboard'))</small></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="{{ route('stripe.webhook') }}"
                                        readonly>
                                    <button type="button" class="copyInput input-group-text" title="@lang('Copy')"><i
                                            class="fas fa-copy"></i></button>
                                </div>
                            </div>

                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
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
    </script>
@endpush
