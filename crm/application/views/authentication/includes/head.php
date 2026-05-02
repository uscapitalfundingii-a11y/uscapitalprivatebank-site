<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="en" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1, maximum-scale=1">
    <title>
        <?php echo e(get_option('companyname')); ?> - <?php echo _l('admin_auth_login_heading'); ?>
    </title>
    <?php echo app_compile_css('admin-auth'); ?>
    <style>
    body,
    html {
        font-size: 16px;
    }

    body>* {
        font-size: 14px;
    }

    body {
        font-family: "Inter", sans-serif;
        color: #475569;
        margin: 0;
        padding: 0;
    }

    .company-logo {
        padding: 25px 10px;
        display: block;
    }

    .company-logo img {
        margin: 0 auto;
        display: block;
    }

    body.login_admin.uscpb-admin-auth {
        min-height: 100vh;
        color: #1e293b;
        background:
            radial-gradient(circle at top left, rgba(199, 161, 87, 0.18), transparent 34rem),
            linear-gradient(135deg, #061225 0%, #0f233f 46%, #f5f7fb 46%, #f5f7fb 100%);
    }

    .uscpb-admin-auth__shell {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(420px, 520px);
        min-height: 100vh;
    }

    .uscpb-admin-auth__brand,
    .uscpb-admin-auth__panel {
        display: flex;
        align-items: center;
    }

    .uscpb-admin-auth__brand {
        position: relative;
        overflow: hidden;
        padding: 64px;
        color: #fff;
    }

    .uscpb-admin-auth__brand::before {
        content: "";
        position: absolute;
        inset: 0;
        background:
            linear-gradient(135deg, rgba(255, 255, 255, 0.12) 0 1px, transparent 1px 100%),
            linear-gradient(45deg, rgba(255, 255, 255, 0.08) 0 1px, transparent 1px 100%);
        background-size: 72px 72px;
        opacity: 0.32;
    }

    .uscpb-admin-auth__brand-inner {
        position: relative;
        z-index: 1;
        max-width: 620px;
    }

    .uscpb-admin-auth__eyebrow,
    .uscpb-admin-auth__kicker {
        margin: 0 0 14px;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .uscpb-admin-auth__eyebrow {
        color: #d8b86f;
    }

    .uscpb-admin-auth__brand h1 {
        margin: 0;
        max-width: 560px;
        color: #fff;
        font-size: 48px;
        font-weight: 800;
        line-height: 1.05;
    }

    .uscpb-admin-auth__brand p {
        max-width: 540px;
        margin: 24px 0 0;
        color: #cbd5e1;
        font-size: 17px;
        line-height: 1.7;
    }

    .uscpb-admin-auth__status {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        margin-top: 34px;
        padding: 11px 15px;
        border: 1px solid rgba(216, 184, 111, 0.38);
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.42);
        color: #e2e8f0;
        font-size: 13px;
        font-weight: 700;
    }

    .uscpb-admin-auth__status-dot {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: #d8b86f;
        box-shadow: 0 0 0 5px rgba(216, 184, 111, 0.14);
    }

    .uscpb-admin-auth__panel {
        justify-content: center;
        padding: 48px 32px;
    }

    .uscpb-admin-auth__card {
        width: 100%;
        max-width: 430px;
        padding: 34px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.16);
    }

    .uscpb-admin-auth__logo.company-logo {
        padding: 0 0 26px;
    }

    .uscpb-admin-auth__logo img {
        max-width: 210px;
        max-height: 72px;
        object-fit: contain;
    }

    .uscpb-admin-auth__header {
        margin-bottom: 28px;
    }

    .uscpb-admin-auth__kicker {
        color: #b18943;
    }

    .uscpb-admin-auth__header h2 {
        margin: 0 0 8px;
        color: #0f172a;
        font-size: 25px;
        font-weight: 800;
        line-height: 1.25;
    }

    .uscpb-admin-auth__header p {
        margin: 0;
        color: #64748b;
        font-size: 14px;
        line-height: 1.6;
    }

    .uscpb-admin-auth__form .form-group {
        margin-bottom: 20px;
    }

    .uscpb-admin-auth__form .control-label {
        margin-bottom: 9px;
        color: #334155;
        font-size: 13px;
        font-weight: 800;
    }

    .uscpb-admin-auth__label-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 9px;
    }

    .uscpb-admin-auth__label-row .control-label {
        margin-bottom: 0;
    }

    .uscpb-admin-auth__label-row a {
        color: #8a6a2d;
        font-size: 13px;
        font-weight: 700;
        white-space: nowrap;
    }

    .uscpb-admin-auth__label-row a:hover,
    .uscpb-admin-auth__label-row a:focus {
        color: #5f471c;
        text-decoration: none;
    }

    .uscpb-admin-auth__form .form-control {
        height: 48px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: #fff;
        color: #0f172a;
        font-size: 15px;
        box-shadow: none;
        transition: border-color 0.16s ease, box-shadow 0.16s ease;
    }

    .uscpb-admin-auth__form .form-control:focus {
        border-color: #b18943;
        box-shadow: 0 0 0 3px rgba(177, 137, 67, 0.16);
    }

    .uscpb-admin-auth__remember {
        margin-top: 2px;
    }

    .uscpb-admin-auth__remember .checkbox {
        margin: 0;
    }

    .uscpb-admin-auth__remember label {
        color: #475569;
        font-weight: 600;
    }

    .uscpb-admin-auth__submit.btn {
        height: 48px;
        margin-top: 8px;
        border: 0;
        border-radius: 6px;
        background: #0f233f;
        color: #fff;
        font-size: 15px;
        font-weight: 800;
        box-shadow: 0 12px 24px rgba(15, 35, 63, 0.22);
        transition: background 0.16s ease, transform 0.16s ease, box-shadow 0.16s ease;
    }

    .uscpb-admin-auth__submit.btn:hover,
    .uscpb-admin-auth__submit.btn:focus {
        background: #16365f;
        color: #fff;
        box-shadow: 0 16px 30px rgba(15, 35, 63, 0.28);
        transform: translateY(-1px);
    }

    .uscpb-admin-auth .alert {
        border-radius: 6px;
    }

    .uscpb-admin-auth .g-recaptcha {
        margin-bottom: 20px;
    }

    @media screen and (max-width: 980px) {
        body.login_admin.uscpb-admin-auth {
            background: #f5f7fb;
        }

        .uscpb-admin-auth__shell {
            grid-template-columns: 1fr;
        }

        .uscpb-admin-auth__brand {
            min-height: 280px;
            padding: 42px 28px;
            background: linear-gradient(135deg, #061225 0%, #102849 100%);
        }

        .uscpb-admin-auth__brand h1 {
            font-size: 36px;
        }

        .uscpb-admin-auth__brand p {
            font-size: 15px;
        }

        .uscpb-admin-auth__panel {
            align-items: flex-start;
            padding: 28px 18px 42px;
        }
    }

    @media screen and (max-width: 520px) {
        .uscpb-admin-auth__brand {
            min-height: auto;
            padding: 34px 20px;
        }

        .uscpb-admin-auth__brand h1 {
            font-size: 30px;
        }

        .uscpb-admin-auth__card {
            padding: 26px 20px;
        }

        .uscpb-admin-auth__label-row {
            align-items: flex-start;
            flex-direction: column;
            gap: 6px;
        }

        .uscpb-admin-auth__label-row a {
            white-space: normal;
        }
    }

    @media screen and (max-height: 575px),
    screen and (min-width: 992px) and (max-width:1199px) {

        #rc-imageselect,
        .g-recaptcha {
            transform: scale(0.83);
            -webkit-transform: scale(0.83);
            transform-origin: 0 0;
            -webkit-transform-origin: 0 0;
        }
    }
    </style>
    <?php if (show_recaptcha()) { ?>
    <script src='https://www.google.com/recaptcha/api.js'></script>
    <?php } ?>
    <?php if (file_exists(FCPATH . 'assets/css/custom.css')) { ?>
    <link href="<?php echo base_url('assets/css/custom.css'); ?>" rel="stylesheet" id="custom-css">
    <?php } ?>
    <?php hooks()->do_action('app_admin_authentication_head'); ?>
</head>
