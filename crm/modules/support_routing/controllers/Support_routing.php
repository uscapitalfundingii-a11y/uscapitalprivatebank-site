<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Support_routing extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        if (!is_admin()) {
            access_denied('support_routing');
        }
    }

    public function index()
    {
        $routingResult = support_routing_route_unassigned_tickets(50);
        $companyContextAvailable = $this->db->table_exists(db_prefix() . 'company_context_companies')
            && $this->db->table_exists(db_prefix() . 'company_context_record_map');
        $companies = [];
        if ($companyContextAvailable) {
            $companies = $this->db
                ->order_by('sort_order', 'ASC')
                ->order_by('name', 'ASC')
                ->get(db_prefix() . 'company_context_companies')
                ->result_array();
        }

        $filters = [
            'company_id'  => trim((string) $this->input->get('company_id', true)),
            'company'     => trim((string) $this->input->get('company', true)),
            'source_site' => trim((string) $this->input->get('source_site', true)),
            'specialist'  => trim((string) $this->input->get('specialist', true)),
            'status'      => trim((string) $this->input->get('status', true)),
            'search'      => trim((string) $this->input->get('search', true)),
        ];

        $this->db->select('n.id as note_id,n.description,n.dateadded as note_date,t.ticketid,t.subject,t.userid,t.contactid,t.department,t.status,t.assigned,t.lastreply,t.date as ticket_date,d.name as department_name,ts.name as status_name,c.company,ct.firstname as contact_firstname,ct.lastname as contact_lastname,ct.email as contact_email,ass.firstname as assigned_firstname,ass.lastname as assigned_lastname');
        if ($companyContextAvailable) {
            $this->db->select('cc.id as company_context_id,cc.name as company_context_name,cc.slug as company_context_slug,cc.primary_domain as company_context_domain');
        }
        $this->db->select('(SELECT COUNT(*) FROM ' . db_prefix() . 'ticket_replies tr WHERE tr.ticketid=t.ticketid) as reply_count', false);
        $this->db->select('(SELECT MAX(tr.date) FROM ' . db_prefix() . 'ticket_replies tr WHERE tr.ticketid=t.ticketid) as latest_reply_date', false);
        $this->db->select('(SELECT COUNT(*) FROM ' . db_prefix() . 'ticket_attachments ta WHERE ta.ticketid=t.ticketid) as ticket_attachment_count', false);
        $this->db->select('(SELECT COUNT(*) FROM ' . db_prefix() . 'tasks task WHERE task.rel_type="ticket" AND task.rel_id=t.ticketid) as related_task_count', false);
        $this->db->select('(SELECT COUNT(*) FROM ' . db_prefix() . 'chatclientmessages cm WHERE cm.sender_id=CONCAT("client_", t.contactid) OR cm.reciever_id=CONCAT("client_", t.contactid)) as client_chat_count', false);
        $this->db->from(db_prefix() . 'tickets t');
        $this->db->join(
            db_prefix() . 'notes n',
            'n.rel_id = t.ticketid AND n.rel_type = "ticket" AND n.description LIKE "%' . $this->db->escape_like_str(SUPPORT_ROUTING_NOTE_MARKER) . '%"',
            'left',
            false
        );
        $this->db->join(db_prefix() . 'departments d', 'd.departmentid = t.department', 'left');
        $this->db->join(db_prefix() . 'tickets_status ts', 'ts.ticketstatusid = t.status', 'left');
        $this->db->join(db_prefix() . 'clients c', 'c.userid = t.userid', 'left');
        $this->db->join(db_prefix() . 'contacts ct', 'ct.id = t.contactid', 'left');
        $this->db->join(db_prefix() . 'staff ass', 'ass.staffid = t.assigned', 'left');
        if ($companyContextAvailable) {
            $this->db->join(db_prefix() . 'company_context_record_map crm', 'crm.rel_type = "ticket" AND crm.rel_id = t.ticketid', 'left', false);
            $this->db->join(db_prefix() . 'company_context_companies cc', 'cc.id = crm.company_id', 'left');
            if ($filters['company_id'] !== '' && is_numeric($filters['company_id'])) {
                $this->db->where('cc.id', (int) $filters['company_id']);
            }
        }
        if ($filters['status'] !== '' && is_numeric($filters['status'])) {
            $this->db->where('t.status', (int) $filters['status']);
        }
        if ($filters['search'] !== '') {
            $this->db->group_start();
            $this->db->like('t.subject', $filters['search']);
            $this->db->or_like('c.company', $filters['search']);
            $this->db->or_like('ct.email', $filters['search']);
            $this->db->or_like('n.description', $filters['search']);
            $this->db->group_end();
        }
        $this->db->order_by('t.date', 'DESC');
        $this->db->limit(500);
        $rows = $this->db->get()->result_array();

        $items = [];
        foreach ($rows as $row) {
            $row['routing'] = $this->parse_routing_note($row['description'] ?? '');
            if (!empty($row['company_context_slug']) && empty($row['routing']['source_company'])) {
                $row['routing']['source_company'] = $row['company_context_slug'];
            }
            if (!empty($row['company_context_name']) && empty($row['routing']['source_brand'])) {
                $row['routing']['source_brand'] = $row['company_context_name'];
            }
            $row['has_routing_metadata'] = !empty($row['note_id']);
            if ($filters['company'] !== '') {
                $companyNeedle = strtolower($filters['company']);
                $companyHaystack = strtolower(($row['routing']['source_company'] ?? '') . ' ' . ($row['routing']['source_brand'] ?? '') . ' ' . ($row['company_context_name'] ?? ''));
                if (strpos($companyHaystack, $companyNeedle) === false) {
                    continue;
                }
            }
            if ($filters['source_site'] !== '' && stripos($row['routing']['source_site'] ?? '', $filters['source_site']) === false) {
                continue;
            }
            if ($filters['specialist'] !== '' && stripos($row['routing']['assigned_specialist'] ?? '', $filters['specialist']) === false) {
                continue;
            }
            $items[] = $row;
        }

        $data = [
            'title'          => 'Support Routing',
            'items'          => $items,
            'filters'        => $filters,
            'routing_result' => $routingResult,
            'statuses'       => $this->db->order_by('statusorder', 'ASC')->get(db_prefix() . 'tickets_status')->result_array(),
            'companies'      => $companies,
        ];

        $this->load->view('support_routing/admin/overview', $data);
    }

    public function route_now()
    {
        if (!$this->input->is_ajax_request() && strtoupper($this->input->method()) !== 'POST') {
            show_404();
        }

        $result = support_routing_route_unassigned_tickets(50);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => true,
                'result'  => $result,
            ]));
    }

    private function parse_routing_note($description)
    {
        $metadata = [];
        foreach (support_routing_fields() as $field) {
            $metadata[$field] = '';
        }
        $metadata['assignment_status'] = '';
        $metadata['assigned_staffid'] = '';
        $metadata['route_reason'] = '';
        $metadata['captured_at'] = '';
        $metadata['capture_status'] = 'not_captured';

        $start = strpos($description, SUPPORT_ROUTING_NOTE_MARKER);
        $end = strpos($description, SUPPORT_ROUTING_NOTE_END_MARKER);
        if ($start === false || $end === false || $end <= $start) {
            return $metadata;
        }

        $json = trim(substr($description, $start + strlen(SUPPORT_ROUTING_NOTE_MARKER), $end - ($start + strlen(SUPPORT_ROUTING_NOTE_MARKER))));
        $decoded = json_decode($json, true);
        if (is_array($decoded)) {
            foreach ($decoded as $key => $value) {
                $metadata[$key] = is_scalar($value) ? (string) $value : '';
            }
            $metadata['capture_status'] = 'captured';
        }

        return $metadata;
    }
}
