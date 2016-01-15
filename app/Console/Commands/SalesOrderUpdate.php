<?php

namespace App\Console\Commands;

use App\Jobs\GetSalesOrderStatusUpdateFromCmp;
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
    protected $description = 'Command description';

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
        $salesOrder = SalesOrder::raw()->find(["status" => "NEW"],
            [
                "orderId" => true,
                "partnerId" => true
            ]);

        if ($salesOrder) {
            $this->info(sprintf("Found %s \"NEW\" sales order", $salesOrder->count()));
        }

        foreach ($salesOrder as $val) {

            $partnerId = $val['partnerId'];
            $orderId = $val['orderId'];
            $this->info(sprintf("Dispatching partner %s with orderId %s channel Elevenia", $partnerId, $orderId));

            $this->dispatch(new GetSalesOrderStatusUpdateFromCmp($partnerId, $orderId));
        }
    }
}
