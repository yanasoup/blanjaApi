<?php

class Client extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->library('log4php','logger');
        $this->load->helper('string');
        $this->load->library('xmlrpc');
        $this->load->helper('url');
        $this->logId = strtotime(date('YmdHis'));
    }

    function index() {
        #$server_url = site_url('xmlrpc');
        #$server_url = "https://alfamvp.upoint.co.id/index.php/xmlrpc";
        #$this->xmlrpc->server($server_url, 443);
        #$server_url = "http://118.97.213.210/alfamvp/index.php/xmlrpc";
        #$server_url = "https://118.97.213.210/alfamvp/index.php/xmlrpc";
        $server_url = "https://118.97.213.210/alfamvp/index.php/xmlrpc";
        echo "SERVER URL : ".$server_url."<br />";
        #die;
        $this->xmlrpc->server($server_url);


        $params = array(
            array(
                array(
                    "ProcessingCode" => 7000,
                    "ItemID" => "OV50",
                    "TransactionID" => random_string('alnum',6),
                    "TransactionDateTime" => 'Tue Aug 07 16:31:00 ICT 2016',
                    "Amount" => '50000',
                    "TerminalID" => '123456789011',
                    "PIN" => '66666666',
                    "CardNumber" => '99999999',
                    "MerchantType" => '1111',
                    "MerchantID" => '1234',
                    "Destination" => '1234567890',
                ),
                'struct'
            ),
        );
        #$params = array('How is it going?');
        #echo json_encode($params); die;
        $this->xmlrpc->set_debug(TRUE);
        $this->xmlrpc->method('tokenRequest');
        $this->xmlrpc->request($params);

        if ( ! $this->xmlrpc->send_request())
        {
            Log4php::getLogger()->debug($this->logId." ERROR ".$this->xmlrpc->display_error());
            echo "ERROR ".$this->xmlrpc->display_error();
        }
        else
        {
            Log4php::getLogger()->debug($this->logId." RESPONSE ".$this->xmlrpc->display_response());
            echo '<pre>';
            print_r($this->xmlrpc->display_response());
            echo '</pre>';
        }


    }

    function indexold()
    {

        $this->load->helper('url');
        $server_url = site_url('xmlrpc');
        #$server_url = "http://118.97.213.210/alfamvp/";

        $this->load->library('xmlrpc');

        $this->xmlrpc->server($server_url, 80);
        $this->xmlrpc->method('Greetings');

        $request = array('How is it going?');
        $this->xmlrpc->request($request);
        $this->xmlrpc->set_debug(TRUE);
        if ( ! $this->xmlrpc->send_request())
        {
            echo $this->xmlrpc->display_error();
        }
        else
        {
            echo '<pre>';
            print_r($this->xmlrpc->display_response());
            echo '</pre>';
        }
    }


}