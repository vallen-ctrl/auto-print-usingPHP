<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Status Pembayaran</title>
<style>
  * { box-sizing: border-box; }
  body {
    margin: 0;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #0f172a;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: #e2e8f0;
  }
  .card {
    width: 100%;
    max-width: 420px;
    margin: 20px;
    padding: 40px 32px;
    background: #1e293b;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,.45);
    text-align: center;
  }
  .spinner {
    width: 56px;
    height: 56px;
    margin: 0 auto 28px;
    border: 4px solid rgba(148,163,184,.25);
    border-top-color: #38bdf8;
    border-radius: 50%;
    animation: spin 0.9s linear infinite;
  }
  @keyframes spin { to { transform: rotate(360deg); } }
  .status-text {
    font-size: 18px;
    font-weight: 600;
    line-height: 1.5;
    min-height: 54px;
    transition: color .3s ease;
  }
  .order-id {
    margin-top: 24px;
    font-size: 12px;
    color: #64748b;
    word-break: break-all;
  }
  .done .spinner {
    border-color: #22c55e;
    border-top-color: #22c55e;
    animation: none;
    position: relative;
  }
  .done .spinner::after {
    content: "✓";
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: #22c55e;
  }
  .done .status-text { color: #4ade80; }
  .error .spinner {
    border-color: #ef4444;
    border-top-color: #ef4444;
    animation: none;
    position: relative;
  }
  .error .spinner::after {
    content: "!";
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: #ef4444;
  }
  .error .status-text { color: #f87171; }
</style>
</head>
<body>
  <div class="card" id="card">
    <div class="spinner"></div>
    <div class="status-text" id="statusText">Memferivikasi pembayaran…</div>
    <div class="order-id" id="orderId"></div>
  </div>

<script>
(function() {
    const STATUS_FLOW = [
        "Memferivikasi pembayaran",
        "Menunggu pembayaran",
        "Mendapatkan pembayaran",
        "Pembayaran berhasil diverifikasi"
    ];

    const POLL_INTERVAL = 3000; // Cek status tiap 3 detik

    const card = document.getElementById("card");
    const statusText = document.getElementById("statusText");
    const orderIdEl = document.getElementById("orderId");

    // 1. Ambil Order ID dari URL Path (contoh: /struk/01a171f...)
    const pathParts = window.location.pathname.split('/');
    const orderId = pathParts[pathParts.length - 1]; 

    if (!orderId || orderId === 'struk' || orderId === '') {
        card.classList.add("error");
        statusText.textContent = "Order ID tidak ditemukan di URL";
        return;
    }

    // 2. URL API
    const API_BASE = window.location.origin;
    const url = API_BASE + "/struk/order/" + encodeURIComponent(orderId);

    orderIdEl.textContent = "Order: " + orderId;

    // 3. Animasi teks loading
    let step = 0;
    statusText.textContent = STATUS_FLOW[0];
    const rotator = setInterval(() => {
        // Hanya muter di 3 teks pertama. Teks ke-4 ("berhasil") hanya muncul jika API balas "paid"
        step = (step + 1) % (STATUS_FLOW.length - 1);
        statusText.textContent = STATUS_FLOW[step];
    }, 2500);

    let stopped = false;

    function finish(success, message) {
        if (stopped) return;
        stopped = true;
        clearInterval(rotator);
        card.classList.remove("done", "error");
        card.classList.add(success ? "done" : "error");
        statusText.textContent = message;
    }

    async function poll() {
        if (stopped) return;

        try {
            const res = await fetch(url, {
                headers: { "Accept": "application/json" },
                cache: "no-store"
            });

            if (!res.ok) {
                if (res.status === 404 || res.status === 410) {
                    finish(false, "Order tidak ditemukan / kedaluwarsa");
                    return;
                }
            } else {
                let data = null;
                try {
                    data = await res.json(); 
                } catch {
                    data = null;
                }

                // Mengambil string dari JSON { "status": "paid" }
                const status = data && data.status ? data.status.toLowerCase() : "";

                if (status === "paid") {
                    finish(true, "Pembayaran berhasil diverifikasi");
                    return;
                }
                
                if (status === "failed" || status === "refunded") {
                    finish(false, "Pembayaran gagal / dikembalikan");
                    return;
                }
                
                // Jika "pending" atau "processing", kode akan lanjut ke bawah (polling lagi)
            }
        } catch (err) {
            console.warn("Koneksi terputus, mencoba lagi...", err);
        }

        if (!stopped) setTimeout(poll, POLL_INTERVAL);
    }

    // Jalankan polling pertama kali
    poll();
})();
</script>
</body>
</html>