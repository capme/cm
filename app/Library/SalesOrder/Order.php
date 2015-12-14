<?php

namespace App\Library\SalesOrder;

use SimpleXMLElement;
use GuzzleHttp\Client;

use App\Model\Partner;



class Order
{
	protected $client;
	protected $order_url;
	
	public function __construct()
	{
		$this->client = new Client();
		$this->url = 'http://api.elevenia.co.id/rest/orderservices/orders?ordStat=202&dateFrom=2015/01/01&dateTo=2015/12/30'; 
	}
	
	/**
	 * Format order from elevenia
	 * @param array
	 */
	private function formatOrder($order)
	{
		// TODO - format order to be follow with fulfillment format
		return $order;
	}
	
	/**
	 * Get order data from elevenia
	 * @param string $api_key
	 * @return array
	 */
	public function get($api_key)
	{
		$res = $this->client->request('GET',$this->url,[
			'headers' => [
				'openapikey' => $api_key
			]
		]);
		$xml = $res->getBody();
		$order = new SimpleXMLElement($xml);
		
		return $order;
	}
	
	/**
	 * Save formated order to mongodb
	 * @param array $order
	 */
	public function save($order)
	{
		$order = $this->formatOrder($order); // format order
		// TODO - save to mongodb
	}
}