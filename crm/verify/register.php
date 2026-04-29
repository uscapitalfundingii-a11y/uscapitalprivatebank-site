<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/crm_verify_auth.php';

$credentials = verify_load_users();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $organization = trim((string) ($_POST['organization'] ?? ''));
    $reference = trim((string) ($_POST['reference'] ?? ''));

    if ($username === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $error = 'Please complete every required field before submitting your verification access request.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Your password confirmation does not match.';
    } elseif (isset($credentials[$username])) {
        $error = 'That username already exists in the verification system.';
    } else {
        $credentials[$username] = [
            'password' => $password,
            'email' => $email,
            'organization' => $organization,
            'reference' => $reference,
            'status' => 'pending',
            'role' => 'trustee',
            'created_at' => date('c'),
        ];

        verify_save_users($credentials);

        $approvalLink = 'https://www.uscapitalprivatebank.com/crm/verify/admin/index.php?user=' . urlencode($username);
        $subject = 'New Verification Access Request';
        $message = "A new verification access request has been submitted.\n\n"
            . "Username: {$username}\n"
            . "Email: {$email}\n"
            . "Organization: {$organization}\n"
            . "Reference: {$reference}\n\n"
            . "Review: {$approvalLink}";

        @mail('chairman@uscapitalprivatebank.com', $subject, $message, 'From: noreply@uscapitalprivatebank.com');

        header('Location: register-success.php?user=' . urlencode($username));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register For Verification Access | US Capital Private Bank</title>
    <link rel="stylesheet" href="theme.css">
</head>
<body class="verify-portal">
    <div class="verify-shell">
        <div class="verify-topbar">
            <div class="verify-brand">
                <img src="https://www.uscapitalprivatebank.com/assets/images/logoIcon/logo.png" alt="US Capital Private Bank">
                <div class="verify-brand-copy">
                    <h1>US Capital Private Bank</h1>
                    <p>Secure Verification Enrollment</p>
                </div>
            </div>
            <div class="verify-links">
                <a class="verify-link" href="index.php">Verification Home</a>
                <a class="verify-link" href="admin/index.php">Admin Review</a>
            </div>
        </div>

        <div class="verify-hero">
            <div class="verify-card">
                <div class="verify-card-inner">
                    <span class="verify-kicker">Protected Enrollment</span>
                    <h2 class="verify-title">Request document verification access.</h2>
                    <p class="verify-copy">
                        This workspace is used for secure document verification, issuance tracking, and approved-client upload management.
                        Register below and your access request will be routed for internal approval. Once approved, the bank can assign your access level for trustee review or verification administration.
                    </p>

                    <div class="verify-actions">
                        <a class="verify-button-secondary" href="index.php">Back To Verification</a>
                        <a class="verify-button-secondary" href="dashboard.php">User Dashboard</a>
                    </div>
                </div>
            </div>

            <div class="verify-card">
                <div class="verify-qr-panel">
                    <div>
                        <div class="verify-qr-frame" style="aspect-ratio:auto; min-height:320px;">
                            <div style="text-align:left;">
                                <h3 style="margin:0 0 16px; font-size:28px;">Access standards</h3>
                                <ul class="verify-feature-list">
                                    <li>Pending registrations remain blocked until a bank reviewer approves them.</li>
                                    <li>Trustee-level users can review and print records already in the system.</li>
                                    <li>Administrative users can upload issued documents and manage repository controls.</li>
                                    <li>Access requests are logged with email, institution, and internal reference notes.</li>
                                </ul>
                            </div>
                        </div>
                        <p class="verify-footer-note">Private-banking document systems are intentionally controlled. Approval protects client files, issuer records, and verification history.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="verify-card" style="margin-top:26px;">
            <div class="verify-card-inner">
                <?php if ($error !== ''): ?>
                    <div class="verify-alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <form method="post" novalidate>
                    <div class="verify-grid">
                        <div>
                            <div class="verify-form-group">
                                <label class="verify-label" for="username">Username</label>
                                <input class="verify-input" id="username" name="username" type="text" value="<?= htmlspecialchars((string) ($_POST['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Create your verification username" required>
                            </div>
                            <div class="verify-form-group">
                                <label class="verify-label" for="email">Email Address</label>
                                <input class="verify-input" id="email" name="email" type="email" value="<?= htmlspecialchars((string) ($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="name@example.com" required>
                            </div>
                            <div class="verify-form-group">
                                <label class="verify-label" for="organization">Institution / Organization</label>
                                <input class="verify-input" id="organization" name="organization" type="text" value="<?= htmlspecialchars((string) ($_POST['organization'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Optional organization or office name">
                            </div>
                        </div>

                        <div>
                            <div class="verify-form-group">
                                <label class="verify-label" for="reference">Internal Reference</label>
                                <input class="verify-input" id="reference" name="reference" type="text" value="<?= htmlspecialchars((string) ($_POST['reference'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Client reference, deal number, or desk note">
                            </div>
                            <div class="verify-form-group">
                                <label class="verify-label" for="password">Password</label>
                                <input class="verify-input" id="password" name="password" type="password" placeholder="Create a secure password" required>
                            </div>
                            <div class="verify-form-group">
                                <label class="verify-label" for="confirm_password">Confirm Password</label>
                                <input class="verify-input" id="confirm_password" name="confirm_password" type="password" placeholder="Retype your password" required>
                            </div>
                        </div>
                    </div>

                    <div class="verify-actions">
                        <button class="verify-button" type="submit">Submit Access Request</button>
                        <a class="verify-button-secondary" href="index.php">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

