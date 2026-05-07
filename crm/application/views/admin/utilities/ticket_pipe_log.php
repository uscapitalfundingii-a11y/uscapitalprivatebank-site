<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<style>
    .uscap-ticket-pipe-log-page .panel_s,
    .uscap-ticket-pipe-log-page .panel-table-full {
        max-width: 100%;
    }

    .uscap-ticket-pipe-log-page .panel-table-full {
        overflow-x: auto;
        overflow-y: visible;
    }

    .uscap-ticket-pipe-log-page .dataTables_wrapper,
    .uscap-ticket-pipe-log-page .dataTables_scroll,
    .uscap-ticket-pipe-log-page .dataTables_scrollBody {
        max-width: 100%;
    }

    .uscap-ticket-pipe-log-page table.table-activity-log {
        table-layout: fixed;
        width: 100% !important;
    }

    .uscap-ticket-pipe-log-page table.table-activity-log > thead > tr > th,
    .uscap-ticket-pipe-log-page table.table-activity-log > tbody > tr > td {
        overflow: visible;
        overflow-wrap: anywhere;
        text-overflow: clip;
        vertical-align: top;
        white-space: normal;
    }

    .uscap-ticket-pipe-log-page table.table-activity-log > thead > tr > th:nth-child(1),
    .uscap-ticket-pipe-log-page table.table-activity-log > tbody > tr > td:nth-child(1) {
        width: 150px !important;
    }

    .uscap-ticket-pipe-log-page table.table-activity-log > thead > tr > th:nth-child(2),
    .uscap-ticket-pipe-log-page table.table-activity-log > tbody > tr > td:nth-child(2) {
        width: 120px !important;
    }

    .uscap-ticket-pipe-log-page table.table-activity-log > thead > tr > th:nth-child(3),
    .uscap-ticket-pipe-log-page table.table-activity-log > tbody > tr > td:nth-child(3),
    .uscap-ticket-pipe-log-page table.table-activity-log > thead > tr > th:nth-child(4),
    .uscap-ticket-pipe-log-page table.table-activity-log > tbody > tr > td:nth-child(4) {
        width: 190px !important;
    }

    .uscap-ticket-pipe-log-page table.table-activity-log > thead > tr > th:nth-child(5),
    .uscap-ticket-pipe-log-page table.table-activity-log > tbody > tr > td:nth-child(5) {
        width: 190px !important;
    }

    .uscap-ticket-pipe-log-page table.table-activity-log > thead > tr > th:nth-child(6),
    .uscap-ticket-pipe-log-page table.table-activity-log > tbody > tr > td:nth-child(6) {
        min-width: 320px;
    }

    .uscap-ticket-pipe-log-page table.table-activity-log > thead > tr > th:nth-child(7),
    .uscap-ticket-pipe-log-page table.table-activity-log > tbody > tr > td:nth-child(7) {
        width: 140px !important;
    }
</style>
<div id="wrapper">
    <div class="content uscap-ticket-pipe-log-page">
        <div class="row">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-4">
                        <?php echo render_date_input('activity_log_date', 'utility_activity_log_filter_by_date', '', [], [], '', 'activity-log-date'); ?>
                    </div>
                    <div class="col-md-8 text-right mtop20">
                        <a class="btn btn-danger _delete"
                            href="<?php echo admin_url('utilities/clear_pipe_log'); ?>"><?php echo _l('clear_activity_log'); ?></a>
                    </div>
                </div>
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="clearfix"></div>
                        <div class="panel-table-full">
                            <?php render_datatable([
                        _l('ticket_pipe_name'),
                        _l('ticket_pipe_date'),
                        _l('ticket_pipe_email_to'),
                        _l('ticket_pipe_email'),
                        _l('ticket_pipe_subject'),
                        _l('ticket_pipe_message'),
                        _l('ticket_pipe_status'),
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
