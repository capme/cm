<?php

namespace App\Library\SalesOrder;

use SimpleXMLElement;
use GuzzleHttp\Client;

use App\Model\SalesOrder;

class Order
{
	private $client;
	private $baseUrl;

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
	 * @param array $input
	 * @return array
	 */
	public function updateAWB($input)
	{
		
	}
	
	/**
	 * Cancellation Product
	 * @return array
	 */
	public function cancel()
	{
		$url = $this->baseUrl . '/claimservice/reqrejectorder?'
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
			. 'ordStat=301&dateFrom='
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
		$order = $this->parseOrder($order);

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
				'order_id' => $order['ordNo'],
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