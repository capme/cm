<?php

use App\Library\Inventory;

use GuzzleHttp\Handler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client;

class ProductTest extends TestCase
{
    public $token;
    public $mockEnabled;
    public $partnerId;

    protected $preserveGlobalState=false;
    protected $runTestInSeparateProcess=true;

    public function setUp()
    {
        parent::setUp();
        $this->token = 'fe868c8788f602061778b49949cf3643';
        $this->partnerId = 143;
        $this->mockEnabled = true;
    }

    public function testServerClientNetworkError()
    {
        $product = new Inventory($this->token);

        $handlerContext = [
            'errno' => 28,
            'error' => 'Connection timed out after 10004 milliseconds',
            'http_code' => 0
        ];

        $this->mockProduct($product, [
            new Response(501),
            new Response(404),
            new ConnectException('Network Error', new Request('GET', $this->baseUrl), null, $handlerContext)
        ]);

        $res = $product->getListProduct();
        $this->assertEquals(501, $res['code']);

        $res = $product->getListProduct();
        $this->assertEquals(404, $res['code']);


        $res = $product->getListProduct();
        $this->assertEquals(0, $res['code']);
    }

    public function testGetSuccess()
    {
        $product = new Inventory($this->token);

        $xml = "
            <Products>
                <product>
                    <advrtStmt>Super Sale For Product SWB2N</advrtStmt>
                    <bndlDlvCnYn>N</bndlDlvCnYn>
                    <cuponcheck>N</cuponcheck>
                    <dispCtgrNo>242</dispCtgrNo>
                    <dispCtgrStatCd>03</dispCtgrStatCd>
                    <dlvGrntYn>N</dlvGrntYn>
                    <exchDlvCst>0</exchDlvCst>
                    <htmlDetail>&lt;p&gt;Detail Barang SWB2N New. Super sale&lt;/p&gt;</htmlDetail>
                    <imageKindChk>01</imageKindChk>
                    <memberNo>26080677</memberNo>
                    <mstrPrdNo>0</mstrPrdNo>
                    <optionAllAddPrc>0</optionAllAddPrc>
                    <outsideYnIn>N</outsideYnIn>
                    <outsideYnOut>N</outsideYnOut>
                    <prdAttrCd></prdAttrCd>
                    <prdImage01>http://image.elevenia.co.id/g/2/5/4/8/3/2/254832_B.jpg</prdImage01>
                    <prdNm>Product SWB2N New</prdNm>
                    <prdNo>254832</prdNo>
                    <prdStatCd>01</prdStatCd>
                    <prdUpdYN>Y</prdUpdYN>
                    <prdWght>1000</prdWght>
                    <preSelPrc>0</preSelPrc>
                    <proxyYn>N</proxyYn>
                    <rtngdDlvCst>0</rtngdDlvCst>
                    <selMnbdNckNm></selMnbdNckNm>
                    <selMthdCd>01</selMthdCd>
                    <selPrc>25000</selPrc>
                    <selPrdClfCd></selPrdClfCd>
                    <selStatCd>103</selStatCd>
                    <selStatNm>Active</selStatNm>
                    <selTermUseYn>Y</selTermUseYn>
                    <sellerItemEventYn>N</sellerItemEventYn>
                    <sellerPrdCd>SWB2N</sellerPrdCd>
                    <shopNo>0</shopNo>
                    <validateMsg></validateMsg>
                    <nResult>0</nResult>
                    <createDt>2016-02-03 17:58:48</createDt>
                    <dispCtgrNm>Susu Formula</dispCtgrNm>
                    <dispCtgrNmMid>Susu Formula &amp; Makanan Bayi</dispCtgrNmMid>
                    <dispCtgrNmRoot>Perlengkapan Bayi</dispCtgrNmRoot>
                    <dscAmt>0</dscAmt>
                    <dscPrice>0</dscPrice>
                    <dispCtgrNo2>241</dispCtgrNo2>
                    <dispCtgrNo1>226</dispCtgrNo1>
                    <updateDt>2016-02-03 17:58:48</updateDt>
                </product>
                <product>
                    <advrtStmt>Super Sale For Product SWB1N</advrtStmt>
                    <bndlDlvCnYn>N</bndlDlvCnYn>
                    <cuponcheck>N</cuponcheck>
                    <dispCtgrNo>242</dispCtgrNo>
                    <dispCtgrStatCd>03</dispCtgrStatCd>
                    <dlvGrntYn>N</dlvGrntYn>
                    <exchDlvCst>0</exchDlvCst>
                    <htmlDetail>&lt;p&gt;Detail Barang SWB1N New. Super sale&lt;/p&gt;</htmlDetail>
                    <imageKindChk>01</imageKindChk>
                    <memberNo>26080677</memberNo>
                    <mstrPrdNo>0</mstrPrdNo>
                    <optionAllAddPrc>0</optionAllAddPrc>
                    <outsideYnIn>N</outsideYnIn>
                    <outsideYnOut>N</outsideYnOut>
                    <prdAttrCd></prdAttrCd>
                    <prdImage01>http://image.elevenia.co.id/g/2/5/4/7/0/3/254703_B.jpg</prdImage01>
                    <prdNm>Product SWB1N New</prdNm>
                    <prdNo>254703</prdNo>
                    <prdStatCd>01</prdStatCd>
                    <prdUpdYN>Y</prdUpdYN>
                    <prdWght>1000</prdWght>
                    <preSelPrc>0</preSelPrc>
                    <proxyYn>N</proxyYn>
                    <rtngdDlvCst>0</rtngdDlvCst>
                    <selMnbdNckNm></selMnbdNckNm>
                    <selMthdCd>01</selMthdCd>
                    <selPrc>25000</selPrc>
                    <selPrdClfCd></selPrdClfCd>
                    <selStatCd>103</selStatCd>
                    <selStatNm>Active</selStatNm>
                    <selTermUseYn>Y</selTermUseYn>
                    <sellerItemEventYn>N</sellerItemEventYn>
                    <sellerPrdCd>SWB1N</sellerPrdCd>
                    <shopNo>0</shopNo>
                    <validateMsg></validateMsg>
                    <nResult>0</nResult>
                    <createDt>2016-01-21 14:13:58</createDt>
                    <dispCtgrNm>Susu Formula</dispCtgrNm>
                    <dispCtgrNmMid>Susu Formula &amp; Makanan Bayi</dispCtgrNmMid>
                    <dispCtgrNmRoot>Perlengkapan Bayi</dispCtgrNmRoot>
                    <dscAmt>0</dscAmt>
                    <dscPrice>0</dscPrice>
                    <dispCtgrNo2>241</dispCtgrNo2>
                    <dispCtgrNo1>226</dispCtgrNo1>
                    <updateDt>2016-01-21 14:13:58</updateDt>
                </product>
            </Products>
        ";
        $this->mockProduct($product, [
            new Response(200, [], $xml)
        ]);

        $res = $product->getListProduct();


        $this->assertEquals(200, $res['code']);
        $this->assertArrayHasKey('product', $res['body']);
        $this->assertEquals(2, count($res['body']['product']));
    }

    private function mockProduct(Inventory $product, array $queue)
    {
        if (!$this->mockEnabled) return;
        $mock = new MockHandler($queue);
        $handler = HandlerStack::create($mock);
        $product->client = new Client(['handler'=>$handler]);
    }
}