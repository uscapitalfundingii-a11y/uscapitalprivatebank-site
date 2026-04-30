<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Mailbox
Description: Mailbox is a webmail client for Perfex's dashboard.
Version: 2.0.1
Requires at least: 3.0
Author: Themesic Interactive
Author URI: https://codecanyon.net/user/themesic/portfolio
*/

define('MAILBOX_MODULE_NAME', 'mailbox');
define('MAILBOX_MODULE_UPLOAD_FOLDER', module_dir_path(MAILBOX_MODULE_NAME, 'uploads'));

hooks()->add_action('after_cron_run', 'scan_email_server');
hooks()->add_action('app_admin_head', 'mailbox_add_head_components');
hooks()->add_action('app_admin_footer', 'mailbox_load_js');
hooks()->add_action('admin_init', 'mailbox_add_settings_tab');
hooks()->add_action('admin_init', 'mailbox_module_init_menu_items');
hooks()->add_filter('migration_tables_to_replace_old_links', 'mailbox_migration_tables_to_replace_old_links');

/**
 * Injects chat CSS.
 *
 * @return null
 */
function mailbox_add_head_components()
{
    if ('1' == get_option('mailbox_enabled')) {
        $CI = &get_instance();
        echo '<link href="'.base_url('modules/mailbox/assets/css/mailbox_styles.css').'?v='.$CI->app_scripts->core_version().'"  rel="stylesheet" type="text/css" />';
    }
}

/**
 * Injects chat Javascript.
 *
 * @return null
 */
function mailbox_load_js()
{
    if ('1' == get_option('mailbox_enabled')) {
        $CI = &get_instance();
        echo '<script src="'.module_dir_url('mailbox', 'assets/js/mailbox_js.js').'?v='.$CI->app_scripts->core_version().'"></script>';
    }
}

/**
 * Init mailbox module menu items in setup in admin_init hook.
 *
 * @return null
 */
function mailbox_module_init_menu_items()
{
    $CI = &get_instance();
    if ('1' == get_option('mailbox_enabled')) {
        $mailboxStaffId = mailbox_get_selected_staff_id();
        $unreadCount = total_rows(db_prefix().'mail_inbox', [
            'read'        => '0',
            'to_staff_id' => $mailboxStaffId,
            'trash'       => '0',
            'folder'      => 'inbox',
        ]);

        $CI->app_menu->add_sidebar_menu_item('mailbox', [
            'collapse' => true,
            'name'     => _l('mailbox'),
            'icon'     => 'fa fa-envelope-square',
            'position' => 6,
            'badge'    => $unreadCount > 0 ? [
                'value' => $unreadCount,
                'color' => '#f0ad4e',
            ] : [],
        ]);

        $CI->app_menu->add_sidebar_children_item('mailbox', [
            'slug'     => 'mailbox-inbox',
            'name'     => _l('mailbox_inbox'),
            'href'     => admin_url('mailbox/folder/inbox'),
            'position' => 5,
            'badge'    => $unreadCount > 0 ? [
                'value' => $unreadCount,
                'color' => '#f0ad4e',
            ] : [],
        ]);

        $CI->app_menu->add_sidebar_children_item('mailbox', [
            'slug'     => 'mailbox-starred',
            'name'     => _l('mailbox_starred'),
            'href'     => admin_url('mailbox/folder/starred'),
            'position' => 10,
            'badge'    => [],
        ]);

        $CI->app_menu->add_sidebar_children_item('mailbox', [
            'slug'     => 'mailbox-sent',
            'name'     => _l('mailbox_sent'),
            'href'     => admin_url('mailbox/folder/sent'),
            'position' => 15,
            'badge'    => [],
        ]);

        $CI->app_menu->add_sidebar_children_item('mailbox', [
            'slug'     => 'mailbox-important',
            'name'     => _l('mailbox_important'),
            'href'     => admin_url('mailbox/folder/important'),
            'position' => 20,
            'badge'    => [],
        ]);

        $CI->app_menu->add_sidebar_children_item('mailbox', [
            'slug'     => 'mailbox-draft',
            'name'     => _l('mailbox_draft'),
            'href'     => admin_url('mailbox/folder/draft'),
            'position' => 25,
            'badge'    => [],
        ]);

        $CI->app_menu->add_sidebar_children_item('mailbox', [
            'slug'     => 'mailbox-trash',
            'name'     => _l('mailbox_trash'),
            'href'     => admin_url('mailbox/folder/trash'),
            'position' => 30,
            'badge'    => [],
        ]);

        $CI->app_menu->add_sidebar_children_item('mailbox', [
            'slug'     => 'mailbox-sync',
            'name'     => 'Sync Center',
            'href'     => admin_url('mailbox/folder/sync'),
            'position' => 35,
            'badge'    => [],
        ]);

        $CI->app_menu->add_sidebar_children_item('mailbox', [
            'slug'     => 'mailbox-settings',
            'name'     => 'Mail Settings',
            'href'     => admin_url('mailbox/folder/config'),
            'position' => 40,
            'badge'    => [],
        ]);
    }
}

/**
 * Determine if the current user can switch between staff mailboxes.
 *
 * @return bool
 */
function mailbox_can_switch_staff_mailbox()
{
    return function_exists('is_admin') && is_admin();
}

/**
 * Get the active mailbox staff id for the current session.
 *
 * @return int
 */
function mailbox_get_selected_staff_id()
{
    $CI = &get_instance();
    $currentStaffId = (int) get_staff_user_id();

    if (!mailbox_can_switch_staff_mailbox()) {
        return $currentStaffId;
    }

    $selectedStaffId = (int) $CI->session->userdata('mailbox_selected_staff_id');
    if ($selectedStaffId <= 0) {
        return $currentStaffId;
    }

    $exists = $CI->db->where('staffid', $selectedStaffId)->count_all_results(db_prefix().'staff');
    if (!$exists) {
        return $currentStaffId;
    }

    return $selectedStaffId;
}

/**
 * Persist the active mailbox staff id for admin mailbox switching.
 *
 * @param int $staffId
 *
 * @return void
 */
function mailbox_set_selected_staff_id($staffId)
{
    if (!mailbox_can_switch_staff_mailbox()) {
        return;
    }

    $CI = &get_instance();
    $staffId = (int) $staffId;

    if ($staffId <= 0) {
        $CI->session->unset_userdata('mailbox_selected_staff_id');
        return;
    }

    $exists = $CI->db->where('staffid', $staffId)->count_all_results(db_prefix().'staff');
    if ($exists) {
        $CI->session->set_userdata('mailbox_selected_staff_id', $staffId);
    }
}

/**
 * Normalize mailbox date strings.
 *
 * @param string $dateString
 *
 * @return string
 */
function mailbox_imap_datetime($dateString)
{
    $timestamp = strtotime((string) $dateString);

    if ($timestamp === false || $timestamp <= 0) {
        return date('Y-m-d H:i:s');
    }

    return date('Y-m-d H:i:s', $timestamp);
}

/**
 * Normalize IMAP folder names for safe comparisons.
 *
 * @param string $folderName
 *
 * @return string
 */
function mailbox_normalize_folder_name($folderName)
{
    $folderName = trim((string) $folderName);
    $folderName = str_replace('\\', '/', $folderName);

    return strtolower($folderName);
}

/**
 * Resolve IMAP host for a mailbox address.
 *
 * @param string $email
 * @param string $configuredHost
 *
 * @return string
 */
function mailbox_resolve_imap_host($email, $configuredHost = '')
{
    $configuredHost = trim((string) $configuredHost);
    $useDomainMailHost = (string) get_option('mailbox_imap_use_domain_mail_host') !== '0';
    $domain = '';

    if (strpos((string) $email, '@') !== false) {
        $domain = trim(substr(strrchr((string) $email, '@'), 1));
    }

    if ($configuredHost !== '') {
        return $configuredHost;
    }

    if ($useDomainMailHost && $domain !== '') {
        return 'mail.' . $domain;
    }

    if ($domain !== '') {
        return 'mail.' . $domain;
    }

    return $configuredHost;
}

/**
 * Resolve the configured folder against real server folder names.
 *
 * @param Imap   $imap
 * @param string $preferredFolder
 *
 * @return string
 */
function mailbox_resolve_folder_to_scan($imap, $preferredFolder)
{
    $preferredFolder = trim((string) $preferredFolder);
    if ($preferredFolder === '') {
        $preferredFolder = 'INBOX';
    }

    $folders = $imap->getFolders();
    if (!is_array($folders) || count($folders) === 0) {
        return $preferredFolder;
    }

    $normalizedPreferred = mailbox_normalize_folder_name($preferredFolder);

    foreach ($folders as $folder) {
        if (mailbox_normalize_folder_name($folder) === $normalizedPreferred) {
            return $folder;
        }
    }

    $commonInboxNames = ['INBOX', 'Inbox', 'inbox'];
    foreach ($commonInboxNames as $candidate) {
        foreach ($folders as $folder) {
            if (mailbox_normalize_folder_name($folder) === mailbox_normalize_folder_name($candidate)) {
                return $folder;
            }
        }
    }

    return $preferredFolder;
}

/**
 * Ensure mailbox sync tracking columns exist.
 *
 * @return void
 */
function mailbox_ensure_imap_sync_columns()
{
    $CI = &get_instance();

    if (!$CI->db->field_exists('imap_uid', 'mail_inbox')) {
        $CI->db->query('ALTER TABLE `'.db_prefix().'mail_inbox` ADD COLUMN `imap_uid` VARCHAR(120) NULL DEFAULT NULL');
    }

    if (!$CI->db->field_exists('imap_folder', 'mail_inbox')) {
        $CI->db->query('ALTER TABLE `'.db_prefix().'mail_inbox` ADD COLUMN `imap_folder` VARCHAR(191) NULL DEFAULT NULL');
    }

    if (!$CI->db->field_exists('mail_source', 'mail_inbox')) {
        $CI->db->query('ALTER TABLE `'.db_prefix().'mail_inbox` ADD COLUMN `mail_source` VARCHAR(30) NULL DEFAULT NULL');
    }
}

/**
 * Get the option name used to store signature presets for a mailbox owner.
 *
 * @param int $staffId
 *
 * @return string
 */
function mailbox_signature_presets_option_name($staffId)
{
    return 'mailbox_signature_presets_staff_' . (int) $staffId;
}

/**
 * Get raw signature presets text for a mailbox owner.
 *
 * @param int $staffId
 *
 * @return string
 */
function mailbox_get_signature_presets_raw($staffId)
{
    return (string) get_option(mailbox_signature_presets_option_name($staffId));
}

/**
 * Save raw signature presets text for a mailbox owner.
 *
 * @param int    $staffId
 * @param string $rawText
 *
 * @return void
 */
function mailbox_save_signature_presets_raw($staffId, $rawText)
{
    $optionName = mailbox_signature_presets_option_name($staffId);
    $rawText = trim((string) $rawText);

    if (get_option($optionName) === false) {
        add_option($optionName, $rawText);
    } else {
        update_option($optionName, $rawText);
    }
}

/**
 * Parse signature preset blocks for quick insertion in compose/reply.
 * Each block is separated by a line with five dashes: -----
 * First line is used as the preset label.
 *
 * @param int $staffId
 *
 * @return array<int,array<string,string>>
 */
function mailbox_get_signature_presets($staffId)
{
    $raw = mailbox_get_signature_presets_raw($staffId);
    if ($raw === '') {
        return [];
    }

    $blocks = preg_split('/\R\s*-----\s*\R/', $raw);
    if (!is_array($blocks)) {
        return [];
    }

    $presets = [];
    foreach ($blocks as $block) {
        $block = trim((string) $block);
        if ($block === '') {
            continue;
        }

        $lines = preg_split('/\R/', $block);
        $label = trim((string) ($lines[0] ?? 'Signature'));
        $content = trim($block);
        if ($content === '') {
            continue;
        }

        $presets[] = [
            'label'   => $label,
            'content' => nl2br($content),
        ];
    }

    return $presets;
}

/**
 * Resolve a logical mailbox bucket to the real server folder.
 *
 * @param Imap   $imap
 * @param string $logicalFolder
 * @param string $preferredInboxFolder
 *
 * @return string
 */
function mailbox_resolve_server_folder($imap, $logicalFolder, $preferredInboxFolder = 'Inbox')
{
    $logicalFolder = mailbox_normalize_folder_name($logicalFolder);

    if ($logicalFolder === 'inbox') {
        return mailbox_resolve_folder_to_scan($imap, $preferredInboxFolder);
    }

    $candidates = [];
    if ($logicalFolder === 'sent') {
        $candidates = ['Sent', 'Sent Items', 'INBOX.Sent', 'INBOX.Sent Items', 'Sent Messages'];
    } elseif ($logicalFolder === 'draft' || $logicalFolder === 'drafts') {
        $candidates = ['Drafts', 'Draft', 'INBOX.Drafts', 'INBOX.Draft'];
    } elseif ($logicalFolder === 'trash' || $logicalFolder === 'deleted' || $logicalFolder === 'deleted items') {
        $candidates = ['Trash', 'Deleted Items', 'INBOX.Trash', 'INBOX.Deleted Items', 'Bin'];
    }

    $folders = $imap->getFolders();
    if (!is_array($folders) || count($folders) === 0) {
        return '';
    }

    foreach ($candidates as $candidate) {
        foreach ($folders as $folder) {
            if (mailbox_normalize_folder_name($folder) === mailbox_normalize_folder_name($candidate)) {
                return $folder;
            }
        }
    }

    return '';
}

/**
 * Open an IMAP connection for one mailbox owner.
 *
 * @param array $staff
 *
 * @return Imap|null
 */
function mailbox_open_staff_imap($staff)
{
    $enabled = get_option('mailbox_enabled');
    $configuredImapServer = trim((string) get_option('mailbox_imap_server'));
    $imapPort = (int) get_option('mailbox_imap_port');
    $encryption = get_option('mailbox_encryption');
    $sharedPassword = (string) get_option('mailbox_shared_password');
    $emailPassword = !empty($staff['mail_password']) ? (string) $staff['mail_password'] : $sharedPassword;
    $staffEmail = $staff['email'];
    $imapServer = mailbox_resolve_imap_host($staffEmail, $configuredImapServer);

    if (1 != $enabled || strlen($imapServer) === 0 || $emailPassword === '') {
        return null;
    }

    if ($imapPort <= 0) {
        $imapPort = 993;
    }

    if (strpos($imapServer, ':') === false) {
        $imapServer .= ':' . $imapPort;
    }

    $imapLibraryPath = APPPATH.'third_party/php-imap/Imap.php';
    if (!file_exists($imapLibraryPath)) {
        $imapLibraryPath = module_dir_path(MAILBOX_MODULE_NAME, 'third_party/php-imap/Imap.php');
    }
    require_once $imapLibraryPath;
    include_once APPPATH.'third_party/simple_html_dom.php';

    $imap = new Imap($imapServer, $staffEmail, $emailPassword, $encryption);
    if (false === $imap->isConnected()) {
        log_activity('Failed to connect to IMAP from email: '.$staffEmail.' Error: '.$imap->getError(), null);

        return null;
    }

    return $imap;
}

/**
 * Remove a local mail row and its attachments.
 *
 * @param int $messageId
 *
 * @return void
 */
function mailbox_delete_local_mail($messageId)
{
    $CI = &get_instance();
    $messageId = (int) $messageId;

    $attachments = $CI->db
        ->where('mail_id', $messageId)
        ->where('type', 'inbox')
        ->get(db_prefix().'mail_attachment')
        ->result_array();

    foreach ($attachments as $attachment) {
        $attachmentPath = MAILBOX_MODULE_UPLOAD_FOLDER.'/inbox/'.$messageId.'/'.$attachment['file_name'];
        if (file_exists($attachmentPath)) {
            @unlink($attachmentPath);
        }
    }

    $attachmentFolder = MAILBOX_MODULE_UPLOAD_FOLDER.'/inbox/'.$messageId;
    if (is_dir($attachmentFolder)) {
        $folderFiles = @scandir($attachmentFolder);
        if (is_array($folderFiles)) {
            foreach ($folderFiles as $folderFile) {
                if ($folderFile === '.' || $folderFile === '..') {
                    continue;
                }

                $fullPath = $attachmentFolder.'/'.$folderFile;
                if (is_file($fullPath)) {
                    @unlink($fullPath);
                }
            }
        }
        @rmdir($attachmentFolder);
    }

    $CI->db->where('mail_id', $messageId)->where('type', 'inbox')->delete(db_prefix().'mail_attachment');
    $CI->db->where('id', $messageId)->delete(db_prefix().'mail_inbox');
}

/**
 * Remove local IMAP-imported emails that no longer exist on the server.
 *
 * @param int    $staffId
 * @param string $folder
 * @param array  $serverUids
 *
 * @return void
 */
function mailbox_reconcile_deleted_server_messages($staffId, $folder, array $serverUids)
{
    $CI = &get_instance();

    $CI->db->select('id');
    $CI->db->from(db_prefix().'mail_inbox');
    $CI->db->where('to_staff_id', (int) $staffId);
    $CI->db->where('mail_source', 'imap');
    $CI->db->where('imap_folder', $folder);

    if (count($serverUids) > 0) {
        $CI->db->where_not_in('imap_uid', $serverUids);
    }

    $staleMessages = $CI->db->get()->result_array();

    foreach ($staleMessages as $message) {
        mailbox_delete_local_mail((int) $message['id']);
    }
}

/**
 * Import one server folder into local mail records.
 *
 * @param Imap   $imap
 * @param array  $staff
 * @param string $logicalFolder
 * @param string $serverFolder
 * @param string $fetchMode
 * @param bool   $onlyUnseenDefault
 *
 * @return int
 */
function mailbox_import_imap_folder($imap, $staff, $logicalFolder, $serverFolder, $fetchMode = 'unread', $onlyUnseenDefault = true)
{
    $CI = &get_instance();
    $staffId = (int) $staff['staffid'];
    $imported = 0;

    if ($serverFolder === '' || !$imap->selectFolder($serverFolder)) {
        return 0;
    }

    if ('all' === $fetchMode) {
        $emails = $imap->getMessages();
    } elseif ('recent' === $fetchMode) {
        $emails = array_slice($imap->getMessages(), 0, 15);
    } elseif ($onlyUnseenDefault) {
        $emails = $imap->getUnreadMessages();
    } else {
        $emails = $imap->getMessages();
    }

    $serverUids = [];

    foreach ($emails as $email) {
        $serverUids[] = (string) $email['uid'];
        $plainTextBody = $imap->getPlainTextBody($email['uid']);
        $plainTextBody = trim($plainTextBody);
        if (!empty($plainTextBody)) {
            $email['body'] = $plainTextBody;
        }
        $email['body'] = handle_google_drive_links_in_text($email['body']);
        $email['body'] = prepare_imap_email_body_html($email['body']);

        $attachments = [];
        if (isset($email['attachments'])) {
            foreach ($email['attachments'] as $key => $at) {
                $_atName = $email['attachments'][$key]['name'];
                unset($email['attachments'][$key]['name']);
                $email['attachments'][$key]['filename'] = $_atName;
                $_attachment = $imap->getAttachment($email['uid'], $key);
                $email['attachments'][$key]['data'] = $_attachment['content'];
            }
            $attachments = $email['attachments'];
        }

        if ('true' == hooks()->apply_filters('imap_fetch_from_email_by_reply_to_header', 'true')) {
            $replyTo = $imap->getReplyToAddresses($email['uid']);

            if (1 === count($replyTo)) {
                $email['from'] = $replyTo[0];
            }
        }

        $toList = [];
        if (isset($email['to'])) {
            foreach ($email['to'] as $to) {
                $toList[] = trim(preg_replace('/(.*)<(.*)>/', '\\2', $to));
            }
        }

        $ccList = [];
        if (isset($email['cc'])) {
            foreach ($email['cc'] as $cc) {
                $ccList[] = trim(preg_replace('/(.*)<(.*)>/', '\\2', $cc));
            }
        }

        $fromEmail = trim(preg_replace('/(.*)<(.*)>/', '\\2', $email['from']));
        $senderName = preg_replace('/(.*)<(.*)>/', '\\1', $email['from']);
        $senderName = trim(str_replace('"', '', $senderName));
        if ($senderName === '' && $fromEmail !== '') {
            $senderName = $fromEmail;
        }
        $dateReceived = mailbox_imap_datetime(isset($email['date']) ? $email['date'] : '');
        $folderTrash = $logicalFolder === 'trash' ? 1 : 0;

        $CI->db->where('to_staff_id', $staffId);
        $CI->db->group_start();
        $CI->db->group_start();
        $CI->db->where('imap_uid', (string) $email['uid']);
        $CI->db->where('imap_folder', $serverFolder);
        $CI->db->group_end();
        $CI->db->or_group_start();
        $CI->db->where('from_email', $fromEmail);
        $CI->db->where('subject', isset($email['subject']) ? $email['subject'] : '');
        $CI->db->where('date_received', $dateReceived);
        $CI->db->where('folder', $logicalFolder);
        $CI->db->group_end();
        $CI->db->group_end();
        $existingMessage = $CI->db->get(db_prefix().'mail_inbox')->row();

        if ($existingMessage) {
            $CI->db->where('id', $existingMessage->id)->update(db_prefix().'mail_inbox', [
                'imap_uid'    => (string) $email['uid'],
                'imap_folder' => $serverFolder,
                'mail_source' => 'imap',
                'read'        => !empty($email['unread']) ? 0 : 1,
                'trash'       => $folderTrash,
                'folder'      => $logicalFolder,
            ]);
            continue;
        }

        $fromStaffId = get_staff_id_by_email($fromEmail);
        $inbox = [
            'from_email'    => $fromEmail,
            'from_staff_id' => $fromStaffId ? $fromStaffId : 0,
            'to'            => implode(',', $toList),
            'cc'            => implode(',', $ccList),
            'sender_name'   => $senderName,
            'subject'       => isset($email['subject']) ? $email['subject'] : '',
            'body'          => isset($email['body']) ? $email['body'] : '',
            'to_staff_id'   => $staffId,
            'date_received' => $dateReceived,
            'folder'        => $logicalFolder,
            'trash'         => $folderTrash,
            'read'          => !empty($email['unread']) ? 0 : 1,
            'imap_uid'      => (string) $email['uid'],
            'imap_folder'   => $serverFolder,
            'mail_source'   => 'imap',
        ];

        $CI->db->insert(db_prefix().'mail_inbox', $inbox);
        $inboxId = $CI->db->insert_id();
        if (!$inboxId) {
            continue;
        }

        if (count($attachments) > 0) {
            $path = MAILBOX_MODULE_UPLOAD_FOLDER.'/inbox/'.$inboxId.'/';
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
                $fp = fopen($path.'index.html', 'w');
                fclose($fp);
            }

            foreach ($attachments as $attachment) {
                $attachmentName = $attachment['filename'];
                $filenameparts = explode('.', $attachmentName);
                $extension = strtolower(end($filenameparts));
                $filename = trim(preg_replace('/[^a-zA-Z0-9-_ ]/', '', implode('', array_slice($filenameparts, 0, -1))));
                if (!$filename) {
                    $filename = 'attachment';
                }

                $attachmentName = unique_filename($path, $filename.'.'.$extension);
                $fp = fopen($path.$attachmentName, 'w');
                fwrite($fp, $attachment['data']);
                fclose($fp);

                $CI->db->insert(db_prefix().'mail_attachment', [
                    'mail_id'   => $inboxId,
                    'type'      => 'inbox',
                    'file_name' => $attachmentName,
                    'file_type' => get_mime_by_extension($attachmentName),
                ]);
            }

            $CI->db->where('id', $inboxId);
            $CI->db->update(db_prefix().'mail_inbox', [
                'has_attachment' => 1,
            ]);
        }

        $imported++;
    }

    if ('all' === $fetchMode) {
        mailbox_reconcile_deleted_server_messages($staffId, $serverFolder, array_values(array_unique($serverUids)));
    }

    return $imported;
}

/**
 * Apply a local mailbox action to the server copy.
 *
 * @param array  $staff
 * @param array  $message
 * @param string $group
 * @param string $action
 * @param int    $value
 *
 * @return bool
 */
function mailbox_apply_server_message_action($staff, $message, $group, $action, $value)
{
    if (empty($message['mail_source']) || $message['mail_source'] !== 'imap' || empty($message['imap_uid']) || empty($message['imap_folder'])) {
        return true;
    }

    $imap = mailbox_open_staff_imap($staff);
    if (!$imap) {
        return false;
    }

    $selected = $imap->selectFolder($message['imap_folder']);
    if (!$selected) {
        $imap->close();

        return false;
    }

    $ok = true;
    if ($action === 'read') {
        $ok = $imap->setUnseenMessage($message['imap_uid'], (int) $value === 1);
    } elseif ($action === 'trash') {
        if ($group === 'trash' || (int) $message['trash'] === 1 || mailbox_normalize_folder_name($message['folder']) === 'trash') {
            if (method_exists($imap, 'permanentlyDeleteMessage')) {
                $ok = $imap->permanentlyDeleteMessage($message['imap_uid']);
            } else {
                $ok = $imap->deleteMessage($message['imap_uid']);
            }
        } else {
            $ok = $imap->deleteMessage($message['imap_uid']);
        }
    }

    $imap->close();

    return $ok !== false;
}

/**
 * Init mailbox module setting menu items in setup in admin_init hook.
 *
 * @return null
 */
function mailbox_add_settings_tab()
{
    $CI = &get_instance();
    $CI->app_tabs->add_settings_tab('mailbox-settings', [
       'name'     => ''._l('mailbox_setting').'',
       'view'     => 'mailbox/mailbox_settings',
       'position' => 36,
   ]);
}

/**
 * mailbox migration tables to replace old links description.
 *
 * @param array $tables
 *
 * @return array
 */
function mailbox_migration_tables_to_replace_old_links($tables)
{
    $tables[] = [
                'table' => db_prefix().'mail_inbox',
                'field' => 'description',
            ];

    return $tables;
}

/**
 * Scan mailbox from mail-server.
 *
 * @param array  $staff
 * @param string $fetchMode
 *
 * @return int
 */
function mailbox_scan_staff_email($staff, $fetchMode = 'unread')
{
    $enabled = get_option('mailbox_enabled');
    $unseenEmail = get_option('mailbox_only_loop_on_unseen_emails');

    if (1 != $enabled) {
        return 0;
    }

    $CI = &get_instance();
    mailbox_ensure_imap_sync_columns();

    $staffId = (int) $staff['staffid'];
    $folderScan = trim((string) get_option('mailbox_folder_scan'));
    $imported = 0;

    $imap = mailbox_open_staff_imap($staff);
    if (!$imap) {
        return 0;
    }

    $CI->db->where('staffid', $staffId);
    $CI->db->update(db_prefix().'staff', [
        'last_email_check' => time(),
    ]);

    $resolvedInbox = mailbox_resolve_server_folder($imap, 'inbox', $folderScan !== '' ? $folderScan : 'Inbox');
    if ($resolvedInbox !== '') {
        $imported += mailbox_import_imap_folder($imap, $staff, 'inbox', $resolvedInbox, $fetchMode, (int) $unseenEmail === 1);
    }

    if ($fetchMode === 'all' || $fetchMode === 'recent') {
        $resolvedSent = mailbox_resolve_server_folder($imap, 'sent', $folderScan);
        if ($resolvedSent !== '') {
            $imported += mailbox_import_imap_folder($imap, $staff, 'sent', $resolvedSent, 'all', false);
        }

        $resolvedDraft = mailbox_resolve_server_folder($imap, 'draft', $folderScan);
        if ($resolvedDraft !== '') {
            $imported += mailbox_import_imap_folder($imap, $staff, 'draft', $resolvedDraft, 'all', false);
        }

        $resolvedTrash = mailbox_resolve_server_folder($imap, 'trash', $folderScan);
        if ($resolvedTrash !== '') {
            $imported += mailbox_import_imap_folder($imap, $staff, 'trash', $resolvedTrash, 'all', false);
        }
    }

    $imap->close();

    return $imported;
}

/**
 * Scan mailbox from mail-server.
 *
 * @param int|null $staffId
 * @param string   $fetchMode
 *
 * @return int
 */
function mailbox_scan_email_server($staffId = null, $fetchMode = 'unread')
{
    $enabled     = get_option('mailbox_enabled');
    $check_every = (int) get_option('mailbox_check_every');

    if (1 != $enabled) {
        return 0;
    }

    $CI = &get_instance();
    $sharedPassword = trim((string) get_option('mailbox_shared_password'));
    $CI->db->select()
        ->from(db_prefix().'staff');

    if ($sharedPassword === '') {
        $CI->db->where(db_prefix().'staff.mail_password !=', '');
    }

    if (!empty($staffId)) {
        $CI->db->where(db_prefix().'staff.staffid', (int) $staffId);
    }

    $staffs   = $CI->db->get()->result_array();
    $imported = 0;

    foreach ($staffs as $staff) {
        $last_run = isset($staff['last_email_check']) ? (int) $staff['last_email_check'] : 0;
        if (!empty($staffId) || 'all' === $fetchMode || 'recent' === $fetchMode || empty($last_run) || (time() > $last_run + ($check_every * 60))) {
            $imported += mailbox_scan_staff_email($staff, $fetchMode);
        }
    }

    return $imported;
}

/**
 * Cron hook wrapper.
 *
 * @return bool
 */
function scan_email_server()
{
    mailbox_scan_email_server();

    return true;
}

/**
 * Load the module helper.
 */
$CI = &get_instance();
$CI->load->helper(MAILBOX_MODULE_NAME.'/mailbox');

/*
 * Register the activation mailbox
 */
register_activation_hook(MAILBOX_MODULE_NAME, 'mailbox_activation_hook');

/**
 * The activation function.
 */
function mailbox_activation_hook()
{
    $CI = &get_instance();
    require_once __DIR__.'/install.php';
}

/*
 * Register mailbox language files
 */
register_language_files(MAILBOX_MODULE_NAME, [MAILBOX_MODULE_NAME]);


hooks()->add_action('app_init',MAILBOX_MODULE_NAME.'_actLib');
function mailbox_actLib()
{
    $CI = & get_instance();
    $CI->load->library(MAILBOX_MODULE_NAME.'/Env2api');
    $envato_res = $CI->env2api->validatePurchase(MAILBOX_MODULE_NAME);
    if (!$envato_res) {
        set_alert('danger', "One of your modules failed its verification and got deactivated. Please reactivate or contact support.");
        redirect(admin_url('modules'));
    }
}

hooks()->add_action('pre_activate_module', MAILBOX_MODULE_NAME.'_sidecheck');
function mailbox_sidecheck($module_name)
{
    if ($module_name['system_name'] == MAILBOX_MODULE_NAME) {
        modules\mailbox\core\Apiinit::activate($module_name);
    }
}

hooks()->add_action('pre_deactivate_module', MAILBOX_MODULE_NAME.'_deregister');
function mailbox_deregister($module_name)
{
    if ($module_name['system_name'] == MAILBOX_MODULE_NAME) {
        delete_option(MAILBOX_MODULE_NAME."_verification_id");
        delete_option(MAILBOX_MODULE_NAME."_last_verification");
        delete_option(MAILBOX_MODULE_NAME . "_product_token");
        delete_option(MAILBOX_MODULE_NAME."_heartbeat");
    }
}
