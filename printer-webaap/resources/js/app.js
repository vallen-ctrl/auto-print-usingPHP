// Komponen sketsa + perilaku UI untuk halaman beranda, /upload/terjadwal,
// /formulir/terjadwal, dan /formulir-kilat. Impor per elemen supaya seluruh
// koleksi wired-elements tidak ikut dibundel.
import 'wired-elements/lib/wired-card.js';
import 'wired-elements/lib/wired-button.js';
import 'wired-elements/lib/wired-icon-button.js';
import 'wired-elements/lib/wired-input.js';
import 'wired-elements/lib/wired-radio.js';
import 'wired-elements/lib/wired-radio-group.js';
import 'wired-elements/lib/wired-checkbox.js';
import 'wired-elements/lib/wired-divider.js';
import 'wired-elements/lib/wired-combo.js';
import 'wired-elements/lib/wired-item.js';

/* ---------- Perbaikan bug library ---------- */

// wired-item 3.0.0-rc.6 selalu gagal menggambar: fungsi hachureFill bawaan
// library memanggil `fillPolygon` yang sudah tidak ada di roughjs 4. Error itu
// membatalkan render, sehingga item tetap tersembunyi (opacity: 0) dan isi
// dropdown tidak pernah terlihat. Gambar bawaan wired-item hanya dipakai untuk
// penanda item terpilih, jadi aman dimatikan supaya itemnya bisa tampil.
const wiredItemClass = customElements.get('wired-item');

if (wiredItemClass) {
    wiredItemClass.prototype.draw = () => {};
}

/* ---------- Sketsa wired-elements ---------- */

// Wired-elements menggambar sketsanya sekali berdasarkan ukuran elemen saat itu
// dan tidak selalu menggambar ulang saat ukurannya berubah (misalnya saat lebar
// layar melewati breakpoint, atau saat daftar file membuat panel jadi lebih tinggi).
// Jadi gambar ulang kita minta dari luar.
const sketsaSelector = 'wired-card, wired-button, wired-input, wired-radio, wired-radio-group, wired-checkbox, wired-combo';
const sketched = document.querySelectorAll(sketsaSelector);

function refreshSketches() {
    sketched.forEach((el) => {
        if (typeof el.wiredRender === 'function') {
            el.wiredRender(true);
        } else {
            el.requestUpdate?.();
        }
    });
}

if (sketched.length > 0) {
    window.addEventListener('resize', refreshSketches);

    if ('ResizeObserver' in window) {
        new ResizeObserver(refreshSketches).observe(document.body);
    }

    // Ukuran kotak bergantung pada font, jadi sketsa yang digambar sebelum font
    // selesai dimuat bisa tidak sesuai elemennya. Gambar ulang setelah font siap.
    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(refreshSketches);
    }
}

/* ---------- Label untuk input sketsa ---------- */

// <input> milik wired-input ada di dalam shadow DOM, jadi <label for="...">
// tidak otomatis terhubung. Nama labelnya kita salin ke input di dalamnya.
function salinLabelKeInput() {
    document.querySelectorAll('wired-input').forEach((el) => {
        const inner = el.shadowRoot ? el.shadowRoot.querySelector('input') : null;

        if (!inner || inner.getAttribute('aria-label')) {
            return;
        }

        const label = el.id ? document.querySelector('label[for="' + el.id + '"]') : null;

        if (label) {
            inner.setAttribute('aria-label', label.textContent.trim());
        }
    });
}

salinLabelKeInput();
window.addEventListener('load', salinLabelKeInput);

/* ---------- Navigasi tombol ---------- */

// Tombol diberi data-href dari Blade (mis. {{ route('upload.terjadwal') }}).
document.querySelectorAll('[data-href]').forEach((el) => {
    el.addEventListener('click', () => {
        window.location.href = el.dataset.href;
    });
});

/* ---------- Tombol kembali ---------- */

// Kembali ke halaman sebelumnya. Kalau halaman ini dibuka langsung (tidak ada
// halaman sebelumnya dari situs ini), dipakai data-fallback dari Blade.
const backButton = document.getElementById('btn-kembali');

if (backButton) {
    backButton.addEventListener('click', () => {
        const fromThisSite = document.referrer !== '' && document.referrer.startsWith(window.location.origin);

        if (fromThisSite && window.history.length > 1) {
            window.history.back();
        } else {
            window.location.href = backButton.dataset.fallback || '/';
        }
    });
}

/* ---------- Serah terima daftar file antar halaman ---------- */

// Tanpa server, objek File tidak bisa dibawa antar halaman. Jadi yang disimpan
// hanya nama filenya, supaya halaman formulir tetap bisa menampilkan daftarnya.
const FILE_STORAGE_KEY = 'printer:file-names';
const FORM_STORAGE_KEY = 'printer:formulir-terjadwal';

// Aturan: halaman upload selalu memulai dari nol. Begitu halaman ini dibuka,
// semua konfigurasi formulir yang tersimpan dibersihkan.
if (document.getElementById('input-pdf')) {
    try {
        localStorage.removeItem(FORM_STORAGE_KEY);
        sessionStorage.removeItem(FILE_STORAGE_KEY);
    } catch (error) {
        // Penyimpanan browser tidak tersedia: tidak masalah.
    }
}

const bacaNamaFile = () => {
    try {
        const raw = sessionStorage.getItem(FILE_STORAGE_KEY);
        const names = raw ? JSON.parse(raw) : [];

        return Array.isArray(names) ? names : [];
    } catch (error) {
        return [];
    }
};

/* ---------- Pilih file PDF (maksimal sesuai data-max pada input) ---------- */

// Semua di sini hanya bekerja di browser: memilih file, menyaring yang bukan PDF,
// membatasi jumlahnya, dan menampilkan daftarnya. Belum ada pengiriman file ke
// server mana pun.
const pickButton = document.getElementById('btn-pilih-pdf');
const pdfInput = document.getElementById('input-pdf');
const pdfCount = document.getElementById('file-count');
const pdfList = document.getElementById('file-list');
const pdfWarning = document.getElementById('file-warning');

if (pickButton && pdfInput && pdfCount && pdfList && pdfWarning) {
    const maxFiles = Number(pdfInput.dataset.max) || 10;
    const chosen = [];

    const isPdf = (file) => file.type === 'application/pdf' || /\.pdf$/i.test(file.name);

    const formatSize = (bytes) => {
        if (bytes >= 1024 * 1024) {
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }

        return Math.max(1, Math.round(bytes / 1024)) + ' KB';
    };

    // Daftar file dikembalikan ke input, supaya isi input selalu sama dengan
    // yang terlihat di layar (kalau nanti form ini dikirim).
    const syncInput = () => {
        const transfer = new DataTransfer();

        chosen.forEach((file) => transfer.items.add(file));

        pdfInput.files = transfer.files;
    };

    const removeFile = (index) => {
        chosen.splice(index, 1);
        pdfWarning.textContent = '';
        pdfWarning.hidden = true;
        applyChanges();
    };

    // Dipanggil setiap kali daftar file berubah: isi input disamakan, daftar
    // digambar, lalu sketsa digambar ulang setelah tinggi panel benar-benar final.
    const applyChanges = () => {
        syncInput();
        renderFiles();
        refreshSketches();
    };

    function renderFiles() {
        pdfList.textContent = '';

        chosen.forEach((file, index) => {
            const item = document.createElement('li');
            item.className = 'file-item';

            const name = document.createElement('span');
            name.className = 'file-name';
            name.textContent = file.name;

            const size = document.createElement('span');
            size.className = 'file-size';
            size.textContent = formatSize(file.size);

            const remove = document.createElement('wired-icon-button');
            remove.className = 'file-remove';
            remove.setAttribute('roughness', '1.2');

            const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            icon.setAttribute('class', 'file-remove-icon');
            icon.setAttribute('viewBox', '0 0 24 24');
            icon.setAttribute('fill', 'none');
            icon.setAttribute('stroke', 'currentColor');
            icon.setAttribute('stroke-width', '2');
            icon.setAttribute('stroke-linecap', 'round');
            icon.setAttribute('aria-hidden', 'true');

            const cross = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            cross.setAttribute('d', 'M6 6 18 18M18 6 6 18');
            icon.append(cross);

            // Nama tombol untuk pembaca layar; teksnya disembunyikan secara visual.
            const label = document.createElement('span');
            label.className = 'visually-hidden';
            label.textContent = 'Hapus ' + file.name;

            remove.append(icon, label);
            remove.addEventListener('click', () => removeFile(index));

            item.append(name, size, remove);
            pdfList.append(item);
        });

        const total = chosen.length;

        pdfCount.textContent = total + ' / ' + maxFiles + ' file dipilih';
        pdfCount.hidden = total === 0;
        pdfList.hidden = total === 0;
    }

    pickButton.addEventListener('click', () => pdfInput.click());

    pdfInput.addEventListener('change', () => {
        let skippedType = 0;
        let skippedLimit = 0;

        Array.from(pdfInput.files || []).forEach((file) => {
            if (!isPdf(file)) {
                skippedType++;
                return;
            }

            if (chosen.length >= maxFiles) {
                skippedLimit++;
                return;
            }

            const duplicate = chosen.some((picked) => picked.name === file.name && picked.size === file.size);

            if (!duplicate) {
                chosen.push(file);
            }
        });

        const messages = [];

        if (skippedType > 0) {
            messages.push(skippedType + ' file dilewati karena bukan PDF.');
        }

        if (skippedLimit > 0) {
            messages.push(skippedLimit + ' file dilewati karena maksimal ' + maxFiles + ' file.');
        }

        pdfWarning.textContent = messages.join(' ');
        pdfWarning.hidden = messages.length === 0;

        applyChanges();
    });

    /* ---------- Lanjutkan ke halaman formulir ---------- */

    const lanjutButton = document.getElementById('btn-lanjutkan');

    if (lanjutButton) {
        let mengirim = false;

        lanjutButton.addEventListener('click', async () => {
            if (mengirim) {
                return;
            }

            if (chosen.length === 0) {
                pdfWarning.textContent = 'Pilih minimal satu file PDF dulu.';
                pdfWarning.hidden = false;
                return;
            }

            // Simpan nama file supaya halaman formulir tetap bisa menampilkan daftarnya.
            try {
                sessionStorage.setItem(FILE_STORAGE_KEY, JSON.stringify(chosen.map((file) => file.name)));
            } catch (error) {
                // Penyimpanan browser tidak tersedia: halaman formulir tetap bisa dibuka.
            }

            const action = lanjutButton.dataset.action;

            // Kalau endpoint belum tersedia, kembali ke perilaku navigasi lama.
            if (!action) {
                if (lanjutButton.dataset.next) {
                    window.location.href = lanjutButton.dataset.next;
                }
                return;
            }

            // Kirim file yang dipilih ke server. Satu kali upload hanya boleh
            // maksimal 10 file dan semuanya PDF (validasi juga diulang di server).
            const formData = new FormData();

            chosen.forEach((file) => formData.append('files[]', file));

            mengirim = true;

            try {
                const response = await fetch(action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': lanjutButton.dataset.token || '',
                        Accept: 'application/json',
                    },
                    body: formData,
                });

                // Server mengarahkan ke halaman berikutnya dengan order id.
                if (response.redirected) {
                    window.location.href = response.url;
                    return;
                }

                const data = await response.json().catch(() => ({}));

                if (response.ok) {
                    window.location.href = data.redirect || lanjutButton.dataset.next;
                    return;
                }

                const pesan = data.message
                    || (data.errors ? Object.values(data.errors).flat()[0] : null)
                    || 'Upload gagal. Coba lagi.';

                pdfWarning.textContent = pesan;
                pdfWarning.hidden = false;
            } catch (error) {
                pdfWarning.textContent = 'Tidak bisa menghubungi server. Coba lagi.';
                pdfWarning.hidden = false;
            } finally {
                mengirim = false;
            }
        });
    }
}

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
    catatan: document.getElementById('catatan')
};

if (fileCombo && fileKosong && kolomPengaturan.orientasi) {
    const paketChecks = Array.from(document.querySelectorAll('.paket-check'));
    const subTotal = document.getElementById('sub-total');

    const pengaturanAwal = () => ({
        orientasi: 'portrait',
        ukuran: 'a4',
        warna: 'hitam-putih',
        kualitas: 'standar',
        jumlah: '1',
        halaman: '',
        catatan: '',
        paket: []
    });

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

    const tulisKonfigurasi = () => {
        try {
            localStorage.setItem(FORM_STORAGE_KEY, JSON.stringify({
                fileAktif: fileAktif,
                files: Object.fromEntries(pengaturanPerFile)
            }));
        } catch (error) {
            // Penyimpanan browser tidak tersedia: konfigurasi tidak persist.
        }
    };

    const namaFile = bacaNamaFile();
    const tersimpan = bacaKonfigurasi();
    const simpanan = tersimpan && tersimpan.files ? tersimpan.files : {};
    const pengaturanPerFile = new Map();
    let fileAktif = null;

    // Nama panjang dipendekkan di dropdown supaya kotaknya tidak melebar;
    // nama lengkapnya tetap ada di tooltip.
    const potongNama = (name) => (name.length > 26 ? name.slice(0, 23) + '…' : name);

    // Sub total dihitung dari paket yang dicentang pada file yang sedang aktif.
    const hitungSubTotal = () => {
        let total = 0;

        paketChecks.forEach((box) => {
            if (box.checked) {
                total += Number(box.dataset.harga) || 0;
            }
        });

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
        paket: paketChecks.filter((box) => box.checked).map((box) => box.dataset.id)
    });

    const tulisPengaturan = (state) => {
        kolomPengaturan.orientasi.selected = state.orientasi;
        kolomPengaturan.ukuran.selected = state.ukuran;
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
            pengaturanPerFile.set(fileAktif, bacaPengaturan());
            tulisKonfigurasi();
        }
    };

    namaFile.forEach((name) => {
        const item = document.createElement('wired-item');
        item.setAttribute('value', name);
        item.textContent = potongNama(name);
        item.title = name;
        fileCombo.append(item);

        // Muat konfigurasi tersimpan untuk file ini, atau pakai nilai awal.
        pengaturanPerFile.set(name, simpanan[name] || pengaturanAwal());
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

    // Perubahan pengaturan disimpan ke file yang sedang dipilih.
    ['orientasi', 'ukuran', 'warna', 'kualitas'].forEach((key) => {
        kolomPengaturan[key].addEventListener('selected', simpanFileAktif);
    });

    ['jumlah', 'halaman', 'catatan'].forEach((key) => {
        kolomPengaturan[key].addEventListener('change', simpanFileAktif);
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

// Halaman bersifat opsional. Kalau diisi, hanya boleh satu nomor ("1", "2")
// atau rentang ("1-2"), dan nomor awalnya tidak boleh lebih besar.
const periksaHalaman = () => {
    const isi = (inputHalaman.value || '').replace(/\s/g, '');

    if (isi === '') {
        return tandaiKolom(inputHalaman, '');
    }

    if (!/^\d+(-\d+)?$/.test(isi)) {
        return tandaiKolom(inputHalaman, 'Format halaman hanya boleh 1, 2, atau rentang 1-2.');
    }

    const bagian = isi.split('-');

    if (bagian.length === 2 && Number(bagian[0]) > Number(bagian[1])) {
        return tandaiKolom(inputHalaman, 'Halaman awal tidak boleh lebih besar dari halaman akhir.');
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

    if (tombolBayar) {
        tombolBayar.addEventListener('click', () => {
            const kolom = [inputWhatsapp, inputTanggal, inputPukul, inputHalaman];
            const hasil = [periksaWhatsapp(), periksaTanggal(), periksaPukul(), periksaHalaman()];
            const pertamaSalah = hasil.indexOf(false);

            if (pertamaSalah >= 0) {
                kolom[pertamaSalah].scrollIntoView({ block: 'center', behavior: 'smooth' });
                kolom[pertamaSalah].focus();
            }
        });
    }
}

/* ---------- Formulir belum dikirim ke mana pun ---------- */

const cetakForm = document.getElementById('form-cetak');

if (cetakForm) {
    cetakForm.addEventListener('submit', (event) => {
        event.preventDefault();
    });
}

/* ---------- Sub total paket di halaman formulir kilat ---------- */

const paketKilat = document.querySelectorAll('.paket-checkbox');
const subtotalKilat = document.getElementById('subtotal');

if (paketKilat.length && subtotalKilat) {
    const hitung = () => {
        let total = 0;

        paketKilat.forEach((checkbox) => {
            if (checkbox.checked) {
                total += Number(checkbox.dataset.harga || 0);
            }
        });

        subtotalKilat.textContent = total.toLocaleString('id-ID');
    };

    paketKilat.forEach((checkbox) => checkbox.addEventListener('change', hitung));
    hitung();
}
