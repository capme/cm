<?php

class GetNewSalesOrdersTest extends TestCase
{
    public function testJobs()
    {
        $this->expectsJobs(\App\Jobs\GetSalesOrdersFromChannel::class);

        Artisan::call('salesorder:create');
    }
}