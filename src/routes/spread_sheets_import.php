<?php


use Illuminate\Support\Facades\Route;


Route::get('/import', function () {
    return view('spreadsheetsDataImport::index');
});
