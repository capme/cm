<?php

namespace App\Library;

use App\Model\Partner;
use App\Model\SalesOrder;
use SimpleXMLElement;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ConnectException;


class Order
{
	public $client;

	private $baseUrl;
	private $timeout;
	private $token;

	public function __construct($token, $baseUrl='http://api.elevenia.co.id/rest', $timeout=10)
	{
		$this->client = new Client();
		$this->baseUrl = $baseUrl;
		$this->timeout = $timeout;
		$this->token = $token;
	}

	public function get($input)
	{
		$url = $this->baseUrl . '/orderservices/orders?'
			. 'ordStat='.$input['ordStat'].'&dateFrom='
			. $this->eleveniaDate($input['dateFrom'])
			. '&dateTo=' . $this->eleveniaDate($input['dateTo']);

		$res = $this->request('GET', $url);
		
		return $res;
	}

	public function accept($input)
	{
		$url = $this->baseUrl . '/orderservices/orders/accept?'
		. 'ordNo=' . $input['ordNo']
		. '&ordPrdSeq=' . $input['ordPrdSeq'];

		$res = $this->request('POST', $url);

		return $res;
	}

	public function updateAWB($input)
	{
		$url = $this->baseUrl . '/orderservices/orders/inputAwb?'
			. 'awb=' . $input['awb']
			. '&dlvNo=' . $input['dlvNo']
			. '&dlvMthdCd=' . $input['dlvMthdCd']
			. '&dlvEtprsCd=' . $input['dlvEtprsCd']
			. '&ordNo=' . $input['ordNo']
			. '&dlvEtprsNm=' . $input['dlvEtprsNm']
			. '&ordPrdSeq=' . $input['ordPrdSeq']
		;

		$res = $this->request('GET', $url);

		return $res;
	}

	public function cancel($input)
	{
		$url = $this->baseUrl . '/orderservices/order/reject?'
			. 'dlvNo=' . $input['dlvNo']
			. '&ordNo=' . $input['ordNo']
			. '&ordPrdSeq=' . $input['ordPrdSeq']
			. '&message=' . $input['message']
			. '&ordCnRsnCd=' . $input['ordCnRsnCd']
			. '&ordQty=' . $input['ordQty']
		;

		$res = $this->request('POST', $url);

		return $res;
	}

	public function parseOrder($partnerId, $order){
		/*
		 *
		 * format return array nya :
		 *
		 * array(
		 * 		"{URL fullfillment by ordNo ke - 1}" => {array regional put sales order},
		 * 		"{URL fullfillment by ordNo ke - 2}" => {array regional put sales order},
		 * 		"{URL fullfillment by ordNo ke - (n)}" => {array regional put sales order}
		 * )
		 *
		 *
		 */
		//$partnerId = $this->getPartnerId();
		$arrFullFillment = array();
		$arrFullFillmentItem = array();

		if(isset($order[0]))
		{
			foreach($order as $key => $data)
			{
				$arrFullFillment[$data['ordNo']] =

					[
								"orderCreatedTime" => gmdate("Y-m-d\TH:i:s\Z", strtotime($data['ordStlEndDt'])),
								"customerInfo" => [
									"addressee" => $data["ordNm"],
									"address1" => $data["rcvrBaseAddr"],
									"province" => "",
									"postalCode" => "0", // Y (String) -> from elevenia doesn't have zip code
									"country" => "Indonesia",
									"phone" => $data['rcvrPrtblNo'],
									"email" => "order@elevenia.co.id"
								],
								"orderShipmentInfo" => [
									"addressee" => $data["rcvrNm"],
									"address1" => $data["rcvrBaseAddr"],
									"address2" => "",
									"subDistrict" => "",
									"district" => "",
									"city" => "",
									"province" => "",
									"postalCode" => "0", // Y (String) -> from elevenia doesn't have zip code
									"country" => "Indonesia",
									"phone" => $data["rcvrTlphn"],
									"email" => "order@elevenia.co.id"
								],
								"paymentType" => "NON_COD",
								"shippingType" => "STANDARD_2_4_DAYS",
								"grossTotal" => (float)number_format($data['orderAmt'], 2, ".", ""),
								"currUnit" => "IDR",
								"orderItems" => null

					];

				$arrFullFillmentItem[$data['ordNo']][] = $data['sellerPrdCd']."|".$data['ordQty']."|".$data['selPrc'];
			}
			foreach($arrFullFillmentItem as $key => $data)
			{
				$orderItem = array();
				foreach($data as $keySub => $dataSub)
				{
					$arrDataSub = explode("|", $dataSub);
					$orderItem[] = [
						"partnerId" => $partnerId,
						"itemId" => $arrDataSub[0],
						"qty" => (int)$arrDataSub[1],
						"subTotal" => (float)$arrDataSub[2]
					];
				}

				$arrFullFillment[$key]['orderItems'] = $orderItem;
			}
		}
		else
		{
			//1 order 1 item
			//$order = $order['order'];
			$orderItem[] = [
				"partnerId" => $partnerId,
				"itemId" => $order['sellerPrdCd'],
				"qty" => (int)$order['ordQty'],
				"subTotal" => (float)$order['selPrc']
			];

			$arrFullFillment =
				[
					$order['ordNo'] =>
					[
						"orderCreatedTime" => gmdate("Y-m-d\TH:i:s\Z", strtotime($order['ordStlEndDt'])),
						"customerInfo" => [
							"addressee" => $order["ordNm"],
							"address1" => $order["rcvrBaseAddr"],
							"province" => "",
							"postalCode" => "0", // Y (String) -> from elevenia doesn't have zip code
							"country" => "Indonesia",
							"phone" => $order['rcvrPrtblNo'],
							"email" => "order@elevenia.co.id"
						],
						"orderShipmentInfo" => [
							"addressee" => $order["rcvrNm"],
							"address1" => $order["rcvrBaseAddr"],
							"address2" => "",
							"subDistrict" => "",
							"district" => "",
							"city" => "",
							"province" => "",
							"postalCode" => "0", // Y (String) -> from elevenia doesn't have zip code
							"country" => "Indonesia",
							"phone" => $order["rcvrTlphn"],
							"email" => "order@elevenia.co.id"
						],
						"paymentType" => "NON_COD",
						"shippingType" => "STANDARD_2_4_DAYS",
						"grossTotal" => (float)number_format($order['orderAmt'], 2, ".", ""),
						"currUnit" => "IDR",
						"orderItems" => $orderItem

					]
				];
		}


		return $arrFullFillment;
	}

	/*
    public function parseOrder($partnerId, $order)

    {
		$orderItem = [];
		if(isset($order[0])) {
			$totAmount = 0;
			foreach($order as $itemOrder)
			{
				$orderItem[] = [
					"partnerId" => $partnerId,
					"itemId" => $itemOrder['sellerPrdCd'],
					"qty" => (int)$itemOrder['ordQty'],
					"subTotal" => (float)$itemOrder['selPrc']
				];
				$totAmount = $totAmount + (int)$itemOrder['selPrc'];
			}
			$order = $order[0];
			$order['orderAmt'] = $totAmount;
		} else {
			$orderItem[] = [
				"partnerId" => $partnerId,
				"itemId" => $order['sellerPrdCd'],
				"qty" => (int)$order['ordQty'],
				"subTotal" => (float)$order['selPrc']
			];
		}

        $orders = [
            "orderCreatedTime" => gmdate("Y-m-d\TH:i:s\Z", strtotime($order['ordStlEndDt'])),
            "customerInfo" => [
                "addressee" => $order["ordNm"],
                "address1" => $order["rcvrBaseAddr"],
                "province" => "",
                "postalCode" => "0",
                "country" => "Indonesia",
                "phone" => $order['rcvrPrtblNo'],
                "email" => "order@elevenia.co.id"
            ],
            "orderShipmentInfo" => [
                "addressee" => $order["rcvrNm"],
                "address1" => $order["rcvrBaseAddr"],
                "address2" => "",
                "subDistrict" => "",
                "district" => "",
                "city" => "",
                "province" => "",
                "postalCode" => "0",
                "country" => "Indonesia",
                "phone" => $order["rcvrTlphn"],
                "email" => "order@elevenia.co.id"
            ],
            "paymentType" => "NON_COD",
            "shippingType" => "STANDARD_2_4_DAYS",
            "grossTotal" => (float)number_format($order['orderAmt'], 2, ".", ""),
            "currUnit" => "IDR",
            "orderItems" => $orderItem
        ];

        return $orders;
    }
	*/

    private function eleveniaDate($date)
	{
		return str_replace('-', '/', $date);
	}

	private function request($method, $url, $options=[])
	{
		try{
			$options = array_merge_recursive(
				[
					'headers' => ['openapikey' => $this->token],
					'timeout' => $this->timeout
				], $options
			);

			$req = $this->client->request($method, $url, $options);
			$xml = $req->getBody();

			$order = new SimpleXMLElement($xml);

			$res = [
				'body' => json_decode(json_encode($order), true),
				'code' => $req->getStatusCode()
			];
		} catch (ClientException $e) {
			$res = [
				'message' => $e->getResponse()->getReasonPhrase(),
				'body' => json_decode($e->getResponse()->getBody(), true),
				'code' => $e->getResponse()->getStatusCode()
			];
		} catch (ServerException $e) {
			$res = [
				'message' => $e->getResponse()->getReasonPhrase(),
				'code' => $e->getResponse()->getStatusCode()
			];
		} catch (ConnectException $e) {
			$handleContext = $e->getHandlerContext();
			$res = [
				'message' => $handleContext['error'],
				'code' => $handleContext['http_code']
			];
		}

		return $res;
	}
	public function save($partnerId, $order)
	{
		$orderRegional = $this->parseOrder($partnerId, $order);
		$arrSalesOrderRegional = array();
		foreach ($orderRegional as $keyRes => $itemRes) {
			$arrSalesOrderRegional[] =
				array(
					"orderId" => $keyRes,
					"channel" =>
						array(
							"name" => "elevenia",
							"sales_order" => $order,
							"last_sync" => date("Y-m-d")
						),
					"acommerce" =>
						array(
							"sales_order" => $itemRes,
							"sales_order_status" => "NEW",
							"last_sync" => date("Y-m-d")
						),
					"created_date" => "",
					"updated_date" => ""
				);
		}

		return SalesOrder::raw()->insert($arrSalesOrderRegional);
	}
}