<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: CRM Company Context
Description: Adds multi-company/brand context, group selection, agent lane mapping, and brand-aware support routing metadata for the shared CRM.
Version: 1.0.0
Author: USCPB Aurora Fleet
Requires at least: 2.3.*
*/

define('COMPANY_CONTEXT_MODULE_NAME', 'company_context');
define('COMPANY_CONTEXT_SESSION_KEY', 'company_context_current_company_id');
define('COMPANY_CONTEXT_CAPTURE_SESSION_KEY', 'company_context_capture_metadata');
define('COMPANY_CONTEXT_NOTE_MARKER', '[company-context-metadata]');
define('COMPANY_CONTEXT_NOTE_END_MARKER', '[/company-context-metadata]');

register_activation_hook(COMPANY_CONTEXT_MODULE_NAME, 'company_context_activation_hook');
register_language_files(COMPANY_CONTEXT_MODULE_NAME, [COMPANY_CONTEXT_MODULE_NAME]);

hooks()->add_action('app_init', 'company_context_capture_query_params');
hooks()->add_action('clients_init', 'company_context_capture_query_params');
hooks()->add_action('admin_init', 'company_context_admin_menu');
hooks()->add_action('admin_navbar_start', 'company_context_render_admin_selector');
hooks()->add_action('app_admin_head', 'company_context_render_admin_assets');
hooks()->add_action('before_client_open_ticket_form_start', 'company_context_render_hidden_fields');
hooks()->add_action('ticket_created', 'company_context_ticket_created');

function company_context_activation_hook()
{
    $CI = &get_instance();
    require_once(__DIR__ . '/install.php');
}

function company_context_admin_menu()
{
    if (!is_staff_logged_in()) {
        return;
    }

    $CI = &get_instance();

    $CI->app_menu->add_sidebar_menu_item('company-context', [
        'name'     => _l('company_context_menu'),
        'href'     => admin_url('company_context'),
        'icon'     => 'fa fa-sitemap',
        'position' => 4,
    ]);
}

function company_context_fields()
{
    return [
        'company_id',
        'source_company',
        'source_brand',
        'source_site',
        'source_path',
        'entry_type',
        'intent',
        'department',
        'assigned_specialist',
        'specialist_title',
    ];
}

function company_context_field_aliases()
{
    return [
        'source_company'      => ['company', 'company_slug', 'brand', 'source_brand'],
        'source_brand'        => ['brand', 'source_company'],
        'source_site'         => ['site', 'domain'],
        'source_path'         => ['source_url', 'source_page', 'path'],
        'entry_type'          => ['channel', 'support_entry_type'],
        'intent'              => ['support_intent'],
        'department'          => ['assigned_department'],
        'assigned_specialist' => ['agent', 'specialist'],
        'specialist_title'    => ['agent_title', 'assigned_specialist_title'],
    ];
}

function company_context_capture_query_params()
{
    static $captured = false;
    if ($captured) {
        return;
    }
    $captured = true;

    $CI = &get_instance();
    $metadata = [];

    foreach (company_context_fields() as $field) {
        $value = $CI->input->get($field, true);
        if (($value === null || $value === '') && isset(company_context_field_aliases()[$field])) {
            foreach (company_context_field_aliases()[$field] as $alias) {
                $value = $CI->input->get($alias, true);
                if ($value !== null && $value !== '') {
                    break;
                }
            }
        }
        if ($value !== null && $value !== '') {
            $metadata[$field] = company_context_clean_value($value);
        }
    }

    if (!$metadata) {
        return;
    }

    $CI->load->model('company_context/company_context_model');
    $company = $CI->company_context_model->resolve_company_from_metadata($metadata);
    if ($company) {
        $metadata['company_id'] = (string) $company['id'];
        $metadata['source_company'] = $company['slug'];
        $metadata['source_brand'] = $company['name'];
    }

    $existing = $CI->session->userdata(COMPANY_CONTEXT_CAPTURE_SESSION_KEY);
    if (!is_array($existing)) {
        $existing = [];
    }

    $CI->session->set_userdata(COMPANY_CONTEXT_CAPTURE_SESSION_KEY, array_merge($existing, $metadata));
}

function company_context_current_metadata()
{
    $CI = &get_instance();
    $metadata = $CI->session->userdata(COMPANY_CONTEXT_CAPTURE_SESSION_KEY);
    if (!is_array($metadata)) {
        $metadata = [];
    }

    $posted = $CI->input->post('company_context', true);
    if (is_array($posted)) {
        foreach (company_context_fields() as $field) {
            if (isset($posted[$field]) && $posted[$field] !== '') {
                $metadata[$field] = company_context_clean_value($posted[$field]);
            }
        }
    }

    foreach (company_context_fields() as $field) {
        if (!isset($metadata[$field])) {
            $metadata[$field] = '';
        }
    }

    return $metadata;
}

function company_context_clean_value($value)
{
    $value = trim((string) $value);
    $value = strip_tags($value);
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);

    return mb_substr($value, 0, 255);
}

function company_context_render_hidden_fields()
{
    $metadata = company_context_current_metadata();
    foreach (company_context_fields() as $field) {
        if ($metadata[$field] !== '') {
            echo form_hidden('company_context[' . $field . ']', $metadata[$field]);
        }
    }
}

function company_context_render_admin_selector()
{
    if (!is_staff_logged_in()) {
        return;
    }

    $CI = &get_instance();
    $CI->load->model('company_context/company_context_model');

    $companies = $CI->company_context_model->get_accessible_companies(get_staff_user_id(), is_admin());
    if (!$companies) {
        return;
    }

    $currentId = (int) $CI->session->userdata(COMPANY_CONTEXT_SESSION_KEY);
    $csrfName = $CI->security->get_csrf_token_name();
    $csrfHash = $CI->security->get_csrf_hash();

    echo '<li class="company-context-nav">';
    echo '<form action="' . admin_url('company_context/select') . '" method="post" class="company-context-selector-form">';
    echo '<input type="hidden" name="' . e($csrfName) . '" value="' . e($csrfHash) . '">';
    echo '<select name="company_id" class="form-control company-context-selector" onchange="this.form.submit()" aria-label="' . e(_l('company_context_selector_label')) . '">';
    if (is_admin()) {
        echo '<option value="0"' . ($currentId === 0 ? ' selected' : '') . '>' . e(_l('company_context_all_companies')) . '</option>';
    }
    foreach ($companies as $company) {
        echo '<option value="' . (int) $company['id'] . '"' . ($currentId === (int) $company['id'] ? ' selected' : '') . '>';
        echo e($company['name']);
        echo '</option>';
    }
    echo '</select>';
    echo '</form>';
    echo '</li>';
}

function company_context_render_admin_assets()
{
    if (!is_staff_logged_in()) {
        return;
    }

    $CI = &get_instance();
    $CI->load->model('company_context/company_context_model');
    $company = $CI->company_context_model->get_current_company();

    echo '<link rel="stylesheet" type="text/css" href="' . module_dir_url(COMPANY_CONTEXT_MODULE_NAME, 'assets/css/company_context.css') . '">';

    if (!$company) {
        return;
    }

    $primary = company_context_safe_color(isset($company['primary_color']) ? $company['primary_color'] : '#1d4f8f', '#1d4f8f');
    $secondary = company_context_safe_color(isset($company['secondary_color']) ? $company['secondary_color'] : '#2563eb', '#2563eb');
    $accent = company_context_safe_color(isset($company['accent_color']) ? $company['accent_color'] : '#10b981', '#10b981');

    echo '<style id="company-context-brand-vars">';
    echo ':root{--company-context-primary:' . e($primary) . ';--company-context-secondary:' . e($secondary) . ';--company-context-accent:' . e($accent) . ';}';
    echo '.company-context-active-brand{background:linear-gradient(135deg,' . e($primary) . ',' . e($secondary) . ');}';
    echo '</style>';
}

function company_context_safe_color($color, $fallback)
{
    $color = trim((string) $color);
    if (preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
        return $color;
    }

    return $fallback;
}

function company_context_ticket_created($ticketId)
{
    $CI = &get_instance();
    $ticketId = (int) $ticketId;
    if ($ticketId <= 0) {
        return;
    }

    $metadata = company_context_current_metadata();
    $CI->load->model('company_context/company_context_model');
    $company = $CI->company_context_model->resolve_company_from_metadata($metadata);

    if (!$company && !empty($metadata['company_id'])) {
        $company = $CI->company_context_model->get_company((int) $metadata['company_id']);
    }

    if (!$company) {
        return;
    }

    $metadata['company_id'] = (string) $company['id'];
    $metadata['source_company'] = $company['slug'];
    $metadata['source_brand'] = $company['name'];
    $metadata['captured_at'] = date('Y-m-d H:i:s');

    $CI->company_context_model->upsert_record_map((int) $company['id'], 'ticket', $ticketId, [
        'source_site' => $metadata['source_site'] ?? '',
        'source_path' => $metadata['source_path'] ?? '',
        'origin'      => $metadata['entry_type'] ?: 'support_ticket',
    ]);

    company_context_insert_ticket_note($ticketId, $metadata, 'Company/brand context captured for shared CRM routing and admin filtering.');
    $CI->session->unset_userdata(COMPANY_CONTEXT_CAPTURE_SESSION_KEY);
}

function company_context_insert_ticket_note($ticketId, $payload, $summary)
{
    $CI = &get_instance();

    $exists = $CI->db
        ->where('rel_id', (int) $ticketId)
        ->where('rel_type', 'ticket')
        ->like('description', COMPANY_CONTEXT_NOTE_MARKER)
        ->count_all_results(db_prefix() . 'notes') > 0;
    if ($exists) {
        return;
    }

    $description = COMPANY_CONTEXT_NOTE_MARKER . "\n"
        . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        . "\n" . COMPANY_CONTEXT_NOTE_END_MARKER . "\n\n"
        . $summary . ' Internal metadata only.';

    $CI->db->insert(db_prefix() . 'notes', [
        'rel_id'         => (int) $ticketId,
        'rel_type'       => 'ticket',
        'description'    => $description,
        'date_contacted' => null,
        'addedfrom'      => get_staff_user_id() ?: 0,
        'dateadded'      => date('Y-m-d H:i:s'),
    ]);
}
