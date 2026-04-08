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
$canManageDocuments = verify_is_admin();
$currentRole = $canManageDocuments ? 'Admin' : 'Trustee';
$filesDir = __DIR__ . '/files';
$documentsFile = __DIR__ . '/documents.json';
$documents = [];

if (is_file($documentsFile)) {
    $decodedDocuments = json_decode((string) file_get_contents($documentsFile), true);
    if (is_array($decodedDocuments)) {
        $documents = $decodedDocuments;
    }
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file'])) {
    if (!$canManageDocuments) {
        $error = 'Only verification administrators can delete repository documents.';
    } else {
    $deleteFile = basename((string) $_POST['delete_file']);
    $deletePath = $filesDir . '/' . $deleteFile;

        if ($deleteFile === '' || !is_file($deletePath)) {
            $error = 'That document could not be found for deletion.';
        } else {
            @unlink($deletePath);

            if (isset($documents[$deleteFile])) {
                unset($documents[$deleteFile]);
                file_put_contents($documentsFile, json_encode($documents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
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
    if (!$canManageDocuments) {
        $error = 'Only verification administrators can edit repository documents.';
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

            file_put_contents($documentsFile, json_encode($documents, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $message = 'The verification record was updated successfully.';
        }
    }
}

$entries = [];
if (is_dir($filesDir)) {
    $files = glob($filesDir . '/*.*') ?: [];
    foreach ($files as $path) {
        $file = basename($path);
        $meta = $documents[$file] ?? [];
        $code = (string) ($meta['code'] ?? pathinfo($file, PATHINFO_FILENAME));
        $entries[] = [
            'file' => $file,
            'title' => (string) ($meta['title'] ?? pathinfo($file, PATHINFO_FILENAME)),
            'code' => $code,
            'uploaded_by' => (string) ($meta['uploaded_by'] ?? 'Unknown'),
            'uploaded_at' => (string) ($meta['uploaded_at'] ?? date('c', filemtime($path))),
            'notes' => (string) ($meta['notes'] ?? ''),
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
                <a class="verify-link" href="upload.php">Upload Desk</a>
                <a class="verify-button-secondary" href="logout.php">Sign Out</a>
            </div>
        </div>

        <div class="verify-hero">
            <div class="verify-card">
                <div class="verify-card-inner">
                    <span class="verify-kicker">Repository View</span>
                    <h2 class="verify-title">All uploaded verification documents in one place.</h2>
                    <p class="verify-copy">
                        Signed in as <strong><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></strong>. This library shows the current verification records stored in the system with direct links to view, download, or test the live verification code.
                    </p>
                    <div class="verify-actions">
                        <a class="verify-button" href="upload.php">Upload New Document</a>
                        <a class="verify-button-secondary" href="index.php#code-verification">Open Code Lookup</a>
                    </div>
                </div>
            </div>

            <div class="verify-card">
                <div class="verify-qr-panel">
                    <div style="width:100%;">
                        <h3 style="margin:0 0 16px; font-size:28px;">Library summary</h3>
                        <div class="verify-meta-list">
                            <div class="verify-meta-item"><strong>Stored Records</strong><span><?= count($entries) ?></span></div>
                            <div class="verify-meta-item"><strong>Access Level</strong><span><?= htmlspecialchars($currentRole, ENT_QUOTES, 'UTF-8') ?></span></div>
                            <div class="verify-meta-item"><strong>Available Actions</strong><span><?= htmlspecialchars($canManageDocuments ? 'View, print, download, verify, and delete' : 'View, print, download, and verify', ENT_QUOTES, 'UTF-8') ?></span></div>
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
                    <div class="verify-empty">No documents are stored yet. Upload the first record from the upload desk.</div>
                <?php else: ?>
                    <table class="verify-table" id="document-library-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Document Code</th>
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
                                ]);
                                ?>
                                <tr data-search="<?= htmlspecialchars(mb_strtolower($searchBlob), ENT_QUOTES, 'UTF-8') ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($entry['title'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <?php if ($entry['notes'] !== ''): ?>
                                            <div style="margin-top:6px; color:var(--verify-muted);"><?= htmlspecialchars($entry['notes'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($entry['code'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($entry['uploaded_by'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars(date('M j, Y g:i A', strtotime($entry['uploaded_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <a class="verify-link" href="<?= htmlspecialchars($entry['print_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Print</a>
                                        <a class="verify-link" href="<?= htmlspecialchars($entry['view_url'], ENT_QUOTES, 'UTF-8') ?>">View</a>
                                        <a class="verify-link" href="<?= htmlspecialchars($entry['download_url'], ENT_QUOTES, 'UTF-8') ?>">Download</a>
                                        <a class="verify-link" href="<?= htmlspecialchars($entry['verify_url'], ENT_QUOTES, 'UTF-8') ?>">Verify</a>
                                        <?php if ($canManageDocuments): ?>
                                            <button class="verify-link" type="button" style="cursor:pointer;" data-edit-target="edit-<?= htmlspecialchars(md5($entry['file']), ENT_QUOTES, 'UTF-8') ?>">Edit</button>
                                            <form class="verify-inline-form" method="post" onsubmit="return confirm('Delete this document and remove its verification code from the system?');">
                                                <input type="hidden" name="delete_file" value="<?= htmlspecialchars($entry['file'], ENT_QUOTES, 'UTF-8') ?>">
                                                <button class="verify-link" type="submit" style="cursor:pointer;">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php if ($canManageDocuments): ?>
                                    <tr id="edit-<?= htmlspecialchars(md5($entry['file']), ENT_QUOTES, 'UTF-8') ?>" data-search="<?= htmlspecialchars(mb_strtolower($searchBlob), ENT_QUOTES, 'UTF-8') ?>" style="display:none;">
                                        <td colspan="5">
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
                                                <div style="display:flex; gap:12px; flex-wrap:wrap;">
                                                    <button class="verify-button" type="submit">Save Changes</button>
                                                    <button class="verify-button-secondary" type="button" data-edit-target="edit-<?= htmlspecialchars(md5($entry['file']), ENT_QUOTES, 'UTF-8') ?>">Cancel</button>
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
                    document.querySelectorAll('tr[id^="edit-"]').forEach((editRow) => {
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
