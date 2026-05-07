<?php
defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: USCPB Support Routing
Description: Captures public support routing metadata into CRM tickets and exposes an admin-only support routing oversight view.
Version: 1.0.0
Author: USCPB Aurora Fleet
Requires at least: 2.3.*
*/

define('SUPPORT_ROUTING_MODULE_NAME', 'support_routing');
define('SUPPORT_ROUTING_SESSION_KEY', 'support_routing_metadata');
define('SUPPORT_ROUTING_NOTE_MARKER', '[support-routing-metadata]');
define('SUPPORT_ROUTING_NOTE_END_MARKER', '[/support-routing-metadata]');

hooks()->add_action('app_init', 'support_routing_capture_query_params');
hooks()->add_action('clients_init', 'support_routing_capture_query_params');
hooks()->add_action('before_client_open_ticket_form_start', 'support_routing_render_hidden_fields');
hooks()->add_filter('before_ticket_created', 'support_routing_before_ticket_created', 10, 2);
hooks()->add_action('ticket_created', 'support_routing_ticket_created');
hooks()->add_action('admin_init', 'support_routing_admin_menu');

function support_routing_fields()
{
    return [
        'source_site',
        'source_path',
        'intent',
        'category',
        'department',
        'assigned_specialist',
        'specialist_title',
    ];
}

function support_routing_field_aliases()
{
    return [
        'intent'            => ['support_intent', 'entry_type'],
        'category'          => ['support_category', 'specialist_group'],
        'department'        => ['assigned_department'],
        'assigned_specialist'=> ['specialist', 'agent'],
        'specialist_title'  => ['assigned_specialist_title', 'agent_title'],
        'source_site'       => ['source_brand', 'brand'],
        'source_path'       => ['source_url', 'source_page'],
    ];
}

function support_routing_capture_query_params()
{
    $CI = &get_instance();
    $metadata = [];

    foreach (support_routing_fields() as $field) {
        $value = $CI->input->get($field, true);
        if (($value === null || $value === '') && isset(support_routing_field_aliases()[$field])) {
            foreach (support_routing_field_aliases()[$field] as $alias) {
                $value = $CI->input->get($alias, true);
                if ($value !== null && $value !== '') {
                    break;
                }
            }
        }
        if ($value !== null && $value !== '') {
            $metadata[$field] = support_routing_clean_value($value);
        }
    }

    if ($metadata) {
        $existing = $CI->session->userdata(SUPPORT_ROUTING_SESSION_KEY);
        if (!is_array($existing)) {
            $existing = [];
        }

        $CI->session->set_userdata(SUPPORT_ROUTING_SESSION_KEY, array_merge($existing, $metadata));
    }
}

function support_routing_current_metadata()
{
    $CI = &get_instance();
    $metadata = $CI->session->userdata(SUPPORT_ROUTING_SESSION_KEY);
    if (!is_array($metadata)) {
        $metadata = [];
    }

    $posted = $CI->input->post('support_routing', true);
    if (is_array($posted)) {
        foreach (support_routing_fields() as $field) {
            if (isset($posted[$field]) && $posted[$field] !== '') {
                $metadata[$field] = support_routing_clean_value($posted[$field]);
            }
        }
    }

    foreach (support_routing_fields() as $field) {
        if (!isset($metadata[$field])) {
            $metadata[$field] = '';
        }
    }

    return $metadata;
}

function support_routing_clean_value($value)
{
    $value = trim((string) $value);
    $value = strip_tags($value);
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);

    return mb_substr($value, 0, 255);
}

function support_routing_staff_aliases()
{
    return [
        'morpheus'        => ['id' => 50, 'name' => 'Morpheus | CRM Support Lead', 'title' => 'CRM Support Lead'],
        'kai'             => ['id' => 50, 'name' => 'Morpheus | CRM Support Lead', 'title' => 'CRM Support Lead'],
        'kai leung'       => ['id' => 50, 'name' => 'Morpheus | CRM Support Lead', 'title' => 'CRM Support Lead'],
        'sophia'          => ['id' => 42, 'name' => 'Sophia Grant', 'title' => 'Account Services Coordinator'],
        'sophia grant'    => ['id' => 42, 'name' => 'Sophia Grant', 'title' => 'Account Services Coordinator'],
        'julian'          => ['id' => 43, 'name' => 'Julian Carter', 'title' => 'Transaction Support Associate'],
        'julian carter'   => ['id' => 43, 'name' => 'Julian Carter', 'title' => 'Transaction Support Associate'],
        'adrian'          => ['id' => 45, 'name' => 'Adrian Sterling', 'title' => 'Institutional Sales Manager'],
        'sterling'        => ['id' => 45, 'name' => 'Adrian Sterling', 'title' => 'Institutional Sales Manager'],
        'adrian sterling' => ['id' => 45, 'name' => 'Adrian Sterling', 'title' => 'Institutional Sales Manager'],
        'victor'          => ['id' => 46, 'name' => 'Victor Hale', 'title' => 'Closing Specialist'],
        'victor hale'     => ['id' => 46, 'name' => 'Victor Hale', 'title' => 'Closing Specialist'],
        'valor'           => ['id' => 46, 'name' => 'Victor Hale', 'title' => 'Closing Specialist'],
        'miles'           => ['id' => 47, 'name' => 'Miles Carter', 'title' => 'Pipeline Development Coordinator'],
        'miles carter'    => ['id' => 47, 'name' => 'Miles Carter', 'title' => 'Pipeline Development Coordinator'],
        'atlas'           => ['id' => 47, 'name' => 'Miles Carter', 'title' => 'Pipeline Development Coordinator'],
        'vanessa'         => ['id' => 48, 'name' => 'Vanessa Hart', 'title' => 'Marketing Communications Director'],
        'vanessa hart'    => ['id' => 48, 'name' => 'Vanessa Hart', 'title' => 'Marketing Communications Director'],
        'vesper'          => ['id' => 48, 'name' => 'Vanessa Hart', 'title' => 'Marketing Communications Director'],
        'bennett'         => ['id' => 49, 'name' => 'Bennett Cole', 'title' => 'Campaign Strategy Manager'],
        'bennett cole'    => ['id' => 49, 'name' => 'Bennett Cole', 'title' => 'Campaign Strategy Manager'],
        'beacon'          => ['id' => 49, 'name' => 'Bennett Cole', 'title' => 'Campaign Strategy Manager'],
        'cipher'          => ['id' => 0, 'name' => 'Caleb Stone', 'title' => 'Website Support Specialist'],
        'caleb'           => ['id' => 0, 'name' => 'Caleb Stone', 'title' => 'Website Support Specialist'],
        'caleb stone'     => ['id' => 0, 'name' => 'Caleb Stone', 'title' => 'Website Support Specialist'],
        'locke'           => ['id' => 0, 'name' => 'Dorian Locke', 'title' => 'Security Intake Specialist'],
        'dorian'          => ['id' => 0, 'name' => 'Dorian Locke', 'title' => 'Security Intake Specialist'],
        'dorian locke'    => ['id' => 0, 'name' => 'Dorian Locke', 'title' => 'Security Intake Specialist'],
        'hermes'          => ['id' => 0, 'name' => 'Elias Reed', 'title' => 'Social Inbox Director'],
        'mercury'         => ['id' => 0, 'name' => 'Mercury Vale', 'title' => 'Communications Coordinator'],
    ];
}

function support_routing_staff_is_active($staffId)
{
    $staffId = (int) $staffId;
    if ($staffId <= 0) {
        return false;
    }

    $CI = &get_instance();

    return (int) $CI->db->where('staffid', $staffId)->where('active', 1)->count_all_results(db_prefix() . 'staff') > 0;
}

function support_routing_staff_by_name($displayName)
{
    $displayName = trim((string) $displayName);
    if ($displayName === '') {
        return 0;
    }

    $CI = &get_instance();
    $parts = preg_split('/\s+/', $displayName);
    $first = $parts[0] ?? '';
    $last = count($parts) > 1 ? $parts[count($parts) - 1] : '';

    $CI->db->select('staffid');
    $CI->db->from(db_prefix() . 'staff');
    $CI->db->where('active', 1);
    $CI->db->group_start();
    $CI->db->like('CONCAT(firstname, " ", lastname)', $displayName, 'both', false);
    if ($first !== '' && $last !== '') {
        $CI->db->or_group_start();
        $CI->db->like('firstname', $first);
        $CI->db->like('lastname', $last);
        $CI->db->group_end();
    }
    $CI->db->group_end();
    $row = $CI->db->limit(1)->get()->row_array();

    return $row ? (int) $row['staffid'] : 0;
}

function support_routing_resolve_staff($alias)
{
    $alias = strtolower(trim((string) $alias));
    if ($alias === '') {
        return ['staffid' => 0, 'name' => '', 'title' => '', 'available' => false];
    }

    $aliases = support_routing_staff_aliases();
    foreach ($aliases as $needle => $candidate) {
        if (strpos($alias, $needle) !== false) {
            $staffId = (int) $candidate['id'];
            if (!support_routing_staff_is_active($staffId)) {
                $staffId = support_routing_staff_by_name($candidate['name']);
            }

            return [
                'staffid'   => $staffId,
                'name'      => $candidate['name'],
                'title'     => $candidate['title'],
                'available' => $staffId > 0,
            ];
        }
    }

    return ['staffid' => 0, 'name' => '', 'title' => '', 'available' => false];
}

function support_routing_match_staff($primaryAlias, $fallbackAlias = 'morpheus')
{
    $primary = support_routing_resolve_staff($primaryAlias);
    if (!empty($primary['available'])) {
        return $primary;
    }

    $fallback = support_routing_resolve_staff($fallbackAlias);
    $fallback['fallback_from'] = $primaryAlias;

    return $fallback;
}

function support_routing_assignment_decision($metadata = [], $ticket = [])
{
    $metadata = is_array($metadata) ? $metadata : [];
    $ticket = is_array($ticket) ? $ticket : [];

    $pieces = [];
    foreach (support_routing_fields() as $field) {
        $pieces[] = $metadata[$field] ?? '';
    }
    foreach (['subject', 'department_name', 'service_name', 'message', 'body'] as $field) {
        $pieces[] = $ticket[$field] ?? '';
    }
    $haystack = strtolower(trim(implode(' ', array_filter($pieces))));

    $explicit = support_routing_resolve_staff($metadata['assigned_specialist'] ?? '');
    if (!empty($explicit['available'])) {
        return [
            'staffid' => $explicit['staffid'],
            'specialist' => $explicit['name'],
            'specialist_title' => $metadata['specialist_title'] ?: $explicit['title'],
            'route_reason' => 'explicit_specialist_metadata',
            'assignment_status' => 'automatic_assigned',
        ];
    }

    $rules = [
        ['/(fraud|phishing|security|suspicious|hack|recovery|compromis|scam)/', 'locke', 'morpheus', 'security_or_fraud_risk'],
        ['/(deposit|withdraw|withdrawal|transfer|wire|payment|wallet|balance|refund|\blc\b|sblc|\bbg\b|bank guarantee|letter of credit|transaction desk)/', 'julian', 'morpheus', 'transaction_or_payment'],
        ['/(institutional|broker|relationship|sales|pricing|quote|prospect|deal|investor|private placement)/', 'adrian', 'morpheus', 'institutional_sales'],
        ['/(login|password|profile|photo|account access|portal access|sign[ -]?in|sign[ -]?up)/', 'sophia', 'morpheus', 'account_access_profile'],
        ['/(kyc|compliance|onboard|onboarding|verification|verify|document|upload|camera|identity|principal|beneficial owner)/', 'morpheus', 'morpheus', 'kyc_onboarding_documents'],
        ['/(trading|market|portfolio|copy trading|paper trading|live trading|order|brokerage|suitability)/', 'cipher', 'morpheus', 'trading_platform'],
        ['/(swift| mt | mx | iso20022|iso 20022|rwa|pof|bcl|gpi|uetr)/', 'morpheus', 'morpheus', 'swift_operations'],
        ['/(website|bug|broken|page|link|performance|error|not working|404|500|upload flow)/', 'cipher', 'morpheus', 'website_or_upload_issue'],
        ['/(follow[ -]?up|pipeline|cadence|reactivat|list hygiene)/', 'miles', 'morpheus', 'pipeline_follow_up'],
        ['/(objection|closing|close|hesitat|stall)/', 'victor', 'morpheus', 'closing_or_objection'],
        ['/(campaign|ad|advertis|marketing|press|copy|cta|facebook|instagram|linkedin)/', 'vanessa', 'morpheus', 'marketing_communications'],
        ['/(whatsapp|telegram|teams|wechat|messenger|social inbox|dm|direct message)/', 'hermes', 'morpheus', 'social_inbox'],
    ];

    foreach ($rules as $rule) {
        if ($haystack !== '' && preg_match($rule[0], $haystack)) {
            $staff = support_routing_match_staff($rule[1], $rule[2]);

            return [
                'staffid' => (int) $staff['staffid'],
                'specialist' => $staff['name'],
                'specialist_title' => $staff['title'],
                'route_reason' => $rule[3] . (!empty($staff['fallback_from']) ? '_fallback_to_morpheus' : ''),
                'assignment_status' => (int) $staff['staffid'] > 0 ? 'automatic_assigned' : 'manual_review_no_available_specialist',
            ];
        }
    }

    if (strpos($haystack, 'trading.uscpb.net') !== false) {
        $staff = support_routing_match_staff('cipher', 'morpheus');
    } else {
        $staff = support_routing_match_staff('morpheus', 'morpheus');
    }

    return [
        'staffid' => (int) $staff['staffid'],
        'specialist' => $staff['name'],
        'specialist_title' => $staff['title'],
        'route_reason' => 'fallback_general_support' . (!empty($staff['fallback_from']) ? '_fallback_to_morpheus' : ''),
        'assignment_status' => (int) $staff['staffid'] > 0 ? 'automatic_assigned' : 'manual_review_no_available_specialist',
    ];
}

function support_routing_render_hidden_fields()
{
    $metadata = support_routing_current_metadata();
    foreach (support_routing_fields() as $field) {
        if ($metadata[$field] !== '') {
            echo form_hidden('support_routing[' . $field . ']', $metadata[$field]);
        }
    }
}

function support_routing_before_ticket_created($data, $admin)
{
    if ($admin) {
        return $data;
    }

    $metadata = support_routing_current_metadata();
    $decision = support_routing_assignment_decision($metadata, $data);
    $staffId = (int) $decision['staffid'];
    if ($staffId > 0 && (!isset($data['assigned']) || (int) $data['assigned'] === 0)) {
        $data['assigned'] = $staffId;
    }

    return $data;
}

function support_routing_ticket_created($ticketId)
{
    $CI = &get_instance();
    $metadata = support_routing_current_metadata();

    $ticketId = (int) $ticketId;
    $ticket = $CI->db
        ->select('t.ticketid,t.subject,t.assigned,d.name as department_name')
        ->from(db_prefix() . 'tickets t')
        ->join(db_prefix() . 'departments d', 'd.departmentid=t.department', 'left')
        ->where('t.ticketid', $ticketId)
        ->get()
        ->row_array();
    if (!$ticket) {
        return;
    }

    $decision = support_routing_assignment_decision($metadata, $ticket);
    $staffId = (int) $decision['staffid'];
    if ($staffId > 0 && (int) $ticket['assigned'] === 0) {
        $CI->db->where('ticketid', $ticketId)->update(db_prefix() . 'tickets', ['assigned' => $staffId]);
        $ticket['assigned'] = $staffId;
    }

    $payload = $metadata;
    $payload['assigned_specialist'] = $payload['assigned_specialist'] ?: $decision['specialist'];
    $payload['specialist_title'] = $payload['specialist_title'] ?: $decision['specialist_title'];
    $payload['assignment_status'] = ((int) $ticket['assigned'] === $staffId && $staffId > 0) ? $decision['assignment_status'] : 'manual_review';
    $payload['assigned_staffid'] = $staffId > 0 ? $staffId : '';
    $payload['route_reason'] = $decision['route_reason'];
    $payload['captured_at'] = date('Y-m-d H:i:s');

    support_routing_insert_ticket_note($ticketId, $payload, 'Support routing metadata captured and routed in real time.');

    $CI->session->unset_userdata(SUPPORT_ROUTING_SESSION_KEY);
}

function support_routing_ticket_has_note($ticketId)
{
    $CI = &get_instance();

    return (int) $CI->db
        ->where('rel_id', (int) $ticketId)
        ->where('rel_type', 'ticket')
        ->like('description', SUPPORT_ROUTING_NOTE_MARKER)
        ->count_all_results(db_prefix() . 'notes') > 0;
}

function support_routing_insert_ticket_note($ticketId, $payload, $summary)
{
    if (support_routing_ticket_has_note($ticketId)) {
        return;
    }

    $CI = &get_instance();
    $description = SUPPORT_ROUTING_NOTE_MARKER . "\n"
        . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        . "\n" . SUPPORT_ROUTING_NOTE_END_MARKER . "\n\n"
        . $summary . ' Use this for internal routing/admin visibility only.';

    $CI->db->insert(db_prefix() . 'notes', [
        'rel_id'         => (int) $ticketId,
        'rel_type'       => 'ticket',
        'description'    => $description,
        'date_contacted' => null,
        'addedfrom'      => get_staff_user_id() ?: 0,
        'dateadded'      => date('Y-m-d H:i:s'),
    ]);
}

function support_routing_route_unassigned_tickets($limit = 50)
{
    $CI = &get_instance();
    $limit = max(1, min(200, (int) $limit));

    $CI->db->select('t.ticketid,t.subject,t.userid,t.contactid,t.department,t.status,t.assigned,t.date,t.lastreply,d.name as department_name,ts.name as status_name');
    $CI->db->from(db_prefix() . 'tickets t');
    $CI->db->join(db_prefix() . 'departments d', 'd.departmentid=t.department', 'left');
    $CI->db->join(db_prefix() . 'tickets_status ts', 'ts.ticketstatusid=t.status', 'left');
    $CI->db->where('(t.assigned IS NULL OR t.assigned=0)', null, false);
    $CI->db->where('LOWER(COALESCE(ts.name,"")) NOT LIKE', '%closed%');
    $CI->db->where('LOWER(COALESCE(ts.name,"")) NOT LIKE', '%answered%');
    $CI->db->where('LOWER(COALESCE(ts.name,"")) NOT LIKE', '%staff initiated%');
    $CI->db->order_by('COALESCE(t.lastreply,t.date)', 'ASC', false);
    $rows = $CI->db->limit($limit)->get()->result_array();

    $result = [
        'checked' => count($rows),
        'routed' => 0,
        'manual_review' => 0,
        'items' => [],
    ];

    foreach ($rows as $ticket) {
        $decision = support_routing_assignment_decision([], $ticket);
        $staffId = (int) $decision['staffid'];
        $item = [
            'ticketid' => (int) $ticket['ticketid'],
            'subject' => $ticket['subject'],
            'status' => $ticket['status_name'],
            'route_reason' => $decision['route_reason'],
            'assigned_staffid' => $staffId,
            'assigned_specialist' => $decision['specialist'],
        ];

        if ($staffId > 0) {
            $CI->db->where('ticketid', (int) $ticket['ticketid'])->update(db_prefix() . 'tickets', ['assigned' => $staffId]);
            $payload = [];
            foreach (support_routing_fields() as $field) {
                $payload[$field] = '';
            }
            $payload['assigned_specialist'] = $decision['specialist'];
            $payload['specialist_title'] = $decision['specialist_title'];
            $payload['assignment_status'] = $decision['assignment_status'];
            $payload['assigned_staffid'] = $staffId;
            $payload['route_reason'] = $decision['route_reason'];
            $payload['captured_at'] = date('Y-m-d H:i:s');
            $payload['capture_status'] = 'routed_without_source_metadata';
            support_routing_insert_ticket_note((int) $ticket['ticketid'], $payload, 'Support ticket auto-routed from the real-time Support Routing admin queue.');
            $result['routed']++;
            $item['action'] = 'routed';
        } else {
            $result['manual_review']++;
            $item['action'] = 'manual_review';
        }

        $result['items'][] = $item;
    }

    return $result;
}

function support_routing_has_metadata($metadata)
{
    foreach (support_routing_fields() as $field) {
        if (isset($metadata[$field]) && $metadata[$field] !== '') {
            return true;
        }
    }

    return false;
}

function support_routing_specialist_staff_id($specialist)
{
    $staff = support_routing_resolve_staff($specialist);

    return (int) $staff['staffid'];
}

function support_routing_admin_menu()
{
    if (!is_admin()) {
        return;
    }

    $CI = &get_instance();
    $CI->app_menu->add_sidebar_menu_item('support-routing-oversight', [
        'name'     => 'Support Routing',
        'href'     => admin_url('support_routing'),
        'icon'     => 'fa fa-route',
        'position' => 7,
    ]);
}
