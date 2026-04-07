@extends($activeTemplate . 'layouts.app')

@section('main-content')
    @include($activeTemplate . 'partials.header')
    @include('partials.public_verification_portal')
    @include($activeTemplate . 'partials.footer')
@endsection
