<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <br>
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tab-content">
                            <div class="mailbox-heading-bar">
                                <div class="mailbox-heading-main">
                                    <h4 class="customer-profile-group-heading mailbox-heading-title">
                                        <?php if ('detail' == $group) {
                                            echo $title;
                                        } else {
                                            echo _l('mailbox_'.$group);
                                        }
                                        ?>
                                    </h4>
                                    <div class="mailbox-action-strip">
                                        <a href="<?php echo admin_url().'mailbox/compose'; ?>" class="btn btn-info">
                                            <i class="fa fa-edit"></i>
                                            Compose email
                                        </a>
                                        <a href="<?php echo admin_url('mailbox/folder/sync'); ?>" class="btn btn-default">
                                            <i class="fa fa-download"></i>
                                            Send/Receive email
                                        </a>
                                        <a href="<?php echo admin_url('mailbox/folder/config'); ?>" class="btn btn-default">
                                            <i class="fa fa-cogs"></i>
                                            Mail Settings
                                        </a>
                                    </div>
                                </div>
                                <?php if (!empty($can_switch_staff_mailbox) && !empty($mailbox_staffs)) { ?>
                                    <form method="get" action="<?php echo admin_url('mailbox/folder/'.html_escape($group === 'detail' ? 'inbox' : $group)); ?>" class="mailbox-staff-switch-form">
                                        <label for="mailbox_staff_id" class="control-label mright10">Mailbox owner</label>
                                        <select name="staff_id" id="mailbox_staff_id" class="form-control mailbox-staff-select">
                                            <?php foreach ($mailbox_staffs as $staffMember) { ?>
                                            <option value="<?php echo (int) $staffMember['staffid']; ?>" <?php echo isset($selected_staff_id) && (int) $selected_staff_id === (int) $staffMember['staffid'] ? 'selected' : ''; ?>>
                                                <?php echo html_escape(trim($staffMember['firstname'].' '.$staffMember['lastname']).' - '.$staffMember['email']); ?>
                                            </option>
                                            <?php } ?>
                                        </select>
                                        <button type="submit" class="btn btn-default">View mailbox</button>
                                    </form>
                                <?php } ?>
                            </div>
                            <?php if (isset($mailbox_status) && is_array($mailbox_status) && $group !== 'compose' && $group !== 'detail') { ?>
                            <div class="mailbox-status-card mailbox-status-<?php echo count($mailbox_status['issues']) > 0 ? 'warning' : 'info'; ?> mtop15">
                                <div class="mailbox-status-row">
                                    <div class="mailbox-status-pill"><strong>Mailbox</strong> <?php echo !empty($mailbox_status['staff']['email']) ? html_escape($mailbox_status['staff']['email']) : 'Not configured'; ?></div>
                                    <div class="mailbox-status-pill"><strong>Server</strong> <?php echo !empty($mailbox_status['resolved_imap_server']) ? html_escape($mailbox_status['resolved_imap_server']) : 'Not configured'; ?></div>
                                    <div class="mailbox-status-pill"><strong>Folder</strong> <?php echo $mailbox_status['folder_scan'] !== '' ? html_escape($mailbox_status['folder_scan']) : 'Not configured'; ?></div>
                                    <div class="mailbox-status-pill"><strong>Port</strong> <?php echo !empty($mailbox_status['imap_port']) ? html_escape($mailbox_status['imap_port']) : 'Not configured'; ?></div>
                                    <div class="mailbox-status-pill"><strong>Encryption</strong> <?php echo $mailbox_status['encryption'] !== '' ? strtoupper(html_escape($mailbox_status['encryption'])) : 'None'; ?></div>
                                    <div class="mailbox-status-pill"><strong>Password</strong> <?php echo !empty($mailbox_status['has_password']) ? 'Saved' : 'Missing'; ?></div>
                                    <div class="mailbox-status-pill"><strong>Last sync</strong> <?php echo !empty($mailbox_status['last_email_check_at']) ? _dt(date('Y-m-d H:i:s', (int) $mailbox_status['last_email_check_at'])) : 'Never'; ?></div>
                                </div>
                                <?php if (count($mailbox_status['issues']) > 0) { ?>
                                <ul class="mtop10 mbot0">
                                    <?php foreach ($mailbox_status['issues'] as $issue) { ?>
                                    <li><?php echo html_escape($issue); ?></li>
                                    <?php } ?>
                                </ul>
                                <?php } ?>
                            </div>
                            <?php } ?>
                            <?php if ('compose' != $group && 'config' != $group && 'sync' != $group) {?>
                            <div class="horizontal-scrollable-tabs preview-tabs-top">
                                <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
                                <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
                                <div class="horizontal-tabs">
                                    <ul class="nav nav-tabs nav-tabs-horizontal mbot15" role="tablist">
                                        <?php if ('inbox' == $group || 'starred' == $group || 'important' == $group || ('detail' == $group && isset($type) && 'outbox' != $type)) {?>
                                        <li role="presentation" data-toggle="tooltip" title="" class="tab-separator" data-original-title="<?php echo _l('mailbox_add_star'); ?>">
                                            <a href="Javascript:void(0)" aria-controls="tab_emails_tracking" role="tab" data-toggle="tab" onclick="update_mass('<?php echo $group; ?>','starred',0);window.location.reload(); return false;">
                                                <i class="fa fa-star orange" aria-hidden="true"></i>
                                            </a>
                                        </li>
                                        <li role="presentation" data-toggle="tooltip" title="" class="tab-separator" data-original-title="<?php echo _l('mailbox_remove_star'); ?>">
                                            <a href="Javascript:void(0)" aria-controls="tab_emails_tracking" role="tab" data-toggle="tab" onclick="update_mass('<?php echo $group; ?>','starred',1);window.location.reload(); return false;">
                                                <i class="fa fa-star" aria-hidden="true"></i>
                                            </a>
                                        </li>
                                        <li role="presentation" data-toggle="tooltip" title="" class="tab-separator" data-original-title="<?php echo _l('mailbox_mark_as_important'); ?>">
                                            <a href="Javascript:void(0)" aria-controls="tab_emails_tracking" role="tab" data-toggle="tab" onclick="update_mass('<?php echo $group; ?>','important',0);window.location.reload(); return false;">
                                                <i class="fa fa-bookmark green" aria-hidden="true"></i>
                                            </a>
                                        </li>
                                        <li role="presentation" data-toggle="tooltip" title="" class="tab-separator" data-original-title="<?php echo _l('mailbox_mark_as_not_important'); ?>">
                                            <a href="Javascript:void(0)" aria-controls="tab_emails_tracking" role="tab" data-toggle="tab" onclick="update_mass('<?php echo $group; ?>','important',1);window.location.reload(); return false;">
                                                <i class="fa fa-bookmark" aria-hidden="true"></i>
                                            </a>
                                        </li>                                        
                                        <li role="presentation" data-toggle="tooltip" title="" class="tab-separator" data-original-title="<?php echo _l('mailbox_mark_as_unread'); ?>">
                                            <a href="Javascript:void(0)" aria-controls="tab_emails_tracking" role="tab" data-toggle="tab" onclick="update_mass('<?php echo $group; ?>','read',1);window.location.reload(); return false;">
                                                <i class="fa fa-envelope orange" aria-hidden="true"></i>
                                            </a>
                                        </li>
                                        <li role="presentation" data-toggle="tooltip" title="" class="tab-separator" data-original-title="<?php echo _l('mailbox_mark_as_read'); ?>">
                                            <a href="Javascript:void(0)" aria-controls="tab_emails_tracking" role="tab" data-toggle="tab" onclick="update_mass('<?php echo $group; ?>','read',0);window.location.reload(); return false;">
                                                <i class="fa fa-envelope" aria-hidden="true"></i>
                                            </a>
                                        </li>
                                        <?php } ?>
                                        <li role="presentation" data-toggle="tooltip" title="" class="tab-separator" data-original-title="<?php echo _l('mailbox_delete'); ?>">
                                            <a href="Javascript:void(0)" aria-controls="tab_emails_tracking" role="tab" data-toggle="tab" onclick="update_mass('<?php echo $group; ?>','trash',1,'inbox');window.location.reload(); return false;">
                                                <i class="fa fa-trash red" aria-hidden="true"></i>
                                            </a>
                                        </li>
                                        <?php if ('detail' == $group) {?>
                                        <li role="presentation" data-toggle="tooltip" title="" class="tab-separator" data-original-title="<?php echo _l('mailbox_reply'); ?>">
                                            <a href="<?php echo admin_url().'mailbox/reply/'.$inbox->id.'/reply/'.$type; ?>">
                                                <i class="fa fa-mail-reply" aria-hidden="true"></i>
                                            </a>
                                        </li>
                                        <li role="presentation" data-toggle="tooltip" title="" class="tab-separator" data-original-title="<?php echo _l('mailbox_reply_all'); ?>">
                                            <a href="<?php echo admin_url().'mailbox/reply/'.$inbox->id.'/replyall/'.$type; ?>">
                                                <i class="fa fa-mail-reply-all" aria-hidden="true"></i>
                                            </a>
                                        </li>
                                        <li role="presentation" data-toggle="tooltip" title="" class="tab-separator" data-original-title="<?php echo _l('mailbox_forward'); ?>">
                                            <a href="<?php echo admin_url().'mailbox/reply/'.$inbox->id.'/forward/'.$type; ?>">
                                                <i class="fa fa-mail-forward" aria-hidden="true"></i>
                                            </a>
                                        </li>
                                        <?php }?>
                                    </ul>                    
                                </div>                                
                            </div>    
                            <?php }?>                                                    
                            <div class="tab-content">
                                <?php if ('compose' == $group && !isset($type)) {
                                    $this->load->view('mailbox/mailbox_compose');
                                } elseif ('compose' == $group && 'reply' == $type) {
                                    $this->load->view('mailbox/mailbox_reply');
                                } elseif ('detail' == $group && 'inbox' == $type) {
                                    $this->load->view('mailbox/mailbox_detail');
                                } elseif ('detail' == $group && 'outbox' == $type) {
                                    $this->load->view('mailbox/mailbox_detail_outbox');
                                } elseif ('sync' == $group) {
                                    $this->load->view('mailbox/mailbox_sync');
                                } elseif ('config' == $group) {
                                    $this->load->view('mailbox/mailbox_config');
                                } else {?>
                                    <?php $this->load->view('mailbox/partials/direct_list'); ?>
                                <?php } ?>
                                
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script type="text/javascript">
	"use strict";

    $(function(){
        init_btn_with_tooltips();   
        init_tabs_scrollable();   
    });
</script>
</body>
</html>
