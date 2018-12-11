<?php

class Refno extends CI_Model {
    function __construct()
    {
        parent::__construct();
    }

    public static function getId() {
        $CI =&get_instance();
        $params['created_at'] = date('Y-m-d H:i:s');
        $CI->db->insert("refno_id",$params);
        return $CI->db->insert_id();
    }
}