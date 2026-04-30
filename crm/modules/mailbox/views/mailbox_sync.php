<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$counts = isset($mailbox_folder_counts) && is_array($mailbox_folder_counts) ? $mailbox_folder_counts : [];
$staffEmail = !empty($mailbox_status['staff']['email']) ? $mailbox_status['staff']['email'] : '';
$lastSyncText = !empty($mailbox_status['last_email_check_at']) ? _dt(date('Y-m-d H:i:s', (int) $mailbox_status['last_email_check_at'])) : 'Never';
?>
<div class="alert alert-info">
    This page keeps mailbox syncing separate from the inbox viewer. Use the sync button below to pull the next batch of up to <strong>15</strong> messages at a time, then go back to Inbox to review them.
</div>

<div class="row">
    <div class="col-md-8">
        <div class="panel_s">
            <div class="panel-body">
                <h4 class="no-margin">Mailbox Sync Center</h4>
                <?php if ($staffEmail !== '') { ?>
                <p class="text-muted mtop10">Selected mailbox: <strong><?php echo html_escape($staffEmail); ?></strong></p>
                <?php } ?>
                <p class="text-muted">Last completed sync: <strong><?php echo html_escape($lastSyncText); ?></strong></p>
                <a href="<?php echo admin_url('mailbox/fetch_now'); ?>" class="btn btn-info">
                    <i class="fa fa-refresh"></i>
                    Run Next Sync Batch
                </a>
                <a href="<?php echo admin_url('mailbox/folder/inbox'); ?>" class="btn btn-default mleft5">
                    <i class="fa fa-inbox"></i>
                    View Inbox
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="panel_s">
            <div class="panel-body">
                <h4 class="no-margin">Current Cache</h4>
                <p class="text-muted mtop10">These counts show what is already stored locally in the CRM for this mailbox.</p>
                <div>Inbox: <strong><?php echo (int) ($counts['inbox'] ?? 0); ?></strong></div>
                <div>Unread: <strong><?php echo (int) ($counts['unread'] ?? 0); ?></strong></div>
                <div>Sent: <strong><?php echo (int) ($counts['sent'] ?? 0); ?></strong></div>
                <div>Draft: <strong><?php echo (int) ($counts['draft'] ?? 0); ?></strong></div>
                <div>Trash: <strong><?php echo (int) ($counts['trash'] ?? 0); ?></strong></div>
                <div>Important: <strong><?php echo (int) ($counts['important'] ?? 0); ?></strong></div>
                <div>Starred: <strong><?php echo (int) ($counts['starred'] ?? 0); ?></strong></div>
            </div>
        </div>
    </div>
</div>

<div class="panel_s">
    <div class="panel-body">
        <h4 class="no-margin">How It Works</h4>
        <ul class="mtop15 mbot0">
            <li>The inbox page stays lighter because it no longer tries to do receive work while rendering.</li>
            <li>Each click on <strong>Run Next Sync Batch</strong> pulls the next small group of recent messages.</li>
            <li>After each batch, return to Inbox, Sent, Draft, or Trash to see what has populated.</li>
        </ul>
    </div>
</div>
