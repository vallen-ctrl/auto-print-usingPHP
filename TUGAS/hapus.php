<?php
require_once 'database.php';
db::start(); // Memulai koneksi database

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Menggunakan class db untuk menghapus dari tabel ToDo
    db::delete('ToDo', 'id', $id);
    
    // Lempar balik ke halaman utama
    header("Location: index.php?pesan=hapus_sukses");
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>