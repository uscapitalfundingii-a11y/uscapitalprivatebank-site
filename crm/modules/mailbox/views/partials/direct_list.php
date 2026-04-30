<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="alert alert-info">
    Showing the most recent synced emails on this page directly from the server cache. Use <strong>Send/Receive email</strong> again to pull the next batch.
</div>
<div class="table-responsive">
    <table class="table table-mailbox-direct">
        <thead>
            <tr>
                <th style="width:140px;">Actions</th>
                <th><?php echo $group === 'sent' ? _l('mailbox_to') : _l('mailbox_from'); ?></th>
                <th><?php echo _l('mailbox_subject'); ?></th>
                <th><?php echo _l('mailbox_date'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($mailbox_rows)) { ?>
                <?php foreach ($mailbox_rows as $rowItem) { ?>
                    <?php
                    $read = ((int) $rowItem['read'] === 1) ? '' : 'bold';
                    $primaryText = in_array($group, ['sent', 'draft'], true) ? $rowItem['to'] : $rowItem['sender_name'];
                    $deleteGroup = ($rowItem['folder'] === 'trash' || (int) $rowItem['trash'] === 1) ? 'trash' : $group;
                    ?>
                    <tr>
                        <td>
                            <a class="btn btn-default btn-icon" onclick="update_field('<?php echo $deleteGroup; ?>','starred',<?php echo (int) $rowItem['stared']; ?>,<?php echo (int) $rowItem['id']; ?>,'inbox'); return false;"><i class="fa fa-star<?php echo (int) $rowItem['stared'] === 1 ? ' orange' : ''; ?>"></i></a>
                            <a class="btn btn-default btn-icon" onclick="update_field('<?php echo $deleteGroup; ?>','important',<?php echo (int) $rowItem['important']; ?>,<?php echo (int) $rowItem['id']; ?>,'inbox'); return false;"><i class="fa fa-bookmark<?php echo (int) $rowItem['important'] === 1 ? ' green' : ''; ?>"></i></a>
                            <a class="btn btn-default btn-icon" onclick="update_field('<?php echo $deleteGroup; ?>','trash',1,<?php echo (int) $rowItem['id']; ?>,'inbox'); return false;"><i class="fa fa-trash"></i></a>
                        </td>
                        <td><a href="<?php echo admin_url().'mailbox/inbox/'.(int) $rowItem['id']; ?>"><span class="<?php echo $read; ?>"><?php echo html_escape($primaryText); ?></span></a></td>
                        <td><a href="<?php echo admin_url().'mailbox/inbox/'.(int) $rowItem['id']; ?>"><span class="<?php echo $read; ?>"><?php echo html_escape($rowItem['subject']); ?></span><?php if ((int) $rowItem['has_attachment'] > 0) { ?> <i class="fa fa-paperclip"></i><?php } ?><br><span class="text-muted"><?php echo clear_textarea_breaks(text_limiter((string) $rowItem['body'], 2, '...')); ?></span></a></td>
                        <td><a href="<?php echo admin_url().'mailbox/inbox/'.(int) $rowItem['id']; ?>"><span class="<?php echo $read; ?>"><?php echo _dt($rowItem['date_received']); ?></span></a></td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="4" class="text-center text-muted">No synced emails yet for this mailbox section. Click <strong>Send/Receive email</strong> to pull the next batch.</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
