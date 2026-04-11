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

$affiliations = [
    'Bank Officer',
    'Trustee',
    'Director',
    'Client Services',
    'Operations',
    'Executive Office',
];

$message = '';
$error = '';
$previewCard = null;
$cards = verify_load_id_cards();

if (isset($_POST['delete_id_card'])) {
    $targetCode = strtoupper(trim((string) ($_POST['delete_id_card'] ?? '')));
    if ($targetCode !== '' && isset($cards[$targetCode])) {
        $photoUrl = (string) ($cards[$targetCode]['photo_url'] ?? '');
        if ($photoUrl !== '' && str_starts_with($photoUrl, 'idcard_photos/')) {
            $photoPath = dirname(__DIR__) . '/' . str_replace(['../', '..\\'], '', $photoUrl);
            if (is_file($photoPath)) {
                @unlink($photoPath);
            }
        }
        unset($cards[$targetCode]);
        verify_save_id_cards($cards);
        $message = 'ID card deleted.';
        if ($previewCard !== null && ($previewCard['code'] ?? '') === $targetCode) {
            $previewCard = null;
        }
    }
}

if (isset($_POST['create_id_card'])) {
    $name = trim((string) ($_POST['name'] ?? ''));
    $title = trim((string) ($_POST['title'] ?? ''));
    $department = trim((string) ($_POST['department'] ?? ''));
    $affiliation = trim((string) ($_POST['affiliation'] ?? 'Bank Officer'));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $photoUrl = trim((string) ($_POST['photo_url'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));
    $code = strtoupper(trim((string) ($_POST['code'] ?? '')));

    if ($name === '' || $title === '') {
        $error = 'Name and title are required to generate an ID card.';
    } else {
        if ($code === '') {
            $code = verify_generate_id_card_code($name);
        }

        if (isset($_FILES['photo_file']) && is_array($_FILES['photo_file']) && (int) ($_FILES['photo_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $uploadError = (int) ($_FILES['photo_file']['error'] ?? UPLOAD_ERR_OK);
            if ($uploadError !== UPLOAD_ERR_OK) {
                $error = 'The passport photo could not be uploaded. Please try again.';
            } else {
                $tmpName = (string) ($_FILES['photo_file']['tmp_name'] ?? '');
                $originalName = (string) ($_FILES['photo_file']['name'] ?? 'photo.jpg');
                $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
                if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    $error = 'Please upload a JPG, PNG, or WEBP passport photo.';
                } else {
                    $photoDir = verify_id_card_photo_dir_path();
                    if (!is_dir($photoDir)) {
                        @mkdir($photoDir, 0775, true);
                    }

                    $targetName = strtolower($code) . '-' . substr(md5($originalName . microtime(true)), 0, 8) . '.' . $extension;
                    $targetPath = $photoDir . '/' . $targetName;
                    if (!@move_uploaded_file($tmpName, $targetPath)) {
                        $error = 'The passport photo could not be saved on the server.';
                    } else {
                        $photoUrl = verify_id_card_photo_web_path($targetName);
                    }
                }
            }
        }

        if ($error === '') {
            $record = verify_normalize_id_card_record($code, [
                'code' => $code,
                'name' => $name,
                'title' => $title,
                'department' => $department,
                'affiliation' => $affiliation,
                'email' => $email,
                'phone' => $phone,
                'photo_url' => $photoUrl,
                'notes' => $notes,
                'status' => 'active',
                'created_by' => (string) ($_SESSION['username'] ?? 'admin'),
                'created_at' => date('c'),
                'updated_at' => date('c'),
            ]);

            if ($record !== null) {
                $cards[$code] = $record;
                verify_save_id_cards($cards);
                $previewCard = $record;
                $message = 'ID card created and ready to print.';
            }
        }
    }
}

if ($previewCard === null && isset($_GET['code'])) {
    $previewCard = verify_find_id_card_by_code((string) $_GET['code']);
}

uksort($cards, static function ($a, $b) use ($cards) {
    return strcmp((string) ($cards[$b]['updated_at'] ?? ''), (string) ($cards[$a]['updated_at'] ?? ''));
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card Studio | US Capital Private Bank</title>
    <link rel="stylesheet" href="../theme.css">
    <style>
        .idcard-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .idcard-form-grid .verify-form-group--full {
            grid-column: 1 / -1;
        }

        .idcard-preview-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 18px;
        }

        .idcard-inline-meta {
            margin-top: 12px;
            color: var(--verify-muted);
            font-size: 14px;
        }

        .idcard-action-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .idcard-delete-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 18px;
            border-radius: 999px;
            border: 1px solid rgba(255, 114, 114, 0.28);
            background: rgba(255, 114, 114, 0.1);
            color: #ffb4b4;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, background 0.2s ease, border-color 0.2s ease;
        }

        .idcard-delete-button:hover {
            transform: translateY(-1px);
            background: rgba(255, 114, 114, 0.18);
            border-color: rgba(255, 114, 114, 0.4);
        }

        @media (max-width: 860px) {
            .idcard-form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="verify-portal">
    <div class="verify-shell">
        <div class="verify-topbar">
            <div class="verify-brand">
                <img src="https://www.uscapitalprivatebank.com/assets/images/logoIcon/logo.png" alt="US Capital Private Bank">
                <div class="verify-brand-copy">
                    <h1>US Capital Private Bank</h1>
                    <p>ID Card Studio</p>
                </div>
            </div>
            <div class="verify-links">
                <a class="verify-link" href="../dashboard.php">Dashboard</a>
                <a class="verify-link" href="index.php">Admin Review</a>
                <a class="verify-link" href="../index.php">Verification Home</a>
                <a class="verify-button-secondary" href="index.php?logout=1">Sign Out</a>
            </div>
        </div>

        <div class="verify-card" style="margin-top:28px;">
            <div class="verify-card-inner">
                <?php if ($error !== ''): ?>
                    <div class="verify-alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if ($message !== ''): ?>
                    <div class="verify-alert success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <span class="verify-kicker">Employee Credentials</span>
                <h2 class="verify-title" style="max-width:900px; font-size:clamp(30px, 3.4vw, 44px);">Create branded employee ID cards with QR verification.</h2>
                <p class="verify-copy">Enter the employee's information below to create a printable front-and-back identification card. Each badge gets a unique verification code and QR link back to the bank's verification site.</p>
            </div>
        </div>

        <div class="verify-grid" style="margin-top:26px; grid-template-columns: minmax(0, 1.1fr) minmax(320px, 0.9fr);">
            <div class="verify-card">
                <div class="verify-card-inner">
                    <h3 style="margin:0 0 18px; font-size:24px;">Card Details</h3>
                    <form method="post" enctype="multipart/form-data">
                        <div class="idcard-form-grid">
                            <div class="verify-form-group">
                                <label class="verify-label" for="name">Employee Name</label>
                                <input class="verify-input" id="name" name="name" type="text" required>
                            </div>
                            <div class="verify-form-group">
                                <label class="verify-label" for="title">Title</label>
                                <input class="verify-input" id="title" name="title" type="text" placeholder="Trustee / Director" required>
                            </div>
                            <div class="verify-form-group">
                                <label class="verify-label" for="department">Department</label>
                                <input class="verify-input" id="department" name="department" type="text" placeholder="Verification Desk">
                            </div>
                            <div class="verify-form-group">
                                <label class="verify-label" for="affiliation">Affiliation</label>
                                <select class="verify-input" id="affiliation" name="affiliation">
                                    <?php foreach ($affiliations as $affiliation): ?>
                                        <option value="<?= htmlspecialchars($affiliation, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($affiliation, ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="verify-form-group">
                                <label class="verify-label" for="email">Email</label>
                                <input class="verify-input" id="email" name="email" type="email" placeholder="employee@uscapitalprivatebank.com">
                            </div>
                            <div class="verify-form-group">
                                <label class="verify-label" for="phone">Phone</label>
                                <input class="verify-input" id="phone" name="phone" type="text" placeholder="+971...">
                            </div>
                            <div class="verify-form-group">
                                <label class="verify-label" for="code">Card Code</label>
                                <input class="verify-input" id="code" name="code" type="text" placeholder="Auto-generate if left blank">
                            </div>
                            <div class="verify-form-group">
                                <label class="verify-label" for="photo_file">Passport Photo Upload</label>
                                <input class="verify-input" id="photo_file" name="photo_file" type="file" accept=".jpg,.jpeg,.png,.webp">
                            </div>
                            <div class="verify-form-group">
                                <label class="verify-label" for="photo_url">Or Photo URL</label>
                                <input class="verify-input" id="photo_url" name="photo_url" type="text" placeholder="Optional image URL if the photo is already online">
                            </div>
                            <div class="verify-form-group verify-form-group--full">
                                <label class="verify-label" for="notes">Notes</label>
                                <textarea class="verify-textarea" id="notes" name="notes" placeholder="Optional notes or internal remarks"></textarea>
                            </div>
                        </div>
                        <div class="verify-actions">
                            <button class="verify-button" type="submit" name="create_id_card" value="1">Create ID Card</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="verify-card">
                <div class="verify-card-inner">
                    <h3 style="margin:0 0 18px; font-size:24px;">Latest Preview</h3>
                    <?php if ($previewCard === null): ?>
                        <div class="verify-empty">Create a card to open the print preview and QR verification page.</div>
                    <?php else: ?>
                        <div class="idcard-inline-meta">Code: <strong><?= htmlspecialchars($previewCard['code'], ENT_QUOTES, 'UTF-8') ?></strong></div>
                        <div class="idcard-inline-meta">Verification URL: <strong><?= htmlspecialchars('https://www.uscapitalprivatebank.com/crm/verify/idcard.php?code=' . urlencode($previewCard['code']), ENT_QUOTES, 'UTF-8') ?></strong></div>
                        <div class="idcard-preview-actions">
                            <a class="verify-button" href="../idcard.php?code=<?= urlencode($previewCard['code']) ?>" target="_blank" rel="noopener">Open Preview</a>
                            <a class="verify-button-secondary" href="../idcard.php?code=<?= urlencode($previewCard['code']) ?>&print=1" target="_blank" rel="noopener">Print ID Card</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="verify-card" style="margin-top:26px;">
            <div class="verify-card-inner">
                <h3 style="margin:0 0 18px; font-size:24px;">Issued ID Cards</h3>
                <?php if (empty($cards)): ?>
                    <div class="verify-empty">No employee ID cards have been generated yet.</div>
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
                                        <td><span class="verify-status approved"><?= htmlspecialchars($card['status'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td>
                                            <div class="idcard-action-group">
                                                <a class="verify-link" href="?code=<?= urlencode($card['code']) ?>">Preview</a>
                                                <a class="verify-link" href="../idcard.php?code=<?= urlencode($card['code']) ?>" target="_blank" rel="noopener">Verify</a>
                                                <form method="post" onsubmit="return confirm('Delete this ID card?');" style="margin:0;">
                                                    <button class="idcard-delete-button" type="submit" name="delete_id_card" value="<?= htmlspecialchars($card['code'], ENT_QUOTES, 'UTF-8') ?>">Delete</button>
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
