<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-5 left-column">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php echo form_open('zoom_meetings/zoom_meetings/save_configuration', ['id' => 'configuration-form']); ?>
                        <div class="form-group">
                            <?php
                            echo form_hidden('id', $settings[0]['id'] ?? '');
                            echo render_input('zoom_email', 'Zoom Client ID', $settings[0]['zoom_email'] ?? '', 'text', ['required' => true]);
                            echo render_input('api_key', 'Zoom Redirect URI', $settings[0]['api_key'] ?? '', 'text', ['required' => true]);
                            echo render_input('api_secret', 'Zoom Client Secret', $settings[0]['api_secret'] ?? '', 'text', ['required' => true]);
                            ?>
                        </div>
                        <div class="btn-bottom-toolbar text-right">
                            <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
                        </div>
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>
