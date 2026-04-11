<?php
require_once __DIR__ . '/crm_verify_auth.php';

$code = strtoupper(trim((string) ($_GET['code'] ?? '')));
$card = verify_find_id_card_by_code($code);
$isPrint = isset($_GET['print']);

function verify_card_render_design(?array $card): array
{
    $global = verify_load_id_card_design();
    if ($card === null) {
        return $global;
    }

    $stored = is_array($card['design'] ?? null) ? $card['design'] : [];
    return verify_normalize_id_card_design(array_merge($global, $stored));
}

function verify_card_asset_url(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$design = verify_card_render_design($card);
$isLandscape = $design['orientation'] === 'landscape';
$logoUrl = verify_card_asset_url((string) $design['logo_image']);
$frontBackground = verify_card_asset_url((string) $design['front_background']);
$backBackground = verify_card_asset_url((string) $design['back_background']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $card ? htmlspecialchars($card['name'], ENT_QUOTES, 'UTF-8') . ' | Employee ID Verification' : 'Employee ID Verification' ?></title>
    <style>
        :root {
            --primary: <?= htmlspecialchars($design['primary_color'], ENT_QUOTES, 'UTF-8') ?>;
            --secondary: <?= htmlspecialchars($design['secondary_color'], ENT_QUOTES, 'UTF-8') ?>;
            --accent: <?= htmlspecialchars($design['accent_color'], ENT_QUOTES, 'UTF-8') ?>;
            --metal: <?= htmlspecialchars($design['metal_color'], ENT_QUOTES, 'UTF-8') ?>;
            --headline-font: "<?= htmlspecialchars($design['headline_font'], ENT_QUOTES, 'UTF-8') ?>", "Segoe UI", sans-serif;
            --body-font: "<?= htmlspecialchars($design['body_font'], ENT_QUOTES, 'UTF-8') ?>", "Segoe UI", sans-serif;
            --card-width: <?= $isLandscape ? '98mm' : '63mm' ?>;
            --card-height: <?= $isLandscape ? '63mm' : '98mm' ?>;
            --card-radius: 5mm;
            --shadow: 0 28px 70px rgba(10, 18, 38, 0.18);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: var(--body-font);
            color: #14203b;
            background: linear-gradient(180deg, #eef4fb 0%, #ffffff 100%);
        }
        .page {
            max-width: 1320px;
            margin: 0 auto;
            padding: 36px 24px 56px;
        }
        .notice {
            margin-bottom: 24px;
            padding: 24px 28px;
            border-radius: 24px;
            background: rgba(255,255,255,0.96);
            box-shadow: var(--shadow);
        }
        .notice h1 {
            margin: 0 0 10px;
            font-family: var(--headline-font);
            font-size: clamp(2rem, 3vw, 3rem);
            color: var(--primary);
        }
        .notice p {
            margin: 0;
            color: #5d6b86;
            line-height: 1.75;
        }
        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 18px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 18px;
            border-radius: 999px;
            text-decoration: none;
            border: 0;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--accent), #0c85d4);
            color: #fff;
        }
        .btn-secondary {
            background: rgba(20, 32, 59, 0.08);
            color: var(--primary);
        }
        .stage {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
            align-items: start;
        }
        .badge {
            position: relative;
            width: 100%;
            max-width: 520px;
            min-height: 780px;
            border-radius: 34px;
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid rgba(20, 32, 59, 0.08);
            background: #fff;
        }
        .badge-inner {
            position: relative;
            min-height: 100%;
            display: flex;
            flex-direction: column;
            padding: 28px;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.82), rgba(247,250,255,0.98)),
                radial-gradient(circle at top right, rgba(43,137,217,0.12), transparent 24%);
        }
        .badge.has-background .badge-inner::before {
            content: "";
            position: absolute;
            inset: 0;
            background-position: center;
            background-size: cover;
            background-repeat: no-repeat;
            opacity: 0.08;
            pointer-events: none;
        }
        .front.has-background .badge-inner::before { background-image: url("<?= $frontBackground ?>"); }
        .back.has-background .badge-inner::before { background-image: url("<?= $backBackground ?>"); }
        .band {
            position: relative;
            margin: -28px -28px 24px;
            padding: 26px 28px 22px;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.14), transparent),
                linear-gradient(135deg, #312d28 0%, var(--metal) 100%);
            color: #fff;
            border-bottom: 8px solid var(--primary);
        }
        .band h2 {
            margin: 0;
            text-align: center;
            font-family: var(--headline-font);
            font-size: clamp(2rem, 3vw, 3rem);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .band p {
            margin: 10px 0 0;
            text-align: center;
            font-size: 0.84rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.9);
        }
        .front-top {
            position: relative;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 120px;
            gap: 18px;
            align-items: start;
        }
        .crest {
            width: 100%;
            max-width: 120px;
            object-fit: contain;
            filter: drop-shadow(0 12px 24px rgba(10,18,38,0.12));
        }
        .name {
            margin: 0;
            font-family: var(--headline-font);
            font-size: clamp(2.3rem, 3vw, 3.2rem);
            line-height: 1.02;
            color: var(--primary);
        }
        .title {
            margin-top: 10px;
            color: var(--secondary);
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-size: 1.08rem;
        }
        .pill {
            display: inline-flex;
            margin-top: 14px;
            padding: 9px 14px;
            border-radius: 999px;
            background: rgba(20, 32, 59, 0.08);
            color: var(--secondary);
            font-size: 0.82rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            font-weight: 700;
        }
        .meta {
            position: relative;
            margin-top: 22px;
            display: grid;
            gap: 10px;
            font-size: 1rem;
            line-height: 1.6;
        }
        .meta strong { color: var(--primary); }
        .front-bottom {
            position: relative;
            margin-top: auto;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 210px;
            gap: 18px;
            align-items: end;
        }
        .photo-frame,
        .qr-frame,
        .back-panel {
            border-radius: 26px;
            background: rgba(255,255,255,0.92);
            border: 1px solid rgba(20, 32, 59, 0.08);
            box-shadow: 0 12px 30px rgba(10, 18, 38, 0.08);
        }
        .photo-frame {
            overflow: hidden;
            aspect-ratio: 3 / 4;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .photo-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center top;
            display: block;
        }
        .photo-placeholder {
            padding: 20px;
            text-align: center;
            color: var(--secondary);
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }
        .qr-frame {
            padding: 14px;
            text-align: center;
        }
        .qr-frame img {
            width: 100%;
            height: auto;
            display: block;
            border-radius: 16px;
            background: #fff;
        }
        .qr-frame span {
            display: block;
            margin-top: 10px;
            color: var(--primary);
            font-size: 0.82rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            font-weight: 700;
        }
        .code-strip {
            position: relative;
            margin-top: 14px;
            text-align: center;
            color: var(--primary);
            font-size: 0.88rem;
            letter-spacing: 0.22em;
            text-transform: uppercase;
        }
        .back-panel {
            position: relative;
            padding: 24px;
        }
        .back-panel h3 {
            margin: 0 0 12px;
            font-family: var(--headline-font);
            color: var(--primary);
            font-size: 1.9rem;
        }
        .back-panel p {
            margin: 0;
            color: #5d6b86;
            line-height: 1.75;
        }
        .verify-grid {
            margin-top: 18px;
            display: grid;
            gap: 12px;
        }
        .verify-grid div {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(20, 32, 59, 0.08);
        }
        .verify-grid strong { color: var(--primary); }
        .credential-note {
            margin-top: 18px;
            padding: 16px 18px;
            border-radius: 20px;
            background: rgba(20, 32, 59, 0.05);
            color: #5d6b86;
            line-height: 1.7;
        }
        .website {
            margin-top: auto;
            padding-top: 22px;
            text-align: center;
            color: var(--primary);
            font-weight: 700;
            letter-spacing: 0.28em;
            text-transform: uppercase;
            font-size: 0.86rem;
        }
        .not-found {
            max-width: 720px;
            margin: 90px auto;
            padding: 32px;
            border-radius: 28px;
            background: rgba(255,255,255,0.96);
            box-shadow: var(--shadow);
            text-align: center;
        }
        .landscape {
            max-width: 760px;
            min-height: 520px;
        }
        .landscape .front-top,
        .landscape .front-bottom {
            grid-template-columns: minmax(0, 1.1fr) 180px;
        }
        .landscape .photo-frame { aspect-ratio: 4 / 3; }
        @media (max-width: 980px) {
            .stage { grid-template-columns: 1fr; }
            .badge, .landscape { max-width: 100%; min-height: 680px; }
        }
        @media print {
            body { background: #fff; }
            .notice, .actions { display: none !important; }
            .page { max-width: none; padding: 0; }
            .stage { gap: 8mm; }
            .badge {
                width: var(--card-width);
                min-height: var(--card-height);
                box-shadow: none;
                border-radius: var(--card-radius);
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
<?php if ($card === null): ?>
    <div class="not-found">
        <h1>ID Card Not Found</h1>
        <p>The employee credential you requested could not be verified. Please confirm the credential code and try again.</p>
    </div>
<?php else: ?>
    <div class="page">
        <div class="notice">
            <h1>Employee ID Verification</h1>
            <p>This employee identification card is registered inside the U.S. Capital Private Bank verification workspace. The printed QR code resolves back to this live verification record for direct credential confirmation.</p>
            <?php if (!$isPrint): ?>
                <div class="actions">
                    <button class="btn btn-primary" type="button" onclick="window.print()">Print ID Card</button>
                    <a class="btn btn-secondary" href="https://www.uscapitalprivatebank.com/crm/verify/admin/idcards.php">Back To ID Card Studio</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="stage">
            <section class="badge front<?= $frontBackground !== '' ? ' has-background' : '' ?><?= $isLandscape ? ' landscape' : '' ?>">
                <div class="badge-inner">
                    <div class="band">
                        <h2>U.S. Capital Private Bank</h2>
                        <p>Private Bank . Asset Monetization . Project Funding . Wealth Management</p>
                    </div>

                    <div class="front-top">
                        <div>
                            <h3 class="name"><?= htmlspecialchars($card['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <div class="title"><?= htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="pill"><?= htmlspecialchars($card['affiliation'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <?php if ($logoUrl !== ''): ?><img class="crest" src="<?= $logoUrl ?>" alt="U.S. Capital Private Bank crest"><?php endif; ?>
                    </div>

                    <div class="meta">
                        <?php if ($card['department'] !== ''): ?><div><strong>Department:</strong> <?= htmlspecialchars($card['department'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        <?php if ($card['email'] !== ''): ?><div><strong>Email:</strong> <?= htmlspecialchars($card['email'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        <?php if ($card['phone'] !== ''): ?><div><strong>Phone:</strong> <?= htmlspecialchars($card['phone'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                    </div>

                    <div class="front-bottom">
                        <div class="photo-frame">
                            <?php if ((string) $card['photo_url'] !== ''): ?>
                                <img src="<?= htmlspecialchars($card['photo_url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($card['name'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php else: ?>
                                <div class="photo-placeholder">Employee Photo</div>
                            <?php endif; ?>
                        </div>
                        <div class="qr-frame">
                            <img src="idcard_qr.php?code=<?= urlencode($card['code']) ?>" alt="Employee verification QR code">
                            <span>QR Verification</span>
                        </div>
                    </div>

                    <div class="code-strip">Credential Code <?= htmlspecialchars($card['code'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </section>

            <section class="badge back<?= $backBackground !== '' ? ' has-background' : '' ?><?= $isLandscape ? ' landscape' : '' ?>">
                <div class="badge-inner">
                    <div class="band">
                        <h2>Credential Validation</h2>
                        <p>Institutional Identity and Verification Record</p>
                    </div>

                    <div class="back-panel">
                        <h3>Verified Institutional Credential</h3>
                        <p>This badge identifies the bearer as an approved employee or officer credentialed through the bank’s secure verification workspace. Verification may be completed by QR scan or credential code.</p>

                        <div class="verify-grid">
                            <div><strong>Employee</strong><span><?= htmlspecialchars($card['name'], ENT_QUOTES, 'UTF-8') ?></span></div>
                            <div><strong>Title</strong><span><?= htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8') ?></span></div>
                            <div><strong>Affiliation</strong><span><?= htmlspecialchars($card['affiliation'], ENT_QUOTES, 'UTF-8') ?></span></div>
                            <div><strong>Status</strong><span><?= htmlspecialchars(ucfirst((string) $card['status']), ENT_QUOTES, 'UTF-8') ?></span></div>
                            <div><strong>Issued</strong><span><?= htmlspecialchars(date('F j, Y', strtotime($card['created_at'])), ENT_QUOTES, 'UTF-8') ?></span></div>
                            <div><strong>Code</strong><span><?= htmlspecialchars($card['code'], ENT_QUOTES, 'UTF-8') ?></span></div>
                        </div>

                        <div class="credential-note">
                            This badge is intended for direct institutional identification and internal employee credentialing. If found, please direct verification inquiries through the bank’s verification portal.
                            <?php if ((string) $card['notes'] !== ''): ?>
                                <br><br><strong>Notes:</strong> <?= htmlspecialchars($card['notes'], ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="website">www.uscapitalprivatebank.com</div>
                </div>
            </section>
        </div>
    </div>
<?php endif; ?>
</body>
</html>
