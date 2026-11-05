import 'wired-elements/lib/wired-card.js';
import 'wired-elements/lib/wired-button.js';
import '../wired/sketch.js';
import '../shared/navigate.js';
import { bukaModal } from '../shared/modal.js';

/* ---------- Pilih tempat edit ---------- */

// Halaman edit belum dibuat, jadi kedua tombol hanya memberi tahu bahwa
// langkah ini masih menyusul. Ganti handler ini saat halaman edit siap.
const bukaPemberitahuan = (tempat) => {
    bukaModal('modal-soon', {
        title: 'Belum tersedia',
        message: 'Edit di ' + tempat + ' belum tersedia. Langkah berikutnya akan dibuat setelah alur scan QR selesai.',
    });
};

document.querySelectorAll('.print-button[data-tempat]').forEach((tombol) => {
    tombol.addEventListener('click', () => {
        bukaPemberitahuan(tombol.dataset.tempat || 'komputer');
    });
});
