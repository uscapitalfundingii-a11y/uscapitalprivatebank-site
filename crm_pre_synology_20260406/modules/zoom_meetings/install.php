<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

// Clear existing data if tables exist
if ($CI->db->table_exists(db_prefix() . 'zoom_meetings')) {
    $CI->db->empty_table(db_prefix() . 'zoom_meetings');
}

if ($CI->db->table_exists(db_prefix() . 'zoom_client_meetings')) {
    $CI->db->truncate(db_prefix() . 'zoom_client_meetings');
}

if ($CI->db->table_exists(db_prefix() . 'zoom')) {
    $CI->db->empty_table(db_prefix() . 'zoom');
}

if ($CI->db->table_exists(db_prefix() . 'zoom_client')) {
    $CI->db->truncate(db_prefix() . 'zoom_client');
}

// Create Zoom Meetings Table
if (!$CI->db->table_exists(db_prefix() . 'zoom_meetings')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'zoom_meetings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `subject` varchar(255) NOT NULL,
        `meeting_id` varchar(255) NOT NULL,
        `start_time` datetime DEFAULT NULL,
        `timezone` varchar(100) DEFAULT NULL,
        `duration` varchar(20) DEFAULT NULL,
        `agenda` text DEFAULT NULL,
        `join_url` varchar(500) DEFAULT NULL,
        `access_token` text DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
}

// Create or Update Zoom Table
if ($CI->db->table_exists(db_prefix() . 'zoom')) {
    // Add Missing Fields
    if (!$CI->db->field_exists('refresh_token', db_prefix() . 'zoom')) {
        $CI->db->query('ALTER TABLE `' . db_prefix() . 'zoom` ADD COLUMN `refresh_token` TEXT DEFAULT NULL;');
    }

    if (!$CI->db->field_exists('access_token', db_prefix() . 'zoom')) {
        $CI->db->query('ALTER TABLE `' . db_prefix() . 'zoom` ADD COLUMN `access_token` TEXT DEFAULT NULL;');
    }

    // Ensure Dummy Data Exists
    $count = $CI->db->count_all(db_prefix() . 'zoom');
    if ($count == 0) {
        $api_key = 'Zoom Client ID';
        $api_secret = 'Zoom Client Secret';
        $zoom_email = 'Zoom Redirect URI';

        $data = [
            'zoom_email' => $zoom_email,
            'api_key' => $api_key,
            'api_secret' => $api_secret,
            'access_token' => null,
            'refresh_token' => null,
        ];

        $CI->db->insert(db_prefix() . 'zoom', $data);
    }
} else {
    // Create the Zoom Table
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'zoom` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `zoom_email` varchar(255) NOT NULL,
        `api_key` varchar(255) NOT NULL,
        `api_secret` varchar(255) NOT NULL,
        `access_token` text DEFAULT NULL,
        `refresh_token` text DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');

    // Insert Dummy Data
    $api_key = 'Zoom Redirect URI';
    $api_secret = 'Zoom Client Secret';
    $zoom_email = 'Zoom Client ID';

    $data = [
        'zoom_email' => $zoom_email,
        'api_key' => $api_key,
        'api_secret' => $api_secret,
        'access_token' => null,
        'refresh_token' => null,
    ];

    $CI->db->insert(db_prefix() . 'zoom', $data);
}

// Create Zoom Client Meetings Table (if necessary)
if (!$CI->db->table_exists(db_prefix() . 'zoom_client_meetings')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'zoom_client_meetings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `meeting_id` varchar(255) NOT NULL,
        `client_id` int(11) NOT NULL,
        `start_time` datetime DEFAULT NULL,
        `join_url` varchar(500) DEFAULT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
}

// Create Zoom Client Table (if necessary)
if (!$CI->db->table_exists(db_prefix() . 'zoom_client')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'zoom_client` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `zoom_email` varchar(255) NOT NULL,
        `client_id` int(11) NOT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
}
