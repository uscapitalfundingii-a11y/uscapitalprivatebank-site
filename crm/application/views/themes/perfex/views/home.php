<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
.client-dashboard-hero {
    position: relative;
    overflow: hidden;
    border-radius: 24px;
    padding: 34px 36px;
    margin-bottom: 28px;
    color: #fff;
    background: linear-gradient(135deg, rgba(8,32,71,.96), rgba(20,92,180,.88)), url('https://uscapitalprivatebank.com/assets/img/hero-bg.jpg') center/cover no-repeat;
    box-shadow: 0 24px 50px rgba(7, 32, 70, 0.22);
}
.client-dashboard-hero:before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at top right, rgba(255,255,255,.2), transparent 35%);
}
.client-dashboard-hero > * {
    position: relative;
    z-index: 1;
}
.client-dashboard-eyebrow {
    display: inline-block;
    letter-spacing: .18em;
    text-transform: uppercase;
    font-size: 12px;
    font-weight: 700;
    color: #bcd7ff;
    margin-bottom: 14px;
}
.client-dashboard-title {
    margin: 0 0 16px;
    font-size: 44px;
    line-height: 1.08;
    font-weight: 800;
    color: #fff;
}
.client-dashboard-copy {
    max-width: 780px;
    font-size: 17px;
    line-height: 1.75;
    color: rgba(255,255,255,.88);
    margin-bottom: 22px;
}
.client-dashboard-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}
.client-dashboard-actions .btn-default {
    background: rgba(255,255,255,.1);
    color: #fff;
    border-color: rgba(255,255,255,.35);
}
.client-dashboard-actions .client-dashboard-start-btn {
    font-size: 18px;
    font-weight: 800;
    line-height: 1.15;
    min-height: 56px;
    padding: 16px 28px;
}
.client-dashboard-steps {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 14px;
    margin-top: 24px;
}
.client-dashboard-step {
    padding: 18px 18px 16px;
    border-radius: 16px;
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.14);
}
.client-dashboard-step strong {
    display: block;
    margin-bottom: 8px;
    color: #bcd7ff;
    text-transform: uppercase;
    letter-spacing: .08em;
    font-size: 12px;
}
.client-dashboard-step span {
    display: block;
    color: #fff;
    line-height: 1.6;
}
@media (max-width: 767px) {
    .client-dashboard-hero {
        padding: 26px 22px;
    }
    .client-dashboard-title {
        font-size: 34px;
    }
    .client-dashboard-steps {
        grid-template-columns: 1fr;
    }
}
</style>
<div class="row">
    <div class="col-md-12 section-client-dashboard">
        <div class="client-dashboard-hero">
            <span class="client-dashboard-eyebrow">U.S. Capital Private Bank Client Portal</span>
            <h1 class="client-dashboard-title">Secure project management for every banking request.</h1>
            <p class="client-dashboard-copy">Use this portal to open a structured project, upload documents into the correct transaction workspace, and keep your communication with the bank organized, traceable, and professionally routed.</p>
            <div class="client-dashboard-actions">
                <a href="<?= site_url('clients/new_project'); ?>" class="btn btn-primary client-dashboard-start-btn">Start Here!</a>
                <a href="<?= site_url('clients/projects'); ?>" class="btn btn-default">View Projects</a>
                <a href="<?= site_url('clients/tickets'); ?>" class="btn btn-default">Support Tickets</a>
            </div>
            <div class="client-dashboard-steps">
                <div class="client-dashboard-step">
                    <strong>Step 1</strong>
                    <span>Create a project for the exact transaction or service request you are working on.</span>
                </div>
                <div class="client-dashboard-step">
                    <strong>Step 2</strong>
                    <span>Upload files and supporting documents directly inside that project workspace.</span>
                </div>
                <div class="client-dashboard-step">
                    <strong>Step 3</strong>
                    <span>Use tickets and updates inside the project so your relationship team has the full context.</span>
                </div>
            </div>
        </div>
        <h3 id="greeting" class="tw-font-semibold tw-mt-0"></h3>
        <?php if (has_contact_permission('projects')) { ?>
        <div id="client-first-visit-help" style="display:none;">
            <?php $client_page_help_context = 'home'; ?>
            <?php get_template_part('client_directions'); ?>
        </div>
        <?php } ?>
        <?php if (has_contact_permission('projects')) { ?>
        <h3 class="projects-summary-heading tw-text-neutral-700 tw-font-medium tw-text-lg tw-mt-7">
            <?= _l('projects_summary'); ?>
        </h3>
        <?php get_template_part('projects/project_summary'); ?>
        <?php } ?>
        <?php hooks()->do_action('client_area_after_project_overview'); ?>
        <?php
            if (has_contact_permission('invoices')) { ?>
        <div class="tw-mb-3">
            <h3 class="invoices-quick-info-heading tw-text-neutral-700 tw-font-medium tw-text-lg tw-mt-7 tw-mb-0">
                <?= _l('clients_quick_invoice_info'); ?>
            </h3>
            <?php if (has_contact_permission('invoices')) { ?>
            <a href="<?= site_url('clients/statement'); ?>"
                class="tw-text-sm">
                <?= _l('view_account_statement'); ?>
            </a>
            <?php } ?>
        </div>
        <div class="panel_s">
            <div class="panel-body">
                <?php get_template_part('invoices_stats'); ?>
                <hr />
                <div class="row">
                    <div class="col-md-3">
                        <?php if (count($payments_years) > 0) { ?>
                        <div class="form-group">
                            <select
                                data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>"
                                class="form-control" id="payments_year" name="payments_years" data-width="100%"
                                onchange="total_income_bar_report();"
                                data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>">
                                <?php foreach ($payments_years as $year) { ?>
                                <option
                                    value="<?= e($year['year']); ?>"
                                    <?php if ($year['year'] == date('Y')) {
                                        echo 'selected';
                                    } ?>>
                                    <?= e($year['year']); ?>
                                </option>
                                <?php } ?>
                            </select>
                        </div>
                        <?php } ?>
                        <?php if (is_client_using_multiple_currencies()) { ?>
                        <div id="currency" class="form-group mtop15" data-toggle="tooltip"
                            title="<?= _l('clients_home_currency_select_tooltip'); ?>">
                            <select
                                data-none-selected-text="<?= _l('dropdown_non_selected_tex'); ?>"
                                class="form-control" name="currency">
                                <?php foreach ($currencies as $currency) {
                                    $selected = '';
                                    if ($currency['isdefault'] == 1) {
                                        $selected = 'selected';
                                    } ?>
                                <option
                                    value="<?= e($currency['id']); ?>"
                                    <?= e($selected); ?>>
                                    <?= e($currency['symbol']); ?>
                                    -
                                    <?= e($currency['name']); ?>
                                </option>
                                <?php
                                } ?>
                            </select>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="relative" style="max-height:400px;">
                            <canvas id="client-home-chart" height="400" class="animated fadeIn"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>
        <?php hooks()->do_action('client_area_dashboard_end'); ?>
    </div>
    <script>
        var greetDate = new Date();
        var hrsGreet = greetDate.getHours();

        var greet;
        if (hrsGreet < 12)
            greet = "<?= _l('good_morning'); ?>";
        else if (hrsGreet >= 12 && hrsGreet <= 17)
            greet = "<?= _l('good_afternoon'); ?>";
        else if (hrsGreet >= 17 && hrsGreet <= 24)
            greet = "<?= _l('good_evening'); ?>";

        if (greet) {
            document.getElementById('greeting').innerHTML =
                '<b>' + greet + ' <?= e($contact->firstname); ?>!</b>';
        }

        (function () {
            var helpKey = 'client-home-help-dismissed-<?= (int) $contact->id; ?>';
            var helpWrap = document.getElementById('client-first-visit-help');
            if (!helpWrap) {
                return;
            }

            if (!window.localStorage.getItem(helpKey)) {
                helpWrap.style.display = 'block';
            }

            var alertBox = helpWrap.querySelector('.panel_s');
            if (!alertBox) {
                return;
            }

            var closeWrap = document.createElement('div');
            closeWrap.className = 'text-right';
            closeWrap.innerHTML = '<button type="button" class="btn btn-default btn-sm">Hide tips</button>';
            closeWrap.onclick = function () {
                window.localStorage.setItem(helpKey, '1');
                helpWrap.style.display = 'none';
            };
            alertBox.querySelector('.panel-body').insertBefore(closeWrap, alertBox.querySelector('.panel-body').firstChild);
        })();
    </script>
