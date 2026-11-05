import 'wired-elements/lib/wired-card.js';
import 'wired-elements/lib/wired-button.js';
import '../wired/sketch.js';
import '../shared/navigate.js';
import { bukaModal } from '../shared/modal.js';
import jsQR from 'jsqr';

/* ---------- Data halaman ---------- */

const halaman = document.getElementById('scan-page');
const video = document.getElementById('camera');
const stage = document.getElementById('camera-stage');
const stateBox = document.getElementById('camera-state');
const stateText = document.getElementById('camera-state-text');
const retryButton = document.getElementById('btn-coba');
const overlay = document.getElementById('scan-overlay');

// Endpoint verifikasi token (route "verified.token") tanpa bagian token.
const verifyBase = halaman?.dataset.verifyBase || '/scan/url';
const printUrl = halaman?.dataset.printUrl || '/print/kilat';

/* ---------- Pemindai QR ---------- */

const kanvas = document.createElement('canvas');
const konteks = kanvas.getContext('2d', { willReadFrequently: true });

// jsQR cukup dijalankan ~7 kali per detik supaya baterai dan CPU tidak boros.
const JEDA_PINDAI = 140;

// Frame kamera diperkecil sebelum dipindai. QR di layar komputer besar, jadi
// 640 px sudah cukup dan jauh lebih ringan untuk ponsel.
const MAKS_LEBAR_PINDAI = 640;

let stream = null;
let memindai = false;
let frameId = 0;
let waktuTerakhir = 0;
let sedangMemproses = false;
let membukaKamera = false;

const tampilkanStatus = (pesan, { error = false } = {}) => {
    if (!stateBox) {
        return;
    }

    stateBox.hidden = false;
    stateBox.classList.toggle('is-error', error);
    stateText.textContent = pesan;

    if (retryButton) {
        retryButton.hidden = !error;
    }
};

const sembunyikanStatus = () => {
    if (stateBox) {
        stateBox.hidden = true;
    }
};

const tampilkanLoader = () => {
    if (overlay) {
        overlay.hidden = false;
    }
};

const sembunyikanLoader = () => {
    if (overlay) {
        overlay.hidden = true;
    }
};

const pesanKamera = (error) => {
    switch (error?.name) {
        case 'NotAllowedError':
        case 'SecurityError':
            return 'Izin kamera ditolak. Izinkan akses kamera lewat ikon kamera di address bar, lalu coba lagi.';
        case 'NotFoundError':
        case 'OverconstrainedError':
            return 'Kamera tidak ditemukan di perangkat ini.';
        case 'NotReadableError':
        case 'AbortError':
            return 'Kamera sedang dipakai aplikasi lain. Tutup aplikasi tersebut lalu coba lagi.';
        default:
            return 'Kamera tidak bisa dibuka. Pastikan halaman ini dibuka lewat HTTPS atau localhost.';
    }
};

const hentikanKamera = () => {
    memindai = false;

    if (frameId) {
        window.cancelAnimationFrame(frameId);
        frameId = 0;
    }

    if (stream) {
        stream.getTracks().forEach((track) => track.stop());
        stream = null;
    }

    if (video) {
        video.srcObject = null;
    }

    stage?.classList.remove('is-live');
};

const pindai = (waktu) => {
    if (!memindai) {
        return;
    }

    frameId = window.requestAnimationFrame(pindai);

    if (waktu - waktuTerakhir < JEDA_PINDAI) {
        return;
    }

    waktuTerakhir = waktu;

    if (!video || !konteks || video.readyState < 2 || video.videoWidth === 0) {
        return;
    }

    kanvas.width = Math.min(video.videoWidth, MAKS_LEBAR_PINDAI);
    kanvas.height = Math.round(video.videoHeight * (kanvas.width / video.videoWidth));
    konteks.drawImage(video, 0, 0, kanvas.width, kanvas.height);

    let gambar = null;

    try {
        gambar = konteks.getImageData(0, 0, kanvas.width, kanvas.height);
    } catch (error) {
        return;
    }

    const hasil = jsQR(gambar.data, gambar.width, gambar.height, {
        inversionAttempts: 'dontInvert',
    });

    if (hasil && typeof hasil.data === 'string' && hasil.data !== '') {
        tanganiHasilScan(hasil.data);
    }
};

const mulaiKamera = async () => {
    if (sedangMemproses || membukaKamera || stream) {
        return;
    }

    if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
        tampilkanStatus(
            'Browser ini tidak memberi akses kamera. Buka halaman lewat HTTPS atau localhost, lalu coba lagi.',
            { error: true }
        );
        return;
    }

    membukaKamera = true;
    tampilkanStatus('Menyiapkan kamera…');

    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: { ideal: 'environment' },
                width: { ideal: 1280 },
                height: { ideal: 720 },
            },
            audio: false,
        });

        video.srcObject = stream;
        await video.play().catch(() => {});

        stage?.classList.add('is-live');
        sembunyikanStatus();

        memindai = true;
        waktuTerakhir = 0;
        frameId = window.requestAnimationFrame(pindai);
    } catch (error) {
        hentikanKamera();
        tampilkanStatus(pesanKamera(error), { error: true });
    } finally {
        membukaKamera = false;
    }
};

/* ---------- Verifikasi token ke server ---------- */

// QR komputer berisi token mentah. Kalau yang terpindai berupa URL, ambil
// bagian tokennya saja supaya tetap bisa diverifikasi.
const ambilToken = (teks) => {
    const isi = String(teks || '').trim();

    if (isi === '') {
        return '';
    }

    if (/^https?:\/\//i.test(isi)) {
        try {
            const url = new URL(isi);
            const dariQuery = url.searchParams.get('token');

            if (dariQuery) {
                return dariQuery;
            }

            const bagian = url.pathname.split('/').filter(Boolean);

            return bagian[bagian.length - 1] || '';
        } catch (error) {
            // Bukan URL yang valid: pakai isi apa adanya di bawah.
        }
    }

    const bagian = isi.split('/').filter(Boolean);

    return bagian[bagian.length - 1] || '';
};

const pesanServer = (data, status) => {
    if (data && typeof data.message === 'string' && data.message.trim() !== '') {
        return data.message;
    }

    if (status === 404) {
        return 'QR sudah kedaluwarsa. Pindai QR terbaru di layar komputer.';
    }

    if (status === 401 || status === 403) {
        return 'QR komputer tidak dikenali.';
    }

    if (status >= 500) {
        return 'Server sedang bermasalah. Coba lagi beberapa saat lagi.';
    }

    return 'QR tidak bisa diverifikasi. Coba lagi.';
};

const tampilkanGagal = (pesan) => {
    sedangMemproses = false;
    sembunyikanLoader();

    const modal = bukaModal('modal-info', { message: pesan });

    if (!modal) {
        mulaiKamera();
        return;
    }

    // Setelah user menutup pesan, kamera dibuka lagi supaya bisa memindai QR baru.
    modal.addEventListener('modal-close', () => {
        if (!sedangMemproses) {
            mulaiKamera();
        }
    }, { once: true });
};

const tanganiHasilScan = async (teks) => {
    if (sedangMemproses) {
        return;
    }

    sedangMemproses = true;

    // Kamera dimatikan lebih dulu supaya QR yang sama tidak terbaca berkali-kali.
    hentikanKamera();

    const token = ambilToken(teks);

    if (!token || token.split('.').length !== 2) {
        tampilkanGagal('QR yang dipindai bukan QR komputer. Pastikan kamu memindai QR terbaru di layar komputer.');
        return;
    }

    tampilkanLoader();

    try {
        const respons = await fetch(verifyBase + '/' + encodeURIComponent(token), {
            headers: { Accept: 'application/json' },
            cache: 'no-store',
        });

        const data = await respons.json().catch(() => null);

        if (!respons.ok || !data || data.error) {
            tampilkanGagal(pesanServer(data, respons.status));
            return;
        }

        // Token + data komputer disimpan untuk langkah berikutnya (/print/kilat).
        try {
            window.sessionStorage.setItem('printer:computer-token', token);
            window.sessionStorage.setItem('printer:computer', JSON.stringify(data));
        } catch (error) {
            // Penyimpanan browser tidak tersedia: tidak masalah.
        }

        window.location.href = printUrl;
    } catch (error) {
        tampilkanGagal('Tidak bisa menghubungi server. Periksa koneksi lalu coba lagi.');
    }
};

/* ---------- Mulai ---------- */

retryButton?.addEventListener('click', () => {
    mulaiKamera();
});

// Jembatan untuk pengujian/otomasi: kirim QR palsu tanpa kamera.
//   document.dispatchEvent(new CustomEvent('scan-qr:hasil', { detail: { teks: '<token>' } }))
document.addEventListener('scan-qr:hasil', (event) => {
    const teks = event.detail?.teks;

    if (typeof teks === 'string' && teks !== '') {
        tanganiHasilScan(teks);
    }
});

// Kembali dari aplikasi lain di ponsel: nyalakan kamera lagi kalau sudah berhenti.
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible' && !stream && !sedangMemproses) {
        mulaiKamera();
    }
});

window.addEventListener('pagehide', hentikanKamera);

mulaiKamera();
