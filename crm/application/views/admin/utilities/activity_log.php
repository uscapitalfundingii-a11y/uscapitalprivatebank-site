<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<style>
    .uscap-activity-log-page .panel_s,
    .uscap-activity-log-page .panel-table-full {
        max-width: 100%;
    }

    .uscap-activity-log-page .panel-table-full {
        overflow-x: auto;
        overflow-y: visible;
    }

    .uscap-activity-log-page .dataTables_wrapper,
    .uscap-activity-log-page .dataTables_scroll,
    .uscap-activity-log-page .dataTables_scrollBody {
        max-width: 100%;
    }

    .uscap-activity-log-page table.table-activity-log {
        table-layout: fixed;
        width: 100% !important;
    }

    .uscap-activity-log-page table.table-activity-log > thead > tr > th:first-child,
    .uscap-activity-log-page table.table-activity-log > tbody > tr > td:first-child {
        max-width: 760px;
        overflow: visible;
        overflow-wrap: anywhere;
        text-overflow: clip;
        vertical-align: top;
        white-space: normal;
        width: auto;
    }

    .uscap-activity-log-page table.table-activity-log > thead > tr > th:nth-child(2),
    .uscap-activity-log-page table.table-activity-log > tbody > tr > td:nth-child(2) {
        white-space: nowrap;
        width: 145px !important;
    }

    .uscap-activity-log-page table.table-activity-log > thead > tr > th:nth-child(3),
    .uscap-activity-log-page table.table-activity-log > tbody > tr > td:nth-child(3) {
        white-space: nowrap;
        width: 190px !important;
    }
</style>
<div id="wrapper">
    <div class="content uscap-activity-log-page">
        <div class="row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-4">
                        <?php echo render_date_input('activity_log_date', 'utility_activity_log_filter_by_date', '', [], [], '', 'activity-log-date'); ?>
                    </div>
                    <div class="col-md-8 text-right mtop20">
                        <a class="btn btn-danger _delete"
                            href="<?php echo admin_url('utilities/clear_activity_log'); ?>"><?php echo _l('clear_activity_log'); ?></a>
                    </div>
                </div>
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="panel-table-full">
                            <?php render_datatable([
                            _l('utility_activity_log_dt_description'),
                            _l('utility_activity_log_dt_date'),
                            _l('utility_activity_log_dt_staff'),
                            ], 'activity-log'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>

</html>
