<?php

class SalesOrderCreateTest extends TestCase
{
    public $samplePartners;

    public function setUp()
    {
        parent::setUp();
        $this->samplePartners = json_decode('[{ "_id" : "5694e60e8b36a1721d3f6e50", "partnerId" : 143, "channel" : { "elevenia" : { "openapikey" : "fe868c8788f602061778b49949cf3643", "email" : "test11.acom@gmail.com", "password" : "acom2015" }, "frisianflag" : {  } }, "cmps" : { "username" : "frisianflag", "apiKey" : "frisianflag123!" } }]', true);
    }

    public function testCommand()
    {
        $this->expectsJobs(\App\Jobs\GetSalesOrdersFromChannel::class);

        $mock = Mockery::mock('alias:App\Model\Partner')
            ->shouldReceive('raw->find')
            ->once()
            ->andReturn(
                $this->samplePartners
            );

        Artisan::call('salesorder:create');
    }

    public function testSalesOrderCreateJob()
    {
        $this->expectsJobs(\App\Jobs\CreateSalesOrderToCmps::class);

        $order = Mockery::mock(new \App\Library\Order($this->samplePartners[0]['partnerId']));
        $order->shouldReceive('get')
            ->once()
            ->andReturn([
                'code' => 200,
                'body' => [
                    'order' => []
                ]
            ]);
        $order->shouldReceive('parseOrdersFromElevenia')
            ->once()
            ->andReturn([

            ]);
        $order->shouldReceive('save')
            ->once();

        $job = Mockery::mock(new App\Jobs\GetSalesOrdersFromChannel($this->samplePartners[0]));
        $job->shouldNotReceive('release');
        $job->shouldReceive('getOrder')
            ->andReturn($order);

        $job->handle();

    }
}