<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Chat_history extends AdminController
{
    private $clientMessageLimit = 1000;
    private $conversationLimit = 250;

    public function __construct()
    {
        parent::__construct();

        if (!is_admin()) {
            access_denied('Chat History');
        }
    }

    public function index()
    {
        close_setup_menu();

        $tab = $this->input->get('tab');
        $tab = in_array($tab, ['clients', 'staff'], true) ? $tab : 'clients';

        $search = trim((string) $this->input->get('search', true));
        $clientKey = $this->normalizeClientKey($this->input->get('client', true));
        $staffId = $this->normalizeStaffId($this->input->get('staff', true));

        $data = [
            'title' => 'Chat History',
            'tab' => $tab,
            'search' => $search,
            'client_key' => $clientKey,
            'staff_id' => $staffId,
            'client_conversations' => $this->getClientConversations($search),
            'staff_conversations' => $this->getStaffConversations($search),
            'client_messages' => $clientKey ? $this->getClientMessages($clientKey) : [],
            'staff_messages' => $staffId ? $this->getStaffMessages($staffId) : [],
            'selected_client' => $clientKey ? $this->getClientLabel($clientKey) : null,
            'selected_staff' => $staffId ? $this->getStaffLabel($staffId) : null,
            'client_table_exists' => $this->db->table_exists(db_prefix() . 'chatclientmessages'),
            'staff_table_exists' => $this->db->table_exists(db_prefix() . 'chatmessages'),
        ];

        $this->load->view('admin/chat_history/index', $data);
    }

    private function getClientConversations($search)
    {
        $table = db_prefix() . 'chatclientmessages';

        if (!$this->db->table_exists($table)) {
            return [];
        }

        $contacts = db_prefix() . 'contacts';
        $clients = db_prefix() . 'clients';
        $params = [];
        $where = '';

        if ($search !== '') {
            $like = '%' . $this->db->escape_like_str($search) . '%';
            $where = "WHERE (
                x.client_key LIKE ? OR
                ct.firstname LIKE ? OR
                ct.lastname LIKE ? OR
                ct.email LIKE ? OR
                c.company LIKE ?
            )";
            $params = [$like, $like, $like, $like, $like];
        }

        $sql = "
            SELECT
                x.client_key,
                x.last_time,
                x.message_count,
                x.staff_count,
                ct.id AS contact_id,
                ct.userid,
                ct.firstname,
                ct.lastname,
                ct.email,
                c.company
            FROM (
                SELECT
                    CASE
                        WHEN sender_id LIKE 'client\\_%' THEN sender_id
                        ELSE reciever_id
                    END AS client_key,
                    MAX(time_sent) AS last_time,
                    COUNT(*) AS message_count,
                    COUNT(DISTINCT CASE
                        WHEN sender_id LIKE 'staff\\_%' THEN sender_id
                        WHEN reciever_id LIKE 'staff\\_%' THEN reciever_id
                        ELSE NULL
                    END) AS staff_count
                FROM {$table}
                WHERE sender_id LIKE 'client\\_%' OR reciever_id LIKE 'client\\_%'
                GROUP BY client_key
            ) x
            LEFT JOIN {$contacts} ct ON ct.id = CAST(SUBSTRING(x.client_key, 8) AS UNSIGNED)
            LEFT JOIN {$clients} c ON c.userid = ct.userid
            {$where}
            ORDER BY x.last_time DESC
            LIMIT {$this->conversationLimit}
        ";

        return $this->db->query($sql, $params)->result_array();
    }

    private function getStaffConversations($search)
    {
        $table = db_prefix() . 'chatmessages';

        if (!$this->db->table_exists($table)) {
            return [];
        }

        $staff = db_prefix() . 'staff';
        $params = [];
        $where = '';

        if ($search !== '') {
            $like = '%' . $this->db->escape_like_str($search) . '%';
            $where = 'WHERE s.firstname LIKE ? OR s.lastname LIKE ? OR s.email LIKE ?';
            $params = [$like, $like, $like];
        }

        $sql = "
            SELECT
                s.staffid,
                s.firstname,
                s.lastname,
                s.email,
                s.profile_image,
                COUNT(m.id) AS message_count,
                MAX(m.time_sent) AS last_time
            FROM {$staff} s
            INNER JOIN {$table} m ON m.sender_id = s.staffid OR m.reciever_id = s.staffid
            {$where}
            GROUP BY s.staffid, s.firstname, s.lastname, s.email, s.profile_image
            ORDER BY last_time DESC
            LIMIT {$this->conversationLimit}
        ";

        return $this->db->query($sql, $params)->result_array();
    }

    private function getClientMessages($clientKey)
    {
        $table = db_prefix() . 'chatclientmessages';

        if (!$this->db->table_exists($table)) {
            return [];
        }

        $rows = $this->db
            ->where('sender_id', $clientKey)
            ->or_where('reciever_id', $clientKey)
            ->order_by('time_sent', 'ASC')
            ->limit($this->clientMessageLimit)
            ->get($table)
            ->result_array();

        return $this->prepareMessages($rows);
    }

    private function getStaffMessages($staffId)
    {
        $table = db_prefix() . 'chatmessages';

        if (!$this->db->table_exists($table)) {
            return [];
        }

        $rows = $this->db
            ->where('sender_id', $staffId)
            ->or_where('reciever_id', $staffId)
            ->order_by('time_sent', 'ASC')
            ->limit($this->clientMessageLimit)
            ->get($table)
            ->result_array();

        return $this->prepareMessages($rows);
    }

    private function prepareMessages($rows)
    {
        foreach ($rows as &$row) {
            $row['sender_label'] = $this->participantLabel($row['sender_id']);
            $row['reciever_label'] = $this->participantLabel($row['reciever_id']);
            $row['clean_message'] = $this->cleanMessage($row['message']);
            $row['time_sent_formatted'] = _dt($row['time_sent']);
        }

        return $rows;
    }

    private function participantLabel($participant)
    {
        $participant = (string) $participant;

        if (strpos($participant, 'client_') === 0) {
            return $this->getClientLabel($participant);
        }

        if (strpos($participant, 'staff_') === 0) {
            return $this->getStaffLabel((int) str_replace('staff_', '', $participant));
        }

        if (is_numeric($participant)) {
            return $this->getStaffLabel((int) $participant);
        }

        return $participant;
    }

    private function getClientLabel($clientKey)
    {
        $contactId = (int) str_replace('client_', '', $clientKey);

        if ($contactId <= 0) {
            return $clientKey;
        }

        $contact = $this->db
            ->select('id, userid, firstname, lastname, email')
            ->where('id', $contactId)
            ->get(db_prefix() . 'contacts')
            ->row_array();

        if (!$contact) {
            return 'Client #' . $contactId;
        }

        $company = '';
        if (!empty($contact['userid'])) {
            $client = $this->db
                ->select('company')
                ->where('userid', (int) $contact['userid'])
                ->get(db_prefix() . 'clients')
                ->row_array();
            $company = $client && !empty($client['company']) ? ' - ' . $client['company'] : '';
        }

        $name = trim($contact['firstname'] . ' ' . $contact['lastname']);
        $name = $name !== '' ? $name : 'Client #' . $contactId;
        $email = !empty($contact['email']) ? ' <' . $contact['email'] . '>' : '';

        return $name . $email . $company;
    }

    private function getStaffLabel($staffId)
    {
        $staffId = (int) $staffId;

        if ($staffId <= 0) {
            return 'Staff #' . $staffId;
        }

        $staff = $this->db
            ->select('staffid, firstname, lastname, email')
            ->where('staffid', $staffId)
            ->get(db_prefix() . 'staff')
            ->row_array();

        if (!$staff) {
            return 'Staff #' . $staffId;
        }

        $name = trim($staff['firstname'] . ' ' . $staff['lastname']);
        $email = !empty($staff['email']) ? ' <' . $staff['email'] . '>' : '';

        return ($name !== '' ? $name : 'Staff #' . $staffId) . $email;
    }

    private function cleanMessage($message)
    {
        $message = html_entity_decode((string) $message, ENT_QUOTES, 'UTF-8');
        $message = strip_tags($message);
        $message = preg_replace('/\s+/', ' ', $message);

        return trim($message);
    }

    private function normalizeClientKey($clientKey)
    {
        $clientKey = trim((string) $clientKey);

        return preg_match('/^client_\d+$/', $clientKey) ? $clientKey : '';
    }

    private function normalizeStaffId($staffId)
    {
        $staffId = (int) $staffId;

        return $staffId > 0 ? $staffId : 0;
    }
}
