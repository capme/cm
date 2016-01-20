<?php

class SalesOrderUpdateChannelTest extends TestCase
{
    /**
     * @expectedException Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function testPartner()
    {
        $salesOrder = new \App\Model\SalesOrder();
        $salesOrder->partnerId = 'NONEXISTANTID'.mt_rand(100000,999999);

        $job = new \App\Jobs\SalesOrderUpdateChannel($salesOrder);
        $job->handle();
    }

    public function testHandle()
    {
        $this->expectsJobs(App\Jobs\SalesOrderUpdateChannel::class);

        $salesOrder = new \App\Model\SalesOrder();
        $salesOrder->partnerId = \App\Model\Partner::first()->partnerId;

        $job = new \App\Jobs\SalesOrderUpdateChannel($salesOrder);
        $job->handle();
    }
}
