<?php
session_start();
require_once __DIR__ . '/crm_verify_auth.php';

if (empty($_SESSION['upload_authenticated']) || empty($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

$username = (string) $_SESSION['username'];
$userRole = verify_current_role();
$canUpload = verify_has_permission('upload_documents');
$canReviewDocuments = verify_has_permission('review_documents');
$canApproveDocuments = verify_has_permission('approve_documents');
$canManageUsers = verify_has_any_permission(['manage_users', 'manage_role_permissions', 'manage_user_permissions']);
$canUseIdCards = verify_has_permission('manage_id_cards');
$canUseCertificates = verify_has_permission('manage_certificates');
$pendingRequests = 0;
$pendingDocuments = 0;
$pageError = trim((string) ($_GET['error'] ?? ''));

if ($canManageUsers) {
    foreach (verify_load_users() as $accountName => $entry) {
        if ($accountName === 'admin' || !is_array($entry)) {
            continue;
        }

        if ((string) ($entry['status'] ?? 'pending') === 'pending') {
            $pendingRequests++;
        }
    }

    foreach (verify_load_documents() as $document) {
        if (!verify_is_document_approved($document)) {
            $pendingDocuments++;
        }
    }
}
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
                <?php if ($canManageUsers): ?>
                    <a class="verify-link" href="admin/index.php">Admin Review<?= $pendingRequests > 0 ? ' (' . $pendingRequests . ')' : '' ?></a>
                <?php endif; ?>
                <?php if ($canUseIdCards): ?>
                    <a class="verify-link" href="admin/idcards.php">ID Card Studio</a>
                <?php endif; ?>
                <?php if ($canUseCertificates): ?>
                    <a class="verify-link" href="admin/certificates.php">Certificate Studio</a>
                <?php endif; ?>
                <a class="verify-button-secondary" href="logout.php">Sign Out</a>
            </div>
        </div>

        <div class="verify-hero">
            <div class="verify-card">
                <div class="verify-card-inner">
                    <?php if ($pageError !== ''): ?>
                        <div class="verify-alert error"><?= htmlspecialchars($pageError, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <span class="verify-kicker">Approved Session</span>
                    <h2 class="verify-title">Welcome back, <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>.</h2>
                    <p class="verify-copy">
                        This secure dashboard is used to access the bank's document verification utilities, verify issued records by secure code or QR reference,
                        and manage role-appropriate access to the document repository.
                    </p>
                    <div class="verify-actions">
                        <?php if ($canUpload): ?>
                            <a class="verify-button" href="upload.php">Go To Upload Access</a>
                        <?php endif; ?>
                        <a class="verify-button<?= $canUpload ? '-secondary' : '' ?>" href="documents.php">View Document Library</a>
                        <?php if ($canManageUsers): ?>
                            <a class="verify-button-secondary" href="admin/index.php">Review Access Requests<?= $pendingRequests > 0 ? ' (' . $pendingRequests . ')' : '' ?></a>
                        <?php endif; ?>
                        <?php if ($canUseIdCards): ?>
                            <a class="verify-button-secondary" href="admin/idcards.php">Create Employee ID Cards</a>
                        <?php endif; ?>
                        <?php if ($canUseCertificates): ?>
                            <a class="verify-button-secondary" href="admin/certificates.php">Create Certificates</a>
                        <?php endif; ?>
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
                            <?php if ($canManageUsers): ?>
                                <div class="verify-meta-item"><strong>Pending Requests</strong><span><?= htmlspecialchars((string) $pendingRequests, ENT_QUOTES, 'UTF-8') ?></span></div>
                            <?php endif; ?>
                            <?php if ($canApproveDocuments): ?>
                                <div class="verify-meta-item"><strong>Pending Documents</strong><span><?= htmlspecialchars((string) $pendingDocuments, ENT_QUOTES, 'UTF-8') ?></span></div>
                            <?php endif; ?>
                            <div class="verify-meta-item"><strong>Routing</strong><span><?= htmlspecialchars($canManageUsers || $canApproveDocuments ? 'Code lookup, repository access, upload desk, and controlled review tools' : 'Code lookup, upload desk, and repository access', ENT_QUOTES, 'UTF-8') ?></span></div>
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
                            <li>Upload new issued documents into the verification workspace.</li>
                        <?php endif; ?>
                        <?php if ($canManageUsers): ?>
                            <li>Approve or reject new verification user registrations from the built-in admin review desk.</li>
                        <?php endif; ?>
                        <?php if ($canApproveDocuments): ?>
                            <li>Approve or reject uploaded documents before they can be verified, viewed, printed, or downloaded.</li>
                        <?php endif; ?>
                        <?php if ($canUseIdCards): ?>
                            <li>Create employee ID cards with QR codes that resolve back to the verification site for live credential validation.</li>
                        <?php endif; ?>
                        <?php if ($canUseCertificates): ?>
                            <li>Generate bank certificates and awards with QR codes that resolve to live verification pages.</li>
                        <?php endif; ?>
                        <?php if (!$canManageUsers && !$canApproveDocuments && !$canUseIdCards && !$canUseCertificates): ?>
                            <li>Review approved documents already in the repository and track your own pending uploads.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <div class="verify-card">
                <div class="verify-card-inner">
                    <h3 style="margin:0 0 14px; font-size:24px;">Operational note</h3>
                    <p class="verify-copy">
                        This dashboard is intentionally minimal and secure. Only approved documents become publicly verifiable, printable, or downloadable through the verification links.
                        <?php if ($canManageUsers || $canApproveDocuments): ?>
                            Use your review tools to approve pending users and documents for this verification workspace.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
