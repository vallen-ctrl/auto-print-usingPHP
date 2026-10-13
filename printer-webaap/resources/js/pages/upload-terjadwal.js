import 'wired-elements/lib/wired-card.js';
import 'wired-elements/lib/wired-button.js';
import 'wired-elements/lib/wired-icon-button.js';
import { refreshSketches } from '../wired/sketch.js';
import '../shared/navigate.js';
import '../shared/back-button.js';
import { simpanNamaFile, gabungFileKeFormulir, bersihkanPenyimpananForm } from '../shared/file-store.js';

// Aturan: halaman upload selalu memulai dari nol. Begitu halaman ini dibuka,
// semua konfigurasi formulir dan daftar file yang tersimpan dibersihkan.
bersihkanPenyimpananForm();

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
            simpanNamaFile(chosen.map((file) => file.name));

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
                    // Gabungkan data file dari server (id, name, total_pages) ke
                    // konfigurasi formulir di localStorage (printer:formulir-terjadwal).
                    gabungFileKeFormulir(data.callback);

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
