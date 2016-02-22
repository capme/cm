<?php

namespace App\Console\Commands;

use App\Jobs\GetSalesOrderStatusFromCpms;
use App\Model\SalesOrder;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesJobs;

class SalesOrderUpdate extends Command
{
    use DispatchesJobs;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'salesorder:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get Sales Order Status Update and update AWB to Channel';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $salesOrder = SalesOrder::raw()->find(["status" => "NEW", "channel.name" => "elevenia"]);

        $foundSalesOrder = 0;

        foreach ($salesOrder as $val) {
            $foundSalesOrder += 1;

            $partnerId = $val['partnerId'];
            $orderId = $val['orderId'];
            $this->info(sprintf("Dispatching partner %s with orderId %s channel Elevenia", $partnerId, $orderId));

            $this->dispatch(new GetSalesOrderStatusFromCpms($partnerId, $orderId, $val));
        }

        if ($foundSalesOrder == 0 ) {
            $this->info("No sales order were found");
        } else {
            $this->info(sprintf("Found %s \"NEW\" sales order", $foundSalesOrder));
        }

    }
}
