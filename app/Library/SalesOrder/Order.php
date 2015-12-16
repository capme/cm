<?php

namespace App\Library\SalesOrder;

use SimpleXMLElement;
use GuzzleHttp\Client;

use App\Model\Partner;
use App\Model\SalesOrder;



class Order
{
	public $apiKey;
	
	private $client;
	private $baseUrl;
	
	/**
	 * Initiate
	 * @param string $apiKey
	 * @var $client Guzzle Object
	 * @var $base_url elevenia base url 
	 * @return void
	 */
	public function __construct()
	{
		$this->client = new Client();
		$this->baseUrl = 'http://api.elevenia.co.id/rest';
	}
	
	/**
	 * Accept Order
	 * @param array $input
	 * @return array
	 */
	public function accept($input)
	{
		$url = $this->baseUrl . '/orderservices/orders/accept?'
				. 'ordNo=.'. $input['ordNo'] .'&ordPrdSeq=' . $input['ordPrdSeq'];
		
		$res = $this->client->request('GET', $url,[
			'headers' => ['openapikey' => $input['apiKey']],
		]);
		
		return $res->getBody();
	}
	
	/**
	 * Update AWB
	 * @return array
	 */
	public function updateAWB($param)
	{
		
	}
	
	/**
	 * Cancellation Product
	 * @return array
	 */
	public function cancel()
	{
		$url = $this->base_url . '/claimservice/reqrejectorder?'
			. 'orderNo=';
		
	}
	
	/**
	 * Get order data from elevenia
	 * @param array $input
	 * @return array
	 */
	public function get($input)
	{
		
		$url = $this->baseUrl . '/orderservices/orders?'
			. 'ordStat=202&dateFrom='
			. $this->eleveniaDate($input['dateFrom'])
			. '&dateTo=' . $this->eleveniaDate($input['dateTo']);
		
		$res = $this->client->request('GET',$url,[
			'headers' => ['openapikey' => $input['apiKey']]
		]);
		
		$xml = $res->getBody();
		$order = new SimpleXMLElement($xml);
		
		return $order;
	}
	
	/**
	 * Save formated order to mongodb
	 * @param array $order
	 * @return void
	 */
	public function save($order)
	{
		
		SalesOrder::raw()->insert($order);
		return $order;
	}
	
	private function eleveniaDate($date)
	{
		return str_replace('-', '/', $date);
	}
	
	/**
	 * Parse order data
	 * @param array $order
	 * @return array
	 */
	private function parseOrder($order)
	{
		return [
			'elevenia' => $order, 
			'amp' => [
				'order_id' => '81729387',
				'orderCreatedTime' => '2015-06-18T10:30:40Z',
				'customerInfo' => [
					'addressee' => 'Dan Happiness',
					'address1' => '964 Rama 4 Road',
					'province' => 'Bangkok',
					'postalCode' => '10500',
					'country' => 'Thailand',
					'phone' => '081-000-0000',
					'email' => ''
				],
				'orderShipmentInfo' => [
					'addressee' => 'Smith Happiness',
					'address1' => '111 Rama 4 rd.',
					'address2' => '',
					'subDistrict' => 'Bangrak',
					'city' => '',
					'province' => 'Bangkok',
					'postalCode' => '10500',
					'country' => 'Thailand',
					'phone' => '081-111-2222',
					'email' => "smith@a.com"
				],
				'paymentType' => 'COD',
				'shippingType' => 'STANDARD_2_4_DAYS',
				'grossTotal' => 12800,
				'currUnit' => 'THB',
				'orderItems' => [
					[
						'partnerId' => 'maybelline',
						'itemId' => 'NIK64254110000000M',
						'qty' => 2,
						'subTotal' => 6000
					],
					[
						'partnerId' => 'maybelline',
						'itemId' => 'NIK64254110000000M',
						'qty' => 2,
						'subTotal' => 6000
					]
				]
			]
		];
	}
}