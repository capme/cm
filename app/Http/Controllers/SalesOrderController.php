<?php

namespace App\Http\Controllers;

use App\Http\Controllers;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Validator;
use App\Partner;
use App\Library\SalesOrder\Order;
use Illuminate\Support\Facades\Input;

class SalesOrderController extends Controller
{
	/**
	 * Get Sales Order From Elevania
	 * @param Order $order
	 * @param Request $request
	 * @return json
	 */
	public function get(Order $order, Request $request)
	{
		$validator = Validator::make($request->all(),[
			'apiKey' => 'required',
			'dateFrom' => 'required|date_format:Y-m-d',
			'dateTo' => 'required|date_format:Y-m-d'
		]);
		
		if ($validator->fails()) {
			return response()->json([
				'message' => 'Validation Failed',
				'status'=>'FAILED',
				'errors'=> $validator->messages()
			], 400);
		}
		
		$input = $request->only(['apiKey', 'dateFrom', 'dateTo']);
		$data = $order->get($input);
		
		return response()->json($data);
	}
	
	public function updateAWB()
	{
		
	}
	
	public function accept()
	{
		
	}
	
	public function cancle()
	{
		
	}
	
	/**
	 * Get Sales Order From Elevania and save to database
	 * @param Order $order
	 * @param Request $request
	 * @return json
	 */
	public function save()
	{
		$order_data = $order->get();
		return response($order->save($order_data));
	}
	
	
	
}