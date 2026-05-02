<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h3 class="tw-mt-0 tw-font-semibold">Admin Control Panel</h3>
                        <p class="text-muted">
                            This is a lighter landing page for the CRM while large imports and campaign jobs are running.
                            Use the quick links below to continue working without waiting for the heavier dashboard pages.
                        </p>
                        <div class="row tw-mt-6">
                            <div class="col-md-4 tw-mb-4">
                                <a href="<?php echo admin_url('tickets'); ?>" class="btn btn-primary btn-block">Support Tickets</a>
                            </div>
                            <div class="col-md-4 tw-mb-4">
                                <a href="<?php echo admin_url('clients'); ?>" class="btn btn-default btn-block">Customers</a>
                            </div>
                            <div class="col-md-4 tw-mb-4">
                                <a href="<?php echo admin_url('settings?group=email'); ?>" class="btn btn-default btn-block">Email Settings</a>
                            </div>
                            <div class="col-md-4 tw-mb-4">
                                <a href="<?php echo admin_url('tasks'); ?>" class="btn btn-default btn-block">Tasks</a>
                            </div>
                            <div class="col-md-4 tw-mb-4">
                                <a href="<?php echo admin_url('projects'); ?>" class="btn btn-default btn-block">Projects</a>
                            </div>
                            <div class="col-md-4 tw-mb-4">
                                <a href="<?php echo admin_url('mailbox/folder/inbox'); ?>" class="btn btn-default btn-block">Mailbox</a>
                            </div>
                        </div>
                        <hr class="tw-my-6">
                        <p class="text-muted tw-mb-0">
                            If a specific section still throws an error while imports are running, tell me which section and I will isolate that controller next.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>
