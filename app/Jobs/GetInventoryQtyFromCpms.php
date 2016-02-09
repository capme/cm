<?php

namespace App\Jobs;
use ChannelBridge\Cpms\InventoryAllocation;
use ChannelBridge\Cpms\Auth as CpmsAuth;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use Cache;
use Log;
use App\Model\Partner;

class GetInventoryQtyFromCpms extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels, DispatchesJobs;

    protected $partnerId;
    protected $channel;
    protected $cacheKey;

    public function __construct($partnerId, $itemChannel)
    {
        $this->partnerId = $partnerId;
        $this->channel = $itemChannel;
        $this->cacheKey = config('cache.prefix_cpms_token')
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
        Log::debug('GetInventoryQtyFromCpms', [
            'status' => 'starting job',
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
                Log::error('GetInventoryQtyFromCpms', [
                    'status' => 'get auth from cpms',
                    'res' => $res
                ]);

                return null;
            }

            return $res['body']['token']['token_id'];
        });

        if (!$token) {
            return;
        }


        $inventoryAllocation = new InventoryAllocation();

        $url = getenv("CPMS_PROTOCOL") . "fulfillment." . getenv("CPMS_BASE_API_URL") . "/channel/"
            . $this->channel['cpms']['channelId'] . "/allocation/merchant" . $this->partnerId;
        $res = $inventoryAllocation->get($token, $url);

        if ($res['message'] != 'success') {
            Log::error("GetInventoryQtyFromCpms", [
                'status' => 'failed to get inventory',
                'response' => $res
            ]);

            $this->release();
            return;

        }

        foreach($res['body'] as $itemSku){
            // TODO - dispatch job to update Inventory quantity
            //$this->dispatch(new UpdateInventoryQtyToChannel($itemSku));
        }

    }
}
