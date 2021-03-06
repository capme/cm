<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\SalesOrderCreate::class,
        Commands\SalesOrderUpdate::class,
        Commands\InventorySync::class,
        Commands\ProductList::class,
        Commands\ProductExportFromChannel::class,
        Commands\ProductImportToDB::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command("salesorder:create")
            ->everyTenMinutes();
        $schedule->command("salesorder:update")
            ->everyTenMinutes();
    }
}
