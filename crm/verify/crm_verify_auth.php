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
