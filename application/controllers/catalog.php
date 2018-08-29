<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
#error_reporting(E_ALL);
class Catalog extends CI_Controller
{
    function __construct() {
        ini_set("display_errors",false);
        parent::__construct();
        $this->load->library('log4php','logger');
        $this->load->library('blanja');
        $this->load->helper('yana');
        $this->logId = strtotime(date('YmdHis'));
    }

    public function index() {
        #die("OK");
        $tplVars = array();
        #<img src="https://www.upoint.id/public_assets/web/images/group/garena.jpg" alt="garena" class="responsive-img">
        $this->load->view('v_catalog',$tplVars);
    }

    public function tes() {
        $params = array();
        $item = array(
            'categoryId' => 1,
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
        $xmlItem = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"yes\"?><item></item>");
        array_to_xml($item,$xmlItem);
        //$params['item'] = $xmlItem->asXML();
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

    public function category() {
        #$commonParams = $this->blanja->prepareCommonParams();
        $this->blanja->cobalog();

    }

    public function tesxml() {
        #$token = $this->blanja->getAccessToken();
        #print_out($token);
        #echo $token;
        $test_array = array (
            'bla' => 'blub',
            'foo' => 'bar',
            'another_array' => array (
                'stack' => 'overflow',
            ),
        );
        $xml = new SimpleXMLElement('<root/>');
        array_walk_recursive($test_array, array ($xml, 'addChild'));

        header("Content-type: text/xml");
        echo $xml->asXML();
    }

}