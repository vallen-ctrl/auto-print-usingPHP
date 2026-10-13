// <input> milik wired-input ada di dalam shadow DOM, jadi <label for="...">
// tidak otomatis terhubung. Nama labelnya kita salin ke input di dalamnya.
function salinLabelKeInput() {
    document.querySelectorAll('wired-input').forEach((el) => {
        const inner = el.shadowRoot ? el.shadowRoot.querySelector('input') : null;

        if (!inner || inner.getAttribute('aria-label')) {
            return;
        }

        const label = el.id ? document.querySelector('label[for="' + el.id + '"]') : null;

        if (label) {
            inner.setAttribute('aria-label', label.textContent.trim());
        }
    });
}

salinLabelKeInput();
window.addEventListener('load', salinLabelKeInput);
