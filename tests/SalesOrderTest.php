<?php

use App\Library\Order;
use GuzzleHttp\Handler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Client;

class SalesOrderTest extends TestCase
{
    public function testGetSuccess()
    {
        $order = new Order();

        $xml = "<Orders><order></order><order></order></Orders>";
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);

        $param = array("ordStat" => "301", "dateFrom" => "2015-12-28", "dateTo" => "2015-12-28",
            "apiKey" => "fe868c8788f602061778b49949cf3643");
        $res = $order->get($param);

        $this->assertEquals(200, $res['code']);
        $this->assertArrayHasKey('order', $res['message']);
    }

    public function testGetEmpty()
    {
        $order = new Order();

        $xml = "<Orders><order></order><order></order></Orders>";
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);

        $param = array("ordStat" => "301", "dateFrom" => "2015-12-28",
            "dateTo" => "2015-12-28", "apiKey" => "fe868c8788f602061778b49949cf3643");
        $res = $order->get($param);

        $this->assertEquals(200, $res['code']);
        $this->assertArrayHasKey('order', $res['message']);
    }

    public function testAcceptSuccess()
    {
        $order = new Order();

        $xml = "<ClientMessage><resultCode>200</resultCode><message>"
            . "Order NO:xxxxxxxxx, status : Shipping in preparation History</message></ClientMessage>";
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);

        $param = array("ordNo" => "201512286029293", "ordPrdSeq" => "1",
            "apiKey" => "fe868c8788f602061778b49949cf3643");
        $ret = $order->accept($param);
        $array = json_decode(json_encode((array)$ret['message']), TRUE);
        $this->assertEquals("Order NO", substr($array["message"],0,8));
    }

    public function testAcceptFailed()
    {
        $order = new Order();

        $xml = "<ClientMessage><resultCode>200</resultCode><message>"
            . "ERROR Accept Order: Transaction Error</message></ClientMessage>";
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);

        $param = array("ordNo" => "201512286029293", "ordPrdSeq" => "1",
            "apiKey" => "fe868c8788f602061778b49949cf3643");
        $ret = $order->accept($param);
        $array = json_decode(json_encode((array)$ret['message']), TRUE);
        $this->assertEquals("ERROR", substr($array["message"],0,5));
    }

    public function testUpdateAWBSuccess()
    {
        $param = array("awb" => "JNE12345", "dlvNo" => "8000028244", "dlvMthdCd" => "01",
            "dlvEtprsCd" => "00301", "ordNo" => "201512286028790", "dlvEtprsNm" => "TIKI Regular",
            "ordPrdSeq" => "1", "apiKey" => "fe868c8788f602061778b49949cf3643");
        $order = new Order();

        $xml = "<ClientMessage><resultCode>200</resultCode><message>SUCCES: order# " . $param['ordNo']
            . ", ord_prd_seq: ".$param['ordPrdSeq']." status is now On Shipping</message></ClientMessage>";
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);
        $ret = $order->updateAWB($param);
        $array = json_decode(json_encode((array)$ret['message']), TRUE);
        $this->assertEquals("SUCCES", substr($array["message"],0,6));
    }

    public function testUpdateAWBFailed()
    {
        $param = array("awb" => "JNE12345", "dlvNo" => "8000027521", "dlvMthdCd" => "01",
            "dlvEtprsCd" => "00301", "ordNo" => "201512165928898", "dlvEtprsNm" => "TIKI Regular",
            "ordPrdSeq" => "1", "apiKey" => "fe868c8788f602061778b49949cf3643");
        $order = new Order();

        $xml = "<ClientMessage><resultCode>200</resultCode><message>ERROR xxxxxxxxxxxxxx</message></ClientMessage>";
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);
        $ret = $order->updateAWB($param);
        $array = json_decode(json_encode((array)$ret['message']), TRUE);
        $this->assertEquals("ERROR", substr($array["message"], 0, 5));
    }

    public function testCancelSuccess()
    {
        $param = array("dlvNo" => "8000028448", "ordNo" => "201512296037598", "ordPrdSeq" => "1",
            "message" => "test ajah cancel", "ordCnRsnCd" => "99", "ordQty" => "1",
            "apiKey" => "fe868c8788f602061778b49949cf3643", "connect_timeout" => "2");
        $order = new Order();

        $xml = "<ClientMessage><productNo>xxxxxxx</productNo><message>Order: " . $param['ordNo']
            . " has been cancelled.</message><resultCode>200</resultCode></ClientMessage>";
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);
        $ret = $order->cancel($param);
        $array = json_decode(json_encode((array)$ret['message']), TRUE);
        $this->assertArrayHasKey("productNo", $array);
    }

    public function testCancelFailed()
    {
        $param = array("dlvNo" => "8000027599", "ordNo" => "201512165930342", "ordPrdSeq" => "1",
            "message" => "test ajah cancel", "ordCnRsnCd" => "99", "ordQty" => "1",
            "apiKey" => "fe868c8788f602061778b49949cf3643", "connect_timeout" => "2");
        $order = new Order();

        $xml = "<ClientMessage><resultCode>200</resultCode><message>ERROR when attempting "
            . "to cancel order: Invalid order number or delivery number, please check ordNo and "
            . "dlvNo parameters.</message></ClientMessage>";
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);
        $ret = $order->cancel($param);
        $array = json_decode(json_encode((array)$ret['message']), TRUE);
        $this->assertEquals("ERROR", substr($array["message"], 0, 5));
        $this->assertArrayNotHasKey("productNo",$array);
    }

    private function mockSalesOrder(Order $order, array $queue)
    {
        $mock = new MockHandler($queue);
        $handler = HandlerStack::create($mock);
        $order->client = new Client(['handler'=>$handler]);

    }
}