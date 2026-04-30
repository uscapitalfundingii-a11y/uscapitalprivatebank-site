@push('style')
    <style>
        .identity-legal-page {
            padding: 56px 0 96px;
        }

        .identity-legal-shell {
            display: grid;
            gap: 28px;
        }

        .identity-legal-card,
        .identity-legal-sidecard {
            background: rgba(255, 255, 255, 0.98);
            border: 1px solid rgba(15, 31, 69, 0.08);
            border-radius: 28px;
            box-shadow: 0 24px 60px rgba(15, 31, 69, 0.08);
        }

        .identity-legal-card {
            padding: 34px;
        }

        .identity-legal-grid {
            display: grid;
            gap: 24px;
            grid-template-columns: minmax(0, 1.35fr) minmax(280px, 0.65fr);
            align-items: start;
        }

        .identity-legal-kicker {
            margin: 0 0 12px;
            color: #1ea7ff;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .identity-legal-card h1 {
            margin: 0;
            color: #0f1f45;
            font-size: clamp(2.2rem, 4vw, 3.8rem);
            line-height: 1.04;
            letter-spacing: -0.03em;
        }

        .identity-legal-lead,
        .identity-legal-card p,
        .identity-legal-sidecard p {
            color: #61708d;
            line-height: 1.85;
        }

        .identity-legal-lead {
            margin: 18px 0 0;
            font-size: 1.05rem;
        }

        .identity-legal-body {
            margin-top: 28px;
        }

        .identity-legal-body p + p {
            margin-top: 16px;
        }

        .identity-legal-list {
            margin: 24px 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 14px;
        }

        .identity-legal-list li {
            padding: 16px 18px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(15, 31, 69, 0.03), rgba(30, 167, 255, 0.08));
            border: 1px solid rgba(15, 31, 69, 0.08);
            color: #0f1f45;
            font-weight: 700;
        }

        .identity-legal-sidecard {
            padding: 28px;
            position: sticky;
            top: 110px;
        }

        .identity-legal-sidecard h2 {
            margin: 0;
            color: #0f1f45;
            font-size: 1.45rem;
        }

        .identity-legal-links {
            margin: 22px 0 0;
            padding: 0;
            list-style: none;
            display: grid;
            gap: 12px;
        }

        .identity-legal-links a,
        .identity-legal-contact a {
            color: #0f1f45;
            font-weight: 700;
            text-decoration: none;
            word-break: break-word;
        }

        .identity-legal-links a:hover,
        .identity-legal-contact a:hover {
            color: #1ea7ff;
        }

        .identity-legal-contact {
            margin-top: 24px;
            padding-top: 22px;
            border-top: 1px solid rgba(15, 31, 69, 0.08);
        }

        .identity-legal-signoff {
            margin-top: 26px;
            padding: 20px 22px;
            border-radius: 20px;
            background: #0f1f45;
            color: rgba(255, 255, 255, 0.86);
        }

        .identity-legal-signoff strong {
            display: block;
            color: #fff;
            font-size: 1.05rem;
            margin-bottom: 4px;
        }

        @media (max-width: 991px) {
            .identity-legal-grid {
                grid-template-columns: 1fr;
            }

            .identity-legal-sidecard {
                position: static;
            }
        }
    </style>
@endpush

@push('script')
    <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "Organization",
          "name": "U.S. Capital Private Bank",
          "url": "https://uscapitalprivatebank.com",
          "description": "Independent private banking institution",
          "sameAs": [
            "https://uscapitalprivatebank.com",
            "https://uscapitalprivatebank.com/crm",
            "https://uscapitalprivatebank.org",
            "https://uscapitalfundingii.us"
          ]
        }
    </script>
@endpush

<section class="identity-legal-page">
    <div class="container">
        <div class="identity-legal-shell">
            <div class="identity-legal-grid">
                <article class="identity-legal-card">
                    <p class="identity-legal-kicker">@lang('Regulatory Notice')</p>
                    <h1>@lang('Corporate Identity & Legal Independence')</h1>
                    <p class="identity-legal-lead">
                        U.S. Capital Private Bank formally clarifies that it operates as an independent financial institution and is not affiliated, associated, or under the ownership or control of any entity named “U.S. Capital Global” or similarly titled organizations.
                    </p>

                    <div class="identity-legal-body">
                        <p>
                            Any references suggesting that U.S. Capital Private Bank operates under the umbrella of, or is a subsidiary of, “U.S. Capital Global” are factually incorrect and unauthorized.
                        </p>

                        <p>U.S. Capital Private Bank maintains its own:</p>

                        <ul class="identity-legal-list">
                            <li>Governance structure</li>
                            <li>Compliance framework</li>
                            <li>Operational management</li>
                            <li>Financial and institutional programs</li>
                        </ul>

                        <p>
                            All services, instruments, and client relationships are conducted exclusively under the authority and structure of U.S. Capital Private Bank within the jurisdiction of the International Court of Justice.
                        </p>

                        <p>
                            We take matters of identity, representation, and institutional integrity seriously. Any third-party misrepresentation or inaccurate public information is subject to review and corrective action.
                        </p>

                        <div class="identity-legal-signoff">
                            <strong>U.S. Capital Private Bank</strong>
                            <span>Office of the Trustee Chairman</span>
                        </div>
                    </div>
                </article>

                <aside class="identity-legal-sidecard">
                    <h2>@lang('Official Verification')</h2>
                    <p>
                        For official verification, refer directly to U.S. Capital Private Bank authorized platforms only.
                    </p>

                    <ul class="identity-legal-links">
                        <li><a href="https://uscapitalprivatebank.com" target="_blank" rel="noopener">https://uscapitalprivatebank.com</a></li>
                        <li><a href="https://uscapitalprivatebank.com/crm" target="_blank" rel="noopener">https://uscapitalprivatebank.com/crm</a></li>
                        <li><a href="https://uscapitalprivatebank.org" target="_blank" rel="noopener">https://uscapitalprivatebank.org</a></li>
                        <li><a href="https://uscapitalfundingii.us" target="_blank" rel="noopener">https://uscapitalfundingii.us</a></li>
                    </ul>

                    <div class="identity-legal-contact">
                        <p class="mb-2">@lang('For inquiries regarding institutional identity or verification:')</p>
                        <a href="mailto:customerservice@uscapitalprivatebank.com">customerservice@uscapitalprivatebank.com</a>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</section>
