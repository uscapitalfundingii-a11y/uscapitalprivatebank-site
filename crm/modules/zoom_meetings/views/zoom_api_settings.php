<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-5 left-column">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php echo form_open('zoom_meetings/zoom_meetings/api_meeting_submit', array('id' => 'meeting-submit-form')); ?>
                        <?php
                        // Include the ID as a hidden input field
                        echo form_hidden('id', $settings[0]['id']);
                        ?>

                        <?php
                        // Render input fields for Zoom API settings
                        echo render_input('zoom_email', 'Zoom Email', $settings[0]['zoom_email'], 'text', ['required' => 'true']);
                        echo render_input('api_key', 'Zoom API Key', $settings[0]['api_key'], 'text', ['required' => 'true']);
                        echo render_input('api_secret', 'Zoom API Secret', $settings[0]['api_secret'], 'text', ['required' => 'true']);
                        ?>

                        <div class="row">
                            <div class="col-md-12">
                                <?php
                                // Initialize connection status
                                $uri = $settings[0]['api_key'];
                                $client_id = $settings[0]['zoom_email'];
                                $access_token = $settings[0]['access_token'];
                                $isConnected = false;

                                if ($access_token) {
                                    $ch = curl_init();
                                    curl_setopt($ch, CURLOPT_URL, 'https://api.zoom.us/v2/users/me');
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                        'Authorization: Bearer ' . $access_token,
                                        'Content-Type: application/json',
                                    ]);

                                    $response = curl_exec($ch);
                                    $error = curl_error($ch);
                                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                    curl_close($ch);

                                    if ($response && $httpCode === 200) {
                                        $responseData = json_decode($response, true);
                                        if (isset($responseData['id']) && !empty($responseData['id'])) {
                                            $isConnected = true;
                                        } else {
                                            log_message('error', 'Zoom API response error: ' . json_encode($responseData));
                                        }
                                    } else {
                                        log_message('error', "Zoom API cURL Error: $error, HTTP Code: $httpCode");
                                    }
                                }
                                ?>

                                <div>
                                    <strong>App Status:</strong>
                                    <?php if ($isConnected) { ?>
                                        <span style="color: green;"><b>Connected</b></span>
                                    <?php } else { ?>
                                        <span style="color: red;"><b>Not Connected</b></span>
                                        <?php
                                        $authorizationUrl = "https://zoom.us/oauth/authorize?response_type=code&client_id={$client_id}&redirect_uri={$uri}";
                                        echo "<a href='{$authorizationUrl}' class='btn btn-primary mb-3'>Authorize the App</a>";
                                        ?>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                        <?php if ($this->session->flashdata('error')) { ?>
    <div class="alert alert-danger">
        <?php echo $this->session->flashdata('error'); ?>
    </div>
<?php } ?>

                        <div class="btn-bottom-toolbar text-right">
                            <button type="submit" class="btn btn-info"><?php echo _l('Submit'); ?></button>
                        </div>
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

</body>
</html>
