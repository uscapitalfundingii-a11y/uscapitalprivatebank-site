@extends($activeTemplate . 'layouts.app')

@section('app')
    @include($activeTemplate . 'partials.header_top')
    @include($activeTemplate . 'partials.header')
    @include('partials.public_verification_portal')
    @include($activeTemplate . 'partials.footer')
@endsection
