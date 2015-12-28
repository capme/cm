<?php
use App\Library\Order;
use GuzzleHttp\Handler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Client;

class setAcceptOrderTest extends TestCase
{
	public function testTimeOut(){
		$param = array("ordNo" => "201512235991072", "ordPrdSeq" => "1", "connect_timeout" => "1", "apiKey" => "fe868c8788f602061778b49949cf3643", "connect_timeout" => "2");
		$order = new Order();
		$order->baseUrl = "http://api.elevenia.co.id:8000";
		$ret = $order->accept($param);
		$this->assertEquals("0", $ret['code']);
	}

	private function mockGetOrder(Order $order, array $queue)
	{
		$mock = new MockHandler($queue);
		$handler = HandlerStack::create($mock);
		$order->client = new Client(['handler'=>$handler]);

	}

	public function testSuccess(){
		$order = new Order();

		$xml = "<ClientMessage><resultCode>200</resultCode><message>Order NO:xxxxxxxxx, status : Shipping in preparation History</message></ClientMessage>";
		$this->mockGetOrder($order, [
			new Response(200, [], $xml)
		]);

		$param = array("ordNo" => "201512286029293", "ordPrdSeq" => "1", "apiKey" => "fe868c8788f602061778b49949cf3643");
		$ret = $order->accept($param);
		$array = json_decode(json_encode((array)$ret['message']), TRUE);
		$this->assertEquals("Order NO", substr($array["message"],0,8));
	}

	public function testFailed(){
		$order = new Order();

		$xml = "<ClientMessage><resultCode>200</resultCode><message>ERROR Accept Order: Transaction Error</message></ClientMessage>";
		$this->mockGetOrder($order, [
			new Response(200, [], $xml)
		]);

		$param = array("ordNo" => "201512286029293", "ordPrdSeq" => "1", "apiKey" => "fe868c8788f602061778b49949cf3643");
		$ret = $order->accept($param);
		$array = json_decode(json_encode((array)$ret['message']), TRUE);
		$this->assertEquals("ERROR", substr($array["message"],0,5));
	}
}