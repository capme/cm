<?php


class UpdateSalesOrderCommandTest extends TestCase
{
    private function salesOrderData()
    {
        $json =  '[{
                    "_id": {
                        "$id": "5698a9eba2f26e5fc4fd224c"
                    },
                    "channel": {
                        "name": "elevenia",
                        "order": {
                            "addPrdNo": "0",
                            "addPrdYn": "N",
                            "appmtDdDlvDy": "General shipping",
                            "buyMemNo": "26000820",
                            "delvlaceSeq": "140116474",
                            "dlvCstStlTypNm": "Prepaid Delivery Fee",
                            "dlvEtprsCd": "00301",
                            "dlvEtprsNm": "TIKI Regular",
                            "dlvKdCdName": "General shipping",
                            "dlvMthdCd": "01",
                            "dlvMthdCdNm": "Courier service",
                            "dlvNo": "8000028718",
                            "invcUpdateDt": [],
                            "lstDlvCst": "9,000",
                            "memId": "mobi***********************",
                            "ordDlvReqCont": [],
                            "ordDt": "2016\/01\/15",
                            "ordNm": "Elevenia",
                            "ordNo": "201601156166852",
                            "ordPrdSeq": "1",
                            "ordPrdStat": "202",
                            "ordQty": "1",
                            "ordStlEndDt": "2016\/01\/15 13:56:58",
                            "orderAmt": "25000",
                            "rcvrBaseAddr": "Andir Kota Bandung JAWA BARAT UKNOWN",
                            "rcvrMailNo": "SMI-009008",
                            "rcvrNm": "Elevenia",
                            "rcvrPrtblNo": "0878-81181818",
                            "rcvrTlphn": "0878-81181818",
                            "selFeeAmt": "32750",
                            "selFeeRt": "1,250(5.00%)",
                            "selFixedFee": "5.00(%)",
                            "selPrc": "25000",
                            "sellerDscPrc": "0",
                            "sellerPrdCd": "FRSIANPRODUCT000002",
                            "sndPlnDd": [],
                            "tmallApplyDscAmt": "0",
                            "productList": [{
                                "advrt_stmt": [],
                                "appmtDdDlvDy": "General shipping",
                                "atmt_buy_cnfrm_yn": [],
                                "barCode": [],
                                "batchYn": "false",
                                "bonusDscAmt": "0",
                                "bonusDscGb": [],
                                "bonusDscRt": "0.0",
                                "chinaSaleYn": [],
                                "ctgrCupnExYn": "N",
                                "ctgrPntPreRt": [],
                                "ctgrPntPreRtAmt": [],
                                "cupnDlv": "0",
                                "deliveryTimeOut": "false",
                                "delvplaceSeq": "140116474",
                                "dlvInsOrgCst": "0",
                                "dlvNo": "8000028718",
                                "dlvRewardAmt": "0",
                                "errMsg": [],
                                "finalDscPrc": [],
                                "firstDscAmt": "0",
                                "fixedDlvPrd": "false",
                                "giftPrdOptNo": "0",
                                "imgurl": [],
                                "isChangePayMethod": "N",
                                "isHistory": "Y",
                                "limitDt": [],
                                "lowPrcCompYn": [],
                                "mileDscAmt": "0.0",
                                "mileDscRt": "0.0",
                                "mileSaveAmt": "0.0",
                                "mileSaveRt": "0.0",
                                "ordPrdCpnAmtWithoutJang": "0",
                                "ordPrdRewardAmt": [],
                                "ordPrdRewardItmCd": [],
                                "ordPrdRewardItmCdNm": [],
                                "ordPrdSeq": "1",
                                "ordPrdStat": "202",
                                "ordQty": "1",
                                "orgBonusDscRt": "0.0",
                                "pluDscAmt": "0",
                                "pluDscBasis": "0",
                                "pluDscRt": "0.0",
                                "plusDscOcbRwd": "0",
                                "prdNm": "Product FRSIANPRODUCT000002 New",
                                "prdNo": "250911",
                                "prdOptSpcNo": "0",
                                "prdTtoDisconAmt": "0",
                                "prdTypCd": "01",
                                "procReturnDlvCstByBndl": "false",
                                "returnDlvCstByBndl": "0",
                                "rfndMtdCd": [],
                                "selPrc": "25000",
                                "sellerPrdCd": "FRSIANPRODUCT000002",
                                "stPntDscAmt": "0.0",
                                "stPntDscRt": "0.0",
                                "stlStat": [],
                                "tiketSelPrc": "0",
                                "tiketTransFee": "0",
                                "visitDlvYn": "N",
                                "prdClfCdNm": "Ready Stock"
                            }]
                        },
                        "lastSync": {
                            "sec": 1452845547,
                            "usec": 374000
                        }
                    },
                    "orderId": "201601156166852",
                    "partnerId": 143,
                    "acommerce": {
                        "order": {
                            "orderCreatedTime": {
                                "sec": 1452841018,
                                "usec": 0
                            },
                            "customerInfo": {
                                "addressee": "Elevenia",
                                "address1": "Andir Kota Bandung JAWA BARAT UKNOWN",
                                "province": "",
                                "postalCode": "0",
                                "country": "Indonesia",
                                "phone": "0878-81181818",
                                "email": "order@elevenia.co.id"
                            },
                            "orderShipmentInfo": {
                                "addressee": "Elevenia",
                                "address1": "Andir Kota Bandung JAWA BARAT UKNOWN",
                                "address2": "",
                                "subDistrict": "",
                                "district": "",
                                "city": "",
                                "province": "",
                                "postalCode": "0",
                                "country": "Indonesia",
                                "phone": "0878-81181818",
                                "email": "order@elevenia.co.id"
                            },
                            "paymentType": "NON_COD",
                            "shippingType": "STANDARD_2_4_DAYS",
                            "grossTotal": 25000,
                            "currUnit": "IDR",
                            "orderItems": [{
                                "partnerId": "143",
                                "itemId": "FRSIANPRODUCT000002",
                                "qty": 1,
                                "subTotal": 25000
                            }]
                        },
                        "lastSync": {
                            "sec": 1452845547,
                            "usec": 374000
                        }
                    },
                    "status": "NEW",
                    "createdDate": {
                        "sec": 1452845547,
                        "usec": 374000
                    },
                    "updatedDate": {
                        "sec": 1452845547,
                        "usec": 374000
                    }
                }]';

        return json_decode($json, true);
    }

    public function testCommandHandle()
    {
        $this->withoutMiddleware();
        /*
         * Define Mock for database, redis, package
         */

        // MOCK Mongo raw find()

        Mockery::mock('overload:App\Model\SalesOrder')
            ->shouldReceive("raw->find", "raw->update")
            ->once()
            ->andReturn($this->salesOrderData());

        // MOCK CMPS auth
        Mockery::mock('overload:ChannelBridge\Cmps\Auth')
            ->shouldReceive('get')
            ->once()
            ->andReturn(
                [
                    "message" => "success",
                    "body" => [
                        "token" => [
                            "token_id" => "123457809"
                        ]
                    ]
                ]
            );

        // MOCK CMPS SalesOrder
        Mockery::mock('overload:ChannelBridge\Cmps\SalesOrderStatus')
            ->shouldReceive("get")
            ->once()
            ->andReturn([
                "message" => "success",
                "body" => [
                    [
                        "shipPackage" => ["trackingId"=>"oijxon198u8h912"]
                    ]
                ]
            ]);

        // MOCK Elevenia order
        $elevOrder = Mockery::mock('overload:App\Library\Order');
        $elevOrder->shouldReceive('accept', 'updateAWB')
            ->andReturn(
                ['code' => 200],
                ['code' => 200]
            );

        $cmd = Artisan::call('salesorder:update');

    }
}