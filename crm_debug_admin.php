<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
register_shutdown_function(function () {
    $e = error_get_last();
    if ($e) {
        echo "SHUTDOWN:" . print_r($e, true) . PHP_EOL;
    }
});
set_error_handler(function($severity, $message, $file, $line){
    echo "ERROR[$severity] $message in $file:$line" . PHP_EOL;
    return false;
});
set_exception_handler(function($e){
    echo 'EXCEPTION: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
});
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'www.uscapitalprivatebank.com';
$_SERVER['REQUEST_URI'] = '/crm/admin/';
$_SERVER['SCRIPT_NAME'] = '/crm/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/crm/index.php';
include __DIR__ . '/crm/index.php';
