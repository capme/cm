<?php

namespace App\Jobs;

use ChannelBridge\Cpms\SalesOrderStatus;
use ChannelBridge\Cpms\Auth as CpmsAuth;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use Cache;
use Log;
use App\Model\Partner;
use App\Model\SalesOrder;

class GetSalesOrderStatusFromCpms extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels, DispatchesJobs;

    protected $partnerId;
    protected $orderId;
    protected $order;
    protected $salesOrder;
    protected $cacheKey;

    public function __construct($partnerId, $orderId, $salesOrder)
    {
        $this->partnerId = $partnerId;
        $this->orderId = $orderId;
        $this->salesOrder = $salesOrder;
        $this->cacheKey = config('cache.prefix_cmps_token')
            . "elevenia"
            . $partnerId;
    }

    /**
     * Execute the job.
     *
     * @return string
     */
    public function handle()
    {
        $tokenExpiresAt = 50; // Expires CMPS token
        Log::info('Processing job get sales order from CPMS', [
            'channel' => 'elevenia',
            'partnerId' => $this->partnerId,
        ]);

        // Get CMPS Token from cache if not exists create new one and save to cache
        $token = Cache::remember($this->cacheKey, $tokenExpiresAt, function() {
            $partner = Partner::raw()->findOne(["partnerId"=>$this->partnerId], [
                "channel.elevenia.cpms.username" => true,
                "channel.elevenia.cpms.apiKey" => true
            ]);

            $token = new CpmsAuth();
            $res = $token->get(getenv("CPMS_PROTOCOL") . getenv("CPMS_BASE_API_URL") . "/identity/token",
                $partner['channel']['elevenia']['cpms']['username'],
                $partner['channel']['elevenia']['cpms']['apiKey']);

            if ($res['message'] != 'success') {
                Log::error('CPMS Auth', [
                    'code' => $res['code'],
                    'message' => $res['message']
                ]);

                return null;
            }

            return $res['body']['token']['token_id'];
        });

        if (!$token) {
            return;
        }

        $salesOrderStatus = new SalesOrderStatus();

        // Get SalesOrderStatus from CMPS
        $url = getenv("CPMS_PROTOCOL") . "fulfillment." . getenv("CPMS_BASE_API_URL") . "/partner/"
            . $this->partnerId . "/sales-order-status/id?id=" . $this->orderId;

        $res = $salesOrderStatus->get($token, $url);
        //Log::info(print_r($res, true));

        if ($res['message'] != 'success') {
            Log::error("Failed to get SalesOrderStatus form CPMS", [
                "message" => $res["message"],
                "code" => $res['code']
            ]);

            $this->release();
            return;

        }

        $data = $res['body'][0];

        if (!isset($data['shipPackage'][0]['trackingId'])) {
            Log::info("no updated sales order status", [
                "channel" => "elevenia",
                "partnerId" => $this->partnerId,
                "orderId" => $this->orderId
            ]);
            return;
        }

        // set tracking id to sales order for update to elevenia channel
        $this->salesOrder["trackingId"] = $data['shipPackage'][0]['trackingId'];

        $this->dispatch(new UpdateSalesOrderToChannel($this->salesOrder, "awb", 0));

    }
}
