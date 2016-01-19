<?php

namespace App\Jobs;

use App\Model\Partner;
use Carbon\Carbon;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Acommerce\Cmp\SalesOrder;
use Acommerce\Cmp\Auth;
use App\Library\Order;
use Redis;
use Log;



class SaveSalesOrderToRegional extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $order;
    protected $partner;
    protected $tokenId;
    protected $cacheKey;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Partner $partner, $order)
    {
        $this->order = $order;
        $this->partner = $partner;
        $this->cacheKey = config('cache.prefix_cmps_token').$partner->partnerId;
        $this->tokenId = Redis::get($this->cacheKey);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //parse elev structure into regional structure
        $order = new Order($this->partner->channel['elevenia']['openapikey']);
        $ret = $order->parseOrderFromEleveniaToCmps($this->partner->partnerId, $this->order);

        //get token id from redis. if not exists, authentication to regional
        if(!isset($this->tokenId)){
            $auth = new Auth();
            $urlAuth = $urlAuth = "https://".getenv("CMPS_BASE_API_URL")."/identity/token";
            $resAuth = $auth->get($urlAuth, $this->partner->cmps['username'], $this->partner->cmps['apiKey']);
            if(!isset($resAuth['body'])){
                //authentication failed
                $this->release();
                return;
            }
            Redis::set($this->cacheKey, $resAuth['body']['token']['token_id']);
            $this->tokenId = $resAuth['body']['token']['token_id'];
        }

        $salesOrder = new SalesOrder();
            $urlPutSalesOrder = "https://fulfillment.".getenv("CMPS_BASE_API_URL")."/channel/".$this->partner->cmps['username']."/order/" . $this->order['ordNo'];
            $ret['orderCreatedTime'] = gmdate("Y-m-d\TH:i:s\Z", $ret['orderCreatedTime']->sec);
            $res = $salesOrder->create($this->tokenId, $urlPutSalesOrder, $ret);
            if($res['code'] == "401"){
                $auth = new Auth();
                $urlAuth = $urlAuth = "https://".getenv("CMPS_BASE_API_URL")."/identity/token";
                $resAuth = $auth->get($urlAuth, $this->partner->cmps['username'], $this->partner->cmps['apiKey']);
                if(!isset($resAuth['body'])){
                    //authentication failed
                    Log::error('Authentication Failed', $resAuth);
                    $this->release();
                    return;
                }
                Redis::set($this->cacheKey, $resAuth['body']['token']['token_id']);
                $this->tokenId = $resAuth['body']['token']['token_id'];
                $this->release();
                return;
            }

            Log::info('Send Order To Regional', $res);
    }
}
