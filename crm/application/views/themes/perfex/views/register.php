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
    ['label' => 'Services', 'href' => 'https://uscapitalprivatebank.com/services'],
    ['label' => 'FAQ', 'href' => 'https://uscapitalprivatebank.com/faq'],
    ['label' => 'Support', 'href' => site_url('authentication/login')],
];
$bookAppointmentUrl = 'https://www.uscapitalprivatebank.com/crm/appointment_manager/appointment_manager_client/public_form';
$wizardSteps = [
    1 => 'Primary Contact',
    2 => 'Company Profile',
    3 => 'Location & Communication',
    4 => 'Security & Submission',
];
?>

<style>
    .crm-register-shell {
        --crm-navy: #0f2742;
        --crm-ink: #183b56;
        --crm-gold: #c8a24d;
        --crm-sand: #f5efe3;
        --crm-cream: #fcfaf5;
        --crm-line: rgba(15, 39, 66, 0.12);
        --crm-shadow: 0 24px 60px rgba(15, 39, 66, 0.14);
        background:
            radial-gradient(circle at top left, rgba(200, 162, 77, 0.22), transparent 30%),
            linear-gradient(180deg, #f8f4eb 0%, #f4efe4 46%, #fbfaf7 100%);
        margin: -15px;
        min-height: calc(100vh - 30px);
        padding: 0 0 60px;
    }

    .crm-register-shell,
    .crm-register-shell * {
        box-sizing: border-box;
    }

    .crm-register-nav {
        backdrop-filter: blur(18px);
        background: rgba(252, 250, 245, 0.88);
        border-bottom: 1px solid rgba(15, 39, 66, 0.08);
        position: sticky;
        top: 0;
        z-index: 20;
    }

    .crm-register-nav__inner,
    .crm-register-hero,
    .crm-register-content {
        margin: 0 auto;
        max-width: 1240px;
        padding-left: 24px;
        padding-right: 24px;
    }

    .crm-register-nav__inner {
        align-items: center;
        display: flex;
        gap: 20px;
        justify-content: space-between;
        min-height: 84px;
    }

    .crm-register-brand {
        align-items: center;
        color: var(--crm-navy);
        display: flex;
        gap: 16px;
        text-decoration: none;
    }

    .crm-register-brand img {
        background: rgba(15, 39, 66, 0.04);
        border-radius: 18px;
        display: block;
        height: 64px;
        object-fit: contain;
        padding: 6px;
        width: 64px;
    }

    .crm-register-brand__meta {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .crm-register-brand__eyebrow {
        color: var(--crm-gold);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.22em;
        text-transform: uppercase;
    }

    .crm-register-brand__title {
        color: var(--crm-navy);
        font-family: Georgia, "Times New Roman", serif;
        font-size: 20px;
        font-weight: 700;
        line-height: 1.1;
    }

    .crm-register-menu {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-end;
    }

    .crm-register-menu a {
        border-radius: 999px;
        color: var(--crm-ink);
        font-size: 14px;
        font-weight: 600;
        padding: 10px 16px;
        text-decoration: none;
        transition: 0.2s ease;
    }

    .crm-register-menu a:hover,
    .crm-register-menu a.is-active {
        background: rgba(15, 39, 66, 0.08);
        color: var(--crm-navy);
    }

    .crm-register-menu__cta {
        background: linear-gradient(135deg, var(--crm-navy), #1d4973);
        box-shadow: 0 12px 24px rgba(15, 39, 66, 0.18);
        color: #fff !important;
    }

    .crm-register-hero {
        display: grid;
        gap: 30px;
        grid-template-columns: minmax(0, 1.05fr) minmax(420px, 0.95fr);
        padding-top: 42px;
    }

    .crm-register-copy {
        color: var(--crm-ink);
        padding: 30px 0 16px;
    }

    .crm-register-copy__badge {
        background: rgba(200, 162, 77, 0.14);
        border: 1px solid rgba(200, 162, 77, 0.28);
        border-radius: 999px;
        color: #8b6a24;
        display: inline-flex;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.16em;
        margin-bottom: 18px;
        padding: 9px 14px;
        text-transform: uppercase;
    }

    .crm-register-copy h1 {
        color: var(--crm-navy);
        font-family: Georgia, "Times New Roman", serif;
        font-size: 56px;
        font-weight: 700;
        letter-spacing: -0.03em;
        line-height: 1.02;
        margin: 0 0 18px;
        max-width: 11ch;
    }

    .crm-register-copy p {
        font-size: 17px;
        line-height: 1.8;
        margin: 0 0 16px;
        max-width: 650px;
    }

    .crm-register-proof {
        display: grid;
        gap: 14px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        margin-top: 24px;
    }

    .crm-register-proof__card {
        background: rgba(255, 255, 255, 0.76);
        border: 1px solid rgba(15, 39, 66, 0.08);
        border-radius: 22px;
        box-shadow: 0 16px 34px rgba(15, 39, 66, 0.06);
        padding: 18px 18px 16px;
    }

    .crm-register-proof__card strong {
        color: var(--crm-navy);
        display: block;
        font-size: 15px;
        margin-bottom: 6px;
    }

    .crm-register-proof__card span {
        color: #61788b;
        display: block;
        font-size: 13px;
        line-height: 1.6;
    }

    .crm-register-panel {
        background: rgba(255, 255, 255, 0.94);
        border: 1px solid rgba(15, 39, 66, 0.08);
        border-radius: 30px;
        box-shadow: var(--crm-shadow);
        overflow: hidden;
        position: relative;
    }

    .crm-register-panel:before {
        background: linear-gradient(135deg, var(--crm-navy) 0%, #204a74 58%, #c8a24d 100%);
        content: "";
        height: 148px;
        left: 0;
        position: absolute;
        right: 0;
        top: 0;
    }

    .crm-register-panel__inner {
        padding: 32px;
        position: relative;
        z-index: 1;
    }

    .crm-register-panel__intro {
        color: #f8f1e3;
        margin-bottom: 24px;
    }

    .crm-register-panel__eyebrow {
        display: inline-block;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.2em;
        margin-bottom: 10px;
        text-transform: uppercase;
    }

    .crm-register-panel__intro h2 {
        color: #fff;
        font-family: Georgia, "Times New Roman", serif;
        font-size: 31px;
        line-height: 1.08;
        margin: 0 0 12px;
    }

    .crm-register-panel__intro p {
        color: rgba(248, 241, 227, 0.9);
        font-size: 14px;
        line-height: 1.7;
        margin: 0;
    }

    .crm-register-content {
        margin-top: 18px;
    }

    .crm-register-form-wrap {
        background: #fffdfa;
        border: 1px solid rgba(15, 39, 66, 0.08);
        border-radius: 28px;
        box-shadow: var(--crm-shadow);
        padding: 28px;
    }

    .crm-register-alert,
    .crm-register-form-wrap .alert {
        border-radius: 16px;
        margin-bottom: 18px;
    }

    .crm-register-toolbar {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        justify-content: space-between;
        margin-bottom: 22px;
    }

    .crm-register-language {
        min-width: 220px;
    }

    .crm-register-translate-note {
        color: #61788b;
        display: block;
        font-size: 12px;
        line-height: 1.65;
        margin-top: 8px;
        max-width: 320px;
    }

    .crm-register-mode {
        background: rgba(15, 39, 66, 0.05);
        border-radius: 999px;
        display: inline-flex;
        gap: 8px;
        padding: 6px;
    }

    .crm-register-mode button {
        background: transparent;
        border: 0;
        border-radius: 999px;
        color: var(--crm-ink);
        font-size: 13px;
        font-weight: 700;
        padding: 10px 16px;
        transition: 0.2s ease;
    }

    .crm-register-mode button.is-active {
        background: linear-gradient(135deg, var(--crm-navy), #1d4973);
        box-shadow: 0 12px 24px rgba(15, 39, 66, 0.16);
        color: #fff;
    }

    .crm-register-form {
        display: grid;
        gap: 18px;
    }

    .crm-register-progress {
        display: none;
        margin-bottom: 18px;
    }

    .crm-register-form.is-wizard .crm-register-progress {
        display: block;
    }

    .crm-register-progress__head {
        align-items: center;
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }

    .crm-register-progress__label {
        color: var(--crm-navy);
        font-size: 15px;
        font-weight: 700;
    }

    .crm-register-progress__step {
        color: #6a8093;
        font-size: 13px;
        font-weight: 600;
    }

    .crm-register-progress__bar {
        background: rgba(15, 39, 66, 0.08);
        border-radius: 999px;
        height: 10px;
        overflow: hidden;
    }

    .crm-register-progress__fill {
        background: linear-gradient(135deg, var(--crm-gold), #c58b2b);
        border-radius: inherit;
        height: 100%;
        transition: width 0.24s ease;
        width: 25%;
    }

    .crm-register-step {
        background: rgba(255, 255, 255, 0.86);
        border: 1px solid rgba(15, 39, 66, 0.08);
        border-radius: 22px;
        padding: 22px;
    }

    .crm-register-step h3 {
        color: var(--crm-navy);
        font-family: Georgia, "Times New Roman", serif;
        font-size: 26px;
        margin: 0 0 8px;
    }

    .crm-register-step p {
        color: #647a8e;
        font-size: 14px;
        line-height: 1.7;
        margin: 0 0 18px;
    }

    .crm-register-grid {
        display: grid;
        gap: 16px 24px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .crm-register-field label {
        color: var(--crm-navy);
        display: block;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.04em;
        margin-bottom: 9px;
        text-transform: uppercase;
    }

    .crm-register-field .help-block,
    .crm-register-field .text-danger {
        display: block;
        margin-top: 6px;
    }

    .crm-register-form .form-control,
    .crm-register-form .bootstrap-select > .dropdown-toggle {
        background: #fff;
        border: 1px solid rgba(15, 39, 66, 0.14);
        border-radius: 16px;
        box-shadow: none;
        color: var(--crm-ink);
        font-size: 15px;
        min-height: 56px;
        padding: 14px 16px;
    }

    .crm-register-form textarea.form-control {
        min-height: 124px;
    }

    .crm-register-form .form-control:focus {
        border-color: rgba(200, 162, 77, 0.9);
        box-shadow: 0 0 0 4px rgba(200, 162, 77, 0.12);
    }

    .crm-register-form .bootstrap-select .dropdown-toggle .filter-option {
        align-items: center;
        display: flex;
        min-height: 24px;
    }

    .crm-register-persuasion {
        background: linear-gradient(135deg, rgba(15, 39, 66, 0.05), rgba(200, 162, 77, 0.12));
        border: 1px solid rgba(15, 39, 66, 0.08);
        border-radius: 22px;
        padding: 20px;
    }

    .crm-register-persuasion h4 {
        color: var(--crm-navy);
        font-size: 19px;
        font-weight: 700;
        margin: 0 0 10px;
    }

    .crm-register-persuasion p,
    .crm-register-persuasion li {
        color: #5e7488;
        font-size: 14px;
        line-height: 1.75;
    }

    .crm-register-persuasion ul {
        margin: 0;
        padding-left: 20px;
    }

    .crm-register-photo-note {
        color: #7a8794;
        font-size: 13px;
        line-height: 1.7;
        margin-top: 12px;
    }

    .crm-register-actions {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        justify-content: space-between;
        margin-top: 8px;
    }

    .crm-register-actions__left,
    .crm-register-actions__right {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .crm-register-btn,
    .crm-register-actions .btn {
        border-radius: 999px;
        font-weight: 700;
        min-height: 50px;
        padding: 12px 20px;
    }

    .crm-register-btn--primary {
        background: linear-gradient(135deg, var(--crm-navy), #1d4973);
        border: 0;
        box-shadow: 0 16px 30px rgba(15, 39, 66, 0.16);
        color: #fff;
    }

    .crm-register-btn--ghost {
        background: #fff;
        border: 1px solid rgba(15, 39, 66, 0.14);
        color: var(--crm-ink);
    }

    .crm-register-btn--wizard {
        background: linear-gradient(135deg, #d9b66a, #c58b2b);
        border: 0;
        color: #fff;
    }

    .crm-register-btn--appointment,
    .crm-register-menu__appointment {
        background: linear-gradient(135deg, #d9b66a, #c58b2b);
        border: 0;
        box-shadow: 0 14px 26px rgba(156, 108, 31, 0.2);
        color: #fff !important;
    }

    .crm-register-wizard-nav {
        display: none;
        justify-content: space-between;
        margin-top: 10px;
    }

    .crm-register-form.is-wizard .crm-register-wizard-nav {
        display: flex;
    }

    .crm-register-form.is-wizard .crm-register-step {
        display: none;
    }

    .crm-register-form.is-wizard .crm-register-step.is-current {
        display: block;
    }

    .crm-register-login-note {
        color: #60788e;
        font-size: 14px;
    }

    .crm-register-login-note a {
        color: var(--crm-navy);
        font-weight: 700;
        text-decoration: none;
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

    @media (max-width: 1100px) {
        .crm-register-hero {
            grid-template-columns: 1fr;
        }

        .crm-register-copy {
            padding-top: 10px;
        }
    }

    @media (max-width: 860px) {
        .crm-register-nav__inner,
        .crm-register-menu {
            align-items: flex-start;
            flex-direction: column;
        }

        .crm-register-proof,
        .crm-register-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .crm-register-shell {
            margin: -15px -15px 0;
        }

        .crm-register-copy h1 {
            font-size: 36px;
            max-width: none;
        }

        .crm-register-panel__inner,
        .crm-register-form-wrap {
            padding: 20px;
        }

        .crm-register-toolbar,
        .crm-register-actions,
        .crm-register-actions__left,
        .crm-register-actions__right {
            align-items: stretch;
            flex-direction: column;
        }

        .crm-register-language {
            width: 100%;
        }

        .crm-register-mode {
            width: 100%;
        }

        .crm-register-mode button,
        .crm-register-btn,
        .crm-register-actions .btn {
            width: 100%;
        }
    }
</style>

<div class="crm-register-shell">
    <div class="crm-register-nav">
        <div class="crm-register-nav__inner">
            <a class="crm-register-brand" href="https://uscapitalprivatebank.com/">
                <img src="<?= base_url('uploads/company/logo_dark.png'); ?>" alt="<?= e(get_option('companyname')); ?>">
                <span class="crm-register-brand__meta">
                    <span class="crm-register-brand__eyebrow">Private Banking Portal</span>
                    <span class="crm-register-brand__title"><?= e(get_option('companyname')); ?></span>
                </span>
            </a>
            <nav class="crm-register-menu" aria-label="Portal Navigation">
                <?php foreach ($siteLinks as $link) { ?>
                    <a href="<?= $link['href']; ?>"<?= $link['label'] === 'Support' ? ' class="is-active"' : ''; ?>><?= $link['label']; ?></a>
                <?php } ?>
                <a class="crm-register-menu__cta crm-register-menu__appointment" href="<?= e($bookAppointmentUrl); ?>">Book Appointment</a>
                <a class="crm-register-menu__cta" href="<?= site_url('authentication/login'); ?>">Client Login</a>
            </nav>
        </div>
    </div>

    <div class="crm-register-hero">
        <div class="crm-register-copy">
            <span class="crm-register-copy__badge">Secure Client Onboarding</span>
            <h1>Register once. Move every transaction faster.</h1>
            <p>Creating a complete client profile helps the bank route your requests correctly, reduce delays, and maintain one secure channel for documents, updates, appointments, and project communication.</p>
            <p>Clients who complete their information thoroughly tend to experience faster internal processing, clearer communication with relationship teams, and fewer follow-up requests for missing details.</p>

            <div class="crm-register-proof">
                <div class="crm-register-proof__card">
                    <strong>Faster processing</strong>
                    <span>Accurate information helps the bank review and route your requests without avoidable back-and-forth.</span>
                </div>
                <div class="crm-register-proof__card">
                    <strong>Better communication</strong>
                    <span>A complete profile gives your assigned team the context needed to support you efficiently.</span>
                </div>
                <div class="crm-register-proof__card">
                    <strong>Regulatory readiness</strong>
                    <span>Structured information supports internal compliance standards and formal transaction handling.</span>
                </div>
                <div class="crm-register-proof__card">
                    <strong>Professional presentation</strong>
                    <span>Thorough submissions demonstrate attention to detail and build confidence in the onboarding process.</span>
                </div>
            </div>
        </div>

        <div class="crm-register-panel">
            <div class="crm-register-panel__inner">
                <div class="crm-register-panel__intro">
                    <span class="crm-register-panel__eyebrow">Guided Registration</span>
                    <h2>Choose the pace that works best for you.</h2>
                    <p>Use the full form if you prefer to complete everything at once, or switch to the guided wizard for a step-by-step onboarding path.</p>
                </div>

                <div class="crm-register-persuasion">
                    <h4>Why complete every section?</h4>
                    <p>Completing forms thoroughly and accurately helps ensure smooth processing, avoids errors or rejections, supports legal and regulatory requirements, and improves communication with the bank.</p>
                    <ul>
                        <li>Accurate information reduces delays caused by missing or unclear details.</li>
                        <li>Standardized profile data makes internal processing faster and more efficient.</li>
                        <li>Complete submissions support informed decisions and cleaner record-keeping.</li>
                        <li>Well-prepared onboarding demonstrates professionalism and builds trust.</li>
                    </ul>
                    <p class="crm-register-photo-note">Please take a moment to complete all the information and upload a courtesy photo once your portal access is active. Thank you for using U.S. Capital Private Bank.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="crm-register-content">
        <div class="crm-register-form-wrap">
            <?= form_open('authentication/register', ['id' => 'register-form', 'class' => 'crm-register-form']); ?>

            <?php if (!empty($GLOBALS['alert'])) { echo $GLOBALS['alert']; } ?>
            <?= validation_errors('<div class="alert alert-danger crm-register-alert">', '</div>'); ?>

            <div class="crm-register-toolbar">
                <div class="crm-register-mode" role="group" aria-label="Registration Mode">
                    <button type="button" class="is-active" data-register-mode="standard">Complete Form</button>
                    <button type="button" data-register-mode="wizard">Use Guided Wizard</button>
                </div>
                <div class="crm-register-language">
                    <select id="crm-portal-language-register" class="form-control" data-crm-translate-select>
                        <?php foreach ($crmPortalLanguages as $languageCode => $languageLabel) { ?>
                            <option value="<?= e($languageCode); ?>"<?= $languageCode === 'en' ? ' selected' : ''; ?>>
                                <?= e($languageLabel); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <span class="crm-register-translate-note">Clients can select the language they want to read during onboarding, including Swahili and other widely used global languages.</span>
                </div>
            </div>

            <div class="crm-register-progress" aria-live="polite">
                <div class="crm-register-progress__head">
                    <span class="crm-register-progress__label" data-current-step-label><?= $wizardSteps[1]; ?></span>
                    <span class="crm-register-progress__step" data-current-step-count>Step 1 of 4</span>
                </div>
                <div class="crm-register-progress__bar">
                    <div class="crm-register-progress__fill" data-current-step-fill></div>
                </div>
            </div>

            <section class="crm-register-step is-current" data-step="1">
                <h3>Primary Contact Information</h3>
                <p>Enter the main client contact details exactly as they should appear in support records and transaction correspondence.</p>
                <div class="crm-register-grid">
                    <div class="crm-register-field register-firstname-group">
                        <label for="<?= e($fields['firstname']); ?>"><span class="text-danger">*</span> <?= _l('clients_firstname'); ?></label>
                        <input type="text" class="form-control" name="<?= e($fields['firstname']); ?>" id="<?= e($fields['firstname']); ?>" value="<?= set_value($fields['firstname']); ?>">
                        <?= form_error($fields['firstname']); ?>
                    </div>
                    <div class="crm-register-field register-lastname-group">
                        <label for="<?= e($fields['lastname']); ?>"><span class="text-danger">*</span> <?= _l('clients_lastname'); ?></label>
                        <input type="text" class="form-control" name="<?= e($fields['lastname']); ?>" id="<?= e($fields['lastname']); ?>" value="<?= set_value($fields['lastname']); ?>">
                        <?= form_error($fields['lastname']); ?>
                    </div>
                    <div class="crm-register-field register-email-group">
                        <label for="<?= e($fields['email']); ?>"><span class="text-danger">*</span> <?= _l('clients_email'); ?></label>
                        <input type="email" class="form-control" name="<?= e($fields['email']); ?>" id="<?= e($fields['email']); ?>" value="<?= set_value($fields['email']); ?>">
                        <?= form_error($fields['email']); ?>
                    </div>
                    <div class="crm-register-field register-contact-phone-group">
                        <label for="contact_phonenumber">
                            <?php if ($requiredFields['contact']['contact_contact_phonenumber']['is_required']) { ?><span class="text-danger">*</span><?php } ?>
                            <?= _l('clients_phone'); ?>
                        </label>
                        <input type="text" class="form-control" name="contact_phonenumber" id="contact_phonenumber" value="<?= set_value('contact_phonenumber'); ?>">
                        <?= form_error('contact_phonenumber'); ?>
                    </div>
                    <div class="crm-register-field register-website-group">
                        <label for="website">
                            <?php if ($requiredFields['contact']['contact_website']['is_required']) { ?><span class="text-danger">*</span><?php } ?>
                            <?= _l('client_website'); ?>
                        </label>
                        <input type="text" class="form-control" name="website" id="website" value="<?= set_value('website'); ?>">
                        <?= form_error('website'); ?>
                    </div>
                    <div class="crm-register-field register-position-group">
                        <label for="title">
                            <?php if ($requiredFields['contact']['contact_title']['is_required']) { ?><span class="text-danger">*</span><?php } ?>
                            <?= _l('contact_position'); ?>
                        </label>
                        <input type="text" class="form-control" name="title" id="title" value="<?= set_value('title'); ?>">
                        <?= form_error('title'); ?>
                    </div>
                </div>
                <div class="register-contact-custom-fields">
                    <?= render_custom_fields('contacts', '', ['show_on_client_portal' => 1]); ?>
                </div>
            </section>

            <section class="crm-register-step" data-step="2">
                <h3>Company & Service Profile</h3>
                <p>Provide the institution or business details that help the bank identify the correct relationship and service framework for your account.</p>
                <div class="crm-register-grid">
                    <div class="crm-register-field register-company-group">
                        <label for="<?= e($fields['company']); ?>">
                            <?php if (get_option('company_is_required') == 1) { ?><span class="text-danger">*</span><?php } ?>
                            <?= _l('clients_company'); ?>
                        </label>
                        <input type="text" class="form-control" name="<?= e($fields['company']); ?>" id="<?= e($fields['company']); ?>" value="<?= set_value($fields['company']); ?>">
                        <?= form_error($fields['company']); ?>
                    </div>
                    <?php if (get_option('company_requires_vat_number_field') == 1) { ?>
                    <div class="crm-register-field register-vat-group">
                        <label for="vat">
                            <?php if ($requiredFields['company']['company_vat']['is_required']) { ?><span class="text-danger">*</span><?php } ?>
                            <?= _l('clients_vat'); ?>
                        </label>
                        <input type="text" class="form-control" name="vat" id="vat" value="<?= set_value('vat'); ?>">
                        <?= form_error('vat'); ?>
                    </div>
                    <?php } ?>
                    <div class="crm-register-field register-company-phone-group">
                        <label for="phonenumber">
                            <?php if ($requiredFields['company']['company_phonenumber']['is_required']) { ?><span class="text-danger">*</span><?php } ?>
                            <?= _l('clients_phone'); ?>
                        </label>
                        <input type="text" class="form-control" name="phonenumber" id="phonenumber" value="<?= set_value('phonenumber'); ?>">
                        <?= form_error('phonenumber'); ?>
                    </div>
                    <div class="register-company-custom-fields">
                        <?= render_custom_fields('customers', '', ['show_on_client_portal' => 1]); ?>
                    </div>
                </div>
            </section>

            <section class="crm-register-step" data-step="3">
                <h3>Location & Communication Details</h3>
                <p>These details help route documentation, regional handling, and future communication more efficiently across the bank.</p>
                <div class="crm-register-grid">
                    <div class="crm-register-field register-country-group">
                        <label for="country">
                            <?php if ($requiredFields['company']['company_country']['is_required']) { ?><span class="text-danger">*</span><?php } ?>
                            <?= _l('clients_country'); ?>
                        </label>
                        <select data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>" data-live-search="true" name="country" class="form-control" id="country">
                            <option value=""></option>
                            <?php foreach (get_all_countries() as $country) { ?>
                                <option value="<?= e($country['country_id']); ?>"<?php if (get_option('customer_default_country') == $country['country_id']) { echo ' selected'; } ?> <?= set_select('country', $country['country_id']); ?>><?= e($country['short_name']); ?></option>
                            <?php } ?>
                        </select>
                        <?= form_error('country'); ?>
                    </div>
                    <div class="crm-register-field register-city-group">
                        <label for="city">
                            <?php if ($requiredFields['company']['company_city']['is_required']) { ?><span class="text-danger">*</span><?php } ?>
                            <?= _l('clients_city'); ?>
                        </label>
                        <input type="text" class="form-control" name="city" id="city" value="<?= set_value('city'); ?>">
                        <?= form_error('city'); ?>
                    </div>
                    <div class="crm-register-field register-address-group">
                        <label for="address">
                            <?php if ($requiredFields['company']['company_address']['is_required']) { ?><span class="text-danger">*</span><?php } ?>
                            <?= _l('clients_address'); ?>
                        </label>
                        <input type="text" class="form-control" name="address" id="address" value="<?= set_value('address'); ?>">
                        <?= form_error('address'); ?>
                    </div>
                    <div class="crm-register-field register-zip-group">
                        <label for="zip">
                            <?php if ($requiredFields['company']['company_zip']['is_required']) { ?><span class="text-danger">*</span><?php } ?>
                            <?= _l('clients_zip'); ?>
                        </label>
                        <input type="text" class="form-control" name="zip" id="zip" value="<?= set_value('zip'); ?>">
                        <?= form_error('zip'); ?>
                    </div>
                    <div class="crm-register-field register-state-group">
                        <label for="state">
                            <?php if ($requiredFields['company']['company_state']['is_required']) { ?><span class="text-danger">*</span><?php } ?>
                            <?= _l('clients_state'); ?>
                        </label>
                        <input type="text" class="form-control" name="state" id="state" value="<?= set_value('state'); ?>">
                        <?= form_error('state'); ?>
                    </div>
                </div>
            </section>

            <section class="crm-register-step" data-step="4">
                <h3>Security & Final Review</h3>
                <p>Create your secure access credentials, confirm your information, and submit the profile so the bank can begin supporting your requests through the portal.</p>
                <div class="crm-register-grid">
                    <div class="crm-register-field register-password-group">
                        <label for="password"><span class="text-danger">*</span> <?= _l('clients_register_password'); ?></label>
                        <input type="password" class="form-control" name="password" id="password">
                        <?= form_error('password'); ?>
                    </div>
                    <div class="crm-register-field register-password-repeat-group">
                        <label for="passwordr"><span class="text-danger">*</span> <?= _l('clients_register_password_repeat'); ?></label>
                        <input type="password" class="form-control" name="passwordr" id="passwordr">
                        <?= form_error('passwordr'); ?>
                    </div>
                </div>

                <?php if (is_gdpr() && get_option('gdpr_enable_terms_and_conditions') == 1) { ?>
                    <div class="checkbox register-terms-and-conditions-wrapper">
                        <input type="checkbox" name="accept_terms_and_conditions" id="accept_terms_and_conditions" <?= set_checkbox('accept_terms_and_conditions', 'on'); ?>>
                        <label for="accept_terms_and_conditions"><?= _l('gdpr_terms_agree', terms_url()); ?></label>
                        <?= form_error('accept_terms_and_conditions'); ?>
                    </div>
                <?php } ?>

                <?php if ($honeypot) { ?>
                    <label class="honey-element" for="firstname"></label>
                    <input class="honey-element" autocomplete="off" type="text" id="firstname" name="firstname" placeholder="Your first name here">
                    <label class="honey-element" for="lastname"></label>
                    <input class="honey-element" autocomplete="off" type="text" id="lastname" name="lastname" placeholder="Your last name here">
                    <label class="honey-element" for="email"></label>
                    <input class="honey-element" autocomplete="off" type="email" id="email" name="email" placeholder="Your e-mail here">
                    <label class="honey-element" for="company"></label>
                    <input class="honey-element" autocomplete="off" type="text" id="company" name="company" placeholder="Your company here">
                <?php } ?>

                <?php if (show_recaptcha_in_customers_area()) { ?>
                    <div class="register-recaptcha">
                        <div class="g-recaptcha" data-sitekey="<?= get_option('recaptcha_site_key'); ?>"></div>
                        <?= form_error('g-recaptcha-response'); ?>
                    </div>
                <?php } ?>

                <div class="crm-register-persuasion tw-mt-5">
                    <h4>Before you submit</h4>
                    <p>Please review your information carefully. Accurate, complete details reduce the chance of errors, help protect regulatory integrity, and allow the bank to respond more efficiently once your account is active.</p>
                </div>
            </section>

            <div class="crm-register-wizard-nav">
                <button type="button" class="crm-register-btn crm-register-btn--ghost" data-register-prev>Back</button>
                <button type="button" class="crm-register-btn crm-register-btn--wizard" data-register-next>Continue</button>
            </div>

            <div class="crm-register-actions">
                <div class="crm-register-actions__left">
                    <span class="crm-register-login-note">Already have access? <a href="<?= site_url('authentication/login'); ?>">Return to login</a></span>
                </div>
                <div class="crm-register-actions__right">
                    <a href="<?= e($bookAppointmentUrl); ?>" class="crm-register-btn crm-register-btn--appointment">Book Appointment</a>
                    <button type="submit" autocomplete="off" data-loading-text="<?= _l('wait_text'); ?>" class="crm-register-btn crm-register-btn--primary">
                        <?= _l('clients_register_string'); ?>
                    </button>
                </div>
            </div>
            <?= form_close(); ?>
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

document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('.crm-register-form');
    if (!form) {
        return;
    }

    var modeButtons = Array.prototype.slice.call(document.querySelectorAll('[data-register-mode]'));
    var steps = Array.prototype.slice.call(form.querySelectorAll('.crm-register-step'));
    var prevButton = form.querySelector('[data-register-prev]');
    var nextButton = form.querySelector('[data-register-next]');
    var stepLabel = form.querySelector('[data-current-step-label]');
    var stepCount = form.querySelector('[data-current-step-count]');
    var stepFill = form.querySelector('[data-current-step-fill]');
    var currentStep = 1;
    var wizardMode = window.location.hash === '#wizard';

    function updateWizardUi() {
        var totalSteps = steps.length;
        steps.forEach(function (step) {
            var isCurrent = parseInt(step.getAttribute('data-step'), 10) === currentStep;
            step.classList.toggle('is-current', isCurrent || !wizardMode);
        });

        if (stepLabel) {
            stepLabel.textContent = <?= json_encode($wizardSteps); ?>[currentStep];
        }
        if (stepCount) {
            stepCount.textContent = 'Step ' + currentStep + ' of ' + totalSteps;
        }
        if (stepFill) {
            stepFill.style.width = ((currentStep / totalSteps) * 100) + '%';
        }
        if (prevButton) {
            prevButton.style.visibility = currentStep === 1 ? 'hidden' : 'visible';
        }
        if (nextButton) {
            nextButton.textContent = currentStep === totalSteps ? 'Review & Submit' : 'Continue';
        }
    }

    function setMode(nextMode) {
        wizardMode = nextMode === 'wizard';
        form.classList.toggle('is-wizard', wizardMode);
        modeButtons.forEach(function (button) {
            button.classList.toggle('is-active', button.getAttribute('data-register-mode') === nextMode);
        });

        if (wizardMode) {
            window.location.hash = 'wizard';
        } else if (window.location.hash === '#wizard') {
            history.replaceState(null, '', window.location.pathname + window.location.search);
        }

        updateWizardUi();
    }

    modeButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            setMode(button.getAttribute('data-register-mode'));
        });
    });

    if (prevButton) {
        prevButton.addEventListener('click', function () {
            if (currentStep > 1) {
                currentStep -= 1;
                updateWizardUi();
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }

    if (nextButton) {
        nextButton.addEventListener('click', function () {
            if (currentStep < steps.length) {
                currentStep += 1;
                updateWizardUi();
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else {
                var submitButton = form.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.focus();
                }
            }
        });
    }

    setMode(wizardMode ? 'wizard' : 'standard');
});
</script>
