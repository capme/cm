<?php

namespace App\Library;

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

    public function parseOrder($order)
    {
        $partnerId = $this->getPartnerId();
        $order = [
            "orderCreatedTime" => gmdate("Y-m-d\TH:i:s\z", strtotime($order['ordStlEndDt'])), // Y (Datetime) - DONE
            "customerInfo" => [
                "addressee" => $order["ordNm"], // Y (String) - Done
                "address1" => "964 Rama 4 Road", // Y (String)
                "province" => "Bangkok", // N (String)
                "postalCode" => "10500", // Y (String)
                "country" => "Thailand", // Y (String)
                "phone" => "081-000-0000", // Y (String)
                "email" => "smith@a.com" // N (Email)
            ],
            "orderShipmentInfo" => [
                "addressee" => $order["rcvrNm"], // Y (String) - DONE
                "address1" => "111 Rama 4 rd.", // Y (String)
                "address2" => "", // N (String)
                "subDistrict" => "Silom", // N (String)
                "district" => "Bangrak", // N (String)
                "city" => "", // N (String)
                "province" => "Bangkok", // N (String)
                "postalCode" => "10500", // Y (String)
                "country" => "Thailand", // Y (String)
                "phone" => $order["rcvrTlphn"], // Y (String)
                "email" => "smith@a.com" // N (Email)
            ],
            "paymentType" => "COD", // Y enum(NON_COD, COD, CCOD)
            "shippingType" => "STANDARD_2_4_DAYS", // Y enum(NEXT_DAY, EXPRESS_1_2_DAYS, STANDARD_2_4_DAYS, NATIONWIDE_3_5_DAYS)
            "grossTotal" => 12800, // N Decimal(.00)
            "currUnit" => "IDR", // N Enum(THB, SGD, IDR, PHP)-DONE
            "orderItems" => [
                [
                    "partnerId" => $partnerId, // Y String
                    "itemId" => "FRSIAN64254110000000M", // Y String
                    "qty" => 2, // Y Int
                    "subTotal" => 6000 // Y Decimal(.00)
                ]
            ]
        ];

        return $order;
    }



    public function store($salesOrder)
    {
        SalesOrder::create($salesOrder);
    }

    private function getPartnerId()
    {
        // Todo - get partner id make it static
        return "143";
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
}