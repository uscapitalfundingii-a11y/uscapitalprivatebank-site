<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<ul class="nav navbar-pills navbar-pills-flat nav-tabs nav-stacked customer-tabs" role="tablist">
    <li class="<?php if ('inbox' == $group) { echo 'active '; } ?>mail_tab_<?php echo $group; ?>">
        <a data-group="inbox" href="<?php echo admin_url('mailbox/folder/inbox'); ?>">
            <i class="fa fa-inbox menu-icon" aria-hidden="true"></i>
            <?php echo _l('mailbox_inbox'); ?>
            <?php
            $mailbox_staff_id = function_exists('mailbox_get_selected_staff_id') ? mailbox_get_selected_staff_id() : get_staff_user_id();
            $num_unread = total_rows(db_prefix().'mail_inbox', ['read' => '0', 'to_staff_id' => $mailbox_staff_id, 'trash' => '0']);
            if ($num_unread > 0) { ?>
            <span class="badge menu-badge bg-warning"><?php echo $num_unread; ?></span>
            <?php } ?>
        </a>
    </li>
    <li class="<?php if ('starred' == $group) { echo 'active '; } ?>mail_tab_<?php echo $group; ?>">
        <a data-group="starred" href="<?php echo admin_url('mailbox/folder/starred'); ?>">
            <i class="fa fa-star menu-icon orange" aria-hidden="true"></i>
            <?php echo _l('mailbox_starred'); ?>
        </a>
    </li>
    <li class="<?php if ('sent' == $group) { echo 'active '; } ?>mail_tab_<?php echo $group; ?>">
        <a data-group="sent" href="<?php echo admin_url('mailbox/folder/sent'); ?>">
            <i class="fa fa-envelope menu-icon" aria-hidden="true"></i>
            <?php echo _l('mailbox_sent'); ?>
        </a>
    </li>
    <li class="<?php if ('important' == $group) { echo 'active '; } ?>mail_tab_<?php echo $group; ?>">
        <a data-group="important" href="<?php echo admin_url('mailbox/folder/important'); ?>">
            <i class="fa fa-bookmark menu-icon red" aria-hidden="true"></i>
            <?php echo _l('mailbox_important'); ?>
        </a>
    </li>
    <li class="<?php if ('draft' == $group) { echo 'active '; } ?>mail_tab_<?php echo $group; ?>">
        <a data-group="draft" href="<?php echo admin_url('mailbox/folder/draft'); ?>">
            <i class="fa fa-file menu-icon" aria-hidden="true"></i>
            <?php echo _l('mailbox_draft'); ?>
        </a>
    </li>
    <li class="<?php if ('trash' == $group) { echo 'active '; } ?>mail_tab_<?php echo $group; ?>">
        <a data-group="trash" href="<?php echo admin_url('mailbox/folder/trash'); ?>">
            <i class="fa fa-trash menu-icon" aria-hidden="true"></i>
            <?php echo _l('mailbox_trash'); ?>
        </a>
    </li>
    <li class="<?php if ('config' == $group) { echo 'active '; } ?>mail_tab_<?php echo $group; ?>">
        <a data-group="config" href="<?php echo admin_url('mailbox/folder/config'); ?>">
            <i class="fa fa-cogs menu-icon" aria-hidden="true"></i>
            Mail Settings
        </a>
    </li>
    <li class="<?php if ('sync' == $group) { echo 'active '; } ?>mail_tab_<?php echo $group; ?>">
        <a data-group="sync" href="<?php echo admin_url('mailbox/folder/sync'); ?>">
            <i class="fa fa-refresh menu-icon" aria-hidden="true"></i>
            <?php echo _l('mailbox_sync_center'); ?>
        </a>
    </li>
</ul>
