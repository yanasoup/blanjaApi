<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
#error_reporting(E_ALL);
class Catalog extends CI_Controller
{

    const PARTNER_ID = "59";
    const PARTNER_SECRET_TOKEN = "9e870d60f86e0c3edc3a3de434979a9471caf9ce";
    const VOUCHER_REQUEST_URL = "http://192.168.1.6/voucherFeeder/host_to_host/request";
    const VOUCHER_CONFIRM_URL = "http://192.168.1.6/voucherFeeder/host_to_host/confirm";

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


    function __construct() {
        ini_set("display_errors",false);
        parent::__construct();
        $this->load->library('log4php','logger');
        $this->load->library('blanja');
        $this->load->helper(array('yana','string'));
        $this->logId = uniqid(rand());
        $this->load->model('Refno');
        $this->load->model('Transaction');
        $this->emailTpl = "Dear %s,<br /><br />";
        $this->emailTpl .= "Terima kasih sudah berbelanja di <a href='https://www.blanja.com' target='_blank'>Blanja.com</a><br />";
        $this->emailTpl .= "Berikut voucher digital anda : <strong>%s</strong>";
        $this->logisticCompanies = array(
            'DEFAULT' => 'FL',
            'DIGITAL_PRODUCT' => 'DIGITAL'
        );

    }

    public function index() {
        #die("OK");
        $tplVars = array();

        if (isset($_POST['btnsubmit']) && $_POST['btnsubmit']) {
            print_out($_FILES);
            print_out($_POST);
            $resUpload = $this->_upload_excel();
        }
        $this->load->view('parts/v_head');
        $this->load->view('v_catalog',$tplVars);
        $this->load->view('parts/v_foot');

    }

    private function _upload_excel() {
        ini_set('memory_limit','1024M');
        ini_set('max_execution_time',600);
        ini_set('display_errors',E_ALL);
        $this->load->library('excel');
        #$objPHPExcel = new PHPExcel();

        $file = $_FILES['file_excel']['tmp_name'];
        /*print_out($_FILES);
        $objPHPExcel = PHPExcel_IOFactory::load($file);
        print_out($objPHPExcel);*/


        try {
            $inputFileType = PHPExcel_IOFactory::identify($file);
            $objReader = PHPExcel_IOFactory::createReader($inputFileType);
            $objPHPExcel = $objReader->load($file);
        } catch(Exception $e) {
            die('Error loading file "'.pathinfo($file,PATHINFO_BASENAME).'": '.$e->getMessage());
        }

        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        #print_out($highestRow);
        #print_out($highestColumn);
        $itemsDetail = array();

        $message = "";
        $res = array('message' => '','updated' => 0, 'inserted' => 0);
        for ($row = 0; $row <= $highestRow; $row++){
            $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
                NULL,
                TRUE,
                FALSE);

            #print_out($rowData);
            if ($row > 1) {
                try {
                    print_out($rowData);
                    $itemsDetail['item'] = array(
                        //'categoryId' => 20100677, //Digital Voucher
                        'categoryId' => 20010953, //e-Voucher >> Entertainment & Apps e-Voucher >> App Credits
                        'title' => $rowData[0][0],
                        'price' => $rowData[0][2],
                        //'listPrice' => $rowData[0][3], // harga coret
                        'state' => 14,
                        'city' => 1410,
                        'district' => 141010,
                        'weight' => 1,
                        'length' => 1,
                        'width' => 1,
                        'height' => 1,
                        'freightPayer' => 'seller',
                        'description' => $rowData[0][1],
                        'supportPOD' => 'false',
                    );

                    $itemsDetail['sku'] = array(
                        'sku' => array(
                            'outerCode' => $rowData[0][4],
                            'quantity' => $rowData[0][5],
                            'price' => $rowData[0][6]
                        )
                    );
                    $itemsDetail['itemImage'] = array(
                        'itemImage' => array(
                            'url' => $rowData[0][7],
                            'position' => 1,
                        )
                    );

                    $resUpload = $this->postItemtoBlanja($itemsDetail);
                } catch (Exception $e) {
                    print_out($e->getMessage(),0);
                    continue;
                }
            } else {
                $res['success'] = false;
                $res['inserted'] = 0;
                $res['updated'] = 0;
                $res['message'] = "Kolom tidak lengkap";
            }
        }

        return $res;
    }

    public function postItemtoBlanja($itemDetail) {
        $params = array();
        $csvString = "";
        #print_out($obj);
        $item = $itemDetail['item'];
        $xml_item = new SimpleXMLElement("<item></item>");
        array_to_xml($item,$xml_item);
        $xmlItem = $xml_item->asXML();
        $params['item'] = (string)$xmlItem;

        $sku = $itemDetail['sku'];
        $xml_sku = new SimpleXMLElement("<skus></skus>");
        array_to_xml($sku,$xml_sku);
        $xmlSku = $xml_sku->asXML();
        $params['skus'] = (string)$xmlSku;

        $itemImage = $itemDetail['itemImage'];
        $xml_item_images = new SimpleXMLElement("<itemImages></itemImages>");
        array_to_xml($itemImage,$xml_item_images);
        $xmlItemImages = $xml_item_images->asXML();
        $params['itemImages'] = (string)$xmlItemImages;

        #print_out($params);
        $resUpl = $this->blanja->itemCreate($params);
        $xml = new SimpleXMLElement($resUpl);
        $obj = json_decode(json_encode($xml));
        Log4php::getLogger()->debug("upload res : ".json_encode($obj));
        return $obj;

    }
    public function orders()
    {
        $params = array();
        if (isset($_GET['status'])) {
            $params['status'] = strtoupper($_GET['status']);
        }
        #$params['status'] = 'PAID';
        $params['productType'] = 'DIGITAL_PRODUCT';
        #$params['orderDateFrom'] = date("d/m/Y",strtotime("-1 days"));
        #$params['orderDateTo'] = date("d/m/Y");
        if (isset($_GET['orderNumber'])) {
            $params['orderNumber'] = $_GET['orderNumber'];
        }
        $params['pageSize'] = 50;
        #print_out($params,0);
        $xml = new SimpleXMLElement($this->blanja->getOrders($params));
        $apiResult = json_decode(json_encode($xml));

        print_out($apiResult,0);
        if (is_object($apiResult) && property_exists($apiResult,'status') && strtoupper($apiResult->status)=='SUCCESS') {

            if (!property_exists($apiResult->result->orders,'order')) {
                print_out("No Order",0);
            }
            if (is_object($apiResult->result->orders->order)) {
                #kemungkinan ordernya cuma 1
                if (property_exists($apiResult->result->orders->order,'orderNumber')) {
                    $orderList = array($apiResult->result->orders->order);
                } else {
                    $orderList = array();
                }
                print_out("Object (1 order)");
                print_out($orderList,0);

            } else if (is_array($apiResult->result->orders->order)) {
                $orderList = $apiResult->result->orders->order;
                print_out("array (lebih dari 1 order)");
                print_out($orderList,0);

            }


        }
        print_out($apiResult);
    }
    public function getOrdersOld() {
        /** CRON TAB ADA DI USER www-data
         * edit : sudo su -c "crontab -e" www-data
         */
        Log4php::getLogger()->debug(sprintf("%s ======= GET ORDERS START =======",$this->logId));
        $params = array();
        $params['status'] = 'PAID';
        $params['productType'] = 'DIGITAL_PRODUCT';
        $xml = new SimpleXMLElement($this->blanja->getOrders($params));
        $apiResult = json_decode(json_encode($xml));
        #print_out($apiResult,0);
        #Log4php::getLogger()->debug("ORDERS XML : ".json_encode($orders));
        if (is_object($apiResult) && property_exists($apiResult,'status') && strtoupper($apiResult->status)=='SUCCESS') {

            if (!property_exists($apiResult->result->orders,'order')) {
                Log4php::getLogger()->debug(sprintf("%s No PAID Orders",$this->logId));
                Log4php::getLogger()->debug(sprintf("%s ======= GET ORDERS END =======",$this->logId));
                exit(0);
            }

            if (is_object($apiResult->result->orders->order)) {
                #kemungkinan ordernya cuma 1
                # jika cuma 1 order, struktur xml objectnya :
                # $apiResult->result->orders->order->orderNumber
                if (property_exists($apiResult->result->orders->order,'orderNumber')) {
                    $orderList = array($apiResult->result->orders->order);
                } else {
                    $orderList = array();
                }
                #print_out("Object (1 order)");
                #print_out($orderList,0);
            } else if (is_array($apiResult->result->orders->order)) {
                # jika order lebih dari 1, struktur xml objectnya :
                # $apiResult->result->orders->order->orderNumber[indexArray]->orderNumber
                $orderList = $apiResult->result->orders->order;
                #print_out("array (lebih dari 1 order)");
                #print_out($orderList,0);
            }

            if (sizeof($orderList) < 1) {
                Log4php::getLogger()->debug(sprintf("%s No Orders",$this->logId));
                Log4php::getLogger()->debug(sprintf("%s ======= GET ORDERS END =======",$this->logId));
                echo "No Orders".PHP_EOL;
                exit(0);
            }

            #$orderList = $apiResult->result->orders;
            #print_out($orderList,0);
            $i = 0;
            foreach($orderList as $order) {
                #print_out($order,0);
                #Log4php::getLogger()->debug(sprintf("Order line ..".json_encode($order)));
                #print_out($ol,0);
                $qty = $order->orderLines->orderLine->quantity;
                if (intval($qty) > 1) {
                    #print_out("unable to handle more than 1 qty",0);
                    Log4php::getLogger()->debug(sprintf("%s - order %s skipped. qty more than 1",$this->logId,$order->orderNumber));
                    $msgParams=array(
                        'orderNumber' => $order->orderNumber,
                        'message' => 'saat ini sistem kami belum bisa memproses qty lebih dari 1 per order. silahkan isi 1 qty per order'
                    );
                    $xml = new SimpleXMLElement($this->blanja->sendSellerMessage($msgParams));
                    continue;
                }
                try {
                    #if ($olRow->orderNumber == '418598549547932529') {
                    #$olRow->buyerEmail = 'yana.supriatna@melon.co.id';
                    #$olRow->buyerEmail = 'firman.firdaus@melon.co.id';
                    $Transaction = Transaction::getByOrderNumber($order->orderNumber)->row();
                    if ($Transaction) {
                        $kini = date('Y-m-d H:i:s');
                        $expDate = date_create($Transaction->expire_date);
                        date_add($expDate, date_interval_create_from_date_string('-10 minutes'));
                        $tenMinBeforeExpire = date_format($expDate, 'Y-m-d H:i:s');
                        if ($kini > $tenMinBeforeExpire) {
                            Log4php::getLogger()->debug(sprintf("%s - Transaction %s 10 minute to expire or already expire %s %s skipped",$this->logId,$order->orderNumber,$kini,$tenMinBeforeExpire));
                            continue;
                        }

                        # jika sudah confirm, tapi blm di ship ke blanja
                        if($Transaction->accepted && !$Transaction->shipped) {
                            try {
                                /** CALLBACK KE BELANJA /v1/ship */
                                Log4php::getLogger()->debug(sprintf("%s - RE-SHIP ORDER to blanja",$this->logId));
                                $shipParams = array();
                                $shipParams['orderNumber'] = $Transaction->orderNumber;
                                $logistic = in_array($order->orderLines->orderLine->productType,array_keys($this->logisticCompanies)) ? $this->logisticCompanies[$order->orderLines->orderLine->productType] : 'DIGITAL';
                                $shipParams['logisticsCompany'] = $logistic;
                                $shipParams['invoiceNumber'] = $Transaction->invoiceno;
                                $shipRes = $this->shipOrder($shipParams);
                                Log4php::getLogger()->debug(sprintf("%s - SHIP RES : %s",$this->logId,json_encode($shipRes)));
                                if (is_object($shipRes) && property_exists($shipRes,'status')) {
                                    if (strtoupper($shipRes->status) == 'SUCCESS') {
                                        $trxBlanja = array();
                                        #$trxBlanja['id'] = $Transaction->id;
                                        $trxBlanja['orderNumber'] = $Transaction->orderNumber;
                                        $trxBlanja['upointH2hReqRefno'] = $Transaction->upointH2hReqRefno;
                                        $trxBlanja['shipped'] = 1;
                                        Transaction::updateTrx($trxBlanja);

                                        /** KIRIM EMAIL KE user */
                                        Log4php::getLogger()->debug(sprintf("%s - RE-SENDING EMAIL",$this->logId));
                                        $emailTplVars = array(
                                            'user_email' => $Transaction->buyerEmail,
                                            'order_number' => $Transaction->orderNumber,
                                            'trx_time' => $Transaction->transactionTime,
                                            'item_title' => $Transaction->itemTitle,
                                            'item_price' => $Transaction->amount,
                                            'vsn' => $Transaction->voucher,
                                        );
                                        $emailHtml = $this->templateEmail($emailTplVars);
                                        $emailParams = array('to' => $Transaction->buyerEmail,'subject' => 'Voucher '.strtoupper($Transaction->itemTitle).' Order Blanja '.$Transaction->orderNumber,'message' => $emailHtml);

                                        try {
                                            $resEmail = $this->kirimEmail($emailParams);
                                            $objResEmail = json_decode($resEmail);
                                            if (is_object($objResEmail) && property_exists($objResEmail,'email_sent') && $objResEmail->email_sent == true) {
                                                $trxBlanja = array();
                                                #$trxBlanja['id'] = $Transaction->id;
                                                $trxBlanja['orderNumber'] = $Transaction->orderNumber;
                                                $trxBlanja['upointH2hReqRefno'] = $Transaction->upointH2hReqRefno;
                                                $trxBlanja['email_sent'] = 1;
                                                Transaction::updateTrx($trxBlanja);
                                                Log4php::getLogger()->debug(sprintf("%s - RE-SENDING EMAIL SUCCESS",$this->logId));
                                            }
                                        } catch (Exception $e) {
                                            Log4php::getLogger()->debug(sprintf("%s - EMAIL NOT SENT TO %s ORDER NO : %s".$this->logId,$emailParams['to'],$Transaction->orderNumber));

                                        }

                                    }
                                } else {
                                    Log4php::getLogger()->debug(sprintf("%s - failed to ship to blanja ORDER NO : %s",$this->logId,$Transaction->orderNumber));
                                }
                            } catch (Exception $e) {
                                Log4php::getLogger()->debug(sprintf("%s - failed to ship to blanja ORDER NO : %s ",$this->logId,$Transaction->orderNumber));
                                Log4php::getLogger()->debug(sprintf("%s - SHIP RES : %s",$this->logId,json_encode($shipRes)));

                            }
                        }

                        #Log4php::getLogger()->debug(sprintf("order %s already processed skipped...",$Transaction->orderNumber));
                        continue;
                    }

                    $params['buyerEmail'] = $order->buyerEmail;
                    $params['orderNumber'] = $order->orderNumber;
                    $params['receiverMobile'] = $order->receiverMobile;
                    $params['receiverName'] = $order->buyerEmail;
                    $params['transactionTime'] = $order->orderLines->orderLine->created;

                    $kini = date('Y-m-d H:i:s');
                    $expDate = date_create($kini);
                    date_add($expDate, date_interval_create_from_date_string('1 hours'));
                    $params['expire_date'] = date_format($expDate, 'Y-m-d H:i:s');
                    $params['skuCode'] = $order->orderLines->orderLine->skuCode;
                    $params['itemTitle'] = $order->orderLines->orderLine->itemTitle;
                    $params['productType'] = $order->orderLines->orderLine->productType;
                    $params['amount'] = $order->orderLines->orderLine->price;



                    Log4php::getLogger()->debug(sprintf("%s - ========================= PROSES %s %s %s ===============================",$this->logId,$order->orderNumber,$order->orderLines->orderLine->skuCode,$order->buyerEmail));
                    Log4php::getLogger()->debug(sprintf("%s - sendNewVoucherRequest params %s",$this->logId,json_encode($params)));
                    #print_out($params,0);
                    $resVch = $this->sendNewVoucherRequest($params);
                    #print_out($res);
                    #}
                } catch (Exception $e) {
                    Log4php::getLogger()->debug($this->logId." ERROR ".$e->getMessage());
                }
            }

        }
        Log4php::getLogger()->debug(sprintf("%s ======= GET ORDERS END =======",$this->logId));
        echo "DONEs";
    }
    public function sendNewVoucherRequest($inputs) {
        #ini_set('max_execution_time',300);
        $res = array('success' => false,'msg' => '');

        $secret_key = self::PARTNER_SECRET_TOKEN;
        $loop = 1;
        $item_code = $inputs['skuCode'];
        //$item_code = "UGARENA0";
        $partner_id = self::PARTNER_ID;
        $vouchers = array();

        try {
            $refnoId = Refno::getId();
            #$pad = "blanjah2h".random_string('alnum', (30-strlen($refnoId)));
            #$ref_no = substr($pad.$refnoId,0,30);
            $prefix = "blanjah2h";
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
            $trxBlanja['amount_payment'] = $inputs['amount'];
            $trxBlanja['upointH2hReqRefno'] = $ref_no;
            $rowId = Transaction::insertTrx($trxBlanja);


            $paramsI = array(
                'partner_id' => $partner_id,
                'ref_no' => $ref_no,
                'item_code' => $item_code,
                'timestamp' => 30,
                'secret_token' => $secret_key,
                'signature' => md5($partner_id . $ref_no . $item_code . '30' . $secret_key)
            );
            Log4php::getLogger()->debug(sprintf($this->logId . " VOUCHER_REQUEST PARAMS %s", json_encode($paramsI)));

            $jsonI = $this->curl_post(self::VOUCHER_REQUEST_URL, $paramsI);
            $resI = json_decode($jsonI, 1);
            Log4php::getLogger()->debug(sprintf($this->logId . " VOUCHER_REQUEST RES %s", json_encode($resI)));


            # ==== TES RESPONSE OUT OF STOCK
            #$resI = array();
            #$resI['status'] = '0';
            #$resI['error_code'] = 'E004';
            # ==== END TES RESPONSE OUT OF STOCK

            #if (array_key_exist('status',$resI) && $resI['status'] == '1') {
            if ($resI['status']) {
                $trx_id = $resI['trx_id'];
                $paramsII = array(
                    'partner_id' => $partner_id,
                    'trx_id' => $trx_id,
                    'ref_no' => $ref_no,
                    'status' => 1,
                    'secret_token' => $secret_key,
                    'signature' => md5($partner_id . $trx_id . $ref_no . '1' . $secret_key)
                );
                Log4php::getLogger()->debug(sprintf($this->logId . " VOUCHER_CONFIRM PARAMS %s", json_encode($paramsII)));

                $jsonII = $this->curl_post(self::VOUCHER_CONFIRM_URL, $paramsII);
                $resII = json_decode($jsonII, 1);
                $vouchers[] = $resII['item'][0]['value'];
                $arr = array("", "");
                $invNo = $ref_no;
                if (strpos($resII['item'][0]['value'], ";")) {
                    $arr = explode(";", $resII['item'][0]['value']);
                    #$invNo = $arr[0]."-".$arr[1];
                } else {
                    $arr = array($resII['item'][0]['value'], "");
                    #$invNo = $arr[0];
                }

                Log4php::getLogger()->debug(sprintf($this->logId . " VOUCHER_CONFIRM RES %s", json_encode($resII)));

                $trxBlanja = array();
                #$trxBlanja['id'] = $rowId;
                $trxBlanja['accepted'] = 1;
                $trxBlanja['voucher'] = $resII['item'][0]['value'];
                $trxBlanja['invoiceno'] = $invNo;
                $trxBlanja['updated_at'] = date('Y-m-d H:i:s');
                $trxBlanja['orderNumber'] = $inputs['orderNumber'];
                $trxBlanja['upointH2hReqRefno'] = $ref_no;
                Transaction::updateTrx($trxBlanja);

                try {
                    /** CALLBACK KE BELANJA /v1/ship */
                    $shipParams = array();
                    $shipParams['orderNumber'] = $inputs['orderNumber'];
                    $logistic = in_array($inputs['productType'],array_keys($this->logisticCompanies)) ? $this->logisticCompanies[$inputs['productType']] : 'DIGITAL';
                    $shipParams['logisticsCompany'] = $logistic;
                    $shipParams['invoiceNumber'] = $invNo;
                    $shipRes = $this->shipOrder($shipParams);
                    Log4php::getLogger()->debug(sprintf("%s - SHIP RES : %s ",$this->logId,json_encode($shipRes)));
                    if (is_object($shipRes) && property_exists($shipRes,'status')) {
                        if (strtoupper($shipRes->status) == 'SUCCESS') {
                            $trxBlanja = array();
                            #$trxBlanja['id'] = $rowId;
                            $trxBlanja['shipped'] = 1;
                            $trxBlanja['updated_at'] = date('Y-m-d H:i:s');
                            $trxBlanja['orderNumber'] = $inputs['orderNumber'];
                            $trxBlanja['upointH2hReqRefno'] = $ref_no;
                            Transaction::updateTrx($trxBlanja);

                            try {
                                /** KIRIM EMAIL voucher KE User */
                                Log4php::getLogger()->debug(sprintf("%s - SENDING EMAIL",$this->logId));

                                $emailTplVars = array(
                                    'user_email' => $inputs['buyerEmail'],
                                    'order_number' => $inputs['orderNumber'],
                                    'trx_time' => $inputs['transactionTime'],
                                    'item_title' => $inputs['itemTitle'],
                                    'item_price' => $inputs['amount'],
                                    'vsn' => $resII['item'][0]['value'],
                                );
                                $emailHtml = $this->templateEmail($emailTplVars);
                                #$emailParams = array('to' => $inputs['buyerEmail'],'subject' => 'Voucher '.strtoupper($inputs['itemTitle']).' Order Blanja '.$inputs['orderNumber'],'message' => sprintf($this->emailTpl,$inputs['buyerEmail'],$resII['item'][0]['value']));
                                $emailParams = array('to' => $inputs['buyerEmail'],'subject' => 'Voucher '.strtoupper($inputs['itemTitle']).' Order Blanja '.$inputs['orderNumber'],'message' => $emailHtml);
                                $resEmail = $this->kirimEmail($emailParams);
                                $objResEmail = json_decode($resEmail);
                                Log4php::getLogger()->debug(sprintf("%s - RES EMAIL : %s",$this->logId,$resEmail));
                                if (is_object($objResEmail) && property_exists($objResEmail,'email_sent') && $objResEmail->email_sent == true) {
                                    $trxBlanja = array();
                                    #$trxBlanja['id'] = $rowId;
                                    $trxBlanja['email_sent'] = 1;
                                    $trxBlanja['updated_at'] = date('Y-m-d H:i:s');
                                    $trxBlanja['orderNumber'] = $inputs['orderNumber'];
                                    $trxBlanja['upointH2hReqRefno'] = $ref_no;
                                    Transaction::updateTrx($trxBlanja);
                                    Log4php::getLogger()->debug(sprintf("%s - EMAIL SENT",$this->logId));
                                }
                            } catch (Exception $e) {
                                Log4php::getLogger()->debug(sprintf("%s - EMAIL NOT SENT TO %s ORDER NO : %s",$this->logId,$emailParams['to'],$inputs['orderNumber']));
                            }

                            $res = array('success' => true,'msg' => 'sukses');
                        } else {
                            $res = array('success' => false,'msg' => $shipRes->message);
                        }
                    } else {
                        $emailAlertParams = array('to' => self::DEVELOPER_EMAIL,'subject' => 'Failed to Ship to Blanja '.strtoupper($inputs['orderNumber']),'message' => 'failed to ship to blanja order no : '.$inputs['orderNumber']);
                        $resAlert = $this->kirimEmail($emailAlertParams);

                        Log4php::getLogger()->debug(sprintf("%s - failed to ship to blanja ORDER NO : %s",$this->logId,$inputs['orderNumber']));
                        $res = array('success' => false,'msg' => "system busy");
                    }

                } catch (Exception $e) {
                    Log4php::getLogger()->debug(sprintf("%s - ship failed ORDER NO : %s",$this->logId,$inputs['orderNumber']));
                    $emailAlertParams = array('to' => self::DEVELOPER_EMAIL,'subject' => 'Failed to Ship to Blanja '.strtoupper($inputs['orderNumber']),'message' => 'failed to ship to blanja order no : '.$inputs['orderNumber']);
                    $resAlert = $this->kirimEmail($emailAlertParams);
                }

            } else {
                /** E004 : OUT OF STOCK */
                if ($resI['error_code'] == 'E004') {
                    /** callback ke blanja /v1/ sellerMessage */
                    $msgParams=array(
                        'orderNumber' => $inputs['orderNumber'],
                        'message' => 'Item '.$inputs['itemTitle'].' is Out Of Stock'
                    );

                    $xml = new SimpleXMLElement($this->blanja->sendSellerMessage($msgParams));
                    $obj = json_decode(json_encode($xml));

                    $emailAlertParams = array('to' => self::DEVELOPER_EMAIL,'subject' => 'Out Of Stock Blanja '.strtoupper($inputs['orderNumber']),'message' => 'Out of Stock blanja order no : '.$inputs['orderNumber']);
                    $resAlert = $this->kirimEmail($emailAlertParams);

                    $res = array('success' => false, 'msg' => 'Item Out Of Stock', 'err_msg' => 'E004 : Item Out Of Stock');
                    #break;
                } else {
                    $res = array('success' => false,'msg' => 'Error','err_msg' => $resI['error_info']);
                    #break;
                }
            }
            #}
        } catch (Exception $e) {
            $res = array('success' => false,'msg' => '', 'err_msg' => $e->getMessage());
        }
        return $res;

        #echo '<pre>'.print_r($vouchers,1).'</pre>';
    }
    public function sendNewVoucherRequestOnly($inputs) {
        #ini_set('max_execution_time',300);
        $res = array('success' => false,'msg' => '');

        $secret_key = self::PARTNER_SECRET_TOKEN;
        $loop = 1;
        $item_code = $inputs['skuCode'];
        //$item_code = "UGARENA0";
        $partner_id = self::PARTNER_ID;
        $vouchers = array();

        try {
            $refnoId = Refno::getId();
            #$pad = "blanjah2h".random_string('alnum', (30-strlen($refnoId)));
            #$ref_no = substr($pad.$refnoId,0,30);
            $prefix = "blanjah2h";
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
            $trxBlanja['upointH2hReqRefno'] = $ref_no;
            $rowId = Transaction::insertTrx($trxBlanja);


            $paramsI = array(
                'partner_id' => $partner_id,
                'ref_no' => $ref_no,
                'item_code' => $item_code,
                'timestamp' => 30,
                'secret_token' => $secret_key,
                'signature' => md5($partner_id . $ref_no . $item_code . '30' . $secret_key)
            );
            Log4php::getLogger()->debug(sprintf($this->logId . " VOUCHER_REQUEST PARAMS %s", json_encode($paramsI)));

            $jsonI = $this->curl_post(self::VOUCHER_REQUEST_URL, $paramsI);
            $resI = json_decode($jsonI, 1);
            Log4php::getLogger()->debug(sprintf($this->logId . " VOUCHER_REQUEST RES %s", json_encode($resI)));


            # ==== TES RESPONSE OUT OF STOCK
            #$resI = array();
            #$resI['status'] = '0';
            #$resI['error_code'] = 'E004';
            # ==== END TES RESPONSE OUT OF STOCK

            #if (array_key_exist('status',$resI) && $resI['status'] == '1') {
            if ($resI['status']) {
                $trx_id = $resI['trx_id'];
                $paramsII = array(
                    'partner_id' => $partner_id,
                    'trx_id' => $trx_id,
                    'ref_no' => $ref_no,
                    'status' => 1,
                    'secret_token' => $secret_key,
                    'signature' => md5($partner_id . $trx_id . $ref_no . '1' . $secret_key)
                );
                Log4php::getLogger()->debug(sprintf($this->logId . " VOUCHER_CONFIRM PARAMS %s", json_encode($paramsII)));

                $jsonII = $this->curl_post(self::VOUCHER_CONFIRM_URL, $paramsII);
                $resII = json_decode($jsonII, 1);
                $vouchers[] = $resII['item'][0]['value'];
                $arr = array("", "");
                $invNo = $ref_no;
                if (strpos($resII['item'][0]['value'], ";")) {
                    $arr = explode(";", $resII['item'][0]['value']);
                    #$invNo = $arr[0]."-".$arr[1];
                } else {
                    $arr = array($resII['item'][0]['value'], "");
                    #$invNo = $arr[0];
                }

                Log4php::getLogger()->debug(sprintf($this->logId . " VOUCHER_CONFIRM RES %s", json_encode($resII)));

                $trxBlanja = array();
                #$trxBlanja['id'] = $rowId;
                $trxBlanja['accepted'] = 1;
                $trxBlanja['voucher'] = $resII['item'][0]['value'];
                $trxBlanja['invoiceno'] = $invNo;
                $trxBlanja['updated_at'] = date('Y-m-d H:i:s');
                $trxBlanja['orderNumber'] = $inputs['orderNumber'];
                $trxBlanja['upointH2hReqRefno'] = $ref_no;
                Transaction::updateTrx($trxBlanja);

                try {
                    $trxBlanja = array();
                    #$trxBlanja['id'] = $rowId;
                    $trxBlanja['shipped'] = 1;
                    $trxBlanja['updated_at'] = date('Y-m-d H:i:s');
                    $trxBlanja['OrderNumber'] = $inputs['orderNumber'];
                    $trxBlanja['upointH2hReqRefno'] = $ref_no;
                    Transaction::updateTrx($trxBlanja);

                    try {
                        /** KIRIM EMAIL voucher KE User */
                        Log4php::getLogger()->debug(sprintf("%s - SENDING EMAIL",$this->logId));

                        $emailTplVars = array(
                            'user_email' => $inputs['buyerEmail'],
                            'order_number' => $inputs['orderNumber'],
                            'trx_time' => $inputs['transactionTime'],
                            'item_title' => $inputs['itemTitle'],
                            'item_price' => $inputs['amount'],
                            'vsn' => $resII['item'][0]['value'],
                        );
                        $emailHtml = $this->templateEmail($emailTplVars);
                        #$emailParams = array('to' => $inputs['buyerEmail'],'subject' => 'Voucher '.strtoupper($inputs['itemTitle']).' Order Blanja '.$inputs['orderNumber'],'message' => sprintf($this->emailTpl,$inputs['buyerEmail'],$resII['item'][0]['value']));
                        $emailParams = array('to' => $inputs['buyerEmail'],'subject' => 'Voucher '.strtoupper($inputs['itemTitle']).' Order Blanja '.$inputs['orderNumber'],'message' => $emailHtml);
                        $resEmail = $this->kirimEmail($emailParams);
                        $objResEmail = json_decode($resEmail);
                        Log4php::getLogger()->debug(sprintf("%s - RES EMAIL : %s",$this->logId,$resEmail));
                        if (is_object($objResEmail) && property_exists($objResEmail,'email_sent') && $objResEmail->email_sent == true) {
                            $trxBlanja = array();
                            #$trxBlanja['id'] = $rowId;
                            $trxBlanja['email_sent'] = 1;
                            $trxBlanja['updated_at'] = date('Y-m-d H:i:s');
                            $trxBlanja['OrderNumber'] = $inputs['orderNumber'];
                            $trxBlanja['upointH2hReqRefno'] = $ref_no;
                            Transaction::updateTrx($trxBlanja);
                            Log4php::getLogger()->debug(sprintf("%s - EMAIL SENT",$this->logId));
                        }
                    } catch (Exception $e) {
                        Log4php::getLogger()->debug(sprintf("%s - EMAIL NOT SENT TO %s ORDER NO : %s",$this->logId,$emailParams['to'],$inputs['orderNumber']));
                    }

                    $res = array('success' => true,'msg' => 'sukses');

                } catch (Exception $e) {
                    Log4php::getLogger()->debug(sprintf("%s - ship failed ORDER NO : %s",$this->logId,$inputs['orderNumber']));
                    $emailAlertParams = array('to' => self::DEVELOPER_EMAIL,'subject' => 'Failed to Ship to Blanja '.strtoupper($inputs['orderNumber']),'message' => 'failed to ship to blanja order no : '.$inputs['orderNumber']);
                    $resAlert = $this->kirimEmail($emailAlertParams);
                }

            } else {
                /** E004 : OUT OF STOCK */
                if ($resI['error_code'] == 'E004') {
                    /** callback ke blanja /v1/ sellerMessage */
                    $msgParams=array(
                        'orderNumber' => $inputs['orderNumber'],
                        'message' => 'Item '.$inputs['itemTitle'].' is Out Of Stock'
                    );

                    $xml = new SimpleXMLElement($this->blanja->sendSellerMessage($msgParams));
                    $obj = json_decode(json_encode($xml));

                    $emailAlertParams = array('to' => self::DEVELOPER_EMAIL,'subject' => 'Out Of Stock Blanja '.strtoupper($inputs['orderNumber']),'message' => 'Out of Stock blanja order no : '.$inputs['orderNumber']);
                    $resAlert = $this->kirimEmail($emailAlertParams);

                    $res = array('success' => false, 'msg' => 'Item Out Of Stock', 'err_msg' => 'E004 : Item Out Of Stock');
                    #break;
                } else {
                    $res = array('success' => false,'msg' => 'Error','err_msg' => $resI['error_info']);
                    #break;
                }
            }
            #}
        } catch (Exception $e) {
            $res = array('success' => false,'msg' => '', 'err_msg' => $e->getMessage());
        }
        return $res;

        #echo '<pre>'.print_r($vouchers,1).'</pre>';
    }
    public function resentVoucherEmail() {
        Log4php::getLogger()->debug(sprintf("%s ======= resentVoucherEmail job start =======",$this->logId));
        $Transactions = Transaction::getUnsentVoucher()->result();
        if (sizeof($Transactions) > 0) {
            foreach($Transactions as $t) {
                /** KIRIM EMAIL KE user */
                Log4php::getLogger()->debug(sprintf("%s RE-SENDING EMAIL %s ORDER %s",$this->logId,$t->buyerEmail,$t->orderNumber));
                $emailTplVars = array(
                    'user_email' => $t->buyerEmail,
                    'order_number' => $t->orderNumber,
                    'trx_time' => $t->transactionTime,
                    'item_title' => $t->itemTitle,
                    'item_price' => $t->amount_payment,
                    'vsn' => $t->voucher,
                );
                $emailHtml = $this->templateEmail($emailTplVars);
                #$emailParams = array('to' => $t->buyerEmail,'subject' => 'Voucher '.strtoupper($t->itemTitle).' Order Blanja '.$t->orderNumber,'message' => sprintf($this->emailTpl,$t->buyerEmail,$t->voucher));
                $emailParams = array('to' => $t->buyerEmail,'subject' => 'Voucher '.strtoupper($t->itemTitle).' Order Blanja '.$t->orderNumber,'message' => $emailHtml);

                try {
                    $resEmail = $this->kirimEmail($emailParams);
                    $objResEmail = json_decode($resEmail);
                    if (is_object($objResEmail) && property_exists($objResEmail,'email_sent') && $objResEmail->email_sent == true) {
                        $trxBlanja = array();
                        $trxBlanja['email_sent'] = 1;
                        $trxBlanja['orderNumber'] = $t->orderNumber;
                        $trxBlanja['upointH2hReqRefno'] = $t->upointH2hReqRefno;
                        Transaction::updateTrx($trxBlanja);
                        Log4php::getLogger()->debug(sprintf("%s RE-SENDING EMAIL %s SUCCESS",$this->logId,$t->buyerEmail));
                    }
                } catch (Exception $e) {
                    Log4php::getLogger()->debug(sprintf("%s EMAIL NOT SENT TO %s ORDER NO : %s".$this->logId,$emailParams['to'],$t->orderNumber));

                }

            }
        } else {
            Log4php::getLogger()->debug(sprintf("%s No unsent order",$this->logId));
        }
        Log4php::getLogger()->debug(sprintf("%s ======= resentVoucherEmail job end =======",$this->logId));
    }
    public function shipOrder($inputs) {
        $xml = new SimpleXMLElement($this->blanja->shipOrder($inputs));
        $shipRes = json_decode(json_encode($xml));
        return $shipRes;
    }

    public function kirimEmail($inputs) {
        #$params['to'] = "yana.supriatna@melon.co.id";
        $params['to'] = $inputs['to'];
        $params['subject'] = $inputs['subject'];
        $params['message'] = $inputs['message'];
        #$params['subject'] = 'tes';
        #$params['message'] = 'tes kirim email';
        $url = "http://192.168.1.4/bulk_vcr/index.php/alert";
        $res = $this->curl_post($url,$params);

        return $res;

    }
    public function kirimEmailManual() {

        $refno = $_GET['refno'];

        if ($refno == '') {
            die("invalid");
        }
        $row = Transaction::getByRefNo($refno)->row();

        if (!$row) {
            die("Transaction not exist");
        }
        $Transaction = json_decode(json_encode($row),1);
        #print_out($Transaction,0);

        $emailTplVars = array(
            'user_email' => $Transaction['buyerEmail'],
            'order_number' => $Transaction['orderNumber'],
            'trx_time' => $Transaction['transactionTime'],
            'item_title' => $Transaction['itemTitle'],
            'item_price' => $Transaction['amount'],
            'vsn' => $Transaction['voucher'],
        );
        $emailHtml = $this->templateEmail($emailTplVars);
        #print_out($emailTplVars,0);
        #$emailParams = array('to' => $inputs['buyerEmail'],'subject' => 'Voucher '.strtoupper($inputs['itemTitle']).' Order Blanja '.$inputs['orderNumber'],'message' => sprintf($this->emailTpl,$inputs['buyerEmail'],$resII['item'][0]['value']));
        $emailParams = array('to' => $Transaction['buyerEmail'],'subject' => 'Voucher '.strtoupper($Transaction['itemTitle']).' Order Blanja '.$Transaction['orderNumber'],'message' => $emailHtml);
        #print_out($emailParams,0);
        #$params['subject'] = 'tes';
        #$params['message'] = 'tes kirim email';
        $url = "http://192.168.1.4/bulk_vcr/index.php/alert";
        $objResEmail = json_decode($this->curl_post($url,$emailParams));
        Log4php::getLogger()->debug(sprintf("%s - RES EMAIL : %s",$this->logId,json_encode($objResEmail)));
        if (is_object($objResEmail) && property_exists($objResEmail,'email_sent') && $objResEmail->email_sent == true) {
            $trxBlanja = array();
            #$trxBlanja['id'] = $rowId;
            $trxBlanja['email_sent'] = 1;
            $trxBlanja['updated_at'] = date('Y-m-d H:i:s');
            $trxBlanja['orderNumber'] = $Transaction['orderNumber'];
            $trxBlanja['upointH2hReqRefno'] = $Transaction['upointH2hReqRefno'];
            Transaction::updateTrx($trxBlanja);
            Log4php::getLogger()->debug(sprintf("%s - EMAIL SENT", $this->logId));
        }


    }
    public function sentOrdersManual() {
        /** CRON TAB ADA DI USER www-data
         * edit : sudo su -c "crontab -e" www-data
         */
        Log4php::getLogger()->debug(sprintf("======= GET ORDERS MANUAL START ======="));

        $orderNumber = $_GET['orderNumber'];
        $skuCode = $_GET['skuCode'];

        if ($orderNumber == '' && $skuCode == '') {
            die("Invalid");
        }

        $qty=1;
        for ($i=0;$i<$qty;$i++) {
            try {

                $Transaction = Transaction::getByOrderNumber($orderNumber)->row();
                #print_out($Transaction,0);
                if ($Transaction) {
                    $params['buyerEmail'] = $Transaction->buyerEmail;
                    $params['orderNumber'] = $Transaction->orderNumber;
                    #$params['receiverMobile'] = $Transaction->receiverMobile;
                    $params['receiverName'] = $Transaction->buyerEmail;
                    $params['transactionTime'] = $Transaction->transactionTime;

                    $params['expire_date'] = $Transaction->expire_date;
                    $params['skuCode'] = $Transaction->umb_code;
                    $params['itemTitle'] = $Transaction->itemTitle;
                    $params['productType'] = "DIGITAL_PRODUCT";
                    $params['amount'] = $Transaction->amount;


                    Log4php::getLogger()->debug(sprintf("%s - ========================= PROSES %s %s %s ===============================", $this->logId, $Transaction->orderNumber, $Transaction->umb_code, $Transaction->buyerEmail));
                    Log4php::getLogger()->debug(sprintf("%s - sendNewVoucherRequest params %s", $this->logId, json_encode($params)));
                    print_out($params);
                    $this->sendNewVoucherRequestOnly($params);

                }
            } catch (Exception $e) {
                Log4php::getLogger()->debug("ERROR " . $e->getMessage());
            }
        }


        Log4php::getLogger()->debug(sprintf("======= GET ORDERS END ======="));
        echo "DONEs";
    }

    public function updatePriceQuantity()
    {
        $items = $this->itemsOnshelf();
        foreach($items->result->items->item as $it) {
            $item = $this->_getItem($it->id);
            if (is_object($item)) {
                #print_out($item->result->item->title);
                #print_out($item);
                $params = array();
                $params['skuId'] = $item->result->item->skus->sku->skuId;
                $params['qty'] = 9999;
                $xml = new SimpleXMLElement($this->blanja->updatePriceQuantity($params));
                $res = json_decode(json_encode($xml));
                #print_out($res,0);
            }
        }

    }

    public function itemCreate() {
        die("Forbidden");
        $params = array();
        $csvString = "";
        #print_out($obj);
        $item = array(
            'categoryId' => 20010919, //Digital Voucher
            'title' => 'UGARENA0',
            'price' => 1,
            'listPrice' => 1,
            'state' => 14,
            'city' => 1410,
            'district' => 141010,
            'weight' => 1,
            'length' => 1,
            'width' => 1,
            'height' => 1,
            //'buyerObtainPoint' => 0,
            'freightPayer' => 'seller',
            //'postageId' => 0,
            //'expressFee' => 0,
            'description' => 'Voucher Tes Garena',
            //'properties' => 'UGARENA0',
            'supportPOD' => 'false',
            //'podFee' => 0,
        );
        $xml_item = new SimpleXMLElement("<item></item>");
        array_to_xml($item,$xml_item);
        $xmlItem = $xml_item->asXML();
        #Log4php::getLogger()->debug("xml item : ".$xmlItem);
        $params['item'] = (string)$xmlItem;
        #$params['item'] = '<item><categoryId>20010919</categoryId><title>UGARENA0</title><price>0</price><listPrice>0</listPrice><state>14</state><city>1410</city><district>141010</district><weight>0</weight><length>0</length><width>0</width><height>0</height><freightPayer>0</freightPayer><description>UGARENA0</description><supportPOD>0</supportPOD></item>';
        /*$params['item'] = str_replace('<?xml version="1.0"?>','',$params['item']);*/
        #print_out($params,0);

        $sku = array(
            'sku' => array(
                'outerCode' => 'UGARENA0',
                'quantity' => 1,
                'price' => 1
            )
        );
        $xml_sku = new SimpleXMLElement("<skus></skus>");
        array_to_xml($sku,$xml_sku);
        $xmlSku = $xml_sku->asXML();
        #Log4php::getLogger()->debug("xml sku : ".$xmlSku);
        $params['skus'] = (string)$xmlSku;
        #$params['skus'] = '<skus><outerCode>UGARENA0</outerCode><quantity>1</quantity><price>0</price></skus>';

        $itemImage = array(
            'itemImage' => array(
                'url' => 'https://www.upoint.id/public_assets/web/images/group/garena.jpg',
                'position' => 1,
                //'properties' => 'garena test voucher'
            )
        );
        $xml_item_images = new SimpleXMLElement("<itemImages></itemImages>");
        array_to_xml($itemImage,$xml_item_images);
        $xmlItemImages = $xml_item_images->asXML();
        #Log4php::getLogger()->debug("xml itemImages : ".$xmlItemImages);
        $params['itemImages'] = (string)$xmlItemImages;
        #$params['itemImages'] = '<itemImages><url>https://www.upoint.id/public_assets/web/images/group/garena.jpg</url><position>1</position></itemImages>';

        #Log4php::getLogger()->debug("params itemCreate : ".json_encode($params));

        #$xml = new SimpleXMLElement($this->blanja->itemCreate($params));
        #$obj = json_decode(json_encode($xml));
        #print_out($obj);

        echo $this->blanja->itemCreate($params);

    }
    function getOrderDetails($orderNumber) {
        Log4php::getLogger()->debug(sprintf("%s ======= GET ORDERS DETAIL %s =======",$this->logId,$orderNumber));
        #print_out($orderNumber,0);
        $inputs['orderNumber'] = $orderNumber;
        $xml = new SimpleXMLElement($this->blanja->orderDetails($inputs));
        #print_out($xml,0);
        $obj = json_decode(json_encode($xml));
        print_out($obj,0);
        return $obj;

    }
    function _getItem($itemid) {
        $inputs['itemId'] = $itemid;
        $xml = new SimpleXMLElement($this->blanja->itemDetail($inputs));
        $obj = json_decode(json_encode($xml));
        return $obj;
    }

    public function itemDetail() {
        $inputs['itemId'] = $_GET['pid'];
        $xml = new SimpleXMLElement($this->blanja->itemDetail($inputs));
        $obj = json_decode(json_encode($xml));
        print_out($obj);
    }
    public function itemDelete() {
        $inputs['itemId'] = $_GET['pid'];
        $xml = new SimpleXMLElement($this->blanja->itemDelete($inputs));
        $obj = json_decode(json_encode($xml));
        print_out($obj);
    }
    public function putItemOnshelf() {
        $inputs['itemId'] = $this->input->post('itemId');
        $xml = new SimpleXMLElement($this->blanja->putItemOnshelf($inputs));
        $obj = json_decode(json_encode($xml));
        print_out($obj);
    }
    public function itemsOnshelf() {
        $inputs['pageNum'] = 1;
        $xml = new SimpleXMLElement($this->blanja->itemsOnshelfList($inputs));
        $obj = json_decode(json_encode($xml));
        return $obj;
        #print_out($obj);
    }
    public function itemsInstorage() {
        $inputs['pageNum'] = 1;
        $xml = new SimpleXMLElement($this->blanja->itemsInstorageList($inputs));
        $obj = json_decode(json_encode($xml));
        print_out($obj);
    }
    public function categories() {
        $xml = new SimpleXMLElement($this->blanja->businessCategory(array()));
        $obj = json_decode(json_encode($xml));
        print_out($obj);
    }

    public function getOrders() {
        #$this->load->model('transactiondev');
        /** CRON TAB ADA DI USER www-data
         * edit : sudo su -c "crontab -e" www-data
         */
        Log4php::getLogger()->debug(sprintf("%s ======= GET ORDERS START =======",$this->logId));
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
                Log4php::getLogger()->debug(sprintf("%s ======= GET ORDERS END =======",$this->logId));
                exit(0);
            }

            $orderList = array();
            if (is_object($apiResult->result->orders->order)) {
                # jika cuma 1 order, struktur xml objectnya :
                # $apiResult->result->orders->order->orderNumber
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
                Log4php::getLogger()->debug(sprintf("%s ======= GET ORDERS END =======",$this->logId));
                echo "No Orders".PHP_EOL;
                exit(0);
            }


            foreach($orderList as $order) {
                #print_out($order,0);
                $kini = date('Y-m-d H:i:s');
                $expDate = date_create($kini);
                date_add($expDate, date_interval_create_from_date_string('1 hours'));
                $numberOfSuccess = 0;
                $resVch = array();
                $qty = $order->orderLines->orderLine->quantity;
                #$qty=2;
                for ($i=0;$i<$qty;$i++) {
                    try {
                        $params = array();
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
                        Log4php::getLogger()->debug(sprintf("%s - sendNewVoucherRequest params %s", $this->logId, json_encode($params)));
                        #print_out($params);

                        $vcrReqRes = $this->sendNewVoucherRequestOnlyNoEmail($params);
                        if ($vcrReqRes['success']) {
                            $resVch[] = $vcrReqRes;
                            $numberOfSuccess++;
                        }
                    } catch (Exception $e) {
                        Log4php::getLogger()->debug(sprintf("%s ERROR " . $e->getMessage(),$this->logId));
                    }
                }

                if ($numberOfSuccess == 0) {
                    Log4php::getLogger()->debug(sprintf("%s no success transaction orderno %s ", $this->logId,$order->orderNumber));
                    $msgParams=array(
                        'orderNumber' => $order->orderNumber,
                        'message' => 'mohon maaf saat ini sistem kami sedang sibuk. silahkan coba beberapa saat lagi.'
                    );
                    $this->blanja->sendSellerMessage($msgParams);

                    /* sent alert email */
                    $devEmailAlertParams = array(
                        'to' => self::DEVELOPER_EMAIL,
                        'subject' => 'Blanja - no success transaction '.strtoupper($order->orderNumber),
                    );
                    $devEmailAlertParams['message'] = "order no : ".$order->orderNumber;
                    $devEmailAlertParams['message'] .= "<br />Order Qty : ".number_format($qty,0);
                    $devEmailAlertParams['message'] .= "<br />Order delivered : ".number_format($numberOfSuccess,0);
                    $this->kirimEmail($devEmailAlertParams);

                    continue;
                }


                /** ship order */
                $vouchers = array();
                foreach ($resVch as $vcr) {
                    $vouchers[] = $vcr['trx_detail']['voucher'];
                }
                $shipParams = array();
                $shipParams['orderNumber'] = $order->orderNumber;
                $logistic = in_array($order->orderLines->orderLine->productType,array_keys($this->logisticCompanies)) ? $this->logisticCompanies[$order->orderLines->orderLine->productType] : 'DIGITAL';
                $shipParams['logisticsCompany'] = $logistic;
                $shipParams['invoiceNumber'] = $vcr['trx_detail']['invoiceno'];
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
                            'vsn' => implode("\r\n<br />",$vouchers)
                        );
                        $emailHtml = $this->templateEmail($emailTplVars);
                        $emailParams = array('to' => $order->buyerEmail,'subject' => 'Voucher '.strtoupper($order->orderLines->orderLine->itemTitle).' Order Blanja '.$order->orderNumber,'message' => $emailHtml);

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
        Log4php::getLogger()->debug(sprintf("======= GET ORDERS END ======="));
        echo "DONEs";
    }
    public function sendNewVoucherRequestOnlyNoEmail($inputs) {
        #$this->load->model('transactiondev');
        $res = array('success' => false,'msg' => '');

        $secret_key = self::PARTNER_SECRET_TOKEN;
        $item_code = $inputs['skuCode'];
        $partner_id = self::PARTNER_ID;
        $vouchers = array();

        try {
            $refnoId = Refno::getId();
            $prefix = "blanjah2h";
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


            $paramsI = array(
                'partner_id' => $partner_id,
                'ref_no' => $ref_no,
                'item_code' => $item_code,
                'timestamp' => 30,
                'secret_token' => $secret_key,
                'signature' => md5($partner_id . $ref_no . $item_code . '30' . $secret_key)
            );
            Log4php::getLogger()->debug(sprintf($this->logId . " VOUCHER_REQUEST PARAMS %s", json_encode($paramsI)));

            $jsonI = $this->curl_post(self::VOUCHER_REQUEST_URL, $paramsI);
            $resI = json_decode($jsonI, 1);
            Log4php::getLogger()->debug(sprintf($this->logId . " VOUCHER_REQUEST RES %s", json_encode($resI)));


            # ==== TES RESPONSE OUT OF STOCK
            #$resI = array();
            #$resI['status'] = '0';
            #$resI['error_code'] = 'E004';
            # ==== END TES RESPONSE OUT OF STOCK

            if ($resI['status']) {
                $trx_id = $resI['trx_id'];
                $paramsII = array(
                    'partner_id' => $partner_id,
                    'trx_id' => $trx_id,
                    'ref_no' => $ref_no,
                    'status' => 1,
                    'secret_token' => $secret_key,
                    'signature' => md5($partner_id . $trx_id . $ref_no . '1' . $secret_key)
                );
                Log4php::getLogger()->debug(sprintf($this->logId . " VOUCHER_CONFIRM PARAMS %s", json_encode($paramsII)));

                $jsonII = $this->curl_post(self::VOUCHER_CONFIRM_URL, $paramsII);
                $resII = json_decode($jsonII, 1);
                $vouchers[] = $resII['item'][0]['value'];
                $arr = array("", "");
                $invNo = $ref_no;
                if (strpos($resII['item'][0]['value'], ";")) {
                    $arr = explode(";", $resII['item'][0]['value']);
                    #$invNo = $arr[0]."-".$arr[1];
                } else {
                    $arr = array($resII['item'][0]['value'], "");
                    #$invNo = $arr[0];
                }

                Log4php::getLogger()->debug(sprintf($this->logId . " VOUCHER_CONFIRM RES %s", json_encode($resII)));

                $trxBlanja = array();
                #$trxBlanja['id'] = $rowId;
                $trxBlanja['accepted'] = 1;
                $trxBlanja['voucher'] = $resII['item'][0]['value'];
                $trxBlanja['invoiceno'] = $invNo;
                $trxBlanja['updated_at'] = date('Y-m-d H:i:s');
                $trxBlanja['orderNumber'] = $inputs['orderNumber'];
                $trxBlanja['upointH2hReqRefno'] = $ref_no;
                Transaction::updateTrx($trxBlanja);

                $res = array('success' => true, 'trx_detail' => $trxBlanja);
            } else {
                /** E004 : OUT OF STOCK */
                if ($resI['error_code'] == 'E004') {
                    $emailAlertParams = array('to' => self::DEVELOPER_EMAIL,'subject' => 'Out Of Stock Blanja '.strtoupper($inputs['orderNumber']),'message' => 'Out of Stock blanja order no : '.$inputs['orderNumber']);
                    $resAlert = $this->kirimEmail($emailAlertParams);

                    $res = array('success' => false, 'msg' => 'Item Out Of Stock', 'err_msg' => 'E004 : Item Out Of Stock');
                    #break;
                } else {
                    $res = array('success' => false,'msg' => 'Error','err_msg' => $resI['error_info']);
                    #break;
                }
            }
        } catch (Exception $e) {
            $res = array('success' => false,'msg' => '', 'err_msg' => $e->getMessage());
        }
        return $res;
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
    public function templateEmail($inputs = array()) {

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
        $vsn = $inputs['vsn'];

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
            <div style="text-align:left;margin:20px 0 30px 0;border-top:1px solid #e7e7e7;"> <p style="margin:20px 0;color:#333333"> <span style="background-color:#e3211d;margin-right:5px">&nbsp;</span><b>Kode Voucher Digital Anda</b> </p> <div style="padding:10px;border:1px solid #e7e7e7;background-color:#f0f0f0"> <div style="margin:0"><p style="margin:0">$vsn</p> </div> </div>
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

    public function oiy() {
        $cek = Transaction::getByOrderNumberAndInvoiceNo('sd222','sd222');
        if (!$cek) {
            print_out("tidak ada");
        } else {
            print_out("ada");
        }
        print_out($cek);
    }

    public function token() {
        $xml = new SimpleXMLElement($this->blanja->getAccessToken());
        $obj = json_decode(json_encode($xml));
        #echo $token."<br />";
        #echo "TOKEN<br />";
        print_out($obj);
        $token = "";

        if (property_exists($obj,'code') && $obj->code == 'SUCCESS') {
            $token = $obj->result->access_token;
        }
    }
    public function tes() {
        $params = array();
        $item = array(
            'categoryId' => 20010919, //Digital Voucher
            'title' => 'UGARENA0',
            'price' => 0,
            'listPrice' => 0,
            'state' => 14,
            'city' => 1410,
            'district' => 141010,
            'weight' => 0,
            'length' => 0,
            'width' => 0,
            'height' => 0,
            //'buyerObtainPoint' => 0,
            'freightPayer' => 0,
            //'postageId' => 0,
            //'expressFee' => 0,
            'description' => 'UGARENA0',
            //'properties' => 'UGARENA0',
            'supportPOD' => 0,
            //'podFee' => 0,
        );
        $params['item'] = $item;

        $sku = array(
            'outerCode' => 'UGARENA0',
            'quantity' => 1,
            'price' => 0,
        );
        $xmlSku = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?><skus></skus>");
        array_to_xml($sku,$xmlSku);
        //$params['skus'] = $xmlSku->asXML();
        $params['skus'] = $sku;

        $itemImage = array(
            'url' => 'https://www.upoint.id/public_assets/web/images/group/garena.jpg',
            'position' => 1
        );
        $xmlItemImage = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?><itemImages></itemImages>");
        array_to_xml($itemImage,$xmlItemImage);
        //$params['itemImages'] = $xmlItemImage->asXML();
        $params['itemImages'] = $itemImage;

        print_out($params);

    }
    public function tesxml() {
        $item = array(
            'categoryId' => '20010919',
            'title' => 'UGARENA0',
            'price' => '0',
            'listPrice' => '0',
            'state' => '14',
            'city' => '1410',
            'district' => '141010',
            'weight' => '0',
            'length' => '0',
            'width' => '0',
            'height' => '0',
            'freightPayer' => '0',
            'description' => 'UGARENA0',
            'supportPOD' => '0',
        );

        $users_array = array(
            "total_users" => 3,
            "users" => array(
                array(
                    "id" => 1,
                    "name" => "Smith",
                    "address" => array(
                        "country" => "United Kingdom",
                        "city" => "London",
                        "zip" => 56789,
                    )
                ),
                array(
                    "id" => 2,
                    "name" => "John",
                    "address" => array(
                        "country" => "USA",
                        "city" => "Newyork",
                        "zip" => "NY1234",
                    )
                ),
                array(
                    "id" => 3,
                    "name" => "Viktor",
                    "address" => array(
                        "country" => "Australia",
                        "city" => "Sydney",
                        "zip" => 123456,
                    )
                ),
            )
        );
        $xml_user_info = new SimpleXMLElement("<item></item>");
        array_to_xml($item,$xml_user_info);
        #header("Content-type: text/xml");
        #echo $xml_user_info->asXML();
        $xmlItem = $xml_user_info->asXML();
        Log4php::getLogger()->debug("xml item : ".$xmlItem);

        #echo $xml->asXML();
    }
    function array_to_xml($array, &$xml) {
        foreach($array as $key => $value) {
            if(is_array($value)) {
                if(!is_numeric($key)){
                    $subnode = $xml->addChild("$key");
                    array_to_xml($value, $subnode);
                }else{
                    $subnode = $xml->addChild("item$key");
                    array_to_xml($value, $subnode);
                }
            }else {
                $xml->addChild("$key",htmlspecialchars("$value"));
            }
        }
    }
    public function cobakirimemail() {
        $inputs['user_email'] = 'yana.supriatna@melon.co.id';
        $inputs['order_number'] = '418615705452510731';
        $inputs['trx_time'] = '18 September 2018, 13:05:46 WIB';
        $inputs['item_title'] = 'Wifiid 5.000';
        $inputs['item_price'] = 5000;
        $inputs['vsn'] = 'KodeVcr=dummy-vch-ugarena0-3885;Pass=abc-3885';
        $tpl = $this->templateEmail($inputs);

        #$res = $this->kirimEmail(array('to' => $inputs['user_email'],'subject' => $inputs['item_title'],'message' => $tpl));
        #print_out($res);
        echo $tpl;

    }
    public function tespushtoftp() {
        // set up basic connection
        $filepath = "/var/www/blanjaApi/04tUSq_Coda PO Garena - 1 Jan 2018.pdf";
        $ftp_server = "118.97.213.209";
        $ftp_user_name = "sftpdana1";
        $ftp_user_pass = "j7hdyi8J6yu";
        $filename = basename($filepath);
        $remoteFile = "/var/sftp/sftpdana1/".$filename;

        $conn_id = ftp_connect($ftp_server);

        $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);

        try {
            if (ftp_put($conn_id, $remoteFile, $filepath, FTP_ASCII)) {
                echo "upload sukses $filename".PHP_EOL;
            } else {
                echo "Gagal Upload file $filename".PHP_EOL;
            }

        } catch (Exception $e) {
            echo "Upload Exception ".$e->getMessage().PHP_EOL;
        }
        ftp_close($conn_id);
    }
}