<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
#error_reporting(E_ALL);
class Cekorder extends CI_Controller {

    function __construct() {
        ini_set("display_errors",false);
        parent::__construct();
        $this->load->library('logcekorderblanja','logger');
        $this->logId = strtotime(date('YmdHis'));
    }


    public function itemCreate() {
        $path = '/v1/item/create';

    }

    function _generatesign($inputs) {
        #$appKey = " ccb30b0150a4154gf313c5d0e61bca1a ";
        #$appId = " tokoContoh77";
        #$token = "3535F091E69B22F7C59A3AF6DE5A68FD";
        $appKey = " ccb30b0150a4154gf313c5d0e61bca1a ";
        $appId = " tokoContoh77";
        $token = "3535F091E69B22F7C59A3AF6DE5A68FD";

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



}