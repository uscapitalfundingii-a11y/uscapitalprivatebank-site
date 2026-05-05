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

function support_routing_capture_query_params()
{
    $CI = &get_instance();
    $metadata = [];

    foreach (support_routing_fields() as $field) {
        $value = $CI->input->get($field, true);
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
    $metadata = support_routing_current_metadata();
    if (!support_routing_has_metadata($metadata)) {
        return $data;
    }

    $staffId = support_routing_specialist_staff_id($metadata['assigned_specialist']);
    if ($staffId > 0 && (!isset($data['assigned']) || (int) $data['assigned'] === 0)) {
        $data['assigned'] = $staffId;
    }

    return $data;
}

function support_routing_ticket_created($ticketId)
{
    $CI = &get_instance();
    $metadata = support_routing_current_metadata();
    if (!support_routing_has_metadata($metadata)) {
        return;
    }

    $ticketId = (int) $ticketId;
    $ticket = $CI->db->select('ticketid,assigned')->where('ticketid', $ticketId)->get(db_prefix() . 'tickets')->row_array();
    if (!$ticket) {
        return;
    }

    $staffId = support_routing_specialist_staff_id($metadata['assigned_specialist']);
    $assignmentStatus = 'manual_review';
    if ($staffId > 0 && (int) $ticket['assigned'] === $staffId) {
        $assignmentStatus = 'automatic_assigned';
    } elseif ($metadata['assigned_specialist'] === '') {
        $assignmentStatus = 'manual_review_no_specialist';
    } elseif ($staffId === 0) {
        $assignmentStatus = 'manual_review_specialist_not_mapped';
    }

    $payload = $metadata;
    $payload['assignment_status'] = $assignmentStatus;
    $payload['assigned_staffid'] = $staffId > 0 ? $staffId : '';
    $payload['captured_at'] = date('Y-m-d H:i:s');

    $description = SUPPORT_ROUTING_NOTE_MARKER . "\n"
        . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        . "\n" . SUPPORT_ROUTING_NOTE_END_MARKER . "\n\n"
        . 'Support routing metadata captured from the public support entry point. '
        . 'Use this for internal routing/admin visibility only.';

    $CI->db->insert(db_prefix() . 'notes', [
        'rel_id'         => $ticketId,
        'rel_type'       => 'ticket',
        'description'    => $description,
        'date_contacted' => null,
        'addedfrom'      => 0,
        'dateadded'      => date('Y-m-d H:i:s'),
    ]);

    $CI->session->unset_userdata(SUPPORT_ROUTING_SESSION_KEY);
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
    $specialist = strtolower(trim((string) $specialist));
    if ($specialist === '') {
        return 0;
    }

    $map = [
        'morpheus'       => 50,
        'sophia grant'   => 42,
        'sophia'         => 42,
        'julian carter'  => 43,
        'julian'         => 43,
        'adrian sterling'=> 45,
        'adrian'         => 45,
        'sterling'       => 45,
    ];

    foreach ($map as $needle => $staffId) {
        if (strpos($specialist, $needle) !== false) {
            return $staffId;
        }
    }

    return 0;
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
