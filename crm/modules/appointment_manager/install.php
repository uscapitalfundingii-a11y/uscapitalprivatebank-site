<?php
defined('BASEPATH') or exit('No direct script access allowed');
if (!$CI->db->table_exists(db_prefix() . 'appmgr_holidays')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "appmgr_holidays` (
    `id` int(11) NOT NULL,
    `name` varchar(100) NOT NULL,
    `leave_date` date NOT NULL,
    `added_by` int(11) NOT NULL,
    `description` text DEFAULT NULL,
    `added_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'appmgr_holidays` ADD PRIMARY KEY (`id`), ADD KEY `added_by` (`added_by`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'appmgr_holidays` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}
if (!$CI->db->table_exists(db_prefix() . 'appmgr_locations')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "appmgr_locations` (
    `id` int(11) NOT NULL,
    `name` varchar(100) NOT NULL,
    `operation_start_time` time NOT NULL,
    `operation_end_time` time NOT NULL,
    `added_by` int(11) NOT NULL,
    `description` text DEFAULT NULL,
    `added_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'appmgr_locations` ADD PRIMARY KEY (`id`), ADD KEY `added_by` (`added_by`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'appmgr_locations` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}
if (!$CI->db->table_exists(db_prefix() . 'appmgr_appointments')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "appmgr_appointments` (
        `id` int(11) NOT NULL,
        `appointee` int(11) NOT NULL,
        `client` int(11) NOT NULL,
        `appointer` int(11) NOT NULL COMMENT 'added by staff',
        `appointment_date` date NOT NULL,
        `appointment_start_time` time NOT NULL,
        `appointment_end_time` time NOT NULL,
        `location` int(11) NOT NULL,
        `description` text DEFAULT NULL,
         `treatment` int(11) DEFAULT NULL,
         `status` int(11) NOT NULL DEFAULT '1' COMMENT '1:pending, 2:approved, 3:cancelled',
         `opted_rooms` mediumtext DEFAULT NULL,
         `reminder_before` int(11) DEFAULT NULL,
         `reminder_before_type` VARCHAR(50) DEFAULT NULL,
         `isstartnotified` tinyint(4) DEFAULT 0,
        `added_by` INT(11) NOT NULL,
        `added_at` datetime NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'appmgr_appointments` ADD PRIMARY KEY (`id`), ADD KEY `appointer` (`appointer`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'appmgr_appointments` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}
if (!$CI->db->table_exists(db_prefix() . 'appmgr_appointies')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "appmgr_appointies` (
        `id` int(11) NOT NULL,
        `location` int(11) NOT NULL,
        `staff` int(11) NOT NULL,
        `designation` varchar(100) DEFAULT NULL,
        `age` int(11) DEFAULT NULL,
        `gender` varchar(100) DEFAULT NULL,
        `description` text DEFAULT NULL,
        `tags` mediumtext DEFAULT NULL,
        `added_by` INT(11) NOT NULL,
        `added_at` datetime NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'appmgr_appointies` ADD PRIMARY KEY (`id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'appmgr_appointies` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}
if (!$CI->db->table_exists(db_prefix() . 'appmgr_appointee_availibility')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "appmgr_appointee_availibility` (
        `id` int(11) NOT NULL,
        `appointee_id` int(11) NOT NULL,
        `repetition` varchar(50) NOT NULL,
        `available_date_from` date DEFAULT NULL,
        `available_date_to` date DEFAULT NULL,
        `from_time` time DEFAULT NULL,
        `to_time` time DEFAULT NULL,
        `added_by` INT(11) NOT NULL,
        `added_at` datetime NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'appmgr_appointee_availibility` ADD PRIMARY KEY (`id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'appmgr_appointee_availibility` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}
if (!$CI->db->table_exists(db_prefix() . 'appmgr_treatments')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "appmgr_treatments` (
        `id` int(11) NOT NULL,
        `tittle` varchar(100) NOT NULL,
        `description` text DEFAULT NULL,
        `added_by` INT(11) NOT NULL,
        `added_at` datetime NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'appmgr_treatments` ADD PRIMARY KEY (`id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'appmgr_treatments` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}

if (!$CI->db->table_exists(db_prefix() . 'appmgr_appointment_status')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "appmgr_appointment_status` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(50) NOT NULL,
        `statusorder` INT(11) DEFAULT NULL,
        `color` varchar(10) DEFAULT '#28B8DA',
        `isdefault` INT(11) NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
    $CI->db->query("INSERT INTO `" . db_prefix() . "appmgr_appointment_status` (`name`, `statusorder`, `color`, `isdefault`) 
                    VALUES 
                    ('Upcoming', 1, '#d4f09b', 0), 
                    ('Approved', 2, '#2ceeab', 0), 
                    ('Cancelled', 3, '#f86c72', 0),
                    ('Missed', 4, '#e7674a', 0),
                    ('Waiting For Approval', 5, '#eb25ba', 1)
                    ;");
}

if (total_rows(db_prefix() . 'emailtemplates', ['type' => 'appointment_manager', 'slug' => 'appointment-manager-appointment-reminder-to-staff']) == 0) {
    $CI->db->query("INSERT INTO `" . db_prefix() . "emailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
('appointment_manager',	'appointment-manager-appointment-reminder-to-staff',	'english',	'Appointment Reminder (Reminder send To staff)',	'Reminder appointment - {treatment}',	'Hi {staff_firstname} ! <br /><br />This is a reminder for event <a href=\\\"{appointment_link}\\\">{treatment}</a> scheduled at {appointment_date}. <br /><br />Regards.',	'',	'',	0,	1,	0)");
}
if (total_rows(db_prefix() . 'emailtemplates', ['type' => 'appointment_manager', 'slug' => 'appointment-manager-appointment-status-upcoming-to-client']) == 0) {
    $CI->db->query("INSERT INTO `" . db_prefix() . "emailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
('appointment_manager',	'appointment-manager-appointment-status-upcoming-to-client',	'english',	'Appointment Upcoming (Changed upcoming status to the client)',	'Upcoming appointment - {treatment}',	'Hi {client_company}! <br /><br />This is a notification of your appointment <a href=\\\"{appointment_link}\\\">{treatment}</a> which was scheduled at {appointment_date} has {status} successfully. <br /><br />Regards.',	'',	'',	0,	1,	0)");
}
if (total_rows(db_prefix() . 'emailtemplates', ['type' => 'appointment_manager', 'slug' => 'appointment-manager-appointment-status-approved-to-client']) == 0) {
    $CI->db->query("INSERT INTO `" . db_prefix() . "emailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
('appointment_manager',	'appointment-manager-appointment-status-approved-to-client',	'english',	'Appointment Approved (Changed approved status to the client)',	'Approved appointment - {treatment}',	'Hi {client_company}! <br /><br />This is a notification of your appointment <a href=\\\"{appointment_link}\\\">{treatment}</a> which was scheduled at {appointment_date} has {status} successfully. <br /><br />Regards.',	'',	'',	0,	1,	0)");
}
if (total_rows(db_prefix() . 'emailtemplates', ['type' => 'appointment_manager', 'slug' => 'appointment-manager-appointment-status-missed-to-client']) == 0) {
    $CI->db->query("INSERT INTO `" . db_prefix() . "emailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
('appointment_manager',	'appointment-manager-appointment-status-missed-to-client',	'english',	'Appointment Missed (Changed missed status to the client)',	'Missed appointment - {treatment}',	'Hi {client_company}! <br /><br />This is a notification of your appointment <a href=\\\"{appointment_link}\\\">{treatment}</a> which was scheduled at {appointment_date} has {status} by you. <br /><br />Regards.',	'',	'',	0,	1,	0)");
}
if (total_rows(db_prefix() . 'emailtemplates', ['type' => 'appointment_manager', 'slug' => 'appointment-manager-appointment-status-cancelled-to-client']) == 0) {
    $CI->db->query("INSERT INTO `" . db_prefix() . "emailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
('appointment_manager',	'appointment-manager-appointment-status-cancelled-to-client',	'english',	'Appointment Cancelled (Changed cancelled status to the client)',	'Cancelled appointment - {treatment}',	'Hi {client_company}! <br /><br />This is a notification of your appointment <a href=\\\"{appointment_link}\\\">{treatment}</a> which was scheduled at {appointment_date} has {status} . <br /><br />Regards.',	'',	'',	0,	1,	0)");
}
if (total_rows(db_prefix() . 'emailtemplates', ['type' => 'appointment_manager', 'slug' => 'appointment-manager-appointment-reminder-to-client']) == 0) {
    $CI->db->query("INSERT INTO `" . db_prefix() . "emailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
('appointment_manager',	'appointment-manager-appointment-reminder-to-client',	'english',	'Appointment Reminder (Reminder send To client)',	'Reminder appointment - {treatment}',	'Hi {client_company}! <br /><br />This is a reminder for event <a href=\\\"{appointment_link}\\\">{treatment}</a> scheduled at {appointment_date}. <br /><br />Regards.',	'',	'',	0,	1,	0)");
}
if (total_rows(db_prefix() . 'emailtemplates', ['type' => 'appointment_manager', 'slug' => 'appointment-manager-appointment-booked-to-client']) == 0) {
    $CI->db->query("INSERT INTO `" . db_prefix() . "emailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
('appointment_manager',	'appointment-manager-appointment-booked-to-client',	'english',	'Appointment Booked (Notification send To client)',	'Booked appointment - {treatment}',	'Hi {client_company}! <br /><br />This is a notification of your appointment <a href=\\\"{appointment_link}\\\">{treatment}</a> which was scheduled at {appointment_date} has {status} . <br /><br />Regards. <br /><br />Regards.',	'',	'',	0,	1,	0)");
}
if (total_rows(db_prefix() . 'emailtemplates', ['type' => 'appointment_manager', 'slug' => 'appointment-manager-appointment-booked-to-staff']) == 0) {
    $CI->db->query("INSERT INTO `" . db_prefix() . "emailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
('appointment_manager',	'appointment-manager-appointment-booked-to-staff',	'english',	'Appointment Booked (Notification send To staff)',	'Booked appointment - {treatment}',	'Hi {staff_firstname} ! <br /><br />This is a notification of your appointment <a href=\\\"{appointment_link}\\\">{treatment}</a> which was scheduled at {appointment_date} has {status} . <br /><br />Regards.',	'',	'',	0,	1,	0)");
}
if (total_rows(db_prefix() . 'emailtemplates', ['type' => 'appointment_manager', 'slug' => 'appointment-manager-appointment-wait-for-approval-to-client']) == 0) {
    $CI->db->query("INSERT INTO `" . db_prefix() . "emailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
('appointment_manager',	'appointment-manager-appointment-wait-for-approval-to-client',	'english',	'Appointment Booked and wait for approval (Approval Notification send To client)',	'Wating for approval appointment - {treatment}',	'Hi {client_company}! <br /><br />This is a notification of your appointment <a href=\\\"{appointment_link}\\\">{treatment}</a> which was scheduled at {appointment_date} has {status} and waiting for approval. <br /><br />Regards.',	'',	'',	0,	1,	0)");
}
if (total_rows(db_prefix() . 'emailtemplates', ['type' => 'appointment_manager', 'slug' => 'appointment-manager-appointment-wait-for-approval-to-staff']) == 0) {
    $CI->db->query("INSERT INTO `" . db_prefix() . "emailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
('appointment_manager',	'appointment-manager-appointment-wait-for-approval-to-staff',	'english',	'Appointment Booked and wait for approval (Approval Notification send To staff)',	'Wating for approval appointment - {treatment}',	'Hi {staff_firstname} ! <br /><br />This is a notification of your appointment <a href=\\\"{appointment_link}\\\">{treatment}</a> which was scheduled at {appointment_date} has {status} and waiting for approval. <br /><br />Regards.',	'',	'',	0,	1,	0)");
}
if (!$CI->db->table_exists(db_prefix() . 'appmgr_appointee_unavailibility')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "appmgr_appointee_unavailibility` (
        `id` int(11) NOT NULL,
        `appointee_id` INT(11) NOT NULL,
        `unavailable_date` date NOT NULL,
        `description` text DEFAULT NULL,
        `added_by` INT(11) NOT NULL,
        `added_at` datetime NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'appmgr_appointee_unavailibility` ADD PRIMARY KEY (`id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'appmgr_appointee_unavailibility` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}
add_option('am_g_accesstoken');
add_option('am_g_refreshtoken');
add_option('am_g_tokenexpirein');
add_option('appmgr_calendar_id');
$aptTable = db_prefix() . 'appmgr_appointments';
if (!$CI->db->field_exists('gcal_eventid', $aptTable)) {
    $CI->db->query("ALTER TABLE `" . $aptTable . "` ADD `gcal_eventid` varchar(255) DEFAULT NULL;");
}
if (total_rows(db_prefix() . 'emailtemplates', ['type' => 'appointment_manager', 'slug' => 'appointment-manager-appointment-status-change-to-client']) == 0) {
    $CI->db->query("INSERT INTO `" . db_prefix() . "emailtemplates` (`type`, `slug`, `language`, `name`, `subject`, `message`, `fromname`, `fromemail`, `plaintext`, `active`, `order`) VALUES
        ('appointment_manager',	'appointment-manager-appointment-status-change-to-client',	'english',	'Appointment Other status (Status Notification send To client)',	'{treatment}',	'Hi {client_company}! <br /><br />This is a notification of your appointment <a href=\\\"{appointment_link}\\\">{treatment}</a> which was scheduled at {appointment_date} has {status}. <br /><br />Regards.',	'',	'',	0,	1,	0)");
}
if (!$CI->db->field_exists('service_cat', $aptTable)) {
    $CI->db->query("ALTER TABLE `" . $aptTable . "` ADD `service_cat` varchar(250) DEFAULT NULL AFTER `treatment`;");
}
if (!$CI->db->field_exists('additional_appointees', $aptTable)) {
    $CI->db->query("ALTER TABLE `" . $aptTable . "` ADD `additional_appointees` text DEFAULT NULL AFTER `appointee`;");
}
if (!$CI->db->table_exists(db_prefix() . 'appmgr_service_cats')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . "appmgr_service_cats` (
        `id` int(11) NOT NULL,
        `service_id` int(11) NOT NULL,
        `name` varchar(100) NOT NULL,
        `added_at` date NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'appmgr_service_cats` ADD PRIMARY KEY (`id`);');
    $CI->db->query('ALTER TABLE `' . db_prefix() . 'appmgr_service_cats` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1');
}
//version change 103
add_option('appmgr_azure_clientid');
add_option('appmgr_azure_client_secret');
add_option('appmgr_azure_tenant_id');
add_option('appmgr_azure_cal_id');
if (!$CI->db->field_exists('azure_eventid', $aptTable)) {
    $CI->db->query("ALTER TABLE `" . $aptTable . "` ADD `azure_eventid` varchar(255) DEFAULT NULL;");
}

if (total_rows(db_prefix() . 'appmgr_appointment_status', ['name' => 'Cancelled'])) {
    $CI->db->where(['name' => 'Cancelled']);
    $CI->db->update(db_prefix() . 'appmgr_appointment_status', ['name' => 'Declined']);
}
