<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if (isset($client) && (is_admin() || staff_can('edit', 'customers') || staff_can('delete', 'customers'))) { ?>
<div class="panel_s tw-mt-4">
    <div class="panel-body">
        <h4 class="tw-mt-0 tw-mb-3 tw-font-semibold tw-text-neutral-800">
            <i class="fa-regular fa-id-card tw-mr-1"></i>
            Admin Account Controls
        </h4>
        <p class="text-muted tw-text-sm tw-mb-3">
            Manage this customer account, portal profile, contacts, and customer access from one place.
        </p>

        <div class="uscap-customer-action-grid">
            <?php if (is_admin()) { ?>
            <div class="uscap-customer-action">
                <a href="<?= admin_url('clients/login_as_client/' . $client->userid); ?>"
                    target="_blank"
                    class="btn btn-default btn-block">
                    <i class="fa-regular fa-user tw-mr-1"></i>
                    <?= _l('login_as_client'); ?>
                </a>
            </div>
            <?php } ?>

            <div class="uscap-customer-action">
                <a href="<?= admin_url('clients/client/' . $client->userid . '?group=contacts'); ?>"
                    class="btn btn-default btn-block">
                    <i class="fa-regular fa-address-book tw-mr-1"></i>
                    Manage Contact Profiles
                </a>
            </div>

            <?php if (staff_can('edit', 'customers') || is_admin()) { ?>
                <?php if ((int) $client->active === 1) { ?>
                <div class="uscap-customer-action">
                    <a href="<?= admin_url('clients/mark_as_inactive/' . $client->userid); ?>"
                        class="btn btn-warning btn-block _delete">
                        <i class="fa fa-ban tw-mr-1"></i>
                        Deactivate Customer Account
                    </a>
                </div>
                <?php } else { ?>
                <div class="uscap-customer-action">
                    <a href="<?= admin_url('clients/mark_as_active/' . $client->userid); ?>"
                        class="btn btn-success btn-block">
                        <i class="fa fa-check tw-mr-1"></i>
                        Reactivate Customer Account
                    </a>
                </div>
                <?php } ?>
            <?php } ?>

            <?php if (staff_can('delete', 'customers')) { ?>
            <div class="uscap-customer-action">
                <a href="<?= admin_url('clients/delete/' . $client->userid); ?>"
                    class="btn btn-danger btn-block _delete">
                    <i class="fa-regular fa-trash-can tw-mr-1"></i>
                    Permanently Delete Customer
                </a>
            </div>
            <?php } ?>
        </div>

        <?php if (staff_can('delete', 'customers')) { ?>
        <p class="text-danger tw-text-xs tw-mt-3 tw-mb-0">
            Permanent deletion uses the CRM delete routine and may remove related contacts, tickets, projects, files, and notes. Customers with referenced invoices, estimates, or credit notes may be blocked by Perfex safeguards.
        </p>
        <?php } ?>
    </div>
</div>
<?php } ?>
