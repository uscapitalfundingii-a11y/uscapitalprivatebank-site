<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h3 class="tw-mt-0">CRM Admin Recovery</h3>
                        <p class="text-muted">
                            The full dashboard is temporarily in recovery mode, but the admin area is available.
                            Use the links below while Aurora reviews the dashboard widget error.
                        </p>
                        <div class="btn-group mtop15" role="group">
                            <a class="btn btn-primary" href="<?php echo admin_url('clients'); ?>">Clients</a>
                            <a class="btn btn-default" href="<?php echo admin_url('tickets'); ?>">Tickets</a>
                            <a class="btn btn-default" href="<?php echo admin_url('leads'); ?>">Leads</a>
                            <a class="btn btn-default" href="<?php echo admin_url('tasks/list_tasks'); ?>">Tasks</a>
                            <a class="btn btn-default" href="<?php echo admin_url('mailbox/folder/inbox'); ?>">Mailbox</a>
                        </div>
                        <hr>
                        <p class="text-muted mbot0">Recovery reference: <?php echo e($error_reference); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>
