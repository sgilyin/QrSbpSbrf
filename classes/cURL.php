<?php

class cURL {
    public static function exec($url, $data = false, $customRequest = 'POST', $headers = false, $userpwd = false, $ssl = false) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        !$data ?: curl_setopt($ch, CURLOPT_POST, TRUE);
        !$data ?: curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        !$headers ?: curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        !$userpwd ?: curl_setopt($ch, CURLOPT_USERPWD, $userpwd);
        !$ssl ?: curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        !$ssl ?: curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        !$ssl ?: curl_setopt($ch, CURLOPT_SSLCERT, $ssl->certPath);
        !$ssl ?: curl_setopt($ch, CURLOPT_SSLKEY, $ssl->keyPath);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $customRequest);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        Log::handler(sprintf('%s::%s | %d | %s | %s | %s', __CLASS__, __FUNCTION__, $http_code, $url,
            serialize($data), serialize($result)));
        if ($http_code != 200 && $http_code != 201) {
            Log::error(sprintf('%s::%s | %d | %s | %s | %s', __CLASS__, __FUNCTION__, $http_code, $url,
                serialize($data), serialize($result)));
        }
        return $result;
    }
}