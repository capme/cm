<?php

class GetNewSalesOrdersTest extends TestCase
{
    public function testJobs()
    {
        $this->expectsJobs(App\Jobs\GetPartnerNewSalesOrdersFromChannel::class);

        Artisan::call('orders:getnew');
    }
}