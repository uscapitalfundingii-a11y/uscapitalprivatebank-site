<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="tw-font-bold tw-text-xl tw-mt-0"><?= _l('email_marketing_campaign_progress'); ?></h4>

                        <div class="row mtop15">
                            <div class="col-md-6">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="text-muted"><?= _l('email_marketing_total_recipients'); ?></div>
                                        <div class="h2 tw-mt-2" id="campaign-total"><?= e($campaign['total_recipients']); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="text-muted"><?= _l('email_marketing_sent_count'); ?></div>
                                        <div class="h2 tw-mt-2" id="campaign-sent"><?= e($campaign['sent_count']); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="text-muted"><?= _l('email_marketing_remaining_count'); ?></div>
                                        <div class="h2 tw-mt-2" id="campaign-remaining"><?= e($campaign['remaining_count']); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <div class="text-muted"><?= _l('email_marketing_batch_size_label'); ?></div>
                                        <div class="h2 tw-mt-2" id="campaign-batch"><?= e($campaign['batch_size']); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="panel panel-default">
                            <div class="panel-body text-center">
                                <div class="h1 text-primary" id="campaign-countdown"><?= e($campaign['cooling_seconds']); ?></div>
                                <p class="text-muted" id="campaign-status-text">
                                    <?= e(_l('email_marketing_countdown_message', $campaign['cooling_seconds'])); ?>
                                </p>
                                <p class="text-primary" id="campaign-progress-line">
                                    <?= e(_l('email_marketing_progress_line', [$campaign['sent_count'], $campaign['total_recipients']])); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        var campaignId = <?= (int) $campaign['id']; ?>;
        var status = '<?= e($campaign['status']); ?>';
        var coolingSeconds = <?= (int) $campaign['cooling_seconds']; ?>;

        function refreshUi(campaign) {
            $('#campaign-total').text(campaign.total_recipients);
            $('#campaign-sent').text(campaign.sent_count);
            $('#campaign-remaining').text(campaign.remaining_count);
            $('#campaign-batch').text(campaign.batch_size);
            $('#campaign-progress-line').text(campaign.sent_count + ' out of ' + campaign.total_recipients + ' emails were successfully transmitted');
            status = campaign.status;
        }

        function tickCountdown(seconds) {
            $('#campaign-countdown').text(seconds);
            $('#campaign-status-text').text('<?= str_replace('%s', '__VALUE__', _l('email_marketing_countdown_message', '%s')); ?>'.replace('__VALUE__', seconds));

            if (seconds <= 0) {
                runBatch();
                return;
            }

            setTimeout(function() {
                tickCountdown(seconds - 1);
            }, 1000);
        }

        function runBatch() {
            if (status === 'completed') {
                $('#campaign-countdown').text('Done');
                $('#campaign-status-text').text('<?= _l('email_marketing_campaign_completed'); ?>');
                return;
            }

            $.post(admin_url + 'email_marketing/process_batch/' + campaignId).done(function(response) {
                response = JSON.parse(response);
                if (!response.success || !response.campaign) {
                    $('#campaign-status-text').text(response.message || '<?= _l('email_marketing_campaign_failed'); ?>');
                    return;
                }

                refreshUi(response.campaign);
                if (response.campaign.status === 'completed') {
                    $('#campaign-countdown').text('Done');
                    $('#campaign-status-text').text('<?= _l('email_marketing_campaign_completed'); ?>');
                    return;
                }

                tickCountdown(parseInt(response.campaign.cooling_seconds, 10));
            }).fail(function() {
                $('#campaign-status-text').text('<?= _l('email_marketing_campaign_failed'); ?>');
            });
        }

        runBatch();
    });
</script>
</body>
</html>
