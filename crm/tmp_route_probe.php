<?php
// Temporary route probe for DreamHost verification.
// Future agents: see D:\GithubRepos\AGENTS.md and record confirmed fixes in GithubUtilities\solutions.md.
defined('BASEPATH') or define('BASEPATH', __DIR__);
defined('APPPATH') or define('APPPATH', __DIR__ . '/application/');
$route = [];
include APPPATH . 'config/routes.php';
header('Content-Type: application/json');
echo json_encode([
    'admin_route' => $route['admin'] ?? null,
    'time' => date('c'),
], JSON_PRETTY_PRINT);
