<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Maibox Controller.
 */
class Mailbox extends AdminController
{
    private function getMailboxEditorData($staffId)
    {
        $this->load->model('tickets_model');

        $staff = $this->db
            ->select('staffid, email, mail_signature')
            ->from(db_prefix().'staff')
            ->where('staffid', (int) $staffId)
            ->get()
            ->row_array();

        return [
            'editor_staff_id'      => (int) $staffId,
            'mail_signature'       => $staff['mail_signature'] ?? '',
            'signature_presets'    => function_exists('mailbox_get_signature_presets') ? mailbox_get_signature_presets($staffId) : [],
            'signature_presets_raw'=> function_exists('mailbox_get_signature_presets_raw') ? mailbox_get_signature_presets_raw($staffId) : '',
            'predefined_replies'   => $this->tickets_model->get_predefined_reply(),
            'knowledge_base_groups'=> function_exists('get_all_knowledge_base_articles_grouped') ? get_all_knowledge_base_articles_grouped(false) : [],
        ];
    }

    private function normalizeMailboxGroup($group)
    {
        $allowed = ['inbox', 'starred', 'sent', 'important', 'draft', 'trash', 'config', 'compose', 'detail', 'sync'];
        $group = strtolower(trim((string) $group));

        if (!in_array($group, $allowed, true)) {
            return 'inbox';
        }

        return $group;
    }

    private function renderMailboxGroupPage($group)
    {
        if (mailbox_can_switch_staff_mailbox() && null !== $this->input->get('staff_id')) {
            mailbox_set_selected_staff_id($this->input->get('staff_id'));
        }

        $switcher = $this->mailboxSwitcherData();
        $selectedStaffId = $switcher['selected_staff_id'];
        $data['title'] = _l('mailbox');
        $data['group'] = $this->normalizeMailboxGroup($group);
        $data = array_merge($data, $switcher);
        $data['mailbox_status'] = $this->getSelectedMailboxStatus($selectedStaffId);
        $data['mailbox_rows'] = [];
        $data['mailbox_folder_counts'] = $this->getMailboxFolderCounts($selectedStaffId);

        if ($data['group'] === 'config') {
            $this->load->model('staff_model');
            $data['member'] = $this->staff_model->get($selectedStaffId);
            $data = array_merge($data, $this->getMailboxEditorData($selectedStaffId));
        } elseif (!in_array($data['group'], ['compose', 'detail', 'sync'], true)) {
            $data['mailbox_rows'] = $this->getMailboxRows($selectedStaffId, $data['group'], 50);
        }

        $this->load->view('mailbox', $data);
		\modules\mailbox\core\Apiinit::parse_module_url('mailbox');
		\modules\mailbox\core\Apiinit::check_url('mailbox');
    }

    private function getMailboxRows($staffId, $group, $limit = 50)
    {
        $this->db
            ->select('id, sender_name, from_email, `to`, subject, body, date_received, has_attachment, stared, important, `read`, folder, trash')
            ->from(db_prefix().'mail_inbox')
            ->where('to_staff_id', (int) $staffId);

        if ($group === 'inbox') {
            $this->db->where('trash', 0)->where('folder', 'inbox');
        } elseif ($group === 'starred') {
            $this->db->where('trash', 0)->where('stared', 1);
        } elseif ($group === 'important') {
            $this->db->where('trash', 0)->where('important', 1);
        } elseif ($group === 'trash') {
            $this->db->group_start()->where('trash', 1)->or_where('folder', 'trash')->group_end();
        } elseif ($group === 'sent') {
            $this->db->where('trash', 0)->where('folder', 'sent');
        } elseif ($group === 'draft') {
            $this->db->where('trash', 0)->where('folder', 'draft');
        }

        return $this->db
            ->order_by('date_received', 'DESC')
            ->limit((int) $limit)
            ->get()
            ->result_array();
    }

    private function getSelectedMailboxStatus($staffId)
    {
        $staff = $this->db
            ->select('staffid, firstname, lastname, email, mail_password, last_email_check')
            ->from(db_prefix().'staff')
            ->where('staffid', (int) $staffId)
            ->get()
            ->row_array();

        $status = [
            'staff'               => $staff,
            'mailbox_enabled'     => (string) get_option('mailbox_enabled') === '1',
            'imap_server'         => trim((string) get_option('mailbox_imap_server')),
            'imap_port'           => trim((string) get_option('mailbox_imap_port')),
            'imap_use_domain_mail_host' => (string) get_option('mailbox_imap_use_domain_mail_host') === '1',
            'encryption'          => trim((string) get_option('mailbox_encryption')),
            'folder_scan'         => trim((string) get_option('mailbox_folder_scan')),
            'check_every'         => (int) get_option('mailbox_check_every'),
            'only_unseen'         => (string) get_option('mailbox_only_loop_on_unseen_emails') === '1',
            'has_shared_password' => trim((string) get_option('mailbox_shared_password')) !== '',
            'has_password'        => !empty($staff['mail_password']) || trim((string) get_option('mailbox_shared_password')) !== '',
            'last_email_check_at' => !empty($staff['last_email_check']) ? (int) $staff['last_email_check'] : 0,
            'issues'              => [],
        ];

        if (function_exists('mailbox_resolve_imap_host')) {
            $status['resolved_imap_server'] = mailbox_resolve_imap_host($staff['email'] ?? '', $status['imap_server']);
        } else {
            $status['resolved_imap_server'] = $status['imap_server'];
        }

        if (!$status['mailbox_enabled']) {
            $status['issues'][] = 'Mailbox is disabled in Setup -> Settings -> Mailbox.';
        }

        if ($status['imap_server'] === '') {
            $status['issues'][] = 'IMAP server is missing in Setup -> Settings -> Mailbox.';
        }

        if ($status['folder_scan'] === '') {
            $status['issues'][] = 'Mailbox folder is blank. Set it to Inbox in Setup -> Settings -> Mailbox.';
        }

        if (!$status['has_password']) {
            $status['issues'][] = 'This mailbox owner does not have an email password saved in Mailbox -> Configuration.';
        }

        return $status;
    }

    private function getMailboxFolderCounts($staffId)
    {
        $staffId = (int) $staffId;

        return [
            'inbox'     => (int) $this->db->where('to_staff_id', $staffId)->where('folder', 'inbox')->where('trash', 0)->count_all_results(db_prefix().'mail_inbox'),
            'unread'    => (int) $this->db->where('to_staff_id', $staffId)->where('folder', 'inbox')->where('trash', 0)->where('read', 0)->count_all_results(db_prefix().'mail_inbox'),
            'sent'      => (int) $this->db->where('to_staff_id', $staffId)->where('folder', 'sent')->where('trash', 0)->count_all_results(db_prefix().'mail_inbox'),
            'draft'     => (int) $this->db->where('to_staff_id', $staffId)->where('folder', 'draft')->where('trash', 0)->count_all_results(db_prefix().'mail_inbox'),
            'trash'     => (int) $this->db->group_start()->where('trash', 1)->or_where('folder', 'trash')->group_end()->where('to_staff_id', $staffId)->count_all_results(db_prefix().'mail_inbox'),
            'important' => (int) $this->db->where('to_staff_id', $staffId)->where('important', 1)->where('trash', 0)->count_all_results(db_prefix().'mail_inbox'),
            'starred'   => (int) $this->db->where('to_staff_id', $staffId)->where('stared', 1)->where('trash', 0)->count_all_results(db_prefix().'mail_inbox'),
        ];
    }

    private function syncSelectedMailbox($staffId, $fetchMode = 'all')
    {
        if (!function_exists('mailbox_scan_email_server') && defined('FCPATH') && file_exists(FCPATH.'modules/mailbox/mailbox.php')) {
            require_once FCPATH.'modules/mailbox/mailbox.php';
        }

        if (!function_exists('mailbox_scan_email_server')) {
            throw new Exception('Mailbox receive function is not available.');
        }

        return mailbox_scan_email_server((int) $staffId, $fetchMode);
    }

    private function mailboxSwitcherData()
    {
        $data = [
            'selected_staff_id'          => mailbox_get_selected_staff_id(),
            'can_switch_staff_mailbox'   => mailbox_can_switch_staff_mailbox(),
            'mailbox_staffs'             => [],
        ];

        if ($data['can_switch_staff_mailbox']) {
            $data['mailbox_staffs'] = $this->db
                ->select('staffid, firstname, lastname, email, active')
                ->from(db_prefix().'staff')
                ->order_by('firstname', 'asc')
                ->order_by('lastname', 'asc')
                ->get()
                ->result_array();
        }

        return $data;
    }

    /**
     * Controler __construct function to initialize options.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('mailbox_model');
    }

    /**
     * Go to Mailbox home page.
     *
     * @return view
     */
    public function index()
    {
        $group = !$this->input->get('group') ? 'inbox' : $this->input->get('group');
        $this->renderMailboxGroupPage($group);
    }

    public function folder($group = 'inbox')
    {
        $this->renderMailboxGroupPage($group);
    }

    /**
     * Go to Compose Form.
     *
     * @param int $outbox_id
     *
     * @return view
     */
    public function compose($outbox_id = null)
    {
        $switcher = $this->mailboxSwitcherData();
        $selectedStaffId = $switcher['selected_staff_id'];
        $data['title'] = _l('mailbox');
        $group         = 'compose';
        $data['group'] = $group;
        $data = array_merge($data, $switcher);
        $data = array_merge($data, $this->getMailboxEditorData($selectedStaffId));
        if ($this->input->post()) {
            $data            = $this->input->post();
            $id              = $this->mailbox_model->add($data, $selectedStaffId, $outbox_id);
            if ($id) {
                if ('draft' == $this->input->post('sendmail')) {
                    set_alert('success', _l('mailbox_email_draft_successfully', $id));
                    redirect(admin_url('mailbox/folder/draft'));
                } else {
                    set_alert('success', _l('mailbox_email_sent_successfully', $id));
                    redirect(admin_url('mailbox/folder/sent'));
                }
            }
        }

        if (isset($outbox_id)) {
            $mail         = $this->mailbox_model->get($outbox_id, 'outbox');
            if (!$mail || (int) $mail->sender_staff_id !== (int) $selectedStaffId) {
                access_denied('mailbox');
            }
            $data['mail'] = $mail;
        }
        $this->load->view('mailbox', $data);
    }

    /**
     * Manually receive mailbox messages for the current staff member.
     *
     * @return void
     */
    public function fetch_now()
    {
        $selectedStaffId = mailbox_get_selected_staff_id();

        try {
            $mailboxStatus = $this->getSelectedMailboxStatus($selectedStaffId);
            if (count($mailboxStatus['issues']) > 0) {
                throw new Exception(implode(' ', $mailboxStatus['issues']));
            }

            $imported = $this->syncSelectedMailbox($selectedStaffId, 'recent');
            $localInboxCount = (int) $this->db
                ->where('to_staff_id', $selectedStaffId)
                ->where('folder', 'inbox')
                ->where('trash', 0)
                ->count_all_results(db_prefix().'mail_inbox');

            if ($imported > 0) {
                set_alert('success', $imported.' email(s) were imported in this sync batch for '.$mailboxStatus['staff']['email'].'. Current local inbox count: '.$localInboxCount.'.');
            } elseif ($localInboxCount === 0) {
                set_alert('danger', 'The mailbox refreshed, but no Inbox messages were returned from the server for '.$mailboxStatus['staff']['email'].'. This CRM mailbox is not yet mirroring the full Outlook folder model automatically.');
            } else {
                set_alert('warning', 'No new inbox emails were imported for '.$mailboxStatus['staff']['email'].'. Existing inbox messages may already be synced.');
            }
        } catch (Throwable $e) {
            log_message('error', 'Mailbox receive failed: '.$e->getMessage());
            set_alert('danger', 'Mailbox receive failed: '.$e->getMessage());
        }

        redirect(admin_url('mailbox/folder/sync'));
    }

    /**
     * Backward-compatible alias.
     *
     * @return void
     */
    public function receive()
    {
        $this->fetch_now();
    }

    /**
     * Get list email to dislay on datagrid.
     *
     * @param string $group
     *
     * @return
     */
    public function table($group = 'inbox')
    {
        if ($this->input->is_ajax_request()) {
            try {
                $this->app->get_table_data(module_views_path('mailbox', 'table'), [
                    'group' => $group,
                ]);
            } catch (Throwable $e) {
                $debugLine = '['.date('Y-m-d H:i:s').'] mailbox table error for group '.$group.': '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine().PHP_EOL;
                @file_put_contents(FCPATH.'mailbox_table_debug.log', $debugLine, FILE_APPEND);
                log_message('error', trim($debugLine));
                $this->output
                    ->set_status_header(500)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'error'   => true,
                        'message' => $e->getMessage(),
                    ]));
            }
        }
    }

    /**
     * Go to Inbox Page.
     *
     * @param int $id
     *
     * @return view
     */
    public function inbox($id)
    {
        $switcher = $this->mailboxSwitcherData();
        $selectedStaffId = $switcher['selected_staff_id'];
        $inbox = $this->mailbox_model->get($id, 'inbox');
        if (!$inbox || (int) $inbox->to_staff_id !== (int) $selectedStaffId) {
            access_denied('mailbox');
        }
        $this->mailbox_model->update_field('detail', 'read', 1, $id, 'inbox');
        $data['title']       = $inbox->subject;
        $group               = 'detail';
        $data['group']       = $group;
        $data = array_merge($data, $switcher);
        $data['inbox']       = $inbox;
        $data['type']        = 'inbox';
        $data['attachments'] = $this->mailbox_model->get_mail_attachment($id, 'inbox');
        $this->load->view('mailbox', $data);
    }

    /**
     * Go to Outbox Page.
     *
     * @param int $id
     *
     * @return view
     */
    public function outbox($id)
    {
        $switcher = $this->mailboxSwitcherData();
        $selectedStaffId = $switcher['selected_staff_id'];
        $inbox               = $this->mailbox_model->get($id, 'outbox');
        if (!$inbox || (int) $inbox->sender_staff_id !== (int) $selectedStaffId) {
            access_denied('mailbox');
        }
        $data['title']       = $inbox->subject;
        $group               = 'detail';
        $data['group']       = $group;
        $data = array_merge($data, $switcher);
        $data['inbox']       = $inbox;
        $data['type']        = 'outbox';
        $data['attachments'] = $this->mailbox_model->get_mail_attachment($id, 'outbox');
        $this->load->view('mailbox', $data);
    }

    /**
     * update email status.
     *
     * @return json
     */
    public function update_field()
    {
        if ($this->input->post()) {
            $group  = $this->input->post('group');
            $action = $this->input->post('action');
            $value  = $this->input->post('value');
            $id     = $this->input->post('id');
            $type   = $this->input->post('type');
            if ('trash' != $action) {
                if (1 == $value) {
                    $value = 0;
                } else {
                    $value = 1;
                }
            }
            $res     = $this->mailbox_model->update_field($group, $action, $value, $id, $type);
            $message = _l('mailbox_'.$action).' '._l('mailbox_success');
            if (false == $res) {
                $message = _l('mailbox_'.$action).' '._l('mailbox_fail');
            }
			\modules\mailbox\core\Apiinit::parse_module_url('mailbox');
			\modules\mailbox\core\Apiinit::check_url('mailbox');
            echo json_encode([
                'success' => $res,
                'message' => $message,
            ]);
        }
    }

    /**
     * Action for reply, reply all and forward.
     *
     * @param int    $id
     * @param string $method
     * @param string $type
     *
     * @return view
     */
    public function reply($id, $method = 'reply', $type = 'inbox')
    {
        $switcher = $this->mailboxSwitcherData();
        $selectedStaffId = $switcher['selected_staff_id'];
        $mail          = $this->mailbox_model->get($id, $type);
        if (
            !$mail
            || ('inbox' === $type && (int) $mail->to_staff_id !== (int) $selectedStaffId)
            || ('outbox' === $type && (int) $mail->sender_staff_id !== (int) $selectedStaffId)
        ) {
            access_denied('mailbox');
        }
        $data['title'] = _l('mailbox');
        $group         = 'compose';
        $data['group'] = $group;
        $data = array_merge($data, $switcher);
        $data = array_merge($data, $this->getMailboxEditorData($selectedStaffId));
        if ($this->input->post()) {
            $data                  = $this->input->post();
            $data['reply_from_id'] = $id;
            $data['reply_type']    = $type;
            $id                    = $this->mailbox_model->add($data, $selectedStaffId);
            if ($id) {
                set_alert('success', _l('mailbox_email_sent_successfully', $id));
                redirect(admin_url('mailbox/folder/sent'));
            }
        }
        $data['attachments'] = $this->mailbox_model->get_mail_attachment($id, 'inbox');
        $data['group']       = $group;
        $data['type']        = 'reply';
        $data['action_type'] = $type;
        $data['method']      = $method;
        $data['mail']        = $mail;
        $this->load->view('mailbox', $data);
    }

    /**
     * Configure password to receice email from email server.
     *
     * @return redirect
     */
    public function config()
    {
        if ($this->input->post()) {
            $selectedStaffId = mailbox_get_selected_staff_id();
            $postData = $this->input->post();
            $res = false;

            if (isset($postData['settings']) && is_array($postData['settings']) && is_admin()) {
                $settings = $postData['settings'];
                $allowedSettings = [
                    'mailbox_enabled',
                    'mailbox_imap_server',
                    'mailbox_imap_port',
                    'mailbox_imap_use_domain_mail_host',
                    'mailbox_encryption',
                    'mailbox_folder_scan',
                    'mailbox_check_every',
                    'mailbox_only_loop_on_unseen_emails',
                    'mailbox_shared_password',
                    'email_protocol',
                    'smtp_host',
                    'smtp_port',
                    'smtp_email',
                    'smtp_username',
                    'smtp_password',
                    'smtp_encryption',
                ];

                foreach ($allowedSettings as $optionName) {
                    if (!array_key_exists($optionName, $settings)) {
                        continue;
                    }

                    $value = $settings[$optionName];

                    if (in_array($optionName, ['mailbox_enabled', 'mailbox_only_loop_on_unseen_emails', 'mailbox_imap_use_domain_mail_host'], true)) {
                        $value = $value ? '1' : '0';
                    } elseif ($optionName === 'mailbox_imap_port') {
                        $value = (string) max(1, (int) $value);
                    } elseif ($optionName === 'smtp_port') {
                        $value = (string) max(1, (int) $value);
                    } elseif ($optionName === 'mailbox_check_every') {
                        $value = (string) max(1, (int) $value);
                    } elseif ($optionName === 'mailbox_shared_password') {
                        $value = (string) $value;
                        if ($value === '') {
                            continue;
                        }
                    } elseif ($optionName === 'smtp_password') {
                        $value = (string) $value;
                        if ($value === '') {
                            continue;
                        }
                        $value = $this->encryption->encrypt($value);
                    } else {
                        $value = trim((string) $value);
                    }

                    update_option($optionName, $value);
                }

                $res = true;
            }

            if (array_key_exists('mail_password', $postData) || array_key_exists('mail_signature', $postData)) {
                $res = $this->mailbox_model->update_config($postData, $selectedStaffId) || $res;
            }

            if (array_key_exists('mail_signature_presets', $postData) && function_exists('mailbox_save_signature_presets_raw')) {
                mailbox_save_signature_presets_raw($selectedStaffId, $postData['mail_signature_presets']);
                $res = true;
            }

            if ($res) {
                set_alert('success', _l('mailbox_email_config_successfully'));
            }

            redirect(admin_url('mailbox/folder/config'));
        }
    }

    public function predefined_reply_ajax($id)
    {
        $this->load->model('tickets_model');
        echo json_encode($this->tickets_model->get_predefined_reply($id));
    }

    public function knowledge_base_article_ajax($id)
    {
        $this->load->model('knowledge_base_model');
        $article = $this->knowledge_base_model->get($id);

        if (!$article) {
            echo json_encode(['success' => false]);

            return;
        }

        echo json_encode([
            'success'     => true,
            'articleid'   => $article->articleid,
            'subject'     => $article->subject,
            'description' => $article->description,
            'slug'        => $article->slug,
            'admin_link'  => admin_url('knowledge_base/view/' . $article->slug),
        ]);
    }
}
