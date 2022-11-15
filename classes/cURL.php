<?php

class cURL {
    public static function exec($url, $data = false, $customRequest = 'POST', $headers = false, $userpwd = false, $ssl = false) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($data) {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if ($userpwd) {
            curl_setopt($ch, CURLOPT_USERPWD, $userpwd);
        }
        if ($ssl) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSLCERT, $ssl['cert']);
            curl_setopt($ch, CURLOPT_SSLKEY, $ssl['key']);
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $customRequest);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        Log::handler(sprintf('%s::%s | %d | %s | %s | %s | %s', __CLASS__, __FUNCTION__, $http_code, $url,
            serialize($headers), serialize($data), serialize($result)));
        if ($http_code != 200 && $http_code != 201) {
            Log::error(sprintf('%s::%s | %d | %s | %s | %s | %s | %s', __CLASS__, __FUNCTION__, $http_code,
                $url, serialize($data), serialize($result), serialize($headers), curl_error($ch)));
        }
        curl_close($ch);
        return $result;
    }
}