# Spreadsheets data import
### Пакет Laravel для импорта данных из google spreadsheets в базу
### Вывод данных в разделе панели Fillament

### *Установка*
```
composer require smile00112/spreadsheets-data-import
```

### *Использование*
- нужен аккаунт и ключ https://sheetdb.io
  https://github.com/sheetdb/sheetdb-php

### Публикация
php artisan vendor:publish и выбрать провайдера (Smile00112\SpreadsheetsDataImport\Providers\SpreadsheetDataImportServiceProvider)
или
php artisan vendor:publish --provider='Smile00112\SpreadsheetsDataImport\Providers\SpreadsheetDataImportServiceProvider'
для публикации конфига --tag=config
