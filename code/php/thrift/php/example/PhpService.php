<?php
namespace example;
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/CalcService.php';
require_once __DIR__ . '/Types.php';

use Thrift\ClassLoader\ThriftClassLoader;

$loader = new ThriftClassLoader();
$loader->registerNamespace('Thrift', __DIR__ . '/../vendor/apache/thrift/lib/php/lib');
$loader->register();

if (php_sapi_name() == 'cli') {
    ini_set("display_errors", "stderr");
}

use Thrift\Protocol\TBinaryProtocol;
use Thrift\Transport\TPhpStream;
use Thrift\Transport\TBufferedTransport;

class CalculatorHandler implements CalcServiceIf
{

    /**
     * 计算两个数的和
     *
     * @param int $data_one
     * @param int $data_two
     * @return \example\Result 服务返回结果
     *
     */
    public function sum($data_one, $data_two)
    {
        if (!is_numeric($data_one) || !is_numeric($data_two)) {
            $error_code = ErrorCode::FAILED;
            $messages = Constant::get('ERROR_CODE_MESSAGE');
            $msg = $messages[$error_code];
            $result = new Result();
            $result->code = $error_code;
            $result->message = $msg;
            return $result;
        }
        $code = ErrorCode::SUCCESS;
        $messages = Constant::get('ERROR_CODE_MESSAGE');
        $msg = $messages[$code];
        $result = new Result();
        $result->code = $code;
        $result->message = $msg;

        $result_data = new Data();
        $result_data->data_one = $data_one;
        $result_data->data_two = $data_two;
        $result_data->sum = $data_one + $data_two;

        $result->data = $result_data;
        return $result;
    }
}

header('Content-Type', 'application/x-thrift');
if (php_sapi_name() == 'cli') {
    echo "\r\n";
}

$handler = new CalculatorHandler();
$processor = new CalcServiceProcessor($handler);

$transport = new TBufferedTransport(new TPhpStream(TPhpStream::MODE_R | TPhpStream::MODE_W));
$protocol = new TBinaryProtocol($transport, true, true);

$transport->open();
$processor->process($protocol, $protocol);
$transport->close();
