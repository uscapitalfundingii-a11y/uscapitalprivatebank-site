<div class="modal fade" id="_clientsAnnouncementModal" tabindex="-1" role="dialog">
    <form method="POST" id="clients_form">
        <div class="modal-dialog" role="any">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?= isset($title) ? $title : ''  ?></h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" class="ays-ignore" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                    <div class="form-group">

                        <select data-none-selected-text="<?= _l('chat_non_selected_client_text'); ?>" data-actions-box="true" data-live-search="true" id="clients" name="clients[]" multiple class="form-control uscap-announcement-select">
                            <?php
                            if (is_array($clients) && !empty($clients)) {
                                foreach ($clients as $client) : ?>
                                    <option data-subtext="<?= $client['title'] ?>" data-icon="fa fa-user-o" value="<?= $client['contact_id'] ?>"><?= $client['firstname'] . ' ' . $client['lastname']; ?>
                                    </option>
                            <?php endforeach;
                            } ?>
                        </select>
                        <label for="message" class="mtop20"><?= _l('chat_enter_your_message'); ?></label>
                        <textarea style="max-width: 571px;" class="form-control" name="message" rows="6"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
                    <button type="submit" class="btn btn-info submit"><?= _l('chat_send_button'); ?></button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </form>
</div><!-- /.modal -->

<style>
    .dropdown.bootstrap-select.show-tick.form-control.bs3 {
        box-shadow: none;
    }

    #_clientsAnnouncementModal .bootstrap-select.uscap-announcement-select,
    #_clientsAnnouncementModal .bootstrap-select {
        width: 100% !important;
    }

    #_clientsAnnouncementModal .bootstrap-select .dropdown-menu {
        max-height: min(62vh, 560px) !important;
        overflow: hidden !important;
        z-index: 1065;
    }

    #_clientsAnnouncementModal .bootstrap-select .bs-actionsbox {
        display: block !important;
        position: sticky;
        top: 0;
        z-index: 2;
        padding: 10px;
        border-bottom: 1px solid #dbe5f1;
        background: #fff;
    }

    #_clientsAnnouncementModal .bootstrap-select .bs-actionsbox .btn-group {
        display: flex;
        gap: 8px;
        width: 100%;
    }

    #_clientsAnnouncementModal .bootstrap-select .bs-actionsbox .btn {
        flex: 1;
        white-space: nowrap;
        font-weight: 700;
    }

    #_clientsAnnouncementModal .bootstrap-select .inner {
        max-height: calc(min(62vh, 560px) - 110px) !important;
        overflow-y: auto !important;
    }
</style>
<script>
    (function($) {
        "use strict";

        appValidateForm($("#clients_form"), {
            message: "required",
            'clients[]': {
                required: true,
                minlength: 1
            }
        });


        // Select picker settings
        $('#clients').selectpicker({
            actionsBox: true,
            selectAllText: 'Select all',
            deselectAllText: 'Deselect all',
            enableFiltering: true,
            enableCaseInsensitiveFiltering: true,
            selectedTextFormat: 'count > 2',
            width: '100%',
            buttonWidth: '100%',
            size: 12
        });
        $('#clients').selectpicker('refresh');

        // On submit event
        $('#clients_form').on('submit', function(e) {
            e.preventDefault();
            var btn = $('#clients_form .submit');

            var formData = $(this).serialize();

            $.ajax({
                url: prchatSettings.clientsAnnouncementPost,
                method: "POST",
                data: formData,
                beforeSend: function() {
                    $(btn).attr('disabled', true);
                },
                success: function(response) {
                    if (response === 'true') {

                        $('#clients option:selected').each(function() {
                            $(this).prop('selected', false);
                        });

                        $('#clients').selectpicker('refresh');

                        $('#_clientsAnnouncementModal').modal('hide');

                        $('#_clientsAnnouncementModal').on('hidden.bs.modal', function() {
                            $('#frame ul.chat_nav .crm_clients a').click();
                            alert_float('success', "<?= _l('chat_client_announcement_success'); ?>")
                        });
                        $(btn).attr('disabled', true);
                    }
                }
            });
        });
    })(jQuery);
</script>
