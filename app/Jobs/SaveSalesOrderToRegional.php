<?php

namespace App\Jobs;

use App\Model\Partner;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Acommerce\Cmp\SalesOrder;
use Acommerce\Cmp\Auth;
use App\Library\Order;
use Redis;


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
        $this->cacheKey = __CLASS__.':'.$partner->cmps['username'];
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
        $ret = $order->parseOrderFromEleveniaToCmps($this->partner->partnerId."", $this->order);

        //get token id from redis. if not exists, authentication to regional
        if(!isset($this->tokenId)){
            $auth = new Auth();
            $urlAuth = $urlAuth = "https://api.acommercedev.com/identity/token";
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
        foreach ($ret as $keyRes => $itemRes) {
            $urlPutSalesOrder = "https://fulfillment.api.acommercedev.com/channel/".$this->partner->cmps['username']."/order/" . $keyRes;
            $res = $salesOrder->create($this->tokenId, $urlPutSalesOrder, $itemRes);
        }
    }
}
