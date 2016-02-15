<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Model\ChannelProduct;

class ProductImportToDB extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:importtodb {partnerId} {input}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import products from CSV to DB';
    protected $partnerId;
    protected $inputFile;

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

        $this->inputFile = $this->argument('input');
        $this->partnerId = $this->argument('partnerId');
        try {
            $fp = fopen($this->inputFile, 'r');
            $rows = array();
            while (!feof($fp)) {
                $data = fgetcsv($fp);
                $prdNo = $data[0];
                $prdName = $data[1];

                if(trim($prdName) != "Name") {
                    $rows[$prdNo . "|" . $prdName][] = $data;
                }
            }
            fclose($fp);

            foreach($rows as $key => $value) {
                $arr = explode("|",$key);
                if(trim($arr[0]) == "") continue;
                $product = [
                    "channel" => "elevenia",
                    "partnerId" => $this->partnerId,
                    "prdNo" => $arr[0],
                    "prdName" => $arr[1],
                    "items" => []
                ];
                foreach($value as $itemValue){
                    if(trim($itemValue[2]) != "" && trim($itemValue[3]) != "")
                    {
                        $variant = [$itemValue[2], $itemValue[3]];
                    }
                    elseif(trim($itemValue[2]) == "" && trim($itemValue[3]) != "")
                    {
                        $variant = [$itemValue[3]];
                    }
                    elseif(trim($itemValue[3]) == "" && trim($itemValue[2]) != "")
                    {
                        $variant = [$itemValue[2]];
                    }
                    else
                    {
                        $variant = [];
                    }
                    $product["items"][] =
                        [
                            "sku" => $itemValue[4],
                            "variant" => $variant
                        ];
                }



                ChannelProduct::raw()->update(
                    $product,
                    ['$setOnInsert' => $product],
                    ["upsert" => true]
                );
            }

            $this->info(sprintf('Successfully import products to DB'));
        } catch (\Exception $e) {
            $this->info("Failed import products to DB. ".$e->getMessage());
        }
    }

}
