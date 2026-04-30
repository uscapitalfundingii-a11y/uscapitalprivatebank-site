<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php echo form_open_multipart($this->uri->uri_string().'/config', ['id'=>'mailbox_config_form']); ?>
<?php
$mailboxEnabled = get_option('mailbox_enabled');
$imapServer = get_option('mailbox_imap_server');
$imapPort = get_option('mailbox_imap_port');
$mailboxUseDomainMailHost = get_option('mailbox_imap_use_domain_mail_host');
$mailboxEncryption = get_option('mailbox_encryption');
$mailboxFolderScan = get_option('mailbox_folder_scan');
$mailboxCheckEvery = get_option('mailbox_check_every');
$mailboxOnlyUnread = get_option('mailbox_only_loop_on_unseen_emails');
$mailboxSharedPassword = get_option('mailbox_shared_password');
$smtpProtocol = get_option('email_protocol');
$smtpHost = get_option('smtp_host');
$smtpPort = get_option('smtp_port');
$smtpEmail = get_option('smtp_email');
$smtpUsername = get_option('smtp_username');
$smtpPassword = get_option('smtp_password');
if (!empty($smtpPassword)) {
    $decryptedSmtpPassword = $this->encryption->decrypt($smtpPassword);
    if ($decryptedSmtpPassword !== false) {
        $smtpPassword = $decryptedSmtpPassword;
    }
}
$smtpEncryption = get_option('smtp_encryption');
?>
<div class="row">
    <div class="col-lg-12">
        <br>
        <?php echo _l('mailbox_user_pass_instructions'); ?>
        <br><br>
    </div>
    <div class="col-md-6">
        <?php $value = (isset($member) ? $member->email : ''); ?>
        <?php echo render_input('email', 'staff_add_edit_email', $value, 'email', ['autocomplete'=>'off', 'readonly'=>'readonly']); ?>
    </div>
    <div class="col-md-6">
        <label for="mail_password" class="control-label"><?php echo _l('mailbox_email_password'); ?></label>
        <div class="input-group">
        	<?php $value = (isset($member) ? $member->mail_password : ''); ?>
	        <input type="password" class="form-control password" name="mail_password" value="<?php echo $value; ?>" autocomplete="new-password">
	        <span class="input-group-addon">
	        <a href="#mail_password" class="show_password" onclick="showPassword('mail_password'); return false;"><i class="fa fa-eye"></i></a>
	        </span>
	    </div>
        <p class="text-muted mtop5">Leave this blank to keep the current mailbox-specific password. If blank and a shared mailbox password is configured below, the shared password will be used for sync.</p>
    </div>
    <div class="col-md-12">
        <label for="signature" class="control-label"><?php echo _l('mailbox_email_signature'); ?></label>
        <?php $value = (isset($member) ? $member->mail_signature : ''); ?>
        <?php echo render_textarea('mail_signature', '', $value, ['rows' => 10], [], '', 'tinymce tinymce-compose'); ?>
        <p class="text-muted mtop10">
            Use full HTML here if needed. Remote image URLs like
            <code>&lt;img src="https://example.com/signature-logo.png" alt="" style="max-height:60px;"&gt;</code>
            are supported.
        </p>
    </div>
    <div class="col-md-12">
        <label for="mail_signature_presets" class="control-label">Signature Presets Library</label>
        <?php echo render_textarea('mail_signature_presets', '', isset($signature_presets_raw) ? $signature_presets_raw : '', ['rows' => 10], [], '', ''); ?>
        <p class="text-muted mtop10">Create reusable signature blocks here. Separate each preset with a line that contains exactly <code>-----</code>. Use the first line of each block as the preset name shown in compose and reply.</p>
    </div>
</div>
<?php if (is_admin()) { ?>
<hr />
<div class="row">
    <div class="col-md-12">
        <h4 class="tw-font-semibold tw-mt-0">Mailbox Server Settings</h4>
        <p class="text-muted">Use the official mail server values that the hosting provider recommends. For DreamHost, incoming IMAP should normally be <strong>imap.dreamhost.com</strong> on port <strong>993</strong> with <strong>SSL/TLS</strong>.</p>
    </div>
    <div class="col-md-12">
        <div class="form-group">
            <label class="control-label clearfix">Enable Mailbox</label>
            <div class="radio radio-primary radio-inline">
                <input type="radio" id="mailbox_enabled_yes" name="settings[mailbox_enabled]" value="1" <?php if ('1' == (string) $mailboxEnabled) { echo 'checked'; } ?>>
                <label for="mailbox_enabled_yes"><?php echo _l('settings_yes'); ?></label>
            </div>
            <div class="radio radio-primary radio-inline">
                <input type="radio" id="mailbox_enabled_no" name="settings[mailbox_enabled]" value="0" <?php if ('1' != (string) $mailboxEnabled) { echo 'checked'; } ?>>
                <label for="mailbox_enabled_no"><?php echo _l('settings_no'); ?></label>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <?php echo render_input('settings[mailbox_imap_server]', 'leads_email_integration_imap', $imapServer ?: 'imap.dreamhost.com'); ?>
        <p class="text-muted mtop5">If this field is filled in, the CRM should use it directly. Keep it on the official server name unless you truly need a different host.</p>
    </div>
    <div class="col-md-6">
        <?php echo render_input('settings[mailbox_imap_port]', 'Port', $imapPort ?: '993', 'number', ['min' => 1]); ?>
    </div>
    <div class="col-md-12">
        <label class="control-label clearfix">Build IMAP host from each mailbox domain</label>
        <div class="radio radio-primary radio-inline">
            <input type="radio" id="mailbox_domain_host_yes" name="settings[mailbox_imap_use_domain_mail_host]" value="1" <?php if ('1' == (string) $mailboxUseDomainMailHost) { echo 'checked'; } ?>>
            <label for="mailbox_domain_host_yes"><?php echo _l('settings_yes'); ?></label>
        </div>
        <div class="radio radio-primary radio-inline">
            <input type="radio" id="mailbox_domain_host_no" name="settings[mailbox_imap_use_domain_mail_host]" value="0" <?php if ('1' != (string) $mailboxUseDomainMailHost) { echo 'checked'; } ?>>
            <label for="mailbox_domain_host_no"><?php echo _l('settings_no'); ?></label>
        </div>
        <p class="text-muted mtop5">Only enable this if you intentionally want the CRM to build hosts like <code>mail.uscapitalprivatebank.com</code> from each mailbox address. If you are using DreamHost’s official hostnames, leave this set to <strong>No</strong>.</p>
    </div>
    <div class="col-md-6">
        <?php echo render_input('settings[mailbox_folder_scan]', 'leads_email_integration_folder', $mailboxFolderScan ?: 'INBOX'); ?>
    </div>
    <div class="col-md-6">
        <?php echo render_input('settings[mailbox_check_every]', 'leads_email_integration_check_every', $mailboxCheckEvery, 'number', ['min' => 1]); ?>
    </div>
    <div class="col-md-6">
        <label for="mailbox_shared_password" class="control-label">Shared Mailbox Password</label>
        <div class="input-group">
            <input type="password" class="form-control password" id="mailbox_shared_password" name="settings[mailbox_shared_password]" value="<?php echo html_escape($mailboxSharedPassword); ?>" autocomplete="new-password">
            <span class="input-group-addon">
                <a href="#mailbox_shared_password" class="show_password" onclick="showPassword('mailbox_shared_password'); return false;"><i class="fa fa-eye"></i></a>
            </span>
        </div>
        <p class="text-muted mtop5">Use one shared mailbox password for all staff mailboxes on this server. Any mailbox-specific password above will override it.</p>
    </div>
    <div class="col-md-6">
        <label class="control-label clearfix">Import only unread emails during automatic background checks</label>
        <div class="radio radio-primary radio-inline">
            <input type="radio" id="mailbox_only_unread_yes" name="settings[mailbox_only_loop_on_unseen_emails]" value="1" <?php if ('1' == (string) $mailboxOnlyUnread) { echo 'checked'; } ?>>
            <label for="mailbox_only_unread_yes"><?php echo _l('settings_yes'); ?></label>
        </div>
        <div class="radio radio-primary radio-inline">
            <input type="radio" id="mailbox_only_unread_no" name="settings[mailbox_only_loop_on_unseen_emails]" value="0" <?php if ('1' != (string) $mailboxOnlyUnread) { echo 'checked'; } ?>>
            <label for="mailbox_only_unread_no"><?php echo _l('settings_no'); ?></label>
        </div>
        <p class="text-muted mtop5">Set this to No if you want the background sync to be allowed to read older server messages too.</p>
    </div>
    <div class="col-md-12">
        <label class="control-label clearfix"><?php echo _l('leads_email_encryption'); ?></label>
        <div class="radio radio-primary radio-inline">
            <input type="radio" id="mailbox_tls" name="settings[mailbox_encryption]" value="tls" <?php if ('tls' == $mailboxEncryption) { echo 'checked'; } ?>>
            <label for="mailbox_tls">TLS</label>
        </div>
        <div class="radio radio-primary radio-inline">
            <input type="radio" id="mailbox_ssl" name="settings[mailbox_encryption]" value="ssl" <?php if ('ssl' == $mailboxEncryption) { echo 'checked'; } ?>>
            <label for="mailbox_ssl">SSL</label>
        </div>
        <div class="radio radio-primary radio-inline">
            <input type="radio" id="mailbox_no_encryption" name="settings[mailbox_encryption]" value="" <?php if ('' == $mailboxEncryption) { echo 'checked'; } ?>>
            <label for="mailbox_no_encryption"><?php echo _l('leads_email_integration_folder_no_encryption'); ?></label>
        </div>
    </div>
</div>
<hr />
<div class="row">
    <div class="col-md-12">
        <h4 class="tw-font-semibold tw-mt-0">Outgoing Mail Settings</h4>
        <p class="text-muted">Use the official SMTP values from the host. For DreamHost, outgoing SMTP should normally be <strong>smtp.dreamhost.com</strong> on port <strong>587</strong> with <strong>STARTTLS/TLS</strong>.</p>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label class="control-label clearfix">Email Protocol</label>
            <div class="radio radio-primary radio-inline">
                <input type="radio" id="mailbox_email_protocol_smtp" name="settings[email_protocol]" value="smtp" <?php if ('smtp' == (string) $smtpProtocol) { echo 'checked'; } ?>>
                <label for="mailbox_email_protocol_smtp">SMTP</label>
            </div>
            <div class="radio radio-primary radio-inline">
                <input type="radio" id="mailbox_email_protocol_mail" name="settings[email_protocol]" value="mail" <?php if ('smtp' != (string) $smtpProtocol) { echo 'checked'; } ?>>
                <label for="mailbox_email_protocol_mail">Mail</label>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <?php echo render_input('settings[smtp_email]', 'settings_email', $smtpEmail); ?>
    </div>
    <div class="col-md-6">
        <?php echo render_input('settings[smtp_host]', 'settings_email_host', $smtpHost ?: 'smtp.dreamhost.com'); ?>
    </div>
    <div class="col-md-6">
        <?php echo render_input('settings[smtp_port]', 'settings_email_port', $smtpPort ?: '587', 'number', ['min' => 1]); ?>
    </div>
    <div class="col-md-6">
        <?php echo render_input('settings[smtp_username]', 'smtp_username', $smtpUsername); ?>
        <p class="text-muted mtop5">If your server uses the same login as the mailbox, set this to the same email address.</p>
    </div>
    <div class="col-md-6">
        <?php echo render_input('settings[smtp_password]', 'settings_email_password', $smtpPassword, 'password', ['autocomplete' => 'off']); ?>
    </div>
    <div class="col-md-12">
        <label class="control-label clearfix"><?php echo _l('smtp_encryption'); ?></label>
        <div class="radio radio-primary radio-inline">
            <input type="radio" id="mailbox_smtp_tls" name="settings[smtp_encryption]" value="tls" <?php if ('tls' == (string) $smtpEncryption) { echo 'checked'; } ?>>
            <label for="mailbox_smtp_tls">STARTTLS / TLS</label>
        </div>
        <div class="radio radio-primary radio-inline">
            <input type="radio" id="mailbox_smtp_ssl" name="settings[smtp_encryption]" value="ssl" <?php if ('ssl' == (string) $smtpEncryption) { echo 'checked'; } ?>>
            <label for="mailbox_smtp_ssl">SSL/TLS</label>
        </div>
        <div class="radio radio-primary radio-inline">
            <input type="radio" id="mailbox_smtp_none" name="settings[smtp_encryption]" value="" <?php if ((string) $smtpEncryption === '') { echo 'checked'; } ?>>
            <label for="mailbox_smtp_none"><?php echo _l('smtp_encryption_none'); ?></label>
        </div>
    </div>
</div>
<?php } ?>
<div class="row">
	<div class="col-md-12 center-block">
	<br>
		<button type="submit" autocomplete="off" data-loading-text="<?php echo _l('wait_text'); ?>" class="btn btn-info">          
          <?php echo _l('save'); ?>          
        </button>
	</div>
</div>
<?php echo form_close(); ?>
