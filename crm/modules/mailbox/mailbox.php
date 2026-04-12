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
        $CI->app_menu->add_sidebar_menu_item('mailbox', [
            'name'     => _l('mailbox'),
            'href'     => admin_url('mailbox'),
            'icon'     => 'fa fa-envelope-square',
            'position' => 6,
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
 * Normalize IMAP folder names to mailbox categories.
 *
 * @param string $folder
 *
 * @return string|null
 */
function mailbox_map_imap_folder($folder)
{
    $folder = trim((string) $folder);
    $lower  = strtolower($folder);

    if ($lower === 'inbox' || strpos($lower, 'inbox') !== false) {
        return 'inbox';
    }

    if (strpos($lower, 'sent') !== false || strpos($lower, 'gesendet') !== false) {
        return 'sent';
    }

    if (strpos($lower, 'draft') !== false || strpos($lower, 'entwurf') !== false) {
        return 'draft';
    }

    if (strpos($lower, 'trash') !== false || strpos($lower, 'deleted') !== false || strpos($lower, 'papierkorb') !== false) {
        return 'trash';
    }

    return null;
}

/**
 * Convert date string from IMAP message into SQL datetime.
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
 * Ensure dynamic mailbox sync columns exist.
 *
 * @return void
 */
function mailbox_ensure_sync_columns()
{
    $CI = &get_instance();

    if (!column_exists('imap_uid', 'mail_inbox')) {
        $CI->db->query('ALTER TABLE `'.db_prefix().'mail_inbox` ADD COLUMN `imap_uid` VARCHAR(191) NULL AFTER `folder`');
    }
    if (!column_exists('imap_folder', 'mail_inbox')) {
        $CI->db->query('ALTER TABLE `'.db_prefix().'mail_inbox` ADD COLUMN `imap_folder` VARCHAR(191) NULL AFTER `imap_uid`');
    }
    if (!column_exists('imap_uid', 'mail_outbox')) {
        $CI->db->query('ALTER TABLE `'.db_prefix().'mail_outbox` ADD COLUMN `imap_uid` VARCHAR(191) NULL AFTER `reply_type`');
    }
    if (!column_exists('imap_folder', 'mail_outbox')) {
        $CI->db->query('ALTER TABLE `'.db_prefix().'mail_outbox` ADD COLUMN `imap_folder` VARCHAR(191) NULL AFTER `imap_uid`');
    }
}

/**
 * Insert synced inbox email from IMAP.
 *
 * @param array  $staff
 * @param array  $email
 * @param string $imapFolder
 * @param array  $attachments
 *
 * @return int
 */
function mailbox_store_imap_inbox_message($staff, $email, $imapFolder, $attachments)
{
    $CI = &get_instance();
    $staff_id = (int) $staff['staffid'];
    $imapUid  = isset($email['uid']) ? (string) $email['uid'] : '';

    if ($imapUid !== '') {
        $exists = $CI->db->where('to_staff_id', $staff_id)
            ->where('imap_uid', $imapUid)
            ->where('imap_folder', $imapFolder)
            ->count_all_results(db_prefix().'mail_inbox');

        if ($exists) {
            return 0;
        }
    }

    $data['to'] = [];
    if (isset($email['to'])) {
        foreach ($email['to'] as $to) {
            $data['to'][] = trim(preg_replace('/(.*)<(.*)>/', '\\2', $to));
        }
    }

    $data['cc'] = [];
    if (isset($email['cc'])) {
        foreach ($email['cc'] as $cc) {
            $data['cc'][] = trim(preg_replace('/(.*)<(.*)>/', '\\2', $cc));
        }
    }

    $from_email             = preg_replace('/(.*)<(.*)>/', '\\2', $email['from']);
    $sender_name            = preg_replace('/(.*)<(.*)>/', '\\1', $email['from']);
    $sender_name            = trim(str_replace('"', '', $sender_name));
    $inbox                  = [];
    $inbox['from_email']    = $email['from'];
    $from_staff_id          = get_staff_id_by_email(trim($from_email));
    if ($from_staff_id) {
        $inbox['from_staff_id'] = $from_staff_id;
    }
    $inbox['to']            = implode(',', $data['to']);
    $inbox['cc']            = implode(',', $data['cc']);
    $inbox['sender_name']   = $sender_name;
    $inbox['subject']       = isset($email['subject']) ? $email['subject'] : '';
    $inbox['body']          = isset($email['body']) ? $email['body'] : '';
    $inbox['to_staff_id']   = $staff_id;
    $inbox['date_received'] = mailbox_imap_datetime(isset($email['date']) ? $email['date'] : '');
    $inbox['folder']        = 'trash' === mailbox_map_imap_folder($imapFolder) ? 'trash' : 'inbox';
    $inbox['trash']         = 'trash' === mailbox_map_imap_folder($imapFolder) ? 1 : 0;
    $inbox['read']          = !empty($email['unread']) ? 0 : 1;
    $inbox['imap_uid']      = $imapUid;
    $inbox['imap_folder']   = $imapFolder;

    $CI->db->insert(db_prefix().'mail_inbox', $inbox);
    $inbox_id = $CI->db->insert_id();

    if ($inbox_id && count($attachments) > 0) {
        mailbox_store_synced_attachments($attachments, $inbox_id, 'inbox');
    }

    return $inbox_id ? 1 : 0;
}

/**
 * Insert synced sent/draft email from IMAP.
 *
 * @param array  $staff
 * @param array  $email
 * @param string $imapFolder
 * @param array  $attachments
 *
 * @return int
 */
function mailbox_store_imap_outbox_message($staff, $email, $imapFolder, $attachments)
{
    $CI = &get_instance();
    $staff_id = (int) $staff['staffid'];
    $imapUid  = isset($email['uid']) ? (string) $email['uid'] : '';

    if ($imapUid !== '') {
        $exists = $CI->db->where('sender_staff_id', $staff_id)
            ->where('imap_uid', $imapUid)
            ->where('imap_folder', $imapFolder)
            ->count_all_results(db_prefix().'mail_outbox');

        if ($exists) {
            return 0;
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

    $outbox                  = [];
    $outbox['sender_staff_id'] = $staff_id;
    $outbox['to']            = implode(',', $toList);
    $outbox['cc']            = implode(',', $ccList);
    $outbox['sender_name']   = get_staff_full_name($staff_id);
    $outbox['subject']       = isset($email['subject']) ? $email['subject'] : '';
    $outbox['body']          = isset($email['body']) ? $email['body'] : '';
    $outbox['date_sent']     = mailbox_imap_datetime(isset($email['date']) ? $email['date'] : '');
    $outbox['draft']         = 'draft' === mailbox_map_imap_folder($imapFolder) ? 1 : 0;
    $outbox['trash']         = 'trash' === mailbox_map_imap_folder($imapFolder) ? 1 : 0;
    $outbox['imap_uid']      = $imapUid;
    $outbox['imap_folder']   = $imapFolder;

    $CI->db->insert(db_prefix().'mail_outbox', $outbox);
    $outbox_id = $CI->db->insert_id();

    if ($outbox_id && count($attachments) > 0) {
        mailbox_store_synced_attachments($attachments, $outbox_id, 'outbox');
    }

    return $outbox_id ? 1 : 0;
}

/**
 * Save synced IMAP attachments into module storage and DB.
 *
 * @param array  $attachments
 * @param int    $mailId
 * @param string $type
 *
 * @return void
 */
function mailbox_store_synced_attachments($attachments, $mailId, $type)
{
    $CI = &get_instance();
    $path = MAILBOX_MODULE_UPLOAD_FOLDER.'/'.$type.'/'.$mailId.'/';

    if (!file_exists($path)) {
        mkdir($path, 0755, true);
        $fp = fopen($path.'index.html', 'w');
        fclose($fp);
    }

    foreach ($attachments as $attachment) {
        $filename      = $attachment['filename'];
        $filenameparts = explode('.', $filename);
        $extension     = strtolower(end($filenameparts));
        $filenameOnly  = implode('', array_slice($filenameparts, 0, -1));
        $filenameOnly  = trim(preg_replace('/[^a-zA-Z0-9-_ ]/', '', $filenameOnly));
        if (!$filenameOnly) {
            $filenameOnly = 'attachment';
        }

        $storedFilename = unique_filename($path, $filenameOnly.'.'.$extension);
        $fp             = fopen($path.$storedFilename, 'w');
        fwrite($fp, $attachment['data']);
        fclose($fp);

        $CI->db->insert(db_prefix().'mail_attachment', [
            'mail_id'   => $mailId,
            'type'      => $type,
            'file_name' => $storedFilename,
            'file_type' => get_mime_by_extension($storedFilename),
        ]);
    }

    $CI->db->where('id', $mailId);
    $CI->db->update(db_prefix().'mail_'.$type, [
        'has_attachment' => 1,
    ]);
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
    $enabled      = get_option('mailbox_enabled');
    $imap_server  = get_option('mailbox_imap_server');
    $encryption   = get_option('mailbox_encryption');
    $folder_scan  = get_option('mailbox_folder_scan');
    $unseen_email = get_option('mailbox_only_loop_on_unseen_emails');
    if (1 != $enabled || strlen($imap_server) === 0 || empty($staff['mail_password'])) {
        return 0;
    }

    $CI = &get_instance();
    $imapLibraryPath = APPPATH.'third_party/php-imap/Imap.php';
    if (!file_exists($imapLibraryPath)) {
        $imapLibraryPath = module_dir_path(MAILBOX_MODULE_NAME, 'third_party/php-imap/Imap.php');
    }
    require_once $imapLibraryPath;
    include_once APPPATH.'third_party/simple_html_dom.php';

    mailbox_ensure_sync_columns();

    $staff_email = $staff['email'];
    $staff_id    = $staff['staffid'];
    $email_pass  = $staff['mail_password'];
    $imported    = 0;

    $imap = new Imap($imap_server, $staff_email, $email_pass, $encryption);
    if (false === $imap->isConnected()) {
        log_activity('Failed to connect to IMAP from email: '.$staff_email.' Error: '.$imap->getError(), null);

        return 0;
    }

    if ('' == $folder_scan) {
        $folder_scan = 'Inbox';
    }

    $CI->db->where('staffid', $staff_id);
    $CI->db->update(db_prefix().'staff', [
        'last_email_check' => time(),
    ]);

    $foldersToScan = [];
    if ('all' === $fetchMode) {
        foreach ($imap->getFolders() as $folder) {
            if (mailbox_map_imap_folder($folder) !== null) {
                $foldersToScan[] = $folder;
            }
        }
        if (empty($foldersToScan)) {
            $foldersToScan[] = $folder_scan;
        }
    } else {
        $foldersToScan[] = $folder_scan;
    }

    foreach ($foldersToScan as $activeFolder) {
        $folderCategory = mailbox_map_imap_folder($activeFolder);
        if ($folderCategory === null) {
            continue;
        }

        $imap->selectFolder($activeFolder);
        if ('all' === $fetchMode) {
            $emails = $imap->getMessages();
        } elseif ('recent' === $fetchMode) {
            $emails = $imap->getMessages();
            $emails = array_slice($emails, 0, 25);
        } elseif (1 == $unseen_email) {
            $emails = $imap->getUnreadMessages();
        } else {
            $emails = $imap->getMessages();
        }

        foreach ($emails as $email) {
        $plainTextBody = $imap->getPlainTextBody($email['uid']);
        $plainTextBody = trim($plainTextBody);
        if (!empty($plainTextBody)) {
            $email['body'] = $plainTextBody;
        }
        $email['body']       = handle_google_drive_links_in_text($email['body']);
        $email['body']       = prepare_imap_email_body_html($email['body']);
        $data['attachments'] = [];
        $data                = [];
        $data['attachments'] = [];
        if (isset($email['attachments'])) {
            foreach ($email['attachments'] as $key => $at) {
                $_at_name = $email['attachments'][$key]['name'];
                unset($email['attachments'][$key]['name']);
                $email['attachments'][$key]['filename'] = $_at_name;
                $_attachment                            = $imap->getAttachment($email['uid'], $key);
                $email['attachments'][$key]['data']     = $_attachment['content'];
            }
            $data['attachments'] = $email['attachments'];
        } else {
            $data['attachments'] = [];
        }

        if ('true' == hooks()->apply_filters('imap_fetch_from_email_by_reply_to_header', 'true')) {
            $replyTo = $imap->getReplyToAddresses($email['uid']);

            if (1 === count($replyTo)) {
                $email['from'] = $replyTo[0];
            }
        }
            if (in_array($folderCategory, ['inbox', 'trash'], true)) {
                $imported += mailbox_store_imap_inbox_message($staff, $email, $activeFolder, $data['attachments']);
            } elseif (in_array($folderCategory, ['sent', 'draft'], true)) {
                $imported += mailbox_store_imap_outbox_message($staff, $email, $activeFolder, $data['attachments']);
            }
        }
    }
    }

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
    $CI->db->select()
        ->from(db_prefix().'staff')
        ->where(db_prefix().'staff.mail_password !=', '');

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
