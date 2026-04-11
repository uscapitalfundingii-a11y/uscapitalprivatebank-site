<?php
require_once __DIR__ . '/crm_verify_auth.php';

$code = strtoupper(trim((string) ($_GET['code'] ?? '')));
$card = verify_find_id_card_by_code($code);
$isPrint = isset($_GET['print']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $card ? htmlspecialchars($card['name'], ENT_QUOTES, 'UTF-8') . ' | Employee ID Verification' : 'Employee ID Verification' ?></title>
    <style>
        :root {
            --navy: #18294d;
            --navy-soft: #243c6f;
            --blue: #2b89d9;
            --silver: #b5aa93;
            --paper: #f7f8fb;
            --ink: #101a2f;
            --muted: #6d7892;
            --line: rgba(16, 26, 47, 0.1);
            --shadow: 0 28px 70px rgba(10, 18, 38, 0.18);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--ink);
            background: linear-gradient(180deg, #eef4fb 0%, #ffffff 100%);
        }
        .page {
            max-width: 1180px;
            margin: 0 auto;
            padding: 36px 24px 56px;
        }
        .notice {
            margin-bottom: 24px;
            padding: 22px 24px;
            border-radius: 24px;
            background: rgba(255,255,255,0.96);
            box-shadow: var(--shadow);
        }
        .notice h1 {
            margin: 0 0 8px;
            font-size: clamp(2rem, 3vw, 3rem);
            color: var(--navy);
        }
        .notice p {
            margin: 0;
            line-height: 1.75;
            color: var(--muted);
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
            background: linear-gradient(135deg, #1ea7ff 0%, #0c85d4 100%);
            color: #fff;
        }
        .btn-secondary {
            background: rgba(24, 41, 77, 0.08);
            color: var(--navy);
        }
        .card-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
        }
        .id-card {
            min-height: 760px;
            border-radius: 30px;
            overflow: hidden;
            background: #fff;
            box-shadow: var(--shadow);
            border: 1px solid rgba(24, 41, 77, 0.08);
            display: flex;
            flex-direction: column;
        }
        .card-top {
            padding: 22px 26px 18px;
            background:
                linear-gradient(180deg, rgba(0,0,0,0.08), rgba(0,0,0,0)),
                linear-gradient(135deg, #2d2b27 0%, #8f836f 100%);
            color: #fff;
            border-bottom: 8px solid #111c34;
        }
        .card-top h2 {
            margin: 0;
            text-align: center;
            font-size: clamp(1.8rem, 2.6vw, 2.5rem);
            letter-spacing: 0.08em;
        }
        .card-top p {
            margin: 10px 0 0;
            text-align: center;
            font-size: 0.86rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.9);
        }
        .card-body {
            flex: 1;
            padding: 24px 26px 22px;
            display: flex;
            flex-direction: column;
            background:
                radial-gradient(circle at top right, rgba(43,137,217,0.08), transparent 24%),
                linear-gradient(180deg, #fbfcfe 0%, #f4f7fb 100%);
        }
        .identity-head {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 120px;
            gap: 18px;
            align-items: start;
        }
        .identity-head img {
            width: 100%;
            height: auto;
            object-fit: contain;
            filter: drop-shadow(0 12px 24px rgba(10,18,38,0.12));
        }
        .name {
            margin: 0;
            font-size: clamp(2.1rem, 3.1vw, 3rem);
            line-height: 1.04;
            color: var(--navy);
        }
        .title {
            margin-top: 12px;
            color: var(--navy-soft);
            font-size: 1.18rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .pill {
            display: inline-flex;
            margin-top: 14px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(24, 41, 77, 0.08);
            color: var(--navy-soft);
            font-size: 0.82rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            font-weight: 700;
        }
        .meta {
            margin-top: 22px;
            display: grid;
            gap: 10px;
            color: var(--ink);
            font-size: 1.02rem;
            line-height: 1.6;
        }
        .meta strong {
            color: var(--navy);
        }
        .front-bottom {
            margin-top: auto;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 220px;
            gap: 18px;
            align-items: end;
        }
        .photo-frame {
            aspect-ratio: 3 / 4;
            border-radius: 26px;
            overflow: hidden;
            background:
                linear-gradient(180deg, rgba(24, 41, 77, 0.08), rgba(24, 41, 77, 0.02)),
                #ffffff;
            border: 1px solid rgba(24, 41, 77, 0.1);
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
            color: var(--navy-soft);
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }
        .qr-frame {
            background: #fff;
            border-radius: 26px;
            padding: 14px;
            border: 1px solid rgba(24, 41, 77, 0.1);
            text-align: center;
        }
        .qr-frame img {
            width: 100%;
            height: auto;
            display: block;
            background: #fff;
            border-radius: 16px;
        }
        .qr-frame span {
            display: block;
            margin-top: 10px;
            color: var(--navy);
            font-size: 0.84rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }
        .code-strip {
            margin-top: 14px;
            text-align: center;
            color: var(--navy);
            letter-spacing: 0.24em;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        .back-body {
            flex: 1;
            padding: 26px;
            display: flex;
            flex-direction: column;
            background:
                radial-gradient(circle at top left, rgba(43,137,217,0.1), transparent 20%),
                linear-gradient(180deg, #fcfdff 0%, #f3f6fb 100%);
        }
        .back-panel {
            padding: 22px 24px;
            border-radius: 24px;
            background: rgba(255,255,255,0.96);
            border: 1px solid rgba(24, 41, 77, 0.08);
        }
        .back-panel h3 {
            margin: 0 0 12px;
            color: var(--navy);
            font-size: 1.8rem;
        }
        .back-panel p {
            margin: 0;
            color: var(--muted);
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
            gap: 18px;
            padding: 14px 0;
            border-bottom: 1px solid var(--line);
        }
        .verify-grid strong {
            color: var(--navy);
        }
        .credential-note {
            margin-top: 18px;
            padding: 16px 18px;
            border-radius: 20px;
            background: rgba(24, 41, 77, 0.05);
            color: var(--muted);
            line-height: 1.72;
        }
        .website {
            margin-top: auto;
            padding-top: 22px;
            text-align: center;
            color: var(--navy);
            font-weight: 700;
            letter-spacing: 0.28em;
            text-transform: uppercase;
            font-size: 0.9rem;
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
        @media (max-width: 980px) {
            .card-grid { grid-template-columns: 1fr; }
            .id-card { min-height: 680px; }
        }
        @media print {
            body { background: #fff; }
            .notice, .actions { display: none !important; }
            .page { max-width: none; padding: 0; }
            .card-grid { gap: 8mm; }
            .id-card { box-shadow: none; min-height: 88mm; break-inside: avoid; }
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
            <p>This employee identification card is registered inside the U.S. Capital Private Bank verification workspace. The QR code printed on the badge resolves back to this verification record for live credential confirmation.</p>
            <?php if (!$isPrint): ?>
                <div class="actions">
                    <button class="btn btn-primary" type="button" onclick="window.print()">Print ID Card</button>
                    <a class="btn btn-secondary" href="https://www.uscapitalprivatebank.com/crm/verify/admin/idcards.php">Back To ID Card Studio</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="card-grid">
            <section class="id-card">
                <div class="card-top">
                    <h2>U.S. CAPITAL PRIVATE BANK</h2>
                    <p>Private Bank . Asset Monetization . Project Funding . Wealth Management</p>
                </div>
                <div class="card-body">
                    <div class="identity-head">
                        <div>
                            <h3 class="name"><?= htmlspecialchars($card['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <div class="title"><?= htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="pill"><?= htmlspecialchars($card['affiliation'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <img src="uscapital-private-bank-white.png" alt="U.S. Capital Private Bank crest">
                    </div>

                    <div class="meta">
                        <?php if ($card['department'] !== ''): ?><div><strong>Department:</strong> <?= htmlspecialchars($card['department'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        <?php if ($card['email'] !== ''): ?><div><strong>Email:</strong> <?= htmlspecialchars($card['email'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        <?php if ($card['phone'] !== ''): ?><div><strong>Phone:</strong> <?= htmlspecialchars($card['phone'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                    </div>

                    <div class="front-bottom">
                        <div class="photo-frame">
                            <?php if ($card['photo_url'] !== ''): ?>
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

                    <div class="code-strip">ID Code <?= htmlspecialchars($card['code'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </section>

            <section class="id-card">
                <div class="card-top">
                    <h2>Credential Validation</h2>
                    <p>Institutional Identity and Verification Record</p>
                </div>
                <div class="back-body">
                    <div class="back-panel">
                        <h3>Verified Institutional Credential</h3>
                        <p>This employee credential was issued through the bank's secure verification workspace and may be authenticated by QR code or credential code.</p>

                        <div class="verify-grid">
                            <div><strong>Employee</strong><span><?= htmlspecialchars($card['name'], ENT_QUOTES, 'UTF-8') ?></span></div>
                            <div><strong>Title</strong><span><?= htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8') ?></span></div>
                            <div><strong>Affiliation</strong><span><?= htmlspecialchars($card['affiliation'], ENT_QUOTES, 'UTF-8') ?></span></div>
                            <div><strong>Status</strong><span><?= htmlspecialchars(ucfirst($card['status']), ENT_QUOTES, 'UTF-8') ?></span></div>
                            <div><strong>Issued</strong><span><?= htmlspecialchars(date('F j, Y', strtotime($card['created_at'])), ENT_QUOTES, 'UTF-8') ?></span></div>
                            <div><strong>Code</strong><span><?= htmlspecialchars($card['code'], ENT_QUOTES, 'UTF-8') ?></span></div>
                        </div>

                        <div class="credential-note">
                            This badge identifies the bearer as an approved employee or officer of U.S. Capital Private Bank for internal and institutional credentialing purposes. If found, please direct verification inquiries through the bank's verification portal.
                            <?php if ($card['notes'] !== ''): ?>
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
