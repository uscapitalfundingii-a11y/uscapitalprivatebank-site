<?php
session_start();
require_once __DIR__ . '/crm_verify_auth.php';

if (empty($_SESSION['upload_authenticated']) || empty($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

$username = (string) $_SESSION['username'];
$userRole = verify_current_role();
$canUpload = verify_is_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Dashboard | US Capital Private Bank</title>
    <link rel="stylesheet" href="theme.css">
</head>
<body class="verify-portal">
    <div class="verify-shell">
        <div class="verify-topbar">
            <div class="verify-brand">
                <img src="https://www.uscapitalprivatebank.com/assets/images/logoIcon/logo.png" alt="US Capital Private Bank">
                <div class="verify-brand-copy">
                    <h1>US Capital Private Bank</h1>
                    <p>Verification Workspace</p>
                </div>
            </div>
            <div class="verify-links">
                <a class="verify-link" href="https://www.uscapitalprivatebank.com/">Home</a>
                <a class="verify-link" href="https://www.uscapitalprivatebank.com/support">Support</a>
                <a class="verify-link" href="index.php">Verification Home</a>
                <a class="verify-button-secondary" href="logout.php">Sign Out</a>
            </div>
        </div>

        <div class="verify-hero">
            <div class="verify-card">
                <div class="verify-card-inner">
                    <span class="verify-kicker">Approved Session</span>
                    <h2 class="verify-title">Welcome back, <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>.</h2>
                    <p class="verify-copy">
                        This secure dashboard is used to access the bank's document verification utilities, verify issued records by secure code or QR reference,
                        and manage role-appropriate access to the document repository.
                    </p>
                    <div class="verify-actions">
                        <?php if ($canUpload): ?>
                            <a class="verify-button" href="upload.php">Go To Upload Access</a>
                        <?php else: ?>
                            <a class="verify-button" href="documents.php">Open Document Library</a>
                        <?php endif; ?>
                        <a class="verify-button-secondary" href="documents.php">View Document Library</a>
                        <a class="verify-button-secondary" href="index.php#code-verification">Verify A Document</a>
                    </div>
                </div>
            </div>

            <div class="verify-card">
                <div class="verify-qr-panel">
                    <div style="width:100%;">
                        <h3 style="margin:0 0 16px; font-size:28px;">Secure workflow</h3>
                        <div class="verify-meta-list">
                            <div class="verify-meta-item"><strong>Session Status</strong><span>Authenticated</span></div>
                            <div class="verify-meta-item"><strong>Workspace</strong><span>Verification Operations</span></div>
                            <div class="verify-meta-item"><strong>Role</strong><span><?= htmlspecialchars($userRole !== '' ? ucfirst($userRole) : 'User', ENT_QUOTES, 'UTF-8') ?></span></div>
                            <div class="verify-meta-item"><strong>Routing</strong><span><?= htmlspecialchars($canUpload ? 'Code lookup, QR validation, upload desk, and library access' : 'Code lookup, QR validation, and library access', ENT_QUOTES, 'UTF-8') ?></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="verify-grid" style="margin-top:26px;">
            <div class="verify-card">
                <div class="verify-card-inner">
                    <h3 style="margin:0 0 14px; font-size:24px;">Available tools</h3>
                    <ul class="verify-feature-list">
                        <li>Verify documents instantly using a secure code issued with the file.</li>
                        <li>Open QR-enabled documents in a live viewer with traceable verification links.</li>
                        <?php if ($canUpload): ?>
                            <li>Upload new issued documents and maintain the repository as a verification administrator.</li>
                        <?php else: ?>
                            <li>Review and print documents already in the repository as an approved trustee without upload privileges.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <div class="verify-card">
                <div class="verify-card-inner">
                    <h3 style="margin:0 0 14px; font-size:24px;">Operational note</h3>
                    <p class="verify-copy">
                        This dashboard is intentionally minimal and secure. Upload rights are limited to verification administrators.
                        Trustees and other approved viewers can review and print records already on file.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
