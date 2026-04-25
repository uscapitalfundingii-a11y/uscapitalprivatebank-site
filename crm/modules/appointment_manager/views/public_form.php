<?php

defined('BASEPATH') or exit('No direct script access allowed'); ?>

<link rel="stylesheet" type="text/css" id="full-calendar-css"
    href="<?php echo site_url('assets/plugins/fullcalendar/lib/main.min.css?v=3.1.6'); ?>">
    <link rel="stylesheet" type="text/css" id="appmgr-new-publicfrm-css"
    href="<?php echo site_url('modules/appointment_manager/assets/css/public_form_new.css?v=20260425b'); ?>">
<script type="text/javascript" id="full-calendar-js"
    src="<?php echo site_url('assets/plugins/fullcalendar/lib/main.min.js?v=3.1.6'); ?>"></script>

<section class="wrap-plc-frm">
    <div class="data-wrp-pl">
        <div class="appmgr-public-banner">
            <img src="<?php echo module_dir_url('appointment_manager', 'assets/img/schedule-an-appointment.png'); ?>" alt="Schedule an appointment">
        </div>
        <div class="appmgr-public-intro-bar">
            <div class="appmgr-public-intro-copy">
                <span class="appmgr-public-eyebrow">Private Appointment Booking</span>
                <h2>Book the right representative with a clear, guided workflow</h2>
                <p>Select the location, representative, date, and time first. Then complete the request with the appropriate service and meeting notes so the appointment can be confirmed cleanly.</p>
            </div>
            <div class="appmgr-public-intro-cards">
                <div class="appmgr-public-intro-card">
                    <strong>1. Select</strong>
                    <span>Location and staff</span>
                </div>
                <div class="appmgr-public-intro-card">
                    <strong>2. Schedule</strong>
                    <span>Date and time</span>
                </div>
                <div class="appmgr-public-intro-card">
                    <strong>3. Confirm</strong>
                    <span>Service and notes</span>
                </div>
            </div>
        </div>
        <!--Preloader-->
        <div class="preloader-cl">
            <span class="loader"></span>
        </div>
        <!--Close Preloader-->

        <div class="row flx-cl-ar">
            <div class="col-md-5">
                <div class="dt-fm-left mk-cntr-area">

                    <div class="fl-lds">
                        <div>
                            <div class="nm-tm-cl">
                                <span class="iconshow"><i class="fa-solid fa-calendar-days"></i></span>
                                <div class="appmgr-side-copy">
                                    <span class="appmgr-side-kicker">Booking Overview</span>
                                    <h3>Appointment details</h3>
                                    <p>This panel tracks the meeting setup as you select the location, representative, date, time, and timezone. It is the working summary for the appointment request.</p>
                                </div>
                                <div class="name-loction">
                                    <div class="location-container">
                                        <p class="nm-form-cl location-display"><i class="fs-in fa-solid fa-location-dot"></i> Lucknow <i class="fa-solid fa-pen edit-btn-cl location-edit"></i></p>
                                        <div class="location-selct-cl" style="display: none;">
                                            <select class="selectpicker location-select" name="location" data-live-search="true">
                                                <?php
                                                if (isset($locations) && !empty($locations)) { ?>
                                                    <option value=""></option>
                                                    <?php foreach ($locations as $location) {
                                                    ?>
                                                        <option value="<?php echo $location['id']; ?>" data-tokens="<?php echo $location['name']; ?>" data-Tfrom="<?php echo $location['operation_start_time']; ?>"
                                                            data-Tto="<?php echo $location['operation_end_time']; ?>"><?php echo $location['name']; ?></option>
                                                <?php }
                                                } ?>

                                            </select>
                                            <span class="error-lable"> Select Location</span>
                                        </div>

                                    </div>

                                    <div class="name-container">
                                        <p class="nm-form-cl name-display"><i class="fs-in fa-regular fa-user"></i> Select staff members <i class="fa-solid fa-pen edit-btn-cl name-edit"></i></p>
                                        <div class="name-select-cl" style="display: none;">
                                            <select class="selectpicker name-select" data-live-search="true" multiple data-selected-text-format="count > 2" title="Choose staff members">
                                                <option value=""></option>
                                                <?php foreach ($public_appointies as $appointee) { ?>
                                                    <option value="<?php echo $appointee['id']; ?>"><?php echo $appointee['name']; ?></option>
                                                <?php } ?>
                                            </select>
                                            <span class="error-lable"> Select Staff</span>
                                        </div>

                                    </div>

                                    <!-- <h3>0 Minute Meeting</h3> -->
                                </div>
                            </div>
                            <ul class="lst-lft-rg-cl">

                                <li>
                                    <b><span><i class="fa-regular fa-clock"></i></span></b>
                                    <p>0 min</p>
                                </li>

                                <li><b><span><i class="fa-solid fa-calendar-days"></i></span></b>
                                    <p id="selected-time-display">No time selected</p>
                                </li>
                                <li class="time-pckr"><b><span><i class="fa-solid fa-earth-africa"></i></span></b>
                                    <select id="timezone-select" name="timezone" class="selectpicker form-control" data-live-search="true" title="Choose a timezone...">
                                        <?php foreach (get_timezones_list() as $key => $timezones) { ?>
                                            <optgroup label="<?php echo e($key); ?>">
                                                <?php foreach ($timezones as $timezone) { ?>
                                                    <option value="<?php echo e($timezone); ?>" <?php if (get_option('default_timezone') == $timezone) {
                                                                                                    echo 'selected';
                                                                                                } ?>><?php echo e($timezone); ?></option>
                                                <?php } ?>
                                            </optgroup>
                                        <?php } ?>
                                    </select>
                                </li>
                            </ul>
                            <div class="appmgr-staff-switcher-wrap">
                                <h4>Selected staff calendars</h4>
                                <p>Click a staff button to switch the calendar and review that representative’s availability.</p>
                                <div class="appmgr-staff-switcher" id="appmgrStaffSwitcher"></div>
                            </div>
                            <div class="appmgr-side-note">
                                <h4>How to use this page</h4>
                                <p>Choose the representative and meeting details first. After that, the request form opens so you can attach the service type, contact details, and meeting purpose before submission.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-7 right-clnder-data" style="border-left: 1px solid #eee; background-color: white;">

                <div class="dt-fm-right">



                    <div class="wrap-clndr-time">

                        <div class="wrap-clndr-cl" id="remove_from_form">
                            <h2 class="slct-dt-tm">Select a Date & Time</h2>
                            <div id="date-calendar"></div>

                        </div>

                        <div>
                            <!-- Time Picker - Start Time -->
                            <div class="time-picker-overlay" id="startTimePicker">

                                <div class="time-picker-header">
                                    <div class="wrp-time-strc">
                                        <h5 class="time-strt-end" style="background-color: #4CAF50;"><i class="fa-regular fa-clock"></i> Start Time</h5>
                                    </div>
                                    <h3 id="time-picker-date"></h3>
                                    <button class="close-time-picker" id="closestartTimePicker"><i class="fa-solid fa-xmark"></i></button>
                                </div>
                                <div class="time-slots-container" id="timeSlotsContainer">
                                    <!-- Start time slots will be generated here -->
                                </div>
                            </div>

                            <!-- Time Picker - End Time -->
                            <div class="time-picker-overlay" id="endTimePicker">

                                <div class="time-picker-header">

                                    <div class="wrp-time-strc">
                                        <h5 class="time-strt-end" style="background-color: #a92828;"><i class="fa-regular fa-clock"></i> End Time</h5>
                                    </div>

                                    <h3 id="end-time-picker-date">Select an End Time</h3>
                                    <button class="close-time-picker" id="closeEndTimePicker"><i class="fa-solid fa-xmark"></i></button>
                                </div>
                                <div class="time-slots-container" id="endTimeSlotsContainer">
                                    <!-- End time slots will be generated here -->
                                </div>
                                <div class="time-navigation-buttons">
                                    <button id="backToStartTime"><i class="fa-solid fa-arrow-left"></i> Back to start time</button>
                                    <!--                                <button id="confirmEndTime" disabled>Confirm</button>-->
                                </div>

                            </div>
                        </div>

                    </div>

                    <div id="fillForm" class="last-step-form" style="display: none;">
                        <div class="wrap-hedng-bck-btn">
                            <button id="backToCalendar"><i class="fa-solid fa-arrow-left"></i></button>
                            <h2>Enter Appointment Details</h2>
                        </div>
                        <?php echo form_open('', ['id' => 'appmgr-public-form']); ?>
                        <div class="row">
                            <?php
                            echo form_hidden('location');
                            echo form_hidden('appointee');
                            echo form_hidden('additional_appointees');
                            echo form_hidden('timezone');
                            echo form_hidden('appointment_date');
                            echo form_hidden('appointment_start_time');
                            echo form_hidden('appointment_end_time');
                            ?>
                            <div class="col-md-12">
                                <div class="appmgr-conflict-banner" id="appmgrConflictBanner" style="display:none;"></div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?php echo _l('appmgr_ser_cats'); ?>:</label>
                                    <select class="selectpicker" name="service_cat" data-live-search="true">
                                        <option value=""> </option>
                                        <?php foreach ($categories as $cat) { ?>
                                            <option value="<?php echo $cat['id']; ?>"> <?php echo $cat['name']; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?php echo _l('appmgr_treatments'); ?>:</label>
                                    <select class="selectpicker" name="treatment" data-live-search="true">
                                        <option value=""></option>
                                        <?php foreach ($treatments as $treatment) { ?>
                                            <option value="<?php echo $treatment['id']; ?>"><?php echo $treatment['tittle']; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>

                            </div>

                            <div class="col-md-12">
                                <?php echo render_input('company', _l('appmgr_name_label')); ?>
                            </div>

                            <div class="col-md-6">
                                <?php echo render_input('email', _l('appmgr_email_label')); ?>
                            </div>

                            <div class="col-md-6">
                                <?php echo render_input('phonenumber', _l('appmgr_phone_label')); ?>
                            </div>


                            <!-- <div class="col-md-12">
                                <div class="form-group">
                                    <label for="usr">Guest Email (Optional):</label>
                                    <input type="text" name="attendees" class="form-control">
                                </div>
                            </div> -->


                            <div class="col-md-12">
                                <?php echo render_textarea('description', _l('appmgr_public_frm_lbl_desc')); ?>
                            </div>

                        </div>
                        <div class="form-submit-btn">
                            <button type="submit"><?php echo _l('appmgr_btn_book'); ?></button>
                        </div>
                        <?php echo form_close(); ?>
                    </div>

                </div>
            </div>
        </div>


        <!--Last Option-->
        <div class="meeting-details-sec" style="display:none">
            <div>
                <div class="confirmation-check">
                    <i class="fa-solid fa-check"></i>
                </div>
                <h2>Appointment has been successfully scheduled. </h2>
                <p>Our team will contact you shortly.</p>
                <a class="back-to-booking" href="#"><i class="fa-solid fa-arrow-left"></i> Back To Booking </a>
            </div>
        </div>
        <!--Close Last Option-->
    </div>
</section>
<script>
    var disabledDatesString = '<?php echo json_encode($holidays); ?>';
</script>
<script type="text/javascript" id="full-calendar-js"
    src="<?php echo site_url('modules/appointment_manager/assets/js/public_form_new.js?v=20260425c'); ?>"></script>
