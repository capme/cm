<?php

namespace App\Console\Commands;

use App\Library\Inventory;
use Illuminate\Console\Command;

class ProductExportFromChannel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:exportfromchannel {token} {output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export products from Elevania to CSV';

    protected $apiKey;
    protected $outputFile;

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
        $this->apiKey = $this->argument('token');

        $this->outputFile = $this->argument('output');

        if (file_exists($this->outputFile)) {
            $this->error('File already exist: '.$this->outputFile);
            return 1;
        }

        $fp = fopen($this->outputFile, 'w');
        fputcsv($fp, [
            'Number (prdNo)',
            'Name',
            'Option 1',
            'Option 2',
            'aCom SKU',
        ]) or $this->operationError('Error writing to file');

        $inventory = new Inventory($this->apiKey, 60);

        $count = 0;
        $page = 0;
        do {
            $page++;

            $this->info(sprintf('Getting item list for API: %s page #%d', $this->apiKey, $page));
            $res = $inventory->getListProduct($page);
            if ($res['code'] !== 200) {
                $this->apiError($res);
            }

            // If their service is fucked up they will also return 200 so we need to check whether it's a valid response
            if (!isset($res['body']) || !is_array($res['body'])) {
                $this->operationError("Found no product in response body, dumping response:\n".var_export($res, true));
            }

            // So, yeah, this shit can happen as well
            $products = isset($res['body']['product']) ? $res['body']['product'] : [];
            if (!isset($products[0]))
                $products = [$products]; // Goddamn xml we need to check whether there's only 1 product
            $this->info(sprintf('Found %d products', count($products)));
            foreach ($products as $product) {
                $prodNo = $product['prdNo'];
                $this->info(sprintf('Getting detail for product %s', $prodNo));
                $res = $inventory->getProductDetail($prodNo);
                if ($res['code'] !== 200) {
                    $this->apiError($res);
                }

                $product = $res['body'];

                if (isset($product['ProductOptionDetails'])) {
                    // *Sigh*
                    $details = isset($product['ProductOptionDetails'][0]) ?
                        $product['ProductOptionDetails'] :
                        [$product['ProductOptionDetails']];
                    foreach ($details as $detail) {
                        fputcsv($fp, [
                            $product['prdNo'],
                            $product['prdNm'],
                            isset($detail['colValue0']) ? $detail['colValue0'] : '',
                            isset($detail['colValue1']) ? $detail['colValue1'] : '',
                            '',
                        ]) or $this->operationError('Error writing to file');
                        $count++;
                    }
                } else {
                    fputcsv($fp, [
                        $product['prdNo'],
                        $product['prdNm'],
                        '',
                        '',
                        '',
                    ]) or $this->operationError('Error writing to file');
                    $count++;
                }
            }
        } while (count($products));

        fclose($fp);
        $this->info(sprintf('Successfully expoted %d products', $count));
    }

    protected function operationError($message)
    {
        $this->error($message);
        exit(1);
    }

    protected function apiError($res)
    {
        if (isset($res['message']))
            $message = $res['message'];
        elseif (isset($res['body']['message']))
            $message = $res['body']['message'];
        else
            $message = 'N/A';
        $this->error(sprintf('API error #%d (%s)', $message));
        exit(3);
    }
}
