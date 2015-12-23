<?php
use App\Library\Order;

class getOrderTest extends TestCase
{
	public function testTimeOut(){
		$param = array("ordStat" => "301", "dateFrom" => "2015-12-01", "dateTo" => "2015-12-31", "apiKey" => "b2070a630ef576682f2228c078a17816", "connect_timeout" => "3");
		$order = new Order("http://api.elevenia.co.id:8000");
		$ret = $order->get($param);
		$this->assertEquals("0", $ret['code']);
	}

	public function testSuccess(){
		$param = array("ordStat" => "301", "dateFrom" => "2015-12-22", "dateTo" => "2015-12-22", "apiKey" => "b2070a630ef576682f2228c078a17816");
		$order = new Order();
		$ret = $order->get($param);
		$array = json_decode(json_encode((array)$ret['message']), TRUE);
		$this->assertEquals(1, count($array));
	}

	public function testEmpty(){
		$param = array("ordStat" => "301", "dateFrom" => "2010-12-22", "dateTo" => "2010-12-22", "apiKey" => "b2070a630ef576682f2228c078a17816");
		$order = new Order();
		$ret = $order->get($param);
		$array = json_decode(json_encode((array)$ret['message']), TRUE);
		$this->assertEquals(0, count($array));
	}
}