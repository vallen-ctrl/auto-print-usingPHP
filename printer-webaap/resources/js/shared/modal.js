// Perilaku komponen <x-modal> (wired-dialog bergaya sketsa).
//
// Membuka & menutup lewat atribut data, jadi bisa dipakai dari markup mana pun:
//   <wired-button data-modal-open="modal-info">Lihat info</wired-button>
//   <wired-button data-modal-close>Tutup</wired-button>
// atau dari JavaScript, supaya isinya bisa diubah saat kejadiannya berlangsung:
//   import { bukaModal, tutupModal } from '../shared/modal.js';
//   bukaModal('modal-info', { type: 'success', title: 'Berhasil', message: 'Cetakan siap.' });
//
// Modal hanya bisa ditutup lewat latar gelap, elemen ber-data-modal-close, dan
// tombol Escape, kalau punya atribut data-modal-dismissible.
import 'wired-elements/lib/wired-dialog.js';
import 'wired-elements/lib/wired-button.js';
import 'wired-elements/lib/wired-icon-button.js';

const SELECTOR_MODAL = 'wired-dialog.modal';
const SELECTOR_PANEL = '.modal__panel';
const TIPE = ['info', 'success', 'warning', 'error'];

// Klik pada latar gelap hanya menutup modal kalau tekanannya juga mulai dari
// latar gelap (bukan seret seleksi teks dari dalam kotak).
let mulaiDiLuar = false;

const tipeSah = (tipe) => (TIPE.includes(tipe) ? tipe : 'info');

const titikDiDalam = (kotak, event) => event.clientX >= kotak.left
    && event.clientX <= kotak.right
    && event.clientY >= kotak.top
    && event.clientY <= kotak.bottom;

// target boleh berupa elemen, id ("modal-info" / "#modal-info"), atau selector.
const ambilModal = (target) => {
    if (target instanceof Element) {
        return target.closest(SELECTOR_MODAL);
    }

    if (typeof target !== 'string' || target === '') {
        return null;
    }

    const lewatId = document.getElementById(target.startsWith('#') ? target.slice(1) : target);

    if (lewatId && lewatId.matches(SELECTOR_MODAL)) {
        return lewatId;
    }

    try {
        const lewatSelector = document.querySelector(target);

        return lewatSelector instanceof Element && lewatSelector.matches(SELECTOR_MODAL) ? lewatSelector : null;
    } catch (error) {
        return null;
    }
};

const daftarTerbuka = () => Array.from(document.querySelectorAll(SELECTOR_MODAL + '[open]'));

// Modal yang terbuka dicatat berurutan supaya tombol Escape menutup yang
// paling akhir dibuka.
const tumpukan = [];

// Modal yang sudah terbuka sejak halaman dimuat (dirender server dengan
// atribut open) juga dicatat supaya kunci gulir & Escape ikut mengenalinya.
daftarTerbuka().forEach((modal) => tumpukan.push(modal));

// Lit menulis atribut open secara asinkron, jadi status buka/tutup dibaca dari
// propertinya, bukan dari atributnya.
const sedangTerbuka = (el) => el instanceof Element
    && el.matches(SELECTOR_MODAL)
    && el.open === true;

// Elemen yang membuka modal, untuk dikembalikan fokusnya saat modal ditutup.
const pemicuSebelumnya = new WeakMap();

// Kotak isi modal dipakai untuk membedakan klik di dalam kartu (tidak menutup)
// dengan klik di latar gelap (menutup). Kartu sketsanya digambar oleh
// <wired-dialog> di shadow DOM-nya, jadi diambil dari sana.
//
// Ukuran 0 (dialog belum selesai digambar) dianggap "kotak belum diketahui",
// supaya klik tidak pernah menutup modal karena salah hitung.
const kotakIsi = (modal) => {
    const kandidat = [
        modal.shadowRoot ? modal.shadowRoot.querySelector('wired-card') : null,
        modal.querySelector(SELECTOR_PANEL),
    ];

    for (const el of kandidat) {
        if (!el) {
            continue;
        }

        const kotak = el.getBoundingClientRect();

        if (kotak.width > 0 && kotak.height > 0) {
            return kotak;
        }
    }

    return null;
};

// Halaman di belakang modal tidak boleh ikut bergulir. Lebar bilah gulir yang
// hilang diganti padding supaya isi halaman tidak bergeser.
const perbaruiKunciGulir = () => {
    const html = document.documentElement;
    const lebarGulir = window.innerWidth - html.clientWidth;

    if (tumpukan.length > 0) {
        html.style.overflow = 'hidden';
        html.style.paddingRight = lebarGulir > 0 ? lebarGulir + 'px' : '';
    } else {
        html.style.overflow = '';
        html.style.paddingRight = '';
    }
};

// Lit menggambar sketsa sesuai ukuran elemen saat itu, sedangkan modal yang
// tertutup berukuran nol. Jadi gambarnya diminta ulang setelah modal tampil.
const gambarUlang = (modal) => {
    modal.querySelectorAll('wired-button, wired-icon-button, wired-card, wired-input').forEach((el) => {
        if (typeof el.wiredRender === 'function') {
            el.wiredRender(true);
        } else if (typeof el.requestUpdate === 'function') {
            el.requestUpdate();
        }
    });
};

// <wired-button> meneruskan fokus ke <button> di dalam shadow DOM-nya, jadi
// fokus dicari dulu di dalam shadow DOM sebelum jatuh ke elemen itu sendiri.
const fokuskan = (el) => {
    if (!el) {
        return;
    }

    const dalam = el.shadowRoot
        ? el.shadowRoot.querySelector('button, a[href], input, select, textarea, [tabindex]')
        : null;

    if (dalam) {
        dalam.focus();
        return;
    }

    if (typeof el.focus === 'function') {
        el.focus();
    }
};

export function bukaModal(target, isi = {}) {
    const modal = ambilModal(target);

    if (!modal) {
        return null;
    }

    const panel = modal.querySelector(SELECTOR_PANEL);

    // Isi modal boleh diisi ulang setiap kali dibuka. `message` menimpa isi
    // teksnya, jadi jangan dipakai kalau isinya markup dari slot.
    if (panel) {
        if (typeof isi.type === 'string') {
            panel.dataset.type = tipeSah(isi.type);
        }

        if (typeof isi.title === 'string') {
            const judul = panel.querySelector('.modal__title');

            if (judul) {
                judul.textContent = isi.title;

                if (judul.id && judul.textContent.trim() !== '') {
                    modal.setAttribute('aria-labelledby', judul.id);
                }
            }
        }

        if (typeof isi.message === 'string') {
            const badan = panel.querySelector('.modal__body');

            if (badan) {
                badan.textContent = isi.message;
            }
        }
    }

    const aktif = document.activeElement;

    if (aktif instanceof HTMLElement && aktif !== document.body) {
        pemicuSebelumnya.set(modal, aktif);
    }

    modal.open = true;

    if (!tumpukan.includes(modal)) {
        tumpukan.push(modal);
    }

    perbaruiKunciGulir();

    requestAnimationFrame(() => {
        gambarUlang(modal);

        if (panel) {
            panel.focus({ preventScroll: true });
        }
    });

    modal.dispatchEvent(new CustomEvent('modal-open', { detail: { id: modal.id } }));

    return modal;
}

export function tutupModal(target) {
    const modal = ambilModal(target);

    if (!modal || !modal.open) {
        return modal;
    }

    modal.open = false;

    const urutan = tumpukan.indexOf(modal);

    if (urutan >= 0) {
        tumpukan.splice(urutan, 1);
    }

    perbaruiKunciGulir();

    // Fokus dikembalikan ke elemen yang tadi membuka modal, supaya pengguna
    // keyboard tidak kehilangan posisinya.
    const pemicu = pemicuSebelumnya.get(modal);
    pemicuSebelumnya.delete(modal);

    if (pemicu && document.contains(pemicu)) {
        fokuskan(pemicu);
    }

    modal.dispatchEvent(new CustomEvent('modal-close', { detail: { id: modal.id } }));

    return modal;
}

// Pembuka & penutup dipasang lewat event delegation supaya modal (maupun
// tombolnya) tetap bekerja walau ditambah setelah halaman dimuat.
document.addEventListener('click', (event) => {
    const target = event.target;

    if (!(target instanceof Element)) {
        return;
    }

    const pembuka = target.closest('[data-modal-open]');

    if (pembuka) {
        event.preventDefault();
        bukaModal(pembuka.getAttribute('data-modal-open') || pembuka);
        return;
    }

    const penutup = target.closest('[data-modal-close]');

    if (penutup) {
        const modal = penutup.closest(SELECTOR_MODAL);

        if (modal) {
            event.preventDefault();
            tutupModal(modal);
        }

        return;
    }

    // Klik pada latar gelap & pinggiran kartu sampai ke sini dengan target
    // berupa elemen dialognya sendiri (isi shadow DOM tampil sebagai host).
    if (!sedangTerbuka(target) || !mulaiDiLuar
        || !target.hasAttribute('data-modal-dismissible')) {
        return;
    }

    const kotak = kotakIsi(target);

    if (!kotak || titikDiDalam(kotak, event)) {
        return;
    }

    tutupModal(target);
});

document.addEventListener('pointerdown', (event) => {
    const target = event.target;

    if (!sedangTerbuka(target)) {
        mulaiDiLuar = false;
        return;
    }

    const kotak = kotakIsi(target);

    mulaiDiLuar = kotak ? !titikDiDalam(kotak, event) : false;
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    const modal = tumpukan[tumpukan.length - 1];

    if (!modal || !modal.open || !modal.hasAttribute('data-modal-dismissible')) {
        return;
    }

    event.preventDefault();
    tutupModal(modal);
});
