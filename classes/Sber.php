<?php

class Sber {
    private static function authorizationGet() {
        return 'Basic ' . base64_encode(sprintf('%s:%s', sbrfClientId, sbrfClientSecret));
    }

    private static function guidv4($data = null) {
        $data = $data ?? random_bytes(16);
        assert(strlen($data) == 16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s%s%s%s%s%s%s', str_split(bin2hex($data), 4));
    }

    private static function sslGet() {
        $ssl['cert'] = sbrfSslCert;
        $ssl['key'] = sbrfSslKey;
        return $ssl;
    }

    private static function oauth($scope) {
        $url = 'https://api.sberbank.ru:8443/prod/tokens/v2/' . __FUNCTION__;
        $userpwd = sprintf('%s:%s', sbrfClientId, sbrfClientSecret);
        $headers[] = 'RqUID: ' . self::guidv4();
        $data['grant_type'] = 'client_credentials';
        $data['scope'] = "https://api.sberbank.ru/qr/order.$scope";
        return cURL::exec($url, http_build_query($data),'POST', $headers, $userpwd, self::sslGet());
    }

    private static function headersGet($uid, $scope) {
        $headers[] = 'Authorization: Bearer ' . json_decode(self::oauth($scope))->access_token;
        $headers[] = 'Content-Type: application/json';
        $headers[] = "RqUID: $uid";
        return $headers;
    }

    private static function payBilling($payment) {
        $data = array(
            "method" => "paymentUpdate",
            "user" => array(
                "user" => billingUser,
                "pswd" => billingPswd
            ),
            "params" => array(
                "payment" => array(
                    "contractId" => $payment->contractId,
                    "typeId" => billingPayTypeId,
                    "date" => $payment->date,
                    "sum" => $payment->sum,
                    "comment" => $payment->comment
                )
            )
        );
        $url = sprintf('%s/bgbilling/executer/json/ru.bitel.bgbilling.kernel.contract.balance/PaymentService', billingUrl);
        return cURL::exec($url,json_encode($data), 'POST');
    }

    public static function creation($args) {
        $uid = self::guidv4();
        $dateOrder = date("Y-m-d\TH:i:s\Z");
        $data['rq_uid'] = $uid;
        $data['rq_tm'] = $dateOrder;
        $data['member_id'] = sbrfMemberId;
        $data['order_number'] = substr($args->order_number.'_'.$uid, 0, 36);
        $data['order_create_date'] = $dateOrder;
        $data['id_qr'] = sbrfIdQr;
        $data['order_sum'] = $args->order_sum;
        $data['currency'] = '643';
        $data['description'] = $args->description;
        $data['sbp_member_id'] = '100000000111';
        return cURL::exec(sbrfApiUrl.__FUNCTION__, json_encode($data),'POST',
            self::headersGet($uid, 'create'), false, self::sslGet());
    }

    public static function status($args) {
        $uid = self::guidv4();
        $data['rq_uid'] = $uid;
        $data['rq_tm'] = date("Y-m-d\TH:i:s\Z");
        $data['tid'] = sbrfIdQr;
        $data['partner_order_number'] = $args->partner_order_number;
        return cURL::exec(sbrfApiUrl.__FUNCTION__, json_encode($data),'POST',
            self::headersGet($uid, __FUNCTION__), false, self::sslGet());
    }

    public static function revocation($args) {
        $uid = self::guidv4();
        $data['rq_uid'] = $uid;
        $data['rq_tm'] = date("Y-m-d\TH:i:s\Z");
        $data['order_id'] = $args->order_id;
        return cURL::exec(sbrfApiUrl.__FUNCTION__, json_encode($data),'POST',
            self::headersGet($uid, 'revoke'), false, self::sslGet());
    }

    public static function cancel($args) {
        $uid = self::guidv4();
        $data['rq_uid'] = $uid;
        $data['rq_tm'] = date("Y-m-d\TH:i:s\Z");
        $data['order_id'] = $args->order_id;
        $data['operation_id'] = $args->operation_id;
        $data['auth_code'] = $args->auth_code;
        $data['id_qr'] = sbrfIdQr;
        $data['tid'] = sbrfIdQr;
        $data['cancel_operation_sum'] = $args->cancel_operation_sum;
        $data['operation_currency'] = '643';
        return cURL::exec(sbrfApiUrl . __FUNCTION__, json_encode($data), 'POST',
            self::headersGet($uid, __FUNCTION__), false, self::sslGet());
    }

    public static function notify($args) {
        switch ($args->operationType) {
            case 'PAY':
                switch ($args->orderState) {
                    case 'PAID':
                        preg_match('/\d{1,}/', $args->partnerOrderNumber, $matches);
                        $payment = new stdClass();
                        $payment->contractId = intval($matches[0]);
                        $payment->sum = $args->operationSum / 100;
                        $payment->date = preg_replace('/T\d{2}\:\d{2}\:\d{2}Z/', '', $args->operationDateTime);
                        $payment->comment = $args->orderId.'|'.$args->operationId;
                        self::payBilling($payment);
                        break;

                    default:
                        break;
                }
                break;

            default:
                break;
        }
    }
}