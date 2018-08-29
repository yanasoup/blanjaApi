<?php

class Blanja
{
    const API_BLANJA_ENDPOINT_DEV = "http://103.228.248.23/api";
    const API_BLANJA_ENDPOINT_PROD = "http://api.blanja.com";
    const API_BLANJA_ENDPOINT = self::API_BLANJA_ENDPOINT_PROD;
    const API_BLANJA_APPID = "123";
    const API_BLANJA_APPKEY = "456";


    public function getAccessToken() {
        $path = '/v1/token';
        $reqParams = array('appid' => self::API_BLANJA_APPID,'appkey' => self::API_BLANJA_APPKEY);
        $curlOpt = array('CURLOPT_POST',false);
        $res = $this->curl_post(self::API_BLANJA_ENDPOINT,$reqParams,$curlOpt);

        return $res;
    }

    public function itemCreate($inputs) {
        $path = self::API_BLANJA_ENDPOINT.'/v1/item/create';
        $commonParams = $this->prepareCommonParams();
        $reqParams = array(
            'item' => $inputs['item'],
            'skus' => $inputs['skus'],
        );
    }

    public function businesscategory($inputs) {
        log_message('DEBUG', 'coba log error.');
        $path = self::API_BLANJA_ENDPOINT.'/v1/item/category';
        $commonParams = $this->prepareCommonParams();
        $res = $this->curl_post($path,$commonParams);
        return $res;
    }

    function prepareCommonParams() {
        $params = array(
            'appid' => Blanja::API_BLANJA_APPID,
            'access_token' => $this->getAccessToken(),
            'timestamp' => $this->getTimestamp()
        );

        return $params;
    }

    function cobalog() {
        Log4php::getLogger()->debug("Tes debug");
        echo "Done";
    }

    function _generatesign($inputs) {
        #$appKey = " ccb30b0150a4154gf313c5d0e61bca1a ";
        #$appId = " tokoContoh77";
        #$token = "3535F091E69B22F7C59A3AF6DE5A68FD";

        $appKey = self::API_BLANJA_APPKEY;
        $appId = self::API_BLANJA_APPID;

        $param['access_token'] = $inputs['access_token'];
        $param['appid'] = self::API_BLANJA_APPID;
        $param['appkey'] = self::API_BLANJA_APPKEY;
        $param['timestamp'] = $inputs['timestamp'];
        $param['orderNumber'] = $inputs['orderNumber'];
        $param['logisticsCompany'] = $inputs['logisticsCompany'];
        $param['invoiceNumber'] = $inputs['invoiceNumber'];
        $tempStr = '';
        uksort($param, 'strcasecmp');
        foreach($param as $key => $kv)
        {
            $tempStr .= $key . $kv;
        }
        $appKey = $param['appkey'];
        $sign = hash_hmac('sha1', $tempStr, $appKey . '&');
        return $sign;
    }
    function getTimestamp( $timezone="Asia/Jakarta" ) {static $milliseconds;
        if( !isset($milliseconds) )
        {
            if( is_null($timezone) || empty($timezone) )
            {
                $timezone = "Asia/Jakarta";
            }
            $mage_timezone = date_default_timezone_get();
            date_default_timezone_set( $timezone );
            $milliseconds = round(microtime(true) * 1000);
            date_default_timezone_set($mage_timezone);
        }
        return $milliseconds;
    }
    function curl_post($url, array $post = array(), array $options = array(), &$err = null, $countRetry = 0)
    {
        Log4php::getLogger()->debug("curl_post : ".json_encode($post));
        $defaults = array(
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_URL => $url,
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FORBID_REUSE => 0,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_POSTFIELDS => http_build_query($post)


        );

        $ch = curl_init();
        curl_setopt_array($ch, ($defaults + $options));
        $err = "";

        if (!$result = curl_exec($ch)) {
            $info = curl_getinfo($ch);
            Log4php::getLogger()->debug("curl_post error : ".json_encode($info));
            if (intval($info['http_code']) != 200) {
                $err .= "error:" . @curl_error($ch) . " -- " . $info['http_code'];
            }
        }

        Log4php::getLogger()->debug("curl_post result : ".json_encode($result));

        curl_close($ch);
        return $result;
    }
}