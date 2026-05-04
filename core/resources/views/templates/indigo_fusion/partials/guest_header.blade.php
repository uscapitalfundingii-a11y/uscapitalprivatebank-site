<li><a class="{{ menuActive('home') }}" href="{{ route('home') }}">@lang('Home')</a></li>
<li><a class="{{ request()->is('pricing*') ? 'active' : '' }}" href="{{ url('/pricing/') }}">@lang('Pricing')</a></li>
@php
    $pages = App\Models\Page::where('tempname', $activeTemplate)
        ->where('is_default', 0)
        ->get();
@endphp
@foreach ($pages as $k => $data)
    @php
        $isSupportPage = strcasecmp($data->slug, 'support') === 0 || strcasecmp($data->name, 'Support') === 0;
        $isStaffPage = strcasecmp($data->slug, 'staff') === 0 || strcasecmp($data->name, 'Staff') === 0;
        $targetUrl = route('pages', [$data->slug]);

        if ($isSupportPage) {
            $targetUrl = url('/support/');
        } elseif ($isStaffPage) {
            $targetUrl = url('/crm/admin/authentication');
        }
    @endphp
    @if ($isStaffPage)
        <li>
            <a href="{{ url('/crm') }}">
                @lang('CRM')
            </a>
        </li>
    @endif
    <li>
        <a class="@if ((!$isSupportPage && !$isStaffPage) && $data->slug == Request::segment(1)) active @endif" href="{{ $targetUrl }}">
            {{ __($data->name) }}
        </a>
    </li>
@endforeach
<li><a class="{{ menuActive('contact') }}" href="{{ route('contact') }}">@lang('Contact')</a></li>
