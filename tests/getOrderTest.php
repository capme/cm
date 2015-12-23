<?php
use App\Library\Order;

class getOrderTest extends TestCase
{
	public function testTimeOut(){
		try{
			$param = array("ordStat" => "301", "dateFrom" => "2015-12-01", "dateTo" => "2015-12-31", "apiKey" => "fe868c8788f602061778b49949cf3643", "connect_timeout" => "3");
			$order = new Order("http://apifailed.elevenia.co.id");
			$order->get($param);
		}catch(Exception $e){
			$this->assertTrue(true);
		}
	}

	public function testSuccess(){
		$param = array("ordStat" => "301", "dateFrom" => "2015-12-22", "dateTo" => "2015-12-22", "apiKey" => "b2070a630ef576682f2228c078a17816");
		$order = new Order();
		$ret = $order->get($param);
		$array = json_decode(json_encode((array)$ret), TRUE);
		$this->assertEquals(1, count($array));
	}

	public function testEmpty(){
		$param = array("ordStat" => "301", "dateFrom" => "2010-12-22", "dateTo" => "2010-12-22", "apiKey" => "b2070a630ef576682f2228c078a17816");
		$order = new Order();
		$ret = $order->get($param);
		$array = json_decode(json_encode((array)$ret), TRUE);
		$this->assertEquals(0, count($array));
	}
}