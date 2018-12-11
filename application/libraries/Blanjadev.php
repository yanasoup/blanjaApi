<?php

class Blanjadev
{
    //const API_BLANJA_ENDPOINT_DEV = "http://103.228.248.23/api";
    const API_BLANJA_ENDPOINT_DEV = "http://sellerapi.sandbox.metraplasa.co.id/api";
    const API_BLANJA_APPID_DEV = "upoin1s25744";
    const API_BLANJA_APPKEY_DEV = "b113404ce58d4c0b90f569069ebc300c";

    const API_BLANJA_ENDPOINT_PROD = "http://api.blanja.com";
    const API_BLANJA_APPID_PROD = "UPoin0s10871278";
    const API_BLANJA_APPKEY_PROD = "960ab515dd3e485188ca446a92843342";

    const API_BLANJA_ENDPOINT = self::API_BLANJA_ENDPOINT_DEV;
    const API_BLANJA_APPID = self::API_BLANJA_APPID_DEV;
    const API_BLANJA_APPKEY = self::API_BLANJA_APPKEY_DEV;

    public function businessCategory($inputs) {
        Log4php::getLogger()->debug("business category : ".json_encode($inputs));
        $path = self::API_BLANJA_ENDPOINT.'/v1/item/category';
        $commonParams = $this->prepareCommonParams();

        if (!empty($commonParams['access_token'])) {
            $commonParams['sign'] = $this->_generatesign($commonParams);
            $res = $this->curl_post($path,$commonParams);
            return $res;
        } else {
            return array();
        }
    }

    public function getAccessToken() {
        $path = self::API_BLANJA_ENDPOINT.'/v1/token';
        $reqParams = array('appid' => self::API_BLANJA_APPID,'appkey' => self::API_BLANJA_APPKEY);
        $res = $this->curl_get($path.'?'.http_build_query($reqParams));

        $xml = new SimpleXMLElement($res);
        $obj = json_decode(json_encode($xml));
        Log4php::getLogger()->debug("getAccessToken : ".json_encode($obj));
        $token = "";
        if (property_exists($obj,'code') && $obj->code == 'SUCCESS') {
            $token = $obj->result->access_token;
        }

        return $token;
    }

    public function getOrders($inputs) {
        $path = self::API_BLANJA_ENDPOINT.'/v1/orders';
        $commonParams = $this->prepareCommonParams();
        $reqParams = array_merge($commonParams,$inputs);
        if (!empty($commonParams['access_token'])) {
            $reqParams['sign'] = $this->_generatesign($reqParams);
            $res = $this->curl_get($path.'?'.http_build_query($reqParams));
            return $res;
        } else {
            return array();
        }
    }

    public function putItemOnshelf($inputs) {
        $path = self::API_BLANJA_ENDPOINT.'/v1/item/onShelf';
        $commonParams = $this->prepareCommonParams();
        $reqParams = array_merge($commonParams,$inputs);
        if (!empty($commonParams['access_token'])) {
            $reqParams['sign'] = $this->_generatesign($reqParams);
            $res = $this->curl_post($path,$reqParams);
            return $res;
        } else {
            return array();
        }

    }
    public function itemsOnshelfList($inputs) {
        $path = self::API_BLANJA_ENDPOINT.'/v1/item/onshelflist';
        $commonParams = $this->prepareCommonParams();
        $reqParams = array_merge($commonParams,$inputs);
        if (!empty($commonParams['access_token'])) {
            $reqParams['sign'] = $this->_generatesign($reqParams);
            $res = $this->curl_post($path,$reqParams);
            return $res;
        } else {
            return array();
        }

    }
    public function itemsInstorageList($inputs) {
        $path = self::API_BLANJA_ENDPOINT.'/v1/item/instorage';
        $commonParams = $this->prepareCommonParams();
        $reqParams = array_merge($commonParams,$inputs);
        if (!empty($commonParams['access_token'])) {
            $reqParams['sign'] = $this->_generatesign($reqParams);
            $res = $this->curl_post($path,$reqParams);
            return $res;
        } else {
            return array();
        }

    }

    public function itemCreate($inputs) {
        $path = self::API_BLANJA_ENDPOINT.'/v1/item/create';
        $commonParams = $this->prepareCommonParams();
        $reqParams = array_merge($commonParams,$inputs);
        Log4php::getLogger()->debug("itemCreate Params : ".json_encode($reqParams));
        if (!empty($reqParams['access_token'])) {
            $reqParams['sign'] = $this->_generatesign($reqParams);
            $res = $this->curl_post($path,$reqParams);
            return $res;
        } else {
            return array();
        }
    }

    public function orderDetails($inputs) {
        $path = self::API_BLANJA_ENDPOINT.'/v1/order';
        $commonParams = $this->prepareCommonParams();
        $reqParams = array_merge($commonParams,$inputs);
        Log4php::getLogger()->debug("itemCreate Params : ".json_encode($reqParams));
        if (!empty($reqParams['access_token'])) {
            $reqParams['sign'] = $this->_generatesign($reqParams);
            $res = $this->curl_post($path,$reqParams);
            return $res;
        } else {
            return array();
        }
    }

    public function sendSellerMessage($inputs) {
        $path = self::API_BLANJA_ENDPOINT.'/v1/sellerMessage';
        $commonParams = $this->prepareCommonParams();
        $reqParams = array_merge($commonParams,$inputs);
        Log4php::getLogger()->debug("sendSellerMessage Params : ".json_encode($reqParams));
        if (!empty($reqParams['access_token'])) {
            $reqParams['sign'] = $this->_generatesign($reqParams);
            $res = $this->curl_post($path,$reqParams);
            return $res;
        } else {
            return array();
        }
    }
    public function shipOrder($inputs) {
        $path = self::API_BLANJA_ENDPOINT.'/v1/ship';
        $commonParams = $this->prepareCommonParams();
        $reqParams = array_merge($commonParams,$inputs);
        Log4php::getLogger()->debug("shipOrder Params : ".json_encode($reqParams));
        if (!empty($reqParams['access_token'])) {
            $reqParams['sign'] = $this->_generatesign($reqParams);
            Log4php::getLogger()->debug("shipOrder Params 2 : ".json_encode($reqParams));
            $res = $this->curl_post($path,$reqParams);
            return $res;
        } else {
            return array();
        }
    }
    public function itemDetail($inputs) {
        $path = self::API_BLANJA_ENDPOINT.'/v1/item/detail';
        $commonParams = $this->prepareCommonParams();
        $reqParams = array_merge($commonParams,$inputs);
        Log4php::getLogger()->debug("itemCreate Params : ".json_encode($reqParams));
        if (!empty($reqParams['access_token'])) {
            $reqParams['sign'] = $this->_generatesign($reqParams);
            $res = $this->curl_post($path,$reqParams);
            return $res;
        } else {
            return array();
        }
    }
    public function itemDelete($inputs) {
        $path = self::API_BLANJA_ENDPOINT.'/v1/item/delete';
        $commonParams = $this->prepareCommonParams();
        $reqParams = array_merge($commonParams,$inputs);
        Log4php::getLogger()->debug("itemCreate Params : ".json_encode($reqParams));
        if (!empty($reqParams['access_token'])) {
            $reqParams['sign'] = $this->_generatesign($reqParams);
            $res = $this->curl_post($path,$reqParams);
            return $res;
        } else {
            return array();
        }
    }

    public function updatePriceQuantity($inputs) {
        $path = self::API_BLANJA_ENDPOINT.'/v1/item/updatePriceQuantity';
        $commonParams = $this->prepareCommonParams();
        $reqParams = array_merge($commonParams,$inputs);
        Log4php::getLogger()->debug("updatePriceQuantity Params : ".json_encode($reqParams));
        if (!empty($reqParams['access_token'])) {
            $reqParams['sign'] = $this->_generatesign($reqParams);
            $res = $this->curl_post($path,$reqParams);
            return $res;
        } else {
            return array();
        }
    }

    function prepareCommonParams() {
        $params = array(
            'appid' => self::API_BLANJA_APPID,
            'access_token' => $this->getAccessToken(),
            'timestamp' => $this->getTimestamp()
        );
        Log4php::getLogger()->debug("Common Params : ".json_encode($params));

        $loop = 0;
        while (empty($params['access_token'])) {
            $params['access_token'] = $this->getAccessToken();
            if ($loop > 3) {
                break;
            }
            $loop++;
        }

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
        /*$param['access_token'] = $inputs['access_token'];
        $param['appid'] = self::API_BLANJA_APPID;
        $param['appkey'] = self::API_BLANJA_APPKEY;
        $param['timestamp'] = $inputs['timestamp'];
        $param['orderNumber'] = $inputs['orderNumber'];
        $param['logisticsCompany'] = $inputs['logisticsCompany'];
        $param['invoiceNumber'] = $inputs['invoiceNumber'];*/
        $tempStr = '';
        uksort($inputs, 'strcasecmp');
        foreach($inputs as $key => $kv)
        {
            $tempStr .= $key . $kv;
        }
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
        //Log4php::getLogger()->debug("curl_post : ".json_encode($url));
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

        #Log4php::getLogger()->debug("params : ".json_encode(($defaults + $options)));

        $ch = curl_init();
        curl_setopt_array($ch, ($defaults + $options));
        $err = "";

        if (!$result = curl_exec($ch)) {
            $info = curl_getinfo($ch);
            Log4php::getLogger()->debug("curl_post error : ".json_encode($info));
            if (intval($info['http_code']) != 200) {
                $err .= "error:" . @curl_error($ch) . " -- " . $info['http_code'];
                Log4php::getLogger()->debug("curl_post result : ".json_encode($result));
            }
        }

        #Log4php::getLogger()->debug("curl_post result : ".json_encode($result));

        curl_close($ch);
        return $result;
    }
    function curl_get($url, array $options = array(), &$err = null, $countRetry = 0)
    {
        #Log4php::getLogger()->debug("curl_get : ".json_encode($url));
        $defaults = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 45,
        );

        $ch = curl_init();
        curl_setopt_array($ch, ($defaults + $options));
        $err = "";

        if (!$result = curl_exec($ch)) {
            $info = curl_getinfo($ch);
            Log4php::getLogger()->debug("curl_get error : ".json_encode($info));
            if (intval($info['http_code']) != 200) {
                $err .= "error:" . @curl_error($ch) . " -- " . $info['http_code'];
                Log4php::getLogger()->debug("curl_get result : ".json_encode($result));
            }
        }

        #Log4php::getLogger()->debug("curl_get result : ".json_encode($result));


        curl_close($ch);
        return $result;
    }
}