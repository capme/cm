<?php

namespace App\Jobs;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use ChannelBridge\Cpms\SalesOrder;
use ChannelBridge\Cpms\Auth;
use App\Library\Order;
use Carbon\Carbon;
use Cache;
use Log;


class CreateSalesOrderToCpms extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels, DispatchesJobs;

    protected $orderElev;
    protected $partner;
    protected $cacheKey;

    public function __construct(array $partner, $orderElev)
    {
        $this->orderElev = $orderElev;
        $this->partner = $partner;
        $this->cacheKey = config('cache.prefix_cmps_token')
            . "elevenia"
            . $partner['partnerId'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Processing job create sales order to CPMS');
        $tokenExpiresAt = Carbon::now()->addMinutes(55);

        //parse elev structure into regional structure
        $order = new Order($this->partner['channel']['elevenia']['openapikey']);

        $order = $order->parseOrderFromEleveniaToCmps($this->partner['partnerId'], $this->orderElev);

        //get token id from redis. if not exists, authentication to regional
        $token = Cache::remember($this->cacheKey, $tokenExpiresAt, function(){
            $auth = new Auth();
            $url = getenv("CPMS_PROTOCOL") . getenv("CPMS_BASE_API_URL") . "/identity/token";
            $res = $auth->get($url,
                $this->partner['channel']['elevenia']['cpms']['username'],
                $this->partner['channel']['elevenia']['cpms']['apiKey']);

            if($res['message'] != 'success'){
                Log::error('Get CMPS Auth', [
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

        $salesOrder = new SalesOrder();

        $url = getenv('CPMS_PROTOCOL') . "fulfillment." . getenv("CPMS_BASE_API_URL")
            . "/channel/" . $this->partner['channel']['elevenia']["cpms"]['channelId']
            . "/order/" . $this->orderElev['ordNo'];

        $order['orderCreatedTime'] = gmdate("Y-m-d\TH:i:s\Z", $order['orderCreatedTime']->sec);

        $res = $salesOrder->create($token, $url, $order);

        if ($res['message'] != 'success' && $res['code'] != 501) {
            Log::error('Create sales order to CPMS', [
                'message' => $res['message'],
                'code' => $res['code']
            ]);

            return;
        } else { // if success create sales order to CPMS update channel to set accept order
            $this->dispatch(new UpdateSalesOrderToChannel([
                "partnerId" => $this->partner['partnerId'],
                "channel" => ["order" => $this->orderElev]
            ], "accept", 0));
        }

        Log::info('Success create sales order to CPMS');
    }
}
