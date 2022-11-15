<?php

spl_autoload_register(function ($class) {
    include "classes/$class.php";
});

include_once 'config.php';

$remoteAddr = filter_input(INPUT_SERVER, 'REMOTE_ADDR');
$requestMethod = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
$logDir = __DIR__;#.dirname(filter_input(INPUT_SERVER, 'PHP_SELF'));
$requestId = filter_input(INPUT_SERVER, 'REQUEST_ID');

$inputData = json_decode(file_get_contents("php://input"));

#$inputData = filter_input_array(INPUT_POST) ??
#    json_decode(file_get_contents("php://input"), true) ??
#    filter_input_array(INPUT_GET);

Log::access(sprintf('%s | %s | %s | %s', $remoteAddr, $requestId, $requestMethod, serialize($inputData)));
Log::handler(sprintf('%s | %s | %s | %s', $remoteAddr, $requestId, $requestMethod, serialize($inputData)));

$method = preg_replace('/\/sbrf\//', '', parse_url($_SERVER['REQUEST_URI'])['path']);
if ($method == '') {
    echo 'Silent is Golden';
} else {
    switch ($method) {
        case 'pay':
            echo "Not allowed method: pay";
            break;
        case 'registry':
            echo "Not allowed method: registry";
            break;

        default:
            echo Sber::$method($inputData);
            break;
    }
}
