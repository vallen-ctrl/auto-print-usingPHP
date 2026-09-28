<?php
require_once dirname(__DIR__, 3) . '/Controler/formulirCetakControler.php';

$old = [];
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $res = FormulirCetakControler::handleSumbit($_POST);
    $old = $res["data"];
    $errors = $res["error"];

    if (empty($errors)) {
        // TODO: simpan / proses data di sini
        header("Location: " . $_SERVER["REQUEST_URI"]);
        exit;
    }
}


function old(mixed $key)
{
    global $old;
    return $old[$key] ?? '';
}

function checked(mixed $key, mixed $val)
{
    global $old;
    return (($old[$key] ?? '') === $val) ? 'checked' : '';
}

function err(mixed $key)
{
    global $errors;
    return isset($errors[$key])
        ? '<small style="color:red">' . $errors[$key] . '</small>'
        : '';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Cetak</title>
</head>

<body style="display:flex; width:99vw; justify-content:center">
    <form method="POST" style="display:flex; flex-direction:column; width:50%">
        <label for="nama">Nama lengkap</label>
        <input type="text" id="nama" name="nama" value="<?= old('nama') ?>">
        <?= err('nama') ?>

        <label for="no_wa">No WA</label>
        <input type="text" id="no_wa" name="no_wa" value="<?= old('no_wa') ?>">
        <?= err('no_wa') ?>

        <label for="tanggal_ambil">Tanggal ambil</label>
        <input type="date" id="tanggal_ambil" name="tanggal_ambil" value="<?= old('tanggal_ambil') ?>">
        <?= err('tanggal_ambil') ?>

        <label>Orientasi</label>
        <label><input type="radio" name="orientasi" value="landscape" <?= checked('orientasi', 'landscape') ?>> Landscape</label>
        <label><input type="radio" name="orientasi" value="portrait" <?= checked('orientasi', 'portrait') ?>> Portrait</label>
        <?= err('orientasi') ?>

        <label>Warna</label>
        <label><input type="radio" name="warna" value="grayscale" <?= checked('warna', 'grayscale') ?>> Grayscale</label>
        <label><input type="radio" name="warna" value="color" <?= checked('warna', 'color') ?>> Color</label>
        <?= err('warna') ?>

        <label>Ukuran</label>
        <label><input type="radio" name="ukuran" value="a4" <?= checked('ukuran', 'a4') ?>> A4</label>
        <label><input type="radio" name="ukuran" value="f4" <?= checked('ukuran', 'f4') ?>> F4</label>
        <?= err('ukuran') ?>

        <label>Kualitas</label>
        <label><input type="radio" name="kualitas" value="low" <?= checked('kualitas', 'low') ?>> Low</label>
        <label><input type="radio" name="kualitas" value="medium" <?= checked('kualitas', 'medium') ?>> Medium</label>
        <label><input type="radio" name="kualitas" value="high" <?= checked('kualitas', 'high') ?>> High</label>
        <?= err('kualitas') ?>

        <label for="skala">Skala</label>
        <input type="text" id="skala" name="skala" value="<?= old('skala') ?>">
        <?= err('skala') ?>

        <label for="jumlah_cetak">Jumlah cetak</label>
        <input type="number" id="jumlah_cetak" name="jumlah_cetak" value="<?= old('jumlah_cetak') ?>">
        <?= err('jumlah_cetak') ?>

        <label for="custom_halaman">Custom halaman</label>
        <input type="text" id="custom_halaman" name="custom_halaman" value="<?= old('custom_halaman') ?>">
        <?= err('custom_halaman') ?>

        <button type="submit">clickme</button>
    </form>
</body>

</html>