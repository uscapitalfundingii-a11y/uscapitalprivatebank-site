<?php
session_start();
$baseDir = __DIR__ . '/files';

$fileParam = trim($_GET['file'] ?? '');
if (strpos($fileParam, '..') !== false || !$fileParam) {
    die("Invalid file.");
}

$fullPath = realpath($baseDir . '/' . $fileParam);
if (!$fullPath || strpos($fullPath, $baseDir) !== 0 || !is_file($fullPath)) {
    die("Unauthorized or missing file.");
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($fullPath) . '"');
header('Content-Length: ' . filesize($fullPath));
readfile($fullPath);
exit;
