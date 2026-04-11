<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once dirname(__DIR__) . '/crm_verify_auth.php';

if (!empty($_SESSION['upload_authenticated']) && verify_current_role() === 'admin') {
    $_SESSION['verify_admin_authenticated'] = true;
}

if (empty($_SESSION['verify_admin_authenticated'])) {
    header('Location: index.php');
    exit;
}

$templates = verify_default_certificate_templates();
$certificates = verify_load_certificates();
$message = '';
$error = '';

if (isset($_POST['create_certificate'])) {
    $recipient = trim((string) ($_POST['recipient'] ?? ''));
    $recipientTitle = trim((string) ($_POST['recipient_title'] ?? ''));
    $department = trim((string) ($_POST['department'] ?? ''));
    $template = trim((string) ($_POST['template'] ?? 'employee_of_month'));
    $title = trim((string) ($_POST['title'] ?? 'Certificate of Recognition'));
    $body = trim((string) ($_POST['body'] ?? ''));
    $issuedOn = trim((string) ($_POST['issued_on'] ?? date('Y-m-d')));
    $signedBy = trim((string) ($_POST['signed_by'] ?? 'U.S. Capital Private Bank'));
    $signerTitle = trim((string) ($_POST['signer_title'] ?? 'Verification Desk'));
    $status = trim((string) ($_POST['status'] ?? 'active'));
    $code = strtoupper(trim((string) ($_POST['code'] ?? '')));

    if ($recipient === '') {
        $error = 'Recipient name is required.';
    } else {
        if ($code === '') {
            $code = verify_generate_certificate_code($recipient);
        }
        while (isset($certificates[$code])) {
            $code = verify_generate_certificate_code($recipient);
        }

        if ($body === '') {
            $body = 'This certificate is issued in recognition of distinguished performance and verified through the secure U.S. Capital Private Bank verification workspace.';
        }

        $certificates[$code] = verify_normalize_certificate_record($code, [
            'code' => $code,
            'template' => $template,
            'title' => $title,
            'recipient' => $recipient,
            'recipient_title' => $recipientTitle,
            'department' => $department,
            'body' => $body,
            'issued_on' => $issuedOn,
            'signed_by' => $signedBy,
            'signer_title' => $signerTitle,
            'status' => $status,
            'created_by' => (string) ($_SESSION['username'] ?? 'admin'),
            'created_at' => date('c'),
            'updated_at' => date('c'),
        ]);

        verify_save_certificates($certificates);
        $message = 'Certificate created successfully.';
    }
}

if (isset($_POST['delete_certificate'])) {
    $code = strtoupper(trim((string) ($_POST['delete_certificate'] ?? '')));
    if (isset($certificates[$code])) {
        unset($certificates[$code]);
        verify_save_certificates($certificates);
        $message = 'Certificate deleted.';
    }
}

uasort($certificates, static function (array $a, array $b): int {
    return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Studio | U.S. Capital Private Bank</title>
    <link rel="stylesheet" href="../theme.css">
    <style>
        .certificate-grid { display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-top:26px; }
        .certificate-fields { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:16px; }
        .certificate-span-2 { grid-column:span 2; }
        .certificate-delete-button { border:1px solid rgba(255,114,114,.28); background:rgba(145,24,40,.22); color:#ffb8b8; }
        @media (max-width:980px) { .certificate-grid, .certificate-fields { grid-template-columns:1fr; } .certificate-span-2 { grid-column:auto; } }
    </style>
</head>
<body class="verify-portal">
    <div class="verify-shell">
        <div class="verify-topbar">
            <div class="verify-brand">
                <img src="https://www.uscapitalprivatebank.com/assets/images/logoIcon/logo.png" alt="US Capital Private Bank">
                <div class="verify-brand-copy">
                    <h1>U.S. Capital Private Bank</h1>
                    <p>Certificate Studio</p>
                </div>
            </div>
            <div class="verify-links">
                <a class="verify-link" href="../dashboard.php">Dashboard</a>
                <a class="verify-link" href="index.php">Admin Review</a>
                <a class="verify-link" href="idcards.php">ID Card Studio</a>
                <a class="verify-button-secondary" href="../logout.php">Sign Out</a>
            </div>
        </div>

        <div class="verify-card" style="margin-top:28px;">
            <div class="verify-card-inner">
                <?php if ($error !== ''): ?><div class="verify-alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                <?php if ($message !== ''): ?><div class="verify-alert success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                <span class="verify-kicker">Recognition Credentials</span>
                <h2 class="verify-title" style="max-width:900px; font-size:clamp(30px,3.4vw,44px);">Create QR-verified employee certificates and awards.</h2>
                <p class="verify-copy">Issue branded certificates that verify back to the bank website, including employee of the month, top sales, leadership excellence, trustee recognition, and other internal honors.</p>
            </div>
        </div>

        <div class="certificate-grid">
            <section class="verify-card">
                <div class="verify-card-inner">
                    <h3 style="margin:0 0 18px; font-size:24px;">Create Certificate</h3>
                    <form method="post">
                        <div class="certificate-fields">
                            <div class="verify-form-group"><label class="verify-label" for="template">Template</label><select class="verify-input" id="template" name="template"><?php foreach ($templates as $value => $label): ?><option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                            <div class="verify-form-group"><label class="verify-label" for="title">Certificate Title</label><input class="verify-input" id="title" name="title" type="text" value="Certificate of Recognition"></div>
                            <div class="verify-form-group"><label class="verify-label" for="recipient">Recipient</label><input class="verify-input" id="recipient" name="recipient" type="text" required></div>
                            <div class="verify-form-group"><label class="verify-label" for="recipient_title">Recipient Title</label><input class="verify-input" id="recipient_title" name="recipient_title" type="text" placeholder="e.g. Senior Relationship Officer"></div>
                            <div class="verify-form-group"><label class="verify-label" for="department">Department</label><input class="verify-input" id="department" name="department" type="text" placeholder="e.g. Private Banking"></div>
                            <div class="verify-form-group"><label class="verify-label" for="issued_on">Issued On</label><input class="verify-input" id="issued_on" name="issued_on" type="date" value="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="verify-form-group"><label class="verify-label" for="signed_by">Signed By</label><input class="verify-input" id="signed_by" name="signed_by" type="text" value="U.S. Capital Private Bank"></div>
                            <div class="verify-form-group"><label class="verify-label" for="signer_title">Signer Title</label><input class="verify-input" id="signer_title" name="signer_title" type="text" value="Verification Desk"></div>
                            <div class="verify-form-group"><label class="verify-label" for="code">Certificate Code</label><input class="verify-input" id="code" name="code" type="text" placeholder="Leave blank to auto-generate"></div>
                            <div class="verify-form-group"><label class="verify-label" for="status">Status</label><select class="verify-input" id="status" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                            <div class="verify-form-group certificate-span-2"><label class="verify-label" for="body">Certificate Body</label><textarea class="verify-textarea" id="body" name="body" placeholder="Recognition message, certification language, or award statement."></textarea></div>
                        </div>
                        <div class="verify-actions"><button class="verify-button" type="submit" name="create_certificate" value="1">Create Certificate</button></div>
                    </form>
                </div>
            </section>

            <section class="verify-card">
                <div class="verify-card-inner">
                    <span class="verify-kicker">Included Templates</span>
                    <h3 style="margin:12px 0 14px; font-size:28px;">Ready-to-use recognition library</h3>
                    <div class="verify-meta-list">
                        <?php foreach ($templates as $label): ?>
                            <div class="verify-meta-item"><strong><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></strong><span>QR-verified institutional recognition</span></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        </div>

        <div class="verify-card" style="margin-top:26px;">
            <div class="verify-card-inner">
                <h3 style="margin:0 0 18px; font-size:24px;">Issued Certificates</h3>
                <?php if (empty($certificates)): ?>
                    <div class="verify-empty">No certificates have been created yet.</div>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table class="verify-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Template</th>
                                    <th>Recipient</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($certificates as $certificate): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($certificate['code'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($templates[$certificate['template']] ?? $certificate['template'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($certificate['recipient'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><span class="verify-status approved"><?= htmlspecialchars(strtoupper($certificate['status']), ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td>
                                            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                                                <a class="verify-link" href="../certificate.php?code=<?= urlencode($certificate['code']) ?>" target="_blank" rel="noopener">Preview</a>
                                                <a class="verify-link" href="../certificate.php?code=<?= urlencode($certificate['code']) ?>&print=1" target="_blank" rel="noopener">Print</a>
                                                <form method="post" onsubmit="return confirm('Delete this certificate?');">
                                                    <button class="verify-link certificate-delete-button" type="submit" name="delete_certificate" value="<?= htmlspecialchars($certificate['code'], ENT_QUOTES, 'UTF-8') ?>">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
