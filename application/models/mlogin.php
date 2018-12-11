<?php

class Mlogin extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function check($params) {
        $CI =&get_instance();
        return $CI->db->where('username',$params['username'])
            ->where('passwd',md5($params['password']))
            ->get('login');
    }
}