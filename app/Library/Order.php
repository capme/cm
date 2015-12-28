<?php

namespace App\Library;

use GuzzleHttp\Psr7\Response;
use SimpleXMLElement;
use GuzzleHttp\Client;

use App\Model\SalesOrder;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;

class Order
{
	public $client;
	public $baseUrl;

	public function __construct()
	{

		$this->client = new Client();
		$this->baseUrl = 'http://api.elevenia.co.id/rest';

	}
	
	/**
	 * Get order data from elevenia
	 * @param array $input
	 * @return array
	 */
	public function get($input)
	{
		
		$url = $this->baseUrl . '/orderservices/orders?'
			. 'ordStat='.$input['ordStat'].'&dateFrom='
			. $this->eleveniaDate($input['dateFrom'])
			. '&dateTo=' . $this->eleveniaDate($input['dateTo']);
		
		if(!isset($input['connect_timeout'])){
			$input['connect_timeout'] = 3600;
		}
		
		try{
			$res = $this->client->request('GET',$url,[
				'headers' => ['openapikey' => $input['apiKey']],
				'connect_timeout' => $input['connect_timeout'],
				'timeout' => $input['connect_timeout']
			]);
			$xml = $res->getBody();

			$order = new SimpleXMLElement($xml);


			$response = [
                'message' => json_decode(json_encode($order), true),
                'code' => $res->getStatusCode()
            ];
        } catch (RequestException $e) {
            $response = [
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ];
		}
		
		
		return $response;
	}

	public function xmlToArray(Response $res)
	{

	}

	/**
	*Accept order to elevenia
	*/
	public function accept($input)
	{
		$url = $this->baseUrl . '/orderservices/orders/accept?'
		. 'ordNo=' . $input['ordNo']
		. '&ordPrdSeq=' . $input['ordPrdSeq'];

		if(!isset($input['connect_timeout'])){
			$input['connect_timeout'] = 3600;
		}

		try{
			$res = $this->client->request('POST',$url,[
				'headers' => ['openapikey' => $input['apiKey']],
				'connect_timeout' => $input['connect_timeout'],
				'timeout' => $input['connect_timeout']
			]);
			$xml = $res->getBody();
			$order = new SimpleXMLElement($xml);
            $response = [
                'message' => json_decode(json_encode($order), true),
                'code' => $res->getStatusCode()
            ];
        } catch (RequestException $e) {
            $response = [
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ];
		}
		
		
		return $response;
	}

	/**
	 *Send AWB to elevenia
	 */
	public function setAwb($input)
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

		if(!isset($input['connect_timeout'])){
			$input['connect_timeout'] = 3600;
		}

		try{
			$res = $this->client->request('GET',$url,[
				'headers' => ['openapikey' => $input['apiKey']],
				'connect_timeout' => $input['connect_timeout'],
				'timeout' => $input['connect_timeout']
			]);
			$xml = $res->getBody();
			$order = new SimpleXMLElement($xml);
			$response = [
				'message' => json_decode(json_encode($order), true),
				'code' => $res->getStatusCode()
			];
		} catch (RequestException $e) {
			$response = [
				'message' => $e->getMessage(),
				'code' => $e->getCode()
			];
		}


		return $response;
	}

	private function eleveniaDate($date)
	{
		return str_replace('-', '/', $date);
	}
	
}