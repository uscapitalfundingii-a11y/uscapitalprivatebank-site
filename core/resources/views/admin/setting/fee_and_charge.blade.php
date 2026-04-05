@extends('admin.layouts.app')
@section('panel')
    <div class="row mb-none-30">
        <div class="col-lg-12 col-md-12 mb-30">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.fee_charge.update') }}">
                        @csrf
                        <div class="row">
                            <div class="form-group col-xl-6 col-sm-6">
                                <div class="form-group">
                                    <label> @lang('Card Issue Fee')</label>
                                    <div class="input-group">
                                        <input class="form-control" type="text" name="card_issue_fee" required value="{{ getAmount(gs('card_issue_fee')) }}">
                                        <div class="input-group-text">{{ __(gs('cur_text')) }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group col-xl-6 col-sm-6">
                                <label> @lang('Yearly Card Charge')</label>
                                <div class="input-group">
                                    <input class="form-control" type="text" name="yearly_card_charge" required value="{{ getAmount(gs('yearly_card_charge')) }}">
                                    <div class="input-group-text">{{ __(gs('cur_text')) }}</div>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn--primary w-100 h-45">@lang('Submit')</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
