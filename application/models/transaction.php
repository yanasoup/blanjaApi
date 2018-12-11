<?php

class Transaction extends CI_Model {
    function __construct()
    {
        parent::__construct();
    }

    public static function insertTrx($params) {
        $CI =&get_instance();
        $params['created_at'] = date('Y-m-d H:i:s');
        $params['updated_at'] = date('Y-m-d H:i:s');
        $CI->db->insert("transaction",$params);
        return $CI->db->insert_id();
    }
    public static function updateTrx($params) {
        $CI =&get_instance();
        #$CI->db->where('id',$params['id']);
        #$params['updated_at'] = date('Y-m-d H:i:s');
        $CI->db->where('orderNumber',$params['orderNumber']);
        $CI->db->where('upointH2hReqRefno',$params['upointH2hReqRefno']);
        $CI->db->update("transaction",$params);
    }
    public static function updateTrxByOrderNumber($params) {
        $CI =&get_instance();
        #$CI->db->where('id',$params['id']);
        #$params['updated_at'] = date('Y-m-d H:i:s');
        $CI->db->where('orderNumber',$params['orderNumber']);
        $CI->db->where('accepted',1);
        $CI->db->update("transaction",$params);
    }
    public static function getByOrderNumber($orderNo) {
        $CI =&get_instance();
        return $CI->db->where('orderNumber',$orderNo)->get("transaction");
    }
    public static function getByOrderNumberAndInvoiceNo($orderNo) {
        $CI =&get_instance();
        return $CI->db->where('orderNumber',$orderNo)->where('accepted',0)->get("transaction");
    }
    public static function getByRefNo($refNo) {
        $CI =&get_instance();
        return $CI->db->where('upointH2hReqRefno',$refNo)->get("transaction");
    }
    public static function getUnsentVoucher() {
        $CI =&get_instance();
        return $CI->db->where('shipped',1)
            ->where('email_sent',0)
            ->order_by("orderNumber")
            ->get("transaction");
    }
    public static function getUnshippedOrder($where = array()) {
        $CI =&get_instance();
        $res = $CI->db->where('shipped',0)
            ->where('accepted',1)
            ->order_by("orderNumber");

        if (is_array($where) && sizeof($where) > 0) {
            $res->where($where);
        }

        return $res->get("transaction");

    }

}