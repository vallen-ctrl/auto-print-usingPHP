<?php

use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('beranda');
});

Route::get('/dashboard/admin/login', [\App\http\Controllers\loginControler::class, "get"])->name('admin.login');
Route::post('/dashboard/admin/login', [\App\http\Controllers\loginControler::class, "login"]);

Route::get('/dashboard/admin/signup', [\App\http\Controllers\signupControler::class, "get"]);
Route::post('/dashboard/admin/signup', [\App\http\Controllers\signupControler::class, "store"]);


// alur cetak terjadwal
Route::view('/upload/terjadwal', 'upload-terjadwal')->name('upload.terjadwal');
Route::view('/formulir/terjadwal', 'formulir-terjadwal')->name('formulir.terjadwal');

//-------------------------------------------------------------------------------------------------------------------------------------
Route::prefix('dashboard')->middleware([\App\http\Middleware\VerifyTokenMiddleware::class])->group(function () {
    // Mengakses URL: /dashboard
    Route::get('/pendapatan/data', [\App\http\Controllers\dashboardControler::class, 'pendapatan'])
        ->name('pendapatan.data');
    Route::get('/layanan-kertas/data', [\App\http\Controllers\dashboardControler::class, 'layananKertas'])
        ->name('layanan-kertas.data');
    Route::get('/tipe-file/data', [\App\http\Controllers\dashboardControler::class, 'tipeFile'])
        ->name('tipe-file.data');
    Route::get('/order/data', [\App\http\Controllers\dashboardControler::class, 'order'])
        ->name('order.data');
    Route::get("/", [\App\http\Controllers\dashboardControler::class, "get"])->name("dashboard");
    Route::post("/logout", [\App\http\Controllers\dashboardControler::class, "logout"])->name("logout");

    //files
    Route::get('/files', [\App\http\Controllers\FilesController::class, "get"])->name("files.index");

    //order
    Route::get('/order', [\App\Http\Controllers\OrderController::class, "get"])->name("order.index");

    //Layanan
    Route::get('/layanan', [\App\Http\Controllers\LayananController::class, "get"])->name("layanan.index");

    //kertas
    Route::get('/kertas', [\App\Http\Controllers\KertasController::class, "get"])->name("kertas.index");
    
    //komputer
    Route::get('/komputer', [\App\Http\Controllers\KomputerController::class, "get"])->name("komputer.index");
    
    //pengaturan
    Route::get('/pengaturan', [\App\Http\Controllers\PengaturanController::class, "get"])->name("pengaturan.index");
});
