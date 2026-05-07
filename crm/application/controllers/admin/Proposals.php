<?php

use app\services\proposals\ProposalsPipeline;

defined('BASEPATH') or exit('No direct script access allowed');

class Proposals extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('proposals_model');
        $this->load->model('currencies_model');
    }

    private function proposal_templates()
    {
        return [
            'crm-portal-onboarding' => [
                'slug'        => 'crm-portal-onboarding',
                'category'    => 'Client onboarding',
                'title'       => 'CRM Portal / Client Onboarding',
                'subject'     => 'CRM Portal And Client Onboarding Proposal',
                'description' => 'Use when a client needs a structured CRM workspace, document upload guidance, and relationship-team routing.',
                'content'     => '<h2>CRM Portal And Client Onboarding</h2>'
                    . '<p>Thank you for the opportunity to support your onboarding request. This proposal outlines a secure CRM workspace where your relationship team can organize communication, document requests, project updates, and support tickets in one traceable record.</p>'
                    . '<h3>Recommended Scope</h3>'
                    . '<ul><li>Create or confirm the client portal profile and project workspace.</li><li>Route support questions to the correct department or specialist.</li><li>Provide document upload guidance for welcome-package, profile, and onboarding materials.</li><li>Keep follow-up items visible through tickets, tasks, and project updates.</li></ul>'
                    . '<h3>Important Boundaries</h3>'
                    . '<p>This proposal does not guarantee approval, funding, KYC outcome, account activation, wire movement, bank instrument issuance, legal outcome, investment result, or return. Restricted matters are reviewed by the appropriate compliance, legal, security, or executive team.</p>'
                    . '<h3>Proposal Items</h3>{proposal_items}',
            ],
            'institutional-services-review' => [
                'slug'        => 'institutional-services-review',
                'category'    => 'Institutional services',
                'title'       => 'Institutional Services Review',
                'subject'     => 'Institutional Services Review Proposal',
                'description' => 'Use for institutional intake, relationship discovery, and next-step planning without promising outcomes.',
                'content'     => '<h2>Institutional Services Review</h2>'
                    . '<p>We appreciate the opportunity to review your institutional request. This proposal provides a structured path for discovery, document readiness, and specialist routing so the right team can evaluate the request responsibly.</p>'
                    . '<h3>Recommended Scope</h3>'
                    . '<ul><li>Confirm the organization, authorized contacts, and requested service area.</li><li>Collect the high-level transaction, treasury, or account-service background needed for routing.</li><li>Identify the correct internal department and relationship specialist.</li><li>Prepare a follow-up path for documents, calls, and next-step review.</li></ul>'
                    . '<h3>Important Boundaries</h3>'
                    . '<p>Any KYC, account, credit, banking, transaction, deposit, wire, SBLC, monetization, compliance, legal, or investment matter remains subject to formal review. No approval, funding, returns, facility issuance, or transaction outcome is implied.</p>'
                    . '<h3>Proposal Items</h3>{proposal_items}',
            ],
            'kyc-document-readiness' => [
                'slug'        => 'kyc-document-readiness',
                'category'    => 'KYC / documents',
                'title'       => 'KYC And Document Readiness',
                'subject'     => 'KYC And Document Readiness Proposal',
                'description' => 'Use when the client needs help preparing and uploading documents for review.',
                'content'     => '<h2>KYC And Document Readiness</h2>'
                    . '<p>This proposal is intended to help organize the documents and support steps needed for a clear review process. The goal is to reduce back-and-forth and keep the client record complete, traceable, and easy for the review team to inspect.</p>'
                    . '<h3>Recommended Scope</h3>'
                    . '<ul><li>Confirm the client profile and contact details in the CRM portal.</li><li>Guide the client to the proper document upload area.</li><li>Identify missing or unclear document categories for follow-up.</li><li>Route sensitive or restricted documents to the correct review owner.</li></ul>'
                    . '<h3>Important Boundaries</h3>'
                    . '<p>This support does not approve KYC, verify identity outcomes, activate accounts, or confirm transaction capability. Review decisions remain with the authorized review team.</p>'
                    . '<h3>Proposal Items</h3>{proposal_items}',
            ],
            'asset-transaction-intake' => [
                'slug'        => 'asset-transaction-intake',
                'category'    => 'Asset / transaction intake',
                'title'       => 'Asset Or Transaction Intake',
                'subject'     => 'Asset Or Transaction Intake Proposal',
                'description' => 'Use for structured intake of gemstone, asset, instrument, or transaction review requests.',
                'content'     => '<h2>Asset Or Transaction Intake</h2>'
                    . '<p>This proposal creates an organized intake path for reviewing the client request, supporting documents, and appropriate specialist routing. It is designed to keep the conversation professional, documented, and ready for qualified internal review.</p>'
                    . '<h3>Recommended Scope</h3>'
                    . '<ul><li>Open a CRM project or ticket record for the request.</li><li>Collect summary details, supporting documents, and contact information.</li><li>Route asset, instrument, or transaction questions to the assigned specialist.</li><li>Track follow-up questions and requested documentation inside the CRM.</li></ul>'
                    . '<h3>Important Boundaries</h3>'
                    . '<p>Submitted information is not proof of value, validity, transaction readiness, bank acceptance, or monetization eligibility. No funding, returns, instrument issuance, or transaction outcome is promised.</p>'
                    . '<h3>Proposal Items</h3>{proposal_items}',
            ],
            'trading-platform-onboarding' => [
                'slug'        => 'trading-platform-onboarding',
                'category'    => 'Trading platform',
                'title'       => 'Trading Platform Onboarding',
                'subject'     => 'Trading Platform Onboarding Proposal',
                'description' => 'Use for trading platform registration, support routing, and paper/live trading readiness questions.',
                'content'     => '<h2>Trading Platform Onboarding</h2>'
                    . '<p>This proposal outlines a support path for trading platform registration, account setup questions, documentation routing, and platform support. The objective is to help the client reach the correct support lane quickly.</p>'
                    . '<h3>Recommended Scope</h3>'
                    . '<ul><li>Guide the client through registration and portal access.</li><li>Route KYC, suitability, and account setup questions to the proper review team.</li><li>Help identify platform access, market data, or technical support issues.</li><li>Escalate restricted trading, advisory, pricing, or account outcome questions.</li></ul>'
                    . '<h3>Important Boundaries</h3>'
                    . '<p>This proposal does not provide trading advice, investment advice, suitability approval, account approval, performance projection, returns, or live-trading authorization.</p>'
                    . '<h3>Proposal Items</h3>{proposal_items}',
            ],
            'swift-transaction-support' => [
                'slug'        => 'swift-transaction-support',
                'category'    => 'SWIFT / transaction support',
                'title'       => 'SWIFT And Transaction Support',
                'subject'     => 'SWIFT And Transaction Support Proposal',
                'description' => 'Use for safe SWIFT or transaction support intake while keeping restricted operational details private.',
                'content'     => '<h2>SWIFT And Transaction Support</h2>'
                    . '<p>This proposal provides a secure intake and routing structure for SWIFT or transaction-support questions. The CRM record will preserve the request context and route sensitive items to authorized reviewers.</p>'
                    . '<h3>Recommended Scope</h3>'
                    . '<ul><li>Capture the client request and supporting context without exposing restricted details.</li><li>Route forwarded screenshots, bank-to-bank verification questions, or transaction questions to the proper specialist.</li><li>Track required follow-up and document requests inside the CRM.</li><li>Escalate sensitive payment, wire, settlement, compliance, or security matters.</li></ul>'
                    . '<h3>Important Boundaries</h3>'
                    . '<p>We do not confirm SWIFT validity, payment movement, settlement, wire status, UETR status, RWA, POF, BCL, account status, or instrument issuance in proposal text or chat. Restricted operational details remain internal-only.</p>'
                    . '<h3>Proposal Items</h3>{proposal_items}',
            ],
            'fresh-start-client-plan' => [
                'slug'        => 'fresh-start-client-plan',
                'category'    => 'Fresh Start Financials',
                'title'       => 'Fresh Start Financials Client Plan',
                'subject'     => 'Fresh Start Financials Client Plan Proposal',
                'description' => 'Use for financial restart intake, support organization, and approved next-step planning.',
                'content'     => '<h2>Fresh Start Financials Client Plan</h2>'
                    . '<p>This proposal organizes the client request into a practical intake and support plan. The goal is to understand the client objective, gather relevant information, and route the matter to the correct support lane.</p>'
                    . '<h3>Recommended Scope</h3>'
                    . '<ul><li>Confirm client goals and current support needs.</li><li>Identify required documents, forms, and next-step appointments.</li><li>Route account, credit, billing, or support issues to the appropriate specialist.</li><li>Track follow-up tasks and status updates in the CRM.</li></ul>'
                    . '<h3>Important Boundaries</h3>'
                    . '<p>This proposal does not provide legal, tax, investment, or credit advice and does not guarantee approval, settlement, funding, account changes, or financial outcome.</p>'
                    . '<h3>Proposal Items</h3>{proposal_items}',
            ],
            'media-strategy' => [
                'slug'        => 'media-strategy',
                'category'    => 'Media / marketing',
                'title'       => 'One World Media Strategy',
                'subject'     => 'Media Strategy And Client Communication Proposal',
                'description' => 'Use for media, communications, broadcasting, public-facing copy, or campaign support requests.',
                'content'     => '<h2>Media Strategy And Client Communication</h2>'
                    . '<p>This proposal outlines a structured path for media, communication, and campaign-support requests. The focus is on message clarity, routing, review, and controlled publication approval.</p>'
                    . '<h3>Recommended Scope</h3>'
                    . '<ul><li>Define the communication objective and audience.</li><li>Prepare draft language for internal review.</li><li>Route approvals through the appropriate marketing, compliance, or executive reviewer.</li><li>Track approved assets and follow-up actions in the CRM.</li></ul>'
                    . '<h3>Important Boundaries</h3>'
                    . '<p>Draft language must not be published, sent, advertised, or customer-visible until the approved reviewer confirms final language, links, audience, and compliance posture.</p>'
                    . '<h3>Proposal Items</h3>{proposal_items}',
            ],
        ];
    }

    public function index($proposal_id = '')
    {
        $this->list_proposals($proposal_id);
    }

    public function list_proposals($proposal_id = '')
    {
        close_setup_menu();

        if (staff_cant('view', 'proposals') && staff_cant('view_own', 'proposals') && get_option('allow_staff_view_estimates_assigned') == 0) {
            access_denied('proposals');
        }

        $isPipeline = $this->session->userdata('proposals_pipeline') == 'true';

        if ($isPipeline && !$this->input->get('status')) {
            $data['title']           = _l('proposals_pipeline');
            $data['bodyclass']       = 'proposals-pipeline';
            $data['switch_pipeline'] = false;
            // Direct access
            if (is_numeric($proposal_id)) {
                $data['proposalid'] = $proposal_id;
            } else {
                $data['proposalid'] = $this->session->flashdata('proposalid');
            }

            $this->load->view('admin/proposals/pipeline/manage', $data);
        } else {

            // Pipeline was initiated but user click from home page and need to show table only to filter
            if ($this->input->get('status') && $isPipeline) {
                $this->pipeline(0, true);
            }

            $data['proposal_id']           = $proposal_id;
            $data['switch_pipeline']       = true;
            $data['title']                 = _l('proposals');
            $data['proposal_statuses']     = $this->proposals_model->get_statuses();
            $data['proposals_sale_agents'] = $this->proposals_model->get_sale_agents();
            $data['years']                 = $this->proposals_model->get_proposals_years();
            $data['table'] = App_table::find('proposals');
            $this->load->view('admin/proposals/manage', $data);
        }
    }

    public function table()
    {
        if (
            staff_cant('view', 'proposals')
            && staff_cant('view_own', 'proposals')
            && get_option('allow_staff_view_proposals_assigned') == 0
        ) {
            ajax_access_denied();
        }

        App_table::find('proposals')->output();
    }

    public function proposal_relations($rel_id, $rel_type)
    {
        $this->app->get_table_data('proposals_relations', [
            'rel_id'   => $rel_id,
            'rel_type' => $rel_type,
        ]);
    }

    public function delete_attachment($id)
    {
        $file = $this->misc_model->get_file($id);
        if ($file->staffid == get_staff_user_id() || is_admin()) {
            echo $this->proposals_model->delete_attachment($id);
        } else {
            ajax_access_denied();
        }
    }

    public function clear_signature($id)
    {
        if (staff_can('delete',  'proposals')) {
            $this->proposals_model->clear_signature($id);
        }

        redirect(admin_url('proposals/list_proposals/' . $id));
    }

    public function sync_data()
    {
        if (staff_can('create',  'proposals') || staff_can('edit',  'proposals')) {
            $has_permission_view = staff_can('view',  'proposals');

            $this->db->where('rel_id', $this->input->post('rel_id'));
            $this->db->where('rel_type', $this->input->post('rel_type'));

            if (!$has_permission_view) {
                $this->db->where('addedfrom', get_staff_user_id());
            }

            $address = trim($this->input->post('address'));
            $address = nl2br($address);
            $this->db->update(db_prefix() . 'proposals', [
                'phone'   => $this->input->post('phone'),
                'zip'     => $this->input->post('zip'),
                'country' => $this->input->post('country'),
                'state'   => $this->input->post('state'),
                'address' => $address,
                'city'    => $this->input->post('city'),
            ]);

            if ($this->db->affected_rows() > 0) {
                echo json_encode([
                    'message' => _l('all_data_synced_successfully'),
                ]);
            } else {
                echo json_encode([
                    'message' => _l('sync_proposals_up_to_date'),
                ]);
            }
        }
    }

    public function proposal($id = '')
    {
        if ($this->input->post()) {
            $proposal_data = $this->input->post();
            if ($id == '') {
                if (staff_cant('create', 'proposals')) {
                    access_denied('proposals');
                }
                $id = $this->proposals_model->add($proposal_data);
                if ($id) {
                    set_alert('success', _l('added_successfully', _l('proposal')));
                    if ($this->set_proposal_pipeline_autoload($id)) {
                        redirect(admin_url('proposals'));
                    } else {
                        redirect(admin_url('proposals/list_proposals/' . $id));
                    }
                }
            } else {
                if (staff_cant('edit', 'proposals')) {
                    access_denied('proposals');
                }
                $success = $this->proposals_model->update($proposal_data, $id);
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('proposal')));
                }
                if ($this->set_proposal_pipeline_autoload($id)) {
                    redirect(admin_url('proposals'));
                } else {
                    redirect(admin_url('proposals/list_proposals/' . $id));
                }
            }
        }
        if ($id == '') {
            $title = _l('add_new', _l('proposal'));
        } else {
            $data['proposal'] = $this->proposals_model->get($id);

            if (!$data['proposal'] || !user_can_view_proposal($id)) {
                blank_page(_l('proposal_not_found'));
            }

            $data['estimate']    = $data['proposal'];
            $data['is_proposal'] = true;
            $title               = _l('edit', _l('proposal'));
        }

        $this->load->model('taxes_model');
        $data['taxes'] = $this->taxes_model->get();
        $this->load->model('invoice_items_model');
        $data['ajaxItems'] = false;
        if (total_rows(db_prefix() . 'items') <= ajax_on_total_items()) {
            $data['items'] = $this->invoice_items_model->get_grouped();
        } else {
            $data['items']     = [];
            $data['ajaxItems'] = true;
        }
        $data['items_groups'] = $this->invoice_items_model->get_groups();

        $data['statuses']      = $this->proposals_model->get_statuses();
        $data['staff']         = $this->staff_model->get('', ['active' => 1]);
        $data['currencies']    = $this->currencies_model->get();
        $data['base_currency'] = $this->currencies_model->get_base_currency();

        if ($id == '') {
            $template_slug = $this->input->get('template');
            $templates     = $this->proposal_templates();

            if ($template_slug && isset($templates[$template_slug])) {
                $data['selected_proposal_template'] = $templates[$template_slug];
                $data['proposal_template_content']  = $templates[$template_slug]['content'];
                $data['proposal_template_subject']  = $templates[$template_slug]['subject'];
            }
        }

        $data['title'] = $title;
        $this->load->view('admin/proposals/proposal', $data);
    }

    public function templates()
    {
        close_setup_menu();

        if (staff_cant('view', 'proposals') && staff_cant('view_own', 'proposals') && staff_cant('create', 'proposals')) {
            access_denied('proposals');
        }

        $data['title']     = 'Proposal Templates';
        $data['templates'] = $this->proposal_templates();

        $this->load->view('admin/proposals/templates', $data);
    }

    public function get_template()
    {
        if (staff_cant('view', 'proposals') && staff_cant('view_own', 'proposals') && staff_cant('create', 'proposals')) {
            ajax_access_denied();
        }

        $name      = $this->input->get('name');
        $templates = $this->proposal_templates();

        if (!isset($templates[$name])) {
            $this->output
                ->set_status_header(404)
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'message' => 'Template not found']));

            return;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success'  => true,
                'template' => $templates[$name],
            ]));
    }

    public function send_expiry_reminder($id)
    {
        $canView = user_can_view_proposal($id);
        if (!$canView) {
            access_denied('proposals');
        } else {
            if (staff_cant('view', 'proposals') && staff_cant('view_own', 'proposals') && $canView == false) {
                access_denied('proposals');
            }
        }

        $success = $this->proposals_model->send_expiry_reminder($id);
        if ($success) {
            set_alert('success', _l('sent_expiry_reminder_success'));
        } else {
            set_alert('danger', _l('sent_expiry_reminder_fail'));
        }
        if ($this->set_proposal_pipeline_autoload($id)) {
            redirect(previous_url() ?: $_SERVER['HTTP_REFERER']);
        } else {
            redirect(admin_url('proposals/list_proposals/' . $id));
        }
    }

    public function clear_acceptance_info($id)
    {
        if (is_admin()) {
            $this->db->where('id', $id);
            $this->db->update(db_prefix() . 'proposals', get_acceptance_info_array(true));
        }

        redirect(admin_url('proposals/list_proposals/' . $id));
    }

    public function pdf($id)
    {
        if (!$id) {
            redirect(admin_url('proposals'));
        }

        $canView = user_can_view_proposal($id);
        if (!$canView) {
            access_denied('proposals');
        } else {
            if (staff_cant('view', 'proposals') && staff_cant('view_own', 'proposals') && $canView == false) {
                access_denied('proposals');
            }
        }

        $proposal = $this->proposals_model->get($id);

        try {
            $pdf = proposal_pdf($proposal);
        } catch (Exception $e) {
            $message = $e->getMessage();
            echo $message;
            if (strpos($message, 'Unable to get the size of the image') !== false) {
                show_pdf_unable_to_get_image_size_error();
            }
            die;
        }

        $type = 'D';

        if ($this->input->get('output_type')) {
            $type = $this->input->get('output_type');
        }

        if ($this->input->get('print')) {
            $type = 'I';
        }

        $proposal_number = format_proposal_number($id);
        $pdf->Output($proposal_number . '.pdf', $type);
    }

    public function get_proposal_data_ajax($id, $to_return = false)
    {
        if (staff_cant('view', 'proposals') && staff_cant('view_own', 'proposals') && get_option('allow_staff_view_proposals_assigned') == 0) {
            echo _l('access_denied');
            die;
        }

        $proposal = $this->proposals_model->get($id, [], true);

        if (!$proposal || !user_can_view_proposal($id)) {
            echo _l('proposal_not_found');
            die;
        }

        $this->app_mail_template->set_rel_id($proposal->id);
        $data = prepare_mail_preview_data('proposal_send_to_customer', $proposal->email);

        $merge_fields = [];

        $merge_fields[] = [
            [
                'name' => 'Items Table',
                'key'  => '{proposal_items}',
            ],
        ];

        $merge_fields = array_merge($merge_fields, $this->app_merge_fields->get_flat('proposals', 'other', '{email_signature}'));

        $data['proposal_statuses']     = $this->proposals_model->get_statuses();
        $data['members']               = $this->staff_model->get('', ['active' => 1]);
        $data['proposal_merge_fields'] = $merge_fields;
        $data['proposal']              = $proposal;
        $data['totalNotes']            = total_rows(db_prefix() . 'notes', ['rel_id' => $id, 'rel_type' => 'proposal']);
        if ($to_return == false) {
            $this->load->view('admin/proposals/proposals_preview_template', $data);
        } else {
            return $this->load->view('admin/proposals/proposals_preview_template', $data, true);
        }
    }

    public function add_note($rel_id)
    {
        if ($this->input->post() && user_can_view_proposal($rel_id)) {
            $this->misc_model->add_note($this->input->post(), 'proposal', $rel_id);
            echo $rel_id;
        }
    }

    public function get_notes($id)
    {
        if (user_can_view_proposal($id)) {
            $data['notes'] = $this->misc_model->get_notes($id, 'proposal');
            $this->load->view('admin/includes/sales_notes_template', $data);
        }
    }

    public function convert_to_estimate($id)
    {
        if (staff_cant('create', 'estimates')) {
            access_denied('estimates');
        }
        if ($this->input->post()) {
            $this->load->model('estimates_model');
            $estimate_id = $this->estimates_model->add($this->input->post());
            if ($estimate_id) {
                set_alert('success', _l('proposal_converted_to_estimate_success'));
                $this->db->where('id', $id);
                $this->db->update(db_prefix() . 'proposals', [
                    'estimate_id' => $estimate_id,
                    'status'      => 3,
                ]);
                log_activity('Proposal Converted to Estimate [EstimateID: ' . $estimate_id . ', ProposalID: ' . $id . ']');

                hooks()->do_action('proposal_converted_to_estimate', ['proposal_id' => $id, 'estimate_id' => $estimate_id]);

                redirect(admin_url('estimates/estimate/' . $estimate_id));
            } else {
                set_alert('danger', _l('proposal_converted_to_estimate_fail'));
            }
            if ($this->set_proposal_pipeline_autoload($id)) {
                redirect(admin_url('proposals'));
            } else {
                redirect(admin_url('proposals/list_proposals/' . $id));
            }
        }
    }

    public function convert_to_invoice($id)
    {
        if (staff_cant('create', 'invoices')) {
            access_denied('invoices');
        }
        if ($this->input->post()) {
            $this->load->model('invoices_model');
            $invoice_id = $this->invoices_model->add($this->input->post());
            if ($invoice_id) {
                set_alert('success', _l('proposal_converted_to_invoice_success'));
                $this->db->where('id', $id);
                $this->db->update(db_prefix() . 'proposals', [
                    'invoice_id' => $invoice_id,
                    'status'     => 3,
                ]);
                log_activity('Proposal Converted to Invoice [InvoiceID: ' . $invoice_id . ', ProposalID: ' . $id . ']');

                do_action_deprecated('proposal_converted_to_invoice', ['proposal_id' => $id, 'invoice_id' => $invoice_id], '3.1.6', 'after_proposal_converted_to_invoice');
                hooks()->do_action('after_proposal_converted_to_invoice', ['proposal_id' => $id, 'invoice_id' => $invoice_id]);

                redirect(admin_url('invoices/invoice/' . $invoice_id));
            } else {
                set_alert('danger', _l('proposal_converted_to_invoice_fail'));
            }
            if ($this->set_proposal_pipeline_autoload($id)) {
                redirect(admin_url('proposals'));
            } else {
                redirect(admin_url('proposals/list_proposals/' . $id));
            }
        }
    }

    public function get_invoice_convert_data($id)
    {
        $this->load->model('payment_modes_model');
        $data['payment_modes'] = $this->payment_modes_model->get('', [
            'expenses_only !=' => 1,
        ]);
        $this->load->model('taxes_model');
        $data['taxes']         = $this->taxes_model->get();
        $data['currencies']    = $this->currencies_model->get();
        $data['base_currency'] = $this->currencies_model->get_base_currency();
        $this->load->model('invoice_items_model');
        $data['ajaxItems'] = false;
        if (total_rows(db_prefix() . 'items') <= ajax_on_total_items()) {
            $data['items'] = $this->invoice_items_model->get_grouped();
        } else {
            $data['items']     = [];
            $data['ajaxItems'] = true;
        }
        $data['items_groups'] = $this->invoice_items_model->get_groups();

        $data['staff']          = $this->staff_model->get('', ['active' => 1]);
        $data['proposal']       = $this->proposals_model->get($id);
        $data['billable_tasks'] = [];
        $data['add_items']      = $this->_parse_items($data['proposal']);

        if ($data['proposal']->rel_type == 'lead') {
            $this->db->where('leadid', $data['proposal']->rel_id);
            $data['customer_id'] = $this->db->get(db_prefix() . 'clients')->row()->userid;
        } else {
            $data['customer_id'] = $data['proposal']->rel_id;
            $data['project_id'] = $data['proposal']->project_id;
        }
        $data['custom_fields_rel_transfer'] = [
            'belongs_to' => 'proposal',
            'rel_id'     => $id,
        ];
        $this->load->view('admin/proposals/invoice_convert_template', $data);
    }

    public function get_estimate_convert_data($id)
    {
        $this->load->model('taxes_model');
        $data['taxes']         = $this->taxes_model->get();
        $data['currencies']    = $this->currencies_model->get();
        $data['base_currency'] = $this->currencies_model->get_base_currency();
        $this->load->model('invoice_items_model');
        $data['ajaxItems'] = false;
        if (total_rows(db_prefix() . 'items') <= ajax_on_total_items()) {
            $data['items'] = $this->invoice_items_model->get_grouped();
        } else {
            $data['items']     = [];
            $data['ajaxItems'] = true;
        }
        $data['items_groups'] = $this->invoice_items_model->get_groups();

        $data['staff']     = $this->staff_model->get('', ['active' => 1]);
        $data['proposal']  = $this->proposals_model->get($id);
        $data['add_items'] = $this->_parse_items($data['proposal']);

        $this->load->model('estimates_model');
        $data['estimate_statuses'] = $this->estimates_model->get_statuses();
        if ($data['proposal']->rel_type == 'lead') {
            $this->db->where('leadid', $data['proposal']->rel_id);
            $data['customer_id'] = $this->db->get(db_prefix() . 'clients')->row()->userid;
        } else {
            $data['customer_id'] = $data['proposal']->rel_id;
            $data['project_id'] = $data['proposal']->project_id;
        }

        $data['custom_fields_rel_transfer'] = [
            'belongs_to' => 'proposal',
            'rel_id'     => $id,
        ];

        $this->load->view('admin/proposals/estimate_convert_template', $data);
    }

    private function _parse_items($proposal)
    {
        $items = [];
        foreach ($proposal->items as $item) {
            $taxnames = [];
            $taxes    = get_proposal_item_taxes($item['id']);
            foreach ($taxes as $tax) {
                array_push($taxnames, $tax['taxname']);
            }
            $item['taxname']        = $taxnames;
            $item['parent_item_id'] = $item['id'];
            $item['id']             = 0;
            $items[]                = $item;
        }

        return $items;
    }

    /* Send proposal to email */
    public function send_to_email($id)
    {
        $canView = user_can_view_proposal($id);
        if (!$canView) {
            access_denied('proposals');
        } else {
            if (staff_cant('view', 'proposals') && staff_cant('view_own', 'proposals') && $canView == false) {
                access_denied('proposals');
            }
        }

        if ($this->input->post()) {
            try {
                $success = $this->proposals_model->send_proposal_to_email(
                    $id,
                    $this->input->post('attach_pdf'),
                    $this->input->post('cc')
                );
            } catch (Exception $e) {
                $message = $e->getMessage();
                echo $message;
                if (strpos($message, 'Unable to get the size of the image') !== false) {
                    show_pdf_unable_to_get_image_size_error();
                }
                die;
            }

            if ($success) {
                set_alert('success', _l('proposal_sent_to_email_success'));
            } else {
                set_alert('danger', _l('proposal_sent_to_email_fail'));
            }

            if ($this->set_proposal_pipeline_autoload($id)) {
                redirect(previous_url() ?: $_SERVER['HTTP_REFERER']);
            } else {
                redirect(admin_url('proposals/list_proposals/' . $id));
            }
        }
    }

    public function copy($id)
    {
        if (staff_cant('create', 'proposals')) {
            access_denied('proposals');
        }
        $new_id = $this->proposals_model->copy($id);
        if ($new_id) {
            set_alert('success', _l('proposal_copy_success'));
            $this->set_proposal_pipeline_autoload($new_id);
            redirect(admin_url('proposals/proposal/' . $new_id));
        } else {
            set_alert('success', _l('proposal_copy_fail'));
        }
        if ($this->set_proposal_pipeline_autoload($id)) {
            redirect(admin_url('proposals'));
        } else {
            redirect(admin_url('proposals/list_proposals/' . $id));
        }
    }

    public function mark_action_status($status, $id)
    {
        if (staff_cant('edit', 'proposals')) {
            access_denied('proposals');
        }
        $success = $this->proposals_model->mark_action_status($status, $id);
        if ($success) {
            set_alert('success', _l('proposal_status_changed_success'));
        } else {
            set_alert('danger', _l('proposal_status_changed_fail'));
        }
        if ($this->set_proposal_pipeline_autoload($id)) {
            redirect(admin_url('proposals'));
        } else {
            redirect(admin_url('proposals/list_proposals/' . $id));
        }
    }

    public function delete($id)
    {
        if (staff_cant('delete', 'proposals')) {
            access_denied('proposals');
        }
        $response = $this->proposals_model->delete($id);
        if ($response == true) {
            set_alert('success', _l('deleted', _l('proposal')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('proposal_lowercase')));
        }
        redirect(admin_url('proposals'));
    }

    public function get_relation_data_values($rel_id, $rel_type)
    {
        echo json_encode($this->proposals_model->get_relation_data_values($rel_id, $rel_type));
    }

    public function add_proposal_comment()
    {
        if ($this->input->post()) {
            echo json_encode([
                'success' => $this->proposals_model->add_comment($this->input->post()),
            ]);
        }
    }

    public function edit_comment($id)
    {
        if ($this->input->post()) {
            echo json_encode([
                'success' => $this->proposals_model->edit_comment($this->input->post(), $id),
                'message' => _l('comment_updated_successfully'),
            ]);
        }
    }

    public function get_proposal_comments($id)
    {
        $data['comments'] = $this->proposals_model->get_comments($id);
        $this->load->view('admin/proposals/comments_template', $data);
    }

    public function remove_comment($id)
    {
        $this->db->where('id', $id);
        $comment = $this->db->get(db_prefix() . 'proposal_comments')->row();
        if ($comment) {
            if ($comment->staffid != get_staff_user_id() && !is_admin()) {
                echo json_encode([
                    'success' => false,
                ]);
                die;
            }
            echo json_encode([
                'success' => $this->proposals_model->remove_comment($id),
            ]);
        } else {
            echo json_encode([
                'success' => false,
            ]);
        }
    }

    public function save_proposal_data()
    {
        if (staff_cant('edit', 'proposals') && staff_cant('create', 'proposals')) {
            header('HTTP/1.0 400 Bad error');
            echo json_encode([
                'success' => false,
                'message' => _l('access_denied'),
            ]);
            die;
        }
        $success = false;
        $message = '';

        $this->db->where('id', $this->input->post('proposal_id'));
        $this->db->update(db_prefix() . 'proposals', [
            'content' => html_purify($this->input->post('content', false)),
        ]);

        $success = $this->db->affected_rows() > 0;
        $message = _l('updated_successfully', _l('proposal'));

        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);
    }

    // Pipeline
    public function pipeline($set = 0, $manual = false)
    {
        if ($set == 1) {
            $set = 'true';
        } else {
            $set = 'false';
        }
        $this->session->set_userdata([
            'proposals_pipeline' => $set,
        ]);
        if ($manual == false) {
            redirect(admin_url('proposals'));
        }
    }

    public function pipeline_open($id)
    {
        if (staff_can('view',  'proposals') || staff_can('view_own',  'proposals') || get_option('allow_staff_view_proposals_assigned') == 1) {
            $data['proposal']      = $this->get_proposal_data_ajax($id, true);
            $data['proposal_data'] = $this->proposals_model->get($id);
            $this->load->view('admin/proposals/pipeline/proposal', $data);
        }
    }

    public function update_pipeline()
    {
        if (staff_can('edit',  'proposals')) {
            $this->proposals_model->update_pipeline($this->input->post());
        }
    }

    public function get_pipeline()
    {
        if (staff_can('view',  'proposals') || staff_can('view_own',  'proposals') || get_option('allow_staff_view_proposals_assigned') == 1) {
            $data['statuses'] = $this->proposals_model->get_statuses();
            $this->load->view('admin/proposals/pipeline/pipeline', $data);
        }
    }

    public function pipeline_load_more()
    {
        $status = $this->input->get('status');
        $page   = $this->input->get('page');

        $proposals = (new ProposalsPipeline($status))
        ->search($this->input->get('search'))
        ->sortBy(
            $this->input->get('sort_by'),
            $this->input->get('sort')
        )
        ->page($page)->get();

        foreach ($proposals as $proposal) {
            $this->load->view('admin/proposals/pipeline/_kanban_card', [
                'proposal' => $proposal,
                'status'   => $status,
            ]);
        }
    }

    public function set_proposal_pipeline_autoload($id)
    {
        if ($id == '') {
            return false;
        }

        if ($this->session->has_userdata('proposals_pipeline') && $this->session->userdata('proposals_pipeline') == 'true') {
            $this->session->set_flashdata('proposalid', $id);

            return true;
        }

        return false;
    }

    public function get_due_date()
    {
        if ($this->input->post()) {
            $date    = $this->input->post('date');
            $duedate = '';
            if (get_option('proposal_due_after') != 0) {
                $date    = to_sql_date($date);
                $d       = date('Y-m-d', strtotime('+' . get_option('proposal_due_after') . ' DAY', strtotime($date)));
                $duedate = _d($d);
                echo $duedate;
            }
        }
    }
}
