import 'wired-elements/lib/wired-card.js';
import 'wired-elements/lib/wired-button.js';
import 'wired-elements/lib/wired-divider.js';
import '../wired/sketch.js';
import '../shared/navigate.js';
import { bukaModal } from '../shared/modal.js';

/* ---------- Elemen halaman ---------- */

const STATUS_FLOW = [
    'Memferivikasi pembayaran',
    'Menunggu pembayaran',
    'Mendapatkan pembayaran',
    'Pembayaran berhasil diverifikasi',
];

const POLL_INTERVAL = 3000;
const MAKS_404 = 5; // toleransi saat transaksi belum tercatat di Midtrans

const statusCard = document.getElementById('status-card');
const strukCard = document.getElementById('struk-card');
const errorCard = document.getElementById('error-card');
const statusText = document.getElementById('statusText');
const errorText = document.getElementById('errorText');
const orderIdEl = document.getElementById('orderId');
const btnUnduh = document.getElementById('btn-unduh');

const pathParts = window.location.pathname.split('/');
const orderId = pathParts[pathParts.length - 1];

let step = 0;
let stopped = false;
let jumlah404 = 0;
let rotator = null;

const rupiah = (nilai) => 'Rp. ' + Number(nilai || 0).toLocaleString('id-ID');

const tanggal = (iso) => {
    if (!iso) {
        return '-';
    }

    const d = new Date(iso);

    if (Number.isNaN(d.getTime())) {
        return '-';
    }

    const pad = (n) => String(n).padStart(2, '0');

    return pad(d.getDate()) + '-' + pad(d.getMonth() + 1) + '-' + d.getFullYear()
        + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
};

const hentikan = () => {
    if (stopped) {
        return;
    }

    stopped = true;
    clearInterval(rotator);
    statusCard.hidden = true;
};

const tampilkanError = (pesan) => {
    hentikan();
    strukCard.hidden = true;
    btnUnduh.hidden = true;
    errorCard.hidden = false;
    errorText.textContent = pesan;
};

const gambarQr = () => {
    const kotak = document.getElementById('qrcode');
    kotak.innerHTML = '';

    try {
        new QRCode(kotak, {
            text: window.location.href,
            width: 176,
            height: 176,
            colorDark: '#1b1b1b',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.M,
        });
    } catch (error) {
        // QR gagal dibuat (mis. CDN tidak termuat): struk tetap tampil.
    }
};

const tampilkanStruk = (data) => {
    hentikan();

    const files = Array.isArray(data.files) ? data.files : [];
    const subtotal = files.reduce((total, file) => total + Number(file.subtotal || 0), 0);
    const admin = Number(data.biaya_admin || 0);
    const total = Number(data.total_pembayaran || 0);
    const penanganan = Math.max(0, total - subtotal - admin);

    document.getElementById('s-orderid').textContent = data.orderid || orderId;
    document.getElementById('s-pelanggan').textContent = data.pelanggan || '-';
    document.getElementById('s-pesan').textContent = tanggal(data.tanggal_pesan);
    document.getElementById('s-ambil').textContent = tanggal(data.tanggal_ambil);
    document.getElementById('s-admin').textContent = rupiah(admin);
    document.getElementById('s-total').textContent = rupiah(total);
    document.getElementById('s-dibayar').textContent = rupiah(
        Number(data.total_dibayar || 0) > 0 ? data.total_dibayar : total
    );

    const barisPenanganan = document.getElementById('row-penanganan');
    barisPenanganan.hidden = penanganan <= 0;
    document.getElementById('s-penanganan').textContent = rupiah(penanganan);

    const daftarFile = document.getElementById('s-files');
    daftarFile.textContent = '';

    files.forEach((file) => {
        const baris = document.createElement('div');
        baris.className = 'file';

        const info = document.createElement('span');
        info.className = 'file-info';

        const nama = document.createElement('span');
        nama.className = 'file-name';
        nama.textContent = file.name || '-';

        const meta = document.createElement('span');
        meta.className = 'file-meta';
        meta.textContent = [
            file.ukuran || '-',
            (file.pages_toprint || 0) + ' halaman',
            'x ' + (file.jumlah_cetak || 0),
        ].join(' · ');

        info.append(nama, meta);

        const harga = document.createElement('span');
        harga.className = 'file-price';
        harga.textContent = rupiah(file.subtotal);

        baris.append(info, harga);
        daftarFile.appendChild(baris);
    });

    const catatan = files
        .map((file) => (file.catatan_tambahan || '').trim())
        .find((isi) => isi !== '');

    document.getElementById('s-catatan').textContent = catatan || '-';
    document.getElementById('s-item').textContent = files.length + ' item ('
        + (files.length > 0 ? 1 : 0) + ' layanan)';

    gambarQr();

    strukCard.hidden = false;
    btnUnduh.hidden = false;

    // Ingatkan user menyimpan struknya.
    bukaModal('modal-struk');
};

const poll = async () => {
    if (stopped) {
        return;
    }

    try {
        const res = await fetch(window.location.origin + '/struk/order/' + encodeURIComponent(orderId), {
            headers: { Accept: 'application/json' },
            cache: 'no-store',
        });

        if (res.status === 410) {
            tampilkanError('Order tidak ditemukan / kedaluwarsa');
            return;
        }

        if (!res.ok) {
            jumlah404 += 1;

            if (jumlah404 >= MAKS_404) {
                tampilkanError('Order tidak ditemukan / kedaluwarsa');
                return;
            }
        } else {
            jumlah404 = 0;

            let data = null;

            try {
                data = await res.json();
            } catch (error) {
                data = null;
            }

            const status = data && data.status ? String(data.status).toLowerCase() : '';

            if (status === 'paid') {
                try {
                    window.localStorage.clear();
                } catch (error) {
                    // Penyimpanan browser tidak tersedia.
                }

                tampilkanStruk(data);
                return;
            }

            if (status === 'failed' || status === 'refunded') {
                tampilkanError('Pembayaran gagal / dikembalikan');
                return;
            }
        }
    } catch (error) {
        console.warn('Koneksi terputus, mencoba lagi...', error);
    }

    if (!stopped) {
        window.setTimeout(poll, POLL_INTERVAL);
    }
};

/* ---------- Mulai ---------- */

if (!orderId || orderId === 'struk' || orderId === '') {
    tampilkanError('Order ID tidak ditemukan di URL');
} else {
    orderIdEl.textContent = 'Order: ' + orderId;

    rotator = window.setInterval(() => {
        step = (step + 1) % (STATUS_FLOW.length - 1);
        statusText.textContent = STATUS_FLOW[step];
    }, 2500);

    btnUnduh?.addEventListener('click', () => window.print());

    poll();
}
