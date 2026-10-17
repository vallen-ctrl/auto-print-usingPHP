import 'wired-elements/lib/wired-card.js';
import 'wired-elements/lib/wired-button.js';
import 'wired-elements/lib/wired-input.js';
import 'wired-elements/lib/wired-radio.js';
import 'wired-elements/lib/wired-radio-group.js';
import 'wired-elements/lib/wired-checkbox.js';
import 'wired-elements/lib/wired-combo.js';
import '../wired/item-bugfix.js';
import '../wired/sketch.js';
import '../wired/input-label.js';
import '../shared/navigate.js';
import '../shared/back-button.js';
import { FORM_STORAGE_KEY, bacaNamaFile } from '../shared/file-store.js';
import { pengaturanAwal } from '../shared/form-config.js';

/* ---------- Pilih file & pengaturannya di halaman formulir ---------- */

// Pengaturan cetak (orientasi, ukuran, dst.) disimpan per file, dan file yang
// sedang diatur dipilih dari dropdown di bagian "File".
const fileCombo = document.getElementById('pilih-file');
const fileKosong = document.getElementById('file-kosong');
const kolomPengaturan = {
    orientasi: document.getElementById('grup-orientasi'),
    ukuran: document.getElementById('grup-ukuran'),
    warna: document.getElementById('grup-warna'),
    kualitas: document.getElementById('grup-kualitas'),
    jumlah: document.getElementById('jumlah'),
    halaman: document.getElementById('halaman'),
    catatan: document.getElementById('catatan'),
};

const fieldPesanan = {
    nama: document.getElementById('nama'),
    whatsapp: document.getElementById('whatsapp'),
    tanggal: document.getElementById('tanggal'),
    pukul: document.getElementById('pukul'),
};

if (fileCombo && fileKosong && kolomPengaturan.orientasi) {
    const paketChecks = Array.from(document.querySelectorAll('.paket-check'));
    const subTotal = document.getElementById('sub-total');

    // Data pesanan (tidak per file): nama, whatsapp, tanggal & pukul ambil.



    const tulisPesanan = (data) => {
        if (!data) {
            return;
        }

        Object.keys(fieldPesanan).forEach((key) => {
            const el = fieldPesanan[key];

            if (el && typeof data[key] === 'string' && data[key] !== '') {
                el.value = data[key];
            }
        });
    };

    // Baca/tulis konfigurasi seluruh file ke localStorage supaya pilihan tetap
    // ada saat halaman dimuat ulang.
    const bacaKonfigurasi = () => {
        try {
            const raw = localStorage.getItem(FORM_STORAGE_KEY);
            return raw ? JSON.parse(raw) : {};
        } catch (error) {
            return {};
        }
    };

    const bacaPesanan = () => ({
        nama: fieldPesanan.nama ? fieldPesanan.nama.value : '',
        whatsapp: fieldPesanan.whatsapp ? fieldPesanan.whatsapp.value : '',
        tanggal: fieldPesanan.tanggal ? fieldPesanan.tanggal.value : '',
        pukul: fieldPesanan.pukul ? fieldPesanan.pukul.value : '',
    });


    const tulisKonfigurasi = () => {
        try {
            const pesananSekarang = bacaPesanan();
            const konfigurasiLama = bacaKonfigurasi(); // Ambil data lama

            localStorage.setItem(FORM_STORAGE_KEY, JSON.stringify({
                fileAktif: fileAktif,
                // Jika input kosong, gunakan data lama. Jika ada isinya, gunakan yang baru.
                nama: pesananSekarang.nama || konfigurasiLama.nama || '',
                whatsapp: pesananSekarang.whatsapp || konfigurasiLama.whatsapp || '',
                tanggal: pesananSekarang.tanggal || konfigurasiLama.tanggal || '',
                pukul: pesananSekarang.pukul || konfigurasiLama.pukul || '',
                files: Array.from(pengaturanPerFile, ([name, state]) => ({ ...state, name }))
            }));
        } catch (error) {
            // Penyimpanan browser tidak tersedia
        }
    };

    const tersimpan = bacaKonfigurasi();
    const tersimpanFiles = Array.isArray(tersimpan.files) ? tersimpan.files : [];
    const simpanan = {};
    tersimpanFiles.forEach((f) => {
        if (f && f.name) {
            simpanan[f.name] = f;
        }
    });

    // Daftar file diutamakan dari localStorage (printer:formulir-terjadwal) supaya
    // tetap ada saat reload; sessionStorage hanya cadangan.
    const namaFile = tersimpanFiles.length > 0
        ? tersimpanFiles.map((f) => f.name)
        : bacaNamaFile();

    const pengaturanPerFile = new Map();
    let fileAktif = null;

    // Nama panjang dipendekkan di dropdown supaya kotaknya tidak melebar;
    // nama lengkapnya tetap ada di tooltip.
    const potongNama = (name) => (name.length > 26 ? name.slice(0, 23) + '…' : name);

    // Ubah input halaman (mis. "1,4-7") jadi daftar nomor halaman, mengikuti
    // logika App\Rules\CustomHalamanRule::toArray().
    const halamanToArray = (input) => {
        const isi = String(input || '').replace(/\s/g, '');

        if (isi === '') {
            return [];
        }

        const semua = [];

        isi.split(',').forEach((part) => {
            if (part === '') {
                return;
            }

            if (part.includes('-')) {
                let [start, end] = part.split('-').map((n) => parseInt(n, 10));

                if (Number.isNaN(start) || Number.isNaN(end)) {
                    return;
                }

                if (start > end) {
                    [start, end] = [end, start];
                }

                for (let i = start; i <= end; i++) {
                    semua.push(i);
                }
            } else {
                const n = parseInt(part, 10);

                if (!Number.isNaN(n)) {
                    semua.push(n);
                }
            }
        });

        return [...new Set(semua)].sort((a, b) => a - b);
    };

    // Jumlah halaman yang dicetak: semua halaman kalau custom halaman kosong,
    // atau banyaknya halaman yang dipilih dan memang ada di file itu.
    const hitungJumlahHalaman = (totalPages, custom) => {
        const total = Number(totalPages) || 0;
        const isi = String(custom || '').replace(/\s/g, '');

        if (isi === '') {
            return total;
        }

        const pages = halamanToArray(isi);

        if (pages.length === 0) {
            return total;
        }

        return pages.filter((p) => p >= 1 && p <= total).length;
    };

    // Cari <wired-radio> ukuran berdasarkan nama (abaikan besar/kecil huruf).
    const cariRadioUkuran = (value) => {
        const grup = kolomPengaturan.ukuran;

        if (!grup) {
            return null;
        }

        const radios = Array.from(grup.querySelectorAll('wired-radio'));

        return radios.find(
            (el) => (el.getAttribute('name') || '').toLowerCase() === String(value).toLowerCase()
        ) || null;
    };

    // Ukuran default: pilihan pertama dari daftar paper.
    const ukuranPertama = () => {
        const grup = kolomPengaturan.ukuran;
        const radio = grup ? grup.querySelector('wired-radio') : null;

        return radio ? radio.getAttribute('name') : '';
    };

    // Pastikan nilai ukuran cocok dengan salah satu pilihan paper.
    const pilihUkuran = (value) => {
        const grup = kolomPengaturan.ukuran;

        if (!grup) {
            return;
        }

        const cocok = cariRadioUkuran(value);

        grup.selected = cocok ? cocok.getAttribute('name') : ukuranPertama();
    };

    // Harga per halaman kertas terpilih (berwarna → harga_color,
    // hitam putih → harga_grayscale).
    const hargaKertasTerpilih = () => {
        const ukuran = kolomPengaturan.ukuran ? kolomPengaturan.ukuran.selected : '';
        const radio = cariRadioUkuran(ukuran);

        if (!radio) {
            return 0;
        }

        const warna = kolomPengaturan.warna.selected;

        return warna === 'berwarna'
            ? Number(radio.dataset.hargaColor) || 0
            : Number(radio.dataset.hargaGrayscale) || 0;
    };

    // Tampilkan harga per halaman di pilihan warna, sesuai ukuran (paper) yang dipilih.
    const perbaruiHargaWarna = () => {
        const radio = cariRadioUkuran(kolomPengaturan.ukuran ? kolomPengaturan.ukuran.selected : '');
        const color = radio ? Number(radio.dataset.hargaColor) || 0 : 0;
        const grayscale = radio ? Number(radio.dataset.hargaGrayscale) || 0 : 0;

        document.querySelectorAll('.warna-harga').forEach((el) => {
            const nilai = el.dataset.hargaUntuk === 'berwarna' ? color : grayscale;
            el.textContent = ' (Rp. ' + nilai.toLocaleString('id-ID') + ')';
        });
    };

    // Biaya kertas file aktif: harga halaman × jumlah halaman cetak × jumlah cetak.
    const hitungBiayaKertas = () => {
        const state = fileAktif ? (pengaturanPerFile.get(fileAktif) || {}) : {};
        const jumlah = Number(kolomPengaturan.jumlah.value) || 1;
        const halaman = hitungJumlahHalaman(state.total_pages, kolomPengaturan.halaman.value);

        return hargaKertasTerpilih() * halaman * jumlah;
    };

    // Sub total = biaya kertas file aktif + paket yang dicentang (file aktif).
    const hitungSubTotal = () => {
        perbaruiHargaWarna();

        let paketTotal = 0;

        paketChecks.forEach((box) => {
            if (box.checked) {
                paketTotal += Number(box.dataset.harga) || 0;
            }
        });

        const total = hitungBiayaKertas() + paketTotal;

        if (subTotal) {
            subTotal.textContent = total.toLocaleString('id-ID');
        }
    };

    const bacaPengaturan = () => ({
        orientasi: kolomPengaturan.orientasi.selected,
        ukuran: kolomPengaturan.ukuran.selected,
        warna: kolomPengaturan.warna.selected,
        kualitas: kolomPengaturan.kualitas.selected,
        jumlah: kolomPengaturan.jumlah.value,
        halaman: kolomPengaturan.halaman.value,
        catatan: kolomPengaturan.catatan.value,
        id_paper: cariRadioUkuran(kolomPengaturan.ukuran.selected).id,
        paket: paketChecks.filter((box) => box.checked).map((box) => box.dataset.id)
    });

    const tulisPengaturan = (state) => {
        kolomPengaturan.orientasi.selected = state.orientasi;
        pilihUkuran(state.ukuran);
        kolomPengaturan.warna.selected = state.warna;
        kolomPengaturan.kualitas.selected = state.kualitas;
        kolomPengaturan.jumlah.value = state.jumlah;
        kolomPengaturan.halaman.value = state.halaman;
        kolomPengaturan.catatan.value = state.catatan;

        // Pilihan paket (layanan) juga melekat per file.
        const dipilih = state.paket || [];

        paketChecks.forEach((box) => {
            box.checked = dipilih.includes(box.dataset.id);
        });

        hitungSubTotal();
    };

    const simpanFileAktif = () => {
        if (fileAktif) {
            // Pertahankan data yang bukan bagian form (mis. id & total_pages).
            const lama = pengaturanPerFile.get(fileAktif) || {};
            pengaturanPerFile.set(fileAktif, { ...lama, ...bacaPengaturan() });
            tulisKonfigurasi();
        }
    };

    namaFile.forEach((name) => {
        const item = document.createElement('wired-item');
        
        item.setAttribute('value', name);
        item.textContent = potongNama(name);
        item.title = name;
        fileCombo.append(item);

        // Gabungkan konfigurasi tersimpan (termasuk id & total_pages dari server)
        // dengan nilai awal, supaya kolom yang belum tersimpan tetap punya default.
        pengaturanPerFile.set(name, { ...pengaturanAwal(), ...(simpanan[name] || {}) });
    });

    if (namaFile.length > 0) {
        fileAktif = (tersimpan.fileAktif && namaFile.includes(tersimpan.fileAktif))
            ? tersimpan.fileAktif
            : namaFile[0];
        fileCombo.setAttribute('selected', fileAktif);
    }

    fileCombo.hidden = namaFile.length === 0;
    fileKosong.hidden = namaFile.length > 0;

    fileCombo.addEventListener('selected', (event) => {
        const nama = event.detail && event.detail.selected;

        if (!nama || nama === fileAktif) {
            return;
        }

        simpanFileAktif();
        fileAktif = nama;

        const state = pengaturanPerFile.get(nama) || pengaturanAwal();
        pengaturanPerFile.set(nama, state);
        tulisPengaturan(state);
        tulisKonfigurasi();
    });

    // Perubahan pengaturan disimpan ke file aktif & sub total dihitung ulang.
    ['orientasi', 'ukuran', 'warna', 'kualitas'].forEach((key) => {
        kolomPengaturan[key].addEventListener('selected', () => {
            simpanFileAktif();
            hitungSubTotal();
        });
    });

    ['jumlah', 'halaman', 'catatan'].forEach((key) => {
        ['change', 'input'].forEach((jenis) => {
            kolomPengaturan[key].addEventListener(jenis, () => {
                simpanFileAktif();
                hitungSubTotal();
            });
        });
    });

    // Mencentang paket menyimpan pilihan itu ke file yang sedang aktif.
    paketChecks.forEach((box) => {
        box.addEventListener('change', () => {
            simpanFileAktif();
            hitungSubTotal();
        });
    });

    // Seluruh baris paket bisa diklik supaya area sentuhnya lebih lega.
    document.querySelectorAll('.paket-item').forEach((item) => {
        item.addEventListener('click', (event) => {
            if (event.target.closest('wired-checkbox')) {
                return;
            }

            const box = item.querySelector('.paket-check');

            if (box) {
                box.checked = !box.checked;
                simpanFileAktif();
                hitungSubTotal();
            }
        });
    });

    // Data pesanan (nama, whatsapp, tanggal, pukul) disimpan setiap kali berubah.
    Object.values(fieldPesanan).forEach((el) => {
        if (!el) {
            return;
        }

        ['change', 'input'].forEach((jenis) => {
            el.addEventListener(jenis, tulisKonfigurasi);
        });
    });

    // Pulihkan data pesanan dari localStorage sebelum menyimpan ulang state.
    tulisPesanan(tersimpan);

    // Terapkan state file aktif (termasuk paket) lalu hitung sub totalnya.
    if (fileAktif) {
        tulisPengaturan(pengaturanPerFile.get(fileAktif));
        tulisKonfigurasi();
    } else {
        hitungSubTotal();
    }
}

/* ---------- Validasi isi formulir ---------- */

const inputWhatsapp = document.getElementById('whatsapp');
const inputTanggal = document.getElementById('tanggal');
const inputPukul = document.getElementById('pukul');
const inputHalaman = document.getElementById('halaman');

const hariIni = () => {
    const sekarang = new Date();
    const bulan = String(sekarang.getMonth() + 1).padStart(2, '0');
    const tanggal = String(sekarang.getDate()).padStart(2, '0');

    return sekarang.getFullYear() + '-' + bulan + '-' + tanggal;
};

const jamSekarang = () => {
    const sekarang = new Date();

    return String(sekarang.getHours()).padStart(2, '0') + ':' + String(sekarang.getMinutes()).padStart(2, '0');
};

// Pesan tampil di bawah kolomnya; pesan kosong berarti kolom itu valid.
const tandaiKolom = (input, pesan) => {
    const errorEl = document.getElementById('error-' + input.id);

    if (errorEl) {
        errorEl.textContent = pesan || '';
        errorEl.hidden = !pesan;
    }

    const inner = input.shadowRoot ? input.shadowRoot.querySelector('input') : null;

    if (inner) {
        if (pesan) {
            inner.setAttribute('aria-invalid', 'true');
        } else {
            inner.removeAttribute('aria-invalid');
        }
    }

    return pesan === '';
};

const periksaWhatsapp = () => {
    const isi = (inputWhatsapp.value || '').trim();
    const angka = isi.replace(/[\s\-().]/g, '');

    if (isi === '') {
        return tandaiKolom(inputWhatsapp, 'Nomor whatsapp wajib diisi.');
    }

    if (!/^\+?\d+$/.test(angka)) {
        return tandaiKolom(inputWhatsapp, 'Nomor whatsapp hanya boleh berisi angka.');
    }

    if (!/^(\+?62|0)8\d{7,12}$/.test(angka)) {
        return tandaiKolom(inputWhatsapp, 'Nomor whatsapp tidak valid. Contoh: 08123456789.');
    }

    return tandaiKolom(inputWhatsapp, '');
};

const periksaTanggal = () => {
    const isi = inputTanggal.value || '';

    if (isi === '') {
        return tandaiKolom(inputTanggal, 'Tanggal pengambilan wajib diisi.');
    }

    if (isi < hariIni()) {
        return tandaiKolom(inputTanggal, 'Tanggal tidak boleh sebelum hari ini.');
    }

    return tandaiKolom(inputTanggal, '');
};

const periksaPukul = () => {
    const isi = inputPukul.value || '';

    if (isi === '') {
        return tandaiKolom(inputPukul, 'Pukul pengambilan wajib diisi.');
    }

    if (inputTanggal.value === hariIni() && isi < jamSekarang()) {
        return tandaiKolom(inputPukul, 'Pukul tidak boleh lebih awal dari waktu sekarang.');
    }

    return tandaiKolom(inputPukul, '');
};

// Halaman bersifat opsional. Format mengikuti App\Rules\CustomHalamanRule:
//   "1" | "1-2" | "1,2,3" | "1-3,5,7-9"
const periksaHalaman = () => {
    const isi = (inputHalaman.value || '').replace(/\s/g, '');

    if (isi === '') {
        return tandaiKolom(inputHalaman, '');
    }

    if (!/^\d+(-\d+)?(,\d+(-\d+)?)*$/.test(isi)) {
        return tandaiKolom(inputHalaman, 'Format halaman tidak valid. Contoh: 1-2, 1,2,3, 1-5, 1,4-10.');
    }

    for (const part of isi.split(',')) {
        if (part.includes('-')) {
            const [mulai, akhir] = part.split('-').map((n) => parseInt(n, 10));

            if (mulai > akhir) {
                return tandaiKolom(inputHalaman, "Range '" + part + "' tidak valid (start lebih besar dari end).");
            }

            if (mulai < 1) {
                return tandaiKolom(inputHalaman, 'Nomor halaman harus minimal 1.');
            }
        } else if (parseInt(part, 10) < 1) {
            return tandaiKolom(inputHalaman, 'Nomor halaman harus minimal 1.');
        }
    }

    return tandaiKolom(inputHalaman, '');
};

// Tanggal & pukul tidak boleh mundur dari hari ini. Batasnya dipasang ulang
// setiap kali tanggal berubah, termasuk batas jam kalau harinya hari ini.
const perbaruiBatasWaktu = () => {
    inputTanggal.setAttribute('min', hariIni());

    if (inputTanggal.value === hariIni()) {
        inputPukul.setAttribute('min', jamSekarang());
    } else {
        inputPukul.removeAttribute('min');
    }
};

function toIsoWithOffset(tanggal, pukul) {
    // tanggal: "2026-10-29", pukul: "14:04"
    const dt = new Date(`${tanggal}T${pukul}:00`);
    const pad = (n) => String(n).padStart(2, '0');
    const offset = -dt.getTimezoneOffset(); // menit, positif untuk WIB
    const sign = offset >= 0 ? '+' : '-';
    const abs = Math.abs(offset);
    const tz = `${sign}${pad(Math.floor(abs / 60))}:${pad(abs % 60)}`;
    return `${tanggal}T${pukul}:00${tz}`;
}


if (inputWhatsapp && inputTanggal && inputPukul && inputHalaman) {
    perbaruiBatasWaktu();


    inputWhatsapp.addEventListener('change', periksaWhatsapp);

    inputTanggal.addEventListener('change', () => {
        perbaruiBatasWaktu();
        periksaTanggal();
        periksaPukul();
    });

    inputPukul.addEventListener('change', periksaPukul);
    inputHalaman.addEventListener('change', periksaHalaman);

    const tombolBayar = document.getElementById('btn-pembayaran');
    const cetakForm = document.getElementById('form-cetak');

    if (tombolBayar) {
        tombolBayar.addEventListener('click', (event) => {

            const kolom = [inputWhatsapp, inputTanggal, inputPukul, inputHalaman];
            const hasil = [periksaWhatsapp(), periksaTanggal(), periksaPukul(), periksaHalaman()];
            const pertamaSalah = hasil.indexOf(false);

            if (pertamaSalah >= 0) {
                kolom[pertamaSalah].scrollIntoView({ block: 'center', behavior: 'smooth' });
                kolom[pertamaSalah].focus();
            }

            if (cetakForm) {
                // event.preventDefault();
                // cetakForm.requestSubmit();

                Array.from(cetakForm.elements).forEach(el => {
                    if (el.name && el.name !== '_token' && el.name !== '_method') {
                        el.remove();
                    }
                });
                const localStorageSaved = JSON.parse(localStorage.getItem(FORM_STORAGE_KEY))
                const urlParams = new URLSearchParams(window.location.search);
                const dataBaru = {
                    orderid: urlParams.get('order'),
                    nama: localStorageSaved.nama,
                    no_wa: localStorageSaved.whatsapp,
                    tanggal_ambil: toIsoWithOffset(localStorageSaved.tanggal, localStorageSaved.pukul),
                    files: localStorageSaved.files
                };

                console.log(dataBaru)
                for (const [key, value] of Object.entries(dataBaru)) {
                    if (key === 'files' && Array.isArray(value)) {
                        // Handle array of objects khusus untuk 'files'
                        value.forEach((fileObj, index) => {
                            for (const [fileKey, fileValue] of Object.entries(fileObj)) {
                                // Array (mis. paket) harus dikirim sebagai array sungguhan:
                                // files[0][paket][] = id layanan.
                                if (Array.isArray(fileValue)) {
                                    fileValue.forEach((item) => {
                                        const input = document.createElement('input');
                                        input.type = 'hidden';
                                        input.name = `files[${index}][${fileKey}][]`;
                                        input.value = item;
                                        cetakForm.appendChild(input);
                                    });
                                    continue;
                                }

                                const input = document.createElement('input');
                                input.type = 'hidden';
                                // Format name wajib: files[0][orientasi], files[0][ukuran], dst.
                                input.name = `files[${index}][${fileKey}]`;
                                input.value = fileValue;

                                cetakForm.appendChild(input);
                            }
                        });
                    } else {
                        // Untuk field biasa (nama, no_wa, dll)
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = value;
                        cetakForm.appendChild(input);
                    }
                }
                cetakForm.requestSubmit();
                // console.log(dataBaru);
            }
        });
    }
}

/* ---------- Formulir belum dikirim ke mana pun ---------- */



