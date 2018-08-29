<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
#error_reporting(E_ALL);
class Topupalloperator extends CI_Controller {

    private $whiteListIPs = array(
        '192.168.1.2','118.97.188.99'
    );
   
   
    public $logId = 0;
    const TOPUP_DESTINATION_URL = "119.110.71.252";
    const TOPUP_PORT = "6789";
    
    function __construct() {
        ini_set("display_errors",false);
        parent::__construct();
        $this->load->library('logtopup','logger');
        
        $this->load->library('Topuppulsa');
        $this->load->helper('string');
        $this->load->library('encrypt');
        $this->logId = strtotime(date('YmdHis'));
        if ($this->input->server('REQUEST_METHOD') != 'POST') {
            echo "Only Accept POST"; die;
        }
         $requester = $this->input->ip_address();
        $inputs =  file_get_contents("php://input"); 
        Logtopup::getLogger()->debug("=================================================================================================");
        Logtopup::getLogger()->debug($this->logId." REQUEST FROM IP $requester");
        Logtopup::getLogger()->debug($this->logId." REQUEST URI : ".$_SERVER['REQUEST_URI']);
        Logtopup::getLogger()->debug($this->logId." USER : ".$this->input->server('REMOTE_USER'));
        Logtopup::getLogger()->debug($this->logId." HTTP_USER_AGENT : ".$_SERVER['HTTP_USER_AGENT']);
        Logtopup::getLogger()->debug($this->logId." SERVER_PORT : ".$_SERVER['SERVER_PORT']);
        Logtopup::getLogger()->debug($this->logId." REMOTE_PORT : ".$_SERVER['REMOTE_PORT']);
    }

    public function index(){
        $this->output->set_status_header('404');
        echo json_encode(array('status'=>FALSE, 'code'=>'E999'));
    }
 
    function topupRequest($request)
    {

        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        $requester = $this->input->ip_address();
        $inputs =  file_get_contents("php://input");
        Logtopup::getLogger()->debug($this->logId." DATA ".json_encode($inputs));
        if (!$this->_validateIp()) {
           
            Logtopup::getLogger()->debug($this->logId." - IP ADDRESS ".$requester." NOT ALLOWED");
            $res['error_code'] = 34;
            $res['error_desc'] = 'IP not allowed';
             $this->sendResponse($res);
            
        }

         Logtopup::getLogger()->debug($this->logId." PASS 1");
        
        $this->requireParams(array('phone_number','trx_id','item','callback_url','signature'));
        $trx_id = $this->input->post('trx_id');
        $phone_number = $this->input->post('phone_number');
        $item_code = $this->input->post('item');
        $signaturepost = $this->input->post('signature');
        $callbackurl = $this->input->post('callback_url');
        $flag_trx_pulsa = $this->input->post('is_trx_pulsa');
        $status =  -1;
        $secret_token = '@m3tranet12345678';
        
       Logtopup::getLogger()->debug($this->logId." PASS PARAMS");


        $signatureServer = md5($trx_id.$phone_number.$item_code.$secret_token);

        if($signatureServer != $signaturepost){
            Logtopup::getLogger()->debug($this->logId." STATUS 0 ");
            $res['status'] = 0;
            $res['error_code'] = 35;
            $res['error_desc'] = 'signature not allowed';
            $res['signature'] = $signatureServer;
            $res['trx_id'] = $trx_id;
            $this->sendResponse($res);
            exit(0);
        }
         
          Logtopup::getLogger()->debug($this->logId." PASS 2");
         $this->load->model('transactions_model'); 
         $saldo_depost = $this->transactions_model->check_deposit();

        # echo '<pre/>'.print_r($saldo_depost,true);

         Logtopup::getLogger()->debug($this->logId." SALDO DATA".json_encode($saldo_depost));
         #die;

          if(($saldo_depost['saldo']) < 1000000 ){

           

            $message = "Deposit Pulsa Mau Habis untuk Layanan TOP UP All Operator, Saldo sisa adalah Rp.".$saldo_depost['saldo'].".<br/>";
            $url = 'http://192.168.1.4/bulk_vcr/emailalertapi/sendemailalert';
            
            $data['recipients'] = 'firman.firdaus@metranet.co.id';
            $data['message'] =  $message  ; 
            $data['subject'] = 'MAU HABIS DEPOSIT VOUCHER !!!';
            $sendMessage = $this->curl_post($url, $data);


            if (!!$sendMessage) {
                 Logtopup::getLogger()->debug($this->logId." email sent ");
            } 
            else {
                 Logtopup::getLogger()->debug($this->logId." email faild to sent ");
                 
            }
        }
         
         if(($saldo_depost['saldo']) < 30000 ){



             Logtopup::getLogger()->debug($this->logId." SALDO ".$saldo_depost['saldo']. ' - '.$this->amount);
            $res['status'] = 0;
            $res['error_code'] = 36;
            $res['error_desc'] = 'No enough saldo deposit.';
            $res['trx_id'] = -1;
            $this->sendResponse($res);
            exit(0);
         }
         Logtopup::getLogger()->debug($this->logId." PASS 3");
         
         if($this->transactions_model->transaction_insert($trx_id, $phone_number, $status, $item_code,$callbackurl,$flag_trx_pulsa)){
            Logtopup::getLogger()->debug($this->logId." TOPUP START DOING HIS JOBS !!.....");
             $topupstatus = $this->topuppulsa->push($phone_number,$item_code,$trx_id);
             $decodetopupstatus = json_decode($topupstatus,true);
             $decodetopupstatusObject = json_decode($topupstatus);
             #Logtopup::getLogger()->debug($this->logId." resul top up ".print_r($decodetopupstatus,true));

             if(property_exists($decodetopupstatusObject, 'RESPONSECODE'))
             {
                 if($decodetopupstatus['RESPONSECODE'] == 00 || $decodetopupstatus['RESPONSECODE'] == 68){
                    Logtopup::getLogger()->debug($this->logId." STATUS 1 ");
                    $res['status'] = 1;
                    $res['trx_id'] = $trx_id;
                    Logtopup::getLogger()->debug($this->logId." SUCCESS YEAH ....."); 
                    $this->sendResponse($res);
                 }else{ 
                    Logtopup::getLogger()->debug($this->logId." STATUS 0 ");
                    $res['status'] = 0;
                    $res['trx_id'] = -1;
                    $res['errorcode'] = $decodetopupstatus['RESPONSECODE'];
                    $res['error_info'] = $decodetopupstatus['MESSAGE'];
                    $this->sendResponse($res);
             }
            }else{
                Logtopup::getLogger()->debug($this->logId." Not connect to partner api ");
                    Logtopup::getLogger()->debug($this->logId." STATUS 0 ");
                    $res['status'] = 0;
                    $res['trx_id'] = -1;
                    $res['errorcode'] = 505;
                    $res['error_info'] = 'Not connect to partner api';
                    $this->sendResponse($res);
            }
            
         }else{
             Logtopup::getLogger()->debug($this->logId." STATUS 0 ");
             $res['status'] = 0;
             $res['trx_id'] = -1;
             $this->sendResponse($res);
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

   protected function prepErrorMissingParams($trx_id, $missing_params){
        $res = new stdClass();
        $res->result = false;
        $res->trx_id = $trx_id;
        $res->error_code = 'E001';
        $replacee = array('/<num>/', '/<params>/');
        $replacement = array(count($missing_params), join(", ", $missing_params));
        
        $res->error_info = preg_replace($replacee, $replacement, "Missing <num> parameter(s): <params>");
        return $res;
    }
    protected function prepErrorInvalidParam($trx_id, $param){
        $res = new stdClass();
        $res->result = false;
        $res->trx_id = $trx_id;
        $res->error_code = 'E002';
        $replacee = array('/<param>/');
        $replacement = array($param);
        
        $res->error_info = preg_replace($replacee, $replacement, "Invalid parameter: <param>");
        return $res;
    }
    protected function requireParams($params){
         #Logtopup::getLogger()->debug($this->logId." - params ".json_encode($params));
         #Logtopup::getLogger()->debug($this->logId." - input_post ".json_encode($this->input->post('test')));
        $missing_params = array();
        $this->trx_id = -1;
        
        foreach($params as $param){
            #Logtopup::getLogger()->debug($this->logId." - parammeter ".json_encode($param));
            $this->$param = $this->input->post($param);
            if ($this->$param == null) $missing_params[] = $param;
        }
        if (count($missing_params) > 0) {
            $res = $this->prepErrorMissingParams($this->trx_id, $missing_params);
            $this->sendResponse($res);  
        }
    }
    protected function validateParamsRegex($parameter, $regex, $errortext){
        if (isset($this->$parameter)){
            if (!preg_match($regex, $this->$parameter)){
                if (!isset($this->trx_id)) $this->trx_id = -1;
                $res = $this->prepErrorInvalidParam($this->trx_id, $parameter);
                $this->sendResponse($res);
            }   
        }
    }

      //UTILS
    protected function sendResponse($content = null, $code = 200, $status='OK'){
    Logtopup::getLogger()->debug($this->logId." SendResponse ".json_encode($content)); 
        header('Content-type : application/json');
        header('status : '.$code);
//        $this->output
//            ->set_content_type('application/json')
//            ->set_status_header($code)
//            ->set_output(json_encode($content));
        echo json_encode($content);
        exit(0);
    }

    function _extractKeys($xmlObj) {
        #Logtopup::getLogger()->trace($this->logId." - extracting keys from xmlObj...");
        $paramkeys = false;
        try {
            $arr = (array)$xmlObj->params->param->value->struct;
            #Logtopup::getLogger()->info("arr :".json_encode($arr)."\n");
            $params = $arr['member'];

            $paramkeys = array();
            foreach($params as $k => $v) {
                $paramkeys[] = (string)$v->name;
            }

        } catch (Exception $e) {
            Logtopup::getLogger()->error($this->logId." - ERROR EXTRACTING XMLOBJ : ".$e->getMessage()."\n");
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
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POSTFIELDS => http_build_query($post)
        );
        if (strpos("https://", $url) === 0){
            Logtopup::getLogger()->debug($this->logId." SSL");
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
                Logtopup::getLogger()->debug($this->logId." callback_error $err");

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
            Logtopup::getLogger()->info($this->logId." SSL");
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
                Logtopup::getLogger()->debug($this->logId." callback_error $err");
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


    // function echoparams($request)
    // {
    //     Logtopup::getLogger()->debug($this->logId." - SERVER PARAMS : ".json_encode($this->input->get()));
    //     Logtopup::getLogger()->trace($this->logId." - CLIENT IP : ".$this->input->ip_address());

    //     $args = $request->output_parameters();

    //     $keys = array_keys($args[0]);
    //     $tmp = array();

    //     foreach($keys as $k) {
    //         $tmp[$k] = $args[0][$k];
    //     }
    //     $response = array(
    //         array($tmp),
    //         'struct'
    //     );
    //     return $this->xmlrpc->send_response($response);
    // }


}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */