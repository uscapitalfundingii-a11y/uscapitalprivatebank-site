<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$selectedLanguage = (get_contact_language() != '') ? get_contact_language() : get_option('active_language');
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
$siteLinks = [
    ['label' => 'Home', 'href' => 'https://uscapitalprivatebank.com/'],
    ['label' => 'Contact', 'href' => 'https://uscapitalprivatebank.org/contact'],
    ['label' => 'Branches', 'href' => 'https://uscapitalprivatebank.com/branches'],
    ['label' => 'Legal Identity', 'href' => site_url('corporate-identity-legal-independence')],
    ['label' => 'Support', 'href' => site_url('authentication/login')],
];
?>

<style>
    .crm-support-shell {
        --crm-navy: #0f2742;
        --crm-ink: #183b56;
        --crm-gold: #c8a24d;
        --crm-sand: #f5efe3;
        --crm-cream: #fcfaf5;
        --crm-line: rgba(15, 39, 66, 0.12);
        --crm-shadow: 0 24px 60px rgba(15, 39, 66, 0.12);
        background:
            radial-gradient(circle at top left, rgba(200, 162, 77, 0.22), transparent 30%),
            linear-gradient(180deg, #f8f4eb 0%, #f4efe4 46%, #fbfaf7 100%);
        margin: -15px;
        min-height: calc(100vh - 30px);
        padding: 0 0 60px;
    }

    .crm-support-shell,
    .crm-support-shell * {
        box-sizing: border-box;
    }

    .crm-support-nav {
        backdrop-filter: blur(18px);
        background: rgba(252, 250, 245, 0.88);
        border-bottom: 1px solid rgba(15, 39, 66, 0.08);
        position: sticky;
        top: 0;
        z-index: 20;
    }

    .crm-support-nav__inner,
    .crm-support-hero,
    .crm-support-grid {
        margin: 0 auto;
        max-width: 1180px;
        padding-left: 24px;
        padding-right: 24px;
    }

    .crm-support-nav__inner {
        align-items: center;
        display: flex;
        gap: 20px;
        justify-content: space-between;
        min-height: 84px;
    }

    .crm-support-brand {
        align-items: center;
        color: var(--crm-navy);
        display: flex;
        gap: 16px;
        text-decoration: none;
    }

    .crm-support-brand img {
        background: rgba(15, 39, 66, 0.05);
        border-radius: 20px;
        display: block;
        height: 78px;
        max-width: none;
        object-fit: contain;
        padding: 8px;
        width: 78px;
    }

    .crm-support-brand__meta {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .crm-support-brand__eyebrow {
        color: var(--crm-gold);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.22em;
        text-transform: uppercase;
    }

    .crm-support-brand__title {
        color: var(--crm-navy);
        font-family: Georgia, "Times New Roman", serif;
        font-size: 20px;
        font-weight: 700;
        line-height: 1.1;
    }

    .crm-support-menu {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-end;
    }

    .crm-support-menu a {
        border-radius: 999px;
        color: var(--crm-ink);
        font-size: 14px;
        font-weight: 600;
        padding: 10px 16px;
        text-decoration: none;
        transition: 0.2s ease;
    }

    .crm-support-menu a:hover,
    .crm-support-menu a.is-active {
        background: rgba(15, 39, 66, 0.08);
        color: var(--crm-navy);
    }

    .crm-support-menu__cta {
        background: var(--crm-navy);
        box-shadow: 0 12px 24px rgba(15, 39, 66, 0.18);
        color: #fff !important;
    }

    .crm-support-menu__cta:hover {
        background: #133354 !important;
    }

    .crm-support-hero {
        display: grid;
        gap: 32px;
        grid-template-columns: minmax(0, 1.15fr) minmax(380px, 0.85fr);
        padding-bottom: 0;
        padding-top: 44px;
    }

    .crm-support-copy {
        color: var(--crm-ink);
        padding: 36px 0 10px;
    }

    .crm-support-copy__badge {
        background: rgba(200, 162, 77, 0.14);
        border: 1px solid rgba(200, 162, 77, 0.3);
        border-radius: 999px;
        color: #8b6a24;
        display: inline-flex;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.16em;
        margin-bottom: 20px;
        padding: 9px 14px;
        text-transform: uppercase;
    }

    .crm-support-copy h1 {
        color: var(--crm-navy);
        font-family: Georgia, "Times New Roman", serif;
        font-size: 54px;
        font-weight: 700;
        letter-spacing: -0.03em;
        line-height: 1.02;
        margin: 0 0 18px;
        max-width: 11ch;
    }

    .crm-support-copy p {
        font-size: 17px;
        line-height: 1.8;
        margin: 0;
        max-width: 620px;
    }

    .crm-support-highlights {
        display: grid;
        gap: 14px;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        margin-top: 28px;
    }

    .crm-support-highlight {
        background: rgba(255, 255, 255, 0.72);
        border: 1px solid rgba(15, 39, 66, 0.08);
        border-radius: 22px;
        box-shadow: 0 18px 40px rgba(15, 39, 66, 0.06);
        padding: 18px 18px 16px;
    }

    .crm-support-highlight strong {
        color: var(--crm-navy);
        display: block;
        font-size: 15px;
        margin-bottom: 6px;
    }

    .crm-support-highlight span {
        color: #5b7184;
        display: block;
        font-size: 13px;
        line-height: 1.6;
    }

    .crm-support-card {
        background: rgba(255, 255, 255, 0.92);
        border: 1px solid rgba(15, 39, 66, 0.08);
        border-radius: 30px;
        box-shadow: var(--crm-shadow);
        overflow: hidden;
        position: relative;
    }

    .crm-support-card:before {
        background: linear-gradient(135deg, var(--crm-navy) 0%, #204a74 55%, #c8a24d 100%);
        content: "";
        height: 140px;
        left: 0;
        position: absolute;
        right: 0;
        top: 0;
    }

    .crm-support-card__inner {
        padding: 34px;
        position: relative;
        z-index: 1;
    }

    .crm-support-card__intro {
        color: #f6efe0;
        margin-bottom: 30px;
        min-height: 76px;
    }

    .crm-support-card__eyebrow {
        display: inline-block;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.2em;
        margin-bottom: 10px;
        text-transform: uppercase;
    }

    .crm-support-card__intro h2 {
        color: #fff;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 30px;
        line-height: 1.08;
        margin: 0;
    }

    .crm-support-form {
        background: #fffdfa;
        border: 1px solid rgba(15, 39, 66, 0.08);
        border-radius: 26px;
        padding: 24px;
    }

    .crm-support-form .form-group {
        margin-bottom: 18px;
    }

    .crm-support-form label {
        color: var(--crm-navy);
        display: block;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.04em;
        margin-bottom: 9px;
        text-transform: uppercase;
    }

    .crm-support-form .form-control,
    .crm-support-form .bootstrap-select > .dropdown-toggle {
        background: #fff;
        border: 1px solid rgba(15, 39, 66, 0.14);
        border-radius: 16px;
        box-shadow: none;
        color: var(--crm-ink);
        font-size: 15px;
        height: 56px;
        padding: 14px 16px;
    }

    .crm-support-form .form-control:focus {
        border-color: rgba(200, 162, 77, 0.9);
        box-shadow: 0 0 0 4px rgba(200, 162, 77, 0.12);
    }

    .crm-support-translate-note {
        color: #63798b;
        display: block;
        font-size: 12px;
        line-height: 1.65;
        margin-top: 8px;
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

    .crm-support-actions {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        justify-content: space-between;
        margin-top: 8px;
    }

    .crm-support-actions .checkbox {
        margin: 0;
    }

    .crm-support-actions .checkbox label,
    .crm-support-actions .checkbox span,
    .crm-support-actions .checkbox input {
        text-transform: none;
    }

    .crm-support-submit {
        background: linear-gradient(135deg, var(--crm-navy), #1d4973);
        border: 0;
        border-radius: 999px;
        box-shadow: 0 16px 30px rgba(15, 39, 66, 0.16);
        color: #fff;
        font-size: 15px;
        font-weight: 700;
        padding: 15px 22px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        width: 100%;
    }

    .crm-support-submit:hover {
        box-shadow: 0 18px 34px rgba(15, 39, 66, 0.22);
        color: #fff;
        transform: translateY(-1px);
    }

    .crm-support-links {
        color: #60788e;
        display: flex;
        flex-wrap: wrap;
        font-size: 14px;
        gap: 12px 18px;
        justify-content: space-between;
        margin-top: 16px;
    }

    .crm-support-links a {
        color: var(--crm-ink);
        font-weight: 600;
        text-decoration: none;
    }

    .crm-support-identity-link {
        color: #60788e;
        font-size: 13px;
        line-height: 1.7;
        margin-top: 16px;
        text-align: center;
    }

    .crm-support-identity-link a {
        color: var(--crm-navy);
        font-weight: 700;
        text-decoration: none;
    }

    .crm-support-grid {
        display: grid;
        gap: 18px;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        margin-top: 28px;
        padding-top: 0;
    }

    .crm-support-info {
        background: rgba(255, 255, 255, 0.78);
        border: 1px solid rgba(15, 39, 66, 0.08);
        border-radius: 22px;
        min-height: 100%;
        padding: 22px;
    }

    .crm-support-info h3 {
        color: var(--crm-navy);
        font-family: Georgia, "Times New Roman", serif;
        font-size: 24px;
        margin: 0 0 10px;
    }

    .crm-support-info p {
        color: #61788b;
        font-size: 14px;
        line-height: 1.7;
        margin: 0 0 12px;
    }

    .crm-support-info a {
        color: #9a7425;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-decoration: none;
        text-transform: uppercase;
    }

    .crm-support-card .alert,
    .crm-support-shell #alerts .alert {
        border-radius: 16px;
        margin-bottom: 18px;
    }

    @media (max-width: 991px) {
        .crm-support-nav__inner,
        .crm-support-hero,
        .crm-support-grid {
            padding-left: 18px;
            padding-right: 18px;
        }

        .crm-support-nav__inner,
        .crm-support-menu {
            align-items: flex-start;
            flex-direction: column;
        }

        .crm-support-hero {
            grid-template-columns: 1fr;
        }

        .crm-support-copy {
            padding-top: 8px;
        }

        .crm-support-copy h1 {
            font-size: 40px;
            max-width: none;
        }

        .crm-support-highlights,
        .crm-support-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .crm-support-shell {
            margin: -15px -15px 0;
        }

        .crm-support-brand {
            align-items: flex-start;
            flex-direction: column;
        }

        .crm-support-card__inner,
        .crm-support-form {
            padding: 20px;
        }

        .crm-support-copy h1 {
            font-size: 34px;
        }
    }
</style>

<div class="crm-support-shell">
    <div class="crm-support-nav">
        <div class="crm-support-nav__inner">
            <a class="crm-support-brand" href="https://uscapitalprivatebank.com/">
                <img src="<?= base_url('uploads/company/logo_dark.png'); ?>" alt="<?= e(get_option('companyname')); ?>">
                <span class="crm-support-brand__meta">
                    <span class="crm-support-brand__eyebrow">Client Support</span>
                    <span class="crm-support-brand__title"><?= e(get_option('companyname')); ?> CRM</span>
                </span>
            </a>
            <nav class="crm-support-menu" aria-label="Support Navigation">
                <?php foreach ($siteLinks as $link) { ?>
                    <a href="<?= $link['href']; ?>"<?= $link['label'] === 'Support' ? ' class="is-active"' : ''; ?>><?= $link['label']; ?></a>
                <?php } ?>
                <a class="crm-support-menu__cta" href="https://uscapitalprivatebank.com/user/login">Online Banking</a>
            </nav>
        </div>
    </div>

    <div class="crm-support-hero">
        <div class="crm-support-copy">
            <span class="crm-support-copy__badge">Private Banking Service Desk</span>
            <h1>Secure access for faster, cleaner transaction support.</h1>
            <p>Use the CRM portal to review service updates, documents, support conversations, appointments, and account coordination in one secure environment styled to feel like the main U.S. Capital Private Bank website.</p>
            <p>Clients who use the portal consistently typically experience smoother routing, fewer delays caused by missing details, and better communication with the bank throughout the life of a transaction.</p>

            <div class="crm-support-highlights">
                <div class="crm-support-highlight">
                    <strong>Secure sign-in</strong>
                    <span>Access client support activity, service requests, and transaction communication from one branded portal.</span>
                </div>
                <div class="crm-support-highlight">
                    <strong>Faster processing</strong>
                    <span>Complete portal use helps the bank process requests with fewer follow-up questions and less manual cleanup.</span>
                </div>
                <div class="crm-support-highlight">
                    <strong>Better coordination</strong>
                    <span>Projects, tickets, files, and updates stay tied to the right transaction instead of being scattered across channels.</span>
                </div>
            </div>
        </div>

        <div class="crm-support-card">
            <div class="crm-support-card__inner">
                <div class="crm-support-card__intro">
                    <span class="crm-support-card__eyebrow">Client Portal Access</span>
                    <h2><?= _l(get_option('allow_registration') == 1 ? 'clients_login_heading_register' : 'clients_login_heading_no_register'); ?></h2>
                </div>

                <div class="crm-support-form">
                    <?php if (!empty($GLOBALS['alert'])) { echo $GLOBALS['alert']; } ?>
                    <?= form_open($this->uri->uri_string(), ['class' => 'login-form']); ?>
                    <?php hooks()->do_action('clients_login_form_start'); ?>

                    <div class="form-group">
                        <label for="crm-portal-language-login">Client Portal Language</label>
                        <select id="crm-portal-language-login" class="form-control" data-crm-translate-select>
                            <?php foreach ($crmPortalLanguages as $languageCode => $languageLabel) { ?>
                                <option value="<?= e($languageCode); ?>"<?= $languageCode === 'en' ? ' selected' : ''; ?>>
                                    <?= e($languageLabel); ?>
                                </option>
                            <?php } ?>
                        </select>
                        <span class="crm-support-translate-note">Choose the language the client should see on-screen. The bank can continue working internally in English while the portal view is translated for the client.</span>
                    </div>

                    <div class="form-group">
                        <label for="email"><?= _l('clients_login_email'); ?></label>
                        <input type="text" autofocus="true" class="form-control" name="email" id="email" placeholder="<?= _l('clients_login_email'); ?>">
                        <?= form_error('email'); ?>
                    </div>

                    <div class="form-group">
                        <label for="password"><?= _l('clients_login_password'); ?></label>
                        <input type="password" class="form-control" name="password" id="password" placeholder="<?= _l('clients_login_password'); ?>">
                        <?= form_error('password'); ?>
                    </div>

                    <?php if (show_recaptcha_in_customers_area()) { ?>
                        <div class="form-group">
                            <div class="g-recaptcha tw-mb-4" data-sitekey="<?= get_option('recaptcha_site_key'); ?>"></div>
                            <?= form_error('g-recaptcha-response'); ?>
                        </div>
                    <?php } ?>

                    <div class="crm-support-actions">
                        <div class="checkbox">
                            <input type="checkbox" name="remember" id="remember">
                            <label for="remember"><?= _l('clients_login_remember'); ?></label>
                        </div>
                    </div>

                    <div class="form-group tw-mt-6">
                        <button type="submit" class="crm-support-submit">
                            <?= _l('clients_login_login_string'); ?>
                        </button>
                    </div>

                    <div class="crm-support-links">
                        <a href="<?= site_url('authentication/forgot_password'); ?>"><?= _l('customer_forgot_password'); ?></a>
                        <?php if (get_option('allow_registration') == 1) { ?>
                            <a href="<?= site_url('authentication/register'); ?>"><?= _l('clients_register_string'); ?></a>
                            <a href="<?= site_url('authentication/register'); ?>#wizard">Use guided registration</a>
                        <?php } ?>
                    </div>
                    <div class="crm-support-identity-link">
                        Need corporate verification? <a href="<?= site_url('corporate-identity-legal-independence'); ?>" target="_blank" rel="noopener">View Legal / Corporate Identity</a>
                    </div>

                    <?php hooks()->do_action('clients_login_form_end'); ?>
                    <?= form_close(); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="crm-support-grid">
        <div class="crm-support-info">
            <h3>Client Care</h3>
            <p>Coordinate support requests, follow document updates, and keep transaction communication inside one secure workspace instead of fragmented email chains.</p>
            <a href="https://uscapitalprivatebank.org/contact">Contact Support</a>
        </div>
        <div class="crm-support-info">
            <h3>Guided Onboarding</h3>
            <p>New client? Register with the full form or choose the guided wizard so you can complete your profile step by step and reduce onboarding delays.</p>
            <a href="<?= site_url('authentication/register'); ?>#wizard">Start Guided Registration</a>
        </div>
        <div class="crm-support-info">
            <h3>Profile Readiness</h3>
            <p>Thorough and accurate client details help support legal, regulatory, and internal review requirements while improving response quality from the bank.</p>
            <a href="https://uscapitalprivatebank.com/user/login">Online Banking Access</a>
        </div>
    </div>
</div>
<div id="crm-google-translate-element" aria-hidden="true"></div>
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
