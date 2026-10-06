<?php
require_once dirname(__DIR__, 1) . "/handler/database.php";
class jamOperasionalControler
{
    public static function validasiJam(string $inputjam): bool
    {
        return (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $inputjam);
    }

    public static function getDayName(int $day): string
    {
        switch ($day) {
            case 1:
                return "senin";
            case 2:
                return "selasa";
            case 3:
                return "rabu";
            case 4:
                return "kamis";
            case 5:
                return "jumat";
            case 6:
                return "sabtu";
            case 7:
                return "minggu";
            default:
                throw new InvalidArgumentException("expected day 1 to 7");
        }
    }

    public static function insertJam(int $idPengaturan, int $hariKe, string $jamBuka, string $jamTutup, bool $apakahBuka)
    {


        if ($hariKe > 7 || $hariKe < 1) {
            throw new InvalidArgumentException("Eror while out of range hari");
        }

        if (self::validasiJam($jamBuka) == false || self::validasiJam($jamTutup) == false) {
            throw new InvalidArgumentException("expected jam with format H:i:s");
        }

        $insertedData = [
            "nomor_hari" => $hariKe,
            "hari" => self::getDayName($hariKe),
            "watu_buka" => $jamBuka,
            "waktu_tutup" => $jamTutup,
            "apakah_dibuka" => $apakahBuka,
            "id_pengaturan" => $idPengaturan
        ];

        return db::insert("jam_operasional", $insertedData);
    }

    public static function update(int $idJam, array $columntoValue)
    {
        if (isset($columntoValue["nomor_hari"])) {
            if ($columntoValue["nomor_hari"] > 7 || $columntoValue["nomor_hari"] < 1) {
                throw new InvalidArgumentException("Eror while out of range hari");
            }

            $columntoValue["hari"] = self::getDayName($columntoValue["nomor_hari"]);
        }

        if (isset($columntoValue["watu_buka"])) {
            if (self::validasiJam($columntoValue["watu_buka"]) == false ) {
                throw new InvalidArgumentException("expected jam with format H:i:s");
            }
        }

        if (isset($columntoValue["watu_tutup"])) {
            if (self::validasiJam($columntoValue["watu_tutup"]) == false ) {
                throw new InvalidArgumentException("expected jam with format H:i:s");
            }
        }

        return db::update("jam_operasional", $columntoValue, "id", $idJam);
    }

    public static function deleteJam(int $id){
        return db::delete("jam_operasional", "id", $id);
    }

    public static function getAllJamOperasional(string $option = "all"){
        return db::get("SELECT * FROM jam_operasional", [], $option);
    }

    public static function getJamByIdPeraturan(string $idPengturan,string $option = "all"){
        return db::getUsingParam("jam_operasional", "id", $idPengturan ,  $option);
    }
}
