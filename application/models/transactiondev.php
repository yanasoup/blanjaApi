<?php

class Transactiondev extends CI_Model {

    var $table = "transaction_dev";
    const IS_DEV = false;

    function __construct($isDev)
    {
        parent::__construct();
        if ($isDev) {
            $this->table = "transaction_dev";
        } else {
            $this->table = "transaction";
        }
    }
    public function getTableName() {
        return $this->table;
    }
    public static function insertTrx($params) {
        $CI =&get_instance();
        $params['created_at'] = date('Y-m-d H:i:s');
        $params['updated_at'] = date('Y-m-d H:i:s');
        $CI->db->insert(self::getTableName(),$params);
        return $CI->db->insert_id();
    }
    public static function updateTrx($params) {
        $CI =&get_instance();
        #$CI->db->where('id',$params['id']);
        #$params['updated_at'] = date('Y-m-d H:i:s');
        $CI->db->where('orderNumber',$params['orderNumber']);
        $CI->db->where('upointH2hReqRefno',$params['upointH2hReqRefno']);
        $CI->db->update(self::getTableName(),$params);
    }
    public static function getByOrderNumber($orderNo) {
        $CI =&get_instance();
        return $CI->db->where('orderNumber',$orderNo)->get(self::getTableName());
    }
    public static function getByRefNo($refNo) {
        $CI =&get_instance();
        return $CI->db->where('upointH2hReqRefno',$refNo)->get(self::getTableName());
    }
    public static function getUnsentVoucher() {
        $CI =&get_instance();
        return $CI->db->where('shipped',1)
            ->where('email_sent',0)
            ->order_by("orderNumber")
            ->get(self::getTableName());
    }

}