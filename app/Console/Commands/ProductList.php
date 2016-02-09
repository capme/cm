<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Model\Product;
use App\Model\Partner;
use App\Library\Inventory;

class ProductList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $partner = Partner::raw()->find([]);
        foreach($partner as $val){
            $partnerId = $val['partnerId'];
            $openApiKeyElevenia = $val['channel']['elevenia']['openapikey'];
            $product = new Inventory($openApiKeyElevenia);
            $res = $product->getListProduct();
            foreach($res['body']['product'] as $valProduct) {
                $data = ['partnerId' => $partnerId, 'prdNo' => $valProduct['prdNo'], 'sellerPrdCd' => $valProduct['sellerPrdCd']];
                $product->save($partnerId,$data);
            }
        }
    }
}
