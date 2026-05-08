<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$safeColor = function ($color, $fallback = '#1d4f8f') {
    $color = trim((string) $color);
    return preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : $fallback;
};
?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content company-context-admin">
        <div class="row">
            <div class="col-md-12">
                <div class="company-context-hero company-context-active-brand">
                    <div>
                        <p class="company-context-kicker"><?= e(_l('company_context_kicker')); ?></p>
                        <h1><?= e(_l('company_context_menu')); ?></h1>
                        <p><?= e(_l('company_context_intro')); ?></p>
                    </div>
                    <a href="<?= admin_url('support_routing'); ?>" class="btn btn-success">
                        <i class="fa fa-random"></i> <?= e(_l('company_context_support_routing')); ?>
                    </a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?= e(_l('company_context_companies')); ?></h4>
                        <p class="text-muted mtop5"><?= e(_l('company_context_companies_help')); ?></p>
                        <div class="list-group company-context-company-list">
                            <?php foreach ($companies as $company) { ?>
                                <a class="list-group-item<?= (int) $current_company_id === (int) $company['id'] ? ' active' : ''; ?>" href="<?= admin_url('company_context?company_id=' . (int) $company['id']); ?>">
                                    <span class="company-context-dot" style="background: <?= e($safeColor($company['accent_color'], '#10b981')); ?>"></span>
                                    <strong><?= e($company['name']); ?></strong>
                                    <small><?= e($company['primary_domain']); ?></small>
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?= e(_l('company_context_selected_brand')); ?></h4>
                        <?php if ($current_company) { ?>
                            <div class="row mtop15">
                                <div class="col-md-6">
                                    <p><strong><?= e(_l('company_context_public_label')); ?>:</strong><br><?= e($current_company['public_label']); ?></p>
                                    <p><strong><?= e(_l('company_context_domain')); ?>:</strong><br><?= e($current_company['primary_domain']); ?></p>
                                    <?php if (!empty($current_company['domain_aliases'])) { ?>
                                        <p><strong><?= e(_l('company_context_domain_aliases')); ?>:</strong><br><?= nl2br(e($current_company['domain_aliases'])); ?></p>
                                    <?php } ?>
                                    <p><strong><?= e(_l('company_context_support_url')); ?>:</strong><br><a href="<?= e($current_company['support_url']); ?>" target="_blank" rel="noopener noreferrer"><?= e($current_company['support_url']); ?></a></p>
                                    <p><strong><?= e(_l('company_context_support_email')); ?>:</strong><br><?= e($current_company['support_email']); ?></p>
                                    <p><strong><?= e(_l('company_context_reply_to_email')); ?>:</strong><br><?= e($current_company['reply_to_email']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <div class="company-context-swatches">
                                        <span style="background: <?= e($safeColor($current_company['primary_color'], '#1d4f8f')); ?>"></span>
                                        <span style="background: <?= e($safeColor($current_company['secondary_color'], '#2563eb')); ?>"></span>
                                        <span style="background: <?= e($safeColor($current_company['accent_color'], '#10b981')); ?>"></span>
                                    </div>
                                    <p class="text-muted"><?= e(_l('company_context_theme_help')); ?></p>
                                    <p><strong><?= e(_l('company_context_sender_domains')); ?>:</strong><br><?= nl2br(e($current_company['allowed_sender_domains'])); ?></p>
                                </div>
                            </div>
                        <?php } else { ?>
                            <p class="text-muted mtop15"><?= e(_l('company_context_all_selected_help')); ?></p>
                        <?php } ?>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?= e(_l('company_context_agent_lanes')); ?></h4>
                        <div class="table-responsive mtop15">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th><?= e(_l('company_context_company')); ?></th>
                                        <th><?= e(_l('staff_member')); ?></th>
                                        <th><?= e(_l('company_context_role')); ?></th>
                                        <th><?= e(_l('company_context_lane')); ?></th>
                                        <th><?= e(_l('company_context_permissions')); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($staff_lanes as $lane) { ?>
                                        <tr>
                                            <td><?= e($lane['company_name']); ?></td>
                                            <?php
                                            $firstName = isset($lane['firstname']) ? $lane['firstname'] : '';
                                            $lastName = isset($lane['lastname']) ? $lane['lastname'] : '';
                                            $staffName = trim($firstName . ' ' . $lastName);
                                            ?>
                                            <td><?= e($staffName !== '' ? $staffName : 'Staff #' . $lane['staffid']); ?></td>
                                            <td><?= e($lane['role_label']); ?></td>
                                            <td><?= e($lane['lane']); ?></td>
                                            <td>
                                                <span class="label label-info"><?= (int) $lane['can_view'] ? 'view' : 'no view'; ?></span>
                                                <span class="label label-success"><?= (int) $lane['can_reply'] ? 'reply' : 'draft only'; ?></span>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    <?php if (!$staff_lanes) { ?>
                                        <tr><td colspan="5" class="text-muted"><?= e(_l('company_context_no_agent_lanes')); ?></td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?= e(_l('company_context_recent_tickets')); ?></h4>
                        <div class="table-responsive mtop15">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th><?= e(_l('company_context_company')); ?></th>
                                        <th><?= e(_l('ticket_dt_subject')); ?></th>
                                        <th><?= e(_l('client')); ?></th>
                                        <th><?= e(_l('ticket_dt_status')); ?></th>
                                        <th><?= e(_l('company_context_origin')); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_tickets as $ticket) { ?>
                                        <tr>
                                            <td><?= e($ticket['company_name']); ?></td>
                                            <td><a href="<?= admin_url('tickets/ticket/' . (int) $ticket['rel_id']); ?>"><?= e($ticket['subject']); ?></a></td>
                                            <td><?= e($ticket['client_name']); ?></td>
                                            <td><?= e($ticket['status_name']); ?></td>
                                            <td><?= e($ticket['origin']); ?></td>
                                        </tr>
                                    <?php } ?>
                                    <?php if (!$recent_tickets) { ?>
                                        <tr><td colspan="5" class="text-muted"><?= e(_l('company_context_no_recent_tickets')); ?></td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
