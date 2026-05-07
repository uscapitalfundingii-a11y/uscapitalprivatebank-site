<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="uscap-reminders-page-hero">
                            <div>
                                <span class="uscap-calendar-kicker">Staff Follow-Up Center</span>
                                <h4 class="no-margin">
                                    <?php echo e($title); ?>
                                </h4>
                                <p class="text-muted tw-mb-0">
                                    Create and track client, lead, ticket, task, invoice, and other CRM follow-ups from one place.
                                    <?php if (!is_admin()) { ?><br />
                                    <small><?php echo _l('reminders_view_none_admin'); ?></small>
                                    <?php } ?>
                                </p>
                            </div>
                            <button type="button" class="btn btn-primary uscap-add-reminder-btn" data-toggle="modal" data-target="#uscapAddReminderModal">
                                <i class="fa-regular fa-bell"></i>
                                Add Reminder
                            </button>
                        </div>
                        <hr class="hr-panel-separator" />
                           <?php render_datatable([
                            _l('reminder_related'),
                            _l('reminder_description'),
                            _l('reminder_date'),
                            _l('reminder_staff'),
                            _l('reminder_is_notified'),
                            ], 'reminders'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade uscap-add-reminder-modal" id="uscapAddReminderModal" tabindex="-1" role="dialog" aria-labelledby="uscapAddReminderModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?= form_open('', ['id' => 'uscap-add-reminder-form']); ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="uscapAddReminderModalLabel">
                    <i class="fa-regular fa-bell"></i>
                    Add Reminder
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <?= render_select('rel_type', [
                            ['id' => 'customer', 'name' => _l('customer')],
                            ['id' => 'lead', 'name' => _l('lead')],
                            ['id' => 'ticket', 'name' => _l('ticket')],
                            ['id' => 'task', 'name' => _l('task')],
                            ['id' => 'invoice', 'name' => _l('invoice')],
                            ['id' => 'estimate', 'name' => _l('estimate')],
                            ['id' => 'proposal', 'name' => _l('proposal')],
                            ['id' => 'expense', 'name' => _l('expense')],
                            ['id' => 'credit_note', 'name' => _l('credit_note')],
                        ], ['id', 'name'], 'reminder_related', 'customer', [], [], '', 'uscap-reminder-rel-type'); ?>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="uscap_reminder_rel_id" class="control-label"><?= _l('reminder_related'); ?></label>
                            <select name="rel_id" id="uscap_reminder_rel_id" class="ajax-search" data-live-search="true" data-width="100%" data-empty-title="<?= _l('search_ajax_empty'); ?>"></select>
                        </div>
                    </div>
                </div>
                <?= render_datetime_input('date', 'set_reminder_date', '', ['data-date-min-date' => _d(date('Y-m-d')), 'data-step' => 30]); ?>
                <?= render_select('staff', $members, ['staffid', ['firstname', 'lastname']], 'reminder_set_to', get_staff_user_id(), ['data-current-staff' => get_staff_user_id()]); ?>
                <?= render_textarea('description', 'reminder_description'); ?>
                <?php if (is_email_template_active('reminder-email-staff')) { ?>
                <div class="form-group">
                    <div class="checkbox checkbox-primary">
                        <input type="checkbox" name="notify_by_email" id="uscap_notify_by_email">
                        <label for="uscap_notify_by_email"><?= _l('reminder_notify_me_by_email'); ?></label>
                    </div>
                </div>
                <?php } ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= _l('close'); ?></button>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-regular fa-bell"></i>
                    Add Reminder
                </button>
            </div>
            <?= form_close(); ?>
        </div>
    </div>
</div>
<?php $this->load->view(
                                'admin/includes/modals/reminder',
                                [
    'id'             => '',
    'name'           => '',
    'members'        => $members,
    'reminder_title' => _l('edit', _l('reminder')), ]
                            ); ?>
<?php init_tail(); ?>
<script>
        $(function(){
            initDataTable('.table-reminders', admin_url + 'misc/reminders_table', undefined, undefined, undefined, [2,'asc']);
            var $addReminderForm = $('#uscap-add-reminder-form');
            var $relatedType = $addReminderForm.find('[name="rel_type"]');
            var $relatedRecord = $('#uscap_reminder_rel_id');

            function uscapInitReminderRelationSearch() {
                var selectedType = $relatedType.val() || 'customer';
                var $relatedRecordField = $relatedRecord.closest('.form-group');

                if ($relatedRecord.data('selectpicker')) {
                    $relatedRecord.selectpicker('destroy');
                }

                $relatedRecord.remove();
                $relatedRecord = $('<select/>', {
                    name: 'rel_id',
                    id: 'uscap_reminder_rel_id',
                    class: 'ajax-search',
                    'data-live-search': 'true',
                    'data-width': '100%',
                    'data-empty-title': <?= json_encode(_l('search_ajax_empty')); ?>
                });
                $relatedRecordField.append($relatedRecord);

                init_ajax_search(selectedType, '#uscap_reminder_rel_id');
                $relatedRecord.selectpicker('refresh');
            }

            uscapInitReminderRelationSearch();

            $relatedType.on('changed.bs.select change', function() {
                uscapInitReminderRelationSearch();
            });

            $addReminderForm.appFormValidator({
                rules: {
                    rel_type: 'required',
                    rel_id: 'required',
                    date: 'required',
                    staff: 'required',
                    description: 'required'
                },
                submitHandler: function(form) {
                    var $form = $(form);
                    var relType = $relatedType.val();
                    var relId = $relatedRecord.val();

                    $.post(admin_url + 'misc/add_reminder/' + relId + '/' + relType, $form.serialize()).done(function(response) {
                        response = JSON.parse(response);
                        if (response.message !== '') {
                            alert_float(response.alert_type, response.message);
                        }
                        $('#uscapAddReminderModal').modal('hide');
                        reload_reminders_tables();
                    });

                    return false;
                }
            });

            $('#uscapAddReminderModal').on('hidden.bs.modal', function() {
                $addReminderForm[0].reset();
                $relatedType.selectpicker('val', 'customer');
                $addReminderForm.find('[name="staff"]').selectpicker('val', $addReminderForm.find('[name="staff"]').attr('data-current-staff'));
                uscapInitReminderRelationSearch();
            });
        });
    </script>
</body>
</html>
