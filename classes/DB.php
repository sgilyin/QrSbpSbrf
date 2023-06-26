<?php

class DB {
    public static function query($query){
        $mysqli = new mysqli(hostDB, userDB, passDB, nameDB);
        if ($mysqli->connect_errno) {
            Log::handler(sprintf('%s::%s | No DB connection: %s', __CLASS__, __FUNCTION__,
                $mysqli->connect_error));
            Log::error(sprintf('%s::%s | No DB connection: %s', __CLASS__, __FUNCTION__,
                $mysqli->connect_error));
            exit();
        }
        $mysqli->set_charset('utf8mb4');
        $result = $mysqli->query($query);
        $errNo = $mysqli->errno;
        if (!$result) {
            Log::error(sprintf('%s::%s | %s | %s | %s',__CLASS__, __FUNCTION__, $query, $mysqli->errno,
                $mysqli->error));
            Log::handler(sprintf('%s::%s | %s | %s | %s',__CLASS__, __FUNCTION__, $query, $mysqli->errno,
                $mysqli->error));
        }
        $mysqli->close();
        return $result ?? $errNo;
    }
}
/**
 * CREATE TABLE IF NOT EXISTS `payments_qr` (
 * `order_id` varchar(32) COLLATE utf8_bin NOT NULL,
 * `order_number` varchar(36) COLLATE utf8_bin NOT NULL,
 * `order_datetime` DATETIME NOT NULL,
 * `operation_sum` int NOT NULL,
 * `operation_datetime` DATETIME NOT NULL,
 * `order_state` varchar(10) COLLATE utf8_bin NOT NULL,
 * UNIQUE KEY `order_id` (`order_id`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
 */