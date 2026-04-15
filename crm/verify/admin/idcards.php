<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once dirname(__DIR__) . '/crm_verify_auth.php';

if (!empty($_SESSION['upload_authenticated']) && verify_has_permission('manage_id_cards')) {
    $_SESSION['verify_admin_authenticated'] = true;
}

if (!verify_management_session_has_permission('manage_id_cards')) {
    header('Location: index.php');
    exit;
}

function verify_card_font_choices(): array
{
    return [
        'Segoe UI' => 'Segoe UI',
        'Arial' => 'Arial',
        'Georgia' => 'Georgia',
        'Trebuchet MS' => 'Trebuchet MS',
        'Tahoma' => 'Tahoma',
        'Verdana' => 'Verdana',
        'Gill Sans' => 'Gill Sans',
        'Palatino Linotype' => 'Palatino Linotype',
        'Times New Roman' => 'Times New Roman',
    ];
}

function verify_card_affiliation_choices(): array
{
    return [
        'Executive Office',
        'Administration',
        'Trustee Office',
        'Private Banking',
        'Compliance',
        'Operations',
        'Customer Relations',
        'Client Services',
        'Treasury',
        'Bank Officer',
    ];
}

function verify_store_uploaded_asset(string $fieldName, string $prefix, string $currentValue = ''): string
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

function verify_store_uploaded_photo(string $fieldName, string $currentValue = ''): string
{
    if (empty($_FILES[$fieldName]['name']) || !is_uploaded_file($_FILES[$fieldName]['tmp_name'])) {
        return $currentValue;
    }

    $photoDir = verify_id_card_photo_dir_path();
    if (!is_dir($photoDir)) {
        mkdir($photoDir, 0775, true);
    }

    $extension = strtolower(pathinfo((string) $_FILES[$fieldName]['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return $currentValue;
    }

    $filename = 'idcard-' . date('YmdHis') . '-' . substr(md5(uniqid((string) mt_rand(), true)), 0, 6) . '.' . $extension;
    $destination = $photoDir . DIRECTORY_SEPARATOR . $filename;

    if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $destination)) {
        return verify_id_card_photo_web_path($filename);
    }

    return $currentValue;
}

function verify_design_from_request(array $baseDesign): array
{
    $design = $baseDesign;
    $design['preset_key'] = trim((string) ($_POST['preset_key'] ?? $design['preset_key']));
    $design['orientation'] = trim((string) ($_POST['orientation'] ?? $design['orientation']));
    $design['width_mm'] = (float) ($_POST['width_mm'] ?? $design['width_mm']);
    $design['height_mm'] = (float) ($_POST['height_mm'] ?? $design['height_mm']);
    $design['headline_font'] = trim((string) ($_POST['headline_font'] ?? $design['headline_font']));
    $design['body_font'] = trim((string) ($_POST['body_font'] ?? $design['body_font']));
    $design['primary_color'] = trim((string) ($_POST['primary_color'] ?? $design['primary_color']));
    $design['secondary_color'] = trim((string) ($_POST['secondary_color'] ?? $design['secondary_color']));
    $design['accent_color'] = trim((string) ($_POST['accent_color'] ?? $design['accent_color']));
    $design['metal_color'] = trim((string) ($_POST['metal_color'] ?? $design['metal_color']));
    $design['front_background'] = trim((string) ($_POST['front_background_url'] ?? $design['front_background']));
    $design['back_background'] = trim((string) ($_POST['back_background_url'] ?? $design['back_background']));
    $design['logo_image'] = trim((string) ($_POST['logo_image_url'] ?? $design['logo_image']));
    $design['front_background'] = verify_store_uploaded_asset('front_background_file', 'front-background', $design['front_background']);
    $design['back_background'] = verify_store_uploaded_asset('back_background_file', 'back-background', $design['back_background']);
    $design['logo_image'] = verify_store_uploaded_asset('logo_image_file', 'logo-image', $design['logo_image']);

    return verify_normalize_id_card_design($design);
}

$message = '';
$error = '';
$cards = verify_load_id_cards();
$design = verify_load_id_card_design();
$presets = verify_id_card_design_presets();
$fontChoices = verify_card_font_choices();
$affiliationChoices = verify_card_affiliation_choices();
$editCode = strtoupper(trim((string) ($_GET['edit'] ?? '')));
$editingCard = ($editCode !== '' && isset($cards[$editCode])) ? $cards[$editCode] : null;

if (isset($_POST['save_design'])) {
    $design = verify_design_from_request($design);
    verify_save_id_card_design($design);
    $message = 'ID card design settings saved.';
}

if (isset($_POST['create_id_card']) || isset($_POST['update_id_card'])) {
    $designSnapshot = verify_design_from_request($design);
    verify_save_id_card_design($designSnapshot);
    $design = $designSnapshot;
    $name = trim((string) ($_POST['name'] ?? ''));
    $title = trim((string) ($_POST['title'] ?? ''));
    $department = trim((string) ($_POST['department'] ?? ''));
    $affiliation = trim((string) ($_POST['affiliation'] ?? 'Bank Officer'));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $status = trim((string) ($_POST['status'] ?? 'active'));
    $code = strtoupper(trim((string) ($_POST['code'] ?? '')));
    $originalCode = strtoupper(trim((string) ($_POST['original_code'] ?? '')));
    $templateKey = trim((string) ($_POST['preset_key'] ?? $designSnapshot['preset_key']));
    $photoUrl = trim((string) ($_POST['photo_url'] ?? ''));
    $photoUrl = verify_store_uploaded_photo('photo_file', $photoUrl);

    if ($name === '' || $title === '') {
        $error = 'Name and title are required to create an ID card.';
    } else {
        $isUpdate = isset($_POST['update_id_card']) && $originalCode !== '' && isset($cards[$originalCode]);

        if ($code === '') {
            $code = $isUpdate ? $originalCode : verify_generate_id_card_code($name);
        }
        if ($isUpdate && $originalCode !== $code) {
            unset($cards[$originalCode]);
        }
        while ((!$isUpdate || $code !== $originalCode) && isset($cards[$code])) {
            $code = verify_generate_id_card_code($name);
        }

        $cards[$code] = verify_normalize_id_card_record($code, [
            'code' => $code,
            'name' => $name,
            'title' => $title,
            'department' => $department,
            'affiliation' => $affiliation,
            'email' => $email,
            'phone' => $phone,
            'photo_url' => $photoUrl,
            'notes' => $notes,
            'template_key' => $templateKey,
            'design' => $designSnapshot,
            'status' => $status,
            'created_by' => (string) ($_SESSION['username'] ?? 'admin'),
            'created_at' => $isUpdate ? (string) ($cards[$code]['created_at'] ?? ($cards[$originalCode]['created_at'] ?? date('c'))) : date('c'),
            'updated_at' => date('c'),
        ]);

        verify_save_id_cards($cards);
        $message = $isUpdate ? 'Employee ID card updated successfully.' : 'Employee ID card created successfully.';
        $editCode = $code;
        $editingCard = $cards[$code];
    }
}

if (isset($_POST['delete_id_card'])) {
    $code = strtoupper(trim((string) ($_POST['delete_id_card'] ?? '')));
    if (isset($cards[$code])) {
        $photoUrl = (string) ($cards[$code]['photo_url'] ?? '');
        if (str_starts_with($photoUrl, 'idcard_photos/')) {
            $photoPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $photoUrl);
            if (is_file($photoPath)) {
                @unlink($photoPath);
            }
        }
        unset($cards[$code]);
        verify_save_id_cards($cards);
        $message = 'ID card deleted.';
    }
}

uasort($cards, static function (array $a, array $b): int {
    return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
});

$cardForm = [
    'name' => (string) ($editingCard['name'] ?? ''),
    'title' => (string) ($editingCard['title'] ?? ''),
    'department' => (string) ($editingCard['department'] ?? ''),
    'affiliation' => (string) ($editingCard['affiliation'] ?? 'Bank Officer'),
    'email' => (string) ($editingCard['email'] ?? ''),
    'phone' => (string) ($editingCard['phone'] ?? ''),
    'photo_url' => (string) ($editingCard['photo_url'] ?? ''),
    'notes' => (string) ($editingCard['notes'] ?? ''),
    'status' => (string) ($editingCard['status'] ?? 'active'),
    'code' => (string) ($editingCard['code'] ?? ''),
];
if ($editingCard !== null && is_array($editingCard['design'] ?? null)) {
    $design = verify_normalize_id_card_design(array_merge($design, $editingCard['design']));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card Studio | U.S. Capital Private Bank</title>
    <link rel="stylesheet" href="../theme.css">
    <style>
        .studio-grid { display:grid; grid-template-columns:1.15fr 0.85fr; gap:24px; margin-top:26px; }
        .studio-stack { display:grid; gap:24px; }
        .studio-fields { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:16px; }
        .studio-fields--thirds { grid-template-columns:repeat(3, minmax(0,1fr)); }
        .studio-span-2 { grid-column:span 2; }
        .studio-chip-grid { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:12px; }
        .studio-chip { padding:16px 18px; border-radius:18px; border:1px solid rgba(126,180,255,0.18); background:rgba(255,255,255,0.03); }
        .studio-chip strong { display:block; color:var(--verify-text); font-size:14px; letter-spacing:.06em; text-transform:uppercase; }
        .studio-chip span { display:block; margin-top:8px; color:var(--verify-muted); line-height:1.55; }
        .studio-actions { display:flex; flex-wrap:wrap; gap:12px; margin-top:18px; }
        .studio-preview-card { min-height:100%; display:grid; gap:16px; }
        .studio-preview-tile { padding:22px; border-radius:22px; background:linear-gradient(145deg, rgba(6,18,34,.96), rgba(19,45,82,.82)); border:1px solid rgba(126,180,255,.18); box-shadow:inset 0 1px 0 rgba(255,255,255,.04); }
        .studio-preview-tile--light { background:linear-gradient(180deg, rgba(255,255,255,.98), rgba(242,246,252,.96)); color:#162449; }
        .studio-color-row { display:grid; grid-template-columns:repeat(2, minmax(0,1fr)); gap:12px; margin-top:12px; }
        .studio-swatch { border-radius:16px; padding:14px 16px; min-height:82px; border:1px solid rgba(255,255,255,.08); }
        .studio-swatch strong, .studio-swatch span { display:block; }
        .studio-swatch strong { font-size:12px; letter-spacing:.08em; text-transform:uppercase; }
        .studio-swatch span { margin-top:10px; font-weight:700; }
        .studio-table-actions { display:flex; gap:10px; flex-wrap:wrap; }
        .idcard-delete-button { border:1px solid rgba(255,114,114,.28); background:rgba(145,24,40,.22); color:#ffb8b8; }
        .idcard-delete-button:hover { background:rgba(165,30,50,.32); }
        @media (max-width:1024px) {
            .studio-grid, .studio-fields, .studio-fields--thirds, .studio-chip-grid, .studio-color-row { grid-template-columns:1fr; }
            .studio-span-2 { grid-column:auto; }
        }
    </style>
</head>
<body class="verify-portal">
    <div class="verify-shell">
        <div class="verify-topbar">
            <div class="verify-brand">
                <img src="https://www.uscapitalprivatebank.com/assets/images/logoIcon/logo.png" alt="US Capital Private Bank">
                <div class="verify-brand-copy">
                    <h1>U.S. Capital Private Bank</h1>
                    <p>ID Card Design Studio</p>
                </div>
            </div>
            <div class="verify-links">
                <a class="verify-link" href="../dashboard.php">Dashboard</a>
                <a class="verify-link" href="index.php">Admin Review</a>
                <a class="verify-link" href="certificates.php">Certificate Studio</a>
                <a class="verify-button-secondary" href="../logout.php">Sign Out</a>
            </div>
        </div>

        <div class="verify-card" style="margin-top:28px;">
            <div class="verify-card-inner">
                <?php if ($error !== ''): ?><div class="verify-alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                <?php if ($message !== ''): ?><div class="verify-alert success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                <span class="verify-kicker">Employee Credentialing</span>
                <h2 class="verify-title" style="max-width:980px; font-size:clamp(30px,3.5vw,46px);">Full-featured employee ID card maker with live QR verification.</h2>
                <p class="verify-copy">Use this workspace to design portrait or landscape bank ID cards, save reusable brand settings, upload logos and textures, and issue employee credentials that verify back to the bank’s secure verification site.</p>
            </div>
        </div>

        <div class="studio-grid">
            <div class="studio-stack">
                <section class="verify-card">
                    <div class="verify-card-inner">
                        <h3 style="margin:0 0 18px; font-size:24px;"><?= $editingCard ? 'Edit Employee ID Card' : 'Create Employee ID Card' ?></h3>
                        <form method="post" enctype="multipart/form-data">
                            <?php if ($editingCard): ?><input type="hidden" name="original_code" value="<?= htmlspecialchars($editingCard['code'], ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
                            <div class="studio-fields">
                                <div class="verify-form-group"><label class="verify-label" for="name">Employee Name</label><input class="verify-input" id="name" name="name" type="text" value="<?= htmlspecialchars($cardForm['name'], ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. HRM Joseph David Jeremiah" required></div>
                                <div class="verify-form-group"><label class="verify-label" for="title">Title</label><input class="verify-input" id="title" name="title" type="text" value="<?= htmlspecialchars($cardForm['title'], ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. 1st Trustee / Chairman" required></div>
                                <div class="verify-form-group"><label class="verify-label" for="department">Department</label><input class="verify-input" id="department" name="department" type="text" value="<?= htmlspecialchars($cardForm['department'], ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. Executive Office"></div>
                                <div class="verify-form-group"><label class="verify-label" for="affiliation">Affiliation</label><select class="verify-input" id="affiliation" name="affiliation"><?php foreach ($affiliationChoices as $choice): ?><option value="<?= htmlspecialchars($choice, ENT_QUOTES, 'UTF-8') ?>"<?= $cardForm['affiliation'] === $choice ? ' selected' : '' ?>><?= htmlspecialchars($choice, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                                <div class="verify-form-group"><label class="verify-label" for="email">Email</label><input class="verify-input" id="email" name="email" type="email" value="<?= htmlspecialchars($cardForm['email'], ENT_QUOTES, 'UTF-8') ?>" placeholder="employee@uscapitalprivatebank.com"></div>
                                <div class="verify-form-group"><label class="verify-label" for="phone">Phone</label><input class="verify-input" id="phone" name="phone" type="text" value="<?= htmlspecialchars($cardForm['phone'], ENT_QUOTES, 'UTF-8') ?>" placeholder="+971 ..."></div>
                                <div class="verify-form-group"><label class="verify-label" for="code">Card Code</label><input class="verify-input" id="code" name="code" type="text" value="<?= htmlspecialchars($cardForm['code'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Leave blank to auto-generate"></div>
                                <div class="verify-form-group"><label class="verify-label" for="status">Status</label><select class="verify-input" id="status" name="status"><option value="active"<?= $cardForm['status'] === 'active' ? ' selected' : '' ?>>Active</option><option value="inactive"<?= $cardForm['status'] === 'inactive' ? ' selected' : '' ?>>Inactive</option><option value="suspended"<?= $cardForm['status'] === 'suspended' ? ' selected' : '' ?>>Suspended</option></select></div>
                                <div class="verify-form-group"><label class="verify-label" for="photo_url">Photo URL</label><input class="verify-input" id="photo_url" name="photo_url" type="text" value="<?= htmlspecialchars($cardForm['photo_url'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Optional remote image URL"></div>
                                <div class="verify-form-group"><label class="verify-label" for="photo_file">Passport Photo Upload</label><input class="verify-input" id="photo_file" name="photo_file" type="file" accept=".jpg,.jpeg,.png,.webp"></div>
                                <div class="verify-form-group studio-span-2"><label class="verify-label" for="notes">Credential Notes</label><textarea class="verify-textarea" id="notes" name="notes" placeholder="Optional notes, restrictions, issue remarks, or internal comments."><?= htmlspecialchars($cardForm['notes'], ENT_QUOTES, 'UTF-8') ?></textarea></div>
                            </div>
                            <div class="studio-actions">
                                <button class="verify-button" type="submit" name="<?= $editingCard ? 'update_id_card' : 'create_id_card' ?>" value="1"><?= $editingCard ? 'Save ID Card Changes' : 'Create ID Card' ?></button>
                                <?php if ($editingCard): ?>
                                    <a class="verify-link" href="idcards.php">Clear Edit</a>
                                    <a class="verify-link" href="../idcard.php?code=<?= urlencode($editingCard['code']) ?>" target="_blank" rel="noopener">View Current Design</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </section>

                <section class="verify-card">
                    <div class="verify-card-inner">
                        <h3 style="margin:0 0 18px; font-size:24px;">Design Studio</h3>
                        <form method="post" enctype="multipart/form-data">
                            <div class="studio-fields studio-fields--thirds">
                                <div class="verify-form-group"><label class="verify-label" for="preset_key">Template Preset</label><select class="verify-input" id="preset_key" name="preset_key"><?php foreach ($presets as $presetKey => $preset): ?><option value="<?= htmlspecialchars($presetKey, ENT_QUOTES, 'UTF-8') ?>"<?= $design['preset_key'] === $presetKey ? ' selected' : '' ?>><?= htmlspecialchars($preset['label'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                                <div class="verify-form-group"><label class="verify-label" for="orientation">Orientation</label><select class="verify-input" id="orientation" name="orientation"><option value="portrait"<?= $design['orientation'] === 'portrait' ? ' selected' : '' ?>>Portrait</option><option value="landscape"<?= $design['orientation'] === 'landscape' ? ' selected' : '' ?>>Landscape</option></select></div>
                                <div class="verify-form-group"><label class="verify-label" for="headline_font">Headline Font</label><select class="verify-input" id="headline_font" name="headline_font"><?php foreach ($fontChoices as $font): ?><option value="<?= htmlspecialchars($font, ENT_QUOTES, 'UTF-8') ?>"<?= $design['headline_font'] === $font ? ' selected' : '' ?>><?= htmlspecialchars($font, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                                <div class="verify-form-group"><label class="verify-label" for="body_font">Body Font</label><select class="verify-input" id="body_font" name="body_font"><?php foreach ($fontChoices as $font): ?><option value="<?= htmlspecialchars($font, ENT_QUOTES, 'UTF-8') ?>"<?= $design['body_font'] === $font ? ' selected' : '' ?>><?= htmlspecialchars($font, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                                <div class="verify-form-group"><label class="verify-label" for="width_mm">Print Width (mm)</label><input class="verify-input" id="width_mm" name="width_mm" type="number" min="40" max="120" step="0.1" value="<?= htmlspecialchars((string) $design['width_mm'], ENT_QUOTES, 'UTF-8') ?>"></div>
                                <div class="verify-form-group"><label class="verify-label" for="height_mm">Print Height (mm)</label><input class="verify-input" id="height_mm" name="height_mm" type="number" min="54" max="120" step="0.1" value="<?= htmlspecialchars((string) $design['height_mm'], ENT_QUOTES, 'UTF-8') ?>"></div>
                                <div class="verify-form-group"><label class="verify-label" for="primary_color">Primary Color</label><input class="verify-input" id="primary_color" name="primary_color" type="color" value="<?= htmlspecialchars((string) $design['primary_color'], ENT_QUOTES, 'UTF-8') ?>"></div>
                                <div class="verify-form-group"><label class="verify-label" for="secondary_color">Secondary Color</label><input class="verify-input" id="secondary_color" name="secondary_color" type="color" value="<?= htmlspecialchars((string) $design['secondary_color'], ENT_QUOTES, 'UTF-8') ?>"></div>
                                <div class="verify-form-group"><label class="verify-label" for="accent_color">Accent Color</label><input class="verify-input" id="accent_color" name="accent_color" type="color" value="<?= htmlspecialchars((string) $design['accent_color'], ENT_QUOTES, 'UTF-8') ?>"></div>
                                <div class="verify-form-group"><label class="verify-label" for="metal_color">Metallic Tone</label><input class="verify-input" id="metal_color" name="metal_color" type="color" value="<?= htmlspecialchars((string) $design['metal_color'], ENT_QUOTES, 'UTF-8') ?>"></div>
                                <div class="verify-form-group studio-span-2"><label class="verify-label" for="logo_image_url">Logo Image URL</label><input class="verify-input" id="logo_image_url" name="logo_image_url" type="text" value="<?= htmlspecialchars((string) $design['logo_image'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Logo image path or remote URL"></div>
                                <div class="verify-form-group"><label class="verify-label" for="logo_image_file">Logo Upload</label><input class="verify-input" id="logo_image_file" name="logo_image_file" type="file" accept=".jpg,.jpeg,.png,.webp"></div>
                                <div class="verify-form-group studio-span-2"><label class="verify-label" for="front_background_url">Front Texture / Background URL</label><input class="verify-input" id="front_background_url" name="front_background_url" type="text" value="<?= htmlspecialchars((string) $design['front_background'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Optional image path or remote URL"></div>
                                <div class="verify-form-group"><label class="verify-label" for="front_background_file">Front Background Upload</label><input class="verify-input" id="front_background_file" name="front_background_file" type="file" accept=".jpg,.jpeg,.png,.webp"></div>
                                <div class="verify-form-group studio-span-2"><label class="verify-label" for="back_background_url">Back Texture / Background URL</label><input class="verify-input" id="back_background_url" name="back_background_url" type="text" value="<?= htmlspecialchars((string) $design['back_background'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Optional image path or remote URL"></div>
                                <div class="verify-form-group"><label class="verify-label" for="back_background_file">Back Background Upload</label><input class="verify-input" id="back_background_file" name="back_background_file" type="file" accept=".jpg,.jpeg,.png,.webp"></div>
                            </div>
                            <div class="studio-actions">
                                <button class="verify-button" type="submit" name="save_design" value="1">Save Design Settings</button>
                                <a class="verify-link" href="../idcard.php?preview=1" target="_blank" rel="noopener">View Design Preview</a>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
            <div class="studio-stack">
                <section class="verify-card">
                    <div class="verify-card-inner studio-preview-card">
                        <div>
                            <span class="verify-kicker">Studio Overview</span>
                            <h3 style="margin:14px 0 10px; font-size:28px;">Design-ready badge system</h3>
                            <p class="verify-copy" style="font-size:15px;">This studio supports portrait and landscape printing, saved brand settings, uploaded badge textures, centered passport photography, and QR-based credential verification.</p>
                        </div>
                        <div class="studio-chip-grid">
                            <div class="studio-chip"><strong>Saved preset</strong><span><?= htmlspecialchars($presets[$design['preset_key']]['label'] ?? 'Custom', ENT_QUOTES, 'UTF-8') ?></span></div>
                            <div class="studio-chip"><strong>Print size</strong><span><?= htmlspecialchars((string) $design['width_mm'], ENT_QUOTES, 'UTF-8') ?>mm x <?= htmlspecialchars((string) $design['height_mm'], ENT_QUOTES, 'UTF-8') ?>mm</span></div>
                            <div class="studio-chip"><strong>Orientation</strong><span><?= htmlspecialchars(ucfirst((string) $design['orientation']), ENT_QUOTES, 'UTF-8') ?></span></div>
                            <div class="studio-chip"><strong>Fonts</strong><span><?= htmlspecialchars($design['headline_font'], ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars($design['body_font'], ENT_QUOTES, 'UTF-8') ?></span></div>
                        </div>
                        <div class="studio-preview-tile">
                            <div style="font-size:12px; letter-spacing:.12em; text-transform:uppercase; color:#9ccfff;">Current palette</div>
                            <div class="studio-color-row">
                                <div class="studio-swatch" style="background:<?= htmlspecialchars($design['primary_color'], ENT_QUOTES, 'UTF-8') ?>; color:#fff;"><strong>Primary</strong><span><?= htmlspecialchars($design['primary_color'], ENT_QUOTES, 'UTF-8') ?></span></div>
                                <div class="studio-swatch" style="background:<?= htmlspecialchars($design['secondary_color'], ENT_QUOTES, 'UTF-8') ?>; color:#fff;"><strong>Secondary</strong><span><?= htmlspecialchars($design['secondary_color'], ENT_QUOTES, 'UTF-8') ?></span></div>
                                <div class="studio-swatch" style="background:<?= htmlspecialchars($design['accent_color'], ENT_QUOTES, 'UTF-8') ?>; color:#fff;"><strong>Accent</strong><span><?= htmlspecialchars($design['accent_color'], ENT_QUOTES, 'UTF-8') ?></span></div>
                                <div class="studio-swatch" style="background:<?= htmlspecialchars($design['metal_color'], ENT_QUOTES, 'UTF-8') ?>; color:#fff;"><strong>Metal</strong><span><?= htmlspecialchars($design['metal_color'], ENT_QUOTES, 'UTF-8') ?></span></div>
                            </div>
                        </div>
                        <div class="studio-preview-tile studio-preview-tile--light">
                            <strong style="display:block; font-size:13px; letter-spacing:.12em; text-transform:uppercase; color:#2b89d9;">Template library</strong>
                            <p style="margin:12px 0 0; color:#4f6486; line-height:1.7;">20 presets are available right now: 10 portrait and 10 landscape, each built for a more professional banking credential style rather than using reference business cards as background art.</p>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <div class="verify-card" style="margin-top:26px;">
            <div class="verify-card-inner">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:18px;">
                    <h3 style="margin:0; font-size:24px;">Issued ID Cards</h3>
                    <a class="verify-link" href="certificates.php">Open Certificate Studio</a>
                </div>
                <?php if (empty($cards)): ?>
                    <div class="verify-empty">No ID cards have been created yet.</div>
                <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table class="verify-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Title</th>
                                    <th>Affiliation</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cards as $card): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($card['code'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($card['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($card['affiliation'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><span class="verify-status approved"><?= htmlspecialchars(strtoupper((string) $card['status']), ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td>
                                            <div class="studio-table-actions">
                                                <a class="verify-link" href="idcards.php?edit=<?= urlencode($card['code']) ?>">Edit</a>
                                                <a class="verify-link" href="../idcard.php?code=<?= urlencode($card['code']) ?>" target="_blank" rel="noopener">Preview</a>
                                                <a class="verify-link" href="../idcard.php?code=<?= urlencode($card['code']) ?>&print=1" target="_blank" rel="noopener">Print</a>
                                                <a class="verify-link" href="../idcard.php?code=<?= urlencode($card['code']) ?>" target="_blank" rel="noopener">Verify</a>
                                                <form method="post" onsubmit="return confirm('Delete this ID card?');">
                                                    <button class="verify-link idcard-delete-button" type="submit" name="delete_id_card" value="<?= htmlspecialchars($card['code'], ENT_QUOTES, 'UTF-8') ?>">Delete</button>
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
