<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once dirname(__DIR__) . '/crm_verify_auth.php';

if (!empty($_SESSION['upload_authenticated']) && verify_has_permission('manage_certificates')) {
    if (empty($_SESSION['verify_admin_authenticated'])) {
        $_SESSION['verify_admin_authenticated'] = 'linked';
    }
}

if (!verify_management_session_has_permission('manage_certificates')) {
    header('Location: index.php');
    exit;
}

function verify_certificate_font_choices(): array
{
    return [
        'Georgia' => 'Georgia',
        'Segoe UI' => 'Segoe UI',
        'Arial' => 'Arial',
        'Palatino Linotype' => 'Palatino Linotype',
        'Times New Roman' => 'Times New Roman',
        'Trebuchet MS' => 'Trebuchet MS',
    ];
}

function verify_certificate_asset_upload(string $fieldName, string $prefix, string $currentValue = ''): string
{
    if (empty($_FILES[$fieldName]['name']) || !is_uploaded_file($_FILES[$fieldName]['tmp_name'])) {
        return $currentValue;
    }

    $assetDir = verify_id_card_assets_dir_path();
    if (!is_dir($assetDir)) {
        mkdir($assetDir, 0775, true);
    }

    $extension = strtolower(pathinfo((string) $_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return $currentValue;
    }

    $filename = $prefix . '-' . date('YmdHis') . '-' . substr(md5(uniqid((string) mt_rand(), true)), 0, 6) . '.' . $extension;
    $destination = $assetDir . DIRECTORY_SEPARATOR . $filename;
    if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $destination)) {
        return verify_id_card_assets_web_path($filename);
    }

    return $currentValue;
}

function verify_certificate_design_from_request(array $baseDesign): array
{
    $design = $baseDesign;
    $design['preset_key'] = trim((string) ($_POST['design_preset_key'] ?? $design['preset_key']));
    $design['orientation'] = trim((string) ($_POST['design_orientation'] ?? $design['orientation']));
    $design['width_mm'] = (float) ($_POST['design_width_mm'] ?? $design['width_mm']);
    $design['height_mm'] = (float) ($_POST['design_height_mm'] ?? $design['height_mm']);
    $design['headline_font'] = trim((string) ($_POST['design_headline_font'] ?? $design['headline_font']));
    $design['body_font'] = trim((string) ($_POST['design_body_font'] ?? $design['body_font']));
    $design['primary_color'] = trim((string) ($_POST['design_primary_color'] ?? $design['primary_color']));
    $design['secondary_color'] = trim((string) ($_POST['design_secondary_color'] ?? $design['secondary_color']));
    $design['accent_color'] = trim((string) ($_POST['design_accent_color'] ?? $design['accent_color']));
    $design['border_color'] = trim((string) ($_POST['design_border_color'] ?? $design['border_color']));
    $design['background_image'] = trim((string) ($_POST['design_background_image_url'] ?? $design['background_image']));
    $design['seal_image'] = trim((string) ($_POST['design_seal_image_url'] ?? $design['seal_image']));
    $design['logo_image'] = trim((string) ($_POST['design_logo_image_url'] ?? $design['logo_image']));
    $design['background_image'] = verify_certificate_asset_upload('design_background_image_file', 'certificate-background', $design['background_image']);
    $design['seal_image'] = verify_certificate_asset_upload('design_seal_image_file', 'certificate-seal', $design['seal_image']);
    $design['logo_image'] = verify_certificate_asset_upload('design_logo_image_file', 'certificate-logo', $design['logo_image']);
    return verify_normalize_certificate_design($design);
}

$templates = verify_default_certificate_templates();
$certificates = verify_load_certificates();
$design = verify_load_certificate_design();
$designPresets = verify_certificate_design_presets();
$fontChoices = verify_certificate_font_choices();
$message = '';
$error = '';
$editCode = strtoupper(trim((string) ($_GET['edit'] ?? '')));
$editingCertificate = ($editCode !== '' && isset($certificates[$editCode])) ? $certificates[$editCode] : null;

if (isset($_POST['save_certificate_design'])) {
    $design = verify_certificate_design_from_request($design);
    verify_save_certificate_design($design);
    $message = 'Certificate design settings saved.';
}

if (isset($_POST['create_certificate']) || isset($_POST['update_certificate'])) {
    $designSnapshot = verify_certificate_design_from_request($design);
    verify_save_certificate_design($designSnapshot);
    $design = $designSnapshot;
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
    $originalCode = strtoupper(trim((string) ($_POST['original_code'] ?? '')));

    if ($recipient === '') {
        $error = 'Recipient name is required.';
    } else {
        $isUpdate = isset($_POST['update_certificate']) && $originalCode !== '' && isset($certificates[$originalCode]);
        if ($code === '') {
            $code = $isUpdate ? $originalCode : verify_generate_certificate_code($recipient);
        }
        if ($isUpdate && $originalCode !== $code) {
            unset($certificates[$originalCode]);
        }
        while ((!$isUpdate || $code !== $originalCode) && isset($certificates[$code])) {
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
            'design' => $designSnapshot,
            'status' => $status,
            'created_by' => (string) ($_SESSION['username'] ?? 'admin'),
            'created_at' => $isUpdate ? (string) ($certificates[$code]['created_at'] ?? ($certificates[$originalCode]['created_at'] ?? date('c'))) : date('c'),
            'updated_at' => date('c'),
        ]);

        verify_save_certificates($certificates);
        $message = $isUpdate ? 'Certificate updated successfully.' : 'Certificate created successfully.';
        $editCode = $code;
        $editingCertificate = $certificates[$code];
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

$certificateForm = [
    'template' => (string) ($editingCertificate['template'] ?? 'employee_of_month'),
    'title' => (string) ($editingCertificate['title'] ?? 'Certificate of Recognition'),
    'recipient' => (string) ($editingCertificate['recipient'] ?? ''),
    'recipient_title' => (string) ($editingCertificate['recipient_title'] ?? ''),
    'department' => (string) ($editingCertificate['department'] ?? ''),
    'issued_on' => (string) ($editingCertificate['issued_on'] ?? date('Y-m-d')),
    'signed_by' => (string) ($editingCertificate['signed_by'] ?? 'U.S. Capital Private Bank'),
    'signer_title' => (string) ($editingCertificate['signer_title'] ?? 'Verification Desk'),
    'code' => (string) ($editingCertificate['code'] ?? ''),
    'status' => (string) ($editingCertificate['status'] ?? 'active'),
    'body' => (string) ($editingCertificate['body'] ?? ''),
];
if ($editingCertificate !== null && is_array($editingCertificate['design'] ?? null)) {
    $design = verify_normalize_certificate_design(array_merge($design, $editingCertificate['design']));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Studio | U.S. Capital Private Bank</title>
    <link rel="stylesheet" href="../theme.css">
    <style>
        .certificate-grid { display:grid; grid-template-columns:1.1fr .9fr; gap:24px; margin-top:26px; }
        .certificate-fields { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:16px; }
        .certificate-span-2 { grid-column:span 2; }
        .certificate-delete-button { border:1px solid rgba(255,114,114,.28); background:rgba(145,24,40,.22); color:#ffb8b8; }
        .certificate-chip-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:12px; }
        .certificate-chip { padding:16px 18px; border-radius:18px; border:1px solid rgba(126,180,255,.18); background:rgba(255,255,255,.03); }
        .certificate-chip strong { display:block; color:var(--verify-text); font-size:14px; letter-spacing:.06em; text-transform:uppercase; }
        .certificate-chip span { display:block; margin-top:8px; color:var(--verify-muted); line-height:1.55; }
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
                    <h3 style="margin:0 0 18px; font-size:24px;"><?= $editingCertificate ? 'Edit Certificate' : 'Create Certificate' ?></h3>
                    <form method="post">
                        <?php if ($editingCertificate): ?><input type="hidden" name="original_code" value="<?= htmlspecialchars($editingCertificate['code'], ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
                        <div class="certificate-fields">
                            <div class="verify-form-group"><label class="verify-label" for="template">Template</label><select class="verify-input" id="template" name="template"><?php foreach ($templates as $value => $label): ?><option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"<?= $certificateForm['template'] === $value ? ' selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                            <div class="verify-form-group"><label class="verify-label" for="title">Certificate Title</label><input class="verify-input" id="title" name="title" type="text" value="<?= htmlspecialchars($certificateForm['title'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="verify-form-group"><label class="verify-label" for="recipient">Recipient</label><input class="verify-input" id="recipient" name="recipient" type="text" value="<?= htmlspecialchars($certificateForm['recipient'], ENT_QUOTES, 'UTF-8') ?>" required></div>
                            <div class="verify-form-group"><label class="verify-label" for="recipient_title">Recipient Title</label><input class="verify-input" id="recipient_title" name="recipient_title" type="text" value="<?= htmlspecialchars($certificateForm['recipient_title'], ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. Senior Relationship Officer"></div>
                            <div class="verify-form-group"><label class="verify-label" for="department">Department</label><input class="verify-input" id="department" name="department" type="text" value="<?= htmlspecialchars($certificateForm['department'], ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. Private Banking"></div>
                            <div class="verify-form-group"><label class="verify-label" for="issued_on">Issued On</label><input class="verify-input" id="issued_on" name="issued_on" type="date" value="<?= htmlspecialchars($certificateForm['issued_on'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="verify-form-group"><label class="verify-label" for="signed_by">Signed By</label><input class="verify-input" id="signed_by" name="signed_by" type="text" value="<?= htmlspecialchars($certificateForm['signed_by'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="verify-form-group"><label class="verify-label" for="signer_title">Signer Title</label><input class="verify-input" id="signer_title" name="signer_title" type="text" value="<?= htmlspecialchars($certificateForm['signer_title'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="verify-form-group"><label class="verify-label" for="code">Certificate Code</label><input class="verify-input" id="code" name="code" type="text" value="<?= htmlspecialchars($certificateForm['code'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Leave blank to auto-generate"></div>
                            <div class="verify-form-group"><label class="verify-label" for="status">Status</label><select class="verify-input" id="status" name="status"><option value="active"<?= $certificateForm['status'] === 'active' ? ' selected' : '' ?>>Active</option><option value="inactive"<?= $certificateForm['status'] === 'inactive' ? ' selected' : '' ?>>Inactive</option></select></div>
                            <div class="verify-form-group certificate-span-2"><label class="verify-label" for="body">Certificate Body</label><textarea class="verify-textarea" id="body" name="body" placeholder="Recognition message, certification language, or award statement."><?= htmlspecialchars($certificateForm['body'], ENT_QUOTES, 'UTF-8') ?></textarea></div>
                        </div>
                        <div class="verify-actions">
                            <button class="verify-button" type="submit" name="<?= $editingCertificate ? 'update_certificate' : 'create_certificate' ?>" value="1"><?= $editingCertificate ? 'Save Certificate Changes' : 'Create Certificate' ?></button>
                            <?php if ($editingCertificate): ?>
                                <a class="verify-link" href="certificates.php">Clear Edit</a>
                                <a class="verify-link" href="../certificate.php?code=<?= urlencode($editingCertificate['code']) ?>" target="_blank" rel="noopener">View Current Design</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </section>

            <section class="verify-card">
                <div class="verify-card-inner">
                    <span class="verify-kicker">Certificate Design</span>
                    <h3 style="margin:12px 0 14px; font-size:28px;">View and edit certificate styling</h3>
                    <form method="post" enctype="multipart/form-data">
                        <div class="certificate-fields">
                            <div class="verify-form-group"><label class="verify-label" for="design_preset_key">Design Preset</label><select class="verify-input" id="design_preset_key" name="design_preset_key"><?php foreach ($designPresets as $value => $preset): ?><option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"<?= $design['preset_key'] === $value ? ' selected' : '' ?>><?= htmlspecialchars($preset['label'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                            <div class="verify-form-group"><label class="verify-label" for="design_orientation">Orientation</label><select class="verify-input" id="design_orientation" name="design_orientation"><option value="landscape"<?= $design['orientation'] === 'landscape' ? ' selected' : '' ?>>Landscape</option><option value="portrait"<?= $design['orientation'] === 'portrait' ? ' selected' : '' ?>>Portrait</option></select></div>
                            <div class="verify-form-group"><label class="verify-label" for="design_headline_font">Headline Font</label><select class="verify-input" id="design_headline_font" name="design_headline_font"><?php foreach ($fontChoices as $font): ?><option value="<?= htmlspecialchars($font, ENT_QUOTES, 'UTF-8') ?>"<?= $design['headline_font'] === $font ? ' selected' : '' ?>><?= htmlspecialchars($font, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                            <div class="verify-form-group"><label class="verify-label" for="design_body_font">Body Font</label><select class="verify-input" id="design_body_font" name="design_body_font"><?php foreach ($fontChoices as $font): ?><option value="<?= htmlspecialchars($font, ENT_QUOTES, 'UTF-8') ?>"<?= $design['body_font'] === $font ? ' selected' : '' ?>><?= htmlspecialchars($font, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                            <div class="verify-form-group"><label class="verify-label" for="design_primary_color">Primary Color</label><input class="verify-input" id="design_primary_color" name="design_primary_color" type="color" value="<?= htmlspecialchars($design['primary_color'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="verify-form-group"><label class="verify-label" for="design_accent_color">Accent Color</label><input class="verify-input" id="design_accent_color" name="design_accent_color" type="color" value="<?= htmlspecialchars($design['accent_color'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="verify-form-group"><label class="verify-label" for="design_secondary_color">Secondary Color</label><input class="verify-input" id="design_secondary_color" name="design_secondary_color" type="color" value="<?= htmlspecialchars($design['secondary_color'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="verify-form-group"><label class="verify-label" for="design_border_color">Border Color</label><input class="verify-input" id="design_border_color" name="design_border_color" type="color" value="<?= htmlspecialchars($design['border_color'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="verify-form-group"><label class="verify-label" for="design_width_mm">Print Width (mm)</label><input class="verify-input" id="design_width_mm" name="design_width_mm" type="number" min="148" max="420" step="1" value="<?= htmlspecialchars((string) $design['width_mm'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="verify-form-group"><label class="verify-label" for="design_height_mm">Print Height (mm)</label><input class="verify-input" id="design_height_mm" name="design_height_mm" type="number" min="148" max="320" step="1" value="<?= htmlspecialchars((string) $design['height_mm'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="verify-form-group certificate-span-2"><label class="verify-label" for="design_logo_image_url">Logo Image URL</label><input class="verify-input" id="design_logo_image_url" name="design_logo_image_url" type="text" value="<?= htmlspecialchars($design['logo_image'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="verify-form-group"><label class="verify-label" for="design_logo_image_file">Logo Upload</label><input class="verify-input" id="design_logo_image_file" name="design_logo_image_file" type="file" accept=".jpg,.jpeg,.png,.webp"></div>
                            <div class="verify-form-group certificate-span-2"><label class="verify-label" for="design_background_image_url">Background Image URL</label><input class="verify-input" id="design_background_image_url" name="design_background_image_url" type="text" value="<?= htmlspecialchars($design['background_image'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="verify-form-group"><label class="verify-label" for="design_background_image_file">Background Upload</label><input class="verify-input" id="design_background_image_file" name="design_background_image_file" type="file" accept=".jpg,.jpeg,.png,.webp"></div>
                            <div class="verify-form-group certificate-span-2"><label class="verify-label" for="design_seal_image_url">Seal Image URL</label><input class="verify-input" id="design_seal_image_url" name="design_seal_image_url" type="text" value="<?= htmlspecialchars($design['seal_image'], ENT_QUOTES, 'UTF-8') ?>"></div>
                            <div class="verify-form-group"><label class="verify-label" for="design_seal_image_file">Seal Upload</label><input class="verify-input" id="design_seal_image_file" name="design_seal_image_file" type="file" accept=".jpg,.jpeg,.png,.webp"></div>
                        </div>
                        <div class="verify-actions">
                            <button class="verify-button" type="submit" name="save_certificate_design" value="1">Save Certificate Design</button>
                            <a class="verify-link" href="../certificate.php?preview=1" target="_blank" rel="noopener">View Design Preview</a>
                        </div>
                    </form>
                    <div class="certificate-chip-grid" style="margin-top:16px;">
                        <div class="certificate-chip"><strong>Preset</strong><span><?= htmlspecialchars($designPresets[$design['preset_key']]['label'] ?? 'Custom', ENT_QUOTES, 'UTF-8') ?></span></div>
                        <div class="certificate-chip"><strong>Format</strong><span><?= htmlspecialchars(ucfirst($design['orientation']), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars((string) $design['width_mm'], ENT_QUOTES, 'UTF-8') ?> x <?= htmlspecialchars((string) $design['height_mm'], ENT_QUOTES, 'UTF-8') ?> mm</span></div>
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
                                                <a class="verify-link" href="certificates.php?edit=<?= urlencode($certificate['code']) ?>">Edit</a>
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
