<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
#error_reporting(E_ALL);
class Pubgtes extends CI_Controller
{
    function __construct() {
        ini_set("display_errors",E_ALL);
        parent::__construct();
        $this->load->library('logpubgtes','logpubg');
        $this->load->helper(array('yana','string'));
        $this->logId = strtotime(date('YmdHis'));
    }

    function index() {

        Logpubgtes::getLogger()->debug(sprintf("======= request start ======="));
        #$post = $this->input->post();
        $post = array();
        $post['msisdn'] = $this->input->post('msisdn',1);
        $post['sms'] = $this->input->post('message',1);
        $post['trx_id'] = $this->input->post('trx_id',1);
        Logpubgtes::getLogger()->debug(sprintf("request : %s",json_encode($post)));


        $post['tid'] = '45';
        #$post['tid'] = '141';
        #$url = "http://192.168.1.2/api_direct_nbp/submit";
        $url = "https://upoint.co.id/api_direct_nbp/submit";
        $callbackRes = $this->curl_post($url,$post);
        Logpubgtes::getLogger()->debug(sprintf("callback response %s",json_encode($callbackRes)));

        Logpubgtes::getLogger()->debug(sprintf("======= request end ======="));


    }

    function curl_post($url, array $post = array(), array $options = array(), &$err = null, $countRetry = 0)
    {
        Logpubgtes::getLogger()->debug("curl_post : ".json_encode($url));
        Logpubgtes::getLogger()->debug("curl_post : ".json_encode($post));
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
            Logpubgtes::getLogger()->debug("curl_post error : ".json_encode($info));
            if (intval($info['http_code']) != 200) {
                $err .= "error:" . @curl_error($ch) . " -- " . $info['http_code'];
            }
        }

        Logpubgtes::getLogger()->debug("curl_post result : ".json_encode($result));

        curl_close($ch);
        return $result;
    }

}