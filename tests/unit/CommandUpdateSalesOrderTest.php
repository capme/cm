<?php


class CommandUpdateSalesOrderTest extends TestCase
{
    public function testCommand()
    {
        $this->expectsJobs(App\Jobs\GetSalesOrderStatusFromCpms::class);
        Artisan::call('salesorder:update');
    }
}