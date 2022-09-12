<?php

spl_autoload_register(function ($class) {
    include "classes/$class.php";
});

$remoteAddr = filter_input(INPUT_SERVER, 'REMOTE_ADDR');
$requestMethod = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
$logDir = __DIR__;#.dirname(filter_input(INPUT_SERVER, 'PHP_SELF'));
$requestId = filter_input(INPUT_SERVER, 'REQUEST_ID');

$inputData = json_decode(file_get_contents("php://input"));

#$inputData = filter_input_array(INPUT_POST) ??
#    json_decode(file_get_contents("php://input"), true) ??
#    filter_input_array(INPUT_GET);

Log::access(sprintf('%s | %s | %s | %s', $remoteAddr, $requestId, $requestMethod, serialize($inputData)));

$method = preg_replace('/\/sbrf\//', '', parse_url($_SERVER['REQUEST_URI'])['path']);

switch ($method) {
    case 'pay':
        echo 'pay';
        break;
    case 'creation':
        Sber::$method($inputData);
        break;
    case 'status':
        echo 'status';
        break;
    case 'revocation':
        echo 'revocation';
        break;
    case 'cancel':
        echo 'cancel';
        break;
    case 'registry':
        echo 'registry';
        break;
    case 'notify':
        echo 'notify';
        break;

    default:
        break;
}
