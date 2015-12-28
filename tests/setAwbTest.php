<?php
use App\Library\Order;

class setAwbTest extends TestCase
{
    public function testTimeOut(){

        $param = array("awb" => "JNE12345", "dlvNo" => "8000027521", "dlvMthdCd" => "01", "dlvEtprsCd" => "00301", "ordNo" => "201512165928898", "dlvEtprsNm" => "TIKI Regular", "ordPrdSeq" => "1", "apiKey" => "fe868c8788f602061778b49949cf3643");
        $order = new Order("http://api.elevenia.co.id:8000");
        $ret = $order->setAwb($param);
        $this->assertEquals("0", $ret['code']);

    }

    public function testSuccess(){

        $param = array("awb" => "JNE12345", "dlvNo" => "8000028244", "dlvMthdCd" => "01", "dlvEtprsCd" => "00301", "ordNo" => "201512286028790", "dlvEtprsNm" => "TIKI Regular", "ordPrdSeq" => "1", "apiKey" => "fe868c8788f602061778b49949cf3643");
        $order = new Order();
        $ret = $order->setAwb($param);
        $array = json_decode(json_encode((array)$ret['message']), TRUE);
        $this->assertEquals("SUCCES: order# ".$param['ordNo'].", ord_prd_seq: ".$param['ordPrdSeq']." status is now On Shipping", $array["message"]);

    }

    public function testFailed(){
        $param = array("awb" => "JNE12345", "dlvNo" => "8000027521", "dlvMthdCd" => "01", "dlvEtprsCd" => "00301", "ordNo" => "201512165928898", "dlvEtprsNm" => "TIKI Regular", "ordPrdSeq" => "1", "apiKey" => "fe868c8788f602061778b49949cf3643");
        $order = new Order();
        $ret = $order->setAwb($param);
        $array = json_decode(json_encode((array)$ret['message']), TRUE);
        $this->assertEquals("ERROR", substr($array["message"], 0, 5));
    }
}