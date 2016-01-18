<?php

namespace App\Library;

use App\Model\Partner;
use App\Model\SalesOrder;
use SimpleXMLElement;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ConnectException;
use Log;

class Order
{
	const StatusPaid = '202';
	const StatusAcceptOrder = '301';
	const StatusShippingInProgress = '401';
	const StatusCompleted = '501';
	const StatusCancellationInProgress = '701';
	const StatusConfirmProgress = '901';
	const StatusCancelOrder = 'B01';

	const DateFormat = 'Y/m/d';
	const TimeZone = 'Asia/Jakarta';

	public $client;

	private $baseUrl;
	private $timeout;
	private $token;

	public function __construct($token, $timeout=10)
	{
		$this->client = new Client();
		$this->baseUrl = getenv("ELEVENIA_BASE_API_URL");
		$this->timeout = $timeout;
		$this->token = $token;
	}

	public function get($input)
	{
		$url = $this->baseUrl . '/orderservices/orders?'
			. 'ordStat='.$input['ordStat'].'&dateFrom='
			. $this->eleveniaDate($input['dateFrom'])
			. '&dateTo=' . $this->eleveniaDate($input['dateTo']);

		Log::debug(sprintf('Requesting to [%s]', $url));
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

	public function parseOrderFromEleveniaToCmps($partnerId, $orderElev)
	{
		$orderItem = [];
		foreach($orderElev['productList'] as $val)
		{
			$orderItem[] = [
				"partnerId" => "$partnerId",
				"itemId" => $val['sellerPrdCd'],
				"qty" => (int)$val['ordQty'],
				"subTotal" => (float)number_format($val['ordQty'] * $val['selPrc'], 2, ".", "")
			];
		}

		/* Convert from elevenia date (Asia/Jakarta) to UTC */
		$orderCreatedTime = new \DateTime($orderElev['ordStlEndDt'], new \DateTimeZone('Asia/Jakarta'));
		$orderCreatedTime->setTimezone(new \DateTimeZone('UTC'));

		return [
			"orderCreatedTime" => new \MongoDate($orderCreatedTime->getTimestamp()),
			"customerInfo" => [
				"addressee" => $orderElev["ordNm"],
				"address1" => $orderElev["rcvrBaseAddr"],
				"province" => "",
				"postalCode" => "0",
				"country" => "Indonesia",
				"phone" => $orderElev['rcvrPrtblNo'],
				"email" => "order@elevenia.co.id"
			],
			"orderShipmentInfo" => [
				"addressee" => $orderElev["rcvrNm"],
				"address1" => $orderElev["rcvrBaseAddr"],
				"address2" => "",
				"subDistrict" => "",
				"district" => "",
				"city" => "",
				"province" => "",
				"postalCode" => "0",
				"country" => "Indonesia",
				"phone" => $orderElev["rcvrTlphn"],
				"email" => "order@elevenia.co.id"
			],
			"paymentType" => "NON_COD",
			"shippingType" => "STANDARD_2_4_DAYS",
			"grossTotal" => (float)number_format($orderElev['orderAmt'], 2, ".", ""),
			"currUnit" => "IDR",
			"orderItems" => $orderItem

		];
	}

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

	/**
	 * Normalize orders from Elevenia because they are lazy ass programmers
	 *
	 * @param $orders
	 * @return array order
	 */
	public function parseOrdersFromElevenia($orders) {
		$parsedOrders = [];
		if (!isset($orders[0])) {
			$orders = [$orders];
		}
		foreach ($orders as $elevOrder) {
			$ordNo = $elevOrder['ordNo'];
			$orderProduct = $elevOrder['orderProduct'];
			$orderProduct['prdClfCdNm'] = $elevOrder['prdClfCdNm'];
			$orderProduct['prdNm'] = $elevOrder['prdNm'];
			$orderProduct['prdNo'] = $elevOrder['prdNo'];
			if (!isset($parsedOrders[$ordNo])) {
				unset($elevOrder['prdClfCdNm'], $elevOrder['prdNm'], $elevOrder['prdNo'], $elevOrder['orderProduct']);
				$elevOrder['productList'] = [];
				$parsedOrders[$ordNo] = $elevOrder;
			}
			$parsedOrders[$ordNo]['productList'][] = $orderProduct;
		}
		return $parsedOrders;
	}

	public function save($partnerId, $orderElevenia)
	{
		$orderCmps = $this->parseOrderFromEleveniaToCmps($partnerId, $orderElevenia);
		$order = [
			"orderId" => $orderElevenia['ordNo'],
			"partnerId" => $partnerId,
			"channel" =>
				[
					"name" => "elevenia",
					"order" => $orderElevenia,
					"lastSync" => new \MongoDate()
				],
			"acommerce" =>
				[
					"order" => $orderCmps,
					"lastSync" => new \MongoDate()
				],
			"status" => "NEW",
			"createdDate" => new \MongoDate(),
			"updatedDate" => new \MongoDate()
		];

		return SalesOrder::raw()->update(
			[
				"orderId" => $orderElevenia["ordNo"],
				"partnerId" => $partnerId,
				"channel.name" => "elevenia"
			],
			['$setOnInsert' => $order],
			["upsert" => true]
		);
	}
}