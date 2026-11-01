import 'wired-elements/lib/wired-card.js';
import 'wired-elements/lib/wired-button.js';
import '../wired/sketch.js';
import '../shared/back-button.js';

/* ---------- Pilih metode pembayaran ---------- */

const daftarOpsi = Array.from(document.querySelectorAll('.pay-option'));
const daftarLabel = Array.from(document.querySelectorAll('.cta-label'));

const pilihOpsi = (opsi, fokus = false) => {
    daftarOpsi.forEach((item) => {
        const aktif = item === opsi;

        item.classList.toggle('is-selected', aktif);
        item.setAttribute('aria-checked', aktif ? 'true' : 'false');
        item.tabIndex = aktif ? 0 : -1;
    });

    if (fokus) {
        opsi.focus();
    }

    // Label tombol ikut metode yang dipilih, mis. "Bayar Sekarang Via QRIS"
    // atau "Bayar di Kasir". Semua label sudah ada di DOM (ditumpuk), jadi
    // lebar tombol tidak berubah dan sketsanya tidak perlu digambar ulang.
    daftarLabel.forEach((label) => {
        if (label.dataset.cta === opsi.dataset.metode) {
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
