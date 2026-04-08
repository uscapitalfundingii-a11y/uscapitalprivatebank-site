<?php
$username = trim((string) ($_GET['user'] ?? 'your account'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Access Submitted | US Capital Private Bank</title>
    <link rel="stylesheet" href="theme.css">
</head>
<body class="verify-portal">
    <div class="verify-shell">
        <div class="verify-topbar">
            <div class="verify-brand">
                <img src="https://www.uscapitalprivatebank.com/assets/images/logoIcon/logo.png" alt="US Capital Private Bank">
                <div class="verify-brand-copy">
                    <h1>US Capital Private Bank</h1>
                    <p>Verification Enrollment</p>
                </div>
            </div>
            <div class="verify-links">
                <a class="verify-link" href="index.php">Verification Home</a>
                <a class="verify-link" href="admin/index.php">Admin Review</a>
            </div>
        </div>

        <div class="verify-card" style="margin-top:28px;">
            <div class="verify-card-inner" style="padding:52px 40px;">
                <span class="verify-kicker">Request Received</span>
                <h2 class="verify-title" style="max-width:760px;">Your verification access request has been securely submitted.</h2>
                <p class="verify-copy" style="max-width:760px;">
                    The request for <strong><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></strong> is now pending review.
                    Once approved, you will be able to sign in and upload or manage verifiable bank-issued documents.
                </p>

                <div class="verify-grid" style="margin-top:34px;">
                    <div class="verify-card" style="background:var(--verify-panel-soft);">
                        <div class="verify-card-inner">
                            <h3 style="margin:0 0 14px; font-size:24px;">What happens next</h3>
                            <ul class="verify-feature-list">
                                <li>Your request is sent to a verification administrator for review.</li>
                                <li>Approved users may upload documents and manage verification records.</li>
                                <li>Pending users should not try to sign in until approval is completed.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="verify-card" style="background:var(--verify-panel-soft);">
                        <div class="verify-card-inner">
                            <h3 style="margin:0 0 14px; font-size:24px;">Need immediate assistance?</h3>
                            <p class="verify-copy">If your request is time-sensitive, contact the issuing desk or your relationship team and reference the username you submitted.</p>
                        </div>
                    </div>
                </div>

                <div class="verify-actions" style="margin-top:30px;">
                    <a class="verify-button" href="index.php">Return To Verification Portal</a>
                    <a class="verify-button-secondary" href="register.php">Submit Another Request</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
