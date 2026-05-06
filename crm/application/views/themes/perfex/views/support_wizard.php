<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
.client-portal-hero {
    position: relative;
    z-index: 1;
    overflow: hidden;
    border-radius: 24px;
    padding: 34px 36px;
    margin-bottom: 24px;
    color: #fff;
    background: linear-gradient(135deg, rgba(8,32,71,.96), rgba(20,92,180,.88)), url('https://uscapitalprivatebank.com/assets/img/hero-bg.jpg') center/cover no-repeat;
    box-shadow: 0 24px 50px rgba(7, 32, 70, 0.22);
}
.client-portal-hero:before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at top right, rgba(255,255,255,.2), transparent 35%);
}
.client-portal-hero > * {
    position: relative;
    z-index: 1;
}
.client-portal-eyebrow {
    display: inline-block;
    letter-spacing: .18em;
    text-transform: uppercase;
    font-size: 12px;
    font-weight: 700;
    color: #bcd7ff;
    margin-bottom: 14px;
}
.client-portal-hero h1 {
    margin: 0 0 16px;
    font-size: 42px;
    line-height: 1.1;
    font-weight: 800;
    color: #fff;
}
.client-portal-hero p {
    max-width: 760px;
    font-size: 17px;
    line-height: 1.7;
    color: rgba(255,255,255,.88);
    margin-bottom: 22px;
}
.client-portal-steps {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}
.client-portal-step {
    min-width: 180px;
    padding: 14px 16px;
    border-radius: 14px;
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.14);
}
.client-portal-step strong {
    display: block;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #bcd7ff;
    margin-bottom: 6px;
}
.client-portal-step span {
    display: block;
    color: #fff;
    line-height: 1.5;
}
.client-wizard-panel {
    border-radius: 20px;
    overflow: visible;
    border: 1px solid #dbe6f2;
    box-shadow: 0 18px 36px rgba(15, 38, 73, 0.08);
    position: relative;
    z-index: 5;
}
.client-wizard-panel .panel-body {
    padding: 30px;
    overflow: visible;
}
.client-wizard-panel .panel-footer {
    padding: 20px 30px;
    background: linear-gradient(180deg, rgba(248,250,253,.6), #fff);
}
.client-wizard-section {
    margin-top: 24px;
    padding: 22px;
    border-radius: 16px;
    background: linear-gradient(180deg, #f8fbff, #f2f7fd);
    border: 1px solid #dbe7f5;
}
.client-wizard-section h4 {
    margin: 0 0 8px;
    font-size: 20px;
    font-weight: 700;
    color: #0f2d55;
}
.client-wizard-section p {
    margin: 0 0 12px;
    color: #5b6d86;
    line-height: 1.7;
}
.client-wizard-label-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 8px;
}
.client-wizard-mic-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border: 1px solid #c6d8ef;
    border-radius: 999px;
    background: #fff;
    color: #0f4c97;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
}
.client-wizard-mic-btn.is-listening {
    background: #0f4c97;
    border-color: #0f4c97;
    color: #fff;
}
.client-wizard-mic-btn[disabled] {
    opacity: .55;
    cursor: not-allowed;
}
.client-wizard-file-input {
    min-height: 52px;
    padding: 10px 12px;
}
.client-wizard-upload-note {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #48617f;
    font-size: 13px;
}
.client-wizard-panel .bootstrap-select,
.client-wizard-panel .bootstrap-select.open {
    position: relative;
    z-index: 20;
}
.client-wizard-panel .bootstrap-select.open .dropdown-menu {
    z-index: 3050;
}
.client-wizard-panel .dropdown-menu.open {
    z-index: 3050;
}
@media (max-width: 767px) {
    .client-portal-hero {
        padding: 26px 22px;
    }
    .client-portal-hero h1 {
        font-size: 34px;
    }
    .client-wizard-label-row {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
<div class="client-portal-hero">
    <span class="client-portal-eyebrow">Private Client Project Setup</span>
    <h1>Open the Right Project, Route the Right Team</h1>
    <p>Create a secure transaction workspace for this request. Once your project is set up, your files, support communication, and relationship manager updates will stay organized in one protected record.</p>
    <div class="client-portal-steps">
        <div class="client-portal-step">
            <strong>Step 1</strong>
            <span>Name the transaction or service request.</span>
        </div>
        <div class="client-portal-step">
            <strong>Step 2</strong>
            <span>Select the category and service type for routing.</span>
        </div>
        <div class="client-portal-step">
            <strong>Step 3</strong>
            <span>Upload any starting documents so the project is ready immediately.</span>
        </div>
    </div>
</div>
<h4 class="tw-mt-0 tw-mb-3 tw-font-semibold tw-text-lg">New Project</h4>
<?= form_open_multipart('clients/new_project', ['id' => 'client-new-project-form']); ?>
<div class="panel_s client-wizard-panel">
    <div class="panel-body">
        <?= validation_errors('<div class="alert alert-danger text-center">', '</div>'); ?>
        <?php $client_page_help_context = 'new_project'; ?>
        <?php get_template_part('client_directions'); ?>

        <div class="form-group">
            <label for="project_name">Project Name</label>
            <input type="text" class="form-control" name="project_name" id="project_name" value="<?= set_value('project_name'); ?>" placeholder="Example: SBLC Transaction">
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="project_category">Department / Category</label>
                    <select name="project_category" id="project_category" class="form-control selectpicker" data-none-selected-text="Select a category">
                        <option value=""></option>
                        <?php foreach ($category_options as $value => $label) { ?>
                        <option value="<?= e($value); ?>" <?= set_select('project_category', $value); ?>><?= e($label); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="support_service_type">Support / Service Type</label>
                    <select name="support_service_type" id="support_service_type" class="form-control selectpicker" data-none-selected-text="Select a support type">
                        <option value=""></option>
                        <?php foreach ($transaction_options as $value => $label) { ?>
                        <option value="<?= e($value); ?>" <?= set_select('support_service_type', $value); ?>><?= e($label); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="custom_service_type">Other Support / Service Type</label>
            <input type="text" class="form-control" name="custom_service_type" id="custom_service_type" value="<?= set_value('custom_service_type'); ?>" placeholder="Use this only if your support type is not listed above">
        </div>

        <div class="form-group">
            <div class="client-wizard-label-row">
                <label for="project_summary" class="tw-mb-0">Brief Summary</label>
                <button type="button" class="client-wizard-mic-btn" id="project-summary-dictate">
                    <i class="fa fa-microphone"></i>
                    Dictate Summary
                </button>
            </div>
            <textarea name="project_summary" id="project_summary" rows="6" class="form-control" placeholder="Describe the purpose and scope of this transaction or request."><?= set_value('project_summary'); ?></textarea>
        </div>

        <div class="client-wizard-section">
            <h4>Do you have any files for this project?</h4>
            <p>Upload them here and they will be attached directly to this new project after it is created, so your relationship team sees the right documents in the right workspace from the start.</p>
            <input type="file" class="form-control client-wizard-file-input" name="project_files[]" id="project_files" multiple extension="<?= str_replace('.', '', get_option('allowed_files')); ?>" filesize="<?= file_upload_max_size(); ?>">
            <div class="client-wizard-upload-note tw-mt-3">
                <i class="fa fa-paperclip"></i>
                <span>You can attach one or more files now. They will appear inside the project Files tab after creation.</span>
            </div>
        </div>
    </div>
    <div class="panel-footer text-right">
        <a href="<?= site_url('clients'); ?>" class="btn btn-default">Back to Dashboard</a>
        <button type="submit" class="btn btn-primary">Create Project</button>
    </div>
</div>
<?= form_close(); ?>
<script>
(function () {
    var dictateButton = document.getElementById('project-summary-dictate');
    var summaryField = document.getElementById('project_summary');
    if (!dictateButton || !summaryField) {
        return;
    }

    var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
        dictateButton.disabled = true;
        dictateButton.title = 'Voice dictation is not supported in this browser.';
        return;
    }

    var recognition = new SpeechRecognition();
    var listening = false;
    var seedText = '';

    recognition.lang = document.documentElement.lang || 'en-US';
    recognition.interimResults = true;
    recognition.continuous = true;

    dictateButton.addEventListener('click', function () {
        if (listening) {
            listening = false;
            recognition.stop();
            return;
        }

        seedText = summaryField.value.trim();
        listening = true;
        dictateButton.classList.add('is-listening');
        dictateButton.innerHTML = '<i class="fa fa-stop-circle"></i> Stop Dictation';
        recognition.start();
    });

    recognition.onresult = function (event) {
        var finalText = '';
        var interimText = '';

        for (var i = event.resultIndex; i < event.results.length; i++) {
            if (event.results[i].isFinal) {
                finalText += event.results[i][0].transcript + ' ';
            } else {
                interimText += event.results[i][0].transcript;
            }
        }

        var prefix = seedText ? seedText + "\n" : '';
        summaryField.value = prefix + (finalText + interimText).trim();
    };

    recognition.onend = function () {
        listening = false;
        dictateButton.classList.remove('is-listening');
        dictateButton.innerHTML = '<i class="fa fa-microphone"></i> Dictate Summary';
    };

    recognition.onerror = function () {
        listening = false;
        dictateButton.classList.remove('is-listening');
        dictateButton.innerHTML = '<i class="fa fa-microphone"></i> Dictate Summary';
    };
})();
</script>
