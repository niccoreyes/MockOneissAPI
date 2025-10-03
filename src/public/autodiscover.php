<?php
// Dynamic WSDL generator using Laminas AutoDiscover when available
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../Service.php';

use Laminas\Soap\AutoDiscover;

try {
    $autodiscover = new AutoDiscover();
    // Use the Service class for generation
    $autodiscover->setClass('Service')->setUri((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/webservice/index.php');
    header('Content-Type: application/wsdl+xml; charset=utf-8');
    echo $autodiscover->toXml();
} catch (Throwable $e) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Autodiscover error: " . $e->getMessage();
}
