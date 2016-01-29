<?php

class CommandCreateSalesOrderTest extends TestCase
{
    public function testCommand()
    {
        $this->expectsJobs(App\Jobs\GetSalesOrderFromChannel::class);
        Artisan::call('salesorder:create');
    }
}