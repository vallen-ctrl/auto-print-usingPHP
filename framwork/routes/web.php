<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\tokoControler;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix("public/api")->group(function() {
    Route::post("/toko", [tokoControler::class, "post"]);
});
