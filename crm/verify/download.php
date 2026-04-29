<?php
session_start();
require_once __DIR__ . '/crm_verify_auth.php';
$baseDir = __DIR__ . '/files';

$fileParam = trim($_GET['file'] ?? '');
if (strpos($fileParam, '..') !== false || !$fileParam) {
    die("Invalid file.");
}

$fullPath = realpath($baseDir . '/' . $fileParam);
$documents = verify_load_documents();
$document = $documents[basename($fileParam)] ?? null;
if (!$fullPath || strpos($fullPath, $baseDir) !== 0 || !is_file($fullPath) || !is_array($document) || !verify_is_document_approved($document)) {
    die("Unauthorized or missing file.");
}

if (verify_document_is_restricted($document) && !verify_current_user_can_access_document($document)) {
    die("This verified document is restricted to specific approved role groups.");
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($fullPath) . '"');
header('Content-Length: ' . filesize($fullPath));
readfile($fullPath);
exit;
