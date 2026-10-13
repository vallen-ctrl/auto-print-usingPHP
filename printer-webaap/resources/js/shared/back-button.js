// Kalau tombol punya tujuan tetap (data-href) langsung ke sana. Kalau tidak,
// kembali ke halaman sebelumnya, atau ke data-fallback kalau tidak ada.
const backButton = document.getElementById('btn-kembali');

if (backButton) {
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
