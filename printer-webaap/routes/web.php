<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard/admin/login', [\App\http\Controllers\loginControler::class, "get"])->name('admin.login');
Route::post('/dashboard/admin/login', [\App\http\Controllers\loginControler::class, "login"]);

Route::get('/dashboard/admin/signup', [\App\http\Controllers\signupControler::class, "get"]);
Route::post('/dashboard/admin/signup', [\App\http\Controllers\signupControler::class, "store"]);

Route::prefix('dashboard')->middleware([\App\http\Middleware\VerifyTokenMiddleware::class])->group(function () {
    // Mengakses URL: /dashboard
    Route::get("/", [\App\http\Controllers\dashboardControler::class, "get"])->name("dashboard");
    Route::post("/logout", [\App\http\Controllers\dashboardControler::class, "logout"])->name("logout");
});