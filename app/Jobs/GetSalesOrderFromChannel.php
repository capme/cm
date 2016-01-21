<?php

namespace App\Jobs;

use Carbon\Carbon;
use Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\DispatchesJobs;
use App\Library\Order;

class GetSalesOrderFromChannel extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels, DispatchesJobs;

    protected $partner;
    protected $cacheKey;

    public function __construct(array $partner)
    {
        $this->partner = $partner;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info('Running job get new sales order from channel', [
            'channel' => 'elevenia',
            'partner_id' => $this->partner['partnerId']
        ]);

        $dateFrom = Carbon::yesterday(Order::TimeZone)->format(Order::DateFormat);

        $now = Carbon::now(Order::TimeZone);
        $dateTo = $now->format(Order::DateFormat);

        $order = new Order($this->partner['channel']['elevenia']['openapikey']);

        $res = $order->get([
            'ordStat' => Order::StatusPaid,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);

        // Retry if unsucessful http request
        if ($res['code'] !== 200 || isset($result['body']['message'])) {
            Log::error('OpenAPI key', [
                'channel' => 'elevenia',
                'partnerId' => $this->partner['partnerId'],
                'message' => $res['message'],
                'body' => $res['body']
            ]);
            $this->release();
        }

        if (!isset($res['body']['order'])) {
            Log::info('No new order', [
                'channel' => 'elevenia',
                'partnerId' => $this->partner['partnerId']
            ]);
            return;
        }

        // Let's sort this shit first
        $orders = $order->parseOrdersFromElevenia($result['body']['order']);
        foreach ($orders as $orderElev) {

            $order->save($this->partner['partnerId'], $orderElev);
            Log::info('success saving new order to mongodb', [
                'partnerId' => $this->partner['partnerId'],
                'order' => $orderElev,
            ]);
            //dispatch job to save to regional
            $this->dispatch(new CreateSalesOrderToCmps($this->partner, $orderElev));
        }
    }
}
