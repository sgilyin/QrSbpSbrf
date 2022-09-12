<?php

class Sber {
    public static function oauth($client) {
        $url = 'https://api.sberbank.ru:8443/prod/tokens/v2/' . __FUNCTION__;
    }

    public static function creation($args) {
        var_dump($args);
    }
}