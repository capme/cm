<?php
use App\Library\Order;

class setAcceptOrderTest extends TestCase
{
	public function testTimeOut(){

		$param = array("ordNo" => "201512235991072", "ordPrdSeq" => "1", "connect_timeout" => "1", "apiKey" => "fe868c8788f602061778b49949cf3643");
		$order = new Order("http://api.elevenia.co.id:8000");
		$ret = $order->accept($param);
		$this->assertEquals("0", $ret['code']);

	}

	public function testSuccess(){

		$param = array("ordNo" => "201512286029293", "ordPrdSeq" => "1", "apiKey" => "fe868c8788f602061778b49949cf3643");
		$order = new Order();
		$ret = $order->accept($param);
		$array = json_decode(json_encode((array)$ret['message']), TRUE);

		$this->assertEquals("Order NO: ".$param['ordNo'].", status : Shipping in preparation History", $array["message"]);

	}

	public function testFailed(){
		$param = array("ordNo" => "201512286028790", "ordPrdSeq" => "1", "apiKey" => "fe868c8788f602061778b49949cf3643");
		$order = new Order();
		$ret = $order->accept($param);
		$array = json_decode(json_encode((array)$ret['message']), TRUE);

		$this->assertEquals("ERROR", substr($array["message"], 0, 5));
	}
}