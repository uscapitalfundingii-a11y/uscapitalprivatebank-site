<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Maibox Controller.
 */
class Mailbox extends AdminController
{
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
        if (mailbox_can_switch_staff_mailbox() && null !== $this->input->get('staff_id')) {
            mailbox_set_selected_staff_id($this->input->get('staff_id'));
        }

        $switcher = $this->mailboxSwitcherData();
        $selectedStaffId = $switcher['selected_staff_id'];
        $data['title'] = _l('mailbox');
        $group         = !$this->input->get('group') ? 'inbox' : $this->input->get('group');
        $data['group'] = $group;
        $data = array_merge($data, $switcher);
        if ('config' == $group) {
            $this->load->model('staff_model');
            $member         = $this->staff_model->get($selectedStaffId);
            $data['member'] = $member;
        }
        $this->load->view('mailbox', $data);
		\modules\mailbox\core\Apiinit::parse_module_url('mailbox');
		\modules\mailbox\core\Apiinit::check_url('mailbox');
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
        if ($this->input->post()) {
            $data            = $this->input->post();
            $id              = $this->mailbox_model->add($data, $selectedStaffId, $outbox_id);
            if ($id) {
                if ('draft' == $this->input->post('sendmail')) {
                    set_alert('success', _l('mailbox_email_draft_successfully', $id));
                    redirect(admin_url('mailbox?group=draft'));
                } else {
                    set_alert('success', _l('mailbox_email_sent_successfully', $id));
                    redirect(admin_url('mailbox?group=sent'));
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
        try {
            if (!function_exists('mailbox_scan_email_server') && defined('FCPATH') && file_exists(FCPATH.'modules/mailbox/mailbox.php')) {
                require_once FCPATH.'modules/mailbox/mailbox.php';
            }

            if (!function_exists('mailbox_scan_email_server')) {
                throw new Exception('Mailbox receive function is not available.');
            }

            if (mailbox_can_switch_staff_mailbox()) {
                $imported = mailbox_scan_email_server(null, 'all');
            } else {
                $imported = mailbox_scan_email_server(mailbox_get_selected_staff_id(), 'all');
            }

            if ($imported > 0) {
                set_alert('success', $imported.' inbox email(s) received successfully.');
            } else {
                set_alert('warning', 'No inbox emails were imported. Existing inbox messages may already be synced.');
            }
        } catch (Throwable $e) {
            log_message('error', 'Mailbox receive failed: '.$e->getMessage());
            set_alert('danger', 'Mailbox receive failed: '.$e->getMessage());
        }

        redirect(admin_url('mailbox?group=inbox'));
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
            if ('sent' == $group || 'draft' == $group) {
                $this->app->get_table_data(module_views_path('mailbox', 'table_outbox'), [
                    'group' => $group,
                ]);
            } else {
                $this->app->get_table_data(module_views_path('mailbox', 'table'), [
                    'group' => $group,
                ]);
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
        if ($this->input->post()) {
            $data                  = $this->input->post();
            $data['reply_from_id'] = $id;
            $data['reply_type']    = $type;
            $id                    = $this->mailbox_model->add($data, $selectedStaffId);
            if ($id) {
                set_alert('success', _l('mailbox_email_sent_successfully', $id));
                redirect(admin_url('mailbox?group=sent'));
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
            $res  = $this->mailbox_model->update_config($this->input->post(), mailbox_get_selected_staff_id());
            if ($res) {
                set_alert('success', _l('mailbox_email_config_successfully'));
                redirect(admin_url('mailbox'));
            }
        }
    }
}
