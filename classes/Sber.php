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
        $url = 'https://mc.api.sberbank.ru/prod/tokens/v3/' . __FUNCTION__;
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
        $result = cURL::exec(sbrfApiUrl.__FUNCTION__, json_encode($data),'POST',
            self::headersGet($uid, 'create'), false, self::sslGet());
        $decoded = json_decode($result);
        $query = sprintf("INSERT INTO payments_qr VALUES ('%s', '%s', '%s', %s, '%s', '%s')", $decoded->order_id,
            $decoded->order_number, $dateOrder, $data['order_sum'], $decoded->rq_tm, $decoded->order_state);
        DB::query($query);
        return $result;
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
        $result = cURL::exec(sbrfApiUrl.__FUNCTION__, json_encode($data),'POST',
            self::headersGet($uid, 'revoke'), false, self::sslGet());
        $decoded = json_decode($result);
        $query = sprintf("UPDATE payments_qr SET order_state='%s', operation_datetime='%s' WHERE order_id='%s'",
            $decoded->order_state, $decoded->rq_tm, $decoded->order_id);
        DB::query($query);
        return $result;
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
                        $query = sprintf("UPDATE payments_qr SET order_state='%s', operation_datetime='%s' WHERE order_id='%s'",
                            $args->orderState, $args->operationDateTime, $args->orderId);
                        DB::query($query);
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

    public static function cron($args){
        $query = "SELECT order_id, operation_datetime, order_number, TIMESTAMPDIFF(MINUTE, operation_datetime, NOW()) delta FROM payments_qr WHERE NOT order_state IN ('PAID', 'REVOKED')";
        $orders = DB::query($query);
        while ($order = $orders->fetch_object()) {
            $status = new stdClass();
            $status->partner_order_number = $order->order_number;
            $checkStatus = json_decode(self::status($status));
            switch ($checkStatus->order_state) {
                case 'PAID':
                    $notify = new stdClass();
                    $notify->operationType = 'PAY';
                    $notify->orderState = $checkStatus->order_state;
                    $notify->partnerOrderNumber = $order->order_number;
                    $notify->operationSum = $checkStatus->order_operation_params[0]->operation_sum;
                    $notify->operationDateTime = $checkStatus->order_operation_params[0]->operation_date_time;
                    $notify->operationId = $checkStatus->order_operation_params[0]->operation_id;
                    $notify->orderId = $checkStatus->order_id;
                    self::notify($notify);
                    break;

                default:
                    var_dump($order->delta);
                    if (intval($order->delta) > 30) {
                        $revocation = new stdClass();
                        $revocation->order_id = $order->order_id;
                        self::revocation($revocation);
                    }
                    break;
            }
        }
    }
}