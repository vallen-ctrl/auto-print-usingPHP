<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script type="text/javascript"
        src="https://app.stg.midtrans.com/snap/snap.js"
        data-client-key="{{config('midtrans.')}}"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <input id="orderid" type="text" placeholder="order id">
    <button id="pay-button">Pay!</button>
    <div id="snap-container"></div>

    <script src="https://app.sandbox.midtrans.com/snap/snap.js"
        data-client-key="{{ config('midtrans.client_key') }}"></script>

    <script>
        const orderid = document.getElementById('orderid');
        const payButton = document.getElementById('pay-button');

        payButton.addEventListener('click', async () => {
            const res = await fetch('{{ route("create.snap.token") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    orderid: orderid.value
                }),
            });

            const data = await res.json();

            if (!res.ok) {
                console.error('Gagal bikin token', data);
                return;
            }


            window.snap.pay(data.token, {
                onSuccess: (result) => console.log('success', result),
                onPending: (result) => console.log('pending', result),
                onError: (result) => console.log('error', result),
                onClose: () => console.log('popup ditutup'),
            });
        });
    </script>
</body>

</html>