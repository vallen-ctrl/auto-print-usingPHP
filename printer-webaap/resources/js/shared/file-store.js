import { pengaturanAwal } from './form-config.js';

// Tanpa server, objek File tidak bisa dibawa antar halaman. Jadi yang disimpan
// hanya nama filenya, supaya halaman formulir tetap bisa menampilkan daftarnya.
export const FILE_STORAGE_KEY = 'printer:file-names';

// Konfigurasi formulir per file (termasuk data file dari server) disimpan di sini.
export const FORM_STORAGE_KEY = 'printer:formulir-terjadwal';

export const bacaNamaFile = () => {
    try {
        const raw = sessionStorage.getItem(FILE_STORAGE_KEY);
        const names = raw ? JSON.parse(raw) : [];

        return Array.isArray(names) ? names : [];
    } catch (error) {
        return [];
    }
};

export const simpanNamaFile = (names) => {
    try {
        sessionStorage.setItem(FILE_STORAGE_KEY, JSON.stringify(names));
    } catch (error) {
        // Penyimpanan browser tidak tersedia: halaman formulir tetap bisa dibuka.
    }
};

// Gabungkan data file dari server (id, name, total_pages) ke konfigurasi
// formulir yang tersimpan, di bawah key yang sama (printer:formulir-terjadwal).
export const gabungFileKeFormulir = (files) => {
    if (!Array.isArray(files) || files.length === 0) {
        return;
    }

    let data = {};

    try {
        const raw = localStorage.getItem(FORM_STORAGE_KEY);
        data = raw ? JSON.parse(raw) : {};
    } catch (error) {
        data = {};
    }

    if (!data || typeof data !== 'object') {
        data = {};
    }

    const daftar = Array.isArray(data.files) ? data.files : [];
    const cariIndex = (nama) => daftar.findIndex((f) => f && f.name === nama);

    files.forEach((file) => {
        const idx = cariIndex(file.name);
        const lama = idx >= 0 ? daftar[idx] : {};

        const entri = {
            ...pengaturanAwal(),
            ...lama,
            id: file.id,
            name: file.name,
            total_pages: file.total_pages,
        };

        if (idx >= 0) {
            daftar[idx] = entri;
        } else {
            daftar.push(entri);
        }
    });

    data.files = daftar;

    if (!data.fileAktif) {
        data.fileAktif = files[0].name;
    }

    try {
        localStorage.setItem(FORM_STORAGE_KEY, JSON.stringify(data));
    } catch (error) {
        // Penyimpanan browser tidak tersedia.
    }
};

// Halaman upload selalu memulai dari nol: konfigurasi formulir & daftar file
// yang tersimpan dibersihkan.
export const bersihkanPenyimpananForm = () => {
    try {
        localStorage.removeItem(FORM_STORAGE_KEY);
        sessionStorage.removeItem(FILE_STORAGE_KEY);
    } catch (error) {
        // Penyimpanan browser tidak tersedia: tidak masalah.
    }
};
