<?php

namespace Smile00112\SpreadsheetsDataImport\Console\Commands;

use Illuminate\Console\Command;
use Smile00112\SpreadsheetsDataImport\Services\ImportService;

class ImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:spdi-import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
//        dd(config('spreadsheets-data-import.key'));
//        $doctorsSheet = new SheetDB(config('spreadsheets-data-import.key'), 'Врачи');
//        $data = $doctorsSheet->keys();

        //$this->components->error('!!!!!!!!!!!!!!');
        $this->components->info('Импорт информации из гугл таблицы...' );
        $import_service = new ImportService();
        $data = $import_service->import();

        dd($data);

    }
}
