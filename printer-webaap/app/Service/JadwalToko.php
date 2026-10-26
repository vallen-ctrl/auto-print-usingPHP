<?php

namespace App\Service;

use App\Models\JamOperasional;
use App\Models\PengaturanToko;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Pengecekan jadwal pengambilan order terhadap pengaturan toko:
 * - pengaturan_toko.status_buka : saklar toko sedang menerima order atau tidak
 * - jam_operasional             : hari apa & jam berapa toko buka
 */
class JadwalToko
{
    /** 1 = Senin ... 7 = Minggu (sama dengan jam_operasional.nomor_hari). */
    public const NAMA_HARI = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];

    /**
     * Cek `tanggal_ambil` dari request (ISO 8601, contoh: 2026-10-29T14:30:00+07:00).
     *
     * Jam yang dipakai adalah jam lokal pada string yang dikirim browser (yang juga
     * tampil di formulir), bukan dikonversi ke timezone server.
     *
     * @return string|null pesan penolakan, atau null kalau jadwal boleh dipakai
     */
    public function cek(PengaturanToko $pengaturan, string $tanggalAmbil): ?string
    {
        $waktu = $this->parse($tanggalAmbil);

        if ($waktu === null) {
            return 'Jadwal pengambilan tidak dikenali. Silakan pilih tanggal dan pukul lagi.';
        }

        // status_buka null = belum pernah diatur, anggap buka supaya order tidak ikut terkunci
        if ($pengaturan->status_buka !== null && ! $pengaturan->status_buka) {
            return 'Toko sedang tutup. Permintaan order ditolak, silakan coba lagi saat toko buka.';
        }

        $jadwal = JamOperasional::where('id_pengaturan', $pengaturan->id)->get();

        // jam operasional belum diatur sama sekali: cukup ikuti status_buka
        if ($jadwal->isEmpty()) {
            return null;
        }

        return $this->cocokJadwal($jadwal, $waktu);
    }

    /**
     * Cocokkan waktu pengambilan dengan jam operasional yang tersimpan.
     *
     * @param  Collection<int, JamOperasional>  $jadwal  semua baris jam_operasional milik toko
     * @return string|null pesan penolakan, atau null kalau sesuai jadwal
     */
    public function cocokJadwal(Collection $jadwal, CarbonInterface $waktu): ?string
    {
        $nomorHari = $waktu->dayOfWeekIso;
        $namaHari = self::NAMA_HARI[$nomorHari] ?? $waktu->translatedFormat('l');
        $hari = $jadwal->firstWhere('nomor_hari', $nomorHari);

        if ($hari === null) {
            return "Toko tidak buka pada hari {$namaHari}. Silakan pilih hari lain.";
        }

        if (! $hari->apakah_dibuka) {
            return "Toko tutup pada hari {$namaHari}. Silakan pilih hari lain.";
        }

        $buka = $this->jamKeDetik($hari->waktu_buka);
        $tutup = $this->jamKeDetik($hari->waktu_tutup);
        $diminta = $waktu->hour * 3600 + $waktu->minute * 60 + $waktu->second;

        if (! $this->didalamJamOperasional($buka, $tutup, $diminta)) {
            return "Pukul {$waktu->format('H:i')} di luar jam operasional toko hari {$namaHari} "
                . "({$this->rentangJam($hari->waktu_buka, $hari->waktu_tutup)}).";
        }

        return null;
    }

    /**
     * null = sisi itu tidak dibatasi. Jam tutup lebih kecil dari jam buka berarti
     * lewat tengah malam (contoh 20:00 - 02:00). Jam buka sama dengan jam tutup = buka 24 jam.
     */
    private function didalamJamOperasional(?int $buka, ?int $tutup, int $diminta): bool
    {
        if ($buka === null && $tutup === null) {
            return true;
        }

        if ($buka === null) {
            return $diminta <= $tutup;
        }

        if ($tutup === null) {
            return $diminta >= $buka;
        }

        if ($buka === $tutup) {
            return true;
        }

        if ($buka < $tutup) {
            return $diminta >= $buka && $diminta <= $tutup;
        }

        return $diminta >= $buka || $diminta <= $tutup;
    }

    /** "08:00:00" -> 28800 */
    private function jamKeDetik(?string $jam): ?int
    {
        if ($jam === null || trim($jam) === '') {
            return null;
        }

        $bagian = array_map('intval', explode(':', $jam));

        if (count($bagian) < 2) {
            return null;
        }

        return ($bagian[0] ?? 0) * 3600 + ($bagian[1] ?? 0) * 60 + ($bagian[2] ?? 0);
    }

    private function rentangJam(?string $buka, ?string $tutup): string
    {
        $awal = $buka !== null && $buka !== '' ? substr($buka, 0, 5) : 'tanpa batas';
        $akhir = $tutup !== null && $tutup !== '' ? substr($tutup, 0, 5) : 'tanpa batas';

        return "{$awal} - {$akhir}";
    }

    private function parse(string $tanggalAmbil): ?CarbonInterface
    {
        try {
            $waktu = Carbon::createFromFormat('Y-m-d\TH:i:sP', trim($tanggalAmbil));
        } catch (\Throwable) {
            return null;
        }

        return $waktu instanceof CarbonInterface ? $waktu : null;
    }
}
