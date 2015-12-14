<?php

namespace App\Http\Controllers;

use App\Http\Controllers;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Validator;
use App\Partner;
use App\Library\SalesOrder\GetOrder;
use App\Library\SalesOrder\Order;

class SalesController extends Controller
{
	private $client;
	
	public function __construct()
	{
		$this->client = new Client();
	}
	
	/**
	 * Get Sales Order From Elevania and save to database
	 * @param Order $order
	 * @param Request $request
	 * @return json
	 */
	public function order(Order $order, $api_key)
	{
		$order = $order->get($api_key); 
		return response()->json($order);
	}
	
}