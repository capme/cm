<?php
use App\Library\Order;
use GuzzleHttp\Handler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Client;

class getOrderTest extends TestCase
{
	public function testTimeOut(){

		$param = array("ordStat" => "301", "dateFrom" => "2015-12-01", "dateTo" => "2015-12-31", "apiKey" => "fe868c8788f602061778b49949cf3643", "connect_timeout" => "2");
		$order = new Order();
		$order->baseUrl = "http://api.elevenia.co.id:8000";
		$ret = $order->get($param);
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

		$xml = "<Orders><order></order><order></order></Orders>";
		$this->mockGetOrder($order, [
			new Response(200, [], $xml)
		]);

		$param = array("ordStat" => "301", "dateFrom" => "2015-12-28", "dateTo" => "2015-12-28", "apiKey" => "fe868c8788f602061778b49949cf3643");
		$res = $order->get($param);

		$this->assertEquals(200, $res['code']);
		$this->assertArrayHasKey('order', $res['message']);
	}

	public function testEmpty(){
		$order = new Order();

		$xml = "<Orders></Orders>";
		$this->mockGetOrder($order, [
			new Response(200, [], $xml)
		]);

		$param = array("ordStat" => "301", "dateFrom" => "2015-11-28", "dateTo" => "2015-11-28", "apiKey" => "fe868c8788f602061778b49949cf3643");
		$res = $order->get($param);

		$this->assertEquals(200, $res['code']);
		$this->assertEmpty($res['message']);

	}
}