<?php
namespace Smile00112\SpreadsheetsDataImport\Providers;

use Illuminate\Support\ServiceProvider;
use Smile00112\SpreadsheetsDataImport\Console\Commands\ImportCommand;
use Smile00112\SpreadsheetsDataImport\Console\Commands\PackageInstallCommand;
//use Smile00112\SpreadsheetsDataImport\Console\Commands\InstCommand;


class SpreadsheetDataImportServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //$this->loadRoutesFrom(__DIR__.'/../routes/spread_sheets_import.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->publishes([
//            __DIR__.'/../database/migrations/' => database_path('migrations'),
            __DIR__.'/../config/spreadsheets-data-import.php' => config_path('spreadsheets-data-import.php'),
        ]);
        if ($this->app->runningInConsole()) {
            $this->commands([
                PackageInstallCommand::class,
                ImportCommand::class,
            ]);
        }
    }
}
