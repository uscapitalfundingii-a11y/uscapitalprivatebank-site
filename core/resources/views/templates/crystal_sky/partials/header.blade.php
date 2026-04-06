<header class="header" id="header">
    <div class="container">
        <nav class="navbar navbar-expand-lg navbar-light">
            <a class="navbar-brand logo" href="{{ route('home') }}">
                <img src="{{ siteLogo('dark') }}" alt="@lang('image')" />
            </a>
            <button class="navbar-toggler header-button" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span id="hiddenNav"><i class="las la-bars"></i></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav nav-menu ms-auto align-items-lg-center">
                    <li class="nav-item d-block d-lg-none">
                        <div class="top-button d-flex flex-wrap justify-content-between align-items-center"></div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ menuActive('home') }}" aria-current="page" href="{{ route('home') }}">@lang('Home')</a>
                    </li>
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
                        <li class="nav-item">
                            <a class="nav-link @if ((!$isSupportPage && !$isStaffPage) && $data->slug == Request::segment(1)) active @endif" aria-current="page" href="{{ $targetUrl }}">{{ __($data->name) }}</a>
                        </li>
                    @endforeach
                    <li class="nav-item">
                        <a class="nav-link {{ menuActive('contact') }}" href="{{ route('contact') }}">@lang('Contact')</a>
                    </li>

                </ul>
                <div class="nav-right">
                    @if (gs('multi_language'))
                        @php
                            $language = App\Models\Language::all();
                            $currentLang = session('lang') ? $language->where('code', session('lang'))->first() : $language->where('is_default', Status::YES)->first();
                        @endphp

                        @if ($language->count())
                            <div class="language_switcher">
                                <div class="language_switcher__caption">
                                    <span class="icon">
                                        <img src="{{ getImage(getFilePath('language') . '/' . @$currentLang->image, getFileSize('language')) }}" alt="@lang('image')">
                                    </span>
                                    <span class="text"> {{ __(@$currentLang->name) }} </span>
                                </div>
                                <div class="language_switcher__list">
                                    @foreach ($language as $item)
                                        <div class="language_switcher__item    @if (session('lang') == $item->code) selected @endif" data-value="{{ $item->code }}">
                                            <a href="{{ route('lang', $item->code) }}" class="thumb">
                                                <span class="icon">
                                                    <img src="{{ getImage(getFilePath('language') . '/' . $item->image, getFileSize('language')) }}" alt="@lang('image')">
                                                </span>
                                                <span class="text"> {{ __($item->name) }}</span>
                                            </a>
                                        </div>
                                    @endforeach
                                    <div class="language_switcher__item">
                                        <a href="{{ route('lang', 'en') }}" class="thumb notranslate js-language-reset">
                                            <span class="icon">
                                                <img src="{{ getImage(getFilePath('language') . '/' . optional($language->firstWhere('code', 'en'))->image, getFileSize('language')) }}" alt="@lang('image')">
                                            </span>
                                            <span class="text notranslate">Back to English</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                    <div class="signin-btn">
                        @auth
                            <a href="{{ route('user.home') }}" class="btn btn--base">
                                @lang('Dashboard')
                            </a>
                        @else
                            <a href="{{ route('user.login') }}" class="btn btn--base">
                                <i class="las la-sign-in-alt"></i>
                                @lang('Sign In')
                            </a>
                            <a href="{{ route('user.logout') }}" class="logout-btn v-hidden"></a>
                        @endauth
                    </div>
                </div>
            </div>
        </nav>
    </div>
</header>
