<?php

use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('beranda');
});

Route::view('/formulir/kilat', 'formulir-kilat')->name('formulir.kilat');

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
    Route::get('/orders/files/{id}', [\App\Http\Controllers\OrderController::class, "detail"])->name("order.detail");
    Route::get('/order/all', [\App\http\Controllers\OrderController::class, "getorderData"])->name("get.all");
    //Layanan
    Route::get('/layanan', [\App\Http\Controllers\LayananController::class, "get"])->name("layanan.index");
    Route::post('/layanan/add', [\App\Http\Controllers\LayananController::class, "store"])->name("layanan.post");

    //kertas
    Route::get('/kertas', [\App\Http\Controllers\KertasController::class, "get"])->name("kertas.index");
    Route::post('/kertas/add', [\App\http\Controllers\KertasController::class,"store"])->name("kertas.store");
    //komputer
    Route::get('/komputer', [\App\Http\Controllers\KomputerController::class, "get"])->name("komputer.index");
    Route::post('/komputer/add', [\App\http\Controllers\KomputerController::class, "store"])->name("komputer.post");
    Route::get('/komputer/isActive', [\App\http\Controllers\KomputerController::class, "getStatucisActivebyId"])->name("komputer.status");
    Route::get('/komputer/destroy/{id}', [\App\http\Controllers\KomputerController::class, "destroy"])->name("komputer.destroy");
    //pengaturan
    Route::get('/pengaturan', [\App\Http\Controllers\PengaturanController::class, "get"])->name("pengaturan.index");
    Route::post('/pengaturan', [\App\Http\Controllers\PengaturanController::class, "store"])->name("pengaturan.store");
});
