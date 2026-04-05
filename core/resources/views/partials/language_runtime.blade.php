@php
    use App\Constants\Status;
    use App\Models\Language;

    $languageCollection = Language::orderBy('is_default', 'desc')->get();
    $currentLanguage = session('lang') ?: optional($languageCollection->firstWhere('is_default', Status::YES))->code ?: 'en';
    $translateLanguage = Language::toTranslationLocale($currentLanguage);
    $includedLanguages = $languageCollection
        ->pluck('code')
        ->map(fn($code) => Language::toTranslationLocale($code))
        ->filter()
        ->unique()
        ->implode(',');
@endphp

<div id="google_translate_element_runtime" class="d-none"></div>

@if ($translateLanguage !== 'en')
    <button type="button" class="language-reset-button notranslate js-language-reset" data-reset-url="{{ route('lang', 'en') }}">
        Back to English
    </button>
@endif

<style>
    .goog-te-banner-frame.skiptranslate,
    .goog-te-gadget,
    .goog-te-gadget-simple,
    .goog-logo-link {
        display: none !important;
    }

    body {
        top: 0 !important;
    }

    .language-reset-button {
        position: fixed;
        right: 18px;
        bottom: 18px;
        z-index: 2147483647;
        border: 0;
        border-radius: 999px;
        padding: 10px 16px;
        background: #14213d;
        color: #fff;
        font-size: 14px;
        font-weight: 600;
        box-shadow: 0 10px 24px rgba(20, 33, 61, 0.22);
    }

    .language-reset-button:hover {
        background: #0f1a31;
    }
</style>

<script>
    "use strict";

    window.uscpLanguageRuntime = window.uscpLanguageRuntime || {
        targetLanguage: @json($translateLanguage),
        includedLanguages: @json($includedLanguages),
        resetUrl: @json(route('lang', 'en')),
        scriptLoaded: false,
        initialized: false,
        pendingLanguage: null
    };

    function uscpSetTranslateCookie(targetLanguage) {
        const cookieValue = '/auto/' + targetLanguage;
        const oneYear = 365 * 24 * 60 * 60;
        document.cookie = 'googtrans=' + cookieValue + '; path=/; max-age=' + oneYear;
        document.cookie = 'googtrans=' + cookieValue + '; domain=' + window.location.hostname + '; path=/; max-age=' + oneYear;
    }

    function uscpClearTranslateCookie() {
        const domains = [window.location.hostname, '.' + window.location.hostname];

        document.cookie = 'googtrans=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';

        domains.forEach(function(domain) {
            document.cookie = 'googtrans=; domain=' + domain + '; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
        });
    }

    function uscpTriggerTranslate(targetLanguage) {
        if (!targetLanguage || targetLanguage === 'en') {
            uscpClearTranslateCookie();
            return;
        }

        uscpSetTranslateCookie(targetLanguage);

        const combo = document.querySelector('.goog-te-combo');

        if (!combo) {
            window.uscpLanguageRuntime.pendingLanguage = targetLanguage;
            return;
        }

        if (combo.value !== targetLanguage) {
            combo.value = targetLanguage;
            combo.dispatchEvent(new Event('change'));
        }
    }

    function uscpInitGoogleTranslateElement() {
        if (window.uscpLanguageRuntime.initialized) {
            uscpTriggerTranslate(window.uscpLanguageRuntime.targetLanguage);
            return;
        }

        window.uscpLanguageRuntime.initialized = true;

        new google.translate.TranslateElement({
            pageLanguage: 'en',
            includedLanguages: window.uscpLanguageRuntime.includedLanguages,
            autoDisplay: false,
            multilanguagePage: true
        }, 'google_translate_element_runtime');

        window.setTimeout(function() {
            uscpTriggerTranslate(window.uscpLanguageRuntime.pendingLanguage || window.uscpLanguageRuntime.targetLanguage);
        }, 500);
    }

    window.googleTranslateElementInit = uscpInitGoogleTranslateElement;

    (function() {
        if (window.uscpLanguageRuntime.targetLanguage === 'en') {
            uscpClearTranslateCookie();
            return;
        }

        uscpSetTranslateCookie(window.uscpLanguageRuntime.targetLanguage);

        if (window.uscpLanguageRuntime.scriptLoaded) {
            return;
        }

        window.uscpLanguageRuntime.scriptLoaded = true;

        const script = document.createElement('script');
        script.src = 'https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
        script.async = true;
        document.body.appendChild(script);
    })();

    document.addEventListener('click', function(event) {
        const resetButton = event.target.closest('.js-language-reset');

        if (!resetButton) {
            return;
        }

        event.preventDefault();
        uscpClearTranslateCookie();
        window.location.href = resetButton.dataset.resetUrl || window.uscpLanguageRuntime.resetUrl;
    });
</script>
