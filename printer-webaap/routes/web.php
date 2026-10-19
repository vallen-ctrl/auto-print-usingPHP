<?php

use Illuminate\Support\Facades\Route;


Route::get('/', function (\Illuminate\Http\Request $request) {
    $order = $request->session()->get(\App\Http\Controllers\UploadTerjadwalController::SESSION_KEY);

    // User sudah punya order terjadwal yang belum selesai: lanjutkan ke
    // formulir dengan order id tersebut, bukan memulai dari awal.
    if ($order) {
        switch($order["where"]){
            case "formulir.terjadwal":
                return redirect()->route('formulir.terjadwal', ['order' => $order["orderid"]]);

        }
    }

    return view('beranda');
});

Route::view('/formulir/kilat', 'formulir-kilat')->name('formulir.kilat');

Route::get('/dashboard/admin/login', [\App\Http\Controllers\loginControler::class, "get"])->name('admin.login');
Route::post('/dashboard/admin/login', [\App\Http\Controllers\loginControler::class, "login"]);

Route::get('/dashboard/admin/signup', [\App\Http\Controllers\signupControler::class, "get"])->name('admin.signup');
Route::post('/dashboard/admin/signup', [\App\Http\Controllers\signupControler::class, "store"]);


// alur cetak terjadwal
Route::get('/upload/terjadwal', [\App\Http\Controllers\UploadTerjadwalController::class, 'show'])->name('upload.terjadwal');
Route::post('/upload/terjadwal', [\App\Http\Controllers\UploadTerjadwalController::class, 'store'])->name('upload.terjadwal.store');
Route::get('/formulir/terjadwal', [\App\Http\Controllers\FormulirTerjadwalController::class, 'get'])->name('formulir.terjadwal');

// Keluar dari alur terjadwal: hapus order id di session lalu kembali ke beranda.
Route::get('/terjadwal/keluar', function (\Illuminate\Http\Request $request) {
    $request->session()->forget(\App\Http\Controllers\UploadTerjadwalController::SESSION_KEY);

    return redirect('/');
})->name('terjadwal.keluar');

//alur order
Route::post("/order/create", [\App\Http\Controllers\OrderController::class, 'createorder_terjadwal'])->name("create.order");
Route::post('/order/token', [\App\Http\Controllers\OrderController::class, "bayarSekarang"])->name("create.snap.token");

Route::get("/test", function (){
    return view('snap');
});


//-------------------------------------------------------------------------------------------------------------------------------------
Route::prefix('dashboard')->middleware([\App\Http\Middleware\VerifyTokenMiddleware::class])->group(function () {
    // Mengakses URL: /dashboard
    Route::get('/pendapatan/data', [\App\Http\Controllers\dashboardControler::class, 'pendapatan'])
        ->name('pendapatan.data');
    Route::get('/layanan-kertas/data', [\App\Http\Controllers\dashboardControler::class, 'layananKertas'])
        ->name('layanan-kertas.data');
    Route::get('/tipe-file/data', [\App\Http\Controllers\dashboardControler::class, 'tipeFile'])
        ->name('tipe-file.data');
    Route::get('/order/data', [\App\Http\Controllers\dashboardControler::class, 'order'])
        ->name('order.data');
    Route::get("/", [\App\Http\Controllers\dashboardControler::class, "get"])->name("dashboard");
    Route::post("/logout", [\App\Http\Controllers\dashboardControler::class, "logout"])->name("logout");

    //files
    Route::get('/files', [\App\Http\Controllers\FilesController::class, "get"])->name("files.index");
    Route::get('/files/{id}/download', [\App\Http\Controllers\FilesController::class, "download"])->name("files.download");
    Route::delete('/files/{id}', [\App\Http\Controllers\FilesController::class, "destroy"])->name("files.destroy");

    //order
    Route::get('/order', [\App\Http\Controllers\OrderController::class, "get"])->name("order.index");
    Route::get('/orders/files/{id}', [\App\Http\Controllers\OrderController::class, "detail"])->name("order.detail");
    Route::get('/order/all', [\App\Http\Controllers\OrderController::class, "getorderData"])->name("get.all");
    Route::delete('/order/{id}', [\App\Http\Controllers\OrderController::class, "destroy"])->name("order.destroy");

    //Layanan
    Route::get('/layanan', [\App\Http\Controllers\LayananController::class, "get"])->name("layanan.index");
    Route::post('/layanan/add', [\App\Http\Controllers\LayananController::class, "store"])->name("layanan.post");
    Route::put('/layanan/{id}', [\App\Http\Controllers\LayananController::class, "update"])->name("layanan.perbarui");

    //kertas
    Route::get('/kertas', [\App\Http\Controllers\KertasController::class, "get"])->name("kertas.index");
    Route::post('/kertas/add', [\App\Http\Controllers\KertasController::class,"store"])->name("kertas.store");
    Route::put('/kertas/{id}', [\App\Http\Controllers\KertasController::class, "update"])->name("kertas.perbarui");
    //komputer
    Route::get('/komputer', [\App\Http\Controllers\KomputerController::class, "get"])->name("komputer.index");
    Route::post('/komputer/add', [\App\Http\Controllers\KomputerController::class, "store"])->name("komputer.post");
    Route::get('/komputer/isActive', [\App\Http\Controllers\KomputerController::class, "getStatucisActivebyId"])->name("komputer.status");
    Route::get('/komputer/destroy/{id}', [\App\Http\Controllers\KomputerController::class, "destroy"])->name("komputer.destroy");
    //pengaturan
    Route::get('/pengaturan', [\App\Http\Controllers\PengaturanController::class, "get"])->name("pengaturan.index");
    Route::post('/pengaturan', [\App\Http\Controllers\PengaturanController::class, "store"])->name("pengaturan.store");
});
