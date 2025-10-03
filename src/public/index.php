<?php
require __DIR__ . '/../Service.php';

$wsdl = __DIR__ . '/../wsdl/oneiss.wsdl';

// Return WSDL when requested
if (isset($_GET['wsdl'])) {
    header('Content-Type: application/wsdl+xml; charset=utf-8');
    readfile($wsdl);
    exit;
}

// Development: disable WSDL cache
ini_set('soap.wsdl_cache_enabled', '0');

try {
    $server = new SoapServer($wsdl);
    $server->setClass('Service');
    $server->handle();
} catch (Exception $e) {
    header('Content-Type: text/plain; charset=utf-8');
    http_response_code(500);
    echo "SOAP server error: " . $e->getMessage();
}
