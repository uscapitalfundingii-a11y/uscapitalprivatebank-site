@php
    $verifyAction = 'https://www.uscapitalprivatebank.com/crm/verify/verifycode.php';
    $uploadPortal = 'https://www.uscapitalprivatebank.com/crm/verify/';
    $registerPortal = 'https://www.uscapitalprivatebank.com/crm/verify/register.php';
    $sampleQr = 'https://www.uscapitalprivatebank.com/crm/verify/qrcode-sample.png';
@endphp

@push('style')
    <style>
        .verification-hero {
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(circle at top left, rgba(30, 167, 255, 0.18), transparent 34%),
                linear-gradient(180deg, rgba(8, 18, 42, 0.28), rgba(8, 18, 42, 0.5)),
                url("{{ url('/support/what-is-conversational-ai-1.jpg') }}") center center / cover no-repeat;
            min-height: 420px;
        }

        .verification-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(180deg, rgba(9, 20, 46, 0.18), rgba(9, 20, 46, 0.42)),
                radial-gradient(circle at 78% 24%, rgba(30, 167, 255, 0.18), transparent 22%);
        }

        .verification-hero__inner {
            position: relative;
            z-index: 2;
            max-width: 1480px;
            margin: 0 auto;
            padding: 120px 24px 110px;
            color: #fff;
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(320px, 0.95fr);
            gap: 40px;
            align-items: center;
        }

        .verification-hero__eyebrow {
            margin: 0 0 16px;
            color: #bce7ff;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .verification-hero h1 {
            margin: 0;
            max-width: 760px;
            font-size: clamp(2.55rem, 4vw, 4.6rem);
            line-height: 1.04;
            letter-spacing: -0.03em;
            color: #fff;
        }

        .verification-hero__lead {
            max-width: 760px;
            margin: 18px 0 0;
            font-size: 1.08rem;
            line-height: 1.85;
            color: rgba(255, 255, 255, 0.88);
        }

        .verification-hero__visual-wrap {
            display: flex;
            justify-content: flex-end;
        }

        .verification-hero__visual {
            position: relative;
            width: min(100%, 560px);
            min-height: 420px;
            border-radius: 32px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 26px 80px rgba(4, 10, 24, 0.38);
            background:
                linear-gradient(180deg, rgba(10, 22, 45, 0.08), rgba(10, 22, 45, 0.4)),
                url("{{ url('/support/what-is-conversational-ai-1.jpg') }}") center center / cover no-repeat;
        }

        .verification-hero__visual::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(180deg, rgba(6, 15, 32, 0.18), rgba(6, 15, 32, 0.42)),
                radial-gradient(circle at 78% 24%, rgba(43, 207, 255, 0.32), transparent 25%);
        }

        .verification-hero__badge {
            position: absolute;
            z-index: 2;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 14px 18px;
            border-radius: 18px;
            color: #fff;
            font-size: 0.92rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            background: rgba(7, 21, 44, 0.62);
            border: 1px solid rgba(255, 255, 255, 0.14);
            backdrop-filter: blur(12px);
            box-shadow: 0 18px 36px rgba(6, 15, 32, 0.24);
        }

        .verification-hero__badge--top {
            top: 26px;
            left: 26px;
        }

        .verification-hero__badge--bottom {
            right: 26px;
            bottom: 26px;
        }

        .verification-hero__badge-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, #32d1ff, #1787ff);
            box-shadow: 0 10px 24px rgba(30, 167, 255, 0.28);
            font-size: 1rem;
        }

        .verification-page {
            position: relative;
            z-index: 3;
            margin-top: -70px;
            padding: 0 0 80px;
        }

        .verification-page .container {
            max-width: 1480px;
        }

        .verification-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(310px, 0.75fr);
            gap: 24px;
            align-items: start;
        }

        .verification-card,
        .verification-sidebar-card {
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(15, 31, 69, 0.08);
            border-radius: 28px;
            box-shadow: 0 28px 70px rgba(9, 20, 46, 0.14);
        }

        .verification-card {
            padding: 32px;
        }

        .verification-sidebar-card {
            padding: 28px;
        }

        .verification-kicker {
            margin: 0 0 8px;
            color: #1ea7ff;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .verification-card h2,
        .verification-sidebar-card h2 {
            margin: 0;
            color: #0f1f45;
            font-size: 2rem;
            line-height: 1.12;
        }

        .verification-card__lead,
        .verification-sidebar-card p {
            margin: 14px 0 0;
            color: #61708d;
            line-height: 1.8;
        }

        .verification-actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
            margin-top: 28px;
        }

        .verification-panel {
            padding: 24px;
            border-radius: 22px;
            background: linear-gradient(135deg, rgba(15, 31, 69, 0.04), rgba(30, 167, 255, 0.08));
            border: 1px solid rgba(15, 31, 69, 0.08);
        }

        .verification-panel h3 {
            margin: 0 0 12px;
            color: #0f1f45;
            font-size: 1.28rem;
        }

        .verification-panel p {
            margin: 0 0 18px;
            color: #61708d;
            line-height: 1.75;
        }

        .verification-field {
            display: grid;
            gap: 8px;
            margin-bottom: 14px;
        }

        .verification-field label {
            color: #0f1f45;
            font-weight: 700;
        }

        .verification-field input {
            min-height: 54px;
            width: 100%;
            padding: 0 16px;
            border-radius: 14px;
            border: 1px solid rgba(15, 31, 69, 0.12);
            background: rgba(255, 255, 255, 0.96);
            font-size: 1rem;
            color: #1d2840;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .verification-field input:focus {
            border-color: rgba(30, 167, 255, 0.72);
            box-shadow: 0 0 0 4px rgba(30, 167, 255, 0.12);
            outline: none;
        }

        .verification-cta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 16px;
        }

        .verification-cta-row .btn {
            min-width: 170px;
        }

        .verification-register-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 18px;
            border-radius: 12px;
            border: 1px solid rgba(15, 31, 69, 0.12);
            background: rgba(15, 31, 69, 0.04);
            color: #0f1f45;
            font-weight: 700;
        }

        .verification-register-link:hover {
            background: rgba(30, 167, 255, 0.08);
            color: #0f1f45;
        }

        .verification-qr-frame {
            margin-top: 20px;
            padding: 16px;
            border-radius: 22px;
            background: linear-gradient(135deg, rgba(15, 31, 69, 0.03), rgba(30, 167, 255, 0.08));
            border: 1px solid rgba(15, 31, 69, 0.08);
            text-align: center;
        }

        .verification-qr-frame img {
            width: 100%;
            max-width: 210px;
            border-radius: 18px;
            border: 1px solid rgba(15, 31, 69, 0.08);
            background: #fff;
            padding: 10px;
            box-shadow: 0 16px 30px rgba(15, 31, 69, 0.1);
        }

        .verification-info-list {
            margin: 18px 0 0;
            padding-left: 18px;
            color: #61708d;
            line-height: 1.8;
        }

        .verification-info-list strong {
            color: #0f1f45;
        }

        @media (max-width: 1199px) {
            .verification-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767px) {
            .verification-hero__inner {
                padding: 88px 18px 92px;
                grid-template-columns: 1fr;
                gap: 28px;
            }

            .verification-page {
                margin-top: -46px;
            }

            .verification-card,
            .verification-sidebar-card {
                padding: 24px;
                border-radius: 24px;
            }

            .verification-actions {
                grid-template-columns: 1fr;
            }

            .verification-hero__visual {
                min-height: 280px;
            }
        }
    </style>
@endpush

<section class="verification-hero">
    <div class="verification-hero__inner">
        <div class="verification-hero__content">
            <p class="verification-hero__eyebrow">Document Authentication Desk</p>
            <h1>Verify Bank-Issued Documents With Confidence</h1>
            <p class="verification-hero__lead">
                Use the secure verification code issued with your document to confirm authenticity instantly. Approved clients may also continue into the secure upload portal to manage supporting verification files.
            </p>
        </div>
        <div class="verification-hero__visual-wrap">
            <div class="verification-hero__visual" aria-hidden="true">
                <div class="verification-hero__badge verification-hero__badge--top">
                    <span class="verification-hero__badge-mark">✓</span>
                    Issuer-authenticated records
                </div>
                <div class="verification-hero__badge verification-hero__badge--bottom">
                    <span class="verification-hero__badge-mark">QR</span>
                    Real-time code and QR confirmation
                </div>
            </div>
        </div>
    </div>
</section>

<section class="verification-page">
    <div class="container">
        <div class="verification-grid">
            <div class="verification-card">
                <p class="verification-kicker">Verification Portal</p>
                <h2>Secure Code Validation & Client Upload Access</h2>
                <p class="verification-card__lead">
                    This entry page is used to confirm the authenticity of U.S. Capital Private Bank documents and to route approved users into the protected document verification workspace.
                </p>

                <div class="verification-actions">
                    <div class="verification-panel">
                        <h3>Verify Document by Code</h3>
                        <p>Enter the code exactly as it appears on the issued document to check that it was released by U.S. Capital Private Bank.</p>
                        <form method="get" action="{{ $verifyAction }}" class="verify-gcaptcha no-validate">
                            <div class="verification-field">
                                <label for="document_code">@lang('Document Code')</label>
                                <input id="document_code" type="text" name="code" placeholder="e.g. ABC123XYZ" required>
                            </div>
                            <div class="verification-cta-row">
                                <button type="submit" class="btn btn--base">@lang('Verify Document')</button>
                            </div>
                        </form>
                    </div>

                    <div class="verification-panel">
                        <h3>Approved User Upload Access</h3>
                        <p>Approved users can continue into the secure portal to upload verification files, review issued records, or complete internal document follow-up.</p>
                        <div class="verification-cta-row">
                            <a href="{{ $uploadPortal }}" class="btn btn--base">@lang('Open Upload Portal')</a>
                            <a href="{{ $registerPortal }}" class="verification-register-link">@lang('Register')</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="verification-sidebar">
                <div class="verification-sidebar-card">
                    <p class="verification-kicker">Quick Reference</p>
                    <h2>QR Verification Sample</h2>
                    <p>Use the sample below as a reference for the QR structure attached to issued documents and verification materials.</p>
                    <div class="verification-qr-frame">
                        <img src="{{ $sampleQr }}" alt="Sample QR code for document verification">
                    </div>
                </div>

                <div class="verification-sidebar-card mt-4">
                    <p class="verification-kicker">Why It Matters</p>
                    <h2>Trusted Verification Standards</h2>
                    <p>Our verification process is designed to reduce fraud risk and give recipients confidence that issued documents are authentic and traceable.</p>
                    <ul class="verification-info-list">
                        <li><strong>Fraud Prevention:</strong> helps eliminate counterfeit and altered documentation.</li>
                        <li><strong>Instant Validation:</strong> issued codes can be checked in real time against the secure portal.</li>
                        <li><strong>Protected Uploading:</strong> approved users can deliver supporting files through a controlled environment.</li>
                        <li><strong>Issuer Confidence:</strong> recipients can confirm that documents came from U.S. Capital Private Bank.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
