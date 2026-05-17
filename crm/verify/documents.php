<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
set_time_limit(0);
require_once __DIR__ . '/crm_verify_auth.php';

if (empty($_SESSION['upload_authenticated']) || empty($_SESSION['username'])) {
    header('Location: index.php?error=' . urlencode('Please sign in to access the document library.'));
    exit;
}

$username = (string) $_SESSION['username'];
$roleLabels = [
    'admin' => 'Admin',
    'trustee' => 'Trustee',
    'client' => 'Representative',
    'customer' => 'Customer',
    'bank_officer' => 'Bank Officer',
];
$currentRoleKey = verify_current_role();
$currentRole = $roleLabels[$currentRoleKey] ?? ucwords(str_replace('_', ' ', $currentRoleKey));
$canViewRepository = verify_has_permission('view_repository');
$canReviewDocuments = verify_has_permission('review_documents');
$canApproveDocuments = verify_has_permission('approve_documents');
$canEditDocuments = verify_has_permission('edit_documents');
$canReplaceDocuments = verify_has_permission('replace_documents');
$canDeleteDocuments = verify_has_permission('delete_documents');
$canPrintDocuments = verify_has_permission('print_documents');
$canDownloadDocuments = verify_has_permission('download_documents');
$roleOptions = array_filter(verify_available_roles(), static fn($role) => $role !== 'admin');
$approvedUsers = [];
foreach (verify_load_users() as $accountName => $entry) {
    if ($accountName === 'admin' || !is_array($entry) || (string) ($entry['status'] ?? 'pending') !== 'approved') {
        continue;
    }
    $approvedUsers[] = (string) $accountName;
}
$filesDir = __DIR__ . '/files';
$documents = verify_load_documents();

$message = '';
$error = '';

if (!$canViewRepository) {
    header('Location: dashboard.php?error=' . urlencode('Your account is not permitted to open the verification document library.'));
    exit;
}

function verify_replace_uploaded_document_file(array $file, string $targetPath, string $expectedExtension): bool
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
        return false;
    }

    $incomingExtension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    if ($incomingExtension !== strtolower($expectedExtension)) {
        return false;
    }

    $tempPath = $targetPath . '.replacement-' . substr(md5(uniqid((string) mt_rand(), true)), 0, 8);
    if (!move_uploaded_file((string) $file['tmp_name'], $tempPath)) {
        return false;
    }

    $backupPath = $targetPath . '.backup-' . substr(md5(uniqid((string) mt_rand(), true)), 0, 8);
    if (is_file($targetPath) && !@rename($targetPath, $backupPath)) {
        @unlink($tempPath);
        return false;
    }

    if (!@rename($tempPath, $targetPath)) {
        if (is_file($backupPath)) {
            @rename($backupPath, $targetPath);
        }
        @unlink($tempPath);
        return false;
    }

    if (is_file($backupPath)) {
        @unlink($backupPath);
    }

    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_file'])) {
    if (!$canApproveDocuments) {
        $error = 'Your account is not permitted to approve verification documents.';
    } else {
        $targetFile = basename((string) $_POST['approve_file']);
        if (!isset($documents[$targetFile])) {
            $error = 'That document could not be found for approval.';
        } else {
            $documents[$targetFile]['status'] = 'approved';
            $documents[$targetFile]['approved_by'] = $username;
            $documents[$targetFile]['approved_at'] = date('c');
            $documents[$targetFile]['rejection_note'] = '';
            verify_save_documents($documents);
            $message = 'The document has been approved and is now live through its existing verification code, link, and QR flow.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_file'])) {
    if (!$canApproveDocuments) {
        $error = 'Your account is not permitted to reject verification documents.';
    } else {
        $targetFile = basename((string) $_POST['reject_file']);
        if (!isset($documents[$targetFile])) {
            $error = 'That document could not be found for rejection.';
        } else {
            $documents[$targetFile]['status'] = 'rejected';
            $documents[$targetFile]['approved_by'] = '';
            $documents[$targetFile]['approved_at'] = '';
            $documents[$targetFile]['rejection_note'] = trim((string) ($_POST['rejection_note'] ?? ''));
            verify_save_documents($documents);
            $message = 'The document has been rejected and removed from the live verification flow.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file'])) {
    if (!$canDeleteDocuments) {
        $error = 'Your account is not permitted to delete repository documents.';
    } else {
    $deleteFile = basename((string) $_POST['delete_file']);
    $deletePath = $filesDir . '/' . $deleteFile;

        if ($deleteFile === '' || !is_file($deletePath)) {
            $error = 'That document could not be found for deletion.';
        } else {
            @unlink($deletePath);

            if (isset($documents[$deleteFile])) {
                unset($documents[$deleteFile]);
                verify_save_documents($documents);
            }

            $viewQr = __DIR__ . '/tempqr/' . md5($deleteFile) . '.png';
            $printQr = __DIR__ . '/tempqr/' . md5($deleteFile . '-print') . '.png';
            @unlink($viewQr);
            @unlink($printQr);

            $message = 'The document, its stored code record, and generated QR images were removed from the verification system.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_file'])) {
    if (!$canEditDocuments) {
        $error = 'Your account is not permitted to edit repository documents.';
    } else {
        $targetFile = basename((string) $_POST['update_file']);
        if (!isset($documents[$targetFile])) {
            $error = 'That document could not be found for editing.';
        } else {
            $updatedTitle = trim((string) ($_POST['title'] ?? ''));
            $updatedNotes = trim((string) ($_POST['notes'] ?? ''));
            $updatedCode = trim((string) ($_POST['code'] ?? ''));
            $updatedCode = preg_replace('/[^a-zA-Z0-9\-_]/', '', $updatedCode ?? '') ?: '';

            if ($updatedTitle === '') {
                $updatedTitle = pathinfo($targetFile, PATHINFO_FILENAME);
            }

            if ($updatedCode === '') {
                $updatedCode = (string) ($documents[$targetFile]['code'] ?? pathinfo($targetFile, PATHINFO_FILENAME));
            }

            $documents[$targetFile]['title'] = $updatedTitle;
            $documents[$targetFile]['notes'] = $updatedNotes;
            $documents[$targetFile]['code'] = $updatedCode;
            $documents[$targetFile]['uploaded_by'] = (string) ($documents[$targetFile]['uploaded_by'] ?? $username);
            $documents[$targetFile]['uploaded_at'] = (string) ($documents[$targetFile]['uploaded_at'] ?? date('c'));
            $documents[$targetFile]['allowed_roles'] = verify_normalize_roles($_POST['allowed_roles'] ?? [], '');
            $documents[$targetFile]['allowed_users'] = verify_normalize_document_users($_POST['allowed_users'] ?? []);

            verify_save_documents($documents);

            $message = 'The verification record was updated successfully.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['replace_file'])) {
    if (!$canReplaceDocuments) {
        $error = 'Your account is not permitted to replace document revisions.';
    } else {
        $targetFile = basename((string) $_POST['replace_file']);
        $targetPath = $filesDir . '/' . $targetFile;
        if (!isset($documents[$targetFile]) || !is_file($targetPath)) {
            $error = 'That document could not be found for replacement.';
        } elseif (!isset($_FILES['replacement_file'])) {
            $error = 'Please choose a replacement file.';
        } else {
            $expectedExtension = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            if (!verify_replace_uploaded_document_file($_FILES['replacement_file'], $targetPath, $expectedExtension)) {
                $error = 'The replacement file could not be applied. Use the same file type as the current document and try again.';
            } else {
                $documents[$targetFile]['status'] = 'approved';
                $documents[$targetFile]['approved_by'] = $username;
                $documents[$targetFile]['approved_at'] = date('c');
                $documents[$targetFile]['rejection_note'] = '';
                verify_save_documents($documents);
                $message = 'The document revision was replaced successfully. The document code, verification link, and QR destination were preserved.';
            }
        }
    }
}

$entries = [];
if (is_dir($filesDir)) {
    $files = glob($filesDir . '/*.*') ?: [];
    foreach ($files as $path) {
        $file = basename($path);
        $meta = $documents[$file] ?? [];
        $allowedRoles = verify_document_allowed_roles($meta);
        $allowedUsers = verify_document_allowed_users($meta);
        if (!$canReviewDocuments) {
            $status = (string) ($meta['status'] ?? 'approved');
            $uploadedBy = (string) ($meta['uploaded_by'] ?? '');
            if ($status !== 'approved' && $uploadedBy !== $username) {
                continue;
            }
            if ($status === 'approved' && !verify_document_matches_roles($meta, verify_current_roles())) {
                continue;
            }
        }
        $code = (string) ($meta['code'] ?? pathinfo($file, PATHINFO_FILENAME));
        $entries[] = [
            'file' => $file,
            'title' => (string) ($meta['title'] ?? pathinfo($file, PATHINFO_FILENAME)),
            'code' => $code,
            'uploaded_by' => (string) ($meta['uploaded_by'] ?? 'Unknown'),
            'uploaded_at' => (string) ($meta['uploaded_at'] ?? date('c', filemtime($path))),
            'notes' => (string) ($meta['notes'] ?? ''),
            'status' => (string) ($meta['status'] ?? 'approved'),
            'approved_by' => (string) ($meta['approved_by'] ?? ''),
            'approved_at' => (string) ($meta['approved_at'] ?? ''),
            'rejection_note' => (string) ($meta['rejection_note'] ?? ''),
            'allowed_roles' => $allowedRoles,
            'allowed_users' => $allowedUsers,
            'view_url' => 'viewfile.php?file=' . rawurlencode($file),
            'review_url' => 'reviewfile.php?file=' . rawurlencode($file),
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
    <title>Verification Document Library | US Capital Private Bank</title>
    <link rel="stylesheet" href="theme.css">
</head>
<body class="verify-portal">
    <div class="verify-shell">
        <div class="verify-topbar">
            <div class="verify-brand">
                <img src="https://www.uscapitalprivatebank.com/assets/images/logoIcon/logo.png" alt="US Capital Private Bank">
                <div class="verify-brand-copy">
                    <h1>US Capital Private Bank</h1>
                    <p>Verification Document Library</p>
                </div>
            </div>
            <div class="verify-links">
                <a class="verify-link" href="https://www.uscapitalprivatebank.com/">Home</a>
                <a class="verify-link" href="dashboard.php">Dashboard</a>
                <?php if (verify_has_permission('upload_documents')): ?>
                    <a class="verify-link" href="upload.php">Upload Desk</a>
                <?php endif; ?>
                <a class="verify-button-secondary" href="logout.php">Sign Out</a>
            </div>
        </div>

        <div class="verify-hero">
            <div class="verify-card">
                <div class="verify-card-inner">
                    <span class="verify-kicker">Repository View</span>
                    <h2 class="verify-title">All uploaded verification documents in one place.</h2>
                    <p class="verify-copy">
                        Signed in as <strong><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></strong>. This library shows the current verification records stored in the system.
                        Only administrator-approved documents can be verified, viewed, printed, or downloaded publicly.
                    </p>
                    <div class="verify-actions">
                        <?php if (verify_has_permission('upload_documents')): ?>
                            <a class="verify-button" href="upload.php">Upload New Document</a>
                        <?php endif; ?>
                        <a class="verify-button-secondary" href="index.php#code-verification">Open Code Lookup</a>
                    </div>
                </div>
            </div>

            <div class="verify-card">
                <div class="verify-qr-panel">
                    <div style="width:100%;">
                        <h3 style="margin:0 0 16px; font-size:28px;">Library summary</h3>
                        <div class="verify-meta-list">
                            <div class="verify-meta-item"><strong>Visible Records</strong><span><?= count($entries) ?></span></div>
                            <div class="verify-meta-item"><strong>Access Level</strong><span><?= htmlspecialchars($currentRole, ENT_QUOTES, 'UTF-8') ?></span></div>
                            <div class="verify-meta-item"><strong>Available Actions</strong><span><?= htmlspecialchars(
                                $canApproveDocuments || $canEditDocuments || $canReplaceDocuments || $canDeleteDocuments
                                    ? 'Review workflow, document controls, and live link preservation'
                                    : 'Track visible records and use approved verification links',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?></span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="verify-card" style="margin-top:26px;">
            <div class="verify-card-inner">
                <?php if ($error !== ''): ?>
                    <div class="verify-alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if ($message !== ''): ?>
                    <div class="verify-alert success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:end; margin-bottom:24px;">
                    <div style="flex:1 1 360px;">
                        <label class="verify-label" for="library-search">Search documents</label>
                        <input class="verify-input" id="library-search" type="search" placeholder="Search by title, code, uploader, or date...">
                    </div>
                    <div style="min-width:220px;">
                        <div class="verify-label">Live filter</div>
                        <div id="library-search-status" class="verify-empty" style="padding:14px 16px;">Showing all <?= count($entries) ?> records.</div>
                    </div>
                </div>
                <?php if (empty($entries)): ?>
                    <div class="verify-empty">No documents are visible yet. Upload the first record from the upload desk.</div>
                <?php else: ?>
                    <table class="verify-table" id="document-library-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Document Code</th>
                                <th>Status</th>
                                <th>Uploaded By</th>
                                <th>Uploaded</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($entries as $entry): ?>
                                <?php
                                $searchBlob = implode(' ', [
                                    $entry['title'],
                                    $entry['code'],
                                    $entry['uploaded_by'],
                                    date('M j, Y g:i A', strtotime($entry['uploaded_at'])),
                                    date('Y-m-d H:i:s', strtotime($entry['uploaded_at'])),
                                    $entry['notes'],
                                    implode(' ', $entry['allowed_roles']),
                                    implode(' ', $entry['allowed_users']),
                                ]);
                                ?>
                                <tr data-search="<?= htmlspecialchars(mb_strtolower($searchBlob), ENT_QUOTES, 'UTF-8') ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($entry['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <?php if ($entry['notes'] !== ''): ?>
                                            <div style="margin-top:6px; color:var(--verify-muted);"><?= htmlspecialchars($entry['notes'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                        <div style="margin-top:6px; color:var(--verify-muted);"><strong>Access groups:</strong> <?= htmlspecialchars(empty($entry['allowed_roles']) ? 'All approved groups' : implode(', ', array_map(static fn($role) => $roleLabels[$role] ?? ucwords(str_replace('_', ' ', $role)), $entry['allowed_roles'])), ENT_QUOTES, 'UTF-8') ?></div>
                                        <div style="margin-top:6px; color:var(--verify-muted);"><strong>Allowed users:</strong> <?= htmlspecialchars(empty($entry['allowed_users']) ? 'No user-only list' : implode(', ', $entry['allowed_users']), ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php if ($entry['rejection_note'] !== ''): ?>
                                            <div style="margin-top:6px; color:var(--verify-danger);"><strong>Admin note:</strong> <?= htmlspecialchars($entry['rejection_note'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($entry['code'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="verify-status <?= htmlspecialchars($entry['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($entry['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td><?= htmlspecialchars($entry['uploaded_by'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars(date('M j, Y g:i A', strtotime($entry['uploaded_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <?php if ($canReviewDocuments): ?>
                                            <a class="verify-link" href="<?= htmlspecialchars($entry['review_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">View File</a>
                                        <?php endif; ?>
                                        <?php if ($entry['status'] === 'approved'): ?>
                                            <?php if ($canPrintDocuments): ?>
                                                <a class="verify-link" href="<?= htmlspecialchars($entry['print_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Print</a>
                                            <?php endif; ?>
                                            <a class="verify-link" href="<?= htmlspecialchars($entry['view_url'], ENT_QUOTES, 'UTF-8') ?>">View</a>
                                            <?php if ($canDownloadDocuments): ?>
                                                <a class="verify-link" href="<?= htmlspecialchars($entry['download_url'], ENT_QUOTES, 'UTF-8') ?>">Download</a>
                                            <?php endif; ?>
                                            <a class="verify-link" href="<?= htmlspecialchars($entry['verify_url'], ENT_QUOTES, 'UTF-8') ?>">Verify</a>
                                        <?php else: ?>
                                            <span class="verify-link" style="opacity:.7; cursor:default;">Awaiting Admin Review</span>
                                        <?php endif; ?>
                                        <?php if ($canApproveDocuments): ?>
                                            <form class="verify-inline-form" method="post" style="display:inline-flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                                <input type="hidden" name="approve_file" value="<?= htmlspecialchars($entry['file'], ENT_QUOTES, 'UTF-8') ?>">
                                                <button class="verify-link" type="submit" style="cursor:pointer;">Approve</button>
                                            </form>
                                            <button class="verify-link" type="button" style="cursor:pointer;" data-edit-target="review-<?= htmlspecialchars(md5($entry['file']), ENT_QUOTES, 'UTF-8') ?>">Reject</button>
                                        <?php endif; ?>
                                        <?php if ($canEditDocuments): ?>
                                            <button class="verify-link" type="button" style="cursor:pointer;" data-edit-target="edit-<?= htmlspecialchars(md5($entry['file']), ENT_QUOTES, 'UTF-8') ?>">Edit</button>
                                        <?php endif; ?>
                                        <?php if ($canReplaceDocuments): ?>
                                            <button class="verify-link" type="button" style="cursor:pointer;" data-edit-target="replace-<?= htmlspecialchars(md5($entry['file']), ENT_QUOTES, 'UTF-8') ?>">Replace</button>
                                        <?php endif; ?>
                                        <?php if ($canDeleteDocuments): ?>
                                            <form class="verify-inline-form" method="post" onsubmit="return confirm('Delete this document and remove its verification code from the system?');">
                                                <input type="hidden" name="delete_file" value="<?= htmlspecialchars($entry['file'], ENT_QUOTES, 'UTF-8') ?>">
                                                <button class="verify-link" type="submit" style="cursor:pointer;">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php if ($canApproveDocuments): ?>
                                    <tr id="review-<?= htmlspecialchars(md5($entry['file']), ENT_QUOTES, 'UTF-8') ?>" data-search="<?= htmlspecialchars(mb_strtolower($searchBlob), ENT_QUOTES, 'UTF-8') ?>" style="display:none;">
                                        <td colspan="6">
                                            <form method="post" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px; align-items:end;">
                                                <input type="hidden" name="reject_file" value="<?= htmlspecialchars($entry['file'], ENT_QUOTES, 'UTF-8') ?>">
                                                <div style="grid-column: 1 / -1;">
                                                    <label class="verify-label">Rejection note</label>
                                                    <textarea class="verify-textarea" name="rejection_note" rows="3" placeholder="Optional note explaining what must be corrected before this document can go live."><?= htmlspecialchars($entry['rejection_note'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                                </div>
                                                <div style="display:flex; gap:12px; flex-wrap:wrap;">
                                                    <button class="verify-button" type="submit">Reject Document</button>
                                                    <button class="verify-button-secondary" type="button" data-edit-target="review-<?= htmlspecialchars(md5($entry['file']), ENT_QUOTES, 'UTF-8') ?>">Cancel</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ($canEditDocuments): ?>
                                    <tr id="edit-<?= htmlspecialchars(md5($entry['file']), ENT_QUOTES, 'UTF-8') ?>" data-search="<?= htmlspecialchars(mb_strtolower($searchBlob), ENT_QUOTES, 'UTF-8') ?>" style="display:none;">
                                        <td colspan="6">
                                            <form method="post" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px; align-items:end;">
                                                <input type="hidden" name="update_file" value="<?= htmlspecialchars($entry['file'], ENT_QUOTES, 'UTF-8') ?>">
                                                <div>
                                                    <label class="verify-label">Document title</label>
                                                    <input class="verify-input" type="text" name="title" value="<?= htmlspecialchars($entry['title'], ENT_QUOTES, 'UTF-8') ?>">
                                                </div>
                                                <div>
                                                    <label class="verify-label">Document code</label>
                                                    <input class="verify-input" type="text" name="code" value="<?= htmlspecialchars($entry['code'], ENT_QUOTES, 'UTF-8') ?>">
                                                </div>
                                                <div style="grid-column: 1 / -1;">
                                                    <label class="verify-label">Notes</label>
                                                    <textarea class="verify-textarea" name="notes" rows="3" placeholder="Add internal notes or document context..."><?= htmlspecialchars($entry['notes'], ENT_QUOTES, 'UTF-8') ?></textarea>
                                                </div>
                                                <div style="grid-column: 1 / -1;">
                                                    <label class="verify-label">Allowed role groups</label>
                                                    <div style="display:flex; flex-wrap:wrap; gap:12px;">
                                                        <?php foreach ($roleOptions as $roleValue): ?>
                                                            <label style="display:flex; gap:10px; align-items:flex-start; min-width:180px; padding:12px 14px; border-radius:16px; border:1px solid rgba(148, 163, 184, 0.16); background:rgba(15, 23, 42, 0.28);">
                                                                <input type="checkbox" name="allowed_roles[]" value="<?= htmlspecialchars($roleValue, ENT_QUOTES, 'UTF-8') ?>"<?= in_array($roleValue, $entry['allowed_roles'], true) ? ' checked' : '' ?> style="margin-top:4px;">
                                                                <span>
                                                                    <span style="display:block; font-weight:700; color:#f8fafc;"><?= htmlspecialchars($roleLabels[$roleValue] ?? ucwords(str_replace('_', ' ', $roleValue)), ENT_QUOTES, 'UTF-8') ?></span>
                                                                    <span style="display:block; margin-top:4px; font-size:13px; color:rgba(226, 232, 240, 0.72);"><?= htmlspecialchars($roleValue, ENT_QUOTES, 'UTF-8') ?></span>
                                                                </span>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <div style="margin-top:10px; color:var(--verify-muted);">Leave every group unselected to allow all approved role groups to use this document once approved.</div>
                                                </div>
                                                <div style="grid-column: 1 / -1;">
                                                    <label class="verify-label">Allowed individual users</label>
                                                    <div style="display:flex; flex-wrap:wrap; gap:12px;">
                                                        <?php foreach ($approvedUsers as $approvedUser): ?>
                                                            <label style="display:flex; gap:10px; align-items:flex-start; min-width:220px; padding:12px 14px; border-radius:16px; border:1px solid rgba(148, 163, 184, 0.16); background:rgba(15, 23, 42, 0.28);">
                                                                <input type="checkbox" name="allowed_users[]" value="<?= htmlspecialchars($approvedUser, ENT_QUOTES, 'UTF-8') ?>"<?= in_array($approvedUser, $entry['allowed_users'], true) ? ' checked' : '' ?> style="margin-top:4px;">
                                                                <span style="display:block; font-weight:700; color:#f8fafc;"><?= htmlspecialchars($approvedUser, ENT_QUOTES, 'UTF-8') ?></span>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <div style="margin-top:10px; color:var(--verify-muted);">Use this list for exact user-by-user access. Anyone left unselected will not get access through the individual allow list.</div>
                                                </div>
                                                <div style="display:flex; gap:12px; flex-wrap:wrap;">
                                                    <button class="verify-button" type="submit">Save Changes</button>
                                                    <button class="verify-button-secondary" type="button" data-edit-target="edit-<?= htmlspecialchars(md5($entry['file']), ENT_QUOTES, 'UTF-8') ?>">Cancel</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ($canReplaceDocuments): ?>
                                    <tr id="replace-<?= htmlspecialchars(md5($entry['file']), ENT_QUOTES, 'UTF-8') ?>" data-search="<?= htmlspecialchars(mb_strtolower($searchBlob), ENT_QUOTES, 'UTF-8') ?>" style="display:none;">
                                        <td colspan="6">
                                            <form method="post" enctype="multipart/form-data" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px; align-items:end;">
                                                <input type="hidden" name="replace_file" value="<?= htmlspecialchars($entry['file'], ENT_QUOTES, 'UTF-8') ?>">
                                                <div>
                                                    <label class="verify-label">Replace stored revision</label>
                                                    <input class="verify-input" type="file" name="replacement_file" required>
                                                </div>
                                                <div style="grid-column: 1 / -1; color:var(--verify-muted);">
                                                    Replacing a document keeps the same stored filename, document code, verification link, and QR destination. Use the same file type as the original file.
                                                </div>
                                                <div style="display:flex; gap:12px; flex-wrap:wrap;">
                                                    <button class="verify-button" type="submit">Replace Revision</button>
                                                    <button class="verify-button-secondary" type="button" data-edit-target="replace-<?= htmlspecialchars(md5($entry['file']), ENT_QUOTES, 'UTF-8') ?>">Cancel</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        (function () {
            const input = document.getElementById('library-search');
            const status = document.getElementById('library-search-status');
            const table = document.getElementById('document-library-table');
            if (!input || !status || !table) {
                return;
            }

            const rows = Array.from(table.querySelectorAll('tbody tr'));
            const dataRows = rows.filter((row) => !row.id);
            const total = dataRows.length;

            const render = () => {
                const query = input.value.trim().toLowerCase();
                let visible = 0;

                dataRows.forEach((row) => {
                    const haystack = row.getAttribute('data-search') || '';
                    const match = query === '' || haystack.includes(query);
                    row.style.display = match ? '' : 'none';
                    const editTarget = row.nextElementSibling && row.nextElementSibling.id ? row.nextElementSibling : null;
                    if (!match && editTarget) {
                        editTarget.style.display = 'none';
                    }
                    if (match) {
                        visible += 1;
                    }
                });

                status.textContent = query === ''
                    ? `Showing all ${total} records.`
                    : `Showing ${visible} of ${total} matching records.`;
            };

            input.addEventListener('input', render);

            document.querySelectorAll('[data-edit-target]').forEach((button) => {
                button.addEventListener('click', () => {
                    const targetId = button.getAttribute('data-edit-target');
                    const row = targetId ? document.getElementById(targetId) : null;
                    if (!row) {
                        return;
                    }
                    const isOpen = row.style.display !== 'none';
                    document.querySelectorAll('tr[id^="edit-"], tr[id^="review-"], tr[id^="replace-"]').forEach((editRow) => {
                        editRow.style.display = 'none';
                    });
                    row.style.display = isOpen ? 'none' : 'table-row';
                });
            });

            render();
        }());
    </script>
</body>
</html>
