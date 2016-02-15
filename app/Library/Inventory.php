<?php
namespace App\Library;

use App\Model\ChannelProduct;
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

    public function getListProduct($page = 1)
    {
        $url = $this->baseUrl . '/prodservices/product/list?page='.$page;

        $res = $this->request('GET', $url);

        return $res;
    }

    public function getProductDetail($productNum)
    {
        $url = $this->baseUrl . '/prodservices/product/details/'.$productNum;

        $res = $this->request('GET', $url);

        return $res;
    }

    public function getProductStockNumbers($productNum)
    {
        $url = $this->baseUrl . '/prodmarketservice/prodmarket/stck/' . $productNum;

        $res = $this->request('GET', $url);

        return $res;
    }

    /**
     * @param $sku aCommerce's SKU
     * @return string|bool|null Returns prdStckNo. If data not found in DB, returns false.
     */
    public function getProductStockNumberBySku($sku)
    {
        $row = ChannelProduct::raw()->findOne(['channel'=>'elevenia','items.sku' => $sku], ['prdNo', 'partnerId', 'items.$']);
        if ($row === null) {
            return [
                'code' => 404,
                'message' => 'SKU not found in DB',
            ];
        }

        $res = $this->getProductStockNumbers($row['prdNo']);

        if ($res['code'] !== 200) {
            return $res;
        }

        $item = $row['items'][0];

        foreach ($res['body']['ProductStock'] as $productStock) {
            $mixDtlOptNm = isset($productStock['mixDtlOptNm']) ? $productStock['mixDtlOptNm'] : '';
            $stockOptions = explode(',', $mixDtlOptNm);
            if ($item['variant'] == $stockOptions) {
                return [
                    'code' => 200,
                    'body' => $productStock['prdStckNo'],
                ];
            }
        }

        return [
            'code' => 404,
            'message' => 'Option not found in stock numbers',
        ];
    }

    public function updateProductStock($productId, $productStockNum, $stockQty)
    {
        $url = $this->baseUrl . '/prodservices/stockqty/' . $productStockNum;

        $body = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<ProductStock>
  <prdNo>$productId</prdNo>
  <prdStckNo>$productStockNum</prdStckNo>
  <stckQty>$stockQty</stckQty>
</ProductStock>
EOT;

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