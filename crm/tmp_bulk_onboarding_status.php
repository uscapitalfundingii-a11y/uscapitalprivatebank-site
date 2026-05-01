<?php
// One-time status probe. See D:\GithubRepos\AGENTS.md for workspace rules.
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('BASEPATH', __DIR__ . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
define('APPPATH', __DIR__ . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);

require APPPATH . 'config/app-config.php';

$db = @new mysqli(APP_DB_HOSTNAME, APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);
if ($db->connect_errno) {
    $db = @new mysqli('127.0.0.1', APP_DB_USERNAME, APP_DB_PASSWORD, APP_DB_NAME);
}
if ($db->connect_errno) {
    fwrite(STDERR, 'Database connection failed: ' . $db->connect_error . PHP_EOL);
    exit(1);
}
$db->set_charset(APP_DB_CHARSET ?: 'utf8mb4');

$prefix = defined('APP_DB_PREFIX') ? APP_DB_PREFIX : 'tbl';
$subject = 'Onboarding Invitation - US Capital Private Bank, ETO';

function scalar(mysqli $db, string $sql, array $params = [])
{
    $stmt = $db->prepare($sql);
    if (! $stmt) {
        throw new RuntimeException($db->error);
    }
    if ($params) {
        $types = '';
        $bind = [];
        foreach ($params as $param) {
            $types .= is_int($param) ? 'i' : 's';
            $bind[] = $param;
        }
        $stmt->bind_param($types, ...$bind);
    }
    if (! $stmt->execute()) {
        throw new RuntimeException($stmt->error);
    }
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_row() : null;
    if ($result) {
        $result->free();
    }
    $stmt->close();

    return $row ? $row[0] : null;
}

$stats = [
    'tickets_with_subject' => (int) scalar($db, 'SELECT COUNT(*) FROM `' . $prefix . 'tickets` WHERE subject = ?', [$subject]),
    'tickets_last_2_hours' => (int) scalar($db, 'SELECT COUNT(*) FROM `' . $prefix . 'tickets` WHERE subject = ? AND date >= DATE_SUB(NOW(), INTERVAL 2 HOUR)', [$subject]),
    'mail_queue_last_2_hours' => (int) scalar($db, 'SELECT COUNT(*) FROM `' . $prefix . 'mail_queue` WHERE date >= DATE_SUB(NOW(), INTERVAL 2 HOUR)'),
    'mail_queue_pending' => (int) scalar($db, 'SELECT COUNT(*) FROM `' . $prefix . 'mail_queue` WHERE status = "pending"'),
    'mail_queue_sent' => (int) scalar($db, 'SELECT COUNT(*) FROM `' . $prefix . 'mail_queue` WHERE status = "sent"'),
    'latest_matching_ticket' => scalar($db, 'SELECT MAX(ticketid) FROM `' . $prefix . 'tickets` WHERE subject = ?', [$subject]),
    'unique_contact_emails' => (int) scalar($db, 'SELECT COUNT(DISTINCT LOWER(email)) FROM `' . $prefix . 'contacts` WHERE email IS NOT NULL AND email != ""'),
    'unique_active_staff_emails' => (int) scalar($db, 'SELECT COUNT(DISTINCT LOWER(email)) FROM `' . $prefix . 'staff` WHERE active = 1 AND email IS NOT NULL AND email != ""'),
];

echo json_encode($stats, JSON_PRETTY_PRINT), PHP_EOL;
