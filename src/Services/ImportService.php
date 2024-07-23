<?php

namespace Smile00112\SpreadsheetsDataImport\Services;
use App\Models\Direction;
use App\Models\User;
use SheetDB\SheetDB;
use voku\helper\ASCII;

class ImportService
{
    private string $key;
    private array $tabs_to_models_map;
    private $model;
    private string $modelsPath;
    private array $history = [];

    public function __construct()
    {
        $this->key = config('spreadsheets-data-import.sheet_db_key');
        $this->tabs_to_models_map = config('spreadsheets-data-import.tabs_to_models');
        $this->model = config('spreadsheets-data-import.model_name');
        $this->modelsPath = "\\App\\Models\\";

    }

    public function loadData()
    {
        foreach ( $this->tabs_to_models_map as $key => $value){
            $sheetdb = new SheetDB( $this->key, $key);
            $data = $sheetdb->get();

            foreach ($data as $table_row){
                $record = $this->model::create(
                    [
                        'data' => (array)$table_row,
                        'tab_name' => $key,
                    ]
                );
            }

        }
    }
    private function get_search_by_column_name($tab_map_data): string
    {
        foreach ($tab_map_data['columns_to_fields'] as $column_data){
            //dump($column_data);
            if($column_data['field'] === $tab_map_data['search_by_field'])
                return $column_data['column_name'];
        }
        return '';
    }
    public function data_to_models()
    {
        $statistic = [];
        foreach ( $this->tabs_to_models_map as $key => $tab_map_data){
            $statistic[$key] = 0;
            //Выборка в базе по закладке из настроек
            $tab_data = $this->model::where('tab_name', $key)->get();
            //Получаем привязанную модель из настроек
            $className = $this->modelsPath.$tab_map_data['model'];

            foreach ($tab_data as $table_row) {
                //Получаем название колонки из привязки её к полю из настроек
                $search_by_column_name = $this->get_search_by_column_name($tab_map_data);
                //Данные строки в таблице search_by_field
                $data = $table_row->data;
               // dd($data);
                if(!$search_by_column_name){
                    echo 'У закладки '.$key.' не найдена колонка для поля ' . $tab_map_data['search_by_field'] . ' | ';
                    continue;
                }
                //dd(json_encode($data[$search_by_column_name]));

                //Ищем запись в привязанной модели по полю из настроек
                $model = $this->findOrCreate($className, $tab_map_data, $search_by_column_name, $table_row);
                try {

                }
                catch (\Exception $e){
                    dump('Ошибка при обработке вкладки ' . $key);
                    dump($e->getMessage());
                    dump($e->getCode());
                    dump($e->getLine());
                    //dump($tab_map_data);
                    //return false;
                }

                $statistic[$key]++;
                //dd($tab_map_data);
            }
            if($key == 0)
                break;
        }

        dump('Статистика обработанных записей');
        dump($statistic);
    }
    private function findOrCreate($className, $tab_map_data, $search_by_column_name, $table_row)
    {
        $data = $table_row->data;

        $data_to_create = [];
        foreach ($tab_map_data['columns_to_fields'] as $fields_data){

            //Имеем дело с отнощениями в модели
            if(!empty($fields_data['model'])){

                //Ищем или создаём запись для привязки отнощения
                try {
                    $model =  $this->findOrCreate(
                        $this->modelsPath.$fields_data['model'],
                        $fields_data,
                        $this->get_search_by_column_name($fields_data),
                        $table_row
                    );
                }
                catch (\Exception $e){
                    dump('Ошибка при создании/поиске записи для отношений V1:');
                    dump($e->getMessage());
                    dd($fields_data);
                    continue;
                }

                $column = $model->id;
                //Добавим данные по id для отношений в сырые данные, чтобы не менять логику заполнения массива данных для модели
                $data[(string)$column] = $column;
            }else{
                $column = $fields_data['column_name'];
            }

            //Вызов функции трансформирования
            $data_to_create[$fields_data['field']] = $fields_data['transform'] ? $fields_data['transform']($data[(string)$column]) : $data[(string)$column];



        }

        //Обработка полей json (когда несколько колонок табл записываем в одно json поле)
        //признак поля - разделяющая название поля точка
        $data_to_implode = [];
        foreach($data_to_create as $field_name => $field_value){
            if(str_contains($field_name, '.')){
                $field_name_mod = explode('.', $field_name);
                $data_to_implode[$field_name_mod[0]][] = $field_value;
                unset($data_to_create[$field_name]);
            }
        }

        //dump($data_to_implode);

        //мержим данные для json поля
        foreach($data_to_implode as $dti_key => $dt_value){
            $data_to_implode[$dti_key] = array_merge_recursive($dt_value[0],  $dt_value[1], (!empty($dt_value[2]) ? $dt_value[2] : []), (!empty($dt_value[2]) ? $dt_value[2] : []));

            //Костыль для нормального объединения пар значений в отдельные под массивы
            if(!empty($data_to_implode[$dti_key]['ru'])){
                $data_to_implode[$dti_key]['ru'] = [array_merge_recursive($data_to_implode[$dti_key]['ru'][0], $data_to_implode[$dti_key]['ru'][1])];
            }
        }

        dump($data_to_implode);
        //echo '$data_to_create';
        //dd( array_merge_recursive($data_to_create, $data_to_implode) );
        //Объединяем данные для вставки с данными для объединения столбцов в json
        $data_to_create = array_merge_recursive($data_to_create, $data_to_implode);

        //Создание или обновление данных
        if ($className::whereJsonContains($tab_map_data['search_by_field'].'->ru', $data[$search_by_column_name] )->exists()) {
            //dump($className::first()?->name);
            //dump($className::first()?->doctor_speciality_name);
            //dump($className::whereJsonContains('name->ru', "Дермотология" )->get());
            //dd('---------------');

            //update
            $record = $className::whereJsonContains($tab_map_data['search_by_field'].'->ru', $data[$search_by_column_name] )->first();

            //если есть данные для объединённых полей, то запрашиваем данные поля из таблицы для ""накопления
            if(!empty($data_to_implode)){
                //первая итерация записи с json полем - чистим его
                if(empty($this->history[$className]) || !in_array($record->id, $this->history[$className])){
                    //dd($record->prices);
                    $record['prices'] = '{}';
                    $record->save();
                    $this->history[$className][]=$record->id;
                }else{
                    //Запись не старая - Объединяем предыдущую запись с новой
                    $data_to_create['prices'] = array_merge_recursive( $record->prices, $data_to_create['prices']);
                }
            }

            $record->update($data_to_create);
            dump(['Данные для обновления', $data_to_create]);
            //$table_row->resource()->associate($record);
            $table_row->save();
        } else {
            //create
            dump(['Данные для вставки', $data_to_create]);
            $record = $className::create($data_to_create);
            //Добавляем отношения из настроек
            $table_row->resource()->attach($record);
            $table_row->save();
//            echo 'CREATE';
            $this->history[$className][]=$record->id;
        }
        //dump($tab_map_data);

        //Добавляем отношения из настроек
        dump('RELATIONS');
        if(!empty($tab_map_data['relations']))
        foreach ($tab_map_data['relations'] as $relation){

            //Если поле пустое, пропускаем
            if(!$table_row->data[ $relation['column_name'] ]) {
                dump('column_name Empty');
                dump($relation);
                dump($table_row->data);
                dump('------------');
                continue;
            }

            try {
                $model_for_relation =  $this->findOrCreate(
                    $this->modelsPath.$relation['model'],
                    $relation,
                    $this->get_search_by_column_name($relation),
                    $table_row
                );
            }
            catch (\Exception $e){
                dump('Ошибка при создании/поиске записи для отношений V2:');
                dump($e->getMessage());
                dump($e->getLine());
                dd($table_row?->data);

                continue;
            }

            dump('Model associate');
            if($relation['relation_func'] && $relation['relation_add_method']){
                $record->{$relation['relation_func']}()->detach($model_for_relation);
                $record->{$relation['relation_func']}()->{$relation['relation_add_method']}($model_for_relation);
                $record->save();
            }
            //dd($record);
        }
//        else
//            dump('RELATIONS EMPTY');

        return  $record;


        $model = $className::where($search_by_field)->first();
        if(!$model){
            $model = new $className;
            $model->data = $data;
            $model->save();
        }
        return $model;
    }
    public function import(): void
    {
//        $this->model::truncate();
//        $this->loadData();
        $this->data_to_models();
    }

}
