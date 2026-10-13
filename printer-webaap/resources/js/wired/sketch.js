// Wired-elements menggambar sketsanya sekali berdasarkan ukuran elemen saat itu
// dan tidak selalu menggambar ulang saat ukurannya berubah (misalnya saat lebar
// layar melewati breakpoint, atau saat daftar file membuat panel jadi lebih tinggi).
// Jadi gambar ulang kita minta dari luar.
const sketsaSelector = 'wired-card, wired-button, wired-input, wired-radio, wired-radio-group, wired-checkbox, wired-combo';
const sketched = document.querySelectorAll(sketsaSelector);

export function refreshSketches() {
    sketched.forEach((el) => {
        if (typeof el.wiredRender === 'function') {
            el.wiredRender(true);
        } else {
            el.requestUpdate?.();
        }
    });
}

if (sketched.length > 0) {
    window.addEventListener('resize', refreshSketches);

    if ('ResizeObserver' in window) {
        new ResizeObserver(refreshSketches).observe(document.body);
    }

    // Ukuran kotak bergantung pada font, jadi sketsa yang digambar sebelum font
    // selesai dimuat bisa tidak sesuai elemennya. Gambar ulang setelah font siap.
    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(refreshSketches);
    }
}
