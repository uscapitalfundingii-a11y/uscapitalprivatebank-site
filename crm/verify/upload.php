<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/crm_verify_auth.php';

if (empty($_SESSION['upload_authenticated']) || empty($_SESSION['username'])) {
    header('Location: index.php?error=' . urlencode('Please sign in to access the document upload desk.'));
    exit;
}

$username = (string) $_SESSION['username'];
$filesDir = __DIR__ . '/files';
if (!is_dir($filesDir)) {
    @mkdir($filesDir, 0775, true);
}
$documents = verify_load_documents();

$error = '';
$success = '';
$uploaded = null;

function verify_slugify($value)
{
    $value = strtolower(trim((string) $value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);
    return trim((string) $value, '-');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $documentTitle = trim((string) ($_POST['document_title'] ?? ''));
    $documentCode = verify_slugify($_POST['document_code'] ?? '');
    $notes = trim((string) ($_POST['notes'] ?? ''));

    if ($documentTitle === '') {
        $error = 'Please enter a document title before uploading.';
    } elseif (!isset($_FILES['document_file']) || ($_FILES['document_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $error = 'Please choose the file you want to upload.';
    } else {
        $tmpPath = (string) $_FILES['document_file']['tmp_name'];
        $originalName = (string) $_FILES['document_file']['name'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowed = ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'doc', 'docx'];

        if (!in_array($extension, $allowed, true)) {
            $error = 'That file type is not allowed. Please upload PDF, image, or Word document formats only.';
        } else {
            if ($documentCode === '') {
                $documentCode = 'doc-' . date('Ymd') . '-' . substr(md5($documentTitle . $username . microtime(true)), 0, 8);
            }

            $targetName = $documentCode . '.' . $extension;
            $targetPath = $filesDir . '/' . $targetName;

            if (file_exists($targetPath)) {
                $error = 'A document with that code already exists. Please change the code and try again.';
            } elseif (!move_uploaded_file($tmpPath, $targetPath)) {
                $error = 'The document could not be uploaded. Please try again.';
            } else {
                $uploaded = [
                    'title' => $documentTitle,
                    'code' => $documentCode,
                    'file' => $targetName,
                    'notes' => $notes,
                    'verify_url' => 'https://www.uscapitalprivatebank.com/crm/verify/verifycode.php?code=' . rawurlencode($documentCode),
                    'view_url' => 'https://www.uscapitalprivatebank.com/crm/verify/viewfile.php?file=' . rawurlencode($targetName),
                    'print_url' => 'https://www.uscapitalprivatebank.com/crm/verify/print.php?file=' . rawurlencode($targetName),
                    'print_legal_url' => 'https://www.uscapitalprivatebank.com/crm/verify/print.php?file=' . rawurlencode($targetName) . '&paper=legal',
                    'print_letter_url' => 'https://www.uscapitalprivatebank.com/crm/verify/print.php?file=' . rawurlencode($targetName) . '&paper=letter',
                    'print_a4_url' => 'https://www.uscapitalprivatebank.com/crm/verify/print.php?file=' . rawurlencode($targetName) . '&paper=a4',
                ];
                $documents[$targetName] = [
                    'title' => $documentTitle,
                    'code' => $documentCode,
                    'file' => $targetName,
                    'notes' => $notes,
                    'uploaded_by' => $username,
                    'uploaded_at' => date('c'),
                    'status' => verify_is_admin() ? 'approved' : 'pending',
                    'approved_by' => verify_is_admin() ? $username : '',
                    'approved_at' => verify_is_admin() ? date('c') : '',
                ];
                verify_save_documents($documents);
                $success = verify_is_admin()
                    ? 'Your document has been uploaded and approved in the verification repository.'
                    : 'Your document has been uploaded and is now pending administrator approval before verification, viewing, printing, or download.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Verification Document | US Capital Private Bank</title>
    <link rel="stylesheet" href="theme.css">
</head>
<body class="verify-portal">
    <div class="verify-shell">
        <div class="verify-topbar">
            <div class="verify-brand">
                <img src="https://www.uscapitalprivatebank.com/assets/images/logoIcon/logo.png" alt="US Capital Private Bank">
                <div class="verify-brand-copy">
                    <h1>US Capital Private Bank</h1>
                    <p>Document Upload Desk</p>
                </div>
            </div>
            <div class="verify-links">
                <a class="verify-link" href="https://www.uscapitalprivatebank.com/">Home</a>
                <a class="verify-link" href="index.php">Verification Home</a>
                <a class="verify-link" href="dashboard.php">Dashboard</a>
                <a class="verify-button-secondary" href="logout.php">Sign Out</a>
            </div>
        </div>

        <div class="verify-hero">
            <div class="verify-card">
                <div class="verify-card-inner">
                    <span class="verify-kicker">Authenticated Upload</span>
                    <h2 class="verify-title">Upload a new verifiable document.</h2>
                    <p class="verify-copy">
                        Signed in as <strong><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></strong>. Use this desk to place a file into the verification repository and assign the verification code that outside parties will use.
                        <?php if (!verify_is_admin()): ?>
                            New uploads remain pending until a verification administrator approves them.
                        <?php endif; ?>
                    </p>
                    <div class="verify-actions">
                        <a class="verify-button-secondary" href="dashboard.php">Back To Dashboard</a>
                        <a class="verify-button-secondary" href="index.php#code-verification">Open Code Lookup</a>
                        <a class="verify-button-secondary" href="documents.php">View Document Library</a>
                    </div>
                </div>
            </div>

            <div class="verify-card">
                <div class="verify-qr-panel">
                    <div style="width:100%;">
                        <h3 style="margin:0 0 16px; font-size:28px;">Upload guidance</h3>
                        <ul class="verify-feature-list">
                            <li>Use a clean document code if one has already been issued internally.</li>
                            <li>Leave the code blank to let the system generate one automatically.</li>
                            <li>Only administrator-approved documents can be opened through the secure document code or direct verification link.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="verify-card" style="margin-top:26px;">
            <div class="verify-card-inner">
                <?php if ($error !== ''): ?>
                    <div class="verify-alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if ($success !== ''): ?>
                    <div class="verify-alert success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" novalidate>
                    <div class="verify-grid">
                        <div>
                            <div class="verify-form-group">
                                <label class="verify-label" for="document_title">Document Title</label>
                                <input class="verify-input" id="document_title" name="document_title" type="text" value="<?= htmlspecialchars((string) ($_POST['document_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. Asset Confirmation Letter" required>
                            </div>
                            <div class="verify-form-group">
                                <label class="verify-label" for="document_code">Document Code</label>
                                <input class="verify-input" id="document_code" name="document_code" type="text" value="<?= htmlspecialchars((string) ($_POST['document_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Optional custom code, e.g. USCPB-2026-001">
                            </div>
                            <div class="verify-form-group">
                                <label class="verify-label" for="document_file">Document File</label>
                                <input class="verify-input" id="document_file" name="document_file" type="file" required>
                            </div>
                        </div>
                        <div>
                            <div class="verify-form-group">
                                <label class="verify-label" for="notes">Internal Notes</label>
                                <textarea class="verify-textarea" id="notes" name="notes" placeholder="Internal notes, desk comments, or issuing context"><?= htmlspecialchars((string) ($_POST['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="verify-actions">
                        <button class="verify-button" type="submit">Upload Document</button>
                    </div>
                </form>

                <?php if (is_array($uploaded)): ?>
                    <div class="verify-grid" style="margin-top:28px;">
                        <div class="verify-card" style="background:var(--verify-panel-soft);">
                            <div class="verify-card-inner">
                                <h3 style="margin:0 0 16px; font-size:24px;">Uploaded record</h3>
                                <div class="verify-meta-list">
                                    <div class="verify-meta-item"><strong>Title</strong><span><?= htmlspecialchars($uploaded['title'], ENT_QUOTES, 'UTF-8') ?></span></div>
                                    <div class="verify-meta-item"><strong>Document Code</strong><span><?= htmlspecialchars($uploaded['code'], ENT_QUOTES, 'UTF-8') ?></span></div>
                                    <div class="verify-meta-item"><strong>Stored File</strong><span><?= htmlspecialchars($uploaded['file'], ENT_QUOTES, 'UTF-8') ?></span></div>
                                    <div class="verify-meta-item"><strong>Status</strong><span><?= htmlspecialchars(verify_is_admin() ? 'Approved' : 'Pending Admin Approval', ENT_QUOTES, 'UTF-8') ?></span></div>
                                </div>
                            </div>
                        </div>
                        <div class="verify-card" style="background:var(--verify-panel-soft);">
                            <div class="verify-card-inner">
                                <h3 style="margin:0 0 16px; font-size:24px;">Next step</h3>
                                <?php if (verify_is_admin()): ?>
                                    <div class="verify-actions">
                                        <a class="verify-button" href="<?= htmlspecialchars($uploaded['print_legal_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Print Issued Copy</a>
                                        <a class="verify-button" href="<?= htmlspecialchars($uploaded['view_url'], ENT_QUOTES, 'UTF-8') ?>">Open Verified View</a>
                                        <a class="verify-button-secondary" href="<?= htmlspecialchars($uploaded['verify_url'], ENT_QUOTES, 'UTF-8') ?>">Test Document Code</a>
                                        <a class="verify-button-secondary" href="documents.php">Open Document Library</a>
                                    </div>
                                    <div class="verify-actions" style="margin-top:10px;">
                                        <a class="verify-button-secondary" href="<?= htmlspecialchars($uploaded['print_legal_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Print Legal</a>
                                        <a class="verify-button-secondary" href="<?= htmlspecialchars($uploaded['print_letter_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Print Letter</a>
                                        <a class="verify-button-secondary" href="<?= htmlspecialchars($uploaded['print_a4_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Print A4</a>
                                    </div>
                                <?php else: ?>
                                    <p class="verify-copy">This document is waiting for your administrator to approve it from the Admin Review desk. It will become viewable, printable, and verifiable after approval.</p>
                                    <div class="verify-actions">
                                        <a class="verify-button-secondary" href="documents.php">Open Document Library</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
