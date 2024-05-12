<?php

namespace Smile00112\SpreadSheetsDataImport\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class PackageInstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spdi:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish & migrate';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ask = $this->ask('Начать установку? (Y/N)');
        //dd($ask);
        if(Str::lower($ask) === 'y'){
            $this->call('migrate');
            $this->call("vendor:publish --provider='Smile00112\SpreadsheetsDataImport\Providers\SpreadsheetDataImportServiceProvider'");
        }
    }
}
