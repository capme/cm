<?php
use App\Library\Order;
use GuzzleHttp\Handler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Client;

class setCancelTest extends TestCase
{
    public function testTimeOut(){
        $param = array("dlvNo" => "8000027599", "ordNo" => "201512165930342", "ordPrdSeq" => "1", "message" => "test ajah cancel", "ordCnRsnCd" => "99", "ordQty" => "1", "apiKey" => "fe868c8788f602061778b49949cf3643", "connect_timeout" => "2");
        $order = new Order();
        $order->baseUrl = "http://api.elevenia.co.id:8000";
        $ret = $order->setCancel($param);
        $this->assertEquals("0", $ret['code']);
    }

    private function mockGetOrder(Order $order, array $queue)
    {
        $mock = new MockHandler($queue);
        $handler = HandlerStack::create($mock);
        $order->client = new Client(['handler'=>$handler]);
    }

    public function testSuccess(){
        $param = array("dlvNo" => "8000028265", "ordNo" => "201512286029293", "ordPrdSeq" => "1", "message" => "test ajah cancel", "ordCnRsnCd" => "99", "ordQty" => "1", "apiKey" => "fe868c8788f602061778b49949cf3643", "connect_timeout" => "2");
        $order = new Order();

        $xml = "<ClientMessage><productNo>xxxxxxx</productNo><message>Order: ".$param['ordNo']." has been cancelled.</message><resultCode>200</resultCode></ClientMessage>";
        $this->mockGetOrder($order, [
            new Response(200, [], $xml)
        ]);
        $ret = $order->setCancel($param);
        $array = json_decode(json_encode((array)$ret['message']), TRUE);
        $this->assertArrayHasKey("productNo", $array);
    }

    public function testFailed(){
        $param = array("dlvNo" => "8000027599", "ordNo" => "201512165930342", "ordPrdSeq" => "1", "message" => "test ajah cancel", "ordCnRsnCd" => "99", "ordQty" => "1", "apiKey" => "fe868c8788f602061778b49949cf3643", "connect_timeout" => "2");
        $order = new Order();

        $xml = "<ClientMessage><resultCode>200</resultCode><message>ERROR when attempting to cancel order: Invalid order number or delivery number, please check ordNo and dlvNo parameters.</message></ClientMessage>";
        $this->mockGetOrder($order, [
            new Response(200, [], $xml)
        ]);
        $ret = $order->setCancel($param);
        $array = json_decode(json_encode((array)$ret['message']), TRUE);
        $this->assertEquals("ERROR", substr($array["message"], 0, 5));
        $this->assertArrayNotHasKey("productNo",$array);
    }
}