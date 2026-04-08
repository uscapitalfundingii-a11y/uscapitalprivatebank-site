<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$baseDir = __DIR__ . '/files';
$code = trim((string) ($_POST['code'] ?? $_GET['code'] ?? ''));

if ($code === '') {
    header('Location: index.php?error=' . urlencode('Please enter a document code to continue.'));
    exit;
}

$matches = glob($baseDir . '/' . $code . '.*');

if (!$matches) {
    header('Location: index.php?error=' . urlencode('No verified document was found for that code.'));
    exit;
}

$file = basename($matches[0]);
header('Location: viewfile.php?file=' . urlencode($file));
exit;
