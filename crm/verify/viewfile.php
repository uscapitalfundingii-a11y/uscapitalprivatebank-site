<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/crm_verify_auth.php';

$baseDir = __DIR__ . '/files';
$baseUrl = 'https://www.uscapitalprivatebank.com/crm/verify/files';
$requestedFile = basename((string) ($_GET['file'] ?? ''));
$filePath = $requestedFile !== '' ? $baseDir . '/' . $requestedFile : '';
$documents = verify_load_documents();
$document = $documents[$requestedFile] ?? null;

if ($requestedFile === '' || !is_file($filePath) || !is_array($document) || !verify_is_document_approved($document)) {
    header('Location: index.php?error=' . urlencode('The requested verification file could not be located.'));
    exit;
}

$documentUrl = $baseUrl . '/' . rawurlencode($requestedFile);
$downloadUrl = 'download.php?file=' . rawurlencode($requestedFile);
$printUrl = 'print.php?file=' . rawurlencode($requestedFile);
$printOriginalUrl = $documentUrl;
$printUrls = [
    'legal' => $printUrl . '&paper=legal',
    'letter' => $printUrl . '&paper=letter',
    'a4' => $printUrl . '&paper=a4',
];
$extension = strtolower(pathinfo($requestedFile, PATHINFO_EXTENSION));
$documentCode = (string) ($document['code'] ?? pathinfo($requestedFile, PATHINFO_FILENAME));

require_once __DIR__ . '/phpqrcode/qrlib.php';
$qrTempDir = __DIR__ . '/tempqr';
if (!is_dir($qrTempDir)) {
    @mkdir($qrTempDir, 0775, true);
}
$qrFilename = $qrTempDir . '/' . md5($requestedFile) . '.png';
if (!is_file($qrFilename)) {
    QRcode::png($documentUrl, $qrFilename, QR_ECLEVEL_L, 6);
}

$previewHtml = '';
if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
    $previewHtml = '<img src="' . htmlspecialchars($documentUrl, ENT_QUOTES, 'UTF-8') . '" alt="Verified document preview" style="max-width:100%; border-radius:20px; display:block;">';
} elseif ($extension === 'pdf') {
    $previewHtml = '<iframe src="' . htmlspecialchars($documentUrl, ENT_QUOTES, 'UTF-8') . '" style="width:100%; min-height:760px; border:0; border-radius:20px; background:#fff;"></iframe>';
} else {
    $previewHtml = '<div class="verify-empty">Preview is not available for this file type. Use the secure download button below.</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verified Document | US Capital Private Bank</title>
    <link rel="stylesheet" href="theme.css">
</head>
<body class="verify-portal">
    <div class="verify-shell">
        <div class="verify-topbar">
            <div class="verify-brand">
                <img src="https://www.uscapitalprivatebank.com/assets/images/logoIcon/logo.png" alt="US Capital Private Bank">
                <div class="verify-brand-copy">
                    <h1>US Capital Private Bank</h1>
                    <p>Issued Document Verification</p>
                </div>
            </div>
            <div class="verify-links">
                <a class="verify-link" href="https://www.uscapitalprivatebank.com/">Home</a>
                <a class="verify-link" href="index.php">Verification Portal</a>
                <a class="verify-button-secondary" href="<?= htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8') ?>">Secure Download</a>
            </div>
        </div>

        <div class="verify-hero">
            <div class="verify-card">
                <div class="verify-card-inner">
                    <span class="verify-kicker">Validated Record</span>
                    <h2 class="verify-title">Document verification confirmed.</h2>
                    <p class="verify-copy">
                        This record was retrieved from the bank’s verification repository and matched against the secure reference code embedded in the file.
                    </p>

                    <div class="verify-meta-list" style="margin-top:24px;">
                        <div class="verify-meta-item"><strong>Document Code</strong><span><?= htmlspecialchars($documentCode, ENT_QUOTES, 'UTF-8') ?></span></div>
                        <div class="verify-meta-item"><strong>File Name</strong><span><?= htmlspecialchars($requestedFile, ENT_QUOTES, 'UTF-8') ?></span></div>
                        <div class="verify-meta-item"><strong>Document Type</strong><span><?= htmlspecialchars(strtoupper($extension), ENT_QUOTES, 'UTF-8') ?></span></div>
                        <div class="verify-meta-item"><strong>Approved By</strong><span><?= htmlspecialchars((string) ($document['approved_by'] ?? 'Verification Administrator'), ENT_QUOTES, 'UTF-8') ?></span></div>
                        <div class="verify-meta-item"><strong>Verification Source</strong><span>US Capital Private Bank verification repository</span></div>
                    </div>

                    <div class="verify-actions">
                        <a class="verify-button" href="<?= htmlspecialchars($printUrls['legal'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Print Verified Copy</a>
                        <a class="verify-button-secondary" href="<?= htmlspecialchars($printOriginalUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Print Original Copy</a>
                        <a class="verify-button" href="<?= htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8') ?>">Download Verified Document</a>
                        <a class="verify-button-secondary" href="index.php">Verify Another Document</a>
                    </div>
                    <div class="verify-actions" style="margin-top:10px;">
                        <a class="verify-button-secondary" href="<?= htmlspecialchars($printUrls['legal'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Print Legal</a>
                        <a class="verify-button-secondary" href="<?= htmlspecialchars($printUrls['letter'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Print Letter</a>
                        <a class="verify-button-secondary" href="<?= htmlspecialchars($printUrls['a4'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Print A4</a>
                    </div>
                </div>
            </div>

            <div class="verify-card">
                <div class="verify-qr-panel">
                    <div>
                        <div class="verify-qr-frame">
                            <img src="<?= htmlspecialchars('tempqr/' . basename($qrFilename), ENT_QUOTES, 'UTF-8') ?>" alt="Verification QR Code">
                        </div>
                        <p class="verify-footer-note">
                            Scan the QR code to reopen this exact verification record. This supports issuer review, third-party validation, and downstream audit confirmation.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="verify-card" style="margin-top:26px;">
            <div class="verify-card-inner">
                <h3 style="margin:0 0 18px; font-size:26px;">Document preview</h3>
                <?= $previewHtml ?>
            </div>
        </div>
    </div>
</body>
</html>
