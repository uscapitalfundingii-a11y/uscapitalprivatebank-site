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
