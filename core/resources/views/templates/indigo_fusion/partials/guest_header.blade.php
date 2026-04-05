<li><a class="{{ menuActive('home') }}" href="{{ route('home') }}">@lang('Home')</a></li>
@php
    $pages = App\Models\Page::where('tempname', $activeTemplate)
        ->where('is_default', 0)
        ->get();
@endphp
@foreach ($pages as $k => $data)
    @php
        $isSupportPage = strcasecmp($data->slug, 'support') === 0 || strcasecmp($data->name, 'Support') === 0;
    @endphp
    <li>
        <a class="@if ($isSupportPage ? request()->routeIs('ticket.*') : $data->slug == Request::segment(1)) active @endif" href="{{ $isSupportPage ? route('ticket.index') : route('pages', [$data->slug]) }}">
            {{ __($data->name) }}
        </a>
    </li>
@endforeach
<li><a class="{{ menuActive('contact') }}" href="{{ route('contact') }}">@lang('Contact')</a></li>
