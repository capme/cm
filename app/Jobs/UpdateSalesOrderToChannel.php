<?php

namespace App\Jobs;

use Log;
use App\Library\Order;
use App\Model\Partner;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateSalesOrderToChannel extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels, DispatchesJobs;

    /**
     * @var Order
     */
    protected $orderService;
    protected $salesOrder;
    protected $updateStep;
    protected $productIndex;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $salesOrder, $updateStep = null, $productIndex = null)
    {
        $this->salesOrder = $salesOrder;
        $this->updateStep = $updateStep;
        $this->productIndex = $productIndex;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $partner = Partner::raw()->findOne([
            'partnerId' => $this->salesOrder['partnerId']
        ]);

        $this->orderService = new Order($partner['channel']['elevenia']['openapikey']);

        if ($this->updateStep === null) {
            $this->updateStep = 'accept';
            $this->productIndex = 0;
        }

        Log::info("UpdateSalesOrderToChannel", [
            "step" => $this->updateStep,
            "productIndex" => $this->productIndex,
            "orderId" => $this->salesOrder['orderId'],
            "trackingId" => $this->salesOrder['trackingId']
        ]);

        $products = $this->salesOrder['channel']['order']['productList'];
        switch ($this->updateStep) {
            case 'accept':
                $this->updateAccept($products[$this->productIndex]);
                $this->productIndex++;

                if (!isset($products[$this->productIndex])) {
                    $this->updateStep = 'awb';
                    $this->productIndex = 0;
                }
                break;
            case 'awb':
                $this->updateAwb($products[$this->productIndex]);
                $this->productIndex++;
                if (!isset($products[$this->productIndex])) {
                    return;
                }
                break;
        }

        $this->dispatch(new UpdateSalesOrderToChannel($this->salesOrder, $this->updateStep, $this->productIndex));
    }

    protected function updateAccept($product)
    {
        $elevOrder = $this->salesOrder['channel']['order'];
        $res = $this->orderService->accept([
            'ordNo' => $elevOrder['ordNo'],
            'ordPrdSeq' => $product['ordPrdSeq'],
        ]);
        if ($res['code'] !== 200) {
            Log::error('Update AWB error', [
                'type' => 'job',
                'job' => __CLASS__,
                'body' => $res,
            ]);
            throw new \ErrorException('Acccepting order error');
        }
    }

    protected function updateAwb($product)
    {
        $elevOrder = $this->salesOrder['channel']['order'];
        $res = $this->orderService->updateAWB([
            'awb' => $this->salesOrder['trackingId'],
            'dlvNo' => $elevOrder['dlvNo'],
            'dlvMthdCd' => $elevOrder['dlvMthdCd'],
            'dlvEtprsCd' => $elevOrder['dlvEtprsCd'],
            'ordNo' => $elevOrder['ordNo'],
            'dlvEtprsNm' => $elevOrder['dlvEtprsNm'],
            'ordPrdSeq' => $product['ordPrdSeq'],
        ]);
        if ($res['code'] !== 200) {
            Log::error('Update AWB error', [
                'type' => 'job',
                'job' => __CLASS__,
                'body' => $res,
            ]);
            //throw new \ErrorException('Update AWB error');
        }
    }
}