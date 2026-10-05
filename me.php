<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <script>
        fetch("http://localhost/public/api/tokoAuth.php", {
                method: "POST", // bisa diganti PUT
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    username: "vallenmalia",
                    password: "vallen271206"
                })
            })
            .then((res) => res.text())
            .then(console.log)
            .catch(console.error);
    </script>
</body>

</html>