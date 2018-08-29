<?php

class Javahttpresponse extends CI_Controller
{

    function __construct()
    {
        parent::__construct();
        $this->load->library('log4php', 'logger');
    }


    public function index()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $headers = $this->getheaders();
            $resp = array(
                'ack' => 0,
                'message' => 'POST method response from API',
                'input_params' => $_POST,
                'headers' => $headers
            );

        } else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $headers = $this->getheaders();
            $resp = array(
                'ack' => 0,
                'message' => 'GET method response from API',
                'input_params' => $_GET,
                'headers' => $headers
            );

        } else {
            $resp = array(
                'ack' => 0,
                'message' => 'UNKNOWN method request'
            );
        }
        #echo '<pre>'.print_r($resp,1).'</pre>';
        #die;

        header("Content-type: application/json");
        echo json_encode($resp);
    }

    function getheaders()
    {
        $headers = '';
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}