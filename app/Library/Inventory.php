<?php
namespace App\Library;

use App\Model\Product;
use SimpleXMLElement;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ConnectException;

class Inventory
{

    public function __construct($token, $timeout = 10)
    {
        $this->client = new Client();
        $this->baseUrl = getenv("ELEVENIA_BASE_API_URL");
        $this->timeout = $timeout;
        $this->token = $token;
    }

    public function getListProduct()
    {
        $url = $this->baseUrl . '/prodservices/product/list';

        $res = $this->request('GET', $url);

        return $res;
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

    public function save($partnerId, $productElevenia)
    {
        $product = [
            "partnerId" => $partnerId,
            "productNo" => $productElevenia['prdNo'],
            "sellerSku" => $productElevenia['sellerPrdCd']
        ];

        return Product::raw()->update(
            [
                "partnerId" => $partnerId,
                "productNo" => $productElevenia['prdNo'],
                "sellerSku" => $productElevenia['sellerPrdCd']
            ],
            ['$setOnInsert' => $product],
            ["upsert" => true]
        );
    }
}