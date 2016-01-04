<?php

use App\Library\Order;
use GuzzleHttp\Handler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client;

class SalesOrderTest extends TestCase
{
    public $token;

    public function setUp()
    {
        $this->token = 'fe868c8788f602061778b49949cf3643';
    }

    public function testServerClientNetworkError()
    {
        $order = new Order($this->token);
        $input = array("ordStat" => "301", "dateFrom" => "2015-12-28", "dateTo" => "2015-12-28");

        $handlerContext = [
            'errno' => 28,
            'error' => 'Connection timed out after 10004 milliseconds',
            'http_code' => 0
        ];

        $this->mockSalesOrder($order, [
            new Response(501),
            new Response(404),
            new ConnectException('Network Error', new Request('GET', $this->baseUrl), null, $handlerContext)
        ]);

        $res = $order->get($input);
        $this->assertEquals(501, $res['code']);

        $res = $order->get($input);
        $this->assertEquals(404, $res['code']);


        $res = $order->get($input);
        $this->assertEquals(0, $res['code']);
    }


    public function testGetSuccess()
    {
        $order = new Order($this->token);

        $xml = "<Orders><order></order><order></order></Orders>";
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);

        $input = array("ordStat" => "301", "dateFrom" => "2015-12-28", "dateTo" => "2015-12-28");
        $res = $order->get($input);

        $this->assertEquals(200, $res['code']);
        $this->assertArrayHasKey('order', $res['message']);
    }

    public function testGetEmpty()
    {
        $order = new Order($this->token);

        $xml = "<Orders><order></order><order></order></Orders>";
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);

        $input = array("ordStat" => "301", "dateFrom" => "2015-12-28", "dateTo" => "2015-12-28");
        $res = $order->get($input);

        $this->assertEquals(200, $res['code']);
        $this->assertArrayHasKey('order', $res['message']);
    }

    public function testAcceptSuccess()
    {
        $order = new Order($this->token);

        $xml = "<ClientMessage><resultCode>200</resultCode><message>"
            . "Order NO:xxxxxxxxx, status : Shipping in preparation History</message></ClientMessage>";
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);

        $input = array("ordNo" => "201512286029293", "ordPrdSeq" => "1");
        $ret = $order->accept($input);

        $array = json_decode(json_encode((array)$ret['message']), TRUE);

        $this->assertEquals("Order NO", substr($array["message"],0,8));
    }

    public function testAcceptFailed()
    {
        $order = new Order($this->token);

        $xml = "<ClientMessage><resultCode>200</resultCode><message>"
            . "ERROR Accept Order: Transaction Error</message></ClientMessage>";
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);

        $input = array("ordNo" => "201512286029293", "ordPrdSeq" => "1");
        $ret = $order->accept($input);
        $array = json_decode(json_encode((array)$ret['message']), TRUE);
        $this->assertEquals("ERROR", substr($array["message"],0,5));
    }

    public function testUpdateAWBSuccess()
    {
        $order = new Order($this->token);

        $input = array("awb" => "JNE12345", "dlvNo" => "8000028244", "dlvMthdCd" => "01",
            "dlvEtprsCd" => "00301", "ordNo" => "201512286028790", "dlvEtprsNm" => "TIKI Regular",
            "ordPrdSeq" => "1");

        $xml = "<ClientMessage><resultCode>200</resultCode><message>SUCCES: order# " . $input['ordNo']
            . ", ord_prd_seq: ".$input['ordPrdSeq']." status is now On Shipping</message></ClientMessage>";
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);
        $ret = $order->updateAWB($input);
        $array = json_decode(json_encode((array)$ret['message']), TRUE);
        $this->assertEquals("SUCCES", substr($array["message"],0,6));
    }

    public function testUpdateAWBFailed()
    {
        $order = new Order($this->token);

        $xml = "<ClientMessage><resultCode>200</resultCode><message>ERROR xxxxxxxxxxxxxx</message></ClientMessage>";
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);

        $input = array("awb" => "JNE12345", "dlvNo" => "8000027521", "dlvMthdCd" => "01",
            "dlvEtprsCd" => "00301", "ordNo" => "201512165928898", "dlvEtprsNm" => "TIKI Regular",
            "ordPrdSeq" => "1");
        $ret = $order->updateAWB($input);

        $array = json_decode(json_encode((array)$ret['message']), TRUE);
        $this->assertEquals("ERROR", substr($array["message"], 0, 5));
    }

    public function testCancelSuccess()
    {
        $order = new Order($this->token);

        $input = array("dlvNo" => "8000028448", "ordNo" => "201512296037598", "ordPrdSeq" => "1",
            "message" => "test ajah cancel", "ordCnRsnCd" => "99", "ordQty" => "1");
        $xml = "<ClientMessage><productNo>xxxxxxx</productNo><message>Order: " . $input['ordNo']
            . " has been cancelled.</message><resultCode>200</resultCode></ClientMessage>";
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);
        $ret = $order->cancel($input);
        $array = json_decode(json_encode((array)$ret['message']), TRUE);
        $this->assertArrayHasKey("productNo", $array);
    }

    public function testCancelFailed()
    {
        $order = new Order($this->token);

        $xml = "<ClientMessage><resultCode>200</resultCode><message>ERROR when attempting "
            . "to cancel order: Invalid order number or delivery number, please check ordNo and "
            . "dlvNo parameters.</message></ClientMessage>";
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);

        $input = array("dlvNo" => "8000027599", "ordNo" => "201512165930342", "ordPrdSeq" => "1",
            "message" => "test ajah cancel", "ordCnRsnCd" => "99", "ordQty" => "1");

        $ret = $order->cancel($input);
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