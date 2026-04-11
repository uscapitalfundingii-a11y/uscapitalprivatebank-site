<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/crm_verify_auth.php';

$validCredentials = verify_load_users();

session_start();

$message = '';
$error = '';

if (isset($_GET['logout'])) {
    unset($_SESSION['upload_authenticated'], $_SESSION['username'], $_SESSION['user_role']);
    header('Location: ' . basename(__FILE__));
    exit;
}

if (isset($_POST['username'], $_POST['password'], $_POST['login'])) {
    $username = trim((string) $_POST['username']);
    $password = (string) $_POST['password'];
    $storedCredentials = $validCredentials[$username] ?? null;
    $storedPassword = is_array($storedCredentials) ? ($storedCredentials['password'] ?? null) : null;
    $storedStatus = is_array($storedCredentials) ? ($storedCredentials['status'] ?? 'pending') : 'pending';
    $storedRole = is_array($storedCredentials) ? ($storedCredentials['role'] ?? 'trustee') : 'trustee';

    if ($storedPassword !== null && hash_equals((string) $storedPassword, $password) && $storedStatus !== 'pending') {
        $_SESSION['upload_authenticated'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['user_role'] = $storedRole;
        header('Location: dashboard.php');
        exit;
    }

    if ($storedStatus === 'pending') {
        $error = 'Your registration is pending approval. Please check back shortly.';
    } else {
        $error = 'Invalid username or password';
    }
}

$isAuthenticated = isset($_SESSION['upload_authenticated']) && $_SESSION['upload_authenticated'] === true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Document Verification | U.S. Capital Private Bank</title>
    <meta name="description" content="Verify bank-issued documents and access the secure document upload portal for approved U.S. Capital Private Bank clients." />
    <style>
        :root {
            --navy: #0f1f45;
            --navy-deep: #09142e;
            --navy-soft: #162955;
            --sky: #1ea7ff;
            --sky-soft: #92ddff;
            --white: #ffffff;
            --text: #1d2840;
            --muted: #63708b;
            --surface: rgba(255, 255, 255, 0.94);
            --border: rgba(15, 31, 69, 0.1);
            --shadow: 0 28px 70px rgba(9, 20, 46, 0.18);
            --danger: #d83535;
            --success: #178a53;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
            background:
                linear-gradient(180deg, #edf4fc 0%, #ffffff 35%, #f5f8fd 100%);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .hero-shell {
            position: relative;
            min-height: 430px;
            background:
                radial-gradient(circle at top left, rgba(30, 167, 255, 0.18), transparent 34%),
                linear-gradient(180deg, rgba(8, 18, 42, 0.28), rgba(8, 18, 42, 0.5)),
                url("https://www.uscapitalprivatebank.com/support/what-is-conversational-ai-1.jpg") center center / cover no-repeat;
            overflow: hidden;
        }

        .hero-shell::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(180deg, rgba(9, 20, 46, 0.12), rgba(9, 20, 46, 0.4)),
                radial-gradient(circle at 78% 24%, rgba(30, 167, 255, 0.2), transparent 22%);
        }

        .site-header {
            position: relative;
            z-index: 5;
            background: rgba(79, 101, 148, 0.18);
            backdrop-filter: blur(12px);
            box-shadow: inset 0 -1px 0 rgba(255, 255, 255, 0.14);
        }

        .site-header__inner {
            max-width: 1500px;
            margin: 0 auto;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            gap: 22px;
        }

        .site-logo img {
            height: 68px;
            width: auto;
            display: block;
        }

        .site-nav {
            display: flex;
            flex: 1;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px 18px;
        }

        .site-nav a {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.98rem;
            transition: color 0.2s ease;
        }

        .site-nav a:hover,
        .site-nav a.is-active {
            color: var(--sky-soft);
        }

        .site-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 18px;
            border-radius: 12px;
            border: 1px solid transparent;
            font-weight: 600;
            transition: transform 0.2s ease, background-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
        }

        .button:hover {
            transform: translateY(-1px);
        }

        .button--solid {
            background: linear-gradient(135deg, #1ea7ff 0%, #0c85d4 100%);
            color: var(--white);
            box-shadow: 0 16px 30px rgba(30, 167, 255, 0.28);
        }

        .button--ghost {
            background: transparent;
            color: var(--white);
            border-color: rgba(255, 255, 255, 0.24);
        }

        .button--ghost:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        .hero-copy {
            position: relative;
            z-index: 4;
            max-width: 1500px;
            margin: 0 auto;
            padding: 80px 24px 120px;
            color: var(--white);
        }

        .eyebrow {
            margin: 0 0 14px;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: #bce7ff;
        }

        .hero-copy h1 {
            margin: 0;
            max-width: 760px;
            font-size: clamp(2.6rem, 4vw, 4.6rem);
            line-height: 1.04;
            letter-spacing: -0.03em;
        }

        .hero-copy p {
            max-width: 760px;
            margin: 18px 0 0;
            font-size: 1.08rem;
            line-height: 1.85;
            color: rgba(255, 255, 255, 0.88);
        }

        .content-shell {
            position: relative;
            z-index: 6;
            max-width: 1500px;
            margin: -64px auto 72px;
            padding: 0 24px;
        }

        .portal-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(300px, 0.75fr);
            gap: 24px;
            align-items: start;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 28px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .portal-card {
            padding: 32px;
        }

        .section-kicker {
            margin: 0 0 8px;
            color: var(--sky);
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .portal-card h2,
        .panel h2 {
            margin: 0;
            font-size: 2rem;
            line-height: 1.1;
        }

        .portal-card__lead {
            margin: 14px 0 0;
            color: var(--muted);
            line-height: 1.8;
            font-size: 1rem;
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-top: 28px;
        }

        .action-panel {
            padding: 24px;
            border-radius: 22px;
            background: linear-gradient(135deg, rgba(15, 31, 69, 0.04), rgba(30, 167, 255, 0.08));
            border: 1px solid rgba(15, 31, 69, 0.08);
        }

        .action-panel h3 {
            margin: 0 0 12px;
            font-size: 1.28rem;
            color: var(--navy);
        }

        .action-panel p {
            margin: 0 0 18px;
            color: var(--muted);
            line-height: 1.75;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--navy);
            font-weight: 700;
        }

        input {
            width: 100%;
            min-height: 54px;
            border-radius: 14px;
            border: 1px solid rgba(15, 31, 69, 0.12);
            background: rgba(255, 255, 255, 0.94);
            padding: 0 16px;
            font-size: 1rem;
            color: var(--text);
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        input:focus {
            border-color: rgba(30, 167, 255, 0.72);
            box-shadow: 0 0 0 4px rgba(30, 167, 255, 0.12);
        }

        .password-field {
            position: relative;
        }

        .password-field input {
            padding-right: 56px;
        }

        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 34px;
            height: 34px;
            border: 0;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            color: var(--muted);
            cursor: pointer;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .password-toggle:hover,
        .password-toggle:focus-visible {
            background: rgba(30, 167, 255, 0.12);
            color: var(--navy);
            outline: none;
        }

        .password-toggle svg {
            width: 19px;
            height: 19px;
            pointer-events: none;
        }

        .password-toggle .eye-off {
            display: none;
        }

        .password-toggle.is-visible .eye-on {
            display: none;
        }

        .password-toggle.is-visible .eye-off {
            display: block;
        }

        .stack {
            display: grid;
            gap: 14px;
        }

        .submit-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 16px;
        }

        .submit-row .button {
            min-width: 170px;
        }

        .register-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 18px;
            border-radius: 12px;
            border: 1px solid rgba(15, 31, 69, 0.14);
            background: rgba(15, 31, 69, 0.04);
            color: var(--navy);
            font-weight: 700;
        }

        .register-link:hover {
            background: rgba(30, 167, 255, 0.08);
        }

        .access-note {
            margin: 18px 0 0;
            padding: 16px 18px;
            border-radius: 16px;
            background: rgba(15, 31, 69, 0.04);
            border: 1px solid rgba(15, 31, 69, 0.08);
            color: var(--muted);
            line-height: 1.75;
            font-size: 0.98rem;
        }

        .access-note a {
            color: var(--navy);
            font-weight: 700;
        }

        .access-note strong {
            color: var(--navy);
        }

        .status {
            margin: 0 0 14px;
            padding: 14px 16px;
            border-radius: 16px;
            font-weight: 600;
        }

        .status--error {
            background: rgba(216, 53, 53, 0.08);
            color: var(--danger);
            border: 1px solid rgba(216, 53, 53, 0.12);
        }

        .status--success {
            background: rgba(23, 138, 83, 0.08);
            color: var(--success);
            border: 1px solid rgba(23, 138, 83, 0.14);
        }

        .panel {
            padding: 28px;
        }

        .panel + .panel {
            margin-top: 24px;
        }

        .panel p {
            color: var(--muted);
            line-height: 1.8;
            margin: 14px 0 0;
        }

        .qr-frame {
            margin-top: 20px;
            padding: 16px;
            border-radius: 22px;
            background: linear-gradient(135deg, rgba(15, 31, 69, 0.03), rgba(30, 167, 255, 0.08));
            border: 1px solid rgba(15, 31, 69, 0.08);
            text-align: center;
        }

        .qr-frame img {
            width: 100%;
            max-width: 210px;
            border-radius: 18px;
            border: 1px solid rgba(15, 31, 69, 0.08);
            background: #fff;
            padding: 10px;
            box-shadow: 0 16px 30px rgba(15, 31, 69, 0.1);
        }

        .info-list {
            margin: 18px 0 0;
            padding-left: 18px;
            color: var(--muted);
            line-height: 1.8;
        }

        .info-list strong {
            color: var(--navy);
        }

        footer {
            text-align: center;
            padding: 0 24px 40px;
            color: #6b7791;
            font-size: 0.92rem;
        }

        @media (max-width: 1180px) {
            .site-header__inner {
                flex-wrap: wrap;
                justify-content: center;
            }

            .site-nav {
                order: 3;
                width: 100%;
            }

            .site-actions {
                order: 2;
            }

            .portal-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 760px) {
            .hero-copy {
                padding: 68px 18px 100px;
            }

            .content-shell {
                padding: 0 18px;
                margin-top: -48px;
            }

            .portal-card,
            .panel {
                padding: 24px;
            }

            .action-grid {
                grid-template-columns: 1fr;
            }

            .site-nav {
                gap: 8px 14px;
            }

            .site-nav a {
                font-size: 0.92rem;
            }
        }
    </style>
</head>
<body>
    <div class="hero-shell">
        <header class="site-header">
            <div class="site-header__inner">
                <a class="site-logo" href="https://www.uscapitalprivatebank.com/">
                    <img src="https://www.uscapitalprivatebank.com/assets/images/logoIcon/logo.png" alt="U.S. Capital Private Bank">
                </a>

                <nav class="site-nav" aria-label="Primary">
                    <a href="https://www.uscapitalprivatebank.com/">Home</a>
                    <a href="https://www.uscapitalprivatebank.com/about">About Us</a>
                    <a href="https://www.uscapitalprivatebank.com/services">Services</a>
                    <a href="https://www.uscapitalprivatebank.com/faq">FAQ</a>
                    <a href="https://www.uscapitalprivatebank.com/swift-nft">Swift-NFT</a>
                    <a href="https://www.uscapitalprivatebank.com/uscpbgold">USCPBGOLD</a>
                    <a href="https://www.uscapitalprivatebank.com/support/">Support</a>
                    <a href="https://www.uscapitalprivatebank.com/crm/admin/authentication">Staff</a>
                    <a class="is-active" href="https://www.uscapitalprivatebank.com/crm/verify">Verification</a>
                    <a href="https://www.uscapitalprivatebank.com/trading">Trading</a>
                    <a href="https://www.uscapitalprivatebank.com/contact">Contact</a>
                </nav>

                <div class="site-actions">
                    <a class="button button--ghost" href="https://www.uscapitalprivatebank.com/user/login">Sign In</a>
                    <a class="button button--solid" href="https://www.uscapitalprivatebank.com/user/register">Sign Up</a>
                </div>
            </div>
        </header>

        <section class="hero-copy">
            <p class="eyebrow">Document Authentication Desk</p>
            <h1>Verify Bank-Issued Documents With Confidence</h1>
            <p>
                Use the secure verification code issued with your document to validate authenticity instantly. Approved clients may also sign in below to upload supporting files into the verification portal.
            </p>
        </section>
    </div>

    <main class="content-shell">
        <div class="portal-grid">
            <section class="card portal-card">
                <p class="section-kicker">Verification Portal</p>
                <h2>Official Document Verification Portal</h2>
                <p class="portal-card__lead">
                    In accordance with established authentication standards, including <a href="https://www.law.cornell.edu/rules/fre/rule_902" target="_blank" rel="noopener">Federal Rule of Evidence 902</a> governing self-authenticating documents, you are hereby instructed to enter the unique Document Verification Code exactly as it appears on the officially issued instrument.
                </p>

                <div class="action-grid">
                    <article class="action-panel" id="code-verification">
                        <h3>Official Document Verification Portal</h3>
                        <p>This verification process serves to confirm the document’s authenticity, integrity, and official registration within the authorized records system. Documents validated through this system may qualify as self-authenticating, requiring no extrinsic evidence of authenticity when properly verified.</p>
                        <p>All entries must match the issued code precisely. Any deviation, omission, or alteration may result in an invalid or rejected verification response.</p>
                        <form method="get" action="verifycode.php" autocomplete="off" class="stack">
                            <div>
                                <label for="doccode">Document Verification Code</label>
                                <input type="text" id="doccode" name="code" placeholder="e.g. ABC123XYZ" required />
                            </div>
                            <div class="submit-row">
                                <button class="button button--solid" type="submit">Verify Document</button>
                            </div>
                        </form>
                    </article>

                    <article class="action-panel" id="upload-access">
                        <h3>Approved User Sign In</h3>
                        <p>Sign in to upload documents, manage supporting files, and continue your secure document verification workflow.</p>
                        <?php if ($error): ?>
                            <div class="status status--error"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <?php if ($message): ?>
                            <div class="status status--success"><?= htmlspecialchars($message) ?></div>
                        <?php endif; ?>
                        <form method="post" autocomplete="off" class="stack">
                            <div>
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" required />
                            </div>
                            <div class="password-field">
                                <label for="password">Password</label>
                                <input type="password" id="password" name="password" required />
                                <button
                                    class="password-toggle"
                                    type="button"
                                    data-password-toggle="password"
                                    aria-label="Show password"
                                    aria-pressed="false"
                                >
                                    <svg class="eye-on" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M1.5 12s3.75-6.75 10.5-6.75S22.5 12 22.5 12s-3.75 6.75-10.5 6.75S1.5 12 1.5 12Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="12" cy="12" r="3.25" stroke="currentColor" stroke-width="1.8"/>
                                    </svg>
                                    <svg class="eye-off" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M3 3l18 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        <path d="M10.58 5.44A10.67 10.67 0 0 1 12 5.25c6.75 0 10.5 6.75 10.5 6.75a19.2 19.2 0 0 1-3.1 3.95M6.73 6.73C3.88 8.36 1.5 12 1.5 12s3.75 6.75 10.5 6.75a10.8 10.8 0 0 0 4.23-.83" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M9.88 9.88A3 3 0 0 0 14.12 14.12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="submit-row">
                                <button class="button button--solid" type="submit" name="login">Sign In</button>
                                <a class="register-link" href="register.php">Register</a>
                            </div>
                        </form>
                        <p class="access-note">
                            <strong>Customer document access:</strong> If you are a customer and want documents issued by the Bank to be verified, <a href="register.php">register here</a>. Once registered, your request can be added to the customer group for upload access. Customers may sign in to upload documents, await approval, and view only their own submitted documents. Any document issued to you may also be verified by entering its document verification code through this portal, just like any other verification request.
                        </p>
                    </article>
                </div>
            </section>

            <aside>
                <section class="card panel">
                    <p class="section-kicker">Quick Reference</p>
                    <h2>QR Verification Sample</h2>
                    <p>Scan or compare the sample QR structure below to understand how authenticated documents are linked back to the verification system.</p>
                    <div class="qr-frame">
                        <img src="qrcode-sample.png" alt="Sample QR code for document verification">
                    </div>
                </section>

                <section class="card panel">
                    <p class="section-kicker">Why It Matters</p>
                    <h2>Trusted Verification Standards</h2>
                    <p>Our verification process is designed to reduce fraud risk and give recipients confidence that issued documents are authentic and traceable.</p>
                    <ul class="info-list">
                        <li><strong>Fraud Prevention:</strong> helps eliminate counterfeit and altered documentation.</li>
                        <li><strong>Instant Validation:</strong> issued codes can be checked in real time against the secure portal.</li>
                        <li><strong>Protected Uploading:</strong> approved users can deliver supporting files through a controlled environment.</li>
                        <li><strong>Issuer Confidence:</strong> recipients can confirm that documents came from U.S. Capital Private Bank.</li>
                    </ul>
                </section>
            </aside>
        </div>
    </main>

    <footer>
        &copy; <?= date('Y') ?> U.S. Capital Private Bank. All rights reserved.
    </footer>
    <script>
        document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                var inputId = button.getAttribute('data-password-toggle');
                var input = document.getElementById(inputId);

                if (!input) {
                    return;
                }

                var isVisible = input.type === 'text';
                input.type = isVisible ? 'password' : 'text';
                button.classList.toggle('is-visible', !isVisible);
                button.setAttribute('aria-pressed', String(!isVisible));
                button.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
            });
        });
    </script>
</body>
</html>
