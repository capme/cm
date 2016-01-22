<?php

class SalesOrderCreateTest extends TestCase
{
    public $samplePartners;
    public $sampleOrder;

    public function setUp()
    {
        parent::setUp();
        $this->samplePartners = json_decode('[{ "_id" : "5694e60e8b36a1721d3f6e50", "partnerId" : 143, "channel" : { "elevenia" : { "openapikey" : "fe868c8788f602061778b49949cf3643", "email" : "test11.acom@gmail.com", "password" : "acom2015" }, "frisianflag" : {  } }, "cmps" : { "username" : "frisianflag", "apiKey" : "frisianflag123!" } }]', true);
        $this->sampleOrder = json_decode('{"_id":"5698a9eba2f26e5fc4fd224b","channel":{"name":"elevenia","order":{"addPrdNo":"0","addPrdYn":"N","appmtDdDlvDy":"General shipping","buyMemNo":"26000820","delvlaceSeq":"140116473","dlvCstStlTypNm":"Prepaid Delivery Fee","dlvEtprsCd":"00301","dlvEtprsNm":"TIKI Regular","dlvKdCdName":"General shipping","dlvMthdCd":"01","dlvMthdCdNm":"Courier service","dlvNo":"8000028717","invcUpdateDt":[],"lstDlvCst":"9,000","memId":"mobi***********************","ordDlvReqCont":[],"ordDt":"2016\/01\/15","ordNm":"Elevenia","ordNo":"201601156166464","ordPrdSeq":"1","ordPrdStat":"202","ordQty":"1","ordStlEndDt":"2016\/01\/15 10:05:55","orderAmt":"25000","rcvrBaseAddr":"Andir Kota Bandung JAWA BARAT UKNOWN","rcvrMailNo":"SMI-009008","rcvrNm":"Elevenia","rcvrPrtblNo":"0878-81181818","rcvrTlphn":"0878-81181818","selFeeAmt":"32750","selFeeRt":"1,250(5.00%)","selFixedFee":"5.00(%)","selPrc":"25000","sellerDscPrc":"0","sellerPrdCd":"FRSIANPRODUCT000002","sndPlnDd":[],"tmallApplyDscAmt":"0","productList":[{"advrt_stmt":[],"appmtDdDlvDy":"General shipping","atmt_buy_cnfrm_yn":[],"barCode":[],"batchYn":"false","bonusDscAmt":"0","bonusDscGb":[],"bonusDscRt":"0.0","chinaSaleYn":[],"ctgrCupnExYn":"N","ctgrPntPreRt":[],"ctgrPntPreRtAmt":[],"cupnDlv":"0","deliveryTimeOut":"false","delvplaceSeq":"140116473","dlvInsOrgCst":"0","dlvNo":"8000028717","dlvRewardAmt":"0","errMsg":[],"finalDscPrc":[],"firstDscAmt":"0","fixedDlvPrd":"false","giftPrdOptNo":"0","imgurl":[],"isChangePayMethod":"N","isHistory":"Y","limitDt":[],"lowPrcCompYn":[],"mileDscAmt":"0.0","mileDscRt":"0.0","mileSaveAmt":"0.0","mileSaveRt":"0.0","ordPrdCpnAmtWithoutJang":"0","ordPrdRewardAmt":[],"ordPrdRewardItmCd":[],"ordPrdRewardItmCdNm":[],"ordPrdSeq":"1","ordPrdStat":"202","ordQty":"1","orgBonusDscRt":"0.0","pluDscAmt":"0","pluDscBasis":"0","pluDscRt":"0.0","plusDscOcbRwd":"0","prdNm":"Product FRSIANPRODUCT000002 New","prdNo":"250911","prdOptSpcNo":"0","prdTtoDisconAmt":"0","prdTypCd":"01","procReturnDlvCstByBndl":"false","returnDlvCstByBndl":"0","rfndMtdCd":[],"selPrc":"25000","sellerPrdCd":"FRSIANPRODUCT000002","stPntDscAmt":"0.0","stPntDscRt":"0.0","stlStat":[],"tiketSelPrc":"0","tiketTransFee":"0","visitDlvYn":"N","prdClfCdNm":"Ready Stock"}]},"lastSync":"2016-01-15T08:12:27.350Z"},"orderId":"201601156166464","partnerId":143,"acommerce":{"order":{"orderCreatedTime":"2016-01-15T03:05:55Z","customerInfo":{"addressee":"Elevenia","address1":"Andir Kota Bandung JAWA BARAT UKNOWN","province":"","postalCode":"0","country":"Indonesia","phone":"0878-81181818","email":"order@elevenia.co.id"},"orderShipmentInfo":{"addressee":"Elevenia","address1":"Andir Kota Bandung JAWA BARAT UKNOWN","address2":"","subDistrict":"","district":"","city":"","province":"","postalCode":"0","country":"Indonesia","phone":"0878-81181818","email":"order@elevenia.co.id"},"paymentType":"NON_COD","shippingType":"STANDARD_2_4_DAYS","grossTotal":25000,"currUnit":"IDR","orderItems":[{"partnerId":"143","itemId":"FRSIANPRODUCT000002","qty":1,"subTotal":25000}]},"lastSync":"2016-01-15T08:12:27.350Z"},"status":"IN_TRANSIT","createdDate":"2016-01-15T08:12:27.350Z","updatedDate":"2016-01-15T08:12:27.350Z"}', true);
    }

    public function testCommand()
    {
        $this->expectsJobs(\App\Jobs\GetSalesOrderFromChannel::class);

        $mock = Mockery::mock('alias:App\Model\Partner')
            ->shouldReceive('raw->find')
            ->once()
            ->andReturn(
                $this->samplePartners
            );

        Artisan::call('salesorder:create');
    }

    public function testGetSalesOrderFromChannel()
    {
        $this->expectsJobs(\App\Jobs\CreateSalesOrderToCmps::class);

        $order = Mockery::mock('App\Library\Order[get,parseOrdersFromElevenia,save]', [$this->samplePartners[0]['partnerId']]);
        $order->shouldReceive('get')
            ->once()
            ->andReturn([
                'code' => 200,
                'body' => [
                    'order' => [
                        $this->sampleOrder['channel']['order']
                    ]
                ]
            ]);
        $order->shouldReceive('parseOrdersFromElevenia')
            ->once()
            ->andReturn([
                $this->sampleOrder['channel']['order']
            ]);
        $order->shouldReceive('save')
            ->once();

        $job = Mockery::mock('App\Jobs\GetSalesOrderFromChannel[getOrder]', [$this->samplePartners[0]]);
        $job->shouldNotReceive('release');
        $job->shouldReceive('getOrder')
            ->andReturn($order);

        $job->handle();
    }

    public function testCreateSalesOrderToCmp()
    {
        $salesOrder = Mockery::mock('ChannelBridge\Cmps\SalesOrder[create]');
        $salesOrder->shouldReceive('create')
            ->once()
            ->andReturn([
                'message' => 'success'
            ]);

        $job = Mockery::mock('App\Jobs\CreateSalesOrderToCmps[getChannelBridgeSalesOrder]', [
            $this->samplePartners[0],
            $this->sampleOrder['channel']['order']
        ]);
        $job->shouldReceive('getChannelBridgeSalesOrder')
            ->once()
            ->andReturn($salesOrder);

        $job->shouldNotReceive('release');

        $job->handle();

        Cache::shouldReceive('remember')
            ->andReturn('RAND_ID');
    }
}