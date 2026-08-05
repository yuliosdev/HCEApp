<?php

use Illuminate\Support\Facades\Route;

// Ruta Principal
Route::get('/', function () {
    return view('welcome');
});
