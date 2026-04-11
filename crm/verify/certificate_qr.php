<?php
require_once __DIR__ . '/crm_verify_auth.php';
require_once __DIR__ . '/phpqrcode/qrlib.php';

$code = strtoupper(trim((string) ($_GET['code'] ?? '')));
$certificate = verify_find_certificate_by_code($code);

if ($certificate === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Certificate not found.';
    exit;
}

$verificationUrl = 'https://www.uscapitalprivatebank.com/crm/verify/certificate.php?code=' . rawurlencode($certificate['code']);

header('Content-Type: image/png');
QRcode::png($verificationUrl, false, QR_ECLEVEL_M, 6, 1);
