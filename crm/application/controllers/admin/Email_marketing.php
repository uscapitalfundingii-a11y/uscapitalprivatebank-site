<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Email_marketing extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        if (staff_cant('view', 'customers') && !have_assigned_customers()) {
            access_denied('customers');
        }

        $this->load->model('email_marketing_model');
    }

    public function index()
    {
        $data['title'] = _l('email_marketing');
        $data['templates'] = $this->email_marketing_model->get_templates();
        $data['eligible_contacts_count'] = $this->email_marketing_model->count_recipients();
        $this->load->view('admin/email_marketing/index', $data);
    }

    public function template($id)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $template = $this->email_marketing_model->get_template((int) $id);
        echo json_encode([
            'success'  => (bool) $template,
            'template' => $template,
        ]);
    }

    public function launch()
    {
        if (!$this->input->post()) {
            redirect(admin_url('email_marketing'));
        }

        $subject = trim((string) $this->input->post('subject'));
        $message = $this->input->post('message', false);

        if ($subject === '' || trim(strip_tags((string) $message)) === '') {
            set_alert('warning', _l('email_marketing_subject_and_message_required'));
            redirect(admin_url('email_marketing'));
        }

        $campaignId = $this->email_marketing_model->create_campaign([
            'template_name'         => $this->input->post('template_name'),
            'subject'               => $subject,
            'message'               => $message,
            'start_from_contact_id' => (int) $this->input->post('start_from_contact_id'),
            'batch_size'            => (int) $this->input->post('batch_size'),
            'cooling_seconds'       => (int) $this->input->post('cooling_seconds'),
        ]);

        redirect(admin_url('email_marketing/campaign/' . $campaignId));
    }

    public function campaign($id)
    {
        $campaign = $this->email_marketing_model->get_campaign((int) $id);
        if (!$campaign) {
            show_404();
        }

        $data['title'] = _l('email_marketing_campaign_progress');
        $data['campaign'] = $this->email_marketing_model->decorate_campaign($campaign);
        $this->load->view('admin/email_marketing/campaign', $data);
    }

    public function process_batch($id)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $result = $this->email_marketing_model->process_campaign_batch((int) $id);
        echo json_encode($result);
    }
}
