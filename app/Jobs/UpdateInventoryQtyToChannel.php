<?php

namespace App\Jobs;

use App\Jobs\Job;
use App\Model\Product;
use App\Library\Inventory;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateInventoryQtyToChannel extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels, DispatchesJobs;

    protected $skuQtyInfo;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($skuQtyInfo)
    {
        $this->skuQtyInfo = $skuQtyInfo;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $elevItem = Product::raw()->findOne(['sellerSku' => $this->skuQtyInfo['sku']]);

    }
}
