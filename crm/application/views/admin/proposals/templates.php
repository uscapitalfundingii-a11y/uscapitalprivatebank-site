<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
                    <div>
                        <h4 class="tw-mt-0 tw-mb-1 tw-font-bold tw-text-lg tw-text-neutral-700">
                            Proposal Templates
                        </h4>
                        <p class="text-muted no-mbot">
                            Start proposals from approved CRM-safe language, then personalize before sending.
                        </p>
                    </div>
                    <div>
                        <a href="<?= admin_url('proposals'); ?>" class="btn btn-default">
                            <i class="fa fa-arrow-left tw-mr-1"></i>
                            Back to Proposals
                        </a>
                        <?php if (staff_can('create', 'proposals')) { ?>
                        <a href="<?= admin_url('proposals/proposal'); ?>" class="btn btn-primary">
                            <i class="fa-regular fa-plus tw-mr-1"></i>
                            New Blank Proposal
                        </a>
                        <?php } ?>
                    </div>
                </div>

                <div class="alert alert-warning">
                    <strong>Client-safe rule:</strong>
                    These templates are starting points only. Do not promise approval, funding, returns, KYC outcomes,
                    deposits, wires, bank instruments, legal outcomes, account activation, or transaction results.
                    Restricted matters must be routed to the correct reviewer before customer-facing language is sent.
                </div>

                <div class="row">
                    <?php foreach ($templates as $template) { ?>
                    <div class="col-md-6">
                        <div class="panel_s proposal-template-card">
                            <div class="panel-body">
                                <span class="label label-info">
                                    <?= e($template['category']); ?>
                                </span>
                                <h4 class="tw-font-bold tw-mb-2">
                                    <?= e($template['title']); ?>
                                </h4>
                                <p class="text-muted">
                                    <?= e($template['description']); ?>
                                </p>
                                <p class="tw-font-medium">
                                    Subject:
                                    <span class="text-muted"><?= e($template['subject']); ?></span>
                                </p>
                                <div class="tw-flex tw-gap-2 tw-flex-wrap">
                                    <?php if (staff_can('create', 'proposals')) { ?>
                                    <a href="<?= admin_url('proposals/proposal?template=' . $template['slug']); ?>"
                                        class="btn btn-primary">
                                        <i class="fa-regular fa-file-lines tw-mr-1"></i>
                                        Use Template
                                    </a>
                                    <?php } ?>
                                    <button type="button" class="btn btn-default"
                                        data-toggle="collapse"
                                        data-target="#proposal-template-preview-<?= e($template['slug']); ?>">
                                        <i class="fa-regular fa-eye tw-mr-1"></i>
                                        Preview
                                    </button>
                                    <button type="button" class="btn btn-default proposal-template-copy"
                                        data-template-slug="<?= e($template['slug']); ?>">
                                        <i class="fa-regular fa-copy tw-mr-1"></i>
                                        Copy Body
                                    </button>
                                </div>
                                <div id="proposal-template-preview-<?= e($template['slug']); ?>"
                                    class="collapse mtop15 proposal-template-preview">
                                    <div class="well">
                                        <?= $template['content']; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    (function() {
        var templates = <?= json_encode($templates); ?>;

        $('body').on('click', '.proposal-template-copy', function() {
            var slug = $(this).data('template-slug');
            var template = templates[slug];

            if (!template) {
                alert_float('danger', 'Template not found.');
                return;
            }

            var body = template.content
                .replace(/<br\\s*\\/?>/gi, "\n")
                .replace(/<\\/p>/gi, "\n\n")
                .replace(/<\\/h[1-6]>/gi, "\n\n")
                .replace(/<li>/gi, "- ")
                .replace(/<\\/li>/gi, "\n")
                .replace(/<[^>]+>/g, '')
                .replace(/&nbsp;/g, ' ')
                .trim();

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(body).then(function() {
                    alert_float('success', 'Proposal template copied.');
                });
            } else {
                var textarea = $('<textarea />').val(body).appendTo('body').select();
                document.execCommand('copy');
                textarea.remove();
                alert_float('success', 'Proposal template copied.');
            }
        });
    })();
</script>
</body>
</html>
