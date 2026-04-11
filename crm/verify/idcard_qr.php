<?php
require_once __DIR__ . '/crm_verify_auth.php';
require_once __DIR__ . '/phpqrcode/qrlib.php';

$code = strtoupper(trim((string) ($_GET['code'] ?? '')));
$card = verify_find_id_card_by_code($code);

if ($card === null) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'ID card not found.';
    exit;
}

$verificationUrl = 'https://www.uscapitalprivatebank.com/crm/verify/idcard.php?code=' . rawurlencode($card['code']);

header('Content-Type: image/png');
QRcode::png($verificationUrl, false, QR_ECLEVEL_M, 6, 1);
