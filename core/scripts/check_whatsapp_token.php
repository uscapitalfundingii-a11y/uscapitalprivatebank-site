<?php

declare(strict_types=1);

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$crmRoot = '/home/dh_9a4ezr/uscapitalprivatebank.com/crm';
$configFile = $crmRoot . '/application/config/app-config.php';
$moduleFile = $crmRoot . '/modules/whatsapp/whatsapp.php';
$vendorAutoload = $crmRoot . '/modules/whatsapp/vendor/autoload.php';

if (!file_exists($configFile) || !file_exists($moduleFile) || !file_exists($vendorAutoload)) {
    fwrite(STDERR, "missing_files\n");
    exit(1);
}

$config = file_get_contents($configFile);
$readDefine = static function (string $constant) use ($config): string {
    $pattern = "/define\\('" . preg_quote($constant, '/') . "',\\s*'([^']*)'\\);/";
    if (!preg_match($pattern, $config, $matches)) {
        throw new RuntimeException("missing_define:$constant");
    }

    return $matches[1];
};

$dbHost = $readDefine('APP_DB_HOSTNAME');
$dbUser = $readDefine('APP_DB_USERNAME');
$dbPass = $readDefine('APP_DB_PASSWORD');
$dbName = $readDefine('APP_DB_NAME');

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_errno) {
    throw new RuntimeException('connect_failed:' . $mysqli->connect_error);
}

$result = $mysqli->query("SELECT name, value FROM tbloptions WHERE name IN ('whatsapp_verification_id','whatsapp_product_token')");
if (!$result) {
    throw new RuntimeException('query_failed:' . $mysqli->error);
}

$values = [];
while ($row = $result->fetch_assoc()) {
    $values[$row['name']] = (string) $row['value'];
}
$result->free();
$mysqli->close();

$moduleSource = file_get_contents($moduleFile);
preg_match('/Module URI:\\s*(.+)$/mi', $moduleSource, $matches);
$moduleUri = trim($matches[1] ?? '');
$itemId = basename(parse_url($moduleUri, PHP_URL_PATH) ?? '');

require $vendorAutoload;

$verificationDecoded = base64_decode($values['whatsapp_verification_id'] ?? '', true);
echo 'module_uri=' . $moduleUri . PHP_EOL;
echo 'item_id=' . $itemId . PHP_EOL;
echo 'verification_raw=' . ($values['whatsapp_verification_id'] ?? '') . PHP_EOL;
echo 'verification_decoded=' . $verificationDecoded . PHP_EOL;
echo 'token_len=' . strlen($values['whatsapp_product_token'] ?? '') . PHP_EOL;

if ($verificationDecoded === false) {
    echo "verification_decode_failed\n";
    exit(0);
}

$parts = explode('|', $verificationDecoded);
echo 'verification_parts=' . json_encode($parts) . PHP_EOL;

try {
    $decoded = JWT::decode((string) ($values['whatsapp_product_token'] ?? ''), new Key($parts[3] ?? '', 'HS512'));
    echo 'jwt_decode=success' . PHP_EOL;
    echo 'jwt_item_id=' . ($decoded->item_id ?? '') . PHP_EOL;
    echo 'jwt_buyer=' . ($decoded->buyer ?? '') . PHP_EOL;
    echo 'jwt_purchase_code=' . ($decoded->purchase_code ?? '') . PHP_EOL;
    echo 'jwt_check_interval=' . ($decoded->check_interval ?? '') . PHP_EOL;
} catch (Throwable $e) {
    echo 'jwt_decode=failed' . PHP_EOL;
    echo 'jwt_error=' . $e->getMessage() . PHP_EOL;
}
