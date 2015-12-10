<?php

namespace App\Http\Controllers;

use App\Http\Controllers;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Validator;

class SalesOrderController extends Controller
{
	private $client;
	
	public function __construct()
	{
		$this->client = new Client();
	}
	
	/**
	 * Get Sales Order From Elevania
	 * @param string $apikey
	 * @return void
	 */
	public function getOrder()
	{
		
		$url = 'http://api.elevenia.co.id/rest/orderservices/orders?ordStat=202&dateFrom=2015/01/01&dateTo=2015/12/07';
		$res = $this->client->request('GET',$url,[
			'headers' => [
		        'openapikey'      => 'fe868c8788f602061778b49949cf3643'
		    ]			
		]);
		
		return response($res->getBody(), $res->getStatusCode())
			->header('Content-Type', $res->getHeader('Content-Type'));
	}
	
	/**
	 * Create Sales Order Fulfilment
	 * @param Request $request
	 * @return void
	 */
	public function createOrder(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'orderCreatedTime' => 'required',
			'customerInfo.addressee' =>	'required',
			'customerInfo.address1' => 'required',
			'customerInfo.province' => 'required',
			'customerInfo.postalCode' => 'required',
			'customerInfo.country' => 'required',
			'customerInfo.phone' => 'required',
			'customerInfo.email' => 'required',
		]);
		
		if ($validator->fails()) {
			print_r($validator);die;
			return response()->json([
				'message' => $validator->errors->all() 
			], 400);
		}
		
		$url = 'https://fulfillment.api.acommercedev.com/channel/frisianflag/order/FRIS00009';
		$res = $this->client->request('PUT', $url,[
			'header' =>  [
				'X-Subject-Token' => 'a553b82eeb5949b793250e8bcc807f34'
			]
		]);
		$content = $res->getBody();
		return response($content, $res->getStatusCode())
			->headers('Content-Type', $res->getHeader('Content-Type'));
	}
	
	
	
	
}