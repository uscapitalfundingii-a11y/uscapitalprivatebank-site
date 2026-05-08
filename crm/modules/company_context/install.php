<?php

defined('BASEPATH') or exit('No direct script access allowed');

$companiesTable = db_prefix() . 'company_context_companies';
$staffTable = db_prefix() . 'company_context_staff';
$mapTable = db_prefix() . 'company_context_record_map';

if (!$CI->db->table_exists($companiesTable)) {
    $CI->db->query('CREATE TABLE `' . $companiesTable . "` (
        `id` int(11) NOT NULL,
        `name` varchar(191) NOT NULL,
        `slug` varchar(120) NOT NULL,
        `public_label` varchar(191) DEFAULT NULL,
        `primary_domain` varchar(191) DEFAULT NULL,
        `domain_aliases` text DEFAULT NULL,
        `support_email` varchar(191) DEFAULT NULL,
        `default_from_email` varchar(191) DEFAULT NULL,
        `reply_to_email` varchar(191) DEFAULT NULL,
        `bounce_email` varchar(191) DEFAULT NULL,
        `allowed_sender_domains` text DEFAULT NULL,
        `mailbox_owner_staffid` int(11) DEFAULT NULL,
        `support_url` varchar(255) DEFAULT NULL,
        `primary_color` varchar(20) NOT NULL DEFAULT '#1d4f8f',
        `secondary_color` varchar(20) NOT NULL DEFAULT '#2563eb',
        `accent_color` varchar(20) NOT NULL DEFAULT '#10b981',
        `logo_url` varchar(255) DEFAULT NULL,
        `default_department_id` int(11) DEFAULT NULL,
        `default_staffid` int(11) DEFAULT NULL,
        `active` tinyint(1) NOT NULL DEFAULT 1,
        `sort_order` int(11) NOT NULL DEFAULT 100,
        `created_at` datetime NOT NULL,
        `updated_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . $companiesTable . '` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `slug` (`slug`), ADD KEY `active` (`active`), ADD KEY `primary_domain` (`primary_domain`);');
    $CI->db->query('ALTER TABLE `' . $companiesTable . '` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

if ($CI->db->table_exists($companiesTable) && !$CI->db->field_exists('domain_aliases', $companiesTable)) {
    $CI->db->query('ALTER TABLE `' . $companiesTable . '` ADD `domain_aliases` text DEFAULT NULL AFTER `primary_domain`;');
}
if ($CI->db->table_exists($companiesTable) && !$CI->db->field_exists('support_email', $companiesTable)) {
    $CI->db->query('ALTER TABLE `' . $companiesTable . '` ADD `support_email` varchar(191) DEFAULT NULL AFTER `domain_aliases`;');
}
if ($CI->db->table_exists($companiesTable) && !$CI->db->field_exists('default_from_email', $companiesTable)) {
    $CI->db->query('ALTER TABLE `' . $companiesTable . '` ADD `default_from_email` varchar(191) DEFAULT NULL AFTER `support_email`;');
}
if ($CI->db->table_exists($companiesTable) && !$CI->db->field_exists('reply_to_email', $companiesTable)) {
    $CI->db->query('ALTER TABLE `' . $companiesTable . '` ADD `reply_to_email` varchar(191) DEFAULT NULL AFTER `default_from_email`;');
}
if ($CI->db->table_exists($companiesTable) && !$CI->db->field_exists('bounce_email', $companiesTable)) {
    $CI->db->query('ALTER TABLE `' . $companiesTable . '` ADD `bounce_email` varchar(191) DEFAULT NULL AFTER `reply_to_email`;');
}
if ($CI->db->table_exists($companiesTable) && !$CI->db->field_exists('allowed_sender_domains', $companiesTable)) {
    $CI->db->query('ALTER TABLE `' . $companiesTable . '` ADD `allowed_sender_domains` text DEFAULT NULL AFTER `bounce_email`;');
}
if ($CI->db->table_exists($companiesTable) && !$CI->db->field_exists('mailbox_owner_staffid', $companiesTable)) {
    $CI->db->query('ALTER TABLE `' . $companiesTable . '` ADD `mailbox_owner_staffid` int(11) DEFAULT NULL AFTER `allowed_sender_domains`;');
}
if ($CI->db->table_exists($companiesTable) && !$CI->db->field_exists('public_label', $companiesTable)) {
    $CI->db->query('ALTER TABLE `' . $companiesTable . '` ADD `public_label` varchar(191) DEFAULT NULL AFTER `slug`;');
}
if ($CI->db->table_exists($companiesTable) && !$CI->db->field_exists('support_url', $companiesTable)) {
    $CI->db->query('ALTER TABLE `' . $companiesTable . '` ADD `support_url` varchar(255) DEFAULT NULL AFTER `mailbox_owner_staffid`;');
}
if ($CI->db->table_exists($companiesTable) && !$CI->db->field_exists('primary_color', $companiesTable)) {
    $CI->db->query("ALTER TABLE `" . $companiesTable . "` ADD `primary_color` varchar(20) NOT NULL DEFAULT '#1d4f8f' AFTER `support_url`;");
}
if ($CI->db->table_exists($companiesTable) && !$CI->db->field_exists('secondary_color', $companiesTable)) {
    $CI->db->query("ALTER TABLE `" . $companiesTable . "` ADD `secondary_color` varchar(20) NOT NULL DEFAULT '#2563eb' AFTER `primary_color`;");
}
if ($CI->db->table_exists($companiesTable) && !$CI->db->field_exists('accent_color', $companiesTable)) {
    $CI->db->query("ALTER TABLE `" . $companiesTable . "` ADD `accent_color` varchar(20) NOT NULL DEFAULT '#10b981' AFTER `secondary_color`;");
}
if ($CI->db->table_exists($companiesTable) && !$CI->db->field_exists('logo_url', $companiesTable)) {
    $CI->db->query('ALTER TABLE `' . $companiesTable . '` ADD `logo_url` varchar(255) DEFAULT NULL AFTER `accent_color`;');
}
if ($CI->db->table_exists($companiesTable) && !$CI->db->field_exists('default_department_id', $companiesTable)) {
    $CI->db->query('ALTER TABLE `' . $companiesTable . '` ADD `default_department_id` int(11) DEFAULT NULL AFTER `logo_url`;');
}
if ($CI->db->table_exists($companiesTable) && !$CI->db->field_exists('default_staffid', $companiesTable)) {
    $CI->db->query('ALTER TABLE `' . $companiesTable . '` ADD `default_staffid` int(11) DEFAULT NULL AFTER `default_department_id`;');
}

if (!$CI->db->table_exists($staffTable)) {
    $CI->db->query('CREATE TABLE `' . $staffTable . "` (
        `id` int(11) NOT NULL,
        `company_id` int(11) NOT NULL,
        `staffid` int(11) NOT NULL,
        `role_label` varchar(191) DEFAULT NULL,
        `lane` varchar(191) DEFAULT NULL,
        `is_default_owner` tinyint(1) NOT NULL DEFAULT 0,
        `can_view` tinyint(1) NOT NULL DEFAULT 1,
        `can_reply` tinyint(1) NOT NULL DEFAULT 0,
        `created_at` datetime NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . $staffTable . '` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `company_staff` (`company_id`, `staffid`), ADD KEY `staffid` (`staffid`);');
    $CI->db->query('ALTER TABLE `' . $staffTable . '` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

if (!$CI->db->table_exists($mapTable)) {
    $CI->db->query('CREATE TABLE `' . $mapTable . "` (
        `id` int(11) NOT NULL,
        `company_id` int(11) NOT NULL,
        `rel_type` varchar(60) NOT NULL,
        `rel_id` int(11) NOT NULL,
        `source_site` varchar(191) DEFAULT NULL,
        `source_path` varchar(255) DEFAULT NULL,
        `origin` varchar(80) DEFAULT NULL,
        `created_at` datetime NOT NULL,
        `updated_at` datetime DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . $mapTable . '` ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `company_rel` (`company_id`, `rel_type`, `rel_id`), ADD KEY `rel_lookup` (`rel_type`, `rel_id`), ADD KEY `company_id` (`company_id`);');
    $CI->db->query('ALTER TABLE `' . $mapTable . '` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;');
}

company_context_install_seed_company($CI, [
    'name' => 'U.S. Capital Private Bank',
    'slug' => 'us-capital-private-bank',
    'public_label' => 'U.S. Capital Private Bank Client Care',
    'primary_domain' => 'uscapitalprivatebank.com',
    'domain_aliases' => 'uscpb.net',
    'support_email' => 'support@uscapitalprivatebank.com',
    'default_from_email' => 'support@uscapitalprivatebank.com',
    'reply_to_email' => 'support@uscapitalprivatebank.com',
    'bounce_email' => 'bounces@uscapitalprivatebank.com',
    'allowed_sender_domains' => 'uscapitalprivatebank.com,uscpb.net',
    'mailbox_owner_staffid' => 50,
    'support_url' => 'https://www.uscapitalprivatebank.com/crm/clients/open_ticket',
    'primary_color' => '#17216f',
    'secondary_color' => '#3b48ff',
    'accent_color' => '#10b981',
    'sort_order' => 10,
]);

company_context_install_seed_company($CI, [
    'name' => 'ETO',
    'slug' => 'eto',
    'public_label' => 'ETO Client Support',
    'primary_domain' => 'uscapitalprivatebank.org',
    'domain_aliases' => 'uscapitalfundingii.us',
    'support_email' => 'support@uscapitalprivatebank.org',
    'default_from_email' => 'support@uscapitalprivatebank.org',
    'reply_to_email' => 'support@uscapitalprivatebank.org',
    'bounce_email' => 'bounces@uscapitalprivatebank.org',
    'allowed_sender_domains' => 'uscapitalprivatebank.org,uscapitalfundingii.us',
    'mailbox_owner_staffid' => 50,
    'support_url' => 'https://www.uscapitalprivatebank.com/crm/clients/open_ticket?source_company=eto',
    'primary_color' => '#102a43',
    'secondary_color' => '#2f80ed',
    'accent_color' => '#f2c94c',
    'sort_order' => 20,
]);

company_context_install_seed_company($CI, [
    'name' => 'Fresh Start Financials',
    'slug' => 'fresh-start-financials',
    'public_label' => 'Fresh Start Financials Support',
    'primary_domain' => 'freshstartfinancials.co',
    'domain_aliases' => '',
    'support_email' => 'support@freshstartfinancials.co',
    'default_from_email' => 'support@freshstartfinancials.co',
    'reply_to_email' => 'support@freshstartfinancials.co',
    'bounce_email' => 'bounces@freshstartfinancials.co',
    'allowed_sender_domains' => 'freshstartfinancials.co',
    'mailbox_owner_staffid' => 50,
    'support_url' => 'https://www.uscapitalprivatebank.com/crm/clients/open_ticket?source_company=fresh-start-financials',
    'primary_color' => '#064e3b',
    'secondary_color' => '#059669',
    'accent_color' => '#f59e0b',
    'sort_order' => 30,
]);

company_context_install_seed_company($CI, [
    'name' => 'ICJ / ICC Legal Services',
    'slug' => 'icj-icc-legal-services',
    'public_label' => 'ICJ / ICC Legal Services Intake',
    'primary_domain' => 'icjglobal.org',
    'domain_aliases' => '',
    'support_email' => 'support@icjglobal.org',
    'default_from_email' => 'support@icjglobal.org',
    'reply_to_email' => 'support@icjglobal.org',
    'bounce_email' => 'bounces@icjglobal.org',
    'allowed_sender_domains' => 'icjglobal.org',
    'mailbox_owner_staffid' => 50,
    'support_url' => 'https://www.uscapitalprivatebank.com/crm/clients/open_ticket?source_company=icj-icc-legal-services',
    'primary_color' => '#1f2937',
    'secondary_color' => '#4b5563',
    'accent_color' => '#c9a227',
    'sort_order' => 40,
]);

company_context_install_seed_company($CI, [
    'name' => 'Fidelity Pair',
    'slug' => 'fidelity-pair',
    'public_label' => 'Fidelity Pair Support',
    'primary_domain' => 'fidelitypair.com',
    'domain_aliases' => '',
    'support_email' => 'support@fidelitypair.com',
    'default_from_email' => 'support@fidelitypair.com',
    'reply_to_email' => 'support@fidelitypair.com',
    'bounce_email' => 'bounces@fidelitypair.com',
    'allowed_sender_domains' => 'fidelitypair.com',
    'mailbox_owner_staffid' => 50,
    'support_url' => 'https://www.uscapitalprivatebank.com/crm/clients/open_ticket?source_company=fidelity-pair',
    'primary_color' => '#0f766e',
    'secondary_color' => '#14b8a6',
    'accent_color' => '#2563eb',
    'sort_order' => 50,
]);

company_context_install_seed_company($CI, [
    'name' => 'One World Media',
    'slug' => 'one-world-media',
    'public_label' => 'One World Media Support',
    'primary_domain' => 'oneworldmedia.us',
    'domain_aliases' => '',
    'support_email' => 'support@oneworldmedia.us',
    'default_from_email' => 'support@oneworldmedia.us',
    'reply_to_email' => 'support@oneworldmedia.us',
    'bounce_email' => 'bounces@oneworldmedia.us',
    'allowed_sender_domains' => 'oneworldmedia.us',
    'mailbox_owner_staffid' => 50,
    'support_url' => 'https://www.uscapitalprivatebank.com/crm/clients/open_ticket?source_company=one-world-media',
    'primary_color' => '#581c87',
    'secondary_color' => '#7c3aed',
    'accent_color' => '#ec4899',
    'sort_order' => 60,
]);

company_context_install_seed_company($CI, [
    'name' => 'Trading Platform',
    'slug' => 'trading-platform',
    'public_label' => 'Trading Platform Support',
    'primary_domain' => 'trading.uscpb.net',
    'domain_aliases' => '',
    'support_email' => 'support@trading.uscpb.net',
    'default_from_email' => 'support@trading.uscpb.net',
    'reply_to_email' => 'support@trading.uscpb.net',
    'bounce_email' => 'bounces@trading.uscpb.net',
    'allowed_sender_domains' => 'trading.uscpb.net',
    'mailbox_owner_staffid' => 50,
    'support_url' => 'https://www.uscapitalprivatebank.com/crm/clients/open_ticket?source_company=trading-platform',
    'primary_color' => '#0b1120',
    'secondary_color' => '#2563eb',
    'accent_color' => '#22c55e',
    'sort_order' => 70,
]);

company_context_install_seed_company($CI, [
    'name' => 'Swift / NFT Transaction Platform',
    'slug' => 'swift-nft-transaction-platform',
    'public_label' => 'Swift / NFT Transaction Support',
    'primary_domain' => 'swift-nft.com',
    'domain_aliases' => '',
    'support_email' => 'support@swift-nft.com',
    'default_from_email' => 'support@swift-nft.com',
    'reply_to_email' => 'support@swift-nft.com',
    'bounce_email' => 'bounces@swift-nft.com',
    'allowed_sender_domains' => 'swift-nft.com',
    'mailbox_owner_staffid' => 50,
    'support_url' => 'https://www.uscapitalprivatebank.com/crm/clients/open_ticket?source_company=swift-nft-transaction-platform',
    'primary_color' => '#1e1b4b',
    'secondary_color' => '#4338ca',
    'accent_color' => '#06b6d4',
    'sort_order' => 80,
]);

company_context_install_seed_company($CI, [
    'name' => 'GOEDFA Connect',
    'slug' => 'goedfa-connect',
    'public_label' => 'GOEDFA Connect Support',
    'primary_domain' => 'goedfa.org',
    'domain_aliases' => '',
    'support_email' => 'support@goedfa.org',
    'default_from_email' => 'support@goedfa.org',
    'reply_to_email' => 'support@goedfa.org',
    'bounce_email' => 'bounces@goedfa.org',
    'allowed_sender_domains' => 'goedfa.org',
    'mailbox_owner_staffid' => 50,
    'support_url' => 'https://www.uscapitalprivatebank.com/crm/clients/open_ticket?source_company=goedfa-connect',
    'primary_color' => '#14532d',
    'secondary_color' => '#16a34a',
    'accent_color' => '#facc15',
    'sort_order' => 90,
]);

company_context_install_seed_company($CI, [
    'name' => 'MediaTube',
    'slug' => 'mediatube',
    'public_label' => 'MediaTube Support',
    'primary_domain' => 'media-tube.com',
    'domain_aliases' => '',
    'support_email' => 'support@media-tube.com',
    'default_from_email' => 'support@media-tube.com',
    'reply_to_email' => 'support@media-tube.com',
    'bounce_email' => 'bounces@media-tube.com',
    'allowed_sender_domains' => 'media-tube.com',
    'mailbox_owner_staffid' => 50,
    'support_url' => 'https://www.uscapitalprivatebank.com/crm/clients/open_ticket?source_company=mediatube',
    'primary_color' => '#7f1d1d',
    'secondary_color' => '#dc2626',
    'accent_color' => '#f97316',
    'sort_order' => 100,
]);

company_context_install_seed_company($CI, [
    'name' => 'The Royal House of David',
    'slug' => 'the-royal-house-of-david',
    'public_label' => 'Royal House of David Support',
    'primary_domain' => 'royalhouseofdavid.com',
    'domain_aliases' => '',
    'support_email' => 'support@royalhouseofdavid.com',
    'default_from_email' => 'support@royalhouseofdavid.com',
    'reply_to_email' => 'support@royalhouseofdavid.com',
    'bounce_email' => 'bounces@royalhouseofdavid.com',
    'allowed_sender_domains' => 'royalhouseofdavid.com',
    'mailbox_owner_staffid' => 50,
    'support_url' => 'https://www.uscapitalprivatebank.com/crm/clients/open_ticket?source_company=the-royal-house-of-david',
    'primary_color' => '#111827',
    'secondary_color' => '#7c2d12',
    'accent_color' => '#d97706',
    'sort_order' => 110,
]);

company_context_install_seed_company($CI, [
    'name' => 'Friendware',
    'slug' => 'friendware',
    'public_label' => 'Friendware Support',
    'primary_domain' => 'friendware.net',
    'domain_aliases' => '',
    'support_email' => 'support@friendware.net',
    'default_from_email' => 'support@friendware.net',
    'reply_to_email' => 'support@friendware.net',
    'bounce_email' => 'bounces@friendware.net',
    'allowed_sender_domains' => 'friendware.net',
    'mailbox_owner_staffid' => 50,
    'support_url' => 'https://www.uscapitalprivatebank.com/crm/clients/open_ticket?source_company=friendware',
    'primary_color' => '#1d4ed8',
    'secondary_color' => '#60a5fa',
    'accent_color' => '#f97316',
    'sort_order' => 120,
]);

company_context_install_seed_company($CI, [
    'name' => 'CyberShop Direct',
    'slug' => 'cybershop-direct',
    'public_label' => 'CyberShop Direct Support',
    'primary_domain' => 'cybershopdirect.com',
    'domain_aliases' => '',
    'support_email' => 'support@cybershopdirect.com',
    'default_from_email' => 'support@cybershopdirect.com',
    'reply_to_email' => 'support@cybershopdirect.com',
    'bounce_email' => 'bounces@cybershopdirect.com',
    'allowed_sender_domains' => 'cybershopdirect.com',
    'mailbox_owner_staffid' => 50,
    'support_url' => 'https://www.uscapitalprivatebank.com/crm/clients/open_ticket?source_company=cybershop-direct',
    'primary_color' => '#0f172a',
    'secondary_color' => '#0ea5e9',
    'accent_color' => '#22c55e',
    'sort_order' => 130,
]);

company_context_install_seed_company($CI, [
    'name' => 'Elegant Styles',
    'slug' => 'elegant-styles',
    'public_label' => 'Elegant Styles Support',
    'primary_domain' => 'elegantstyles.net',
    'domain_aliases' => '',
    'support_email' => 'support@elegantstyles.net',
    'default_from_email' => 'support@elegantstyles.net',
    'reply_to_email' => 'support@elegantstyles.net',
    'bounce_email' => 'bounces@elegantstyles.net',
    'allowed_sender_domains' => 'elegantstyles.net',
    'mailbox_owner_staffid' => 50,
    'support_url' => 'https://www.uscapitalprivatebank.com/crm/clients/open_ticket?source_company=elegant-styles',
    'primary_color' => '#312e81',
    'secondary_color' => '#c026d3',
    'accent_color' => '#f59e0b',
    'sort_order' => 140,
]);

company_context_install_seed_core_staff($CI);

function company_context_install_seed_company($CI, $company)
{
    $table = db_prefix() . 'company_context_companies';
    $exists = $CI->db->where('slug', $company['slug'])->get($table)->row_array();

    if ($exists) {
        $payload = [];
        foreach ($company as $key => $value) {
            if ($key === 'slug') {
                continue;
            }
            if (!array_key_exists($key, $exists) || $exists[$key] === null || $exists[$key] === '') {
                $payload[$key] = $value;
            }
        }
        if ($payload) {
            $payload['updated_at'] = date('Y-m-d H:i:s');
            $CI->db->where('id', (int) $exists['id'])->update($table, $payload);
        }
        return (int) $exists['id'];
    }

    $company['created_at'] = date('Y-m-d H:i:s');
    if (!isset($company['active'])) {
        $company['active'] = 1;
    }
    $CI->db->insert($table, $company);
    return (int) $CI->db->insert_id();
}

function company_context_install_seed_core_staff($CI)
{
    $companies = $CI->db->get(db_prefix() . 'company_context_companies')->result_array();
    foreach ($companies as $company) {
        company_context_install_seed_staff($CI, (int) $company['id'], 29, 'Fleet Director / Customer Care Overseer', 'Executive oversight', 1, 1);
        company_context_install_seed_staff($CI, (int) $company['id'], 50, 'CRM Support Lead', 'CRM support supervisor', 1, 1);
    }

    $primary = $CI->db->where('slug', 'us-capital-private-bank')->get(db_prefix() . 'company_context_companies')->row_array();
    if (!$primary) {
        return;
    }

    $primaryId = (int) $primary['id'];
    $staff = [
        [39, 'Client Relations Agent', 'Live chat and support tickets'],
        [40, 'Client Relations Agent', 'Live chat and support tickets'],
        [41, 'Client Relations Agent', 'Live chat and support tickets'],
        [42, 'Account Services Coordinator', 'Onboarding, profile, and KYC guidance'],
        [43, 'Transaction Support Associate', 'Transaction intake and document routing'],
        [44, 'Client Relations Agent', 'Live chat and support tickets'],
        [45, 'Institutional Sales Manager', 'Institutional sales discovery'],
        [46, 'Closing Specialist', 'Objections and qualified handoff'],
        [47, 'Pipeline Development Coordinator', 'Pipeline cadence and hygiene'],
        [48, 'Marketing Communications Director', 'Campaign language and intake'],
        [49, 'Campaign Strategy Manager', 'Campaign strategy and approvals queue'],
    ];

    foreach ($staff as $row) {
        company_context_install_seed_staff($CI, $primaryId, $row[0], $row[1], $row[2], 1, 1);
    }
}

function company_context_install_seed_staff($CI, $companyId, $staffId, $roleLabel, $lane, $canView, $canReply)
{
    $table = db_prefix() . 'company_context_staff';
    $exists = $CI->db->where('company_id', $companyId)->where('staffid', $staffId)->get($table)->row_array();
    if ($exists) {
        $payload = [];
        if (!isset($exists['role_label']) || $exists['role_label'] === '') {
            $payload['role_label'] = $roleLabel;
        }
        if (!isset($exists['lane']) || $exists['lane'] === '') {
            $payload['lane'] = $lane;
        }
        if ($payload) {
            $CI->db->where('id', (int) $exists['id'])->update($table, $payload);
        }
        return;
    }

    $CI->db->insert($table, [
        'company_id'       => $companyId,
        'staffid'          => $staffId,
        'role_label'       => $roleLabel,
        'lane'             => $lane,
        'is_default_owner' => $staffId === 50 ? 1 : 0,
        'can_view'         => (int) $canView,
        'can_reply'        => (int) $canReply,
        'created_at'       => date('Y-m-d H:i:s'),
    ]);
}
