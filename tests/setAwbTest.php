<?php
use App\Library\Order;
use GuzzleHttp\Handler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Client;

class setAwbTest extends TestCase
{
    public function testTimeOut(){
        $param = array("awb" => "JNE12345", "dlvNo" => "8000027521", "dlvMthdCd" => "01", "dlvEtprsCd" => "00301", "ordNo" => "201512165928898", "dlvEtprsNm" => "TIKI Regular", "ordPrdSeq" => "1", "apiKey" => "fe868c8788f602061778b49949cf3643", "connect_timeout" => "2");
        $order = new Order();
        $order->baseUrl = "http://api.elevenia.co.id:8000";
        $ret = $order->setAwb($param);
        $this->assertEquals("0", $ret['code']);
    }

    private function mockGetOrder(Order $order, array $queue)
    {
        $mock = new MockHandler($queue);
        $handler = HandlerStack::create($mock);
        $order->client = new Client(['handler'=>$handler]);
    }

    public function testSuccess(){
        $param = array("awb" => "JNE12345", "dlvNo" => "8000028244", "dlvMthdCd" => "01", "dlvEtprsCd" => "00301", "ordNo" => "201512286028790", "dlvEtprsNm" => "TIKI Regular", "ordPrdSeq" => "1", "apiKey" => "fe868c8788f602061778b49949cf3643");
        $order = new Order();

        $xml = "<ClientMessage><resultCode>200</resultCode><message>SUCCES: order# ".$param['ordNo'].", ord_prd_seq: ".$param['ordPrdSeq']." status is now On Shipping</message></ClientMessage>";
        $this->mockGetOrder($order, [
            new Response(200, [], $xml)
        ]);
        $ret = $order->setAwb($param);
        $array = json_decode(json_encode((array)$ret['message']), TRUE);
        $this->assertEquals("SUCCES", substr($array["message"],0,6));
    }

    public function testFailed(){
        $param = array("awb" => "JNE12345", "dlvNo" => "8000027521", "dlvMthdCd" => "01", "dlvEtprsCd" => "00301", "ordNo" => "201512165928898", "dlvEtprsNm" => "TIKI Regular", "ordPrdSeq" => "1", "apiKey" => "fe868c8788f602061778b49949cf3643");
        $order = new Order();

        $xml = "<ClientMessage><resultCode>200</resultCode><message>ERROR xxxxxxxxxxxxxx</message></ClientMessage>";
        $this->mockGetOrder($order, [
            new Response(200, [], $xml)
        ]);
        $ret = $order->setAwb($param);
        $array = json_decode(json_encode((array)$ret['message']), TRUE);
        $this->assertEquals("ERROR", substr($array["message"], 0, 5));
    }
}