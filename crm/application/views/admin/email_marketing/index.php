<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="tw-font-bold tw-text-xl tw-mt-0"><?= _l('email_marketing'); ?></h4>
                        <p class="text-muted">
                            <?= _l('email_marketing_intro', $eligible_contacts_count); ?>
                        </p>

                        <?= form_open(admin_url('email_marketing/launch')); ?>
                        <?php
                        $defaultBatchSize = 25;
                        $defaultCoolingSeconds = 10;
                        $emailsPerMinute = max(1, (int) round(($defaultBatchSize * 60) / max(1, $defaultCoolingSeconds)));
                        ?>

                        <div class="row mtop15">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="tw-border tw-rounded-lg tw-p-4 tw-bg-white tw-border-primary-500">
                                            <div class="tw-flex tw-items-center tw-gap-3">
                                                <i class="fa-regular fa-envelope tw-text-2xl text-primary"></i>
                                                <div>
                                                    <div class="tw-font-semibold"><?= _l('email_marketing_send_via_email'); ?></div>
                                                    <div class="text-muted"><?= _l('email_marketing_send_via_email_help'); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="tw-border tw-rounded-lg tw-p-4 tw-bg-neutral-50">
                                            <div class="tw-flex tw-items-center tw-gap-3">
                                                <i class="fa-solid fa-mobile-screen tw-text-2xl text-muted"></i>
                                                <div>
                                                    <div class="tw-font-semibold"><?= _l('email_marketing_sms_coming_soon'); ?></div>
                                                    <div class="text-muted"><?= _l('email_marketing_sms_coming_soon_help'); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="tw-border tw-rounded-lg tw-p-4 tw-bg-neutral-50">
                                            <div class="tw-flex tw-items-center tw-gap-3">
                                                <i class="fa-regular fa-bell tw-text-2xl text-muted"></i>
                                                <div>
                                                    <div class="tw-font-semibold"><?= _l('email_marketing_push_coming_soon'); ?></div>
                                                    <div class="text-muted"><?= _l('email_marketing_push_coming_soon_help'); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mtop25">
                            <div class="col-md-12">
                                <?= render_select('saved_template_id', $templates, ['id', 'name'], 'email_marketing_saved_messages', '', ['data-none-selected-text' => _l('email_marketing_choose_saved_message')]); ?>
                                <?= form_hidden('template_name', ''); ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <?= render_input('subject', 'email_marketing_subject'); ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="tw-flex tw-justify-end tw-gap-2 tw-mb-2">
                                    <button type="button" class="btn btn-default" id="email-marketing-dictate">
                                        <i class="fa-solid fa-microphone tw-mr-1"></i><?= _l('email_marketing_dictate'); ?>
                                    </button>
                                    <button type="button" class="btn btn-warning" id="email-marketing-revise">
                                        <i class="fa-solid fa-wand-magic-sparkles tw-mr-1"></i><?= _l('email_marketing_revise_with_ai'); ?>
                                    </button>
                                </div>
                                <?= render_textarea('message', 'email_marketing_message', '', [], [], '', 'tinymce'); ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <?= render_input('start_from_contact_id', 'email_marketing_start_from', '1', 'number', ['min' => 1]); ?>
                            </div>
                            <div class="col-md-4">
                                <?= render_input('batch_size', 'email_marketing_per_batch', '25', 'number', ['min' => 1, 'max' => 500]); ?>
                            </div>
                            <div class="col-md-4">
                                <?= render_input('cooling_seconds', 'email_marketing_cooling_period', '10', 'number', ['min' => 1, 'max' => 3600]); ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <p class="text-muted" id="email-marketing-rate-hint">
                                    <?= _l('email_marketing_rate_hint', $emailsPerMinute); ?>
                                </p>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <?= _l('email_marketing_launch_campaign'); ?>
                                </button>
                            </div>
                        </div>

                        <?= form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        function getEditor() {
            return tinymce.get('message');
        }

        $('select[name="saved_template_id"]').on('change', function() {
            var templateId = $(this).val();
            if (!templateId) {
                return;
            }

            requestGet('email_marketing/template/' + templateId).done(function(response) {
                response = JSON.parse(response);
                if (!response.success || !response.template) {
                    return;
                }

                $('input[name="template_name"]').val(response.template.name);
                $('input[name="subject"]').val(response.template.subject);

                var editor = getEditor();
                if (editor) {
                    editor.setContent(response.template.message || '');
                } else {
                    $('textarea[name="message"]').val(response.template.message || '');
                }
            });
        });

        $('#email-marketing-revise').on('click', function() {
            var editor = getEditor();
            var html = editor ? editor.getContent() : $('textarea[name="message"]').val();
            $.post(admin_url + 'ai/text_enhancement/formal', {
                text: html
            }).done(function(response) {
                response = JSON.parse(response);
                if (!response.success) {
                    alert_float('warning', '<?= _l('email_marketing_ai_revision_failed'); ?>');
                    return;
                }

                if (editor) {
                    editor.setContent(response.message || '');
                } else {
                    $('textarea[name="message"]').val(response.message || '');
                }
            }).fail(function(error) {
                var message = '<?= _l('email_marketing_ai_revision_failed'); ?>';
                if (error.responseText) {
                    try {
                        var parsed = JSON.parse(error.responseText);
                        message = parsed.error || message;
                    } catch (e) {}
                }
                alert_float('warning', message);
            });
        });

        var mediaRecorder = null;
        var chunks = [];
        var isRecording = false;

        $('#email-marketing-dictate').on('click', async function() {
            var button = $(this);
            if (isRecording) {
                mediaRecorder.stop();
                return;
            }

            try {
                var stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                chunks = [];
                mediaRecorder = new MediaRecorder(stream);
                mediaRecorder.ondataavailable = function(e) {
                    if (e.data.size > 0) {
                        chunks.push(e.data);
                    }
                };
                mediaRecorder.onstop = function() {
                    isRecording = false;
                    button.removeClass('btn-danger').addClass('btn-default').html('<i class="fa-solid fa-microphone tw-mr-1"></i><?= _l('email_marketing_dictate'); ?>');

                    var blob = new Blob(chunks, { type: 'audio/webm' });
                    var formData = new FormData();
                    formData.append('audio', blob, 'dictation.webm');

                    $.ajax({
                        url: admin_url + 'ai/transcribe_audio',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false
                    }).done(function(response) {
                        response = JSON.parse(response);
                        if (!response.success) {
                            alert_float('warning', response.error || '<?= _l('email_marketing_transcription_failed'); ?>');
                            return;
                        }

                        var editor = getEditor();
                        var existing = editor ? editor.getContent() : $('textarea[name="message"]').val();
                        var combined = existing + '<p>' + $('<div>').text(response.transcript || '').html() + '</p>';
                        if (editor) {
                            editor.setContent(combined);
                        } else {
                            $('textarea[name="message"]').val(combined);
                        }
                    }).fail(function(error) {
                        var message = '<?= _l('email_marketing_transcription_failed'); ?>';
                        if (error.responseText) {
                            try {
                                var parsed = JSON.parse(error.responseText);
                                message = parsed.error || message;
                            } catch (e) {}
                        }
                        alert_float('warning', message);
                    }).always(function() {
                        stream.getTracks().forEach(function(track) {
                            track.stop();
                        });
                    });
                };

                mediaRecorder.start();
                isRecording = true;
                button.removeClass('btn-default').addClass('btn-danger').html('<i class="fa-solid fa-stop tw-mr-1"></i><?= _l('email_marketing_stop_dictation'); ?>');
            } catch (error) {
                alert_float('warning', '<?= _l('email_marketing_microphone_denied'); ?>');
            }
        });

        function updateRateHint() {
            var batchSize = parseInt($('input[name="batch_size"]').val(), 10) || 1;
            var coolingSeconds = parseInt($('input[name="cooling_seconds"]').val(), 10) || 1;
            var emailsPerMinute = Math.max(1, Math.round((batchSize * 60) / Math.max(1, coolingSeconds)));
            $('#email-marketing-rate-hint').text('<?= str_replace('%s', '__VALUE__', _l('email_marketing_rate_hint', '%s')); ?>'.replace('__VALUE__', emailsPerMinute));
        }

        $('input[name="batch_size"], input[name="cooling_seconds"]').on('input change', updateRateHint);
        updateRateHint();
    });
</script>
</body>
</html>
