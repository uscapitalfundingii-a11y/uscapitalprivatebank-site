<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$mailboxEditorId = isset($mailbox_editor_id) ? $mailbox_editor_id : 'body';
$signaturePresets = isset($signature_presets) && is_array($signature_presets) ? $signature_presets : [];
$predefinedReplies = isset($predefined_replies) && is_array($predefined_replies) ? $predefined_replies : [];
$knowledgeBaseGroups = isset($knowledge_base_groups) && is_array($knowledge_base_groups) ? $knowledge_base_groups : [];
?>
<div class="row mailbox-editor-tools mtop10 mbot15">
    <div class="col-md-4">
        <select id="mailbox_insert_signature" data-width="100%" class="selectpicker" data-live-search="true" data-title="Insert signature preset">
            <?php foreach ($signaturePresets as $signaturePreset) { ?>
            <option value="<?php echo html_escape($signaturePreset['label']); ?>"><?php echo html_escape($signaturePreset['label']); ?></option>
            <?php } ?>
        </select>
    </div>
    <div class="col-md-4">
        <select id="mailbox_insert_predefined_reply" data-width="100%" class="selectpicker" data-live-search="true" data-title="<?php echo _l('ticket_single_insert_predefined_reply'); ?>">
            <?php foreach ($predefinedReplies as $predefinedReply) { ?>
            <option value="<?php echo (int) $predefinedReply['id']; ?>"><?php echo html_escape($predefinedReply['name']); ?></option>
            <?php } ?>
        </select>
    </div>
    <div class="col-md-4">
        <select id="mailbox_insert_knowledge_base" data-width="100%" class="selectpicker" data-live-search="true" data-title="<?php echo _l('ticket_single_insert_knowledge_base_link'); ?>">
            <?php foreach ($knowledgeBaseGroups as $knowledgeBaseGroup) { ?>
                <?php if (!empty($knowledgeBaseGroup['articles'])) { ?>
                    <optgroup label="<?php echo html_escape($knowledgeBaseGroup['name']); ?>">
                        <?php foreach ($knowledgeBaseGroup['articles'] as $knowledgeArticle) { ?>
                        <option value="<?php echo (int) $knowledgeArticle['articleid']; ?>"><?php echo html_escape($knowledgeArticle['subject']); ?></option>
                        <?php } ?>
                    </optgroup>
                <?php } ?>
            <?php } ?>
        </select>
    </div>
</div>
<div class="tw-flex tw-justify-end tw-gap-2 tw-mb-2">
    <button type="button" class="btn btn-default" id="mailbox-dictate">
        <i class="fa-solid fa-microphone tw-mr-1"></i><?php echo _l('email_marketing_dictate'); ?>
    </button>
    <button type="button" class="btn btn-warning" id="mailbox-revise-ai">
        <i class="fa-solid fa-wand-magic-sparkles tw-mr-1"></i><?php echo _l('email_marketing_revise_with_ai'); ?>
    </button>
    <button type="button" class="btn btn-info" id="mailbox-reply-ai">
        <i class="fa-solid fa-robot tw-mr-1"></i><?php echo _l('mailbox_reply_with_ai'); ?>
    </button>
</div>
<p class="text-muted mtop5">Use these quick insert tools to add saved signatures, predefined replies, or knowledge base content without leaving mailbox.</p>
<script>
window.mailboxSignaturePresets = <?php echo json_encode($signaturePresets); ?>;
window.mailboxAiContext = <?php echo json_encode(isset($mailbox_ai_context) ? (string) $mailbox_ai_context : ''); ?>;
(function() {
    "use strict";

    function mailboxGetEditor() {
        if (typeof tinyMCE === 'undefined') {
            return null;
        }

        return tinyMCE.get('<?php echo $mailboxEditorId; ?>') || tinyMCE.activeEditor;
    }

    function mailboxInsertIntoEditor(content) {
        var editor = mailboxGetEditor();
        if (editor) {
            editor.execCommand('mceInsertContent', false, content);
            return;
        }

        var textarea = document.getElementById('<?php echo $mailboxEditorId; ?>');
        if (textarea) {
            textarea.value = (textarea.value || '') + content;
        }
    }

    function mailboxSetEditorContent(content) {
        var editor = mailboxGetEditor();
        if (editor) {
            editor.setContent(content);
            return;
        }

        var textarea = document.getElementById('<?php echo $mailboxEditorId; ?>');
        if (textarea) {
            textarea.value = content;
        }
    }

    function mailboxGetEditorContent() {
        var editor = mailboxGetEditor();
        if (editor) {
            return editor.getContent() || '';
        }

        var textarea = document.getElementById('<?php echo $mailboxEditorId; ?>');
        return textarea ? (textarea.value || '') : '';
    }

    $(function() {
        $('#mailbox_insert_signature').off('changed.bs.select').on('changed.bs.select', function() {
            var selectedLabel = $(this).val();
            if (!selectedLabel) {
                return;
            }

            var selectedPreset = null;
            $.each(window.mailboxSignaturePresets || [], function(index, preset) {
                if (preset.label === selectedLabel) {
                    selectedPreset = preset;
                    return false;
                }
            });

            if (selectedPreset && selectedPreset.content) {
                mailboxInsertIntoEditor('<br><br>' + selectedPreset.content);
            }

            $(this).selectpicker('val', '');
        });

        $('#mailbox_insert_predefined_reply').off('changed.bs.select').on('changed.bs.select', function() {
            var replyId = $(this).val();
            if (!replyId) {
                return;
            }

            $.get(admin_url + 'mailbox/predefined_reply_ajax/' + replyId).done(function(response) {
                var reply = typeof response === 'string' ? JSON.parse(response) : response;
                if (reply && reply.message) {
                    mailboxInsertIntoEditor('<br><br>' + reply.message);
                }
            }).always(function() {
                $('#mailbox_insert_predefined_reply').selectpicker('val', '');
            });
        });

        $('#mailbox_insert_knowledge_base').off('changed.bs.select').on('changed.bs.select', function() {
            var articleId = $(this).val();
            if (!articleId) {
                return;
            }

            $.get(admin_url + 'mailbox/knowledge_base_article_ajax/' + articleId).done(function(response) {
                var article = typeof response === 'string' ? JSON.parse(response) : response;
                if (article && article.success) {
                    var content = '<br><br><strong>' + article.subject + '</strong><br>' + article.description;
                    if (article.admin_link) {
                        content += '<br><a href="' + article.admin_link + '" target="_blank">' + article.admin_link + '</a>';
                    }
                    mailboxInsertIntoEditor(content);
                }
            }).always(function() {
                $('#mailbox_insert_knowledge_base').selectpicker('val', '');
            });
        });

        $('#mailbox-revise-ai').off('click').on('click', function() {
            var html = mailboxGetEditorContent();
            $.post(admin_url + 'ai/text_enhancement/formal', {
                text: html
            }).done(function(response) {
                response = typeof response === 'string' ? JSON.parse(response) : response;
                if (!response.success) {
                    alert_float('warning', '<?php echo _l('mailbox_ai_reply_failed'); ?>');
                    return;
                }

                mailboxSetEditorContent(response.message || '');
            }).fail(function(error) {
                var message = '<?php echo _l('mailbox_ai_reply_failed'); ?>';
                if (error.responseText) {
                    try {
                        var parsed = JSON.parse(error.responseText);
                        message = parsed.error || message;
                    } catch (e) {}
                }
                alert_float('warning', message);
            });
        });

        $('#mailbox-reply-ai').off('click').on('click', function() {
            $.post(admin_url + 'ai/email_reply', {
                draft_html: mailboxGetEditorContent(),
                context: window.mailboxAiContext || ''
            }).done(function(response) {
                response = typeof response === 'string' ? JSON.parse(response) : response;
                if (!response.success) {
                    alert_float('warning', '<?php echo _l('mailbox_ai_reply_failed'); ?>');
                    return;
                }

                mailboxSetEditorContent(response.message || '');
            }).fail(function(error) {
                var message = '<?php echo _l('mailbox_ai_reply_failed'); ?>';
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

        $('#mailbox-dictate').off('click').on('click', async function() {
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
                    button.removeClass('btn-danger').addClass('btn-default').html('<i class="fa-solid fa-microphone tw-mr-1"></i><?php echo _l('email_marketing_dictate'); ?>');

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
                        response = typeof response === 'string' ? JSON.parse(response) : response;
                        if (!response.success) {
                            alert_float('warning', response.error || '<?php echo _l('email_marketing_transcription_failed'); ?>');
                            return;
                        }

                        var existing = mailboxGetEditorContent();
                        var combined = existing + '<p>' + $('<div>').text(response.transcript || '').html() + '</p>';
                        mailboxSetEditorContent(combined);
                    }).fail(function(error) {
                        var message = '<?php echo _l('email_marketing_transcription_failed'); ?>';
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
                button.removeClass('btn-default').addClass('btn-danger').html('<i class="fa-solid fa-stop tw-mr-1"></i><?php echo _l('email_marketing_stop_dictation'); ?>');
            } catch (error) {
                alert_float('warning', '<?php echo _l('email_marketing_microphone_denied'); ?>');
            }
        });
    });
})();
</script>
