<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\VerifyTokenMiddleware;

// Route::middleware(['api', VerifyTokenMiddleware::class])->group(function () {
//     // Paper
//     Route::apiResource('paper', \App\Http\Controllers\Api\PaperController::class)->only(['index', 'store', 'update', 'destroy']);

//     // computer
//     Route::post('/computer', [\App\Http\Controllers\Api\ComputerController::class, 'store']);
//     Route::get('/computer', [\App\Http\Controllers\Api\ComputerController::class, 'index']);
//     Route::delete('/computer/{id}', [\App\Http\Controllers\Api\ComputerController::class, 'destroy']);

//     //pengaturan toko
//     // Route::post('/pengaturan-toko', [\App\Http\Controllers\Api\PengaturanTokoController::class, 'store']);
//     Route::put("/pengaturan-toko", [\App\Http\Controllers\Api\PengaturanTokoController::class, 'update']);

//     //jamOperasional
//     Route::post('/jam-operasional', [\App\Http\Controllers\Api\JamOperasionalController::class, 'store']);
//     Route::put('/jam-operasional/{id}', [\App\Http\Controllers\Api\JamOperasionalController::class, 'update']);
//     Route::delete('/jam-operasional/{id}', [\App\Http\Controllers\Api\JamOperasionalController::class, 'destroy']);

//     //metode pembayaran
//     // Kalau pendaftaran midtrans berhasil maka tidak akan menggunakan fitur ini
//     Route::apiResource('metode-pembayaran', \App\Http\Controllers\Api\MetodePembayaranController::class)->only(['store', 'update', 'destroy']);
//     Route::get("/metode-pembayaran", [\App\http\Controllers\Api\MetodePembayaranController::class, 'show']);

//     // Layanan
//     Route::apiResource('layanan', \App\Http\Controllers\Api\LayananController::class)->only(['store', 'update', 'destroy']);

//     // Layanan Terpilih
//     //bagian ini ga dibutuhkan karena bagian ini harus dikelola mandiri oleh sistem 
//     //bisa saja berubah tergantung oleh update aplikasi nantinya
//     // Route::post('/layanan-terpilih', [\App\Http\Controllers\Api\LayananTerpilihController::class, 'store']);
//     // Route::delete('/layanan-terpilih/{id}', [\App\Http\Controllers\Api\LayananTerpilihController::class, 'destroy']);
//     // Route::get('/layanan-terpilih', [\App\Http\Controllers\Api\LayananTerpilihController::class, 'show']);

// });

// Route::get('/trial', [\App\Http\Controllers\Api\UserController::class, 'tiral']);

Route::middleware('api')->group(function () {
    // Computer
    Route::post('/computer/SSE', [\App\Http\Controllers\Api\ComputerController::class, 'serverSendEvent']);
    // Route::get('/computer/token', [\App\Http\Controllers\Api\ComputerController::class, 'getByToken']);
    // Route::get('/computer/{id}', [\App\Http\Controllers\Api\ComputerController::class, 'getById']);
    // User
    // Route::post('/user', [\App\Http\Controllers\Api\UserController::class, 'store']);

    // //layanan
    // Route::get("/layanan", [\App\Http\Controllers\Api\LayananController::class, "index"]);

    // // Toko
    // Route::post('/toko', [\App\Http\Controllers\Api\TokoController::class, 'store']);
    // Route::put('/toko/{id}', [\App\Http\Controllers\Api\TokoController::class, 'update']);
    // // Route::get('/toko/max', [\App\Http\Controllers\Api\TokoController::class, 'checkMax']);
    // Route::get('/toko/info', [\App\Http\Controllers\Api\TokoController::class, 'info']);
    // // Route::get('/toko/ids', [\App\Http\Controllers\Api\TokoController::class, 'ids']);
    // // Route::post('/toko/{id}/token', [\App\Http\Controllers\Api\TokoController::class, 'setToken']);
    // Route::post('/toko/login', [\App\Http\Controllers\Api\TokoController::class, 'login']);
    // // Route::post('/toko/validate-token', [\App\Http\Controllers\Api\TokoController::class, 'validateToken']);

    // // Paper
    // Route::get('/paper', [\App\Http\Controllers\Api\PaperController::class, "get"]);


    // Pengaturan Toko
    // Route::get('/pengaturan-toko/toko/{idToko}', [\App\Http\Controllers\Api\PengaturanTokoController::class, 'getByTokoId']);
    // Route::get('/pengaturan-toko/biaya', [\App\Http\Controllers\Api\PengaturanTokoController::class, 'getBiaya']);
    // // Route::delete('/pengaturan-toko/{id}', [\App\Http\Controllers\Api\PengaturanTokoController::class, 'destroy']);

    // // Jam Operasional
    // Route::get('/jam-operasional', [\App\Http\Controllers\Api\JamOperasionalController::class, 'index']);
    // // Route::get('/jam-operasional/pengaturan/{idPengaturan}', [\App\Http\Controllers\Api\JamOperasionalController::class, 'getByPengaturan']);

    // // Metode Pembayaran
    // // Kalau pendaftaran midtrans berhasil maka tidak akan menggunakan metode ini
    // Route::get('/metode-pembayaran-user', [\App\Http\Controllers\Api\MetodePembayaranController::class, 'index']);
    // // Kalau pendaftaran midtrans berhasil maka tidak akan menggunakan metode ini

    // // File
    // // Route::put('/file/{fileId}/uploader', [\App\Http\Controllers\Api\FileController::class, 'updateUploader']);
    // // Route::put('/file/{fileId}/subtotal', [\App\Http\Controllers\Api\FileController::class, 'updateSubtotal']);
    // // Route::delete('/file/{fileId}', [\App\Http\Controllers\Api\FileController::class, 'destroy']);
    // Route::post('/file', [\App\Http\Controllers\Api\FileController::class, 'store']);
    // Route::get('/file/{orderid}', [\App\Http\Controllers\Api\FileController::class, 'show']);
    // Route::get("/pages", [\App\http\Controllers\Api\FileController::class, "getManyPaper"]);
    // // Order
    // Route::post('/order', [\App\Http\Controllers\Api\OrderController::class, 'store']);
    // Route::put('/order/{id}/total-dibayar', [\App\Http\Controllers\Api\OrderController::class, 'updateTotalDibayar']);
    // Route::put('/order/{id}/metode-pembayaran', [\App\Http\Controllers\Api\OrderController::class, 'updateMetodePembayaran']);
    // Route::put('/order/{id}/status-pembayaran', [\App\Http\Controllers\Api\OrderController::class, 'updateStatusPembayaran']);


    
    // Configuration
    // bagian ini tidak diperlukan dikarenakan natninya akan diupload bersama dengan order
    // karena tabel oreder akan terbuat setelah user mengisi semua formulir
    // Route::post('/configuration', [\App\Http\Controllers\Api\ConfigurationController::class, 'store']);
    // Route::put('/configuration/{id}', [\App\Http\Controllers\Api\ConfigurationController::class, 'update']);
    // Route::delete('/configuration/{id}', [\App\Http\Controllers\Api\ConfigurationController::class, 'destroy']);
    // Route::get('/configuration/file/{fileId}', [\App\Http\Controllers\Api\ConfigurationController::class, 'showByFileId']);
    // Route::get('/configuration/{id}', [\App\Http\Controllers\Api\ConfigurationController::class, 'showById']);

    // File Queue
    // Bagian ini nunggu kabar
    // Route::apiResource('file-queue', \App\Http\Controllers\Api\FileQueueController::class)->only(['index', 'store', 'update', 'destroy']);
    // Route::get('/file-queue/computer/{computerId}', [\App\Http\Controllers\Api\FileQueueController::class, 'getByComputerId']);
});
