<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('authentication/includes/head.php'); ?>

<body class="login_admin uscpb-admin-auth">

    <main class="uscpb-admin-auth__shell">
        <section class="uscpb-admin-auth__brand" aria-label="US Capital Private Bank CRM">
            <div class="uscpb-admin-auth__brand-inner">
                <div class="uscpb-admin-auth__eyebrow">US Capital Private Bank</div>
                <h1>CRM Administration</h1>
                <p>Secure operational access for relationship management, client service, and internal banking workflows.</p>

                <div class="uscpb-admin-auth__status">
                    <span class="uscpb-admin-auth__status-dot" aria-hidden="true"></span>
                    Authorized staff portal
                </div>
            </div>
        </section>

        <section class="uscpb-admin-auth__panel" aria-label="<?= e(_l('admin_auth_login_heading')); ?>">
            <div class="uscpb-admin-auth__card authentication-form-wrapper">
                <div class="company-logo uscpb-admin-auth__logo text-center">
                    <?php get_dark_company_logo(); ?>
                </div>

                <div class="uscpb-admin-auth__header text-center">
                    <p class="uscpb-admin-auth__kicker">Private CRM Access</p>
                    <h2>
                        <?= _l('admin_auth_login_heading'); ?>
                    </h2>
                    <p>
                        <?= _l('welcome_back_sign_in'); ?>
                    </p>
                </div>

                <?php $this->load->view('authentication/includes/alerts'); ?>

                <?= form_open($this->uri->uri_string(), ['class' => 'uscpb-admin-auth__form']); ?>

                <?= validation_errors('<div class="alert alert-danger text-center">', '</div>'); ?>

                <?php hooks()->do_action('after_admin_login_form_start'); ?>

                <div class="form-group">
                    <label for="email" class="control-label">
                        <?= _l('admin_auth_login_email'); ?>
                    </label>
                    <input type="email" id="email" name="email" class="form-control" autocomplete="username" autofocus="1">
                </div>

                <div class="form-group">
                    <span class="uscpb-admin-auth__label-row">
                        <label for="password" class="control-label">
                            <?= _l('admin_auth_login_password'); ?>
                        </label>
                        <a href="<?= admin_url('authentication/forgot_password'); ?>">
                            <?= _l('admin_auth_login_fp'); ?>
                        </a>
                    </span>

                    <input type="password" id="password" name="password" class="form-control" autocomplete="current-password">
                </div>

                <?php if (show_recaptcha()) { ?>
                <div class="g-recaptcha"
                    data-sitekey="<?= get_option('recaptcha_site_key'); ?>">
                </div>
                <?php } ?>

                <div class="form-group uscpb-admin-auth__remember">
                    <div class="checkbox checkbox-inline">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">
                            <?= _l('admin_auth_login_remember_me'); ?></label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block uscpb-admin-auth__submit">
                    <?= _l('admin_auth_login_button'); ?>
                </button>

                <?php hooks()->do_action('before_admin_login_form_close'); ?>

                <?= form_close(); ?>
            </div>
        </section>
    </main>

</body>

</html>
