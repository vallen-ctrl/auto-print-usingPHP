// Entry JS bersama proyek.
import 'wired-elements';

// Sub total paket dihitung di browser, bukan di PHP.
const paket = document.querySelectorAll('.paket-checkbox');
const subtotal = document.getElementById('subtotal');

if (paket.length && subtotal) {
    const hitung = () => {
        let total = 0;
        paket.forEach((checkbox) => {
            if (checkbox.checked) {
                total += Number(checkbox.dataset.harga || 0);
            }
        });
        subtotal.textContent = total.toLocaleString('id-ID');
    };

    paket.forEach((checkbox) => checkbox.addEventListener('change', hitung));
    hitung();
}
