<?php


class CommandUpdateSalesOrderTest extends TestCase
{
    public function testCommand()
    {
        $this->expectsJobs(App\Jobs\GetSalesOrderStatusFromCmps::class);
        Artisan::call('salesorder:update');
    }
}