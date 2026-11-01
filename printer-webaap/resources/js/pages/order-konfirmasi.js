import 'wired-elements/lib/wired-card.js';
import 'wired-elements/lib/wired-button.js';
import '../wired/sketch.js';
import '../shared/back-button.js';
import '../shared/navigate.js';
import { bukaModal } from '../shared/modal.js';

/* ---------- Data halaman ---------- */

const halaman = document.getElementById('halaman-konfirmasi');
const daftarOpsi = Array.from(document.querySelectorAll('.pay-option'));
const daftarLabel = Array.from(document.querySelectorAll('.cta-label'));
const tombolBayar = document.getElementById('btn-bayar');
const timer = document.getElementById('timer-bayar');
const sisaWaktu = document.getElementById('sisa-waktu');

const orderId = halaman?.dataset.orderId ?? '';
const tokenUrl = halaman?.dataset.tokenUrl ?? '';
const kasirUrl = halaman?.dataset.kasirUrl ?? '';
const statusUrl = halaman?.dataset.statusUrl ?? '';
const strukUrl = halaman?.dataset.strukUrl ?? '';
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

let metodeTerpilih = document.querySelector('.pay-option.is-selected')?.dataset.metode ?? 'qris';
let menungguKasir = halaman?.dataset.menungguKasir === '1';
let waktuHabis = false;
let sedangMemproses = false;
let pembayaranSelesai = false;
let kasirDikonfirmasi = false;

const kunciTombol = () => {
    if (tombolBayar) {
        tombolBayar.disabled = true;
    }
};

/* ---------- Pilih metode pembayaran ---------- */

const pilihOpsi = (opsi, fokus = false) => {
    if (menungguKasir) {
        return;
    }

    daftarOpsi.forEach((item) => {
        const aktif = item === opsi;

        item.classList.toggle('is-selected', aktif);
        item.setAttribute('aria-checked', aktif ? 'true' : 'false');
        item.tabIndex = aktif ? 0 : -1;
    });

    if (fokus) {
        opsi.focus();
    }

    metodeTerpilih = opsi.dataset.metode || 'qris';

    // Label tombol ikut metode yang dipilih, mis. "Bayar Sekarang Via QRIS"
    // atau "Bayar di Kasir". Semua label sudah ada di DOM (ditumpuk), jadi
    // lebar tombol tidak berubah dan sketsanya tidak perlu digambar ulang.
    daftarLabel.forEach((label) => {
        if (label.dataset.cta === metodeTerpilih) {
            label.removeAttribute('data-nonaktif');
        } else {
            label.setAttribute('data-nonaktif', 'true');
        }
    });
};

if (daftarOpsi.length) {
    daftarOpsi.forEach((opsi, urutan) => {
        opsi.addEventListener('click', () => pilihOpsi(opsi));

        opsi.addEventListener('keydown', (event) => {
            const arah = {
                ArrowDown: 1,
                ArrowRight: 1,
                ArrowUp: -1,
                ArrowLeft: -1,
            }[event.key];

            if (arah) {
                event.preventDefault();

                const tujuan = daftarOpsi[(urutan + arah + daftarOpsi.length) % daftarOpsi.length];
                pilihOpsi(tujuan, true);
                return;
            }

            if (event.key === ' ' || event.key === 'Enter') {
                event.preventDefault();
                pilihOpsi(opsi);
            }
        });
    });
}

/* ---------- Hitung mundur 20 menit ---------- */

const formatDurasi = (detik) => {
    const menit = Math.floor(detik / 60);
    const sisa = detik % 60;

    return String(menit).padStart(2, '0') + ':' + String(sisa).padStart(2, '0');
};

const matikanPembayaran = () => {
    waktuHabis = true;
    kunciTombol();
    timer?.classList.add('is-habis');
};

const hentikanPembayaran = () => {
    if (waktuHabis) {
        return;
    }

    matikanPembayaran();
    bukaModal('modal-kadaluarsa');
};

let sisaDetik = Math.max(0, Number(halaman?.dataset.sisaDetik ?? 0) || 0);

const jalankanTimer = () => {
    // Saat order sudah diteruskan ke kasir, tidak ada hitung mundur lagi.
    if (!sisaWaktu || menungguKasir) {
        return;
    }

    const render = () => {
        sisaWaktu.textContent = formatDurasi(sisaDetik);

        if (sisaDetik <= 0) {
            hentikanPembayaran();
            return;
        }

        sisaDetik -= 1;
        window.setTimeout(render, 1000);
    };

    render();
};

if (menungguKasir) {
    kunciTombol();
}

jalankanTimer();

/* ---------- Pembayaran ---------- */

const tampilkanGagal = (pesan) => {
    bukaModal('modal-info', {
        type: 'error',
        title: 'Gagal memulai pembayaran',
        message: pesan,
    });
};

// Setelah popup Snap selesai, minta server memverifikasi transaksi ke Midtrans
// (halaman status juga mengecek ulang setelah redirect).
const cekStatusPembayaran = async () => {
    if (!statusUrl) {
        return;
    }

    try {
        await fetch(statusUrl, {
            headers: { Accept: 'application/json' },
            cache: 'no-store',
        });
    } catch (error) {
        // Diabaikan: halaman status akan mengecek ulang.
    }
};

const bukaSnap = (token) => {
    window.snap.pay(token, {
        onSuccess: async () => {
            pembayaranSelesai = true;
            await cekStatusPembayaran();
        },
        onPending: async () => {
            pembayaranSelesai = true;
            await cekStatusPembayaran();
        },
        onError: () => {
            tampilkanGagal('Pembayaran gagal diproses oleh Midtrans. Silakan coba lagi.');
        },
        onClose: () => {
            // Kalau Midtrans tidak melakukan redirect sendiri, pindah ke halaman
            // status pembayaran setelah pembayarannya berhasil.
            if (pembayaranSelesai && strukUrl) {
                window.location.href = strukUrl;
            }
        },
    });
};

const mulaiPembayaranQris = async () => {
    if (sedangMemproses || waktuHabis || menungguKasir) {
        return;
    }

    if (!window.snap) {
        tampilkanGagal('Komponen pembayaran Midtrans belum termuat. Periksa koneksi internet Anda lalu muat ulang halaman.');
        return;
    }

    sedangMemproses = true;

    if (tombolBayar) {
        tombolBayar.disabled = true;
    }

    try {
        const respons = await fetch(tokenUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ orderid: orderId }),
        });

        const data = await respons.json().catch(() => ({}));

        if (!respons.ok || !data.token) {
            // 410 = order sudah kedaluwarsa (file sudah dihapus) atau sesi
            // pembayaran hilang. Tampilkan pesan dari server apa adanya.
            if (respons.status === 410) {
                matikanPembayaran();
                tampilkanGagal(data.message || 'Waktu pembayaran sudah habis. Silakan upload ulang file Anda.');
                return;
            }

            tampilkanGagal(data.message || 'Token pembayaran gagal dibuat. Silakan coba lagi.');
            return;
        }

        bukaSnap(data.token);
    } catch (error) {
        tampilkanGagal('Tidak dapat menghubungi server pembayaran. Periksa koneksi Anda lalu coba lagi.');
    } finally {
        sedangMemproses = false;

        if (!waktuHabis && tombolBayar) {
            tombolBayar.disabled = false;
        }
    }
};

const bayarDiKasir = async () => {
    if (sedangMemproses || waktuHabis || menungguKasir) {
        return;
    }

    sedangMemproses = true;
    kunciTombol();

    try {
        const respons = await fetch(kasirUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ orderid: orderId }),
        });

        const data = await respons.json().catch(() => ({}));

        if (!respons.ok) {
            if (respons.status === 410) {
                matikanPembayaran();
            }

            tampilkanGagal(data.message || 'Gagal memilih pembayaran di kasir. Silakan coba lagi.');
            return;
        }

        menungguKasir = true;
        kasirDikonfirmasi = true;
        kunciTombol();

        bukaModal('modal-kasir', {
            type: 'success',
            title: 'Pesanan diteruskan ke kasir',
            message: data.message || 'Pembayaran dikonfirmasi langsung oleh penjual.',
        });
    } catch (error) {
        tampilkanGagal('Tidak dapat menghubungi server. Periksa koneksi Anda lalu coba lagi.');
    } finally {
        sedangMemproses = false;

        if (!waktuHabis && !menungguKasir && tombolBayar) {
            tombolBayar.disabled = false;
        }
    }
};

tombolBayar?.addEventListener('click', () => {
    if (waktuHabis || menungguKasir) {
        return;
    }

    if (metodeTerpilih === 'kasir') {
        bayarDiKasir();
        return;
    }

    mulaiPembayaranQris();
});

// Setelah pesanan diteruskan ke kasir, muat ulang halaman supaya tampil
// keadaan "menunggu konfirmasi kasir" dari server (tanpa hitung mundur).
document.getElementById('modal-kasir')?.addEventListener('modal-close', () => {
    if (kasirDikonfirmasi) {
        window.location.reload();
    }
});
