<?php

namespace App\Jobs;

use App\Jobs\Job;
use App\Model\SalesOrder;
use Acommerce\Cmp\SalesOrderStatus;
use App\Model\Partner;
use Carbon\Carbon;
use Acommerce\Cmp\Auth as CmpsAuth;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GetSalesOrderStatusUpdateFromCmp extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $partnerId;
    protected $orderId;
    protected $order;

    /**
     * Create a new job instance.
     * @param string $partnerId
     * @param string $orderId
     * @return void
     */
    public function __construct($partnerId, $orderId)
    {
        $this->partnerId = $partnerId;
        $this->orderId = $orderId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $tokenExpiresAt = Carbon::now()->addMinutes(60); // Expires CMPS token
        Log::info('Processing job GetSalesOrderStatusUpdate', [
            'channel' => 'elevenia',
            'partnerId' => $this->partnerId,
        ]);


        // Get CMPS Token from cache if not exists create new one and save to cache
        $token = Cache::remember(config('cache.prefix_cmps_token'), $tokenExpiresAt, function() {
            $partner = Partner::raw()->findOne(["partnerId"=>$this->partnerId], [
                "cmps.username" => true,
                "cmps.apiKey" => true
            ]);
            $token = new CmpsAuth();
            $res = $token->get("https://api.acommercedev.com/identity/token",
                $partner['cmps']['username'], $partner['cmps']['apiKey']);

            return $res['body']['token']['token_id'];
        });

        $salesOrderStatus = new SalesOrderStatus();

        // Get SalesOrderStatus from CMPS
        $url = "https://fulfillment.api.acommercedev.com/partner/"
            . $this->partnerId . "/sales-order-status/id?id=" . $this->orderId;
        Log::info('Get SalesOrderStatus',[
            "job" => "GetSalesOrderStatusUpdate",
            "url" => $url,
            "token" => $token
        ]);

        $res = $salesOrderStatus->get($token, $url);
        Log::info("SalesOrderStatus Response", [
            "response" => $res
        ]);

        /*
         * If there's status update "tracking ID"
         * update database and dispatch job elevenia update status
         */
        if ($res['message'] == "success") {
            $data = $res['body'][0];
            if (isset($data['shipPackage']['trackingId'])) {
                $status = "IN_TRANSIT";
                $trakingId = $data['shipPackage']['trackingId'];
                SalesOrder::raw()->update(
                    [
                        "orderId" => $this->orderId,
                        "partnerId" => $this->partnerId,
                        "channel.name" => "elevenia"
                    ],
                    [
                        '$set' => [
                            "acommerce.lastSync" => new \MongoDate(),
                            "trackingId" => $trakingId,
                            "status" => $status
                        ]
                    ]
                );
                Log::info("Success Update SalesOrderStatus");
                // TODO - Call Dispatch Update Elevenia Status
            }
        } else {
            Log::error("Failed to get SalesOrderStatus form CMPS", [
                "message" => $res["message"],
                "code" => $res['code']
            ]);
        }
    }
}
