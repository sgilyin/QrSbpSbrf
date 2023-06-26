<?php

class Log {
    public static function error($logMessage) {
        self::exec(__FUNCTION__, $logMessage);
    }

    public static function access($logMessage) {
        self::exec(__FUNCTION__, $logMessage);
    }

    public static function handler($logMessage) {
        self::exec(__FUNCTION__, $logMessage);
    }

    public static function debug($logMessage) {
        self::exec(__FUNCTION__, $logMessage);
    }

    private static function exec($logType, $logMessage) {
        error_log(PHP_EOL.PHP_EOL.date('Y-m-d H:i:s')." | $logMessage",            3,
            implode('/', array_filter(array(filter_input(INPUT_SERVER, 'DOCUMENT_ROOT'),
                    substr(dirname(filter_input(INPUT_SERVER, 'PHP_SELF')),1), 'logs',
                    date('Ymd').".$logType.log"))));
    }
}