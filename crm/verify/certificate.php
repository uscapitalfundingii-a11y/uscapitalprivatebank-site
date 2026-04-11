<?php
require_once __DIR__ . '/crm_verify_auth.php';

$code = strtoupper(trim((string) ($_GET['code'] ?? '')));
$certificate = verify_find_certificate_by_code($code);
$isPrint = isset($_GET['print']);
$templates = verify_default_certificate_templates();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $certificate ? htmlspecialchars($certificate['recipient'], ENT_QUOTES, 'UTF-8') . ' | Certificate Verification' : 'Certificate Verification' ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin:0; font-family:"Segoe UI", Arial, sans-serif; color:#172543; background:linear-gradient(180deg, #eef4fb 0%, #ffffff 100%); }
        .page { max-width:1280px; margin:0 auto; padding:36px 24px 56px; }
        .notice { margin-bottom:24px; padding:24px 28px; border-radius:24px; background:#fff; box-shadow:0 28px 70px rgba(10,18,38,.18); }
        .notice h1 { margin:0 0 10px; font-size:clamp(2rem, 3vw, 3rem); color:#18294d; }
        .notice p { margin:0; color:#5d6b86; line-height:1.75; }
        .actions { display:flex; gap:12px; flex-wrap:wrap; margin-top:18px; }
        .btn { display:inline-flex; align-items:center; justify-content:center; padding:12px 18px; border-radius:999px; text-decoration:none; border:0; font:inherit; font-weight:600; cursor:pointer; }
        .btn-primary { background:linear-gradient(135deg, #1ea7ff 0%, #0c85d4 100%); color:#fff; }
        .btn-secondary { background:rgba(24,41,77,.08); color:#18294d; }
        .certificate-wrap { background:#fff; border-radius:34px; overflow:hidden; box-shadow:0 28px 70px rgba(10,18,38,.18); border:1px solid rgba(24,41,77,.08); }
        .certificate-top { padding:28px 32px; background:linear-gradient(135deg, #2d2b27 0%, #8f836f 100%); color:#fff; border-bottom:8px solid #18294d; text-align:center; }
        .certificate-top h2 { margin:0; font-size:clamp(2rem, 3vw, 3rem); letter-spacing:.08em; text-transform:uppercase; }
        .certificate-top p { margin:10px 0 0; letter-spacing:.16em; text-transform:uppercase; opacity:.92; }
        .certificate-body { padding:42px 40px 34px; background:linear-gradient(180deg, #fcfdff 0%, #f4f7fb 100%); }
        .certificate-frame { border:2px solid rgba(143,131,111,.45); border-radius:26px; padding:34px; background:rgba(255,255,255,.92); }
        .certificate-kicker { color:#2b89d9; font-size:.84rem; letter-spacing:.16em; text-transform:uppercase; font-weight:700; text-align:center; }
        .certificate-title { margin:12px 0 10px; color:#18294d; font-size:clamp(2.1rem, 3vw, 3.2rem); text-align:center; }
        .certificate-template { text-align:center; color:#44557b; font-size:1.18rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
        .certificate-recipient { margin:34px 0 8px; text-align:center; font-size:clamp(2.2rem, 3.8vw, 4rem); color:#18294d; font-weight:800; }
        .certificate-role { text-align:center; color:#4f6486; font-size:1.12rem; }
        .certificate-body-copy { margin:28px auto 0; max-width:860px; text-align:center; color:#53627f; line-height:1.9; font-size:1.06rem; }
        .certificate-meta { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:18px; margin-top:34px; }
        .certificate-meta-item { padding:16px 18px; border-radius:18px; background:#f4f7fb; border:1px solid rgba(24,41,77,.08); text-align:center; }
        .certificate-meta-item strong { display:block; color:#18294d; font-size:.82rem; text-transform:uppercase; letter-spacing:.1em; }
        .certificate-meta-item span { display:block; margin-top:8px; color:#4f6486; font-weight:700; }
        .certificate-footer { display:grid; grid-template-columns:minmax(0,1fr) 170px; gap:24px; align-items:end; margin-top:34px; }
        .signoff { color:#4f6486; line-height:1.7; }
        .signoff strong { display:block; color:#18294d; font-size:1.08rem; }
        .qr-box { padding:14px; border-radius:22px; border:1px solid rgba(24,41,77,.08); background:#fff; text-align:center; }
        .qr-box img { width:100%; height:auto; background:#fff; border-radius:16px; }
        .qr-box span { display:block; margin-top:10px; color:#18294d; font-size:.78rem; letter-spacing:.14em; text-transform:uppercase; font-weight:700; }
        .code-row { margin-top:20px; text-align:center; color:#18294d; letter-spacing:.22em; font-size:.86rem; text-transform:uppercase; }
        .not-found { max-width:720px; margin:90px auto; padding:32px; border-radius:28px; background:#fff; box-shadow:0 28px 70px rgba(10,18,38,.18); text-align:center; }
        @media (max-width:900px) { .certificate-meta, .certificate-footer { grid-template-columns:1fr; } }
        @media print { body { background:#fff; } .notice, .actions { display:none !important; } .page { max-width:none; padding:0; } .certificate-wrap { box-shadow:none; } }
    </style>
</head>
<body>
<?php if ($certificate === null): ?>
    <div class="not-found">
        <h1>Certificate Not Found</h1>
        <p>The certificate you requested could not be verified. Please confirm the certificate code and try again.</p>
    </div>
<?php else: ?>
    <div class="page">
        <div class="notice">
            <h1>Certificate Verification</h1>
            <p>This recognition certificate is registered inside the U.S. Capital Private Bank verification workspace. The QR code resolves back to this live record for direct authenticity confirmation.</p>
            <?php if (!$isPrint): ?>
                <div class="actions">
                    <button class="btn btn-primary" type="button" onclick="window.print()">Print Certificate</button>
                    <a class="btn btn-secondary" href="https://www.uscapitalprivatebank.com/crm/verify/admin/certificates.php">Back To Certificate Studio</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="certificate-wrap">
            <div class="certificate-top">
                <h2>U.S. Capital Private Bank</h2>
                <p>Institutional Recognition and Verified Award Record</p>
            </div>
            <div class="certificate-body">
                <div class="certificate-frame">
                    <div class="certificate-kicker">Official Recognition Certificate</div>
                    <h2 class="certificate-title"><?= htmlspecialchars($certificate['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                    <div class="certificate-template"><?= htmlspecialchars($templates[$certificate['template']] ?? $certificate['template'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="certificate-recipient"><?= htmlspecialchars($certificate['recipient'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php if ((string) $certificate['recipient_title'] !== ''): ?><div class="certificate-role"><?= htmlspecialchars($certificate['recipient_title'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                    <div class="certificate-body-copy"><?= nl2br(htmlspecialchars($certificate['body'], ENT_QUOTES, 'UTF-8')) ?></div>
                    <div class="certificate-meta">
                        <div class="certificate-meta-item"><strong>Department</strong><span><?= htmlspecialchars($certificate['department'] !== '' ? $certificate['department'] : 'Institutional Recognition', ENT_QUOTES, 'UTF-8') ?></span></div>
                        <div class="certificate-meta-item"><strong>Issued On</strong><span><?= htmlspecialchars(date('F j, Y', strtotime($certificate['issued_on'])), ENT_QUOTES, 'UTF-8') ?></span></div>
                        <div class="certificate-meta-item"><strong>Status</strong><span><?= htmlspecialchars(ucfirst($certificate['status']), ENT_QUOTES, 'UTF-8') ?></span></div>
                    </div>
                    <div class="certificate-footer">
                        <div class="signoff">
                            <strong><?= htmlspecialchars($certificate['signed_by'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <?= htmlspecialchars($certificate['signer_title'], ENT_QUOTES, 'UTF-8') ?><br>
                            Verified through the secure U.S. Capital Private Bank credentialing system.
                        </div>
                        <div class="qr-box">
                            <img src="certificate_qr.php?code=<?= urlencode($certificate['code']) ?>" alt="Certificate verification QR code">
                            <span>QR Verification</span>
                        </div>
                    </div>
                    <div class="code-row">Certificate Code <?= htmlspecialchars($certificate['code'], ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
</body>
</html>
