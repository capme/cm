<?php

namespace App\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use ChannelBridge\Cmps\SalesOrder;
use ChannelBridge\Cmps\Auth;
use App\Library\Order;
use Carbon\Carbon;
use Cache;
use Log;


class CreateSalesOrderToCmps extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $order;
    protected $partner;
    protected $cacheKey;

    public function __construct(array $partner, $order)
    {
        $this->order = $order;
        $this->partner = $partner;
        $this->cacheKey = config('cache.prefix_cmps_token') . $partner['partnerId'];
    }

    public function getChannelBridgeSalesOrder()
    {
        return new SalesOrder();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Processing job create sales order to CMPS');
        $tokenExpiresAt = Carbon::now()->addMinutes(55);

        //parse elev structure into regional structure
        $order = new Order($this->partner['channel']['elevenia']['openapikey']);

        $order = $order->parseOrderFromEleveniaToCmps($this->partner['partnerId'], $this->order);

        //get token id from redis. if not exists, authentication to regional
        $token = Cache::remember($this->cacheKey, $tokenExpiresAt, function(){
            $auth = new Auth();
            $url = "https://".getenv("CMPS_BASE_API_URL")."/identity/token";
            $res = $auth->get($url, $this->partner['cmps']['username'], $this->partner['cmps']['apiKey']);

            if($res['message'] != 'success'){
                Log::error('Get CMPS Auth', [
                    'code' => $res['code'],
                    'message' => $res['message']
                ]);

                $this->release();
            }

            return $res['body']['token']['token_id'];
        });

        $salesOrder = $this->getChannelBridgeSalesOrder();

        $url = "https://fulfillment." . getenv("CMPS_BASE_API_URL")
            . "/channel/" . $this->partner['cmps']['username'] . "/order/" . $this->order['ordNo'];

        $order['orderCreatedTime'] = gmdate("Y-m-d\TH:i:s\Z", $order['orderCreatedTime']->sec);

        $res = $salesOrder->create($token, $url, $order);

        if ($res['message'] != 'success') {
            Log::error('Create sales order to CMPS', [
                'message' => $res['message'],
                'code' => $res['code']
            ]);
            $this->release();
        }

        Log::info('Success create sales order to CMPS');
    }
}
