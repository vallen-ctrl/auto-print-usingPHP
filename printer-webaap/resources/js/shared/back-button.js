// Kalau tombol punya tujuan tetap (data-href) langsung ke sana. Kalau tidak,
// kembali ke halaman sebelumnya, atau ke data-fallback kalau tidak ada.
const backButton = document.getElementById('btn-kembali');

// Halaman yang memasang data-batal-url menangani kliknya sendiri. Contoh:
// tombol Kembali di konfirmasi pembayaran membatalkan pesanan lebih dulu.
if (backButton && !backButton.dataset.batalUrl) {
    backButton.addEventListener('click', () => {
        if (backButton.dataset.href) {
            window.location.href = backButton.dataset.href;
            return;
        }

        const fromThisSite = document.referrer !== '' && document.referrer.startsWith(window.location.origin);

        if (fromThisSite && window.history.length > 1) {
            window.history.back();
        } else {
            window.location.href = backButton.dataset.fallback || '/';
        }
    });
}
