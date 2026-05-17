<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/crm_verify_auth.php';
session_start();

if (empty($_SESSION['upload_authenticated']) || empty($_SESSION['username'])) {
    header('Location: index.php?error=' . urlencode('Please sign in to review verification documents.'));
    exit;
}

if (!verify_has_any_permission(['review_documents', 'approve_documents', 'edit_documents', 'replace_documents', 'delete_documents'])) {
    header('Location: documents.php?error=' . urlencode('Your account is not permitted to review verification files.'));
    exit;
}

$baseDir = __DIR__ . '/files';
$requestedFile = basename((string) ($_GET['file'] ?? ''));
$filePath = $requestedFile !== '' ? $baseDir . '/' . $requestedFile : '';
$documents = verify_load_documents();
$document = $documents[$requestedFile] ?? null;

if ($requestedFile === '' || !is_file($filePath) || !is_array($document)) {
    header('Location: documents.php?error=' . urlencode('The requested review file could not be located.'));
    exit;
}

$extension = strtolower(pathinfo($requestedFile, PATHINFO_EXTENSION));
$documentCode = (string) ($document['code'] ?? pathinfo($requestedFile, PATHINFO_FILENAME));
$documentTitle = trim((string) ($document['title'] ?? pathinfo($requestedFile, PATHINFO_FILENAME)));
$documentDescription = trim((string) ($document['notes'] ?? ''));
$status = (string) ($document['status'] ?? 'pending');
$uploadedBy = (string) ($document['uploaded_by'] ?? 'Unknown');
$uploadedAt = (string) ($document['uploaded_at'] ?? date('c', filemtime($filePath)));
$rawUrl = 'reviewfile.php?file=' . rawurlencode($requestedFile) . '&raw=1';
$downloadName = preg_replace('/[^a-zA-Z0-9._ -]/', '_', $requestedFile) ?: 'verification-document';
$mimeTypes = [
    'pdf' => 'application/pdf',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'txt' => 'text/plain; charset=UTF-8',
];
$contentType = $mimeTypes[$extension] ?? 'application/octet-stream';

if (isset($_GET['raw'])) {
    header('Content-Type: ' . $contentType);
    header('Content-Length: ' . filesize($filePath));
    header('Content-Disposition: inline; filename="' . addslashes($downloadName) . '"');
    header('X-Content-Type-Options: nosniff');
    readfile($filePath);
    exit;
}

$previewHtml = '';
if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
    $previewHtml = '<img src="' . htmlspecialchars($rawUrl, ENT_QUOTES, 'UTF-8') . '" alt="Verification document review preview" style="max-width:100%; border-radius:20px; display:block;">';
} elseif ($extension === 'pdf') {
    $previewHtml = '<iframe src="' . htmlspecialchars($rawUrl, ENT_QUOTES, 'UTF-8') . '" style="width:100%; min-height:820px; border:0; border-radius:20px; background:#fff;"></iframe>';
} elseif ($extension === 'txt') {
    $previewHtml = '<iframe src="' . htmlspecialchars($rawUrl, ENT_QUOTES, 'UTF-8') . '" style="width:100%; min-height:520px; border:0; border-radius:20px; background:#fff;"></iframe>';
} else {
    $previewHtml = '<div class="verify-empty">Inline preview is not available for this file type. Use Open Original to review the file in a new browser tab before approving it.</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Verification File | US Capital Private Bank</title>
    <link rel="stylesheet" href="theme.css">
</head>
<body class="verify-portal">
    <div class="verify-shell">
        <div class="verify-topbar">
            <div class="verify-brand">
                <img src="https://www.uscapitalprivatebank.com/assets/images/logoIcon/logo.png" alt="US Capital Private Bank">
                <div class="verify-brand-copy">
                    <h1>US Capital Private Bank</h1>
                    <p>Admin Document Review</p>
                </div>
            </div>
            <div class="verify-links">
                <a class="verify-link" href="documents.php">Document Library</a>
                <a class="verify-button-secondary" href="<?= htmlspecialchars($rawUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Open Original</a>
            </div>
        </div>

        <div class="verify-hero">
            <div class="verify-card">
                <div class="verify-card-inner">
                    <span class="verify-kicker">Admin Review Preview</span>
                    <h2 class="verify-title"><?= htmlspecialchars($documentTitle, ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="verify-copy">
                        Review this uploaded file before approving it. This page is available only to accounts with document review or management permissions.
                    </p>
                    <div class="verify-actions">
                        <a class="verify-button" href="<?= htmlspecialchars($rawUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Open Original</a>
                        <a class="verify-button-secondary" href="documents.php">Back To Approval Queue</a>
                    </div>
                </div>
            </div>

            <div class="verify-card">
                <div class="verify-qr-panel">
                    <div style="width:100%;">
                        <h3 style="margin:0 0 16px; font-size:28px;">Review details</h3>
                        <div class="verify-meta-list">
                            <div class="verify-meta-item"><strong>Document Code</strong><span><?= htmlspecialchars($documentCode, ENT_QUOTES, 'UTF-8') ?></span></div>
                            <div class="verify-meta-item"><strong>Status</strong><span><?= htmlspecialchars(ucwords(str_replace('_', ' ', $status)), ENT_QUOTES, 'UTF-8') ?></span></div>
                            <div class="verify-meta-item"><strong>Uploaded By</strong><span><?= htmlspecialchars($uploadedBy, ENT_QUOTES, 'UTF-8') ?></span></div>
                            <div class="verify-meta-item"><strong>Uploaded</strong><span><?= htmlspecialchars(date('M j, Y g:i A', strtotime($uploadedAt)), ENT_QUOTES, 'UTF-8') ?></span></div>
                            <div class="verify-meta-item"><strong>File Type</strong><span><?= htmlspecialchars(strtoupper($extension), ENT_QUOTES, 'UTF-8') ?></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($documentDescription !== ''): ?>
            <div class="verify-card" style="margin-top:26px;">
                <div class="verify-card-inner">
                    <h3 style="margin:0 0 12px; font-size:22px;">Document notes</h3>
                    <p class="verify-copy" style="margin:0;"><?= htmlspecialchars($documentDescription, ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="verify-card" style="margin-top:26px;">
            <div class="verify-card-inner">
                <h3 style="margin:0 0 18px; font-size:26px;">File preview</h3>
                <?= $previewHtml ?>
            </div>
        </div>
    </div>
</body>
</html>
