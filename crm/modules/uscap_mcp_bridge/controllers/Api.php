<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Api extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->output->set_content_type('application/json');
    }

    public function health(): void
    {
        $this->json([
            'bridge_module' => 'uscap_mcp_bridge',
            'status' => get_option('uscap_mcp_bridge_enabled') === '1' ? 'ok' : 'disabled',
            'token_configured' => $this->configuredToken() !== '',
            'perfex_version' => defined('APP_VERSION') ? APP_VERSION : null,
            'timestamp' => date('c'),
        ]);
    }

    public function schema(): void
    {
        if (! $this->authorize()) {
            return;
        }

        $this->json([
            'modules' => ['customers', 'contacts', 'leads', 'tasks', 'tickets', 'notes', 'native_chat'],
            'custom_fields_recommended' => [
                'onboarding_stage',
                'welcome_package_status',
                'welcome_package_type',
                'welcome_package_sent_at',
                'welcome_package_opened_at',
                'welcome_package_due_at',
                'welcome_package_received_at',
                'last_followup_at',
                'next_followup_at',
                'missing_items',
                'internal_owner',
                'morpheus_status',
            ],
            'endpoints' => [
                'GET /uscap_mcp_bridge/api/health',
                'GET /uscap_mcp_bridge/api/schema',
                'GET /uscap_mcp_bridge/api/search?query=...',
                'GET /uscap_mcp_bridge/api/customers',
                'GET /uscap_mcp_bridge/api/customers/{id}',
                'POST /uscap_mcp_bridge/api/customers',
                'PUT /uscap_mcp_bridge/api/customers/{id}',
                'GET /uscap_mcp_bridge/api/leads',
                'GET /uscap_mcp_bridge/api/leads/{id}',
                'POST /uscap_mcp_bridge/api/leads',
                'PUT /uscap_mcp_bridge/api/leads/{id}',
                'GET /uscap_mcp_bridge/api/tasks',
                'POST /uscap_mcp_bridge/api/tasks',
                'POST /uscap_mcp_bridge/api/notes',
                'GET /uscap_mcp_bridge/api/tickets',
                'GET /uscap_mcp_bridge/api/chat_unread',
                'GET /uscap_mcp_bridge/api/chat_conversation/{peer}',
                'POST /uscap_mcp_bridge/api/chat_reply',
            ],
        ]);
    }

    public function search(): void
    {
        if (! $this->authorize()) {
            return;
        }

        $query = trim((string) $this->input->get('query', true));
        if ($query === '') {
            $query = trim((string) $this->input->get('q', true));
        }
        if ($query === '') {
            $this->jsonError('missing_required_field', 'query is required', 422);
            return;
        }

        $like = '%' . $this->db->escape_like_str($query) . '%';

        $customers = $this->db
            ->select('userid, company, phonenumber, datecreated, active')
            ->from(db_prefix() . 'clients')
            ->group_start()
            ->like('company', $query)
            ->or_like('phonenumber', $query)
            ->group_end()
            ->limit(10)
            ->get()
            ->result_array();

        $contacts = $this->db
            ->select('id, userid, firstname, lastname, email, phonenumber, is_primary, active')
            ->from(db_prefix() . 'contacts')
            ->group_start()
            ->like('email', $query)
            ->or_like('firstname', $query)
            ->or_like('lastname', $query)
            ->or_like('phonenumber', $query)
            ->group_end()
            ->limit(10)
            ->get()
            ->result_array();

        $leads = $this->db
            ->select('id, name, email, phonenumber, status, source, dateadded, lastcontact')
            ->from(db_prefix() . 'leads')
            ->group_start()
            ->like('name', $query)
            ->or_like('email', $query)
            ->or_like('phonenumber', $query)
            ->or_like('company', $query)
            ->group_end()
            ->limit(10)
            ->get()
            ->result_array();

        $this->json([
            'query' => $query,
            'customers' => $customers,
            'contacts' => $contacts,
            'leads' => $leads,
            'sql_like' => $like,
        ]);
    }

    public function customers($id = ''): void
    {
        if (! $this->authorize()) {
            return;
        }

        $method = $this->method();
        if ($method === 'GET') {
            $this->json($id === '' ? $this->listCustomers() : $this->getCustomer((int) $id));
            return;
        }

        $data = $this->payload();
        if ($method === 'POST') {
            $company = trim((string) ($data['company'] ?? $data['name'] ?? ''));
            if ($company === '') {
                $this->jsonError('missing_required_field', 'company is required', 422);
                return;
            }

            $insert = [
                'company' => $company,
                'phonenumber' => trim((string) ($data['phonenumber'] ?? $data['phone'] ?? '')),
                'website' => trim((string) ($data['website'] ?? '')),
                'datecreated' => date('Y-m-d H:i:s'),
                'active' => 1,
                'addedfrom' => 0,
            ];
            $this->db->insert(db_prefix() . 'clients', $insert);
            $this->json(['created' => true, 'customer_id' => $this->db->insert_id()], 201);
            return;
        }

        if (($method === 'PUT' || $method === 'PATCH') && is_numeric($id)) {
            $allowed = array_intersect_key($data, array_flip(['company', 'phonenumber', 'website', 'active']));
            if ($allowed === []) {
                $this->jsonError('missing_required_field', 'No allowed customer fields supplied', 422);
                return;
            }
            $this->db->where('userid', (int) $id)->update(db_prefix() . 'clients', $allowed);
            $this->json(['updated' => true, 'customer_id' => (int) $id]);
            return;
        }

        $this->jsonError('not_found', 'Unsupported customers route', 404);
    }

    public function leads($id = ''): void
    {
        if (! $this->authorize()) {
            return;
        }

        $method = $this->method();
        if ($method === 'GET') {
            $this->json($id === '' ? $this->listLeads() : $this->rowById('leads', 'id', (int) $id));
            return;
        }

        $data = $this->payload();
        if ($method === 'POST') {
            $name = trim((string) ($data['name'] ?? $data['company'] ?? ''));
            if ($name === '') {
                $this->jsonError('missing_required_field', 'name is required', 422);
                return;
            }

            $insert = [
                'name' => $name,
                'company' => trim((string) ($data['company'] ?? $name)),
                'email' => trim((string) ($data['email'] ?? '')),
                'phonenumber' => trim((string) ($data['phonenumber'] ?? $data['phone'] ?? '')),
                'description' => nl2br(trim((string) ($data['description'] ?? $data['notes'] ?? ''))),
                'status' => (int) ($data['status'] ?? 1),
                'source' => (int) ($data['source'] ?? 1),
                'dateadded' => date('Y-m-d H:i:s'),
                'addedfrom' => 0,
                'is_public' => 0,
            ];
            $this->db->insert(db_prefix() . 'leads', $insert);
            $this->json(['created' => true, 'lead_id' => $this->db->insert_id()], 201);
            return;
        }

        if (($method === 'PUT' || $method === 'PATCH') && is_numeric($id)) {
            $allowed = array_intersect_key($data, array_flip(['name', 'company', 'email', 'phonenumber', 'description', 'status', 'source', 'lastcontact']));
            if (isset($allowed['description'])) {
                $allowed['description'] = nl2br((string) $allowed['description']);
            }
            if ($allowed === []) {
                $this->jsonError('missing_required_field', 'No allowed lead fields supplied', 422);
                return;
            }
            $this->db->where('id', (int) $id)->update(db_prefix() . 'leads', $allowed);
            $this->json(['updated' => true, 'lead_id' => (int) $id]);
            return;
        }

        $this->jsonError('not_found', 'Unsupported leads route', 404);
    }

    public function contacts($id = ''): void
    {
        if (! $this->authorize()) {
            return;
        }

        if ($this->method() === 'GET') {
            $this->json($id === '' ? $this->listRows('contacts', 'id', ['password']) : $this->rowById('contacts', 'id', (int) $id, ['password']));
            return;
        }

        $this->jsonError('not_implemented', 'Contact writes are intentionally deferred until endpoint validation is complete.', 501);
    }

    public function tasks($id = ''): void
    {
        if (! $this->authorize()) {
            return;
        }

        if ($this->method() === 'GET') {
            $this->json($id === '' ? $this->listRows('tasks', 'id') : $this->rowById('tasks', 'id', (int) $id));
            return;
        }

        if ($this->method() === 'POST') {
            $data = $this->payload();
            $name = trim((string) ($data['name'] ?? $data['subject'] ?? ''));
            if ($name === '') {
                $this->jsonError('missing_required_field', 'name/subject is required', 422);
                return;
            }

            $insert = [
                'name' => $name,
                'description' => nl2br(trim((string) ($data['description'] ?? ''))),
                'priority' => (int) ($data['priority'] ?? 2),
                'dateadded' => date('Y-m-d H:i:s'),
                'startdate' => date('Y-m-d'),
                'duedate' => trim((string) ($data['duedate'] ?? $data['due_date'] ?? '')) ?: null,
                'rel_type' => trim((string) ($data['rel_type'] ?? 'customer')),
                'rel_id' => (int) ($data['rel_id'] ?? $data['customer_id'] ?? 0),
                'addedfrom' => 0,
                'status' => (int) ($data['status'] ?? 1),
            ];
            $this->db->insert(db_prefix() . 'tasks', $insert);
            $this->json(['created' => true, 'task_id' => $this->db->insert_id()], 201);
            return;
        }

        $this->jsonError('not_found', 'Unsupported tasks route', 404);
    }

    public function notes(): void
    {
        if (! $this->authorize()) {
            return;
        }

        if ($this->method() !== 'POST') {
            $this->jsonError('not_found', 'Unsupported notes route', 404);
            return;
        }

        $data = $this->payload();
        $relType = trim((string) ($data['rel_type'] ?? $data['related_type'] ?? ''));
        $relId = (int) ($data['rel_id'] ?? $data['related_id'] ?? 0);
        $description = trim((string) ($data['description'] ?? $data['note'] ?? ''));

        if ($relType === '' || $relId <= 0 || $description === '') {
            $this->jsonError('missing_required_field', 'rel_type, rel_id, and description/note are required', 422);
            return;
        }

        $this->db->insert(db_prefix() . 'notes', [
            'rel_type' => $relType,
            'rel_id' => $relId,
            'description' => nl2br($description),
            'dateadded' => date('Y-m-d H:i:s'),
            'addedfrom' => 0,
        ]);

        $this->json(['created' => true, 'note_id' => $this->db->insert_id()], 201);
    }

    public function tickets($id = ''): void
    {
        if (! $this->authorize()) {
            return;
        }

        if ($this->method() === 'GET') {
            $this->json($id === '' ? $this->listRows('tickets', 'ticketid') : $this->rowById('tickets', 'ticketid', (int) $id));
            return;
        }

        $this->jsonError('not_implemented', 'Ticket writes/replies require a second pass and explicit approval.', 501);
    }

    public function recent_intake_records(): void
    {
        if (! $this->authorize()) {
            return;
        }

        $this->json([
            'leads' => $this->listLeads(20),
            'customers' => $this->listCustomers(20),
        ]);
    }

    public function pending_welcome_packages(): void
    {
        if (! $this->authorize()) {
            return;
        }

        $this->json([
            'records' => [],
            'note' => 'Welcome package custom fields are not mapped yet. Add the recommended fields, then map this endpoint to field IDs.',
        ]);
    }

    public function open_onboarding_tasks(): void
    {
        if (! $this->authorize()) {
            return;
        }

        $this->db->like('name', 'onboarding');
        $this->db->where('status !=', 5);
        $this->db->limit(50);
        $this->json($this->db->get(db_prefix() . 'tasks')->result_array());
    }

    public function chat_unread(): void
    {
        if (! $this->authorize()) {
            return;
        }

        if (! $this->tableExists('chatclientmessages')) {
            $this->json(['records' => [], 'note' => 'Native Perfex chat table is not present.']);
            return;
        }

        $staffParam = strtolower(trim((string) ($this->input->get('staff_id', true) ?: 'all')));
        $limit = max(1, min(100, (int) ($this->input->get('limit', true) ?: 50)));

        $this->db
            ->select('id, sender_id, reciever_id, message, viewed, time_sent, viewed_at')
            ->from(db_prefix() . 'chatclientmessages')
            ->where('viewed', '0')
            ->like('sender_id', 'client_', 'after');

        if ($staffParam !== 'all') {
            $this->db->where('reciever_id', 'staff_' . (int) $staffParam);
        } else {
            $this->db->like('reciever_id', 'staff_', 'after');
        }

        $this->db
            ->order_by('time_sent', 'DESC')
            ->limit($limit);

        $rows = $this->db->get()->result_array();
        $this->json([
            'staff_id' => $staffParam,
            'records' => array_map(fn ($row) => $this->decorateClientChatMessage($row), $rows),
        ]);
    }

    public function chat_conversation($peer = ''): void
    {
        if (! $this->authorize()) {
            return;
        }

        if (! $this->tableExists('chatclientmessages')) {
            $this->json(['records' => [], 'note' => 'Native Perfex chat table is not present.']);
            return;
        }

        $staffId = (int) ($this->input->get('staff_id', true) ?: 39);
        $limit = max(1, min(200, (int) ($this->input->get('limit', true) ?: 50)));
        $peer = trim((string) ($peer ?: $this->input->get('peer', true) ?: $this->input->get('client_ref', true)));
        if ($peer === '') {
            $this->jsonError('missing_required_field', 'peer/client_ref is required, for example client_6.', 422);
            return;
        }
        if (ctype_digit($peer)) {
            $peer = 'client_' . $peer;
        }

        $staffRef = 'staff_' . $staffId;
        $this->db
            ->select('id, sender_id, reciever_id, message, viewed, time_sent, viewed_at')
            ->from(db_prefix() . 'chatclientmessages')
            ->group_start()
                ->group_start()
                    ->where('sender_id', $peer)
                    ->where('reciever_id', $staffRef)
                ->group_end()
                ->or_group_start()
                    ->where('sender_id', $staffRef)
                    ->where('reciever_id', $peer)
                ->group_end()
            ->group_end()
            ->order_by('time_sent', 'DESC')
            ->limit($limit);

        $rows = array_reverse($this->db->get()->result_array());
        $this->json([
            'staff_id' => $staffId,
            'staff_ref' => $staffRef,
            'peer' => $peer,
            'records' => array_map(fn ($row) => $this->decorateClientChatMessage($row), $rows),
        ]);
    }

    public function chat_reply(): void
    {
        if (! $this->authorize()) {
            return;
        }

        if ($this->method() !== 'POST') {
            $this->jsonError('not_found', 'Unsupported chat_reply route', 404);
            return;
        }

        if (! $this->tableExists('chatclientmessages')) {
            $this->jsonError('not_supported', 'Native Perfex chat table is not present.', 501);
            return;
        }

        $data = $this->payload();
        $staffId = (int) ($data['staff_id'] ?? 39);
        $peer = trim((string) ($data['peer'] ?? $data['client_ref'] ?? $data['client_id'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));
        if ($peer === '' || $message === '') {
            $this->jsonError('missing_required_field', 'peer/client_ref and message are required.', 422);
            return;
        }
        if (ctype_digit($peer)) {
            $peer = 'client_' . $peer;
        }
        if (! preg_match('/^client_[0-9]+$/', $peer)) {
            $this->jsonError('invalid_field', 'peer must look like client_123 or be a numeric client/contact id.', 422);
            return;
        }

        $this->db->insert(db_prefix() . 'chatclientmessages', [
            'sender_id' => 'staff_' . $staffId,
            'reciever_id' => $peer,
            'message' => nl2br($message),
            'viewed' => '0',
            'time_sent' => date('Y-m-d H:i:s'),
            'viewed_at' => null,
        ]);

        $this->json([
            'created' => true,
            'message_id' => $this->db->insert_id(),
            'staff_id' => $staffId,
            'peer' => $peer,
        ], 201);
    }

    private function authorize(): bool
    {
        if (get_option('uscap_mcp_bridge_enabled') !== '1') {
            $this->jsonError('permission_denied', 'USCAP MCP Bridge module is disabled.', 403);
            return false;
        }

        $expected = $this->configuredToken();
        if ($expected === '') {
            $this->jsonError('not_configured', 'USCAP MCP Bridge token is not configured in Perfex.', 503);
            return false;
        }

        $provided = (string) ($this->input->get_request_header('X-USCAP-MCP-TOKEN', true)
            ?: $this->input->get_request_header('authtoken', true)
            ?: $this->input->get('token', true));

        if (! hash_equals($expected, $provided)) {
            $this->jsonError('permission_denied', 'Invalid USCAP MCP Bridge token.', 401);
            return false;
        }

        return true;
    }

    private function configuredToken(): string
    {
        if (defined('USCAP_MCP_BRIDGE_TOKEN') && USCAP_MCP_BRIDGE_TOKEN !== '') {
            return (string) USCAP_MCP_BRIDGE_TOKEN;
        }

        return trim((string) get_option('uscap_mcp_bridge_token'));
    }

    private function method(): string
    {
        return strtoupper((string) $this->input->method(true));
    }

    private function payload(): array
    {
        $raw = (string) file_get_contents('php://input');
        $json = json_decode($raw, true);
        if (is_array($json)) {
            return $json;
        }

        $post = $this->input->post(null, true);
        return is_array($post) ? $post : [];
    }

    private function listCustomers(int $limit = 50): array
    {
        $this->db->select('userid, company, phonenumber, website, datecreated, active');
        $this->db->order_by('datecreated', 'DESC');
        $this->db->limit($limit);
        return $this->db->get(db_prefix() . 'clients')->result_array();
    }

    private function getCustomer(int $id): array
    {
        $customer = $this->rowById('clients', 'userid', $id);
        if (! isset($customer['userid'])) {
            return $customer;
        }

        $this->db->select('id, userid, firstname, lastname, email, phonenumber, is_primary, active');
        $this->db->where('userid', $id);
        $this->db->order_by('is_primary', 'DESC');
        $customer['contacts'] = $this->db->get(db_prefix() . 'contacts')->result_array();

        return $customer;
    }

    private function listLeads(int $limit = 50): array
    {
        $this->db->select('id, name, company, email, phonenumber, status, source, dateadded, lastcontact');
        $this->db->order_by('dateadded', 'DESC');
        $this->db->limit($limit);
        return $this->db->get(db_prefix() . 'leads')->result_array();
    }

    private function listRows(string $table, string $idField, array $hidden = [], int $limit = 50): array
    {
        $this->db->limit($limit);
        $this->db->order_by($idField, 'DESC');
        $rows = $this->db->get(db_prefix() . $table)->result_array();
        return array_map(function ($row) use ($hidden) {
            foreach ($hidden as $field) {
                unset($row[$field]);
            }
            return $row;
        }, $rows);
    }

    private function tableExists(string $table): bool
    {
        return $this->db->table_exists(db_prefix() . $table);
    }

    private function decorateClientChatMessage(array $row): array
    {
        $row['direction'] = str_starts_with((string) $row['sender_id'], 'client_') ? 'inbound' : 'outbound';
        $row['plain_message'] = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", (string) $row['message'])));
        if (str_starts_with((string) $row['sender_id'], 'client_')) {
            $row['client_id'] = (int) substr((string) $row['sender_id'], 7);
        } elseif (str_starts_with((string) $row['reciever_id'], 'client_')) {
            $row['client_id'] = (int) substr((string) $row['reciever_id'], 7);
        }
        return $row;
    }

    private function rowById(string $table, string $idField, int $id, array $hidden = []): array
    {
        $this->db->where($idField, $id);
        $row = $this->db->get(db_prefix() . $table)->row_array();
        if (! $row) {
            return ['isError' => true, 'error_type' => 'not_found', 'message' => 'Record not found.'];
        }
        foreach ($hidden as $field) {
            unset($row[$field]);
        }
        return $row;
    }

    private function json(array $payload, int $status = 200): void
    {
        $this->output->set_status_header($status);
        $this->output->set_output(json_encode($payload, JSON_PRETTY_PRINT));
    }

    private function jsonError(string $type, string $message, int $status): void
    {
        $this->json([
            'isError' => true,
            'error_type' => $type,
            'message' => $message,
        ], $status);
    }
}
