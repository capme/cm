<?php

namespace App\Library;

use SimpleXMLElement;
use GuzzleHttp\Client;

use App\Model\SalesOrder;

class Order
{
	private $client;
	private $baseUrl;

	public function __construct($url=null)
	{
		$this->client = new Client();
		if(!is_null($url)){
			$this->baseUrl = $url;
		}else{
			$this->baseUrl = 'http://api.elevenia.co.id/rest';
		}
		//$this->baseUrl = 'http://api.elevenia.co.id/rest';
	}
	
	/**
	 * Get order data from elevenia
	 * @param array $input
	 * @return array
	 */
	public function get($input)
	{
		
		$url = $this->baseUrl . '/orderservices/orders?'
			. 'ordStat='.$input['ordStat'].'&dateFrom='
			. $this->eleveniaDate($input['dateFrom'])
			. '&dateTo=' . $this->eleveniaDate($input['dateTo']);
		
		if(!isset($input['connect_timeout'])){
			$input['connect_timeout'] = 0;
		}

		$res = $this->client->request('GET',$url,[
			'headers' => ['openapikey' => $input['apiKey'],
			'connect_timeout' => $input['connect_timeout']
			]
		]);
		
		$xml = $res->getBody();
		$order = new SimpleXMLElement($xml);
		
		return $order;
	}
	
	private function eleveniaDate($date)
	{
		return str_replace('-', '/', $date);
	}
	
}