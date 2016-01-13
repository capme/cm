<?php

use App\Library\Order;

use GuzzleHttp\Handler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client;
use Acommerce\Cmp\SalesOrder;
use Acommerce\Cmp\SalesOrderStatus;
use Acommerce\Cmp\Auth;

class SalesOrderTest extends TestCase
{
    public $token;
    public $mockEnabled;

    public function setUp()
    {
        parent::setUp();
        $this->token = 'fe868c8788f602061778b49949cf3643';
        $this->mockEnabled = true;
    }

    public function testServerClientNetworkError()
    {
        $order = new Order($this->token);
        $input = ["ordStat" => "301", "dateFrom" => "2015-12-28", "dateTo" => "2015-12-28"];

        $handlerContext = [
            'errno' => 28,
            'error' => 'Connection timed out after 10004 milliseconds',
            'http_code' => 0
        ];

        $this->mockSalesOrder($order, [
            new Response(501),
            new Response(404),
            new ConnectException('Network Error', new Request('GET', $this->baseUrl), null, $handlerContext)
        ]);


        $res = $order->get($input);
        $this->assertEquals(501, $res['code']);

        $res = $order->get($input);
        $this->assertEquals(404, $res['code']);


        $res = $order->get($input);
        $this->assertEquals(0, $res['code']);
    }


    public function testGetSuccess()
    {
        $order = new Order($this->token);

        $xml = "<Orders><order></order><order></order></Orders>";
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);

        $input = ["ordStat" => "202", "dateFrom" => "2016-01-08", "dateTo" => "2016-01-08"];
        $res = $order->get($input);

        $this->assertEquals(200, $res['code']);
        $this->assertArrayHasKey('order', $res['body']);
    }

    public function testGetEmpty()
    {
        $order = new Order($this->token);

        $xml = "<Orders><order></order><order></order></Orders>";
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);

        $input = ["ordStat" => "301", "dateFrom" => "2015-12-28", "dateTo" => "2015-12-28"];
        $res = $order->get($input);

        $this->assertEquals(200, $res['code']);
        $this->assertArrayHasKey('order', $res['body']);
    }

    public function testAcceptSuccess()
    {
        $order = new Order($this->token);

        $xml = "<ClientMessage><resultCode>200</resultCode><message>"
            . "Order NO:xxxxxxxxx, status : Shipping in preparation History</message></ClientMessage>";
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);

        $input = ["ordNo" => "201601116139059", "ordPrdSeq" => "1"];
        $ret = $order->accept($input);

        $array = json_decode(json_encode((array)$ret['body']), TRUE);

        $this->assertEquals("Order NO", substr($array["message"],0,8));
    }

    public function testAcceptFailed()
    {
        $order = new Order($this->token);

        $xml = "<ClientMessage><resultCode>200</resultCode><message>"
            . "ERROR Accept Order: Transaction Error</message></ClientMessage>";
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);

        $input = ["ordNo" => "201512286029293", "ordPrdSeq" => "1"];
        $ret = $order->accept($input);
        $array = json_decode(json_encode((array)$ret['body']), TRUE);
        $this->assertEquals("ERROR", substr($array["message"],0,5));
    }

    public function testUpdateAWBSuccess()
    {
        $order = new Order($this->token);

        $input = ["awb" => "JNE12345", "dlvNo" => "8000028244", "dlvMthdCd" => "01",
            "dlvEtprsCd" => "00301", "ordNo" => "201512286028790", "dlvEtprsNm" => "TIKI Regular",
            "ordPrdSeq" => "1"];

        $xml = "<ClientMessage><resultCode>200</resultCode><message>SUCCES: order# " . $input['ordNo']
            . ", ord_prd_seq: ".$input['ordPrdSeq']." status is now On Shipping</message></ClientMessage>";
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);
        $ret = $order->updateAWB($input);
        $array = json_decode(json_encode((array)$ret['body']), TRUE);
        $this->assertEquals("SUCCES", substr($array["message"],0,6));
    }

    public function testUpdateAWBFailed()
    {
        $order = new Order($this->token);

        $xml = "<ClientMessage><resultCode>200</resultCode><message>ERROR xxxxxxxxxxxxxx</message></ClientMessage>";
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);

        $input = ["awb" => "JNE12345", "dlvNo" => "8000027521", "dlvMthdCd" => "01",
            "dlvEtprsCd" => "00301", "ordNo" => "201512165928898", "dlvEtprsNm" => "TIKI Regular",
            "ordPrdSeq" => "1"];
        $ret = $order->updateAWB($input);

        $array = json_decode(json_encode((array)$ret['body']), TRUE);
        $this->assertEquals("ERROR", substr($array["message"], 0, 5));
    }

    public function testCancelSuccess()
    {
        $order = new Order($this->token);

        $input = ["dlvNo" => "8000028448", "ordNo" => "201512296037598", "ordPrdSeq" => "1",
            "message" => "test ajah cancel", "ordCnRsnCd" => "99", "ordQty" => "1"];
        $xml = "<ClientMessage><productNo>xxxxxxx</productNo><message>Order: " . $input['ordNo']
            . " has been cancelled.</message><resultCode>200</resultCode></ClientMessage>";
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);
        $ret = $order->cancel($input);
        $array = json_decode(json_encode((array)$ret['body']), TRUE);
        $this->assertArrayHasKey("productNo", $array);
    }

    public function testCancelFailed()
    {
        $order = new Order($this->token);

        $xml = "<ClientMessage><resultCode>200</resultCode><message>ERROR when attempting "
            . "to cancel order: Invalid order number or delivery number, please check ordNo and "
            . "dlvNo parameters.</message></ClientMessage>";
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);

        $input = ["dlvNo" => "8000027599", "ordNo" => "201512165930342", "ordPrdSeq" => "1",
            "message" => "test ajah cancel", "ordCnRsnCd" => "99", "ordQty" => "1"];

        $ret = $order->cancel($input);
        $array = json_decode(json_encode((array)$ret['body']), TRUE);
        $this->assertEquals("ERROR", substr($array["message"], 0, 5));
        $this->assertArrayNotHasKey("productNo",$array);
    }

    public function testOneOrderOneItem()
    {
        $order = new Order($this->token);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <Orders>
            <order>
                <addPrdNo>0</addPrdNo>
                <addPrdYn>N</addPrdYn>
                <appmtDdDlvDy>General shipping</appmtDdDlvDy>
                <buyMemNo>26000820</buyMemNo>
                <delvlaceSeq>140116389</delvlaceSeq>
                <dlvCstStlTypNm>Prepaid Delivery Fee</dlvCstStlTypNm>
                <dlvEtprsCd>00301</dlvEtprsCd>
                <dlvEtprsNm>TIKI Regular</dlvEtprsNm>
                <dlvKdCdName>General shipping</dlvKdCdName>
                <dlvMthdCd>01</dlvMthdCd>
                <dlvMthdCdNm>Courier service</dlvMthdCdNm>
                <dlvNo>8000028633</dlvNo>
                <invcUpdateDt></invcUpdateDt>
                <lstDlvCst>9,000</lstDlvCst>
                <memId>mobi***********************</memId>
                <ordDlvReqCont></ordDlvReqCont>
                <ordDt>2016/01/11</ordDt>
                <ordNm>Elevenia</ordNm>
                <ordNo>201601116139059</ordNo>
                <ordPrdSeq>1</ordPrdSeq>
                <ordPrdStat>202</ordPrdStat>
                <ordQty>1</ordQty>
                <ordStlEndDt>2016/01/11 09:29:32</ordStlEndDt>
                <orderAmt>25000</orderAmt>
                <orderProduct>
                    <advrt_stmt></advrt_stmt>
                    <appmtDdDlvDy>General shipping</appmtDdDlvDy>
                    <atmt_buy_cnfrm_yn></atmt_buy_cnfrm_yn>
                    <barCode></barCode>
                    <batchYn>false</batchYn>
                    <bonusDscAmt>0</bonusDscAmt>
                    <bonusDscGb></bonusDscGb>
                    <bonusDscRt>0.0</bonusDscRt>
                    <chinaSaleYn></chinaSaleYn>
                    <ctgrCupnExYn>N</ctgrCupnExYn>
                    <ctgrPntPreRt></ctgrPntPreRt>
                    <ctgrPntPreRtAmt></ctgrPntPreRtAmt>
                    <cupnDlv>0</cupnDlv>
                    <deliveryTimeOut>false</deliveryTimeOut>
                    <delvplaceSeq>140116389</delvplaceSeq>
                    <dlvInsOrgCst>0</dlvInsOrgCst>
                    <dlvNo>8000028633</dlvNo>
                    <dlvRewardAmt>0</dlvRewardAmt>
                    <errMsg></errMsg>
                    <finalDscPrc></finalDscPrc>
                    <firstDscAmt>0</firstDscAmt>
                    <fixedDlvPrd>false</fixedDlvPrd>
                    <giftPrdOptNo>0</giftPrdOptNo>
                    <imgurl></imgurl>
                    <isChangePayMethod>N</isChangePayMethod>
                    <isHistory>Y</isHistory>
                    <limitDt></limitDt>
                    <lowPrcCompYn></lowPrcCompYn>
                    <mileDscAmt>0.0</mileDscAmt>
                    <mileDscRt>0.0</mileDscRt>
                    <mileSaveAmt>0.0</mileSaveAmt>
                    <mileSaveRt>0.0</mileSaveRt>
                    <ordPrdCpnAmtWithoutJang>0</ordPrdCpnAmtWithoutJang>
                    <ordPrdRewardAmt></ordPrdRewardAmt>
                    <ordPrdRewardItmCd></ordPrdRewardItmCd>
                    <ordPrdRewardItmCdNm></ordPrdRewardItmCdNm>
                    <ordPrdSeq>1</ordPrdSeq>
                    <ordPrdStat>202</ordPrdStat>
                    <ordQty>1</ordQty>
                    <orgBonusDscRt>0.0</orgBonusDscRt>
                    <pluDscAmt>0</pluDscAmt>
                    <pluDscBasis>0</pluDscBasis>
                    <pluDscRt>0.0</pluDscRt>
                    <plusDscOcbRwd>0</plusDscOcbRwd>
                    <prdNm>Product FRSIANPRODUCT000002 New</prdNm>
                    <prdNo>250911</prdNo>
                    <prdOptSpcNo>0</prdOptSpcNo>
                    <prdTtoDisconAmt>0</prdTtoDisconAmt>
                    <prdTypCd>01</prdTypCd>
                    <procReturnDlvCstByBndl>false</procReturnDlvCstByBndl>
                    <returnDlvCstByBndl>0</returnDlvCstByBndl>
                    <rfndMtdCd></rfndMtdCd>
                    <selPrc>25000</selPrc>
                    <sellerPrdCd>FRSIANPRODUCT000002</sellerPrdCd>
                    <stPntDscAmt>0.0</stPntDscAmt>
                    <stPntDscRt>0.0</stPntDscRt>
                    <stlStat></stlStat>
                    <tiketSelPrc>0</tiketSelPrc>
                    <tiketTransFee>0</tiketTransFee>
                    <visitDlvYn>N</visitDlvYn>
                </orderProduct>
                <prdClfCdNm>Ready Stock</prdClfCdNm>
                <prdNm>Product FRSIANPRODUCT000002 New</prdNm>
                <prdNo>250911</prdNo>
                <rcvrBaseAddr>Andir Kota Bandung JAWA BARAT UKNOWN</rcvrBaseAddr>
                <rcvrMailNo>SMI-009008</rcvrMailNo>
                <rcvrNm>Elevenia</rcvrNm>
                <rcvrPrtblNo>0878-81181818</rcvrPrtblNo>
                <rcvrTlphn>0878-81181818</rcvrTlphn>
                <selFeeAmt>32750</selFeeAmt>
                <selFeeRt>1,250(5.00%)</selFeeRt>
                <selFixedFee>5.00(%)</selFixedFee>
                <selPrc>25000</selPrc>
                <sellerDscPrc>0</sellerDscPrc>
                <sellerPrdCd>FRSIANPRODUCT000002</sellerPrdCd>
                <sndPlnDd></sndPlnDd>
                <tmallApplyDscAmt>0</tmallApplyDscAmt>
            </order>
        </Orders>';
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);

        $input = ["ordStat" => "202", "dateFrom" => "2016-01-08", "dateTo" => "2016-01-08"];
        $res = $order->get($input);
        $parsedRes = $order->parseOrder(143, $res['body']['order']);

        $this->assertEquals(200, $res['code']);
        $this->assertEquals(1, count($parsedRes));
        $this->assertEquals(1, count($parsedRes['201601116139059']['orderItems']));
    }

    public function testOneOrderMoreThenOneItem()
    {
        $order = new Order($this->token);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <Orders>
            <order>
                <addPrdNo>0</addPrdNo>
                <addPrdYn>N</addPrdYn>
                <appmtDdDlvDy>General shipping</appmtDdDlvDy>
                <buyMemNo>26000820</buyMemNo>
                <delvlaceSeq>140116389</delvlaceSeq>
                <dlvCstStlTypNm>Prepaid Delivery Fee</dlvCstStlTypNm>
                <dlvEtprsCd>00301</dlvEtprsCd>
                <dlvEtprsNm>TIKI Regular</dlvEtprsNm>
                <dlvKdCdName>General shipping</dlvKdCdName>
                <dlvMthdCd>01</dlvMthdCd>
                <dlvMthdCdNm>Courier service</dlvMthdCdNm>
                <dlvNo>8000028633</dlvNo>
                <invcUpdateDt></invcUpdateDt>
                <lstDlvCst>9,000</lstDlvCst>
                <memId>mobi***********************</memId>
                <ordDlvReqCont></ordDlvReqCont>
                <ordDt>2016/01/11</ordDt>
                <ordNm>Elevenia</ordNm>
                <ordNo>201601116139059</ordNo>
                <ordPrdSeq>1</ordPrdSeq>
                <ordPrdStat>202</ordPrdStat>
                <ordQty>1</ordQty>
                <ordStlEndDt>2016/01/11 09:29:32</ordStlEndDt>
                <orderAmt>25000</orderAmt>
                <orderProduct>
                    <advrt_stmt></advrt_stmt>
                    <appmtDdDlvDy>General shipping</appmtDdDlvDy>
                    <atmt_buy_cnfrm_yn></atmt_buy_cnfrm_yn>
                    <barCode></barCode>
                    <batchYn>false</batchYn>
                    <bonusDscAmt>0</bonusDscAmt>
                    <bonusDscGb></bonusDscGb>
                    <bonusDscRt>0.0</bonusDscRt>
                    <chinaSaleYn></chinaSaleYn>
                    <ctgrCupnExYn>N</ctgrCupnExYn>
                    <ctgrPntPreRt></ctgrPntPreRt>
                    <ctgrPntPreRtAmt></ctgrPntPreRtAmt>
                    <cupnDlv>0</cupnDlv>
                    <deliveryTimeOut>false</deliveryTimeOut>
                    <delvplaceSeq>140116389</delvplaceSeq>
                    <dlvInsOrgCst>0</dlvInsOrgCst>
                    <dlvNo>8000028633</dlvNo>
                    <dlvRewardAmt>0</dlvRewardAmt>
                    <errMsg></errMsg>
                    <finalDscPrc></finalDscPrc>
                    <firstDscAmt>0</firstDscAmt>
                    <fixedDlvPrd>false</fixedDlvPrd>
                    <giftPrdOptNo>0</giftPrdOptNo>
                    <imgurl></imgurl>
                    <isChangePayMethod>N</isChangePayMethod>
                    <isHistory>Y</isHistory>
                    <limitDt></limitDt>
                    <lowPrcCompYn></lowPrcCompYn>
                    <mileDscAmt>0.0</mileDscAmt>
                    <mileDscRt>0.0</mileDscRt>
                    <mileSaveAmt>0.0</mileSaveAmt>
                    <mileSaveRt>0.0</mileSaveRt>
                    <ordPrdCpnAmtWithoutJang>0</ordPrdCpnAmtWithoutJang>
                    <ordPrdRewardAmt></ordPrdRewardAmt>
                    <ordPrdRewardItmCd></ordPrdRewardItmCd>
                    <ordPrdRewardItmCdNm></ordPrdRewardItmCdNm>
                    <ordPrdSeq>1</ordPrdSeq>
                    <ordPrdStat>202</ordPrdStat>
                    <ordQty>1</ordQty>
                    <orgBonusDscRt>0.0</orgBonusDscRt>
                    <pluDscAmt>0</pluDscAmt>
                    <pluDscBasis>0</pluDscBasis>
                    <pluDscRt>0.0</pluDscRt>
                    <plusDscOcbRwd>0</plusDscOcbRwd>
                    <prdNm>Product FRSIANPRODUCT000002 New</prdNm>
                    <prdNo>250911</prdNo>
                    <prdOptSpcNo>0</prdOptSpcNo>
                    <prdTtoDisconAmt>0</prdTtoDisconAmt>
                    <prdTypCd>01</prdTypCd>
                    <procReturnDlvCstByBndl>false</procReturnDlvCstByBndl>
                    <returnDlvCstByBndl>0</returnDlvCstByBndl>
                    <rfndMtdCd></rfndMtdCd>
                    <selPrc>25000</selPrc>
                    <sellerPrdCd>FRSIANPRODUCT000002</sellerPrdCd>
                    <stPntDscAmt>0.0</stPntDscAmt>
                    <stPntDscRt>0.0</stPntDscRt>
                    <stlStat></stlStat>
                    <tiketSelPrc>0</tiketSelPrc>
                    <tiketTransFee>0</tiketTransFee>
                    <visitDlvYn>N</visitDlvYn>
                </orderProduct>
                <prdClfCdNm>Ready Stock</prdClfCdNm>
                <prdNm>Product FRSIANPRODUCT000002 New</prdNm>
                <prdNo>250911</prdNo>
                <rcvrBaseAddr>Andir Kota Bandung JAWA BARAT UKNOWN</rcvrBaseAddr>
                <rcvrMailNo>SMI-009008</rcvrMailNo>
                <rcvrNm>Elevenia</rcvrNm>
                <rcvrPrtblNo>0878-81181818</rcvrPrtblNo>
                <rcvrTlphn>0878-81181818</rcvrTlphn>
                <selFeeAmt>32750</selFeeAmt>
                <selFeeRt>1,250(5.00%)</selFeeRt>
                <selFixedFee>5.00(%)</selFixedFee>
                <selPrc>25000</selPrc>
                <sellerDscPrc>0</sellerDscPrc>
                <sellerPrdCd>FRSIANPRODUCT000002</sellerPrdCd>
                <sndPlnDd></sndPlnDd>
                <tmallApplyDscAmt>0</tmallApplyDscAmt>
            </order>
            <order>
                <addPrdNo>0</addPrdNo>
                <addPrdYn>N</addPrdYn>
                <appmtDdDlvDy>General shipping</appmtDdDlvDy>
                <buyMemNo>26000820</buyMemNo>
                <delvlaceSeq>140116389</delvlaceSeq>
                <dlvCstStlTypNm>Prepaid Delivery Fee</dlvCstStlTypNm>
                <dlvEtprsCd>00301</dlvEtprsCd>
                <dlvEtprsNm>TIKI Regular</dlvEtprsNm>
                <dlvKdCdName>General shipping</dlvKdCdName>
                <dlvMthdCd>01</dlvMthdCd>
                <dlvMthdCdNm>Courier service</dlvMthdCdNm>
                <dlvNo>8000028633</dlvNo>
                <invcUpdateDt></invcUpdateDt>
                <lstDlvCst>9,000</lstDlvCst>
                <memId>mobi***********************</memId>
                <ordDlvReqCont></ordDlvReqCont>
                <ordDt>2016/01/11</ordDt>
                <ordNm>Elevenia</ordNm>
                <ordNo>201601116139059</ordNo>
                <ordPrdSeq>1</ordPrdSeq>
                <ordPrdStat>202</ordPrdStat>
                <ordQty>1</ordQty>
                <ordStlEndDt>2016/01/11 09:29:32</ordStlEndDt>
                <orderAmt>25000</orderAmt>
                <orderProduct>
                    <advrt_stmt></advrt_stmt>
                    <appmtDdDlvDy>General shipping</appmtDdDlvDy>
                    <atmt_buy_cnfrm_yn></atmt_buy_cnfrm_yn>
                    <barCode></barCode>
                    <batchYn>false</batchYn>
                    <bonusDscAmt>0</bonusDscAmt>
                    <bonusDscGb></bonusDscGb>
                    <bonusDscRt>0.0</bonusDscRt>
                    <chinaSaleYn></chinaSaleYn>
                    <ctgrCupnExYn>N</ctgrCupnExYn>
                    <ctgrPntPreRt></ctgrPntPreRt>
                    <ctgrPntPreRtAmt></ctgrPntPreRtAmt>
                    <cupnDlv>0</cupnDlv>
                    <deliveryTimeOut>false</deliveryTimeOut>
                    <delvplaceSeq>140116389</delvplaceSeq>
                    <dlvInsOrgCst>0</dlvInsOrgCst>
                    <dlvNo>8000028633</dlvNo>
                    <dlvRewardAmt>0</dlvRewardAmt>
                    <errMsg></errMsg>
                    <finalDscPrc></finalDscPrc>
                    <firstDscAmt>0</firstDscAmt>
                    <fixedDlvPrd>false</fixedDlvPrd>
                    <giftPrdOptNo>0</giftPrdOptNo>
                    <imgurl></imgurl>
                    <isChangePayMethod>N</isChangePayMethod>
                    <isHistory>Y</isHistory>
                    <limitDt></limitDt>
                    <lowPrcCompYn></lowPrcCompYn>
                    <mileDscAmt>0.0</mileDscAmt>
                    <mileDscRt>0.0</mileDscRt>
                    <mileSaveAmt>0.0</mileSaveAmt>
                    <mileSaveRt>0.0</mileSaveRt>
                    <ordPrdCpnAmtWithoutJang>0</ordPrdCpnAmtWithoutJang>
                    <ordPrdRewardAmt></ordPrdRewardAmt>
                    <ordPrdRewardItmCd></ordPrdRewardItmCd>
                    <ordPrdRewardItmCdNm></ordPrdRewardItmCdNm>
                    <ordPrdSeq>1</ordPrdSeq>
                    <ordPrdStat>202</ordPrdStat>
                    <ordQty>1</ordQty>
                    <orgBonusDscRt>0.0</orgBonusDscRt>
                    <pluDscAmt>0</pluDscAmt>
                    <pluDscBasis>0</pluDscBasis>
                    <pluDscRt>0.0</pluDscRt>
                    <plusDscOcbRwd>0</plusDscOcbRwd>
                    <prdNm>Product FRSIANPRODUCT000002 New</prdNm>
                    <prdNo>250911</prdNo>
                    <prdOptSpcNo>0</prdOptSpcNo>
                    <prdTtoDisconAmt>0</prdTtoDisconAmt>
                    <prdTypCd>01</prdTypCd>
                    <procReturnDlvCstByBndl>false</procReturnDlvCstByBndl>
                    <returnDlvCstByBndl>0</returnDlvCstByBndl>
                    <rfndMtdCd></rfndMtdCd>
                    <selPrc>25000</selPrc>
                    <sellerPrdCd>FRSIANPRODUCT000002</sellerPrdCd>
                    <stPntDscAmt>0.0</stPntDscAmt>
                    <stPntDscRt>0.0</stPntDscRt>
                    <stlStat></stlStat>
                    <tiketSelPrc>0</tiketSelPrc>
                    <tiketTransFee>0</tiketTransFee>
                    <visitDlvYn>N</visitDlvYn>
                </orderProduct>
                <prdClfCdNm>Ready Stock</prdClfCdNm>
                <prdNm>Product FRSIANPRODUCT000002 New</prdNm>
                <prdNo>250911</prdNo>
                <rcvrBaseAddr>Andir Kota Bandung JAWA BARAT UKNOWN</rcvrBaseAddr>
                <rcvrMailNo>SMI-009008</rcvrMailNo>
                <rcvrNm>Elevenia</rcvrNm>
                <rcvrPrtblNo>0878-81181818</rcvrPrtblNo>
                <rcvrTlphn>0878-81181818</rcvrTlphn>
                <selFeeAmt>32750</selFeeAmt>
                <selFeeRt>1,250(5.00%)</selFeeRt>
                <selFixedFee>5.00(%)</selFixedFee>
                <selPrc>25000</selPrc>
                <sellerDscPrc>0</sellerDscPrc>
                <sellerPrdCd>FRSIANPRODUCT000003</sellerPrdCd>
                <sndPlnDd></sndPlnDd>
                <tmallApplyDscAmt>0</tmallApplyDscAmt>
            </order>
        </Orders>';
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);

        $input = ["ordStat" => "202", "dateFrom" => "2016-01-08", "dateTo" => "2016-01-08"];
        $res = $order->get($input);
        $parsedRes = $order->parseOrder(143, $res['body']['order']);

        $this->assertEquals(200, $res['code']);
        $this->assertEquals(1, count($parsedRes));
        $this->assertEquals(2, count($parsedRes['201601116139059']['orderItems']));
    }

    public function testMoreThenOneOrderOneItem()
    {
        $order = new Order($this->token);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <Orders>
            <order>
                <addPrdNo>0</addPrdNo>
                <addPrdYn>N</addPrdYn>
                <appmtDdDlvDy>General shipping</appmtDdDlvDy>
                <buyMemNo>26000820</buyMemNo>
                <delvlaceSeq>140116389</delvlaceSeq>
                <dlvCstStlTypNm>Prepaid Delivery Fee</dlvCstStlTypNm>
                <dlvEtprsCd>00301</dlvEtprsCd>
                <dlvEtprsNm>TIKI Regular</dlvEtprsNm>
                <dlvKdCdName>General shipping</dlvKdCdName>
                <dlvMthdCd>01</dlvMthdCd>
                <dlvMthdCdNm>Courier service</dlvMthdCdNm>
                <dlvNo>8000028633</dlvNo>
                <invcUpdateDt></invcUpdateDt>
                <lstDlvCst>9,000</lstDlvCst>
                <memId>mobi***********************</memId>
                <ordDlvReqCont></ordDlvReqCont>
                <ordDt>2016/01/11</ordDt>
                <ordNm>Elevenia</ordNm>
                <ordNo>11</ordNo>
                <ordPrdSeq>1</ordPrdSeq>
                <ordPrdStat>202</ordPrdStat>
                <ordQty>1</ordQty>
                <ordStlEndDt>2016/01/11 09:29:32</ordStlEndDt>
                <orderAmt>25000</orderAmt>
                <orderProduct>
                    <advrt_stmt></advrt_stmt>
                    <appmtDdDlvDy>General shipping</appmtDdDlvDy>
                    <atmt_buy_cnfrm_yn></atmt_buy_cnfrm_yn>
                    <barCode></barCode>
                    <batchYn>false</batchYn>
                    <bonusDscAmt>0</bonusDscAmt>
                    <bonusDscGb></bonusDscGb>
                    <bonusDscRt>0.0</bonusDscRt>
                    <chinaSaleYn></chinaSaleYn>
                    <ctgrCupnExYn>N</ctgrCupnExYn>
                    <ctgrPntPreRt></ctgrPntPreRt>
                    <ctgrPntPreRtAmt></ctgrPntPreRtAmt>
                    <cupnDlv>0</cupnDlv>
                    <deliveryTimeOut>false</deliveryTimeOut>
                    <delvplaceSeq>140116389</delvplaceSeq>
                    <dlvInsOrgCst>0</dlvInsOrgCst>
                    <dlvNo>8000028633</dlvNo>
                    <dlvRewardAmt>0</dlvRewardAmt>
                    <errMsg></errMsg>
                    <finalDscPrc></finalDscPrc>
                    <firstDscAmt>0</firstDscAmt>
                    <fixedDlvPrd>false</fixedDlvPrd>
                    <giftPrdOptNo>0</giftPrdOptNo>
                    <imgurl></imgurl>
                    <isChangePayMethod>N</isChangePayMethod>
                    <isHistory>Y</isHistory>
                    <limitDt></limitDt>
                    <lowPrcCompYn></lowPrcCompYn>
                    <mileDscAmt>0.0</mileDscAmt>
                    <mileDscRt>0.0</mileDscRt>
                    <mileSaveAmt>0.0</mileSaveAmt>
                    <mileSaveRt>0.0</mileSaveRt>
                    <ordPrdCpnAmtWithoutJang>0</ordPrdCpnAmtWithoutJang>
                    <ordPrdRewardAmt></ordPrdRewardAmt>
                    <ordPrdRewardItmCd></ordPrdRewardItmCd>
                    <ordPrdRewardItmCdNm></ordPrdRewardItmCdNm>
                    <ordPrdSeq>1</ordPrdSeq>
                    <ordPrdStat>202</ordPrdStat>
                    <ordQty>1</ordQty>
                    <orgBonusDscRt>0.0</orgBonusDscRt>
                    <pluDscAmt>0</pluDscAmt>
                    <pluDscBasis>0</pluDscBasis>
                    <pluDscRt>0.0</pluDscRt>
                    <plusDscOcbRwd>0</plusDscOcbRwd>
                    <prdNm>Product FRSIANPRODUCT000002 New</prdNm>
                    <prdNo>250911</prdNo>
                    <prdOptSpcNo>0</prdOptSpcNo>
                    <prdTtoDisconAmt>0</prdTtoDisconAmt>
                    <prdTypCd>01</prdTypCd>
                    <procReturnDlvCstByBndl>false</procReturnDlvCstByBndl>
                    <returnDlvCstByBndl>0</returnDlvCstByBndl>
                    <rfndMtdCd></rfndMtdCd>
                    <selPrc>25000</selPrc>
                    <sellerPrdCd>FRSIANPRODUCT000002</sellerPrdCd>
                    <stPntDscAmt>0.0</stPntDscAmt>
                    <stPntDscRt>0.0</stPntDscRt>
                    <stlStat></stlStat>
                    <tiketSelPrc>0</tiketSelPrc>
                    <tiketTransFee>0</tiketTransFee>
                    <visitDlvYn>N</visitDlvYn>
                </orderProduct>
                <prdClfCdNm>Ready Stock</prdClfCdNm>
                <prdNm>Product FRSIANPRODUCT000002 New</prdNm>
                <prdNo>250911</prdNo>
                <rcvrBaseAddr>Andir Kota Bandung JAWA BARAT UKNOWN</rcvrBaseAddr>
                <rcvrMailNo>SMI-009008</rcvrMailNo>
                <rcvrNm>Elevenia</rcvrNm>
                <rcvrPrtblNo>0878-81181818</rcvrPrtblNo>
                <rcvrTlphn>0878-81181818</rcvrTlphn>
                <selFeeAmt>32750</selFeeAmt>
                <selFeeRt>1,250(5.00%)</selFeeRt>
                <selFixedFee>5.00(%)</selFixedFee>
                <selPrc>25000</selPrc>
                <sellerDscPrc>0</sellerDscPrc>
                <sellerPrdCd>FRSIANPRODUCT000002</sellerPrdCd>
                <sndPlnDd></sndPlnDd>
                <tmallApplyDscAmt>0</tmallApplyDscAmt>
            </order>
            <order>
                <addPrdNo>0</addPrdNo>
                <addPrdYn>N</addPrdYn>
                <appmtDdDlvDy>General shipping</appmtDdDlvDy>
                <buyMemNo>26000820</buyMemNo>
                <delvlaceSeq>140116389</delvlaceSeq>
                <dlvCstStlTypNm>Prepaid Delivery Fee</dlvCstStlTypNm>
                <dlvEtprsCd>00301</dlvEtprsCd>
                <dlvEtprsNm>TIKI Regular</dlvEtprsNm>
                <dlvKdCdName>General shipping</dlvKdCdName>
                <dlvMthdCd>01</dlvMthdCd>
                <dlvMthdCdNm>Courier service</dlvMthdCdNm>
                <dlvNo>8000028633</dlvNo>
                <invcUpdateDt></invcUpdateDt>
                <lstDlvCst>9,000</lstDlvCst>
                <memId>mobi***********************</memId>
                <ordDlvReqCont></ordDlvReqCont>
                <ordDt>2016/01/11</ordDt>
                <ordNm>Elevenia</ordNm>
                <ordNo>22</ordNo>
                <ordPrdSeq>1</ordPrdSeq>
                <ordPrdStat>202</ordPrdStat>
                <ordQty>1</ordQty>
                <ordStlEndDt>2016/01/11 09:29:32</ordStlEndDt>
                <orderAmt>25000</orderAmt>
                <orderProduct>
                    <advrt_stmt></advrt_stmt>
                    <appmtDdDlvDy>General shipping</appmtDdDlvDy>
                    <atmt_buy_cnfrm_yn></atmt_buy_cnfrm_yn>
                    <barCode></barCode>
                    <batchYn>false</batchYn>
                    <bonusDscAmt>0</bonusDscAmt>
                    <bonusDscGb></bonusDscGb>
                    <bonusDscRt>0.0</bonusDscRt>
                    <chinaSaleYn></chinaSaleYn>
                    <ctgrCupnExYn>N</ctgrCupnExYn>
                    <ctgrPntPreRt></ctgrPntPreRt>
                    <ctgrPntPreRtAmt></ctgrPntPreRtAmt>
                    <cupnDlv>0</cupnDlv>
                    <deliveryTimeOut>false</deliveryTimeOut>
                    <delvplaceSeq>140116389</delvplaceSeq>
                    <dlvInsOrgCst>0</dlvInsOrgCst>
                    <dlvNo>8000028633</dlvNo>
                    <dlvRewardAmt>0</dlvRewardAmt>
                    <errMsg></errMsg>
                    <finalDscPrc></finalDscPrc>
                    <firstDscAmt>0</firstDscAmt>
                    <fixedDlvPrd>false</fixedDlvPrd>
                    <giftPrdOptNo>0</giftPrdOptNo>
                    <imgurl></imgurl>
                    <isChangePayMethod>N</isChangePayMethod>
                    <isHistory>Y</isHistory>
                    <limitDt></limitDt>
                    <lowPrcCompYn></lowPrcCompYn>
                    <mileDscAmt>0.0</mileDscAmt>
                    <mileDscRt>0.0</mileDscRt>
                    <mileSaveAmt>0.0</mileSaveAmt>
                    <mileSaveRt>0.0</mileSaveRt>
                    <ordPrdCpnAmtWithoutJang>0</ordPrdCpnAmtWithoutJang>
                    <ordPrdRewardAmt></ordPrdRewardAmt>
                    <ordPrdRewardItmCd></ordPrdRewardItmCd>
                    <ordPrdRewardItmCdNm></ordPrdRewardItmCdNm>
                    <ordPrdSeq>1</ordPrdSeq>
                    <ordPrdStat>202</ordPrdStat>
                    <ordQty>1</ordQty>
                    <orgBonusDscRt>0.0</orgBonusDscRt>
                    <pluDscAmt>0</pluDscAmt>
                    <pluDscBasis>0</pluDscBasis>
                    <pluDscRt>0.0</pluDscRt>
                    <plusDscOcbRwd>0</plusDscOcbRwd>
                    <prdNm>Product FRSIANPRODUCT000002 New</prdNm>
                    <prdNo>250911</prdNo>
                    <prdOptSpcNo>0</prdOptSpcNo>
                    <prdTtoDisconAmt>0</prdTtoDisconAmt>
                    <prdTypCd>01</prdTypCd>
                    <procReturnDlvCstByBndl>false</procReturnDlvCstByBndl>
                    <returnDlvCstByBndl>0</returnDlvCstByBndl>
                    <rfndMtdCd></rfndMtdCd>
                    <selPrc>25000</selPrc>
                    <sellerPrdCd>FRSIANPRODUCT000002</sellerPrdCd>
                    <stPntDscAmt>0.0</stPntDscAmt>
                    <stPntDscRt>0.0</stPntDscRt>
                    <stlStat></stlStat>
                    <tiketSelPrc>0</tiketSelPrc>
                    <tiketTransFee>0</tiketTransFee>
                    <visitDlvYn>N</visitDlvYn>
                </orderProduct>
                <prdClfCdNm>Ready Stock</prdClfCdNm>
                <prdNm>Product FRSIANPRODUCT000002 New</prdNm>
                <prdNo>250911</prdNo>
                <rcvrBaseAddr>Andir Kota Bandung JAWA BARAT UKNOWN</rcvrBaseAddr>
                <rcvrMailNo>SMI-009008</rcvrMailNo>
                <rcvrNm>Elevenia</rcvrNm>
                <rcvrPrtblNo>0878-81181818</rcvrPrtblNo>
                <rcvrTlphn>0878-81181818</rcvrTlphn>
                <selFeeAmt>32750</selFeeAmt>
                <selFeeRt>1,250(5.00%)</selFeeRt>
                <selFixedFee>5.00(%)</selFixedFee>
                <selPrc>25000</selPrc>
                <sellerDscPrc>0</sellerDscPrc>
                <sellerPrdCd>FRSIANPRODUCT000003</sellerPrdCd>
                <sndPlnDd></sndPlnDd>
                <tmallApplyDscAmt>0</tmallApplyDscAmt>
            </order>
        </Orders>';
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);

        $input = ["ordStat" => "202", "dateFrom" => "2016-01-08", "dateTo" => "2016-01-08"];
        $res = $order->get($input);
        $parsedRes = $order->parseOrder(143, $res['body']['order']);

        $this->assertEquals(200, $res['code']);
        $this->assertEquals(2, count($parsedRes));
        $this->assertEquals(1, count($parsedRes['11']['orderItems']));
        $this->assertEquals(1, count($parsedRes['22']['orderItems']));
    }

    public function testMoreThenOneOrderMoreThenOneItem(){
        $order = new Order($this->token);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <Orders>
            <order>
                <addPrdNo>0</addPrdNo>
                <addPrdYn>N</addPrdYn>
                <appmtDdDlvDy>General shipping</appmtDdDlvDy>
                <buyMemNo>26000820</buyMemNo>
                <delvlaceSeq>140116389</delvlaceSeq>
                <dlvCstStlTypNm>Prepaid Delivery Fee</dlvCstStlTypNm>
                <dlvEtprsCd>00301</dlvEtprsCd>
                <dlvEtprsNm>TIKI Regular</dlvEtprsNm>
                <dlvKdCdName>General shipping</dlvKdCdName>
                <dlvMthdCd>01</dlvMthdCd>
                <dlvMthdCdNm>Courier service</dlvMthdCdNm>
                <dlvNo>8000028633</dlvNo>
                <invcUpdateDt></invcUpdateDt>
                <lstDlvCst>9,000</lstDlvCst>
                <memId>mobi***********************</memId>
                <ordDlvReqCont></ordDlvReqCont>
                <ordDt>2016/01/11</ordDt>
                <ordNm>Elevenia</ordNm>
                <ordNo>11</ordNo>
                <ordPrdSeq>1</ordPrdSeq>
                <ordPrdStat>202</ordPrdStat>
                <ordQty>1</ordQty>
                <ordStlEndDt>2016/01/11 09:29:32</ordStlEndDt>
                <orderAmt>25000</orderAmt>
                <orderProduct>
                    <advrt_stmt></advrt_stmt>
                    <appmtDdDlvDy>General shipping</appmtDdDlvDy>
                    <atmt_buy_cnfrm_yn></atmt_buy_cnfrm_yn>
                    <barCode></barCode>
                    <batchYn>false</batchYn>
                    <bonusDscAmt>0</bonusDscAmt>
                    <bonusDscGb></bonusDscGb>
                    <bonusDscRt>0.0</bonusDscRt>
                    <chinaSaleYn></chinaSaleYn>
                    <ctgrCupnExYn>N</ctgrCupnExYn>
                    <ctgrPntPreRt></ctgrPntPreRt>
                    <ctgrPntPreRtAmt></ctgrPntPreRtAmt>
                    <cupnDlv>0</cupnDlv>
                    <deliveryTimeOut>false</deliveryTimeOut>
                    <delvplaceSeq>140116389</delvplaceSeq>
                    <dlvInsOrgCst>0</dlvInsOrgCst>
                    <dlvNo>8000028633</dlvNo>
                    <dlvRewardAmt>0</dlvRewardAmt>
                    <errMsg></errMsg>
                    <finalDscPrc></finalDscPrc>
                    <firstDscAmt>0</firstDscAmt>
                    <fixedDlvPrd>false</fixedDlvPrd>
                    <giftPrdOptNo>0</giftPrdOptNo>
                    <imgurl></imgurl>
                    <isChangePayMethod>N</isChangePayMethod>
                    <isHistory>Y</isHistory>
                    <limitDt></limitDt>
                    <lowPrcCompYn></lowPrcCompYn>
                    <mileDscAmt>0.0</mileDscAmt>
                    <mileDscRt>0.0</mileDscRt>
                    <mileSaveAmt>0.0</mileSaveAmt>
                    <mileSaveRt>0.0</mileSaveRt>
                    <ordPrdCpnAmtWithoutJang>0</ordPrdCpnAmtWithoutJang>
                    <ordPrdRewardAmt></ordPrdRewardAmt>
                    <ordPrdRewardItmCd></ordPrdRewardItmCd>
                    <ordPrdRewardItmCdNm></ordPrdRewardItmCdNm>
                    <ordPrdSeq>1</ordPrdSeq>
                    <ordPrdStat>202</ordPrdStat>
                    <ordQty>1</ordQty>
                    <orgBonusDscRt>0.0</orgBonusDscRt>
                    <pluDscAmt>0</pluDscAmt>
                    <pluDscBasis>0</pluDscBasis>
                    <pluDscRt>0.0</pluDscRt>
                    <plusDscOcbRwd>0</plusDscOcbRwd>
                    <prdNm>Product FRSIANPRODUCT000002 New</prdNm>
                    <prdNo>250911</prdNo>
                    <prdOptSpcNo>0</prdOptSpcNo>
                    <prdTtoDisconAmt>0</prdTtoDisconAmt>
                    <prdTypCd>01</prdTypCd>
                    <procReturnDlvCstByBndl>false</procReturnDlvCstByBndl>
                    <returnDlvCstByBndl>0</returnDlvCstByBndl>
                    <rfndMtdCd></rfndMtdCd>
                    <selPrc>25000</selPrc>
                    <sellerPrdCd>FRSIANPRODUCT000002</sellerPrdCd>
                    <stPntDscAmt>0.0</stPntDscAmt>
                    <stPntDscRt>0.0</stPntDscRt>
                    <stlStat></stlStat>
                    <tiketSelPrc>0</tiketSelPrc>
                    <tiketTransFee>0</tiketTransFee>
                    <visitDlvYn>N</visitDlvYn>
                </orderProduct>
                <prdClfCdNm>Ready Stock</prdClfCdNm>
                <prdNm>Product FRSIANPRODUCT000002 New</prdNm>
                <prdNo>250911</prdNo>
                <rcvrBaseAddr>Andir Kota Bandung JAWA BARAT UKNOWN</rcvrBaseAddr>
                <rcvrMailNo>SMI-009008</rcvrMailNo>
                <rcvrNm>Elevenia</rcvrNm>
                <rcvrPrtblNo>0878-81181818</rcvrPrtblNo>
                <rcvrTlphn>0878-81181818</rcvrTlphn>
                <selFeeAmt>32750</selFeeAmt>
                <selFeeRt>1,250(5.00%)</selFeeRt>
                <selFixedFee>5.00(%)</selFixedFee>
                <selPrc>25000</selPrc>
                <sellerDscPrc>0</sellerDscPrc>
                <sellerPrdCd>FRSIANPRODUCT000001</sellerPrdCd>
                <sndPlnDd></sndPlnDd>
                <tmallApplyDscAmt>0</tmallApplyDscAmt>
            </order>
            <order>
                <addPrdNo>0</addPrdNo>
                <addPrdYn>N</addPrdYn>
                <appmtDdDlvDy>General shipping</appmtDdDlvDy>
                <buyMemNo>26000820</buyMemNo>
                <delvlaceSeq>140116389</delvlaceSeq>
                <dlvCstStlTypNm>Prepaid Delivery Fee</dlvCstStlTypNm>
                <dlvEtprsCd>00301</dlvEtprsCd>
                <dlvEtprsNm>TIKI Regular</dlvEtprsNm>
                <dlvKdCdName>General shipping</dlvKdCdName>
                <dlvMthdCd>01</dlvMthdCd>
                <dlvMthdCdNm>Courier service</dlvMthdCdNm>
                <dlvNo>8000028633</dlvNo>
                <invcUpdateDt></invcUpdateDt>
                <lstDlvCst>9,000</lstDlvCst>
                <memId>mobi***********************</memId>
                <ordDlvReqCont></ordDlvReqCont>
                <ordDt>2016/01/11</ordDt>
                <ordNm>Elevenia</ordNm>
                <ordNo>11</ordNo>
                <ordPrdSeq>1</ordPrdSeq>
                <ordPrdStat>202</ordPrdStat>
                <ordQty>1</ordQty>
                <ordStlEndDt>2016/01/11 09:29:32</ordStlEndDt>
                <orderAmt>25000</orderAmt>
                <orderProduct>
                    <advrt_stmt></advrt_stmt>
                    <appmtDdDlvDy>General shipping</appmtDdDlvDy>
                    <atmt_buy_cnfrm_yn></atmt_buy_cnfrm_yn>
                    <barCode></barCode>
                    <batchYn>false</batchYn>
                    <bonusDscAmt>0</bonusDscAmt>
                    <bonusDscGb></bonusDscGb>
                    <bonusDscRt>0.0</bonusDscRt>
                    <chinaSaleYn></chinaSaleYn>
                    <ctgrCupnExYn>N</ctgrCupnExYn>
                    <ctgrPntPreRt></ctgrPntPreRt>
                    <ctgrPntPreRtAmt></ctgrPntPreRtAmt>
                    <cupnDlv>0</cupnDlv>
                    <deliveryTimeOut>false</deliveryTimeOut>
                    <delvplaceSeq>140116389</delvplaceSeq>
                    <dlvInsOrgCst>0</dlvInsOrgCst>
                    <dlvNo>8000028633</dlvNo>
                    <dlvRewardAmt>0</dlvRewardAmt>
                    <errMsg></errMsg>
                    <finalDscPrc></finalDscPrc>
                    <firstDscAmt>0</firstDscAmt>
                    <fixedDlvPrd>false</fixedDlvPrd>
                    <giftPrdOptNo>0</giftPrdOptNo>
                    <imgurl></imgurl>
                    <isChangePayMethod>N</isChangePayMethod>
                    <isHistory>Y</isHistory>
                    <limitDt></limitDt>
                    <lowPrcCompYn></lowPrcCompYn>
                    <mileDscAmt>0.0</mileDscAmt>
                    <mileDscRt>0.0</mileDscRt>
                    <mileSaveAmt>0.0</mileSaveAmt>
                    <mileSaveRt>0.0</mileSaveRt>
                    <ordPrdCpnAmtWithoutJang>0</ordPrdCpnAmtWithoutJang>
                    <ordPrdRewardAmt></ordPrdRewardAmt>
                    <ordPrdRewardItmCd></ordPrdRewardItmCd>
                    <ordPrdRewardItmCdNm></ordPrdRewardItmCdNm>
                    <ordPrdSeq>1</ordPrdSeq>
                    <ordPrdStat>202</ordPrdStat>
                    <ordQty>1</ordQty>
                    <orgBonusDscRt>0.0</orgBonusDscRt>
                    <pluDscAmt>0</pluDscAmt>
                    <pluDscBasis>0</pluDscBasis>
                    <pluDscRt>0.0</pluDscRt>
                    <plusDscOcbRwd>0</plusDscOcbRwd>
                    <prdNm>Product FRSIANPRODUCT000002 New</prdNm>
                    <prdNo>250911</prdNo>
                    <prdOptSpcNo>0</prdOptSpcNo>
                    <prdTtoDisconAmt>0</prdTtoDisconAmt>
                    <prdTypCd>01</prdTypCd>
                    <procReturnDlvCstByBndl>false</procReturnDlvCstByBndl>
                    <returnDlvCstByBndl>0</returnDlvCstByBndl>
                    <rfndMtdCd></rfndMtdCd>
                    <selPrc>25000</selPrc>
                    <sellerPrdCd>FRSIANPRODUCT000002</sellerPrdCd>
                    <stPntDscAmt>0.0</stPntDscAmt>
                    <stPntDscRt>0.0</stPntDscRt>
                    <stlStat></stlStat>
                    <tiketSelPrc>0</tiketSelPrc>
                    <tiketTransFee>0</tiketTransFee>
                    <visitDlvYn>N</visitDlvYn>
                </orderProduct>
                <prdClfCdNm>Ready Stock</prdClfCdNm>
                <prdNm>Product FRSIANPRODUCT000002 New</prdNm>
                <prdNo>250911</prdNo>
                <rcvrBaseAddr>Andir Kota Bandung JAWA BARAT UKNOWN</rcvrBaseAddr>
                <rcvrMailNo>SMI-009008</rcvrMailNo>
                <rcvrNm>Elevenia</rcvrNm>
                <rcvrPrtblNo>0878-81181818</rcvrPrtblNo>
                <rcvrTlphn>0878-81181818</rcvrTlphn>
                <selFeeAmt>32750</selFeeAmt>
                <selFeeRt>1,250(5.00%)</selFeeRt>
                <selFixedFee>5.00(%)</selFixedFee>
                <selPrc>25000</selPrc>
                <sellerDscPrc>0</sellerDscPrc>
                <sellerPrdCd>FRSIANPRODUCT000002</sellerPrdCd>
                <sndPlnDd></sndPlnDd>
                <tmallApplyDscAmt>0</tmallApplyDscAmt>
            </order>
            <order>
                <addPrdNo>0</addPrdNo>
                <addPrdYn>N</addPrdYn>
                <appmtDdDlvDy>General shipping</appmtDdDlvDy>
                <buyMemNo>26000820</buyMemNo>
                <delvlaceSeq>140116389</delvlaceSeq>
                <dlvCstStlTypNm>Prepaid Delivery Fee</dlvCstStlTypNm>
                <dlvEtprsCd>00301</dlvEtprsCd>
                <dlvEtprsNm>TIKI Regular</dlvEtprsNm>
                <dlvKdCdName>General shipping</dlvKdCdName>
                <dlvMthdCd>01</dlvMthdCd>
                <dlvMthdCdNm>Courier service</dlvMthdCdNm>
                <dlvNo>8000028633</dlvNo>
                <invcUpdateDt></invcUpdateDt>
                <lstDlvCst>9,000</lstDlvCst>
                <memId>mobi***********************</memId>
                <ordDlvReqCont></ordDlvReqCont>
                <ordDt>2016/01/11</ordDt>
                <ordNm>Elevenia</ordNm>
                <ordNo>22</ordNo>
                <ordPrdSeq>1</ordPrdSeq>
                <ordPrdStat>202</ordPrdStat>
                <ordQty>1</ordQty>
                <ordStlEndDt>2016/01/11 09:29:32</ordStlEndDt>
                <orderAmt>25000</orderAmt>
                <orderProduct>
                    <advrt_stmt></advrt_stmt>
                    <appmtDdDlvDy>General shipping</appmtDdDlvDy>
                    <atmt_buy_cnfrm_yn></atmt_buy_cnfrm_yn>
                    <barCode></barCode>
                    <batchYn>false</batchYn>
                    <bonusDscAmt>0</bonusDscAmt>
                    <bonusDscGb></bonusDscGb>
                    <bonusDscRt>0.0</bonusDscRt>
                    <chinaSaleYn></chinaSaleYn>
                    <ctgrCupnExYn>N</ctgrCupnExYn>
                    <ctgrPntPreRt></ctgrPntPreRt>
                    <ctgrPntPreRtAmt></ctgrPntPreRtAmt>
                    <cupnDlv>0</cupnDlv>
                    <deliveryTimeOut>false</deliveryTimeOut>
                    <delvplaceSeq>140116389</delvplaceSeq>
                    <dlvInsOrgCst>0</dlvInsOrgCst>
                    <dlvNo>8000028633</dlvNo>
                    <dlvRewardAmt>0</dlvRewardAmt>
                    <errMsg></errMsg>
                    <finalDscPrc></finalDscPrc>
                    <firstDscAmt>0</firstDscAmt>
                    <fixedDlvPrd>false</fixedDlvPrd>
                    <giftPrdOptNo>0</giftPrdOptNo>
                    <imgurl></imgurl>
                    <isChangePayMethod>N</isChangePayMethod>
                    <isHistory>Y</isHistory>
                    <limitDt></limitDt>
                    <lowPrcCompYn></lowPrcCompYn>
                    <mileDscAmt>0.0</mileDscAmt>
                    <mileDscRt>0.0</mileDscRt>
                    <mileSaveAmt>0.0</mileSaveAmt>
                    <mileSaveRt>0.0</mileSaveRt>
                    <ordPrdCpnAmtWithoutJang>0</ordPrdCpnAmtWithoutJang>
                    <ordPrdRewardAmt></ordPrdRewardAmt>
                    <ordPrdRewardItmCd></ordPrdRewardItmCd>
                    <ordPrdRewardItmCdNm></ordPrdRewardItmCdNm>
                    <ordPrdSeq>1</ordPrdSeq>
                    <ordPrdStat>202</ordPrdStat>
                    <ordQty>1</ordQty>
                    <orgBonusDscRt>0.0</orgBonusDscRt>
                    <pluDscAmt>0</pluDscAmt>
                    <pluDscBasis>0</pluDscBasis>
                    <pluDscRt>0.0</pluDscRt>
                    <plusDscOcbRwd>0</plusDscOcbRwd>
                    <prdNm>Product FRSIANPRODUCT000002 New</prdNm>
                    <prdNo>250911</prdNo>
                    <prdOptSpcNo>0</prdOptSpcNo>
                    <prdTtoDisconAmt>0</prdTtoDisconAmt>
                    <prdTypCd>01</prdTypCd>
                    <procReturnDlvCstByBndl>false</procReturnDlvCstByBndl>
                    <returnDlvCstByBndl>0</returnDlvCstByBndl>
                    <rfndMtdCd></rfndMtdCd>
                    <selPrc>25000</selPrc>
                    <sellerPrdCd>FRSIANPRODUCT000002</sellerPrdCd>
                    <stPntDscAmt>0.0</stPntDscAmt>
                    <stPntDscRt>0.0</stPntDscRt>
                    <stlStat></stlStat>
                    <tiketSelPrc>0</tiketSelPrc>
                    <tiketTransFee>0</tiketTransFee>
                    <visitDlvYn>N</visitDlvYn>
                </orderProduct>
                <prdClfCdNm>Ready Stock</prdClfCdNm>
                <prdNm>Product FRSIANPRODUCT000002 New</prdNm>
                <prdNo>250911</prdNo>
                <rcvrBaseAddr>Andir Kota Bandung JAWA BARAT UKNOWN</rcvrBaseAddr>
                <rcvrMailNo>SMI-009008</rcvrMailNo>
                <rcvrNm>Elevenia</rcvrNm>
                <rcvrPrtblNo>0878-81181818</rcvrPrtblNo>
                <rcvrTlphn>0878-81181818</rcvrTlphn>
                <selFeeAmt>32750</selFeeAmt>
                <selFeeRt>1,250(5.00%)</selFeeRt>
                <selFixedFee>5.00(%)</selFixedFee>
                <selPrc>25000</selPrc>
                <sellerDscPrc>0</sellerDscPrc>
                <sellerPrdCd>FRSIANPRODUCT000001</sellerPrdCd>
                <sndPlnDd></sndPlnDd>
                <tmallApplyDscAmt>0</tmallApplyDscAmt>
            </order>
            <order>
                <addPrdNo>0</addPrdNo>
                <addPrdYn>N</addPrdYn>
                <appmtDdDlvDy>General shipping</appmtDdDlvDy>
                <buyMemNo>26000820</buyMemNo>
                <delvlaceSeq>140116389</delvlaceSeq>
                <dlvCstStlTypNm>Prepaid Delivery Fee</dlvCstStlTypNm>
                <dlvEtprsCd>00301</dlvEtprsCd>
                <dlvEtprsNm>TIKI Regular</dlvEtprsNm>
                <dlvKdCdName>General shipping</dlvKdCdName>
                <dlvMthdCd>01</dlvMthdCd>
                <dlvMthdCdNm>Courier service</dlvMthdCdNm>
                <dlvNo>8000028633</dlvNo>
                <invcUpdateDt></invcUpdateDt>
                <lstDlvCst>9,000</lstDlvCst>
                <memId>mobi***********************</memId>
                <ordDlvReqCont></ordDlvReqCont>
                <ordDt>2016/01/11</ordDt>
                <ordNm>Elevenia</ordNm>
                <ordNo>22</ordNo>
                <ordPrdSeq>1</ordPrdSeq>
                <ordPrdStat>202</ordPrdStat>
                <ordQty>1</ordQty>
                <ordStlEndDt>2016/01/11 09:29:32</ordStlEndDt>
                <orderAmt>25000</orderAmt>
                <orderProduct>
                    <advrt_stmt></advrt_stmt>
                    <appmtDdDlvDy>General shipping</appmtDdDlvDy>
                    <atmt_buy_cnfrm_yn></atmt_buy_cnfrm_yn>
                    <barCode></barCode>
                    <batchYn>false</batchYn>
                    <bonusDscAmt>0</bonusDscAmt>
                    <bonusDscGb></bonusDscGb>
                    <bonusDscRt>0.0</bonusDscRt>
                    <chinaSaleYn></chinaSaleYn>
                    <ctgrCupnExYn>N</ctgrCupnExYn>
                    <ctgrPntPreRt></ctgrPntPreRt>
                    <ctgrPntPreRtAmt></ctgrPntPreRtAmt>
                    <cupnDlv>0</cupnDlv>
                    <deliveryTimeOut>false</deliveryTimeOut>
                    <delvplaceSeq>140116389</delvplaceSeq>
                    <dlvInsOrgCst>0</dlvInsOrgCst>
                    <dlvNo>8000028633</dlvNo>
                    <dlvRewardAmt>0</dlvRewardAmt>
                    <errMsg></errMsg>
                    <finalDscPrc></finalDscPrc>
                    <firstDscAmt>0</firstDscAmt>
                    <fixedDlvPrd>false</fixedDlvPrd>
                    <giftPrdOptNo>0</giftPrdOptNo>
                    <imgurl></imgurl>
                    <isChangePayMethod>N</isChangePayMethod>
                    <isHistory>Y</isHistory>
                    <limitDt></limitDt>
                    <lowPrcCompYn></lowPrcCompYn>
                    <mileDscAmt>0.0</mileDscAmt>
                    <mileDscRt>0.0</mileDscRt>
                    <mileSaveAmt>0.0</mileSaveAmt>
                    <mileSaveRt>0.0</mileSaveRt>
                    <ordPrdCpnAmtWithoutJang>0</ordPrdCpnAmtWithoutJang>
                    <ordPrdRewardAmt></ordPrdRewardAmt>
                    <ordPrdRewardItmCd></ordPrdRewardItmCd>
                    <ordPrdRewardItmCdNm></ordPrdRewardItmCdNm>
                    <ordPrdSeq>1</ordPrdSeq>
                    <ordPrdStat>202</ordPrdStat>
                    <ordQty>1</ordQty>
                    <orgBonusDscRt>0.0</orgBonusDscRt>
                    <pluDscAmt>0</pluDscAmt>
                    <pluDscBasis>0</pluDscBasis>
                    <pluDscRt>0.0</pluDscRt>
                    <plusDscOcbRwd>0</plusDscOcbRwd>
                    <prdNm>Product FRSIANPRODUCT000002 New</prdNm>
                    <prdNo>250911</prdNo>
                    <prdOptSpcNo>0</prdOptSpcNo>
                    <prdTtoDisconAmt>0</prdTtoDisconAmt>
                    <prdTypCd>01</prdTypCd>
                    <procReturnDlvCstByBndl>false</procReturnDlvCstByBndl>
                    <returnDlvCstByBndl>0</returnDlvCstByBndl>
                    <rfndMtdCd></rfndMtdCd>
                    <selPrc>25000</selPrc>
                    <sellerPrdCd>FRSIANPRODUCT000002</sellerPrdCd>
                    <stPntDscAmt>0.0</stPntDscAmt>
                    <stPntDscRt>0.0</stPntDscRt>
                    <stlStat></stlStat>
                    <tiketSelPrc>0</tiketSelPrc>
                    <tiketTransFee>0</tiketTransFee>
                    <visitDlvYn>N</visitDlvYn>
                </orderProduct>
                <prdClfCdNm>Ready Stock</prdClfCdNm>
                <prdNm>Product FRSIANPRODUCT000002 New</prdNm>
                <prdNo>250911</prdNo>
                <rcvrBaseAddr>Andir Kota Bandung JAWA BARAT UKNOWN</rcvrBaseAddr>
                <rcvrMailNo>SMI-009008</rcvrMailNo>
                <rcvrNm>Elevenia</rcvrNm>
                <rcvrPrtblNo>0878-81181818</rcvrPrtblNo>
                <rcvrTlphn>0878-81181818</rcvrTlphn>
                <selFeeAmt>32750</selFeeAmt>
                <selFeeRt>1,250(5.00%)</selFeeRt>
                <selFixedFee>5.00(%)</selFixedFee>
                <selPrc>25000</selPrc>
                <sellerDscPrc>0</sellerDscPrc>
                <sellerPrdCd>FRSIANPRODUCT000002</sellerPrdCd>
                <sndPlnDd></sndPlnDd>
                <tmallApplyDscAmt>0</tmallApplyDscAmt>
            </order>
        </Orders>';
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);

        $input = ["ordStat" => "202", "dateFrom" => "2016-01-08", "dateTo" => "2016-01-08"];
        $res = $order->get($input);
        $parsedRes = $order->parseOrder(143, $res['body']['order']);

        $this->assertEquals(200, $res['code']);
        $this->assertEquals(2, count($parsedRes));
        $this->assertEquals(2, count($parsedRes['11']['orderItems']));
        $this->assertEquals(2, count($parsedRes['22']['orderItems']));
    }

    public function testSaveSalesOrderToDB()
    {
        $order = new Order($this->token);

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <Orders>
            <order>
                <addPrdNo>0</addPrdNo>
                <addPrdYn>N</addPrdYn>
                <appmtDdDlvDy>General shipping</appmtDdDlvDy>
                <buyMemNo>26000820</buyMemNo>
                <delvlaceSeq>140116389</delvlaceSeq>
                <dlvCstStlTypNm>Prepaid Delivery Fee</dlvCstStlTypNm>
                <dlvEtprsCd>00301</dlvEtprsCd>
                <dlvEtprsNm>TIKI Regular</dlvEtprsNm>
                <dlvKdCdName>General shipping</dlvKdCdName>
                <dlvMthdCd>01</dlvMthdCd>
                <dlvMthdCdNm>Courier service</dlvMthdCdNm>
                <dlvNo>8000028633</dlvNo>
                <invcUpdateDt></invcUpdateDt>
                <lstDlvCst>9,000</lstDlvCst>
                <memId>mobi***********************</memId>
                <ordDlvReqCont></ordDlvReqCont>
                <ordDt>2016/01/11</ordDt>
                <ordNm>Elevenia</ordNm>
                <ordNo>11</ordNo>
                <ordPrdSeq>1</ordPrdSeq>
                <ordPrdStat>202</ordPrdStat>
                <ordQty>1</ordQty>
                <ordStlEndDt>2016/01/11 09:29:32</ordStlEndDt>
                <orderAmt>25000</orderAmt>
                <orderProduct>
                    <advrt_stmt></advrt_stmt>
                    <appmtDdDlvDy>General shipping</appmtDdDlvDy>
                    <atmt_buy_cnfrm_yn></atmt_buy_cnfrm_yn>
                    <barCode></barCode>
                    <batchYn>false</batchYn>
                    <bonusDscAmt>0</bonusDscAmt>
                    <bonusDscGb></bonusDscGb>
                    <bonusDscRt>0.0</bonusDscRt>
                    <chinaSaleYn></chinaSaleYn>
                    <ctgrCupnExYn>N</ctgrCupnExYn>
                    <ctgrPntPreRt></ctgrPntPreRt>
                    <ctgrPntPreRtAmt></ctgrPntPreRtAmt>
                    <cupnDlv>0</cupnDlv>
                    <deliveryTimeOut>false</deliveryTimeOut>
                    <delvplaceSeq>140116389</delvplaceSeq>
                    <dlvInsOrgCst>0</dlvInsOrgCst>
                    <dlvNo>8000028633</dlvNo>
                    <dlvRewardAmt>0</dlvRewardAmt>
                    <errMsg></errMsg>
                    <finalDscPrc></finalDscPrc>
                    <firstDscAmt>0</firstDscAmt>
                    <fixedDlvPrd>false</fixedDlvPrd>
                    <giftPrdOptNo>0</giftPrdOptNo>
                    <imgurl></imgurl>
                    <isChangePayMethod>N</isChangePayMethod>
                    <isHistory>Y</isHistory>
                    <limitDt></limitDt>
                    <lowPrcCompYn></lowPrcCompYn>
                    <mileDscAmt>0.0</mileDscAmt>
                    <mileDscRt>0.0</mileDscRt>
                    <mileSaveAmt>0.0</mileSaveAmt>
                    <mileSaveRt>0.0</mileSaveRt>
                    <ordPrdCpnAmtWithoutJang>0</ordPrdCpnAmtWithoutJang>
                    <ordPrdRewardAmt></ordPrdRewardAmt>
                    <ordPrdRewardItmCd></ordPrdRewardItmCd>
                    <ordPrdRewardItmCdNm></ordPrdRewardItmCdNm>
                    <ordPrdSeq>1</ordPrdSeq>
                    <ordPrdStat>202</ordPrdStat>
                    <ordQty>1</ordQty>
                    <orgBonusDscRt>0.0</orgBonusDscRt>
                    <pluDscAmt>0</pluDscAmt>
                    <pluDscBasis>0</pluDscBasis>
                    <pluDscRt>0.0</pluDscRt>
                    <plusDscOcbRwd>0</plusDscOcbRwd>
                    <prdNm>Product FRSIANPRODUCT000002 New</prdNm>
                    <prdNo>250911</prdNo>
                    <prdOptSpcNo>0</prdOptSpcNo>
                    <prdTtoDisconAmt>0</prdTtoDisconAmt>
                    <prdTypCd>01</prdTypCd>
                    <procReturnDlvCstByBndl>false</procReturnDlvCstByBndl>
                    <returnDlvCstByBndl>0</returnDlvCstByBndl>
                    <rfndMtdCd></rfndMtdCd>
                    <selPrc>25000</selPrc>
                    <sellerPrdCd>FRSIANPRODUCT000002</sellerPrdCd>
                    <stPntDscAmt>0.0</stPntDscAmt>
                    <stPntDscRt>0.0</stPntDscRt>
                    <stlStat></stlStat>
                    <tiketSelPrc>0</tiketSelPrc>
                    <tiketTransFee>0</tiketTransFee>
                    <visitDlvYn>N</visitDlvYn>
                </orderProduct>
                <prdClfCdNm>Ready Stock</prdClfCdNm>
                <prdNm>Product FRSIANPRODUCT000002 New</prdNm>
                <prdNo>250911</prdNo>
                <rcvrBaseAddr>Andir Kota Bandung JAWA BARAT UKNOWN</rcvrBaseAddr>
                <rcvrMailNo>SMI-009008</rcvrMailNo>
                <rcvrNm>Elevenia</rcvrNm>
                <rcvrPrtblNo>0878-81181818</rcvrPrtblNo>
                <rcvrTlphn>0878-81181818</rcvrTlphn>
                <selFeeAmt>32750</selFeeAmt>
                <selFeeRt>1,250(5.00%)</selFeeRt>
                <selFixedFee>5.00(%)</selFixedFee>
                <selPrc>25000</selPrc>
                <sellerDscPrc>0</sellerDscPrc>
                <sellerPrdCd>FRSIANPRODUCT000002</sellerPrdCd>
                <sndPlnDd></sndPlnDd>
                <tmallApplyDscAmt>0</tmallApplyDscAmt>
            </order>
            <order>
                <addPrdNo>0</addPrdNo>
                <addPrdYn>N</addPrdYn>
                <appmtDdDlvDy>General shipping</appmtDdDlvDy>
                <buyMemNo>26000820</buyMemNo>
                <delvlaceSeq>140116389</delvlaceSeq>
                <dlvCstStlTypNm>Prepaid Delivery Fee</dlvCstStlTypNm>
                <dlvEtprsCd>00301</dlvEtprsCd>
                <dlvEtprsNm>TIKI Regular</dlvEtprsNm>
                <dlvKdCdName>General shipping</dlvKdCdName>
                <dlvMthdCd>01</dlvMthdCd>
                <dlvMthdCdNm>Courier service</dlvMthdCdNm>
                <dlvNo>8000028633</dlvNo>
                <invcUpdateDt></invcUpdateDt>
                <lstDlvCst>9,000</lstDlvCst>
                <memId>mobi***********************</memId>
                <ordDlvReqCont></ordDlvReqCont>
                <ordDt>2016/01/11</ordDt>
                <ordNm>Elevenia</ordNm>
                <ordNo>22</ordNo>
                <ordPrdSeq>1</ordPrdSeq>
                <ordPrdStat>202</ordPrdStat>
                <ordQty>1</ordQty>
                <ordStlEndDt>2016/01/11 09:29:32</ordStlEndDt>
                <orderAmt>25000</orderAmt>
                <orderProduct>
                    <advrt_stmt></advrt_stmt>
                    <appmtDdDlvDy>General shipping</appmtDdDlvDy>
                    <atmt_buy_cnfrm_yn></atmt_buy_cnfrm_yn>
                    <barCode></barCode>
                    <batchYn>false</batchYn>
                    <bonusDscAmt>0</bonusDscAmt>
                    <bonusDscGb></bonusDscGb>
                    <bonusDscRt>0.0</bonusDscRt>
                    <chinaSaleYn></chinaSaleYn>
                    <ctgrCupnExYn>N</ctgrCupnExYn>
                    <ctgrPntPreRt></ctgrPntPreRt>
                    <ctgrPntPreRtAmt></ctgrPntPreRtAmt>
                    <cupnDlv>0</cupnDlv>
                    <deliveryTimeOut>false</deliveryTimeOut>
                    <delvplaceSeq>140116389</delvplaceSeq>
                    <dlvInsOrgCst>0</dlvInsOrgCst>
                    <dlvNo>8000028633</dlvNo>
                    <dlvRewardAmt>0</dlvRewardAmt>
                    <errMsg></errMsg>
                    <finalDscPrc></finalDscPrc>
                    <firstDscAmt>0</firstDscAmt>
                    <fixedDlvPrd>false</fixedDlvPrd>
                    <giftPrdOptNo>0</giftPrdOptNo>
                    <imgurl></imgurl>
                    <isChangePayMethod>N</isChangePayMethod>
                    <isHistory>Y</isHistory>
                    <limitDt></limitDt>
                    <lowPrcCompYn></lowPrcCompYn>
                    <mileDscAmt>0.0</mileDscAmt>
                    <mileDscRt>0.0</mileDscRt>
                    <mileSaveAmt>0.0</mileSaveAmt>
                    <mileSaveRt>0.0</mileSaveRt>
                    <ordPrdCpnAmtWithoutJang>0</ordPrdCpnAmtWithoutJang>
                    <ordPrdRewardAmt></ordPrdRewardAmt>
                    <ordPrdRewardItmCd></ordPrdRewardItmCd>
                    <ordPrdRewardItmCdNm></ordPrdRewardItmCdNm>
                    <ordPrdSeq>1</ordPrdSeq>
                    <ordPrdStat>202</ordPrdStat>
                    <ordQty>1</ordQty>
                    <orgBonusDscRt>0.0</orgBonusDscRt>
                    <pluDscAmt>0</pluDscAmt>
                    <pluDscBasis>0</pluDscBasis>
                    <pluDscRt>0.0</pluDscRt>
                    <plusDscOcbRwd>0</plusDscOcbRwd>
                    <prdNm>Product FRSIANPRODUCT000002 New</prdNm>
                    <prdNo>250911</prdNo>
                    <prdOptSpcNo>0</prdOptSpcNo>
                    <prdTtoDisconAmt>0</prdTtoDisconAmt>
                    <prdTypCd>01</prdTypCd>
                    <procReturnDlvCstByBndl>false</procReturnDlvCstByBndl>
                    <returnDlvCstByBndl>0</returnDlvCstByBndl>
                    <rfndMtdCd></rfndMtdCd>
                    <selPrc>25000</selPrc>
                    <sellerPrdCd>FRSIANPRODUCT000002</sellerPrdCd>
                    <stPntDscAmt>0.0</stPntDscAmt>
                    <stPntDscRt>0.0</stPntDscRt>
                    <stlStat></stlStat>
                    <tiketSelPrc>0</tiketSelPrc>
                    <tiketTransFee>0</tiketTransFee>
                    <visitDlvYn>N</visitDlvYn>
                </orderProduct>
                <prdClfCdNm>Ready Stock</prdClfCdNm>
                <prdNm>Product FRSIANPRODUCT000002 New</prdNm>
                <prdNo>250911</prdNo>
                <rcvrBaseAddr>Andir Kota Bandung JAWA BARAT UKNOWN</rcvrBaseAddr>
                <rcvrMailNo>SMI-009008</rcvrMailNo>
                <rcvrNm>Elevenia</rcvrNm>
                <rcvrPrtblNo>0878-81181818</rcvrPrtblNo>
                <rcvrTlphn>0878-81181818</rcvrTlphn>
                <selFeeAmt>32750</selFeeAmt>
                <selFeeRt>1,250(5.00%)</selFeeRt>
                <selFixedFee>5.00(%)</selFixedFee>
                <selPrc>25000</selPrc>
                <sellerDscPrc>0</sellerDscPrc>
                <sellerPrdCd>FRSIANPRODUCT000003</sellerPrdCd>
                <sndPlnDd></sndPlnDd>
                <tmallApplyDscAmt>0</tmallApplyDscAmt>
            </order>
        </Orders>';
        $this->mockSalesOrder($order, [
            new Response(200, [], $xml)
        ]);

        $input = ["ordStat" => "202", "dateFrom" => "2016-01-08", "dateTo" => "2016-01-08"];
        $res = $order->get($input);

        $ret = $order->save("143", $res['body']['order']);
        $this->assertEquals(200, $res['code']);
        $this->assertEquals(1, $ret['ok']);
    }

    private function mockSalesOrder(Order $order, array $queue)
    {
        if (!$this->mockEnabled) return;
        $mock = new MockHandler($queue);
        $handler = HandlerStack::create($mock);
        $order->client = new Client(['handler'=>$handler]);

    }
}