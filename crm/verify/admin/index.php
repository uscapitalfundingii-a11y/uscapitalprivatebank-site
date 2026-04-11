<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

require_once dirname(__DIR__) . '/crm_verify_auth.php';

if (!empty($_SESSION['upload_authenticated']) && verify_current_role() === 'admin') {
    $_SESSION['verify_admin_authenticated'] = true;
}

const VERIFY_ROLE_OPTIONS = [
    'admin' => 'Admin',
    'trustee' => 'Trustee',
    'client' => 'Client',
    'customer' => 'Customer',
    'bank_officer' => 'Bank Officer',
];

function verify_role_label(string $role): string
{
    return VERIFY_ROLE_OPTIONS[$role] ?? ucfirst(str_replace('_', ' ', $role));
}

function verify_send_approval_email(string $username, array $entry, string $role): bool
{
    $email = trim((string) ($entry['email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $roleLabel = match ($role) {
        'admin' => 'Verification Administrator',
        'trustee' => 'Trustee Reviewer',
        'client' => 'Client',
        'customer' => 'Customer',
        'bank_officer' => 'Bank Officer',
        default => verify_role_label($role),
    };
    $loginUrl = 'https://www.uscapitalprivatebank.com/crm/verify/';
    $subject = 'Your Verification Access Has Been Approved';
    $message = "Hello {$username},\n\n"
        . "Your access request for the U.S. Capital Private Bank document verification workspace has been approved.\n\n"
        . "Assigned role: {$roleLabel}\n"
        . "Login: {$loginUrl}\n\n"
        . "You can now sign in using the username and password you registered with.\n\n"
        . "If you did not request this access, please contact the verification desk immediately.\n\n"
        . "U.S. Capital Private Bank Verification Desk";
    $headers = implode("\r\n", [
        'From: noreply@uscapitalprivatebank.com',
        'Reply-To: chairman@uscapitalprivatebank.com',
        'Content-Type: text/plain; charset=UTF-8',
    ]);

    return @mail($email, $subject, $message, $headers);
}

$users = verify_load_users();
$adminPassword = (string) ($users['admin']['password'] ?? verify_default_admin_record()['password']);

$error = '';
$message = '';

if (isset($_GET['logout'])) {
    unset($_SESSION['verify_admin_authenticated']);
    header('Location: index.php');
    exit;
}

if (isset($_POST['admin_password'])) {
    if (hash_equals($adminPassword, (string) $_POST['admin_password'])) {
        $_SESSION['verify_admin_authenticated'] = true;
        header('Location: index.php');
        exit;
    }
    $error = 'Invalid verification administrator password.';
}

if (!empty($_SESSION['verify_admin_authenticated'])) {
    if (isset($_POST['approve_user'])) {
        $target = (string) $_POST['approve_user'];
        $role = (string) ($_POST['role'] ?? 'trustee');
        if (!array_key_exists($role, VERIFY_ROLE_OPTIONS)) {
            $role = 'trustee';
        }
        if (isset($users[$target]) && is_array($users[$target])) {
            $users[$target]['status'] = 'approved';
            $users[$target]['role'] = $role;
            verify_save_users($users);
            $mailSent = verify_send_approval_email($target, $users[$target], $role);
            $message = "Approved {$target} as {$role}." . ($mailSent ? ' Confirmation email sent.' : ' Approval saved, but confirmation email could not be sent.');
        }
    }

    if (isset($_POST['reject_user'])) {
        $target = (string) $_POST['reject_user'];
        if (isset($users[$target]) && is_array($users[$target])) {
            $users[$target]['status'] = 'rejected';
            verify_save_users($users);
            $message = "Rejected {$target}.";
        }
    }

    if (isset($_POST['update_role_user'])) {
        $target = (string) $_POST['update_role_user'];
        $role = (string) ($_POST['role'] ?? 'trustee');
        if (!array_key_exists($role, VERIFY_ROLE_OPTIONS)) {
            $role = 'trustee';
        }
        if (isset($users[$target]) && is_array($users[$target]) && $target !== 'admin') {
            $users[$target]['role'] = $role;
            verify_save_users($users);
            $mailSent = verify_send_approval_email($target, $users[$target], $role);
            $message = "Updated {$target} to {$role}." . ($mailSent ? ' Confirmation email sent.' : ' Role saved, but confirmation email could not be sent.');
        }
    }

    $pending = [];
    $approved = [];
    foreach ($users as $username => $entry) {
        if ($username === 'admin' || !is_array($entry)) {
            continue;
        }
        if (($entry['status'] ?? 'pending') === 'approved') {
            $approved[$username] = $entry;
        } else {
            $pending[$username] = $entry;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Admin | US Capital Private Bank</title>
    <link rel="stylesheet" href="../theme.css">
</head>
<body class="verify-portal">
    <div class="verify-shell">
        <div class="verify-topbar">
            <div class="verify-brand">
                <img src="https://www.uscapitalprivatebank.com/assets/images/logoIcon/logo.png" alt="US Capital Private Bank">
                <div class="verify-brand-copy">
                    <h1>US Capital Private Bank</h1>
                    <p>Verification Administration</p>
                </div>
            </div>
            <div class="verify-links">
                <a class="verify-link" href="https://www.uscapitalprivatebank.com/">Home</a>
                <a class="verify-link" href="../index.php">Verification Home</a>
                <?php if (!empty($_SESSION['verify_admin_authenticated'])): ?>
                    <a class="verify-link" href="idcards.php">ID Card Studio</a>
                    <a class="verify-link" href="certificates.php">Certificate Studio</a>
                <?php endif; ?>
                <?php if (!empty($_SESSION['verify_admin_authenticated'])): ?>
                    <a class="verify-button-secondary" href="index.php?logout=1">Sign Out</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($_SESSION['verify_admin_authenticated'])): ?>
            <div class="verify-card" style="margin-top:28px;">
                <div class="verify-card-inner">
                    <span class="verify-kicker">Restricted Access</span>
                    <h2 class="verify-title">Verification administration sign-in.</h2>
                    <p class="verify-copy" style="max-width:760px;">
                        This area is reserved for the bank’s verification desk. Use the administrator password to review pending access requests and manage approval states.
                    </p>

                    <div style="max-width:540px; margin-top:30px;">
                        <?php if ($error !== ''): ?>
                            <div class="verify-alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>

                        <form method="post">
                            <div class="verify-form-group">
                                <label class="verify-label" for="admin_password">Administrator Password</label>
                                <input class="verify-input" id="admin_password" name="admin_password" type="password" placeholder="Enter admin password" required>
                            </div>
                            <div class="verify-actions">
                                <button class="verify-button" type="submit">Sign In</button>
                                <a class="verify-button-secondary" href="../index.php">Back</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="verify-card" style="margin-top:28px;">
                <div class="verify-card-inner">
                    <?php if ($message !== ''): ?>
                        <div class="verify-alert success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>

                    <span class="verify-kicker">Verification Desk</span>
                    <h2 class="verify-title" style="max-width: 920px; font-size: clamp(30px, 3.6vw, 44px);">Pending access reviews and approved users.</h2>
                    <p class="verify-copy">Use this control desk to approve new verification enrollments, assign roles, control who can upload versus who can only review and print records, and manage access to the employee ID and certificate studios.</p>
                </div>
            </div>

            <div class="verify-grid" style="margin-top:26px; grid-template-columns:1fr;">
                <div class="verify-card">
                    <div class="verify-card-inner">
                        <h3 style="margin:0 0 18px; font-size:24px;">Pending Requests</h3>
                        <?php if (empty($pending)): ?>
                            <div class="verify-empty">No pending verification requests at the moment.</div>
                        <?php else: ?>
                            <div style="overflow-x:auto;">
                            <table class="verify-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Assign Role</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending as $username => $entry): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars((string) ($entry['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><span class="verify-status <?= htmlspecialchars((string) ($entry['status'] ?? 'pending'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($entry['status'] ?? 'pending'), ENT_QUOTES, 'UTF-8') ?></span></td>
                                            <td>
                                                <form class="verify-inline-form" method="post" style="display:inline-flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                                    <input type="hidden" name="approve_user" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>">
                                                    <select class="verify-input" name="role" style="min-height:40px; width:auto; min-width:120px;">
                                                        <?php foreach (VERIFY_ROLE_OPTIONS as $roleValue => $roleLabel): ?>
                                                            <option value="<?= htmlspecialchars($roleValue, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button class="verify-link" type="submit" style="cursor:pointer;">Approve</button>
                                                </form>
                                            </td>
                                            <td>
                                                <form class="verify-inline-form" method="post" style="display:inline-flex;">
                                                    <input type="hidden" name="reject_user" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>">
                                                    <button class="verify-link" type="submit" style="cursor:pointer;">Reject</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="verify-card">
                    <div class="verify-card-inner">
                        <h3 style="margin:0 0 18px; font-size:24px;">Approved Users</h3>
                        <?php if (empty($approved)): ?>
                            <div class="verify-empty">No approved users have been recorded yet.</div>
                        <?php else: ?>
                            <div style="overflow-x:auto;">
                            <table class="verify-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Role</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($approved as $username => $entry): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars((string) ($entry['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><span class="verify-status approved">approved</span></td>
                                            <td><?= htmlspecialchars(verify_role_label((string) ($entry['role'] ?? 'trustee')), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <form class="verify-inline-form" method="post" style="display:inline-flex; gap:8px; align-items:center;">
                                                    <input type="hidden" name="update_role_user" value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>">
                                                    <select class="verify-input" name="role" style="min-height:40px; width:auto; min-width:120px;">
                                                        <?php foreach (VERIFY_ROLE_OPTIONS as $roleValue => $roleLabel): ?>
                                                            <option value="<?= htmlspecialchars($roleValue, ENT_QUOTES, 'UTF-8') ?>"<?= (($entry['role'] ?? 'trustee') === $roleValue) ? ' selected' : '' ?>><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button class="verify-link" type="submit" style="cursor:pointer;">Save Role</button>
                                                </form>
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
        <?php endif; ?>
    </div>
</body>
</html>
