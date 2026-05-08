<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Company_context extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        if (!is_staff_logged_in()) {
            access_denied('company_context');
        }

        $this->load->model('company_context/company_context_model');
    }

    public function index()
    {
        $requestedCompanyId = (int) $this->input->get('company_id');
        $isAdmin = is_admin();
        $accessibleCompanies = $this->company_context_model->get_accessible_companies(get_staff_user_id(), $isAdmin);
        $accessibleIds = array_map('intval', array_column($accessibleCompanies, 'id'));

        if ($requestedCompanyId > 0 && !$isAdmin && !in_array($requestedCompanyId, $accessibleIds, true)) {
            access_denied('company_context');
        }

        $currentCompanyId = $requestedCompanyId > 0
            ? $requestedCompanyId
            : (int) $this->session->userdata(COMPANY_CONTEXT_SESSION_KEY);

        if ($requestedCompanyId > 0) {
            $this->company_context_model->set_current_company($requestedCompanyId);
        }

        if (!$isAdmin) {
            if ($currentCompanyId <= 0 || !in_array($currentCompanyId, $accessibleIds, true)) {
                $currentCompanyId = $accessibleCompanies ? (int) $accessibleCompanies[0]['id'] : -1;
            }
        }

        $data = [
            'title'              => _l('company_context_menu'),
            'companies'          => $accessibleCompanies,
            'current_company_id' => $currentCompanyId,
            'current_company'    => $currentCompanyId > 0 ? $this->company_context_model->get_company($currentCompanyId) : null,
            'staff_lanes'        => $currentCompanyId < 0 ? [] : $this->company_context_model->get_staff_lanes($currentCompanyId),
            'recent_tickets'     => $currentCompanyId < 0 ? [] : $this->company_context_model->get_recent_company_tickets($currentCompanyId, 50),
        ];

        $this->load->view('company_context/admin/manage', $data);
    }

    public function select()
    {
        if (strtoupper($this->input->method()) !== 'POST') {
            redirect(admin_url());
        }

        $companyId = (int) $this->input->post('company_id');
        if ($companyId > 0 && !is_admin()) {
            $allowed = $this->company_context_model->get_accessible_companies(get_staff_user_id(), false);
            $allowedIds = array_map('intval', array_column($allowed, 'id'));
            if (!in_array($companyId, $allowedIds, true)) {
                access_denied('company_context');
            }
        }

        $this->company_context_model->set_current_company($companyId);
        redirect($this->agent->referrer() ?: admin_url('company_context'));
    }

    public function update_company($companyId)
    {
        if (!is_admin()) {
            access_denied('company_context');
        }

        if (strtoupper($this->input->method()) !== 'POST') {
            redirect(admin_url('company_context'));
        }

        $payload = [
            'name'                  => trim((string) $this->input->post('name', true)),
            'public_label'          => trim((string) $this->input->post('public_label', true)),
            'primary_domain'        => trim((string) $this->input->post('primary_domain', true)),
            'domain_aliases'        => trim((string) $this->input->post('domain_aliases', true)),
            'support_email'         => trim((string) $this->input->post('support_email', true)),
            'default_from_email'    => trim((string) $this->input->post('default_from_email', true)),
            'reply_to_email'        => trim((string) $this->input->post('reply_to_email', true)),
            'bounce_email'          => trim((string) $this->input->post('bounce_email', true)),
            'allowed_sender_domains'=> trim((string) $this->input->post('allowed_sender_domains', true)),
            'mailbox_owner_staffid' => (int) $this->input->post('mailbox_owner_staffid'),
            'support_url'           => trim((string) $this->input->post('support_url', true)),
            'primary_color'         => trim((string) $this->input->post('primary_color', true)),
            'secondary_color'       => trim((string) $this->input->post('secondary_color', true)),
            'accent_color'          => trim((string) $this->input->post('accent_color', true)),
            'logo_url'              => trim((string) $this->input->post('logo_url', true)),
            'default_department_id' => (int) $this->input->post('default_department_id'),
            'default_staffid'       => (int) $this->input->post('default_staffid'),
            'active'                => (int) $this->input->post('active') ? 1 : 0,
            'sort_order'            => (int) $this->input->post('sort_order'),
        ];

        $this->company_context_model->update_company((int) $companyId, $payload);
        set_alert('success', _l('updated_successfully', _l('company_context_company')));
        redirect(admin_url('company_context?company_id=' . (int) $companyId));
    }
}
