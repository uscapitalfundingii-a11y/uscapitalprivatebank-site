<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$isHub = $current_document_slug === 'regulatory-notice';
$accentMap = [
    'Core Notice'    => '#1d4ed8',
    'Risk'           => '#b45309',
    'Identity'       => '#0f766e',
    'Compliance'     => '#047857',
    'Communication'  => '#4338ca',
    'Public Notice'  => '#be123c',
    'Legal'          => '#334155',
    'Navigation'     => '#475569',
];
?>
<style>
    .regulatory-wrap {
        background: linear-gradient(180deg, #f8fafc 0%, #eef3f8 100%);
        border: 1px solid #d8e2ee;
        border-radius: 6px;
        color: #172033;
        font-family: Georgia, "Times New Roman", serif;
        margin-bottom: 40px;
        overflow: hidden;
    }

    .regulatory-masthead {
        background: #0f1f35;
        color: #fff;
        padding: 34px 38px;
        position: relative;
    }

    .regulatory-masthead:after {
        background: linear-gradient(90deg, #c8a24d, #f2d17d, #c8a24d);
        bottom: 0;
        content: "";
        height: 4px;
        left: 0;
        position: absolute;
        right: 0;
    }

    .regulatory-eyebrow {
        color: #f2d17d;
        display: block;
        font-family: Arial, sans-serif;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 2px;
        margin-bottom: 12px;
        text-transform: uppercase;
    }

    .regulatory-title {
        font-size: 34px;
        line-height: 1.15;
        margin: 0;
    }

    .regulatory-subtitle {
        color: #cbd5e1;
        font-family: Arial, sans-serif;
        font-size: 14px;
        line-height: 1.7;
        margin: 16px 0 0;
        max-width: 820px;
    }

    .regulatory-meta {
        background: #fff;
        border-bottom: 1px solid #d8e2ee;
        display: grid;
        gap: 1px;
        grid-template-columns: repeat(3, 1fr);
    }

    .regulatory-meta div {
        background: #fbfdff;
        font-family: Arial, sans-serif;
        padding: 16px 22px;
    }

    .regulatory-meta span {
        color: #64748b;
        display: block;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .regulatory-meta strong {
        color: #0f172a;
        display: block;
        font-size: 13px;
        margin-top: 5px;
    }

    .regulatory-body {
        padding: 30px;
    }

    .regulatory-document {
        background: #fff;
        border: 1px solid #d8e2ee;
        border-radius: 6px;
        box-shadow: 0 16px 45px rgba(15, 31, 53, .08);
        padding: 30px;
    }

    .regulatory-section {
        border-left: 4px solid #c8a24d;
        margin-bottom: 24px;
        padding-left: 18px;
    }

    .regulatory-section h3,
    .regulatory-index-title {
        color: #0f172a;
        font-size: 20px;
        margin: 0 0 10px;
    }

    .regulatory-section p,
    .regulatory-document p {
        color: #334155;
        font-family: Arial, sans-serif;
        font-size: 14px;
        line-height: 1.8;
    }

    .regulatory-index {
        display: grid;
        gap: 16px;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        margin-top: 24px;
    }

    .regulatory-card {
        background: #fff;
        border: 1px solid #d8e2ee;
        border-radius: 6px;
        box-shadow: 0 10px 25px rgba(15, 31, 53, .06);
        display: flex;
        flex-direction: column;
        min-height: 205px;
        padding: 20px;
        position: relative;
        text-decoration: none !important;
    }

    .regulatory-card:before {
        background: var(--accent, #1d4ed8);
        border-radius: 20px;
        content: "";
        height: 9px;
        left: 20px;
        position: absolute;
        top: 18px;
        width: 42px;
    }

    .regulatory-card h4 {
        color: #0f172a;
        font-size: 17px;
        line-height: 1.3;
        margin: 20px 0 8px;
    }

    .regulatory-card p {
        color: #475569;
        font-family: Arial, sans-serif;
        font-size: 13px;
        line-height: 1.55;
        margin: 0 0 18px;
    }

    .regulatory-chip-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: auto;
    }

    .regulatory-chip {
        background: #eef2f7;
        border-radius: 999px;
        color: #334155;
        font-family: Arial, sans-serif;
        font-size: 11px;
        font-weight: 700;
        padding: 6px 9px;
        text-transform: uppercase;
    }

    .regulatory-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 24px;
    }

    .regulatory-button {
        background: #0f1f35;
        border-radius: 4px;
        color: #fff !important;
        display: inline-block;
        font-family: Arial, sans-serif;
        font-size: 13px;
        font-weight: 700;
        padding: 11px 16px;
        text-decoration: none !important;
    }

    .regulatory-button.secondary {
        background: #fff;
        border: 1px solid #cbd5e1;
        color: #0f1f35 !important;
    }

    .regulatory-crosslinks {
        border-top: 1px solid #d8e2ee;
        margin-top: 28px;
        padding-top: 22px;
    }

    @media (max-width: 991px) {
        .regulatory-index {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767px) {
        .regulatory-masthead,
        .regulatory-body,
        .regulatory-document {
            padding: 22px;
        }

        .regulatory-title {
            font-size: 27px;
        }

        .regulatory-meta,
        .regulatory-index {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="regulatory-wrap">
    <header class="regulatory-masthead">
        <span class="regulatory-eyebrow"><?= e($current_document['eyebrow']); ?></span>
        <h1 class="regulatory-title"><?= e($current_document['title']); ?></h1>
        <p class="regulatory-subtitle"><?= e($current_document['description']); ?></p>
    </header>

    <section class="regulatory-meta" aria-label="Document metadata">
        <div>
            <span>Publication Set</span>
            <strong>CRM Legal / Regulatory Hub</strong>
        </div>
        <div>
            <span>Document Status</span>
            <strong><?= e($current_document['status']); ?></strong>
        </div>
        <div>
            <span>Review Path</span>
            <strong>Legal review before publication</strong>
        </div>
    </section>

    <main class="regulatory-body">
        <article class="regulatory-document">
            <?php if ($isHub) { ?>
                <?php foreach ($current_document['sections'] as $section) { ?>
                    <section class="regulatory-section">
                        <h3><?= e($section['heading']); ?></h3>
                        <?php foreach ($section['body'] as $paragraph) { ?>
                            <p><?= e($paragraph); ?></p>
                        <?php } ?>
                    </section>
                <?php } ?>

                <h2 class="regulatory-index-title">Regulatory Document Index</h2>
                <p>Use this index to review the connected public legal, policy, risk, and notice pages.</p>

                <div class="regulatory-index">
                    <?php foreach ($documents as $slug => $document) {
                        $accent = $accentMap[$document['group']] ?? '#1d4ed8';
                        ?>
                        <a class="regulatory-card" style="--accent: <?= e($accent); ?>;" href="<?= site_url($document['path']); ?>">
                            <h4><?= e($document['title']); ?></h4>
                            <p><?= e($document['summary']); ?></p>
                            <span class="regulatory-chip-row">
                                <span class="regulatory-chip"><?= e($document['group']); ?></span>
                                <span class="regulatory-chip"><?= e($document['status']); ?></span>
                            </span>
                        </a>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <section class="regulatory-section">
                    <h3>Disclosure Scope</h3>
                    <p><?= e($current_document['description']); ?></p>
                    <p>This CRM route is part of the complete regulatory disclosure set and should use only reviewed legal wording before publication.</p>
                </section>

                <section class="regulatory-section">
                    <h3>Connected Documents</h3>
                    <p>The full document set remains available from the main Regulatory Notice hub so clients and counterparties can review related notices in context.</p>
                </section>

                <div class="regulatory-actions">
                    <a class="regulatory-button" href="<?= e($regulatory_notice_url); ?>">Back to Regulatory Notice</a>
                    <a class="regulatory-button secondary" href="<?= site_url('privacy-policy'); ?>">Privacy Policy</a>
                    <a class="regulatory-button secondary" href="<?= site_url('terms-and-conditions'); ?>">Terms and Conditions</a>
                </div>

                <div class="regulatory-crosslinks">
                    <h3 class="regulatory-index-title">Related Regulatory Pages</h3>
                    <div class="regulatory-index">
                        <?php foreach ($documents as $slug => $document) {
                            if ($slug === $current_document_slug) {
                                continue;
                            }
                            $accent = $accentMap[$document['group']] ?? '#1d4ed8';
                            ?>
                            <a class="regulatory-card" style="--accent: <?= e($accent); ?>;" href="<?= site_url($document['path']); ?>">
                                <h4><?= e($document['title']); ?></h4>
                                <p><?= e($document['summary']); ?></p>
                                <span class="regulatory-chip-row">
                                    <span class="regulatory-chip"><?= e($document['group']); ?></span>
                                    <span class="regulatory-chip"><?= e($document['status']); ?></span>
                                </span>
                            </a>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </article>
    </main>
</div>
