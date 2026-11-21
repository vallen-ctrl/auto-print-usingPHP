<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $branding['nama'] }} - Scan QR untuk Cetak</title>
    <x-favicon />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Patrick+Hand&display=swap" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>

    @vite(['resources/css/qrtoken.css', 'resources/js/pages/qrtoken.js'])
</head>

<body>
    <main class="qr-main">
        <wired-card roughness="1.2" elevation="1" class="qr-card">
            <div class="qr-brand">
                <x-brand-logo />
                <p class="brand-name">{{ $branding['nama'] }}</p>
            </div>

            <h1 class="qr-title">Scan untuk Mulai Cetak</h1>
            <p class="qr-lead">Buka kamera HP, arahkan ke QR di bawah, lalu ikuti langkahnya.</p>

            <div id="qrcode" class="qr-box" role="img" aria-label="Kode QR untuk membuka halaman cetak"></div>

            <p class="qr-timer" role="timer">
                <span>QR baru dalam</span>
                <strong id="regenerate">60 detik</strong>
            </p>

            <p class="qr-hint" id="qr-hint">
                QR berganti otomatis setiap 60 detik dan setelah dipakai.
            </p>
        </wired-card>
    </main>

    <script>
        const computerToken = "{{ $token }}";
        const timeLimit = 60;
        let timenow = timeLimit;

        const regenerate = document.getElementById("regenerate");
        const hint = document.getElementById("qr-hint");
        const qrElement = document.getElementById("qrcode");

        const qrcode = (text) => new QRCode(qrElement, {
            text: text,
            width: 250,
            height: 250,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.L,
        });

        qrcode("{{ $createToken }}");

        const tick = setInterval(async () => {
            if (timenow-- > 0) {
                regenerate.textContent = `${timenow} detik`;
                return;
            }

            try {
                const res = await fetch('{{ route("computer.token") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ token: computerToken }),
                });

                const data = await res.json();

                if (!res.ok) {
                    throw new Error(data.message || 'Gagal membuat token');
                }

                qrElement.innerHTML = "";
                qrcode(String(data.token));
                hint.textContent = "QR berganti otomatis setiap 60 detik dan setelah dipakai.";
                timenow = timeLimit;
                regenerate.textContent = `${timenow} detik`;
            } catch (error) {
                hint.textContent = "Gagal memperbarui QR. Mencoba lagi sebentar…";
                timenow = 3;
                regenerate.textContent = `${timenow} detik`;
            }
        }, 1000);
    </script>
</body>

</html>
