import 'wired-elements/lib/wired-card.js';
import 'wired-elements/lib/wired-button.js';
import 'wired-elements/lib/wired-checkbox.js';
import 'wired-elements/lib/wired-divider.js';
import 'wired-elements/lib/wired-input.js';
import 'wired-elements/lib/wired-radio.js';
import 'wired-elements/lib/wired-radio-group.js';
import '../wired/sketch.js';
import '../wired/input-label.js';

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
