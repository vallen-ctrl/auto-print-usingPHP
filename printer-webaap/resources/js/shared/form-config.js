// Nilai awal pengaturan cetak untuk satu file. Dipakai halaman upload (saat
// menggabungkan data file dari server) dan halaman formulir.
export const pengaturanAwal = () => ({
    orientasi: 'portrait',
    ukuran: 'a4',
    warna: 'hitam-putih',
    kualitas: 'standar',
    jumlah: '1',
    halaman: '',
    catatan: '',
    paket: [],
});
