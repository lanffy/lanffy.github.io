<?php

namespace example;

error_reporting(E_ALL);
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/CalcService.php';
require_once __DIR__ . '/Types.php';

use Thrift\ClassLoader\ThriftClassLoader;

$loader = new ThriftClassLoader();
$loader->registerNamespace('Thrift', __DIR__ . '/../thrift/lib/php/lib');
$loader->register();

use Thrift\Protocol\TBinaryProtocol;
use Thrift\Transport\THttpClient;
use Thrift\Transport\TBufferedTransport;
use Thrift\Exception\TException;


try {
    $socket = new THttpClient('localhost', 8080, '/php/PhpServer');
    $transport = new TBufferedTransport($socket, 1024, 1024);
    $protocol = new TBinaryProtocol($transport);

    /**
     * @var CalcServiceIf
     */
    $client = new CalcServiceClient($protocol);
    $transport->open();

    $sum = $client->sum(1, 2);
    var_dump($sum);

    $transport->close();
    echo '<br /> DONE <br />';

} catch (TException $tx) {
    print 'TException: ' . $tx->getMessage() . "\n";
}

