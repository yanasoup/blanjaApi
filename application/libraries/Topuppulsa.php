<?php

class Topuppulsa {
    
    function __construct() {
        $this->CI=& get_instance();
        $this->CI->load->library('logtopup','logger');
        $this->CI->load->library('xmlrpc');
        $this->CI->load->library('xmlrpcs');
        $this->logId = strtotime(date('YmdHis'));
    }
    
    function push($nomorhp,$item,$trxid){ 
       # echo '1'; 
        #Logtopup::getLogger()->debug($this->logId." TEST MASUK LIBRARY ");
        #die;
        Logtopup::getLogger()->debug($this->logId." START TOPUP PULSA ... ");
        Logtopup::getLogger()->debug($this->logId." data : nomorhp= ".$nomorhp." item : ".$item." trx_id ".$trxid);
        $server_url = "http://43.247.13.17/webportal/api/h2hxml";
        $this->CI->xmlrpc->server($server_url);

        $params = array(
            array( 
                array( 
                        'MSISDN' => '08118051889', 
                        'REQUESTID' => $trxid,
                        'PIN' => '918273645',  
                        'HOHP' => $nomorhp,
                        'NOM' => $item
                        #'HOHP' => '081219790940'
                ),
                'struct'
            ),
        );
        $this->CI->xmlrpc->set_debug(FALSE);
        $this->CI->xmlrpc->method('topUpRequest');
        $this->CI->xmlrpc->request($params);

        if ( ! $this->CI->xmlrpc->send_request())
        {
            #Log4php::getLogger()->debug($this->logId." ERROR ".$this->CI->display_error());
            Logtopup::getLogger()->debug($this->logId." XML ERROR REQUEST!!!".json_encode($this->CI->xmlrpc->display_response())); 
            Logtopup::getLogger()->debug($this->logId." PARAMS ERROR REQUEST!!!".json_encode($params)); 
            Logtopup::getLogger()->debug($this->logId." ERROR LIBRARY API TOP PULSA".$this->CI->xmlrpc->display_error());
            return false;
        }
        else
        {
            Logtopup::getLogger()->debug($this->logId." XML !!!".json_encode($this->CI->xmlrpc->display_response()));
           # Logtopup::getLogger()->debug($this->logId." JSON ".xmlrpc_decode($this->CI->xmlrpc->display_response()));
           # return json_encode($this->CI->xmlrpc->display_response());

            return json_encode($this->CI->xmlrpc->display_response());
        }

    }
}