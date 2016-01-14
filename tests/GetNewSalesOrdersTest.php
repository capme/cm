<?php

class GetNewSalesOrdersTest extends TestCase
{
    public function testJobs()
    {
        $this->expectsJobs(App\Jobs\GetNewSalesOrder::class);

        Artisan::call('orders:getnew');
    }
}