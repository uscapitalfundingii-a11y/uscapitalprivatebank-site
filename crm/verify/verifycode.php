<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/crm_verify_auth.php';
$code = trim((string) ($_POST['code'] ?? $_GET['code'] ?? ''));

if ($code === '') {
    header('Location: index.php?error=' . urlencode('Please enter a document code to continue.'));
    exit;
}

$document = verify_find_document_by_code($code);
if ($document === null || !verify_is_document_approved($document)) {
    header('Location: index.php?error=' . urlencode('No verified document was found for that code.'));
    exit;
}

if (verify_document_is_restricted($document) && !verify_current_user_can_access_document($document)) {
    header('Location: index.php?error=' . urlencode('That verified document is restricted to specific approved role groups.'));
    exit;
}

$file = basename((string) $document['file']);
header('Location: viewfile.php?file=' . urlencode($file));
exit;
