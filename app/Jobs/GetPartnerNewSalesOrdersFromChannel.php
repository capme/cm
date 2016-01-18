<?php

namespace App\Jobs;

use App\Jobs\Job;
use Carbon\Carbon;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Library\Order;
use App\Model\SalesOrder;
use App\Model\Partner;
use Log;
use Redis;

class GetPartnerNewSalesOrdersFromChannel extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels, DispatchesJobs;

    protected $partner;
    protected $cacheKey;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Partner $partner)
    {
        $this->partner = $partner;
        $this->cacheKey = __CLASS__.':'.$partner->id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Processing job', [
            'type' => 'job',
            'channel' => 'elevenia',
            'partner_id' => $this->partner->id,
            'partner_name' => $this->partner->cmps['username'],
            'key' => $this->partner->channel['elevenia']['openapikey'],
        ]);
        $dateFrom = Redis::get($this->cacheKey);
        if (!$dateFrom)
            $dateFrom = Carbon::yesterday(Order::TimeZone)->format(Order::DateFormat);

        $now = Carbon::now(Order::TimeZone);
        $dateTo = $now->format(Order::DateFormat);

        // Order service
        $order = new Order($this->partner->channel['elevenia']['openapikey']);

        $result = $order->get([
            'ordStat' => Order::StatusPaid,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);

        // Retry if unsucessful http request
        if ($result['code'] !== 200) {
            $this->release();
        // Even if the status is 200 it could still be error
        } elseif (isset($result['body']['message'])) {
            Log::error('Invalid OpenAPI key', [
                'channel' => 'elevenia',
                'partnerId' => $this->partner->id,
                'partnerName' => $this->partner->cmps['username'],
                'key' => $this->partner->channel['elevenia']['openapikey'],
                'error' => $result['body']['message'],
                'body' => $result['body'],
            ]);
            $this->release();
            return;
        } elseif (!isset($result['body']['order'])) {
            Log::info('No order in the body', [
                'channel' => 'elevenia',
                'partnerId' => $this->partner->id,
                'partnerName' => $this->partner->cmps['username'],
                'key' => $this->partner->channel['elevenia']['openapikey'],
                'body' => $result['body'],
            ]);
            return;
        }

        // Save last succesful date
        Redis::set($this->cacheKey, $dateTo);

        // Let's sort this shit first
        $orders = $order->parseOrdersFromElevenia($result['body']['order']);
        foreach ($orders as $elevOrder) {
            Log::info('Saving order', [
                'partnerId' => $this->partner->partnerId,
                'order' => $elevOrder,
            ]);
            $savedDbOrder = $order->save($this->partner->partnerId, $elevOrder);
            //dispatch job to save to regional
            $this->dispatch(new SaveSalesOrderToRegional($this->partner, $elevOrder));
        }
    }
}
