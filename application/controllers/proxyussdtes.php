<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
#error_reporting(E_ALL);
class Proxyussdtes extends CI_Controller
{
    function __construct() {
        ini_set("display_errors",false);
        parent::__construct();
        $this->load->library('logussdtes','logger');
        $this->logger_id = strtotime(date('YmdHis'));
        $this->logger = Logussdtes::getLogger();
    }

    public function ping() {
        $requester = $this->input->ip_address();
        $inputs =  file_get_contents("php://input");
        $this->logger->debug($this->logger_id." - REQUEST FROM IP $requester : \n".trim(preg_replace('/\s+/', '', $inputs)));
        $response['Result'] = true;
        $response['Message'] = "Halo from upoint";
        echo json_encode($response);
    }

    public function index() {
        $requester = $this->input->ip_address();
        $this->logger->debug($this->logger_id." - REQUEST FROM IP $requester");
        $url = "https://upoint.co.id/api_ussd/gettoken";
        $trxid = $this->input->post('trx_id');
        $price = $this->input->post('amount');
        $koit = $this->input->post('item');
        $res = array("success" => false,'msg' => " Curl to TSEL ERROR");

        $arr = array(
            'trx_id' => $trxid,
            'amount' => $price,
            'item' => $koit,
            'secret_token' => 'ab327947447a3a81abeadbd7c0d55b1881cdb1ac',
            'callback_url' => 'https://upoint.co.id/api_dev/callback',
            'merchant' => 'DEV_TEST',
        );
        $options = array(
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_URL => $url,
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FORBID_REUSE => 0,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_POSTFIELDS => http_build_query($arr)
        );



        try {
            $ch = curl_init($url);
            curl_setopt_array($ch,$options);
            $json = curl_exec($ch);
            curl_close($ch);
            $res = json_decode($json);

            $this->logger->debug($this->logger_id . " Curl to TSEL result : " . json_encode($res));

            /*$redirect = "http://auth.telkomsel.com/transaksi/konfirmasi?token=".$res->token;
            $response = new \Phalcon\Http\Response();
            return $response->redirect($redirect, true);*/

        } catch (Exception $e) {
            $this->logger->debug($this->logger_id . " Curl to TSEL ERROR : " . $e->getMessage());
            $res = array("success" => false,'msg' => " Curl to TSEL ERROR : " . $e->getMessage());

        }

        echo json_encode($res);

    }
}