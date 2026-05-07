<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="tw-mt-0 tw-font-semibold">Support Routing Oversight</h4>
                        <p class="text-muted">
                            Admin-only support ticket routing view. New client tickets are routed to the best available specialist in real time; uncaptured tickets stay visible for review instead of disappearing.
                        </p>
                        <div class="alert alert-info" id="support-routing-live-status">
                            <strong>Live routing active.</strong>
                            This page scans unassigned open client-service tickets on load and every 20 seconds while the page is open.
                            <?php if (!empty($routing_result)) { ?>
                                <span class="text-muted">
                                    Last scan checked <?= e($routing_result['checked']); ?> tickets and routed <?= e($routing_result['routed']); ?>.
                                </span>
                            <?php } ?>
                            <button type="button" class="btn btn-info btn-xs pull-right" id="support-routing-run-now">
                                Route now
                            </button>
                            <div class="clearfix"></div>
                        </div>
                        <?= form_open(admin_url('support_routing'), ['method' => 'get', 'class' => 'row']); ?>
                            <div class="col-md-3">
                                <label for="source_site">Source site</label>
                                <input type="text" class="form-control" id="source_site" name="source_site" value="<?= e($filters['source_site']); ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="specialist">Assigned specialist</label>
                                <input type="text" class="form-control" id="specialist" name="specialist" value="<?= e($filters['specialist']); ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="status">Status</label>
                                <select class="form-control selectpicker" id="status" name="status" data-none-selected-text="Any">
                                    <option value=""></option>
                                    <?php foreach ($statuses as $status) { ?>
                                        <option value="<?= e($status['ticketstatusid']); ?>" <?= (string) $filters['status'] === (string) $status['ticketstatusid'] ? 'selected' : ''; ?>>
                                            <?= e($status['name']); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="search">Search</label>
                                <input type="text" class="form-control" id="search" name="search" value="<?= e($filters['search']); ?>">
                            </div>
                            <div class="col-md-1">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">Filter</button>
                            </div>
                        <?= form_close(); ?>
                    </div>
                </div>
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Ticket</th>
                                        <th>Client / Contact</th>
                                        <th>Source</th>
                                        <th>Intent / Category</th>
                                        <th>Requested Department</th>
                                        <th>Specialist</th>
                                        <th>Status</th>
                                        <th>Responses / Docs</th>
                                        <th>Assigned</th>
                                        <th>Captured</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($items)) { ?>
                                        <tr><td colspan="10" class="text-center text-muted">No support tickets found for these filters.</td></tr>
                                    <?php } ?>
                                    <?php foreach ($items as $item) {
                                        $routing = $item['routing'];
                                        $contactName = trim(($item['contact_firstname'] ?? '') . ' ' . ($item['contact_lastname'] ?? ''));
                                        ?>
                                        <tr>
                                            <td>
                                                <a href="<?= admin_url('tickets/ticket/' . $item['ticketid']); ?>">
                                                    #<?= e($item['ticketid']); ?>
                                                </a>
                                                <div class="text-muted"><?= e($item['subject']); ?></div>
                                            </td>
                                            <td>
                                                <?php if (!empty($item['userid'])) { ?>
                                                    <a href="<?= admin_url('clients/client/' . $item['userid']); ?>"><?= e($item['company']); ?></a>
                                                <?php } else { ?>
                                                    <?= e($item['company']); ?>
                                                <?php } ?>
                                                <div class="text-muted">
                                                    <?= e($contactName); ?><?= $item['contact_email'] ? ' - ' . e($item['contact_email']) : ''; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (!empty($item['has_routing_metadata'])) { ?>
                                                    <strong><?= e($routing['source_site']); ?></strong>
                                                    <div class="text-muted"><?= e($routing['source_path']); ?></div>
                                                <?php } else { ?>
                                                    <span class="label label-warning">Not captured</span>
                                                    <div class="text-muted">Manual source review</div>
                                                <?php } ?>
                                            </td>
                                            <td>
                                                <?= e($routing['intent']); ?>
                                                <div class="text-muted"><?= e($routing['category']); ?></div>
                                            </td>
                                            <td>
                                                <?= e($routing['department']); ?>
                                                <div class="text-muted">CRM: <?= e($item['department_name']); ?></div>
                                            </td>
                                            <td>
                                                <?= e($routing['assigned_specialist']); ?>
                                                <div class="text-muted"><?= e($routing['specialist_title']); ?></div>
                                                <?php if (!empty($routing['assignment_status'])) { ?>
                                                    <span class="label label-default"><?= e($routing['assignment_status']); ?></span>
                                                    <?php if (!empty($routing['route_reason'])) { ?>
                                                        <div class="text-muted">Rule: <?= e($routing['route_reason']); ?></div>
                                                    <?php } ?>
                                                <?php } elseif (empty($item['has_routing_metadata'])) { ?>
                                                    <span class="label label-warning">manual_review_no_metadata</span>
                                                <?php } ?>
                                            </td>
                                            <td><?= e($item['status_name']); ?></td>
                                            <td>
                                                Replies: <?= e($item['reply_count']); ?>
                                                <div class="text-muted">Latest: <?= e($item['latest_reply_date'] ?: 'None'); ?></div>
                                                <div class="text-muted">Chat msgs: <?= e($item['client_chat_count']); ?></div>
                                                <div class="text-muted">Attachments: <?= e($item['ticket_attachment_count']); ?> / Tasks: <?= e($item['related_task_count']); ?></div>
                                            </td>
                                            <td>
                                                <?= e(trim(($item['assigned_firstname'] ?? '') . ' ' . ($item['assigned_lastname'] ?? ''))); ?>
                                                <?php if (!empty($item['assigned'])) { ?>
                                                    <div class="text-muted">Staff ID <?= e($item['assigned']); ?></div>
                                                <?php } ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($item['has_routing_metadata'])) { ?>
                                                    <?= e($routing['captured_at'] ?: $item['note_date']); ?>
                                                <?php } else { ?>
                                                    <span class="text-muted">Not captured</span>
                                                <?php } ?>
                                                <div class="text-muted">Ticket: <?= e($item['ticket_date']); ?></div>
                                            </td>
                                        </tr>
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
<script>
(function() {
    var routeUrl = admin_url + 'support_routing/route_now';
    var statusBox = document.getElementById('support-routing-live-status');
    var button = document.getElementById('support-routing-run-now');
    var routingBusy = false;

    function csrfPayload() {
        var payload = {};
        if (typeof csrfData !== 'undefined' && csrfData.token_name) {
            payload[csrfData.token_name] = csrfData.hash;
        }
        return payload;
    }

    function setStatus(message, type) {
        if (!statusBox) {
            return;
        }
        statusBox.className = 'alert alert-' + (type || 'info');
        statusBox.innerHTML = message
            + ' <button type="button" class="btn btn-info btn-xs pull-right" id="support-routing-run-now">Route now</button><div class="clearfix"></div>';
        button = document.getElementById('support-routing-run-now');
        if (button) {
            button.addEventListener('click', runRouting);
        }
    }

    function runRouting() {
        if (routingBusy) {
            return;
        }
        routingBusy = true;
        $.post(routeUrl, csrfPayload())
            .done(function(response) {
                var result = response && response.result ? response.result : {};
                var routed = parseInt(result.routed || 0, 10);
                var checked = parseInt(result.checked || 0, 10);
                setStatus('<strong>Live routing active.</strong> Last scan checked ' + checked + ' tickets and routed ' + routed + '.', routed > 0 ? 'success' : 'info');
                if (routed > 0) {
                    window.setTimeout(function() {
                        window.location.reload();
                    }, 1200);
                }
            })
            .fail(function() {
                setStatus('<strong>Live routing warning.</strong> The automatic scan could not complete. Refresh the page or check server logs.', 'warning');
            })
            .always(function() {
                routingBusy = false;
            });
    }

    if (button) {
        button.addEventListener('click', runRouting);
    }

    window.setInterval(runRouting, 20000);
})();
</script>
