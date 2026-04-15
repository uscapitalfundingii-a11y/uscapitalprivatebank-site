<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/crm_verify_auth.php';

if (empty($_SESSION['upload_authenticated']) || empty($_SESSION['username'])) {
    header('Location: index.php?error=' . urlencode('Please sign in to access your uploaded documents.'));
    exit;
}

$username = (string) $_SESSION['username'];
$filesDir = __DIR__ . '/files';
$documents = verify_load_documents();
$entries = [];

if (is_dir($filesDir)) {
    $files = glob($filesDir . '/*.*') ?: [];
    foreach ($files as $path) {
        $file = basename($path);
        $meta = $documents[$file] ?? null;
        if (!is_array($meta) || (string) ($meta['uploaded_by'] ?? '') !== $username) {
            continue;
        }

        $code = (string) ($meta['code'] ?? pathinfo($file, PATHINFO_FILENAME));
        $entries[] = [
            'file' => $file,
            'title' => (string) ($meta['title'] ?? pathinfo($file, PATHINFO_FILENAME)),
            'code' => $code,
            'uploaded_at' => (string) ($meta['uploaded_at'] ?? date('c', filemtime($path))),
            'notes' => (string) ($meta['notes'] ?? ''),
            'status' => (string) ($meta['status'] ?? 'pending'),
            'allowed_roles' => verify_document_allowed_roles($meta),
            'view_url' => 'viewfile.php?file=' . rawurlencode($file),
            'print_url' => 'print.php?file=' . rawurlencode($file),
            'download_url' => 'download.php?file=' . rawurlencode($file),
            'verify_url' => 'verifycode.php?code=' . rawurlencode($code),
        ];
    }
}

usort($entries, static function ($a, $b) {
    return strcmp((string) ($b['uploaded_at'] ?? ''), (string) ($a['uploaded_at'] ?? ''));
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Uploaded Documents | US Capital Private Bank</title>
    <link rel="stylesheet" href="theme.css">
</head>
<body class="verify-portal">
    <div class="verify-shell">
        <div class="verify-topbar">
            <div class="verify-brand">
                <img src="https://www.uscapitalprivatebank.com/assets/images/logoIcon/logo.png" alt="US Capital Private Bank">
                <div class="verify-brand-copy">
                    <h1>US Capital Private Bank</h1>
                    <p>My Uploaded Documents</p>
                </div>
            </div>
            <div class="verify-links">
                <a class="verify-link" href="https://www.uscapitalprivatebank.com/">Home</a>
                <a class="verify-link" href="dashboard.php">Dashboard</a>
                <a class="verify-link" href="upload.php">Upload Desk</a>
                <?php if (verify_has_permission('view_repository')): ?>
                    <a class="verify-link" href="documents.php">Document Library</a>
                <?php endif; ?>
                <a class="verify-button-secondary" href="logout.php">Sign Out</a>
            </div>
        </div>

        <div class="verify-hero">
            <div class="verify-card">
                <div class="verify-card-inner">
                    <span class="verify-kicker">Private Workspace View</span>
                    <h2 class="verify-title">Your uploaded documents only.</h2>
                    <p class="verify-copy">
                        Signed in as <strong><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></strong>. This page shows only the verification documents you personally uploaded, so you do not need to open the full document library to track your own records.
                    </p>
                    <div class="verify-actions">
                        <a class="verify-button" href="upload.php">Upload Another Document</a>
                        <a class="verify-button-secondary" href="dashboard.php">Back To Dashboard</a>
                    </div>
                </div>
            </div>

            <div class="verify-card">
                <div class="verify-qr-panel">
                    <div style="width:100%;">
                        <h3 style="margin:0 0 16px; font-size:28px;">Your summary</h3>
                        <div class="verify-meta-list">
                            <div class="verify-meta-item"><strong>Uploaded Records</strong><span><?= count($entries) ?></span></div>
                            <div class="verify-meta-item"><strong>Visible Scope</strong><span>Only your uploads</span></div>
                            <div class="verify-meta-item"><strong>Approved Records</strong><span><?= count(array_filter($entries, static fn($entry) => ($entry['status'] ?? '') === 'approved')) ?></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="verify-card" style="margin-top:26px;">
            <div class="verify-card-inner">
                <?php if (empty($entries)): ?>
                    <div class="verify-empty">You have not uploaded any documents yet.</div>
                <?php else: ?>
                    <table class="verify-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Document Code</th>
                                <th>Status</th>
                                <th>Uploaded</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entries as $entry): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($entry['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <?php if ($entry['notes'] !== ''): ?>
                                            <div style="margin-top:6px; color:var(--verify-muted);"><?= htmlspecialchars($entry['notes'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                        <div style="margin-top:6px; color:var(--verify-muted);"><strong>Access groups:</strong> <?= htmlspecialchars(empty($entry['allowed_roles']) ? 'All approved groups' : implode(', ', array_map(static fn($role) => ucwords(str_replace('_', ' ', $role)), $entry['allowed_roles'])), ENT_QUOTES, 'UTF-8') ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($entry['code'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="verify-status <?= htmlspecialchars($entry['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($entry['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><?= htmlspecialchars(date('M j, Y g:i A', strtotime($entry['uploaded_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <?php if ($entry['status'] === 'approved'): ?>
                                            <a class="verify-link" href="<?= htmlspecialchars($entry['view_url'], ENT_QUOTES, 'UTF-8') ?>">View</a>
                                            <a class="verify-link" href="<?= htmlspecialchars($entry['download_url'], ENT_QUOTES, 'UTF-8') ?>">Download</a>
                                            <a class="verify-link" href="<?= htmlspecialchars($entry['print_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Print</a>
                                            <a class="verify-link" href="<?= htmlspecialchars($entry['verify_url'], ENT_QUOTES, 'UTF-8') ?>">Verify</a>
                                        <?php else: ?>
                                            <span class="verify-link" style="opacity:.7; cursor:default;">Awaiting Admin Review</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
