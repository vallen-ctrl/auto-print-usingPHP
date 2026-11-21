<?php

test('halaman upload menampilkan popup error saat ada pesan error dari sesi', function () {
    $this->withoutVite();

    $this->withSession(['error' => 'Order tidak ditemukan. Silakan upload ulang file Anda.'])
        ->get(route('upload.terjadwal'))
        ->assertOk()
        ->assertSee('Gagal')
        ->assertSee('Order tidak ditemukan. Silakan upload ulang file Anda.');
});

test('halaman upload menampilkan popup error untuk kegagalan unggahan', function () {
    $this->withoutVite();

    $this->get(route('upload.terjadwal'))
        ->assertOk()
        ->assertSee('modal-upload-error')
        ->assertSee('Upload Gagal');
});
