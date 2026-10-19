<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    @vite('resources/css/app.css')
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs/qrcode.min.js"></script>
</head>

<body class=" flex justify-center items-center w-full h-dvh">
    <div class="flex flex-col bg-neutral-primary-soft max-w-sm p-6 border border-default rounded-base shadow-xs">
        <div id="qrcode" class=" mx-auto"></div>
        <h5 class="mt-6 mb-2 text-center text-2xl font-semibold tracking-tight text-heading">Scan di sini untuk upload / langsung cetak sekarang</h5>
        <p class="mb-6 text-center text-body">Gambar akan di generate ulang setiap 60 detik / setelah digunakan</p>
        <p class="text-body" id="regenerate">regenrate dalam : </p>
    </div>


    <script>
        // 2. Initialize and configure the QR code
        const computerToken = "{{$token}}"
        const timeLimit = 60;
        let Timenow = timeLimit;
        const regenerate = document.getElementById("regenerate");
        const qrElements = document.getElementById("qrcode")
        const qrcode = (text) => new QRCode(qrElements, {
            text: text, // The URL or text to encode
            width: 250, // Width in pixels
            height: 250, // Height in pixels
            colorDark: "#000000", // QR code dot color
            colorLight: "#ffffff", // Background color
            correctLevel: QRCode.CorrectLevel.L // Error correction level (L, M, Q, H)
        });
        qrcode("{{$createToken}}");


        const inver = setInterval(async () => {
            if (Timenow-- <= 0) {
                try{

                    const res = await fetch('{{ route("computer.token") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            token: computerToken
                        }),
                    });
    
                    const data = await res.json();
    
                    if (!res.ok) {
                        console.error('Gagal bikin token', data);
                        return;
                    }
    
                    const token = data.token
    
                    qrElements.innerHTML = "";
                    qrcode(String(token));
                    Timenow = timeLimit;
                }catch{
                    alert("eror while fetch to server")
                    Timenow = timeLimit;
                }
            }
            regenerate.textContent = `regenrate dalam : ${Timenow}`;
        }, 1000);
    </script>

    <script src="https://cdn.jsdelivr.net/npm/flowbite@4.0.1/dist/flowbite.min.js"></script>
</body>

</html>