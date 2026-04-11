<?php

function verify_users_file_path()
{
    return __DIR__ . '/users.json';
}

function verify_default_admin_record()
{
    return [
        'password' => 'Darius1985Angel!?.',
        'email' => '',
        'organization' => '',
        'reference' => '',
        'status' => 'approved',
        'role' => 'admin',
        'created_at' => date('c'),
    ];
}

function verify_normalize_user_record($username, $entry)
{
    if ($username === 'admin' && !is_array($entry)) {
        $entry = ['password' => (string) $entry];
    }

    if (!is_array($entry)) {
        return null;
    }

    return [
        'password' => (string) ($entry['password'] ?? ''),
        'email' => (string) ($entry['email'] ?? ''),
        'organization' => (string) ($entry['organization'] ?? ''),
        'reference' => (string) ($entry['reference'] ?? ''),
        'status' => (string) ($entry['status'] ?? 'pending'),
        'role' => (string) ($entry['role'] ?? ($username === 'admin' ? 'admin' : 'trustee')),
        'created_at' => (string) ($entry['created_at'] ?? date('c')),
    ];
}

function verify_load_users()
{
    $credentialsFile = verify_users_file_path();
    if (!file_exists($credentialsFile)) {
        $users = ['admin' => verify_default_admin_record()];
        file_put_contents($credentialsFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return $users;
    }

    $decoded = json_decode((string) file_get_contents($credentialsFile), true);
    if (!is_array($decoded)) {
        $decoded = [];
    }

    $users = [];
    foreach ($decoded as $username => $entry) {
        $normalized = verify_normalize_user_record((string) $username, $entry);
        if ($normalized !== null) {
            $users[(string) $username] = $normalized;
        }
    }

    if (!isset($users['admin'])) {
        $users['admin'] = verify_default_admin_record();
    } else {
        $users['admin']['role'] = 'admin';
        $users['admin']['status'] = 'approved';
        if ($users['admin']['password'] === '') {
            $users['admin']['password'] = verify_default_admin_record()['password'];
        }
    }

    file_put_contents($credentialsFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return $users;
}

function verify_save_users(array $users)
{
    file_put_contents(verify_users_file_path(), json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function verify_current_role()
{
    return (string) ($_SESSION['user_role'] ?? '');
}

function verify_is_admin()
{
    return verify_current_role() === 'admin';
}

function verify_is_trustee()
{
    return verify_current_role() === 'trustee';
}

function verify_documents_file_path()
{
    return __DIR__ . '/documents.json';
}

function verify_normalize_document_record($filename, $entry)
{
    if (!is_array($entry)) {
        return null;
    }

    $file = basename((string) ($entry['file'] ?? $filename));
    $defaultCode = pathinfo($file, PATHINFO_FILENAME);

    return [
        'title' => (string) ($entry['title'] ?? $defaultCode),
        'code' => (string) ($entry['code'] ?? $defaultCode),
        'file' => $file,
        'notes' => (string) ($entry['notes'] ?? ''),
        'uploaded_by' => (string) ($entry['uploaded_by'] ?? 'Unknown'),
        'uploaded_at' => (string) ($entry['uploaded_at'] ?? date('c')),
        'status' => (string) ($entry['status'] ?? 'approved'),
        'approved_by' => (string) ($entry['approved_by'] ?? ''),
        'approved_at' => (string) ($entry['approved_at'] ?? ''),
        'rejection_note' => (string) ($entry['rejection_note'] ?? ''),
    ];
}

function verify_load_documents()
{
    $documentsFile = verify_documents_file_path();
    if (!file_exists($documentsFile)) {
        file_put_contents($documentsFile, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return [];
    }

    $decoded = json_decode((string) file_get_contents($documentsFile), true);
    if (!is_array($decoded)) {
        $decoded = [];
    }

    $documents = [];
    foreach ($decoded as $filename => $entry) {
        $normalized = verify_normalize_document_record((string) $filename, $entry);
        if ($normalized !== null) {
            $documents[$normalized['file']] = $normalized;
        }
    }

    file_put_contents($documentsFile, json_encode($documents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return $documents;
}

function verify_save_documents(array $documents)
{
    file_put_contents(verify_documents_file_path(), json_encode($documents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function verify_is_document_approved(array $document)
{
    return (string) ($document['status'] ?? 'pending') === 'approved';
}

function verify_find_document_by_code(string $code)
{
    $normalizedCode = trim($code);
    if ($normalizedCode === '') {
        return null;
    }

    foreach (verify_load_documents() as $document) {
        if ((string) ($document['code'] ?? '') === $normalizedCode) {
            return $document;
        }
    }

    return null;
}

function verify_id_cards_file_path()
{
    return __DIR__ . '/idcards.json';
}

function verify_normalize_id_card_record($key, $entry)
{
    if (!is_array($entry)) {
        return null;
    }

    $code = strtoupper(trim((string) ($entry['code'] ?? $key)));
    if ($code === '') {
        return null;
    }

    return [
        'code' => $code,
        'name' => trim((string) ($entry['name'] ?? '')),
        'title' => trim((string) ($entry['title'] ?? '')),
        'department' => trim((string) ($entry['department'] ?? '')),
        'affiliation' => trim((string) ($entry['affiliation'] ?? 'Bank Officer')),
        'email' => trim((string) ($entry['email'] ?? '')),
        'phone' => trim((string) ($entry['phone'] ?? '')),
        'photo_url' => trim((string) ($entry['photo_url'] ?? '')),
        'notes' => trim((string) ($entry['notes'] ?? '')),
        'template_key' => trim((string) ($entry['template_key'] ?? '')),
        'design' => verify_normalize_id_card_design(is_array($entry['design'] ?? null) ? $entry['design'] : []),
        'status' => trim((string) ($entry['status'] ?? 'active')),
        'created_by' => trim((string) ($entry['created_by'] ?? '')),
        'created_at' => trim((string) ($entry['created_at'] ?? date('c'))),
        'updated_at' => trim((string) ($entry['updated_at'] ?? date('c'))),
    ];
}

function verify_load_id_cards()
{
    $file = verify_id_cards_file_path();
    if (!file_exists($file)) {
        file_put_contents($file, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return [];
    }

    $decoded = json_decode((string) file_get_contents($file), true);
    if (!is_array($decoded)) {
        $decoded = [];
    }

    $cards = [];
    foreach ($decoded as $key => $entry) {
        $normalized = verify_normalize_id_card_record((string) $key, $entry);
        if ($normalized !== null) {
            $cards[$normalized['code']] = $normalized;
        }
    }

    file_put_contents($file, json_encode($cards, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return $cards;
}

function verify_save_id_cards(array $cards)
{
    file_put_contents(verify_id_cards_file_path(), json_encode($cards, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function verify_generate_id_card_code(string $name = ''): string
{
    $base = preg_replace('/[^A-Z0-9]+/', '', strtoupper($name));
    $base = substr($base, 0, 6);
    if ($base === '') {
        $base = 'USCPB';
    }

    return $base . '-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 6));
}

function verify_find_id_card_by_code(string $code)
{
    $normalized = strtoupper(trim($code));
    if ($normalized === '') {
        return null;
    }

    foreach (verify_load_id_cards() as $card) {
        if (($card['code'] ?? '') === $normalized) {
            return $card;
        }
    }

    return null;
}

function verify_id_card_photo_dir_path()
{
    return __DIR__ . '/idcard_photos';
}

function verify_id_card_photo_web_path(string $filename)
{
    return 'idcard_photos/' . rawurlencode(basename($filename));
}

function verify_id_card_assets_dir_path()
{
    return __DIR__ . '/idcard_assets';
}

function verify_id_card_assets_web_path(string $filename)
{
    return 'idcard_assets/' . rawurlencode(basename($filename));
}

function verify_id_card_design_settings_path()
{
    return __DIR__ . '/idcard_design.json';
}

function verify_default_id_card_design(): array
{
    return [
        'preset_key' => 'portrait_classic_navy',
        'orientation' => 'portrait',
        'width_mm' => 54,
        'height_mm' => 86,
        'headline_font' => 'Segoe UI',
        'body_font' => 'Segoe UI',
        'primary_color' => '#18294d',
        'secondary_color' => '#243c6f',
        'accent_color' => '#2b89d9',
        'metal_color' => '#8f836f',
        'front_background' => '',
        'back_background' => '',
        'logo_image' => 'uscapital-private-bank-white.png',
    ];
}

function verify_normalize_id_card_design(array $settings): array
{
    $defaults = verify_default_id_card_design();
    $normalized = array_merge($defaults, $settings);

    $presets = verify_id_card_design_presets();
    $presetKey = trim((string) ($normalized['preset_key'] ?? ''));
    if ($presetKey !== '' && isset($presets[$presetKey])) {
        $normalized = array_merge($defaults, $presets[$presetKey], $settings);
    }

    $normalized['orientation'] = in_array((string) $normalized['orientation'], ['portrait', 'landscape'], true)
        ? (string) $normalized['orientation']
        : $defaults['orientation'];

    $normalized['preset_key'] = isset($presets[$presetKey]) ? $presetKey : $defaults['preset_key'];

    $normalized['width_mm'] = max(40, min(120, (float) $normalized['width_mm']));
    $normalized['height_mm'] = max(54, min(120, (float) $normalized['height_mm']));

    foreach (['headline_font', 'body_font', 'primary_color', 'secondary_color', 'accent_color', 'metal_color', 'front_background', 'back_background', 'logo_image'] as $key) {
        $normalized[$key] = trim((string) $normalized[$key]);
    }

    return $normalized;
}

function verify_load_id_card_design(): array
{
    $path = verify_id_card_design_settings_path();
    if (!file_exists($path)) {
        $defaults = verify_default_id_card_design();
        file_put_contents($path, json_encode($defaults, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return $defaults;
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    if (!is_array($decoded)) {
        $decoded = [];
    }

    $normalized = verify_normalize_id_card_design($decoded);
    file_put_contents($path, json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return $normalized;
}

function verify_save_id_card_design(array $settings): void
{
    $normalized = verify_normalize_id_card_design($settings);
    file_put_contents(verify_id_card_design_settings_path(), json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function verify_id_card_design_presets(): array
{
    return [
        'portrait_classic_navy' => ['label' => 'Portrait Classic Navy', 'orientation' => 'portrait', 'primary_color' => '#18294d', 'secondary_color' => '#243c6f', 'accent_color' => '#2b89d9', 'metal_color' => '#8f836f'],
        'portrait_silver_glass' => ['label' => 'Portrait Silver Glass', 'orientation' => 'portrait', 'primary_color' => '#22314f', 'secondary_color' => '#4f6486', 'accent_color' => '#6cb2ff', 'metal_color' => '#a8aeb8'],
        'portrait_exec_blue' => ['label' => 'Portrait Executive Blue', 'orientation' => 'portrait', 'primary_color' => '#11284e', 'secondary_color' => '#29558f', 'accent_color' => '#2ea0ff', 'metal_color' => '#8f8b81'],
        'portrait_midnight_gold' => ['label' => 'Portrait Midnight Gold', 'orientation' => 'portrait', 'primary_color' => '#121b33', 'secondary_color' => '#3d4f79', 'accent_color' => '#d8b15b', 'metal_color' => '#9f7c3f'],
        'portrait_platinum' => ['label' => 'Portrait Platinum', 'orientation' => 'portrait', 'primary_color' => '#1c2940', 'secondary_color' => '#61728e', 'accent_color' => '#8eb8f5', 'metal_color' => '#a7a19a'],
        'portrait_royal' => ['label' => 'Portrait Royal', 'orientation' => 'portrait', 'primary_color' => '#1a2557', 'secondary_color' => '#3b509f', 'accent_color' => '#4aa8ff', 'metal_color' => '#988867'],
        'portrait_carbongrey' => ['label' => 'Portrait Carbon Grey', 'orientation' => 'portrait', 'primary_color' => '#1d2430', 'secondary_color' => '#4b5a6d', 'accent_color' => '#57b9ff', 'metal_color' => '#8e877c'],
        'portrait_deepsea' => ['label' => 'Portrait Deep Sea', 'orientation' => 'portrait', 'primary_color' => '#102a43', 'secondary_color' => '#1f5f8b', 'accent_color' => '#1fb6ff', 'metal_color' => '#8b8772'],
        'portrait_marble' => ['label' => 'Portrait Marble', 'orientation' => 'portrait', 'primary_color' => '#23324b', 'secondary_color' => '#7d8ca3', 'accent_color' => '#3e8eea', 'metal_color' => '#b3aca1'],
        'portrait_founders' => ['label' => 'Portrait Founder Series', 'orientation' => 'portrait', 'primary_color' => '#162449', 'secondary_color' => '#405988', 'accent_color' => '#2ba4ff', 'metal_color' => '#8a7f6b'],
        'landscape_classic_navy' => ['label' => 'Landscape Classic Navy', 'orientation' => 'landscape', 'primary_color' => '#18294d', 'secondary_color' => '#243c6f', 'accent_color' => '#2b89d9', 'metal_color' => '#8f836f'],
        'landscape_silver_glass' => ['label' => 'Landscape Silver Glass', 'orientation' => 'landscape', 'primary_color' => '#22314f', 'secondary_color' => '#4f6486', 'accent_color' => '#6cb2ff', 'metal_color' => '#a8aeb8'],
        'landscape_exec_blue' => ['label' => 'Landscape Executive Blue', 'orientation' => 'landscape', 'primary_color' => '#11284e', 'secondary_color' => '#29558f', 'accent_color' => '#2ea0ff', 'metal_color' => '#8f8b81'],
        'landscape_midnight_gold' => ['label' => 'Landscape Midnight Gold', 'orientation' => 'landscape', 'primary_color' => '#121b33', 'secondary_color' => '#3d4f79', 'accent_color' => '#d8b15b', 'metal_color' => '#9f7c3f'],
        'landscape_platinum' => ['label' => 'Landscape Platinum', 'orientation' => 'landscape', 'primary_color' => '#1c2940', 'secondary_color' => '#61728e', 'accent_color' => '#8eb8f5', 'metal_color' => '#a7a19a'],
        'landscape_royal' => ['label' => 'Landscape Royal', 'orientation' => 'landscape', 'primary_color' => '#1a2557', 'secondary_color' => '#3b509f', 'accent_color' => '#4aa8ff', 'metal_color' => '#988867'],
        'landscape_carbongrey' => ['label' => 'Landscape Carbon Grey', 'orientation' => 'landscape', 'primary_color' => '#1d2430', 'secondary_color' => '#4b5a6d', 'accent_color' => '#57b9ff', 'metal_color' => '#8e877c'],
        'landscape_deepsea' => ['label' => 'Landscape Deep Sea', 'orientation' => 'landscape', 'primary_color' => '#102a43', 'secondary_color' => '#1f5f8b', 'accent_color' => '#1fb6ff', 'metal_color' => '#8b8772'],
        'landscape_marble' => ['label' => 'Landscape Marble', 'orientation' => 'landscape', 'primary_color' => '#23324b', 'secondary_color' => '#7d8ca3', 'accent_color' => '#3e8eea', 'metal_color' => '#b3aca1'],
        'landscape_founders' => ['label' => 'Landscape Founder Series', 'orientation' => 'landscape', 'primary_color' => '#162449', 'secondary_color' => '#405988', 'accent_color' => '#2ba4ff', 'metal_color' => '#8a7f6b'],
    ];
}

function verify_certificates_file_path()
{
    return __DIR__ . '/certificates.json';
}

function verify_default_certificate_templates(): array
{
    return [
        'employee_of_month' => 'Employee of the Month',
        'top_sales' => 'Top Sales Performance',
        'leadership_excellence' => 'Leadership Excellence',
        'service_excellence' => 'Service Excellence',
        'trustee_recognition' => 'Trustee Recognition',
        'compliance_honors' => 'Compliance Honors',
    ];
}

function verify_normalize_certificate_record($key, $entry)
{
    if (!is_array($entry)) {
        return null;
    }

    $code = strtoupper(trim((string) ($entry['code'] ?? $key)));
    if ($code === '') {
        return null;
    }

    return [
        'code' => $code,
        'template' => trim((string) ($entry['template'] ?? 'employee_of_month')),
        'title' => trim((string) ($entry['title'] ?? 'Certificate of Recognition')),
        'recipient' => trim((string) ($entry['recipient'] ?? '')),
        'recipient_title' => trim((string) ($entry['recipient_title'] ?? '')),
        'department' => trim((string) ($entry['department'] ?? '')),
        'body' => trim((string) ($entry['body'] ?? '')),
        'issued_on' => trim((string) ($entry['issued_on'] ?? date('Y-m-d'))),
        'signed_by' => trim((string) ($entry['signed_by'] ?? 'U.S. Capital Private Bank')),
        'signer_title' => trim((string) ($entry['signer_title'] ?? 'Verification Desk')),
        'status' => trim((string) ($entry['status'] ?? 'active')),
        'created_by' => trim((string) ($entry['created_by'] ?? '')),
        'created_at' => trim((string) ($entry['created_at'] ?? date('c'))),
        'updated_at' => trim((string) ($entry['updated_at'] ?? date('c'))),
    ];
}

function verify_load_certificates()
{
    $path = verify_certificates_file_path();
    if (!file_exists($path)) {
        file_put_contents($path, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return [];
    }

    $decoded = json_decode((string) file_get_contents($path), true);
    if (!is_array($decoded)) {
        $decoded = [];
    }

    $items = [];
    foreach ($decoded as $key => $entry) {
        $normalized = verify_normalize_certificate_record((string) $key, $entry);
        if ($normalized !== null) {
            $items[$normalized['code']] = $normalized;
        }
    }

    file_put_contents($path, json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    return $items;
}

function verify_save_certificates(array $items)
{
    file_put_contents(verify_certificates_file_path(), json_encode($items, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function verify_generate_certificate_code(string $recipient = ''): string
{
    $base = preg_replace('/[^A-Z0-9]+/', '', strtoupper($recipient));
    $base = substr($base, 0, 6);
    if ($base === '') {
        $base = 'CERT';
    }

    return $base . '-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 6));
}

function verify_find_certificate_by_code(string $code)
{
    $normalized = strtoupper(trim($code));
    if ($normalized === '') {
        return null;
    }

    foreach (verify_load_certificates() as $item) {
        if (($item['code'] ?? '') === $normalized) {
            return $item;
        }
    }

    return null;
}
