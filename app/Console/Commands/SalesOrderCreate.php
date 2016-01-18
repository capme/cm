<?php

namespace App\Console\Commands;

use App\Jobs\GetPartnerNewSalesOrdersFromChannel;
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
        // Get partners
        $partners = Partner::all();
        $this->info(sprintf('Found %d partners', count($partners)));
        // Iterate over partners to create job
        foreach ($partners as $partner) {
            $this->info('Dispatching partner: '.$partner->id);
            $this->dispatch(new GetPartnerNewSalesOrdersFromChannel($partner));
        }
    }
}
