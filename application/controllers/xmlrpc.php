<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
#error_reporting(E_ALL);
class Xmlrpc extends CI_Controller {

    private $whiteListIPs = array(
        '222.124.12.100', // IP ALFAMART PROD
        '118.96.245.193',
        /* '118.97.118.98', // IP ALFAMART DEV
        '118.97.188.95','222.124.159.18','36.72.111.16','118.97.188.99',
        '118.96.223.27','36.79.173.96','125.160.228.164','36.79.173.96',
        '180.245.230.149','180.253.254.51'*/
    );
    #private $requiredRequestKeys = array('ProcessingCode','ItemID','TransactionID','TransactionDateTime','Amount','TerminalID','PIN','CardNumber','MerchantType','MerchantID','Destination');
    private $requiredRequestKeys = array('ProcessingCode','ItemID','TransactionID','TransactionDateTime','Amount','TerminalID','PIN','CardNumber','MerchantType','MerchantID','Destination');
    /*private $requiredtopupInquiryParams = array('ProcessingCode','TransactionID','TransactionDateTime','RefTransactionID',
        'RefTransactionDateTime','RefDestination','RefAmount','RefReferenceNo','RefCardNumber','PIN',
        'MerchantType','TerminalID','MerchantID','Message');*/
    private $requiredtopupInquiryParams = array('ProcessingCode','TransactionDateTime','RefTransactionID',
        'RefTransactionDateTime','RefDestination','RefAmount','RefReferenceNo','RefCardNumber','PIN',
        'MerchantType','MerchantID');
    public $logId = 0;
    const H2H_VOUCHER_REQUEST_URL = "http://192.168.1.6/voucherFeeder/host_to_host/request";
    const H2H_VOUCHER_CONFIRM_URL = "http://192.168.1.6/voucherFeeder/host_to_host/confirm";
    const VOUCHER_REQUEST_URL = "https://evaplatform.net:12801/mvp/dist/init";
    const VOUCHER_CONFIRM_URL = "https://evaplatform.net:12801/mvp/dist/confirm";
    const ALFAMART_STEAM_PARTNER_ID = 31;
    const ALFAMART_STEAM_SECRET_TOKEN = 'c25a425f353fb7a4b5aa1f0b57b9796853a5d213';
    const SUBDEALER_LOGIN = "alfamvp1:alfamvp123@";
    const ALFA_LOGIN = "alfamvp:8j4%D3";
    const XMLRPC_PREFIX = 'xmlrpc';
    const SUBDEALER_PIN = '8j46D3';
    const IS_DEV = 1;

    /*public $valid_items = array(
      'WIFIID' => array(5000,10000,25000),
    );*/

    public $valid_items = array(
        '10S20005' => 5000,
        '10S20007' => 20000,
        '10S20008' => 50000,
        'ST6K' => 8000,
        'ST60K' => 80000,
        'ST120K' => 160000,
        'ST400K' => 530000,
        'UGARENA0' => 1,
    );

    public $voucherWifiId = array(
        '10S20005',
        '10S20007',
        '10S20008'
    );

    public $voucherSteam = array(
        'ST6K',
        'ST60K',
        'ST120K',
        'ST400K','UGARENA0'
    );


    function __construct() {
        ini_set("display_errors",false);
        parent::__construct();
        $this->load->library('log4php','logger');
        $this->load->library('xmlrpc');
        $this->load->library('xmlrpcs');
        $this->load->helper('string');
        $this->load->library('encrypt');
        $this->logId = strtotime(date('YmdHis'));
    }

    public function index()
    {
        echo "Hi there"; die;
        #echo '<pre>'.print_r($_SERVER,1).'</pre>'; die;
        if ($this->input->server('REQUEST_METHOD') != 'POST') {
            echo "Only Accept POST"; die;
        }
        #die("AlfaMVP");

        #echo '<pre>'.print_out($_SERVER,1).'</pre>';die;

        $requester = $this->input->ip_address();
        $inputs =  file_get_contents("php://input");
        Log4php::getLogger()->debug("=================================================================================================");
        Log4php::getLogger()->debug($this->logId." REQUEST FROM IP $requester");
        Log4php::getLogger()->debug($this->logId." REQUEST URI : ".$_SERVER['REQUEST_URI']);
        Log4php::getLogger()->debug($this->logId." USER : ".$this->input->server('REMOTE_USER'));
        Log4php::getLogger()->debug($this->logId." HTTP_USER_AGENT : ".$_SERVER['HTTP_USER_AGENT']);
        Log4php::getLogger()->debug($this->logId." SERVER_PORT : ".$_SERVER['SERVER_PORT']);
        Log4php::getLogger()->debug($this->logId." REMOTE_PORT : ".$_SERVER['REMOTE_PORT']);

        $config['functions']['tokenRequest'] = array('function' => $this::XMLRPC_PREFIX.'.tokenRequest');
        $config['functions']['topupInquiry'] = array('function' => $this::XMLRPC_PREFIX.'.topupInquiry');
        $config['functions']['echo'] = array('function' => 'xmlrpc.hello');
        $config['object'] = $this;

        $this->xmlrpcs->initialize($config);
        #Log4php::getLogger()->debug("END OF REQUEST");
        #Log4php::getLogger()->debug("=================================================================================================");
        $this->xmlrpcs->serve();
    }

    function tokenRequest($request)
    {
        $requester = $this->input->ip_address();
        $inputs =  file_get_contents("php://input");
        #Log4php::getLogger()->debug($this->logId." - REQUEST FROM IP $requester : \n".trim(preg_replace('/\s+/', '', $inputs)));
        $requester = $this->input->ip_address();
        $args = $request->output_parameters();
        Log4php::getLogger()->debug($this->logId." - tokenRequest PARAMS : ".json_encode($args));
        $datetime = new DateTime();
        $time = $datetime->format(DateTime::ISO8601);
        #$refno = random_string('alnum',16);
        $refno = array_key_exists('TransactionID',$args[0]) ? $args[0]['TransactionID'] : "";
        $itemCode = array_key_exists('ItemID',$args[0]) ? $args[0]['ItemID'] : "";
        #$itemCode = "SKYPE1USD";
        $response = array(
            array(
                "ProcessingCode" => array_key_exists('ProcessingCode',$args[0]) ? trim($args[0]['ProcessingCode']) : "",
                "CardNumber" => array_key_exists('CardNumber',$args[0]) ? trim($args[0]['CardNumber']) : "",
                "TransactionDateTime" => array_key_exists('TransactionDateTime',$args[0]) ? array(@$args[0]['TransactionDateTime'],'dateTime.iso8601') : "",
                "ItemID" => $itemCode,
                "Amount" => array_key_exists('Amount',$args[0]) ? $args[0]['Amount'] : "",
                "TransactionID" => array_key_exists('TransactionID',$args[0]) ? $args[0]['TransactionID'] : "",
                "Destination" => array_key_exists('Destination',$args[0]) ? $args[0]['Destination'] : "",
                "ReferenceNo" => "",
                "ResponseCode" => "00",
                "TerminalID" => array_key_exists('TerminalID',$args[0]) ? $args[0]['TerminalID'] : "",
                "TokenSN" => "",
                "TerminalBalance" => 0,
                "NetworkIID" => array_key_exists('NetworkIID',$args[0]) ? $args[0]['NetworkIID'] : "",
                "Message" => "",
            ),
            'struct'
        );

        if (!$this->_validateIp()) {
            Log4php::getLogger()->debug($this->logId." - IP ADDRESS ".$requester." NOT ALLOWED");
            $response[0]['ResponseCode'] = '34';
            $response[0]['Message'] = 'ip address '.$requester.' not allowed';
            return $this->xmlrpc->send_response($response);
            exit;
        }

        if ($args[0]['PIN'] != self::SUBDEALER_PIN) {
            Log4php::getLogger()->debug($this->logId." - Login ERROR : ");
            $response[0]['ResponseCode'] = '40';
            $response[0]['Message'] = "Login error";
            return $this->xmlrpc->send_response($response);
            exit;
        }

        $paramNotExist = $this->_validateRequestParams($inputs,$this->requiredRequestKeys);
        if (sizeof($paramNotExist) > 0) {
            Log4php::getLogger()->debug($this->logId." - REQUIRED PARAMETER DOESN'T EXIST : ".json_encode(array_values($paramNotExist)));
            $response[0]['ResponseCode'] = '10';
            $response[0]['Message'] = "missing required parameter(s) - [".implode(',',array_values($paramNotExist))."]";
            return $this->xmlrpc->send_response($response);
            exit;
        }


        if (!in_array( $args[0]['ItemID'],array_keys($this->valid_items) ) ) {
            Log4php::getLogger()->debug($this->logId." - INVALID ITEM ID : ".$args[0]['ItemID']);
            $response[0]['ResponseCode'] = '13';
            $response[0]['Message'] = "invalid item id";
            return $this->xmlrpc->send_response($response);
            exit;
        }

        if (!intval($args[0]['Amount'])) {
            Log4php::getLogger()->debug($this->logId." - INVALID AMOUNT : ".$args[0]['Amount']);
            $response[0]['ResponseCode'] = '13';
            $response[0]['Message'] = "invalid amount";
            return $this->xmlrpc->send_response($response);
            exit;
        }

        if ( intval($args[0]['Amount']) != intval($this->valid_items[$args[0]['ItemID']]) ) {
            Log4php::getLogger()->debug($this->logId." - INVALID AMOUNT : ".$args[0]['Amount']);
            $response[0]['ResponseCode'] = '13';
            $response[0]['Message'] = "invalid denom";
            return $this->xmlrpc->send_response($response);
            exit;
        }


        /*if ($response[0]['Destination'] == '085221103456') {
            $response[0]['ResponseCode'] = '41';
            $response[0]['ReferenceNo'] = '';
            $response[0]['TokenSN'] = '';
            $response[0]['Message'] = 'Payment failed.';
            $response[1] = 'struct';
            Log4php::getLogger()->debug($this->logId." - Payment FAILED");
            return $this->xmlrpc->send_response($response);
        } else if ($response[0]['Destination'] == '085221103457') {
            sleep(40);
            $response[0]['ResponseCode'] = '68';
            $response[0]['ReferenceNo'] = '';
            $response[0]['TokenSN'] = '';
            $response[0]['Message'] = 'Transaction timeout.';
            $response[1] = 'struct';
            Log4php::getLogger()->debug($this->logId." - Transaction timeout");
            return $this->xmlrpc->send_response($response);
        }*/
        #$itemCode = "UGARENA0";
        $params = array('refno' => $refno, 'itemcode' => $itemCode);
        $resGet = $this->getVoucher($params);
        Log4php::getLogger()->debug($this->logId." - INIT REQ RESPONSE : ".json_encode($resGet));


        if ($resGet) {
            #Log4php::getLogger()->debug($this->logId." - getVoucher response : ".json_encode($resGet));
            if ( intval($resGet->resultCode) == 0) {
                $confirmParams = array('trxId' => $resGet->trxId, 'requestCode' => 1000,'itemcode' => $resGet->itemCode,'refno' => $resGet->refNo);
                $resConf = $this->confirmVoucher($confirmParams);
                if ($resConf->resultCode == 0) {
                    $response[0]['ResponseCode'] = '00';
                    #$response[0]['ReferenceNo'] = $refno;
                    $response[0]['ReferenceNo'] = $resGet->trxId;
                    if (in_array($resGet->itemCode,$this->voucherSteam)) {
                        #$response[0]['TokenSN'] = "Username: ".$resConf->vsn.PHP_EOL."Password: ".$resConf->hrn;
                        $response[0]['TokenSN'] = $resConf->vsn;
                        $response[0]['Message'] .= " Utk keluhan/gangguan hub. UPoint CS : 021-79187250 atau Email ke support@metranet.co.id";
                    } else {
                        #$response[0]['TokenSN'] = "Username: ".$resConf->vsn.PHP_EOL."Password: ".$resConf->hrn;
                        $response[0]['TokenSN'] = "Username: ".$resConf->vsn."-".PHP_EOL."Password: ".$resConf->hrn;
                        $response[0]['Message'] .= " Utk keluhan/gangguan hub. UPoint CS : 147 / 021-79187250";
                    }

                    Log4php::getLogger()->debug($this->logId." - CONFIRM SUCCESS : ".json_encode($response));
                } else {
                    Log4php::getLogger()->debug($this->logId." - CONFIRM FAILED, RESPONSE : ".json_encode($resConf));
                    $response[0]['ResponseCode'] = '91';
                    $response[0]['ReferenceNo'] = '';
                    $response[0]['TokenSN'] = '';
                    $response[0]['Message'] = 'internal system error';
                }
            } else {
                if (intval($resGet->resultCode) == 2001) {
                    $response[0]['ResponseCode'] = '70';
                    $response[0]['ReferenceNo'] = '';
                    $response[0]['TokenSN'] = '';
                    $response[0]['Message'] = 'Voucher out of stock';

                }
                if (intval($resGet->resultCode) == 1021) {
                    $response[0]['ResponseCode'] = '50';
                    $response[0]['ReferenceNo'] = '';
                    $response[0]['TokenSN'] = '';
                    $response[0]['Message'] = 'refNo exists';

                }
                if (intval($resGet->resultCode) == 3101) {
                    $response[0]['ResponseCode'] = '68';
                    $response[0]['ReferenceNo'] = '';
                    $response[0]['TokenSN'] = '';
                    $response[0]['Message'] = 'Transaction Expired';
                }
                if (intval($resGet->resultCode) == -1) {
                    $response[0]['ResponseCode'] = '99';
                    $response[0]['ReferenceNo'] = '';
                    $response[0]['TokenSN'] = '';
                    $response[0]['Message'] = 'Undefined Error Code';
                }
                Log4php::getLogger()->debug($this->logId." - INIT REQUEST ERROR , RESPONSE : ".json_encode($resGet));
            }
        } else {
            Log4php::getLogger()->debug($this->logId." - INIT REQUEST FAILED, RESPONSE : ".json_encode($resGet));
            $response[0]['ResponseCode'] = '91';
            $response[0]['ReferenceNo'] = '';
            $response[0]['TokenSN'] = '';
            $response[0]['Message'] = 'internal system error';
        }

        $response[1] = 'struct';
        Log4php::getLogger()->debug($this->logId." - FINAL RESPONSE ".json_encode($response));
        Log4php::getLogger()->debug($this->logId." - END OF REQUEST");
        Log4php::getLogger()->debug("=================================================================================================");

        return $this->xmlrpc->send_response($response);
    }
    function topupInquiry($request)
    {
        $requester = $this->input->ip_address();
        $inputs =  file_get_contents("php://input");
        Log4php::getLogger()->debug($this->logId." - REQUEST FROM CLIENT IP $requester : \n".trim(preg_replace('/\s+/', '', $inputs)));
        $requester = $this->input->ip_address();
        $args = $request->output_parameters();
        Log4php::getLogger()->debug($this->logId." - topupInquiry PARAMS : ".json_encode($args));
        $datetime = new DateTime();
        $time = $datetime->format(DateTime::ISO8601);
        $refno = random_string('alnum',16);
        $response = array(
            array(
                "ProcessingCode" => array_key_exists('ProcessingCode',$args[0]) ? trim($args[0]['ProcessingCode']) : "",
                "TransactionID" => array_key_exists('TransactionID',$args[0]) ? $args[0]['TransactionID'] : "",
                "TransactionDateTime" => array_key_exists('TransactionDateTime',$args[0]) ? array(@$args[0]['TransactionDateTime'],'dateTime.iso8601') : "",
                "RefTransactionID" => array_key_exists('RefTransactionID',$args[0]) ? array(@$args[0]['RefTransactionID'],'dateTime.iso8601') : "",
                "RefTransactionDateTime" => array_key_exists('RefTransactionDateTime',$args[0]) ? array(@$args[0]['RefTransactionDateTime'],'dateTime.iso8601') : "",
                "RefDestination" => array_key_exists('RefDestination',$args[0]) ? $args[0]['RefDestination'] : "",
                "RefAmount" => array_key_exists('RefAmount',$args[0]) ? trim($args[0]['RefAmount']) : "",
                "RefReferenceNo" => array_key_exists('RefReferenceNo',$args[0]) ? $args[0]['RefReferenceNo'] : "",
                "RefCardNumber" => array_key_exists('RefCardNumber',$args[0]) ? $args[0]['RefCardNumber'] : "",
                "RefStatus" => array_key_exists('RefStatus',$args[0]) ? $args[0]['RefStatus'] : "",
                "TokenSN" => "",
                "TerminalID" => array_key_exists('TerminalID',$args[0]) ? $args[0]['TerminalID'] : "",
                "ResponseCode" => "00",
                "Message" => array_key_exists('Message',$args[0]) ? $args[0]['Message'] : "",
            ),
            'struct'
        );

        if (!$this->_validateIp()) {
            Log4php::getLogger()->debug($this->logId." - IP ADDRESS ".$requester." NOT ALLOWED");
            $response[0]['ResponseCode'] = '34';
            $response[0]['Message'] = 'ip address not allowed';
            return $this->xmlrpc->send_response($response);
            exit;
        }

        $paramNotExist = $this->_validateRequestParams($inputs,$this->requiredtopupInquiryParams);
        if (sizeof($paramNotExist) > 0) {
            Log4php::getLogger()->debug($this->logId." - REQUIRED PARAMETER DOESN'T EXIST : ".json_encode(array_values($paramNotExist)));
            $response[0]['ResponseCode'] = '10';
            $response[0]['Message'] = "missing required parameter(s) - [".implode(',',array_values($paramNotExist))."]";
            return $this->xmlrpc->send_response($response);
            exit;
        }

        $response[0]['ResponseCode'] = '00';
        $response[0]['TokenSN'] = random_string('alnum',16);
        $response[0]['Message'] .= " Untuk keluhan/gangguan hubungi UPoint CS : 021-79187250. Terima Kasih";
        $response[1] = 'struct';
        Log4php::getLogger()->debug($this->logId." - RESPONSE : ".$this->encrypt->encode(json_encode($response),'n0n33dt0kn0w'));
        Log4php::getLogger()->debug($this->logId." - END OF REQUEST");
        Log4php::getLogger()->debug("=================================================================================================");

        return $this->xmlrpc->send_response($response);
    }

    private function getVoucher($params){
        $data = array(
            "refNo"=>$params['refno'],
            "itemCode"=>$params['itemcode']
        );
        $opt = array();
        $err = null;
        $res = null;
        $info = array();
        if (in_array($params['itemcode'],$this->voucherWifiId)) {
            Log4php::getLogger()->debug($this->logId." evaplatform getVoucher request : ".json_encode($data));
            $resV = $this->curl_post($this::VOUCHER_REQUEST_URL, $data, $opt, $err);
            Log4php::getLogger()->debug($this->logId." evaplatform getVoucher response : ".$resV);
            if ($objresV = @json_decode($resV)){
                if (isset($objresV->resultCode)){
                    $v = new stdClass();
                    $v->status = true;
                    $v->resultCode = $objresV->resultCode;
                    $v->refNo = $objresV->refNo;
                    $v->itemCode = $objresV->itemCode;
                    $v->trxId = $objresV->trxId;
                    $v->trxExpire = $objresV->trxExpire;
                    return $v;
                } else {
                    return $objresV;
                }
                return false;
            }
        } else {
            $data = array(
                'partner_id' => self::ALFAMART_STEAM_PARTNER_ID,
                'ref_no' => $params['refno'],
                'item_code' => $params['itemcode'],
                'timestamp' => 30,
                'secret_token' => self::ALFAMART_STEAM_SECRET_TOKEN,
            );

            $data['signature'] = md5($data['partner_id'] . $data['ref_no'] . $data['item_code'] . '30' . $data['secret_token']);
            Log4php::getLogger()->debug($this->logId." feeder h2h getVoucher request : ".json_encode($data));
            $resV = $this->curl_post($this::H2H_VOUCHER_REQUEST_URL, $data, $opt, $err);
            Log4php::getLogger()->debug($this->logId." feeder h2h getVoucher response : ".$resV);

            if ($objresV = @json_decode($resV)){
                if (isset($objresV->status) && $objresV->status == '1'){
                    $v = new stdClass();
                    $v->status = true;
                    $v->resultCode = 0;
                    $v->refNo = $objresV->ref_no;
                    $v->itemCode = $data['item_code'];
                    $v->trxId = $objresV->trx_id;
                    $v->trxExpire = date("Y-m-d H:i:s", strtotime( '+1 minute' ) );
                    return $v;
                } else {
                    $v = new stdClass();
                    $v->status = false;
                    $resultCode = -1;
                    if ($objresV->error_code=='E004') {
                        $resultCode = 2001;
                    }
                    $v->resultCode = $resultCode;
                    $v->trxId = $objresV->trx_id;
                    return $v;

                    #return $objresV;
                }
                return false;
            }

        }

    }
    private function confirmVoucher($params){
        #Log4php::getLogger()->debug($this->logId." executing confirmVoucher. ");
        $data = array(
            "trxId"=> $params['trxId'],
            //"trxId"=> random_string('numeric',16),
            "requestCode"=>$params['requestCode']
        );
        $opt = array();
        $err = null;
        $res = null;
        $info = array();
        if (in_array($params['itemcode'],$this->voucherWifiId)) {
            $resV = $this->curl_post($this::VOUCHER_CONFIRM_URL, $data, $opt, $err);
            Log4php::getLogger()->debug($this->logId." evaplatform confirmVoucher response : ".$resV);
            return json_decode($resV);
        } else {
            $data = array(
                'partner_id' => self::ALFAMART_STEAM_PARTNER_ID,
                'trx_id' => $params['trxId'],
                'ref_no' => $params['refno'],
                'status' => 1,
            );
            $data['signature'] = md5($data['partner_id'] . $data['trx_id'] . $data['ref_no'] . $data['status'] . self::ALFAMART_STEAM_SECRET_TOKEN);
            Log4php::getLogger()->debug($this->logId." feeder h2h confirmVoucher requests : ".json_encode($data));
            $resV = $this->curl_post($this::H2H_VOUCHER_CONFIRM_URL, $data, $opt, $err);
            Log4php::getLogger()->debug($this->logId." feeder h2h confirmVoucher responses : ".json_encode($resV));


            if ($objresV = @json_decode($resV)){
                if (isset($objresV->status)){
                    $dummyResV = new stdClass();
                    $dummyResV->resultCode = 0;
                    $dummyResV->trxId = $objresV->trx_id;
                    //$dummyResV->vsn = "KodeVcr=DUMMY-".random_string('numeric',16);
                    $dummyResV->vsn = $objresV->item[0]->value;
                    $dummyResV->hrn = "";
                    $dummyResV->expDate = date("Y-m-d H:i:s", strtotime( '+1 month' ) );
                    $dummyResV->itemCode = $params['itemcode'];
                    Log4php::getLogger()->debug($this->logId." alfamart steam confirmVoucher response : ".json_encode($dummyResV));
                    return $dummyResV;
                } else {
                    return $objresV;
                }
            }
        }



    }

    function _validateIp() {
        $requester = $this->input->ip_address();
        if (!in_array($requester,$this->whiteListIPs)) {
            return false;
        } else {
            return true;
        }
    }

    function _validateRequestParams($XMLRequest,$paramList) {
        #Log4php::getLogger()->trace($this->logId." - validating request params...");
        $paramNotExist = array();
        $XMLString = $XMLRequest;
        if ($xmlobj = $this->_parseXml($XMLString)) {
            if($paramKeys = $this->_extractKeys($xmlobj)) {
                $paramNotExist = array_diff($paramList, $paramKeys);
            }
        }
        return $paramNotExist;
    }

    function _parseXml($XMLString = "") {
        $xmlobj = false;
        try {
            #Log4php::getLogger()->trace($this->logId." - parsing xml...");
            $xmlobj=simplexml_load_string($XMLString);
        } catch (Exception $e) {
            Log4php::getLogger()->error($this->logId." - ERROR PARSING XMLREQUEST : ".$e->getMessage()."\n");
        }

        return $xmlobj;
    }

    function _extractKeys($xmlObj) {
        #Log4php::getLogger()->trace($this->logId." - extracting keys from xmlObj...");
        $paramkeys = false;
        try {
            $arr = (array)$xmlObj->params->param->value->struct;
            #Log4php::getLogger()->info("arr :".json_encode($arr)."\n");
            $params = $arr['member'];

            $paramkeys = array();
            foreach($params as $k => $v) {
                $paramkeys[] = (string)$v->name;
            }

        } catch (Exception $e) {
            Log4php::getLogger()->error($this->logId." - ERROR EXTRACTING XMLOBJ : ".$e->getMessage()."\n");
        }

        return $paramkeys;

    }


    function curl_post($url, array $post = array(), array $options = array(), &$err = null, $countRetry = 0) {
        if ($countRetry > 0) $this->logger->log($this->loggerid . " " . "RETRY : $countRetry");

        $defaults = array(
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_URL => $url,
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FORBID_REUSE => 0,
            CURLOPT_USERPWD => $this::SUBDEALER_LOGIN,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POSTFIELDS => http_build_query($post)
        );
        if (strpos("https://", $url) === 0){
            Log4php::getLogger()->debug($this->logId." SSL");
            $defaults[CURLOPT_SSL_VERIFYHOST] = 0;
            $defaults[CURLOPT_SSL_VERIFYPEER] = 0;
        }
        $ch = curl_init();
        curl_setopt_array($ch, ($options + $defaults));
        $err = "";

        if( ! $result = curl_exec($ch))
        {
            $info = curl_getinfo($ch);
            if (intval($info['http_code']) != 200){
                $err .= "error:" . @curl_error($ch) . " -- " . $info['http_code'];
                Log4php::getLogger()->debug($this->logId." callback_error $err");
                Log4php::getLogger()->debug($this->logId." callback_error info".json_eoncode($info));

            }
        }
        $info = curl_getinfo($ch);
        curl_close($ch);
        if ($err != "" && intval($info['http_code']) != 200 && $countRetry < 5){
            $countRetry++;
            sleep(2);
            $result = $this->curl_post($url, $post, $options, $err, $countRetry);
        }
        return $result;
    }
    /**
     * Send a GET requst using cURL
     * @param string $url to request
     * @param array $get values to send
     * @param array $options for cURL
     * @return string
     */
    function curl_get($url, array $get = NULL, array $options = array(), &$err = null, $countRetry = 0) {
        if ($countRetry > 0) $this->logger->log($this->loggerid . " " . "RETRY : $countRetry");
        $http_query = "" . @http_build_query($get);
        $defaults = array(
            CURLOPT_URL => $url. (strpos($url, '?') === FALSE ? '?' : ''). $http_query,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT => 45
        );

        if (strpos("https://", $url) === 0){
            Log4php::getLogger()->info($this->logId." SSL");
            $defaults[CURLOPT_SSL_VERIFYHOST] = 0;
            $defaults[CURLOPT_SSL_VERIFYPEER] = 0;
        }
        $ch = curl_init();
        curl_setopt_array($ch, ($options + $defaults));
        $err = "";

        if( ! $result = curl_exec($ch))
        {
            $info = curl_getinfo($ch);
            if (intval($info['http_code']) != 200){
                $err .= "error:" . @curl_error($ch) . " -- " . $info['http_code'];
                Log4php::getLogger()->debug($this->logId." callback_error $err");
            }
        }
        $info = curl_getinfo($ch);
        curl_close($ch);
        if ($err != "" && intval($info['http_code']) != 200 && $countRetry < 5){
            $countRetry++;
            sleep(2);
            $result = $this->curl_post($url, $get, $options, $err, $countRetry);
        }
        return $result;
    }


    function echoparams($request)
    {
        Log4php::getLogger()->debug($this->logId." - SERVER PARAMS : ".json_encode($this->input->get()));
        Log4php::getLogger()->trace($this->logId." - CLIENT IP : ".$this->input->ip_address());

        $args = $request->output_parameters();

        $keys = array_keys($args[0]);
        $tmp = array();

        foreach($keys as $k) {
            $tmp[$k] = $args[0][$k];
        }
        $response = array(
            array($tmp),
            'struct'
        );
        return $this->xmlrpc->send_response($response);
    }

    function hello($request)
    {
        $requester = $this->input->ip_address();
        Log4php::getLogger()->debug($this->logId." REQUEST FROM CLIENT IP $requester");

        $args = $request->output_parameters();

        $response = array(
            array('hallo there'),
            'struct'
        );
        return $this->xmlrpc->send_response($response);
    }


}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */