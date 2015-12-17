<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;

use App\Library\SalesOrder\Order;



class SalesOrderController extends Controller
{
	/**
	 * Get Sales Order From Elevania
	 * @param Order $order
	 * @param Request $request
	 * @return string
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

    /**
     * @param Order $order
     * @param Request $request
     * @return string
     */
	public function accept(Order $order, Request $request)
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
	
	public function cancel()
	{
		
	}
	
	/**
     * Create Fulfillment
     */
	public function createFulfillment()
	{
		
	}
	
}