<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Directapi extends CI_Controller
{

    const BLANJA_PARTNER_ID = "195";
    const BLANJA_SECRET_TOKEN = "c1b9c38713021c94784d4301186cd66b07d9f1fd";
    const INQUIRY_URL = "http://192.168.1.2/upoint2/dev_api_dtu/inquiry";
    const PAYMENT_URL = "http://192.168.1.2/upoint2/dev_api_dtu/payment";
    const PRODUCT_ROS = 'rules_of_survival';
    const PRODUCT_ML = 'mobile_legends';
    const PRODUCT_AOV = 'arena_of_valor';
    const PRODUCT_FF = 'free_fire';
    const DEVELOPER_EMAIL = 'yana.supriatna@melon.co.id';
    const DTU_ITEM_CHECK_API_URL = "https://upoint.co.id/api_blanja_dtu_item/get_item";
    const DTU_ITEM_CHECK_API_APIKEY = "8sk993j883nnsdau1230s8sd234";

    private static $PRODUCT_LIST = array(
        self::PRODUCT_ROS,
        self::PRODUCT_ML,
        self::PRODUCT_AOV,
        self::PRODUCT_FF,
    );

    public static $GAME_NAME = array(
        self::PRODUCT_ROS => 'Rules Of Survival',
        self::PRODUCT_ML => 'Mobile Legends',
        self::PRODUCT_AOV => 'Garena Age Of Valor',
        self::PRODUCT_FF => 'Garena Free Fire',
    );

    function __construct()
    {
        ini_set("display_errors", false);
        parent::__construct();
        $this->load->library('log4php', 'logger');
        $this->load->library('blanja');
        $this->load->helper(array('yana', 'string'));
        $this->logId = uniqid(rand());
        $this->load->model('Refno');
        $this->load->model('Transaction');

    }

    public function getOrders() {
        #$this->load->model('transactiondev');
        /** CRON TAB ADA DI USER www-data
         * edit : sudo su -c "crontab -e" www-data
         */
        Log4php::getLogger()->debug(sprintf("%s ======= GET DTU ORDERS START =======",$this->logId));
        $params = array();
        $params['status'] = 'PAID';
        $params['productType'] = 'DIGITAL_PRODUCT';
        #$params['orderNumber'] = "418765716284510375";
        $xml = new SimpleXMLElement($this->blanja->getOrders($params));
        $apiResult = json_decode(json_encode($xml));
        #print_out($apiResult,0);
        #Log4php::getLogger()->debug("ORDERS XML : ".json_encode($orders));
        if (is_object($apiResult) && property_exists($apiResult,'status') && strtoupper($apiResult->status)=='SUCCESS') {

            if (!property_exists($apiResult->result->orders,'order')) {
                Log4php::getLogger()->debug(sprintf("%s No PAID Orders",$this->logId));
                Log4php::getLogger()->debug(sprintf("%s ======= GET DTU ORDERS END =======",$this->logId));
                exit(0);
            }

            $orderList = array();
            if (is_object($apiResult->result->orders->order)) {
                if (property_exists($apiResult->result->orders->order,'orderNumber')) {
                    $orderList = array($apiResult->result->orders->order);
                } else {
                    $orderList = array();
                }
            } else if (is_array($apiResult->result->orders->order)) {
                # jika order lebih dari 1, struktur xml objectnya :
                # $apiResult->result->orders->order->orderNumber[indexArray]->orderNumber
                $orderList = $apiResult->result->orders->order;
            }

            if (sizeof($orderList) < 1) {
                Log4php::getLogger()->debug(sprintf("%s No Orders",$this->logId));
                Log4php::getLogger()->debug(sprintf("%s ======= GET DTU ORDERS END =======",$this->logId));
                echo "No Orders".PHP_EOL;
                exit(0);
            }


            foreach($orderList as $order) {

                if(!in_array($order->orderLines->orderLine->productCode,self::$PRODUCT_LIST)) {
                    continue;
                }

                #print_out($order,0);
                $kini = date('Y-m-d H:i:s');
                $expDate = date_create($kini);
                date_add($expDate, date_interval_create_from_date_string('1 hours'));
                $numberOfSuccess = 0;
                $resDtu = array();
                $qty = $order->orderLines->orderLine->quantity;
                #$qty=2;
                for ($i=0;$i<$qty;$i++) {
                    try {
                        $params = array();
                        $params['productCode'] = $order->orderLines->orderLine->productCode;
                        $params['userInfo'] = $order->orderLines->orderLine->userInfo;
                        $params['buyerEmail'] = $order->buyerEmail;
                        $params['skuCode'] = $order->orderLines->orderLine->skuCode;
                        $params['amount'] = $order->orderLines->orderLine->price;
                        $params['amount_payment'] = $order->orderLines->orderLine->payment;
                        #$params['buyerEmail'] = self::DEVELOPER_EMAIL;
                        #$params['skuCode'] = "UGARENA0";
                        #$params['amount'] = 0;
                        $params['orderNumber'] = $order->orderNumber;
                        $params['receiverMobile'] = $order->receiverMobile;
                        $params['receiverName'] = $order->buyerEmail;
                        $params['transactionTime'] = $order->orderLines->orderLine->created;
                        $params['expire_date'] = date_format($expDate, 'Y-m-d H:i:s');
                        $params['itemTitle'] = $order->orderLines->orderLine->itemTitle;
                        $params['productType'] = $order->orderLines->orderLine->productType;

                        Log4php::getLogger()->debug(sprintf("%s - ========================= PROSES %s %s %s ===============================", $this->logId, $order->orderNumber, $order->orderLines->orderLine->skuCode, $order->buyerEmail));
                        Log4php::getLogger()->debug(sprintf("%s - sendNewInquiryAndPaymentOnlyNoEmail params %s", $this->logId, json_encode($params)));
                        #print_out($params);

                        $vcrReqRes = $this->sendNewInquiryAndPaymentOnlyNoEmail($params);
                        if ($vcrReqRes['success']) {
                            $resDtu[] = $vcrReqRes;
                            $numberOfSuccess++;
                        }
                    } catch (Exception $e) {
                        Log4php::getLogger()->debug(sprintf("%s ERROR " . $e->getMessage(),$this->logId));
                    }
                }

                if ($numberOfSuccess == 0) {
                    Log4php::getLogger()->debug(sprintf("%s no success transaction orderno %s ", $this->logId,$order->orderNumber));

                    foreach ($resDtu as $dtu) {
                        if (array_key_exists('err_code',$dtu)) {
                            $msgParams=array(
                                'orderNumber' => $order->orderNumber,
                                'message' => $dtu['err_msg']
                            );
                            $this->blanja->sendSellerMessage($msgParams);
                            continue;
                        } else {
                            $msgParams=array(
                                'orderNumber' => $order->orderNumber,
                                'message' => 'mohon maaf saat ini sistem kami sedang sibuk. silahkan coba beberapa saat lagi.'
                            );
                            $this->blanja->sendSellerMessage($msgParams);
                        }
                    }

                    continue;
                }


                /** ship order */
                $shipParams = array();
                $shipParams['orderNumber'] = $order->orderNumber;
                $logistic = in_array($order->orderLines->orderLine->productType,array_keys($this->logisticCompanies)) ? $this->logisticCompanies[$order->orderLines->orderLine->productType] : 'DIGITAL';
                $shipParams['logisticsCompany'] = $logistic;
                $shipParams['invoiceNumber'] = $dtu['trx_detail']['invoiceno'];
                $shipRes = $this->shipOrder($shipParams);
                #$shipRes = new stdClass();
                #$shipRes->status = 'success';

                Log4php::getLogger()->debug(sprintf("%s - SHIP RES : %s",$this->logId,json_encode($shipRes)));
                if (is_object($shipRes) && property_exists($shipRes,'status')) {
                    if (strtoupper($shipRes->status) == 'SUCCESS') {
                        $trxBlanja = array();
                        $trxBlanja['shipped'] = 1;
                        $trxBlanja['orderNumber'] = $order->orderNumber;
                        Transaction::updateTrxByOrderNumber($trxBlanja);

                        /** kirim email ke user */
                        Log4php::getLogger()->debug(sprintf("%s - SENDING EMAIL to %s",$this->logId,$order->buyerEmail));
                        $trxTime = date("Y-m-d H:i:s",strtotime($order->orderLines->orderLine->created));
                        $emailTplVars = array(
                            'user_email' => $order->buyerEmail,
                            'order_number' => $order->orderNumber,
                            'trx_time' => $trxTime,
                            'item_title' => $order->orderLines->orderLine->itemTitle,
                            'item_price' => $order->orderLines->orderLine->payment,
                            'topupstatus' => 'Berhasil'
                        );
                        $emailHtml = $this->templateEmailDtu($emailTplVars);
                        $emailParams = array('to' => $order->buyerEmail,'subject' => 'Blanja - Direct TopUp '.self::$GAME_NAME[$order->orderLines->orderLine->productCode].' '.$order->orderNumber,'message' => $emailHtml);

                        try {
                            $resEmail = $this->kirimEmail($emailParams);
                            $objResEmail = json_decode($resEmail);
                            if (is_object($objResEmail) && property_exists($objResEmail,'email_sent') && $objResEmail->email_sent == true) {
                                $trxBlanja = array();
                                $trxBlanja['email_sent'] = 1;
                                $trxBlanja['orderNumber'] = $order->orderNumber;
                                Transaction::updateTrxByOrderNumber($trxBlanja);
                                Log4php::getLogger()->debug(sprintf("%s - SENDING EMAIL SUCCESS",$this->logId));
                            }
                        } catch (Exception $e) {
                            Log4php::getLogger()->debug(sprintf("%s - EMAIL NOT SENT TO %s ORDER NO : %s".$this->logId,$emailParams['to'],$order->orderNumber));

                        }
                        /** end kirim email ke user */
                    }
                } else {
                    Log4php::getLogger()->debug(sprintf("%s - failed to ship to blanja ORDER NO : %s",$this->logId,$order->orderNumber));
                }
                /** end ship order */


                /* sent alert email */
                if ($numberOfSuccess != $qty) {
                    $devEmailAlertParams = array(
                        'to' => self::DEVELOPER_EMAIL,
                        'subject' => 'Blanja - Some vouchers not delivered '.strtoupper($order->orderNumber),
                    );
                    $devEmailAlertParams['message'] = "order no : ".$order->orderNumber;
                    $devEmailAlertParams['message'] .= "<br />Order Qty : ".number_format($qty,0);
                    $devEmailAlertParams['message'] .= "<br />Order delivered : ".number_format($numberOfSuccess,0);
                    $this->kirimEmail($devEmailAlertParams);
                }
                /* end sent alert email */
            }

        }
        Log4php::getLogger()->debug(sprintf("======= GET DTU ORDERS END ======="));
        echo "DONEs";
    }
    private function sendNewInquiryAndPaymentOnlyNoEmail($inputs) {
        #$this->load->model('transactiondev');
        $res = array('success' => false,'msg' => '');

        $secret_key = self::BLANJA_SECRET_TOKEN;
        $item_code = $inputs['skuCode'];
        $partner_id = self::BLANJA_PARTNER_ID;
        $callbackUrl = "http://118.97.213.210/blanjaApi/catalog/dtu_callback";
        $vouchers = array();

        try {
            $refnoId = Refno::getId();
            $prefix = "blanjadtu";
            $pad = $prefix.random_string('alnum', (30-(strlen($prefix)+strlen($refnoId))));
            $ref_no = substr($pad.$refnoId,0,30);

            $trxBlanja = array();
            $trxBlanja['id'] = $refnoId;
            $trxBlanja['accepted'] = 0;
            $trxBlanja['orderNumber'] = $inputs['orderNumber'];
            $trxBlanja['umb_code'] = $inputs['skuCode'];
            $trxBlanja['buyerEmail'] = $inputs['buyerEmail'];
            $trxBlanja['itemTitle'] = $inputs['itemTitle'];
            $trxBlanja['expire_date'] = $inputs['expire_date'];
            $trxBlanja['transactionTime'] = $inputs['transactionTime'];
            $trxBlanja['amount'] = $inputs['amount'];
            $trxBlanja['amount_payment'] = $inputs['amount_payment'];
            $trxBlanja['upointH2hReqRefno'] = $ref_no;
            $rowId = Transaction::insertTrx($trxBlanja);

            /* inquiry
             * aov request : {"_url":"\/api_dtu\/inquiry","partner_id":"170","trx_id":"1153567","product":"arena_of_valor","item":"aov_7","user_info":"{\"user_id\":\"1039652468163741\", \"user_ip\":\"114.124.215.87\"}","callback_url":"https:\/\/duniagames.co.id\/payment\/external\/callback","signature":"7e34df9ecd122715135299eb9ea37a6cc223206a","address":"209.97.170.5","ua":"GuzzleHttp\/6.3.2 curl\/7.52.1 PHP\/7.0.32-2+0~20181015120817.7+stretch~1.gbpa6b8cf","method":"POST"}
             * aov response : {"status":100,"status_msg":"OK","t_id":"DGAPI5pyb5r00o1hmDyLANxbqU6u0SCcm6p9a8","trx_id":"1153567","info":{"product":"arena_of_valor","item":"aov_7","amount":"2500","user_info":{"user_id":"1039652468163741","user_ip":"114.124.215.87"},"time":"2018-12-01 23:57:46","details":[{"packed_role_id":786432,"server_name":"Baratayuda","role_name":"ItsME\u2022\u20aa\u30ad\u30e2\u30c1"}]}}
             *
             * mobleg request : {"_url":"\/api_dtu\/inquiry","partner_id":"170","trx_id":"1113371","product":"mobile_legends","item":"diamond_TelkomselIDR_75000","user_info":"{\"user_id\":\"115058387\", \"zone_id\":\"2577\"}","callback_url":"https:\/\/duniagames.co.id\/payment\/external\/callback","signature":"24743c314b5e833bcfaa56ae22694432ca7b61f3","address":"209.97.170.5","ua":"GuzzleHttp\/6.3.2 curl\/7.52.1 PHP\/7.0.32-2+0~20181015120817.7+stretch~1.gbpa6b8cf","method":"POST"}
             * mobleg  response : {"status":100,"status_msg":"OK","t_id":"DGAPI1c16blyi3ulcNA9wYQ2k7hDwuV7MQcLek","trx_id":"1113371","info":{"product":"mobile_legends","item":"diamond_TelkomselIDR_75000","amount":"75000","user_info":{"user_id":"115058387","zone_id":"2577"},"time":"2018-12-01 09:53:20","details":{"username":"akbar07"}}}
             *
             * free_fire request : {"_url":"\/api_dtu\/inquiry","partner_id":"170","trx_id":"1106416","product":"free_fire","item":"freefire_12","user_info":"{\"user_id\":\"233188181\", \"user_ip\":\"182.0.145.6\"}","callback_url":"https:\/\/duniagames.co.id\/payment\/external\/callback","signature":"a8300832963604b052f29b1a578c85fb263c0c8d","address":"209.97.170.5","ua":"GuzzleHttp\/6.3.2 curl\/7.52.1 PHP\/7.0.32-2+0~20181015120817.7+stretch~1.gbpa6b8cf","method":"POST"}
             * free_fire response : {"status":100,"status_msg":"OK","t_id":"DGAPI60p94yz0c0lduBxj5ceNYNe9ppPhHozED","trx_id":"1106416","info":{"product":"free_fire","item":"freefire_12","amount":"2500","user_info":{"user_id":"233188181","user_ip":"182.0.145.6"},"time":"2018-12-01 04:22:09","details":[{"packed_role_id":0,"server_name":"","role_name":""}]}}
            */
            $inquiryParams = array(
                'partner_id' => $partner_id,
                'trx_id' => $inputs['orderNumber'],
                'product' => $inputs['productCode'],
                'item' => $item_code,
                'user_info' => $inputs['userInfo'],
                'callback_url' => $callbackUrl,
                'signature' => sha1($inputs['orderNumber'] . $inputs['productCode'] . $item_code . $inputs['userInfo'] . $callbackUrl . $secret_key)
            );
            Log4php::getLogger()->debug(sprintf($this->logId . " INQUIRY REQUEST PARAMS %s", json_encode($inquiryParams)));

            $jsonI = $this->curl_post(self::INQUIRY_URL, $inquiryParams);
            $resI = json_decode($jsonI, 1);
            Log4php::getLogger()->debug(sprintf($this->logId . " INQUIRY RESULT %s", json_encode($resI)));


            if ( int($resI['status']) == 100) {

                /* payment
                * aov request : {"_url":"\/api_dtu\/payment","partner_id":"170","trx_id":"1153567","signature":"df830d820adb90deddae7f2cb3d52136ee55e518","payment_info":"{\"packed_role_id\":\"786432\"}","address":"209.97.170.5","ua":"GuzzleHttp\/6.3.2 curl\/7.52.1 PHP\/7.0.32-2+0~20181015120817.7+stretch~1.gbpa6b8cf","method":"POST"}
                * aov response: {"status":100,"status_msg":"OK","t_id":"DGAPI5pyb5r00o1hmDyLANxbqU6u0SCcm6p9a8","trx_id":"1153567","info":{"product":"arena_of_valor","item":"aov_7","amount":"2500","user_info":{"user_id":"1039652468163741","user_ip":"114.124.215.87"},"time":"2018-12-01 23:58:10"}}
                *
                * mobleg request : {"_url":"\/api_dtu\/payment","partner_id":"170","trx_id":"1113371","signature":"5cfb2cce02e4e456cb06bfbbbcf8592e19ba3671","address":"209.97.170.5","ua":"GuzzleHttp\/6.3.2 curl\/7.52.1 PHP\/7.0.32-2+0~20181015120817.7+stretch~1.gbpa6b8cf","method":"POST"}
                * mobleg response : {"status":100,"status_msg":"OK","t_id":"DGAPI1c16blyi3ulcNA9wYQ2k7hDwuV7MQcLek","trx_id":"1113371","info":{"product":"mobile_legends","item":"diamond_TelkomselIDR_75000","amount":"75000","user_info":{"user_id":"115058387","zone_id":"2577"},"time":"2018-12-01 09:53:44"}}
                *
                * free_fire request : {"_url":"\/api_dtu\/payment","partner_id":"170","trx_id":"1106416","signature":"2a061a6d1f13538c06689f4ce1661fa2163ac479","payment_info":"{\"packed_role_id\":\"0\"}","address":"209.97.170.5","ua":"GuzzleHttp\/6.3.2 curl\/7.52.1 PHP\/7.0.32-2+0~20181015120817.7+stretch~1.gbpa6b8cf","method":"POST"}
                * free_fire response : {"status":100,"status_msg":"OK","t_id":"DGAPI60p94yz0c0lduBxj5ceNYNe9ppPhHozED","trx_id":"1106416","info":{"product":"free_fire","item":"freefire_12","amount":"2500","user_info":{"user_id":"233188181","user_ip":"182.0.145.6"},"time":"2018-12-01 04:22:39"}}
                */

                $trx_id = $resI['trx_id'];
                $paramsII = array(
                    'partner_id' => $partner_id,
                    'trx_id' => $inputs['orderNumber'],
                    'signature' => md5( $trx_id . $secret_key)
                );


                if (is_array($resI['info']['details'])) {
                    if ($inputs['productCode'] == 'free_fire')
                        $params['payment_info'] = json_decode('{"packed_role_id":'.$resI['info']['details'][0]->packed_role_id.'}');
                }

                Log4php::getLogger()->debug(sprintf($this->logId . " PAYMENT REQUEST %s", json_encode($paramsII)));

                $jsonII = $this->curl_post(self::PAYMENT_URL, $paramsII);
                $resII = json_decode($jsonII, 1);
                Log4php::getLogger()->debug(sprintf($this->logId . " PAYMENT RESPONSE %s", json_encode($resII)));

                if (int($resII['status']) == 100) {
                    $trxBlanja = array();
                    #$trxBlanja['id'] = $rowId;
                    $trxBlanja['accepted'] = 1;
                    //$trxBlanja['voucher'] = $resII['item'][0]['value'];
                    $trxBlanja['invoiceno'] = $resII['t_id'];
                    $trxBlanja['updated_at'] = date('Y-m-d H:i:s');
                    $trxBlanja['orderNumber'] = $inputs['orderNumber'];
                    $trxBlanja['upointH2hReqRefno'] = $resII['t_id'];
                    Transaction::updateTrx($trxBlanja);

                    $res = array('success' => true, 'trx_detail' => $trxBlanja);
                } else {
                    if (int($resI['status']) == 300) {
                        $res = array('success' => false, 'err_code' => $resI['status'], 'msg' => 'error', 'err_msg' => 'transaction rejected by partner');
                        #break;
                    } else if (int($resI['status']) == 301) {
                        $res = array('success' => false, 'err_code' => $resI['status'], 'msg' => 'error', 'err_msg' => 'transaction expired');
                    } else if (int($resI['status']) == 302) {
                        $res = array('success' => false, 'err_code' => $resI['status'], 'msg' => 'error', 'err_msg' => 'transaction expired');
                    } else if (int($resI['status']) == 303) {
                        $res = array('success' => false, 'err_code' => $resI['status'], 'msg' => 'error', 'err_msg' => 'already paid');
                    } else {
                        $res = array('success' => false,'msg' => 'error','err_msg' => 'system busy');
                        #break;
                    }
                    $emailAlertParams = array('to' => self::DEVELOPER_EMAIL, 'subject' => 'Direct TopUp Inquiry Error Blanja ' . strtoupper($inputs['orderNumber']), 'message' => 'error code: ' . $resI['status']);
                    $this->kirimEmail($emailAlertParams);
                }
            } else {

                if (int($resI['status']) == 300) {
                    $res = array('success' => false, 'err_code' => $resI['status'], 'msg' => 'error', 'err_msg' => 'transaction rejected by partner');
                    #break;
                } else if (int($resI['status']) == 301) {
                    $res = array('success' => false, 'err_code' => $resI['status'], 'msg' => 'error', 'err_msg' => 'transaction expired');
                } else if (int($resI['status']) == 302) {
                    $res = array('success' => false, 'err_code' => $resI['status'], 'msg' => 'error', 'err_msg' => 'transaction expired');
                } else if (int($resI['status']) == 303) {
                    $res = array('success' => false, 'err_code' => $resI['status'], 'msg' => 'error', 'err_msg' => 'already paid');
                } else {
                    $res = array('success' => false,'msg' => 'error','err_msg' => 'system busy');
                    #break;
                }

                $emailAlertParams = array('to' => self::DEVELOPER_EMAIL, 'subject' => 'Direct TopUp Payment Error Blanja ' . strtoupper($inputs['orderNumber']), 'message' => 'error code: ' . $resI['status']);
                $this->kirimEmail($emailAlertParams);

            }
        } catch (Exception $e) {
            $res = array('success' => false,'msg' => 'error', 'err_msg' => $e->getMessage());
            $emailAlertParams = array('to' => self::DEVELOPER_EMAIL, 'subject' => 'Direct TopUp Error Blanja ' . strtoupper($inputs['orderNumber']), 'message' => 'error details ' . $e->getMessage());
            $this->kirimEmail($emailAlertParams);
        }
        return $res;
    }
    private function checkDtuItem($item_code) {
        #$item_code = 'freefire_12';
        $p = array();
        $p['item_code'] = $item_code;
        $p['apiKey'] = self::DTU_ITEM_CHECK_API_APIKEY;

        $item = $this->curl_post(self::DTU_ITEM_CHECK_API_URL,$p);

        #echo '<pre>'.print_r($item,1).'</pre>';
    }
    private function templateEmailDtu($inputs = array()) {

        if (!is_array($inputs) || sizeof($inputs) < 1) {
            die("Forbidden");
        }
        /*$inputs['user_email'] = 'yana.supriatna@melon.co.id';
        $inputs['order_number'] = '418615705452510731';
        $inputs['trx_time'] = '14 September 2018, 13:05:46 WIB';
        $inputs['item_title'] = 'Wifiid 5.000';
        $inputs['item_price'] = 5000;
        $inputs['vsn'] = 'KodeVcr=dummy-vch-ugarena0-3885;Pass=abc-3885';*/

        $user_email = $inputs['user_email'];
        $order_number = $inputs['order_number'];
        $trx_time = $inputs['trx_time'];
        $item_title = $inputs['item_title'];
        $item_price = number_format($inputs['item_price'],0,',','.');
        $topupstatus = $inputs['topupstatus'];

        $template= <<<EOL
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta charset="utf-8" />
    <title>UPoint Voucher</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <!-- bootstrap & fontawesome -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css" integrity="sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb" crossorigin="anonymous">
</head>
<body>
<div style="background-color:#f7f7f7;color:#444;font-size:13px;font-family:'Helvetica Neue',helvetica,arial,sans-serif;margin:0;width:100%">
    <div style="padding-top:15px"></div>
    <div style="background-color:#f7f7f7;width:100%;min-width:590px;max-width:600px;margin:0 auto" align="center">
        <div class="m_-2768711487826518196brd-box" style="line-height:20px;font-size:14px;padding:20px;margin:0 10px;box-sizing:border-box;background-color:#ffffff;color:#666666;border:1px solid #e7e7e7;border-radius:4px">
            <div style="width:100%;text-align:left;margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid #e7e7e7"> <a href="https://upoint.id" target="_blank"><img width="120px" src="https://store.upoint.id/assets/image/favicon-96x96.png" style="width:64px" border="0" class="CToWUd"></a> </div>
            <div style="text-align:left">
                <h1 style="font-size:18px;color:#333333">Hai $user_email,</h1>
                <p style="margin:0">Terima kasih sudah berbelanja di Blanja.com dengan nomor pesanan $order_number.</p>
            </div>
            <div style="text-align:left;margin:20px 0 30px 0;border-top:1px solid #e7e7e7;"> <p style="margin:20px 0;color:#333333"> <span style="background-color:#e3211d;margin-right:5px">&nbsp;</span><b>Status TopUp </b> </p> <div style="padding:10px;border:1px solid #e7e7e7;background-color:#f0f0f0"> <div style="margin:0"><p style="margin:0">$topupstatus</p> </div> </div>
            </div>
            
            <div style="text-align:left;margin:20px 0 30px 0;border-top:1px solid #e7e7e7">
                <p style="margin:20px 0;color:#333333"> <span style="background-color:#e3211d;margin-right:5px">&nbsp;</span><b>Detil Blanjaan - $trx_time</b> </p>
                <div style="margin-bottom:10px">
                    <table width="100%">
                        <tbody>
                        <tr style="vertical-align:top;font-weight:bold">
                            <td width="50%">Nama Barang</td>
                            <td width="20%" align="right">Jumlah</td>
                            <td width="30%" align="right">Harga</td>
                        </tr>
                        </tbody>
                    </table>
                    <div style="margin:10px 0;padding:20px 0 0 0;border-top:1px solid #e7e7e7;border-bottom:1px solid #e7e7e7">
                        <table width="100%">
                            <tbody>
                            <tr style="vertical-align:top">
                                <td width="50%">
                                    <p style="margin:0">$item_title</p>
                                    <p style="margin:10px 0 20px 0">@Rp $item_price</p>
                                </td>
                                <td width="20%" align="right">1</td>
                                <td width="30%" align="right">Rp $item_price</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-bottom:10px;padding-bottom:10px;border-bottom:0px solid #e7e7e7">
                        <table width="100%">
                            <tbody>
                            <tr style="vertical-align:top">
                                <td width="70%" align="right">Blanjaan:</td>
                                <td width="30%" align="right">Rp $item_price</td>
                            </tr>
                            <tr style="vertical-align:top">
                                <td width="70%" align="right"><b>Total Pembayaran:</b></td>
                                <td width="30%" align="right"><b>Rp $item_price</b></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div style="text-align:left;margin:20px 0;padding:20px 0;border-top:1px solid #e7e7e7;border-bottom:1px solid #e7e7e7">
                <div style="display:table;width:100%">
                    <div style="display:table-cell;width:50px;vertical-align:middle;text-align:center"><img src="https://ci4.googleusercontent.com/proxy/0kZnvzk8n4S0_PP78crLUbr4tBfL5wWRWF5dUQEvEqCU_0_ZLuiIVZ-k6nIzVd3IUob-e-CbH0kHWCsqnu1lO6MmDAcdrzTIsY3mtpGc0h_3=s0-d-e1-ft#http://s2.blanja.com/static/images/public/email/fill-52.png" alt="" width="30px" style="width:30px" class="CToWUd"></div>
                    <div style="display:table-cell;text-align:left;font-size:12px">
                        <p style="margin:0;margin-left:10px">Harap tidak menginformasikan nomor kontak, e-mail, atau password Anda kepada siapapun dan TIDAK membuka link atau tautan apapun yang mengatasnamakan UPoint karena kami tidak pernah memberikan promo/hadiah apapun tanpa informasi resmi.</p>
                    </div>
                </div>
            </div>
            <div style="margin:20px 0;border-bottom:1px solid #e7e7e7">
                <div style="width:258px;margin-top:0;margin-bottom:20px;margin-left:auto;margin-right:auto">
                    <div style="display:block">
                        <p style="margin-bottom:10px">Ikuti Kami</p>
                        <div><a href="https://www.facebook.com/upoint.id/" style="text-decoration:none" target="_blank"><img src="https://ci6.googleusercontent.com/proxy/zgRV0tNynSgUYOurFvaROf668_tZKewwnA1QKqf3D1whMQsEXNQGYNVYe5M2LSlnVpFIp7FykRwmbc3WJrw2-0o_cHsR2GAlcS8ArnGHkylbMA=s0-d-e1-ft#http://s2.blanja.com/static/images/public/email/facebook.png" alt="Facebook" width="30px" style="margin-right:10px;width:30px" class="CToWUd"></a><a href="https://www.instagram.com/upoint.id/?hl=id" style="text-decoration:none" target="_blank"><img src="https://ci5.googleusercontent.com/proxy/asN20bw0grCjzk5LrHpRar461IB8qwymJopK_jyQmzw8vm7vuPnFHSusr5_dps_b_-rb3mFg3xxn-zFIF4fvqXsisC85779gmGGQRxzPSui7gxk=s0-d-e1-ft#http://s2.blanja.com/static/images/public/email/instagram.png" alt="Instagram" width="30px" style="margin-right:10px;width:30px" class="CToWUd"></a></div>
                    </div>
                </div>
            </div>
            <div style="font-style:italic;font-size:12px">
                <p style="margin:0">Punya pertanyaan? hubungi kami <a href="mailto:cs@upoint.co.id" style="color:#666666;text-decoration:underline" target="_blank">cs@upoint.co.id</a></p>
                <p style="margin:0">Customer Service : 021 - 79187250</p>
            </div>
        </div>
        <div style="margin:10px;color:#999999;font-size:10px;padding-bottom:15px">
            <p style="margin:0">Harap jangan membalas e-mail ini, karena e-mail ini dikirimkan secara otomatis oleh sistem.</p>
            <p style="margin:15px 0 0 0;font-size:12px;color:#666666">Copyright 2018 © UPoint. All Rights Reserved. | <a href="mailto:cs@upoint.co.id" style="color:#ea5a25" target="_blank">cs@upoint.co.id</a></p>
        </div>
    </div>
    <div style="padding-bottom:15px"></div>
    <img src="https://ci6.googleusercontent.com/proxy/HuwyvrcsPlZX3-bIqF3kEdmseFktaMeYgOW0M79JqY2oiH-U2BZYEVBxdly3zCarEAcpmguXTXhmodTRk-WqyRKgbRVzbzkLvQXPJUqOXHsgu4QYYtAR2Cis6bwMfV3e5nGy_qhIezmO5Ow=s0-d-e1-ft#https://mandrillapp.com/track/open.php?u=30259155&amp;id=dc1dfbb25d854fd488ff2c4c0b1d6dc7" height="1" width="1" class="CToWUd">
</div>
</body>
</html>
EOL;

        return $template;
        #$this->load->view('v_email',$tplVars);
    }

    function curl_post($url, array $post = array(), array $options = array(), &$err = null, $countRetry = 0)
    {
        Log4php::getLogger()->debug($this->logId." curl_post : ".json_encode($url));
        if ($url != 'http://192.168.1.4/bulk_vcr/index.php/alert')
            Log4php::getLogger()->debug($this->logId." curl_post : ".json_encode($post));

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
            Log4php::getLogger()->debug($this->logId." curl_post error : ".json_encode($info));
            if (intval($info['http_code']) != 200) {
                $err .= "error:" . @curl_error($ch) . " -- " . $info['http_code'];
            }
        }

        if ($url != 'http://192.168.1.4/bulk_vcr/index.php/alert')
            Log4php::getLogger()->debug($this->logId." curl_post result : ".$result);

        curl_close($ch);
        return $result;
    }

}