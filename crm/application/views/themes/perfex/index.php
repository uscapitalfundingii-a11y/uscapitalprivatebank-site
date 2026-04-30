<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$crmPortalLanguages = [
    'en'    => 'English',
    'zh-CN' => 'Chinese (Simplified)',
    'es'    => 'Spanish',
    'hi'    => 'Hindi',
    'ar'    => 'Arabic',
    'fr'    => 'French',
    'bn'    => 'Bengali',
    'pt'    => 'Portuguese',
    'ru'    => 'Russian',
    'ur'    => 'Urdu',
    'id'    => 'Indonesian',
    'de'    => 'German',
    'ja'    => 'Japanese',
    'sw'    => 'Swahili',
    'te'    => 'Telugu',
    'mr'    => 'Marathi',
    'tr'    => 'Turkish',
    'ta'    => 'Tamil',
    'ko'    => 'Korean',
    'vi'    => 'Vietnamese',
    'it'    => 'Italian',
    'fa'    => 'Persian',
    'th'    => 'Thai',
    'gu'    => 'Gujarati',
    'pl'    => 'Polish',
    'uk'    => 'Ukrainian',
    'ml'    => 'Malayalam',
    'pa'    => 'Punjabi',
    'nl'    => 'Dutch',
    'ha'    => 'Hausa',
];
?>
<?= theme_head_view(); ?>
<?php get_template_part($navigationEnabled ? 'navigation' : ''); ?>
<style>
    .customers-top-submenu-language {
        margin-left: auto;
        min-width: 220px;
    }

    .customers-top-submenu-language select {
        background: #fff;
        border: 1px solid rgba(15, 39, 66, 0.12);
        border-radius: 999px;
        color: #183b56;
        font-size: 13px;
        font-weight: 600;
        height: 40px;
        padding: 0 16px;
        width: 100%;
    }

    .customers-top-submenu-language select:focus {
        border-color: rgba(200, 162, 77, 0.9);
        box-shadow: 0 0 0 3px rgba(200, 162, 77, 0.12);
        outline: 0;
    }

    .skiptranslate,
    .goog-te-banner-frame,
    .goog-te-balloon-frame,
    .goog-logo-link,
    .goog-te-gadget span {
        display: none !important;
    }

    body {
        top: 0 !important;
    }

    #crm-google-translate-element {
        height: 0;
        overflow: hidden;
        position: absolute;
        width: 0;
    }
</style>
<div id="wrapper">
    <div id="content">
        <div class="container">
            <div class="row">
                <?php get_template_part('alerts'); ?>
            </div>
        </div>
        <?php if (isset($knowledge_base_search)) { ?>
        <?php get_template_part('knowledge_base/search'); ?>
        <?php } ?>
        <div class="container">
            <?php hooks()->do_action('customers_content_container_start'); ?>
            <div class="row">
                <?php
            /**
             * Don't show calendar for invoices, estimates, proposals etc.. views where no navigation is included or in kb area
             */
            if (is_client_logged_in() && $subMenuEnabled && ! isset($knowledge_base_search)) { ?>
                <ul class="submenu customer-top-submenu">
                    <?php hooks()->do_action('before_customers_area_sub_menu_start'); ?>
                    <li class="customers-top-submenu-appointment">
                        <a href="https://www.uscapitalprivatebank.com/crm/appointment_manager/appointment_manager_client/public_form"
                            class="tw-inline-flex tw-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="tw-w-5 tw-h-5 tw-mr-1">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.75 3v2.25M17.25 3v2.25M3.75 8.25h16.5M4.5 6h15A1.5 1.5 0 0121 7.5v12A1.5 1.5 0 0119.5 21h-15A1.5 1.5 0 013 19.5v-12A1.5 1.5 0 014.5 6zm4.5 7.5h6" />
                            </svg>
                            <span>Book Appointment</span>
                        </a>
                    </li>
                    <li class="customers-top-submenu-calendar">
                        <a href="<?= site_url('clients/calendar'); ?>"
                            class="tw-inline-flex tw-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                stroke="currentColor" class="tw-w-5 tw-h-5 tw-mr-1">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z" />
                            </svg>
                            <span>
                                <?= _l('calendar'); ?>
                            </span>
                        </a>
                    </li>
                    <li class="customers-top-submenu-language">
                        <select aria-label="Client portal language" data-crm-translate-select>
                            <?php foreach ($crmPortalLanguages as $languageCode => $languageLabel) { ?>
                                <option value="<?= e($languageCode); ?>"<?= $languageCode === 'en' ? ' selected' : ''; ?>>
                                    <?= e($languageLabel); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </li>
                    <?php hooks()->do_action('after_customers_area_sub_menu_end'); ?>
                </ul>
                <div class="clearfix"></div>
                <?php } ?>
                <?= theme_template_view(); ?>
            </div>
        </div>
    </div>
</div>
</div>
<div id="crm-google-translate-element" aria-hidden="true"></div>
<?= theme_footer_view();

// Always have app_customers_footer() just before the closing </body>
app_customers_footer();
/**
 * Check for any alerts stored in session
 */
app_js_alerts();
?>
<script>
(function () {
    var storageKey = 'crm_portal_preferred_language';
    var cookiePrefix = 'googtrans=/en/';
    var selector = '[data-crm-translate-select]';
    var languageMap = <?= json_encode($crmPortalLanguages); ?>;
    var initialized = false;

    function setCookie(name, value) {
        var expires = new Date();
        expires.setFullYear(expires.getFullYear() + 1);
        var cookie = name + '=' + value + '; expires=' + expires.toUTCString() + '; path=/';
        if (location.protocol === 'https:') {
            cookie += '; secure';
        }
        document.cookie = cookie;
    }

    function syncSelectors(languageCode) {
        document.querySelectorAll(selector).forEach(function (select) {
            if (select.value !== languageCode && languageMap[languageCode]) {
                select.value = languageCode;
            }
        });
    }

    function applyLanguage(languageCode, forceReload) {
        var nextLanguage = languageMap[languageCode] ? languageCode : 'en';
        localStorage.setItem(storageKey, nextLanguage);
        setCookie('googtrans', cookiePrefix + nextLanguage);
        syncSelectors(nextLanguage);

        if (forceReload) {
            window.location.reload();
        }
    }

    window.crmInitGoogleTranslate = function () {
        if (!window.google || !window.google.translate || !window.google.translate.TranslateElement || initialized) {
            return;
        }

        initialized = true;
        new window.google.translate.TranslateElement({
            pageLanguage: 'en',
            autoDisplay: false,
            includedLanguages: Object.keys(languageMap).join(',')
        }, 'crm-google-translate-element');
    };

    document.addEventListener('DOMContentLoaded', function () {
        var storedLanguage = localStorage.getItem(storageKey) || 'en';
        syncSelectors(storedLanguage);
        applyLanguage(storedLanguage, false);

        document.querySelectorAll(selector).forEach(function (select) {
            select.addEventListener('change', function () {
                applyLanguage(select.value, true);
            });
        });
    });

    var script = document.createElement('script');
    script.src = 'https://translate.google.com/translate_a/element.js?cb=crmInitGoogleTranslate';
    script.async = true;
    document.head.appendChild(script);
})();
</script>
</body>

</html>
