<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body tw-bg-gradient-to-l tw-from-transparent tw-to-neutral-50">
                        <?= $this->import->importGuidelinesInfoHtml(); ?>
                    </div>
                </div>
                <div class="tw-flex tw-justify-between tw-items-center tw-mb-3">
                    <h4 class="tw-my-0 tw-font-bold tw-text-lg tw-text-neutral-700 tw-flex tw-items-center tw-gap-x-2">
                        <?= _l('import_customers'); ?>
                    </h4>
                    <?= $this->import->downloadSampleFormHtml(); ?>
                </div>
                <div class="panel_s">
                    <div class="panel-body">
                        <?= $this->import->maxInputVarsWarningHtml(); ?>
                        <?php if (! $this->import->isSimulation()) { ?>
                        <?= $this->import->createSampleTableHtml(); ?>
                        <?php } else { ?>
                        <div class="tw-mb-6">
                            <?= $this->import->simulationDataInfo(); ?>
                        </div>
                        <?= $this->import->createSampleTableHtml(true); ?>
                        <?php } ?>
                        <div class="row">
                            <div class="col-md-4 mtop15">
                                <?= form_open_multipart($this->uri->uri_string(), ['id' => 'import_form']); ?>
                                <?= form_hidden('clients_import', 'true'); ?>
                                <?= render_input('file_csv', 'choose_csv_file', '', 'file'); ?>
                                <div class="alert alert-info">
                                    <strong><?= _l('utility_import_csv'); ?></strong><br>
                                    Upload your CSV, then use the mapping tool below to match incoming column headers to CRM customer fields before import.
                                </div>
                                <div class="panel panel-default">
                                    <div class="panel-body">
                                        <h5 class="tw-font-semibold tw-mt-0"><?= _l('csv_field_mapping'); ?></h5>
                                        <p class="text-muted">
                                            Preview the first row headers from your CSV and map each one to the correct CRM field. Leave a field as "Skip this column" if you do not want to import it.
                                        </p>
                                        <div id="csv-mapping-empty-state" class="text-muted">
                                            Choose a CSV file to generate the field-mapping list.
                                        </div>
                                        <div id="csv-mapping-container" class="hide">
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>CSV Column</th>
                                                            <th>CRM Field</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="csv-mapping-body"></tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                if (is_admin() || get_option('staff_members_create_inline_customer_groups') == '1') {
                    echo render_select_with_input_group('groups_in[]', $groups, ['id', 'name'], 'customer_groups', ($this->input->post('groups_in') ? $this->input->post('groups_in') : []), '<div class="input-group-btn"><a href="#" class="btn btn-default" data-toggle="modal" data-target="#customer_group_modal"><i class="fa fa-plus"></i></a></div>', ['multiple' => true, 'data-actions-box' => true], [], '', '', false);
                } else {
                    echo render_select('groups_in[]', $groups, ['id', 'name'], 'customer_groups', ($this->input->post('groups_in') ? $this->input->post('groups_in') : []), ['multiple' => true, 'data-actions-box' => true], [], '', '', false);
                }
echo render_input('default_pass_all', 'default_pass_clients_import', $this->input->post('default_pass_all')); ?>
                                <div class="checkbox checkbox-primary mtop10">
                                    <input type="checkbox" id="merge_existing" name="merge_existing" value="1" <?= $this->input->post('merge_existing') === null || $this->input->post('merge_existing') === '1' ? 'checked' : ''; ?>>
                                    <label for="merge_existing">Merge existing customers by email, or by phone plus matching company/name</label>
                                </div>
                                <div class="form-group">
                                    <button type="button"
                                        class="btn btn-primary import btn-import-submit"><?= _l('import'); ?></button>
                                    <button type="button"
                                        class="btn btn-default simulate btn-import-submit"><?= _l('simulate_import'); ?></button>
                                </div>
                                <?= form_close(); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->load->view('admin/clients/client_group'); ?>
<?php init_tail(); ?>
<script
    src="<?= base_url('assets/plugins/jquery-validation/additional-methods.min.js'); ?>">
</script>
<script>
    $(function() {
        appValidateForm($('#import_form'), {
            file_csv: {
                required: true,
                extension: "csv"
            },
            source: 'required',
            status: 'required'
        });

        var fieldOptions = [
            {
                value: '',
                label: 'Skip this column'
            },
            <?php foreach ($importable_database_fields as $field) { ?>
            {
                value: '<?= html_escape($field); ?>',
                label: '<?= html_escape($this->import->formatFieldNameForHeading($field)); ?>'
            },
            <?php } ?>
            <?php foreach ($importable_custom_fields as $field) { ?>
            {
                value: 'custom_field:<?= (int) $field['id']; ?>',
                label: 'Custom Field: <?= html_escape($field['name']); ?>'
            },
            <?php } ?>
        ];

        var autoMatchAliases = {
            firstname: ['firstname', 'first_name', 'given_name', 'name', 'full_name'],
            lastname: ['lastname', 'last_name', 'surname', 'family_name'],
            email: ['email', 'email_address', 'primary_email'],
            company: ['company', 'company_name', 'business_name', 'organization', 'organisation'],
            contact_phonenumber: ['phone', 'phone_number', 'mobile', 'mobile_number', 'contact_phone'],
            phonenumber: ['company_phone', 'business_phone'],
            country: ['country', 'country_name'],
            address: ['address', 'street', 'street_address', 'address_line_1', 'address_line1'],
            zip: ['zip', 'zip_code', 'postal_code', 'postcode', 'postal']
        };

        function normalizeHeader(value) {
            return String(value || '')
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_+|_+$/g, '');
        }

        function parseCsvLine(line) {
            var result = [];
            var current = '';
            var inQuotes = false;

            for (var i = 0; i < line.length; i++) {
                var char = line[i];
                var next = line[i + 1];

                if (char === '"' && inQuotes && next === '"') {
                    current += '"';
                    i++;
                    continue;
                }

                if (char === '"') {
                    inQuotes = !inQuotes;
                    continue;
                }

                if (char === ',' && !inQuotes) {
                    result.push(current);
                    current = '';
                    continue;
                }

                current += char;
            }

            result.push(current);
            return result;
        }

        function guessField(header) {
            var normalized = normalizeHeader(header);

            $.each(fieldOptions, function(_, option) {
                if (option.value === normalized) {
                    normalized = option.value;
                    return false;
                }
            });

            var matched = '';
            $.each(autoMatchAliases, function(target, aliases) {
                if ($.inArray(normalized, aliases) !== -1) {
                    matched = target;
                    return false;
                }
            });

            return matched;
        }

        function renderMapping(headers) {
            var $body = $('#csv-mapping-body');
            $body.empty();

            $.each(headers, function(index, header) {
                var cleanHeader = $.trim(header);
                if (!cleanHeader) {
                    return;
                }

                var guessed = guessField(cleanHeader);
                var $select = $('<select class="form-control"></select>')
                    .attr('name', 'field_mappings[' + cleanHeader.replace(/"/g, '&quot;') + ']');

                $.each(fieldOptions, function(_, option) {
                    var $option = $('<option></option>')
                        .attr('value', option.value)
                        .text(option.label);

                    if (option.value === guessed) {
                        $option.prop('selected', true);
                    }

                    $select.append($option);
                });

                var $row = $('<tr></tr>');
                $row.append($('<td></td>').text(cleanHeader));
                $row.append($('<td></td>').append($select));
                $body.append($row);
            });

            $('#csv-mapping-empty-state').addClass('hide');
            $('#csv-mapping-container').removeClass('hide');
        }

        $('input[name="file_csv"]').on('change', function(event) {
            var file = event.target.files && event.target.files[0];
            if (!file) {
                return;
            }

            var reader = new FileReader();
            reader.onload = function(loadEvent) {
                var text = loadEvent.target.result || '';
                var firstLine = String(text).split(/\r?\n/)[0] || '';
                var headers = parseCsvLine(firstLine);
                renderMapping(headers);
            };
            reader.readAsText(file);
        });
    });
</script>
</body>

</html>
