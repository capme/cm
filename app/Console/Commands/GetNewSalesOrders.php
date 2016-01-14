<?php

namespace App\Console\Commands;

use App\Jobs\GetNewSalesOrder;
use Illuminate\Console\Command;
use Illuminate\Foundation\Bus\DispatchesJobs;
use DB;
use App\Model\Partner;

class GetNewSalesOrders extends Command
{
    use DispatchesJobs;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:getnew';

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
            $this->dispatch(new GetNewSalesOrder($partner));
        }
    }
}
