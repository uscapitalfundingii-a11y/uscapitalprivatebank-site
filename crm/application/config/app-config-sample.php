<?php

defined('BASEPATH') or exit('No direct script access allowed');
/*
* --------------------------------------------------------------------------
* Base Site URL
* --------------------------------------------------------------------------
*
* URL to your CodeIgniter root. Typically this will be your base URL,
* WITH a trailing slash:
*
*   http://example.com/
*
* If this is not set then CodeIgniter will try guess the protocol, domain
* and path to your installation. However, you should always configure this
* explicitly and never rely on auto-guessing, especially in production
* environments.
*
*/
define('APP_BASE_URL', '[base_url]');

/*
* --------------------------------------------------------------------------
* Encryption Key
* IMPORTANT: Do not change this ever!
* --------------------------------------------------------------------------
*
* If you use the Encryption class, you must set an encryption key.
* See the user guide for more info.
*
* http://codeigniter.com/user_guide/libraries/encryption.html
*
* Auto added on install
*/
define('APP_ENC_KEY', '[encryption_key]');

/**
 * Database Credentials
 * The hostname of your database server
 */
define('APP_DB_HOSTNAME', '[db_hostname]');

/**
 * The username used to connect to the database
 */
define('APP_DB_USERNAME', '[db_username]');

/**
 * The password used to connect to the database
 */
define('APP_DB_PASSWORD', '[db_password]');

/**
 * The name of the database you want to connect to
 */
define('APP_DB_NAME', '[db_name]');

/**
 * @since  2.3.0
 * Database charset
 */
define('APP_DB_CHARSET', 'utf8mb4');

/**
 * @since  2.3.0
 * Database collation
 */
define('APP_DB_COLLATION', 'utf8mb4_unicode_ci');

/**
 *
 * Session handler driver
 * By default the database driver will be used.
 *
 * For files session use this config:
 * define('SESS_DRIVER', 'files');
 * define('SESS_SAVE_PATH', NULL);
 * In case you are having problem with the SESS_SAVE_PATH consult with your hosting provider to set "session.save_path" value to php.ini
 *
 */
define('SESS_DRIVER', 'database');
define('SESS_SAVE_PATH', 'sessions');
define('APP_SESSION_COOKIE_SAME_SITE', 'Lax');

/**
 * Enables CSRF Protection
 */
define('APP_CSRF_PROTECTION', true);

/**
 * Optional shared secret for Laravel -> CRM single sign-on.
 * Use the same value as CRM_SSO_SECRET in the Laravel .env file.
 */
// define('APP_CRM_SSO_SECRET', 'replace-with-a-long-random-secret');

/**
 * Optional Base44 Super Agent integration.
 * You can either provide direct URLs per capability or use a base URL + endpoint template.
 *
 * Examples:
 * define('APP_BASE44_SUPERAGENT_API_KEY', 'replace-with-api-key');
 * define('APP_BASE44_SUPERAGENT_RESPONSE_URL', 'https://your-base44-endpoint-for-replies');
 * define('APP_BASE44_SUPERAGENT_FOLLOWUP_URL', 'https://your-base44-endpoint-for-followups');
 * define('APP_BASE44_SUPERAGENT_SUMMARY_URL', 'https://your-base44-endpoint-for-summaries');
 *
 * Or:
 * define('APP_BASE44_SUPERAGENT_BASE_URL', 'https://your-base44-api-host');
 * define('APP_BASE44_SUPERAGENT_ENDPOINT_TEMPLATE', '/agents/%s/messages');
 * define('APP_BASE44_SUPERAGENT_RESPONSE_AGENT', 'support_response_agent');
 * define('APP_BASE44_SUPERAGENT_FOLLOWUP_AGENT', 'support_followup_agent');
 * define('APP_BASE44_SUPERAGENT_SUMMARY_AGENT', 'support_response_agent');
 */

/**
 * Optional higher-accuracy dictation/transcription service for CRM reply boxes.
 * If this is configured, the CRM records audio and sends it to your transcription API.
 * If not configured, the CRM falls back to the browser speech engine.
 *
 * Examples:
 * define('APP_AI_TRANSCRIPTION_URL', 'https://api.openai.com/v1/audio/transcriptions');
 * define('APP_AI_TRANSCRIPTION_API_KEY', 'replace-with-api-key');
 * define('APP_AI_TRANSCRIPTION_AUTH_HEADER', 'Authorization');
 * define('APP_AI_TRANSCRIPTION_AUTH_PREFIX', 'Bearer');
 * define('APP_AI_TRANSCRIPTION_MODEL', 'whisper-1');
 * define('APP_AI_TRANSCRIPTION_LANGUAGE', 'en');
 */
