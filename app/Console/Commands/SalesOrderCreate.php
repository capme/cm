<?php

namespace App\Console\Commands;

use App\Jobs\GetSalesOrderFromChannel;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Model\Partner;

class SalesOrderCreate extends Command
{
    use DispatchesJobs;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'salesorder:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'get sales order from CHANNEL and create to CPMS';

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
        // Get partners
        $partners = Partner::raw()->find();

        $totalPartner = 0;

        // Iterate over partners to create job
        foreach ($partners as $partner) {
            $totalPartner += 1;
            $this->info('Dispatching partner: '.$partner['partnerId']);
            $this->dispatch(new GetSalesOrderFromChannel($partner));
        }

        if ($totalPartner == 0) {
            $this->info("No partners were found");
        } else {
            $this->info(sprintf('Found %d partners', count($partners)));
        }

    }
}
