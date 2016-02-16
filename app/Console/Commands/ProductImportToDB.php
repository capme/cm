<?php

namespace App\Console\Commands;

use League\Csv\Reader;

use Illuminate\Console\Command;
use App\Model\ChannelProduct;



class ProductImportToDB extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:importtodb {partnerId} {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import products from CSV to DB';
    protected $partnerId;
    protected $file;

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
        $this->partnerId = $this->argument('partnerId');
        $this->file = $this->argument('file');
        try {

            $csv = Reader::createFromPath($this->file);
            $res = $csv->setOffset(1)->fetchAll();
            $channelProduct = [];
            foreach ($res as $val) {
                if (isset($channelProduct[$val[0]])) {
                    $add = [
                        "sku" => trim($val[4]),
                        "variant" => []
                    ];
                    if ($val[2]) array_push($add['variant'], $val[2]);
                    if ($val[3]) array_push($add['variant'], $val[3]);
                    array_push($channelProduct[$val[0]]['items'], $add);
                } else {
                    $channelProduct[$val[0]] = [
                        "channel" => "elevenia",
                        "partnerId" => (int)$this->partnerId,
                        "prdNo" => (int)$val[0],
                        "prdName" => trim($val[1]),
                        "items" => [
                            [
                                "sku" => trim($val[4]),
                                "variant" => []
                            ]
                        ]
                    ];

                    if ($val[2]) array_push($channelProduct[$val[0]]['items'][0]['variant'], $val[2]);
                    if ($val[3]) array_push($channelProduct[$val[0]]['items'][0]['variant'], $val[3]);
                }
            }

            ChannelProduct::raw()->remove(['partnerId'=>(int)$this->partnerId, 'channel' => 'elevenia']);
            ChannelProduct::raw()->batchInsert($channelProduct);


            $this->info(sprintf('Successfully import products to DB'));
        } catch (\Exception $e) {
            $this->info("Failed import products to DB. ".$e->getMessage());
        }
    }

}
