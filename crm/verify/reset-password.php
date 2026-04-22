<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/crm_verify_auth.php';

$users = verify_load_users();
$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$resolved = verify_find_user_by_reset_token($users, $token);
$error = '';
$message = '';

if ($token === '' || $resolved === null) {
    $error = 'This password reset link is invalid or has expired.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($password === '' || $confirmPassword === '') {
        $error = 'Please complete both password fields.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Your password confirmation does not match.';
    } else {
        $username = (string) ($resolved['username'] ?? '');
        $users[$username]['password'] = $password;
        verify_clear_password_reset($users, $username);
        verify_save_users($users);
        $message = 'Your password has been updated. You can sign in now.';
        $resolved = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Verification Password | US Capital Private Bank</title>
    <link rel="stylesheet" href="theme.css">
</head>
<body class="verify-portal">
    <div class="verify-shell">
        <div class="verify-topbar">
            <div class="verify-brand">
                <img src="https://www.uscapitalprivatebank.com/assets/images/logoIcon/logo.png" alt="US Capital Private Bank">
                <div class="verify-brand-copy">
                    <h1>US Capital Private Bank</h1>
                    <p>Verification Password Reset</p>
                </div>
            </div>
            <div class="verify-links">
                <a class="verify-link" href="index.php">Verification Home</a>
                <a class="verify-link" href="register.php">Register</a>
            </div>
        </div>

        <div class="verify-card" style="margin-top:28px;">
            <div class="verify-card-inner">
                <span class="verify-kicker">Secure Password Reset</span>
                <h2 class="verify-title">Set a new verification portal password.</h2>
                <p class="verify-copy" style="max-width:760px;">
                    Use the secure link provided by the verification desk to create a new password for your portal account.
                </p>

                <?php if ($error !== ''): ?>
                    <div class="verify-alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
                <?php if ($message !== ''): ?>
                    <div class="verify-alert success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <?php if ($resolved !== null): ?>
                    <form method="post" style="max-width:540px;">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="verify-form-group">
                            <label class="verify-label" for="password">New Password</label>
                            <input class="verify-input" id="password" name="password" type="password" required>
                        </div>
                        <div class="verify-form-group">
                            <label class="verify-label" for="confirm_password">Confirm New Password</label>
                            <input class="verify-input" id="confirm_password" name="confirm_password" type="password" required>
                        </div>
                        <div class="verify-actions">
                            <button class="verify-button" type="submit">Save Password</button>
                            <a class="verify-button-secondary" href="index.php">Back To Sign In</a>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="verify-actions">
                        <a class="verify-button" href="index.php">Go To Sign In</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
