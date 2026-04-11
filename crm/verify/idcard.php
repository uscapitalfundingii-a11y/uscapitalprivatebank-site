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
            --navy: #24385e;
            --navy-deep: #13203f;
            --silver: #a89f89;
            --white: #ffffff;
            --ink: #10192f;
            --muted: #6a7691;
            --surface: #eef4fb;
            --shadow: 0 28px 65px rgba(10, 18, 38, 0.18);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(180deg, #eef5fc 0%, #ffffff 100%);
            color: var(--ink);
        }
        .page {
            max-width: 1220px;
            margin: 0 auto;
            padding: 40px 24px 60px;
        }
        .notice {
            margin-bottom: 24px;
            padding: 18px 22px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.94);
            box-shadow: var(--shadow);
        }
        .notice h1 {
            margin: 0 0 8px;
            font-size: clamp(2rem, 3vw, 3rem);
            color: var(--navy-deep);
        }
        .notice p {
            margin: 0;
            line-height: 1.7;
            color: var(--muted);
        }
        .cards {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
        }
        .id-card {
            position: relative;
            min-height: 720px;
            border-radius: 32px;
            overflow: hidden;
            box-shadow: var(--shadow);
            background: #fff;
            border: 1px solid rgba(36, 56, 94, 0.08);
        }
        .id-card__surface {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            filter: saturate(0.9) contrast(1.05);
        }
        .id-card__overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(12, 24, 48, 0.78), rgba(27, 44, 78, 0.7) 28%, rgba(255, 255, 255, 0.1) 30%, rgba(255, 255, 255, 0) 42%);
        }
        .id-card__content {
            position: relative;
            z-index: 2;
            height: 100%;
            padding: 28px 28px 26px;
            display: flex;
            flex-direction: column;
        }
        .card-band {
            margin: -28px -28px 24px;
            padding: 22px 28px 18px;
            background: linear-gradient(180deg, rgba(44, 39, 33, 0.92), rgba(152, 143, 120, 0.92));
            border-bottom: 10px solid rgba(14, 19, 35, 0.88);
        }
        .card-band h2 {
            margin: 0;
            color: var(--white);
            text-align: center;
            font-size: clamp(1.7rem, 2.6vw, 2.5rem);
            letter-spacing: 0.06em;
        }
        .card-band p {
            margin: 10px 0 0;
            color: rgba(255, 255, 255, 0.88);
            text-align: center;
            letter-spacing: 0.22em;
            font-size: 0.86rem;
            text-transform: uppercase;
        }
        .identity-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 190px;
            gap: 20px;
            align-items: start;
        }
        .identity-row img.logo {
            width: 100%;
            max-width: 180px;
            justify-self: end;
            align-self: center;
            filter: drop-shadow(0 8px 18px rgba(0, 0, 0, 0.18));
        }
        .employee-name {
            margin: 0;
            font-size: clamp(2rem, 3vw, 3rem);
            line-height: 1.05;
            color: #121b32;
        }
        .employee-title {
            margin: 10px 0 0;
            font-size: 1.2rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--navy);
            font-weight: 700;
        }
        .employee-affiliation {
            margin-top: 14px;
            display: inline-flex;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(36, 56, 94, 0.08);
            color: var(--navy);
            font-size: 0.84rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .contact-block {
            margin-top: 28px;
            display: grid;
            gap: 10px;
            color: #1b2642;
            font-size: 1.1rem;
            line-height: 1.5;
        }
        .contact-block strong {
            color: var(--navy-deep);
        }
        .bottom-row {
            margin-top: auto;
            display: grid;
            grid-template-columns: 1fr 230px;
            gap: 18px;
            align-items: end;
        }
        .photo-box {
            min-height: 230px;
            border-radius: 24px;
            border: 1px solid rgba(36, 56, 94, 0.12);
            background: linear-gradient(160deg, rgba(15, 31, 69, 0.08), rgba(36, 56, 94, 0.02));
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .photo-placeholder {
            display: grid;
            place-items: center;
            width: 100%;
            height: 100%;
            color: var(--navy);
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .qr-box {
            background: rgba(255, 255, 255, 0.92);
            border-radius: 24px;
            padding: 14px;
            border: 1px solid rgba(36, 56, 94, 0.12);
            text-align: center;
        }
        .qr-box img {
            width: 100%;
            height: auto;
            display: block;
            border-radius: 14px;
            background: #fff;
        }
        .qr-box span {
            display: block;
            margin-top: 10px;
            color: var(--navy-deep);
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }
        .footer-code {
            margin-top: 16px;
            text-align: center;
            color: var(--navy-deep);
            font-size: 0.95rem;
            letter-spacing: 0.28em;
            text-transform: uppercase;
        }
        .back-card .card-band {
            background: linear-gradient(180deg, rgba(42, 38, 33, 0.92), rgba(145, 135, 110, 0.92));
        }
        .back-card .id-card__overlay {
            background: linear-gradient(180deg, rgba(14, 19, 35, 0.6), rgba(14, 19, 35, 0.4) 18%, rgba(255, 255, 255, 0.1) 22%, rgba(255, 255, 255, 0) 45%);
        }
        .back-body {
            margin-top: 12px;
            padding: 18px 20px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(36, 56, 94, 0.08);
            line-height: 1.7;
            color: #202b46;
        }
        .back-body h3 {
            margin: 0 0 10px;
            color: var(--navy-deep);
            font-size: 1.5rem;
        }
        .back-grid {
            display: grid;
            gap: 12px;
            margin-top: 16px;
        }
        .back-grid div {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(36, 56, 94, 0.08);
        }
        .back-grid strong {
            color: var(--navy-deep);
        }
        .website-strip {
            margin-top: auto;
            padding-top: 16px;
            text-align: center;
            color: #111b33;
            letter-spacing: 0.35em;
            text-transform: uppercase;
            font-weight: 700;
        }
        .print-actions {
            display: flex;
            gap: 12px;
            margin-top: 18px;
        }
        .print-actions a, .print-actions button {
            border: 0;
            border-radius: 999px;
            padding: 12px 18px;
            font: inherit;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #1ea7ff 0%, #0c85d4 100%);
            color: #fff;
        }
        .btn-secondary {
            background: rgba(36, 56, 94, 0.08);
            color: var(--navy-deep);
        }
        .not-found {
            max-width: 760px;
            margin: 90px auto;
            padding: 32px;
            border-radius: 28px;
            background: rgba(255,255,255,0.95);
            box-shadow: var(--shadow);
            text-align: center;
        }
        @media (max-width: 980px) {
            .cards { grid-template-columns: 1fr; }
            .id-card { min-height: 660px; }
        }
        @media print {
            body { background: #fff; }
            .notice, .print-actions { display: none !important; }
            .page { max-width: none; padding: 0; }
            .cards { gap: 10mm; }
            .id-card { box-shadow: none; break-inside: avoid; min-height: 88mm; }
        }
    </style>
</head>
<body>
<?php if ($card === null): ?>
    <div class="not-found">
        <h1>ID Card Not Found</h1>
        <p>The employee credential you requested could not be verified. Please confirm the code and try again.</p>
    </div>
<?php else: ?>
    <div class="page">
        <div class="notice">
            <h1>Employee ID Verification</h1>
            <p>This credential is registered in the verification system of U.S. Capital Private Bank. The QR code printed on the employee badge resolves back to this record for issuer-side validation.</p>
            <?php if (!$isPrint): ?>
                <div class="print-actions">
                    <button class="btn-primary" type="button" onclick="window.print()">Print ID Card</button>
                    <a class="btn-secondary" href="https://www.uscapitalprivatebank.com/verification/">Verification Home</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="cards">
            <section class="id-card front-card">
                <div class="id-card__surface" style="background-image:url('idcard-front-reference.jpg');"></div>
                <div class="id-card__overlay"></div>
                <div class="id-card__content">
                    <div class="card-band">
                        <h2>U.S. CAPITAL PRIVATE BANK</h2>
                        <p>Private Bank . Asset Monetization . Project Funding . Wealth Management</p>
                    </div>

                    <div class="identity-row">
                        <div>
                            <h3 class="employee-name"><?= htmlspecialchars($card['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <div class="employee-title"><?= htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="employee-affiliation"><?= htmlspecialchars($card['affiliation'], ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <img class="logo" src="uscapital-private-bank-white.png" alt="U.S. Capital Private Bank crest">
                    </div>

                    <div class="contact-block">
                        <?php if ($card['phone'] !== ''): ?><div><strong>Phone:</strong> <?= htmlspecialchars($card['phone'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        <?php if ($card['email'] !== ''): ?><div><strong>Email:</strong> <?= htmlspecialchars($card['email'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        <?php if ($card['department'] !== ''): ?><div><strong>Department:</strong> <?= htmlspecialchars($card['department'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                    </div>

                    <div class="bottom-row">
                        <div class="photo-box">
                            <?php if ($card['photo_url'] !== ''): ?>
                                <img src="<?= htmlspecialchars($card['photo_url'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($card['name'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php else: ?>
                                <div class="photo-placeholder">Employee Photo</div>
                            <?php endif; ?>
                        </div>
                        <div class="qr-box">
                            <img src="idcard_qr.php?code=<?= urlencode($card['code']) ?>" alt="Employee verification QR code">
                            <span>QR Verification</span>
                        </div>
                    </div>

                    <div class="footer-code">ID Code <?= htmlspecialchars($card['code'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </section>

            <section class="id-card back-card">
                <div class="id-card__surface" style="background-image:url('idcard-back-reference.jpg');"></div>
                <div class="id-card__overlay"></div>
                <div class="id-card__content">
                    <div class="card-band">
                        <h2>Credential Validation</h2>
                        <p>Global Banking Identity and Verification Record</p>
                    </div>

                    <div class="back-body">
                        <h3>Verified Institutional Credential</h3>
                        <p>This employee identification card is issued through the bank's verification workspace and can be confirmed using the printed QR code or credential code.</p>
                        <div class="back-grid">
                            <div><strong>Employee</strong><span><?= htmlspecialchars($card['name'], ENT_QUOTES, 'UTF-8') ?></span></div>
                            <div><strong>Title</strong><span><?= htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8') ?></span></div>
                            <div><strong>Status</strong><span><?= htmlspecialchars(ucfirst($card['status']), ENT_QUOTES, 'UTF-8') ?></span></div>
                            <div><strong>Issued</strong><span><?= htmlspecialchars(date('F j, Y', strtotime($card['created_at'])), ENT_QUOTES, 'UTF-8') ?></span></div>
                            <div><strong>Code</strong><span><?= htmlspecialchars($card['code'], ENT_QUOTES, 'UTF-8') ?></span></div>
                        </div>
                        <?php if ($card['notes'] !== ''): ?>
                            <p style="margin:16px 0 0;"><strong>Notes:</strong> <?= htmlspecialchars($card['notes'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="website-strip">www.uscapitalprivatebank.com</div>
                </div>
            </section>
        </div>
    </div>
<?php endif; ?>
</body>
</html>
