<?php
    require_once dirname(__DIR__) . "/handler/session.php";
    require_once dirname(__DIR__) . "/handler/validation.php";

    class FormulirCetakControler {
        public static function handleSumbit(array $POST){
            $data = validator::input([
                "nama","no_wa",
                "tanggal_ambil", "orientasi",
                "warna", "ukuran",
                "kualitas", "skala",
                "jumlah_cetak", "custom_halaman",
                ], $POST);

            $rules =[
                "nama" => "required",
                "no_wa" => "required|min:10|max:14",
                "tanggal_ambil" => "required|date",
                "orientasi" => "required",
                "warna" => "required",
                "ukuran" => "required",
                "kualitas" => "required",
            ];
            
            $error = validator::validate($data, $rules);

            $result = [
                "error" => $error,
                "data" => $data
            ];
            
            return $result;
        }
    }
?>