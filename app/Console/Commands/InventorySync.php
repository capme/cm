<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Model\Partner;
use App\Jobs\GetInventoryQtyFromCpms;
use Illuminate\Foundation\Bus\DispatchesJobs;

class InventorySync extends Command
{
    use DispatchesJobs;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:sync';

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
        $partner = Partner::raw()->find([]);
        foreach($partner as $val){
            $partnerId = $val['partnerId'];
            //get qty for each partner on each channel in CPMS and then dispatch it
            foreach($val['channel'] as $keyChannel => $itemChannel){
                $this->info("Get Qty From Partner Id ".$partnerId.", Channel : " . $keyChannel);
                $this->dispatch(new GetInventoryQtyFromCpms($partnerId, $itemChannel));
            }
        }
    }
}
