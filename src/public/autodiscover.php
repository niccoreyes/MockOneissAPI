<?php
// Dynamic WSDL generator using Laminas AutoDiscover when available
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../Service.php';
require __DIR__ . '/../Model/PushInjuryData.php';
require __DIR__ . '/../Model/PushApirData.php';
require __DIR__ . '/../Soap/MockOneissSoap.php';

use Laminas\Soap\AutoDiscover;
use MockOneiss\Soap\MockOneissSoap;

try {
    $autodiscover = new AutoDiscover();
    // Use the strongly-typed MockOneissSoap class for generation
    $autodiscover->setClass(MockOneissSoap::class)->setUri((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/webservice/index.php');
    header('Content-Type: application/wsdl+xml; charset=utf-8');
    echo $autodiscover->toXml();
} catch (Throwable $e) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Autodiscover error: " . $e->getMessage();
}
