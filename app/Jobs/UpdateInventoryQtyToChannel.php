<?php

namespace App\Jobs;

use App\Jobs\Job;
use App\Model\Partner;
use App\Model\Product;
use App\Library\Inventory;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateInventoryQtyToChannel extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels, DispatchesJobs;

    protected $partnerId;
    protected $sku;
    protected $qty;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($partnerId, $skuQtyInfo)
    {
        $this->partnerId = $partnerId;
        $this->sku = $skuQtyInfo['sku'];
        $this->qty = $skuQtyInfo['qty'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $partner = Partner::raw()->findOne(['partnerId' => $this->partnerId]);
        $inventoryService = new Inventory($partner['channel']['elevenia']['openapikey']);
        $res = $inventoryService->updateProductStock($this->sku, $this->qty);
        if ($res !== 200) {
            $this->release();
            Log::error('Error updating stock to Elevenia', [
                'partnerId' => $this->partnerId,
                'sku' => $this->sku,
                'qty' => $this->qty,
                'res' => $res,
            ]);
            return;
        }

        Log::info('Successfully updating stock to Elevenia', [
            'partnerId' => $this->partnerId,
            'sku' => $this->sku,
            'qty' => $this->qty,
        ]);
    }
}
