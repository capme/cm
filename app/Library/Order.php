<?php

namespace App\Library;

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
				'message' => json_decode(json_encode($order), true),
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