<?php

namespace App\Jobs;

use ChannelBridge\Cmps\SalesOrderStatus;
use ChannelBridge\Cmps\Auth as CmpsAuth;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Carbon\Carbon;
use Cache;
use Log;
use App\Model\Partner;
use App\Model\SalesOrder;

class GetSalesOrderStatusFromCmps extends Job implements ShouldQueue
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
        $this->cacheKey = config('cache.prefix_cmps_token') . $partnerId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $tokenExpiresAt = Carbon::now()->addMinutes(55); // Expires CMPS token
        Log::info('Processing job get sales order from CMPS', [
            'channel' => 'elevenia',
            'partnerId' => $this->partnerId,
        ]);

        // Get CMPS Token from cache if not exists create new one and save to cache
        $token = Cache::remember($this->cacheKey, $tokenExpiresAt, function() {
            $partner = Partner::raw()->findOne(["partnerId"=>$this->partnerId], [
                "cmps.username" => true,
                "cmps.apiKey" => true
            ]);

            $token = new CmpsAuth();
            $res = $token->get("https://" . getenv("CMPS_BASE_API_URL") . "/identity/token",
                $partner['cmps']['username'], $partner['cmps']['apiKey']);
            if ($res['message'] != 'success') {
                Log::error('CMPS Auth', [
                    'code' => $res['code'],
                    'message' => $res['message']
                ]);

                $this->release();
            }
            return $res['body']['token']['token_id'];
        });

        $salesOrderStatus = new SalesOrderStatus();

        // Get SalesOrderStatus from CMPS
        $url = "https://fulfillment." . getenv("CMPS_BASE_API_URL") . "/partner/"
            . $this->partnerId . "/sales-order-status/id?id=" . $this->orderId;


        $res = $salesOrderStatus->get($token, $url);

        if ($res['message'] != 'success') {
            Log::error("Failed to get SalesOrderStatus form CMPS", [
                "message" => $res["message"],
                "code" => $res['code']
            ]);
            $this->release();
        }

        $data = $res['body'][0];

        if (!isset($data['shipPackage']['trackingId'])) {
            Log::info("no updated sales order status", [
                "channel" => "elevenia",
                "partnerId" => $this->partnerId,
                "orderId" => $this->orderId
            ]);
            return;
        }

        SalesOrder::raw()->update(
            [
                "orderId" => $this->orderId,
                "partnerId" => $this->partnerId,
                "channel.name" => "elevenia"
            ],
            [
                '$set' => [
                    "acommerce.lastSync" => new \MongoDate(),
                    "trackingId" => $data['shipPackage']['trackingId'],
                    "status" => 'IN_TRANSIT'
                ]
            ]
        );

        Log::info("success update sales order status on mongo", [
            "channel" => "elevenia",
            "partnerId" => $this->partnerId,
            "orderId" => $this->orderId
        ]);

        $this->dispatch(new UpdateSalesOrderToChannel($this->salesOrder));

    }
}
