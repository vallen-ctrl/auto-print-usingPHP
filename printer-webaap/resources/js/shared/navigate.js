// Tombol diberi data-href dari Blade (mis. {{ route('upload.terjadwal') }}).
document.querySelectorAll('[data-href]').forEach((el) => {
    el.addEventListener('click', () => {
        window.location.href = el.dataset.href;
    });
});
