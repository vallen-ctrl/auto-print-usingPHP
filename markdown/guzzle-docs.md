# Dokumentasi Guzzle HTTP Client (PHP)

Guzzle adalah library PHP untuk mengirim HTTP request (GET, POST, PUT, DELETE, dll) ke API atau server lain.

## Daftar Isi

1. [Instalasi](#instalasi)
2. [Setup Dasar](#setup-dasar)
3. [GET Request](#get-request)
4. [POST Request](#post-request)
5. [PUT, PATCH, DELETE](#put-patch-delete)
6. [Headers & Autentikasi](#headers--autentikasi)
7. [Query Parameters](#query-parameters)
8. [Upload File](#upload-file)
9. [Membaca Response](#membaca-response)
10. [Error Handling](#error-handling)
11. [Timeout](#timeout)
12. [Base URI & Konfigurasi Default](#base-uri--konfigurasi-default)
13. [Request Async](#request-async)
14. [Contoh Lengkap](#contoh-lengkap)

---

## Instalasi

Pastikan sudah ada PHP dan Composer, lalu jalankan:

```bash
composer require guzzlehttp/guzzle
```

Folder `vendor/` dan file `composer.json` akan otomatis dibuat.

---

## Setup Dasar

```php
<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;

$client = new Client();
```

---

## GET Request

```php
$response = $client->get('https://api.example.com/users');

echo $response->getStatusCode(); // 200
echo $response->getBody();       // isi response (string)
```

Parse JSON jadi array:

```php
$data = json_decode($response->getBody(), true);
print_r($data);
```

---

## POST Request

### Kirim JSON

```php
$response = $client->post('https://api.example.com/users', [
    'json' => [
        'nama'  => 'Kolens',
        'email' => 'kolens@example.com',
    ],
]);
```

> Opsi `json` otomatis set header `Content-Type: application/json`.

### Kirim Form (x-www-form-urlencoded)

```php
$response = $client->post('https://api.example.com/login', [
    'form_params' => [
        'username' => 'kolens',
        'password' => 'rahasia',
    ],
]);
```

---

## PUT, PATCH, DELETE

```php
// PUT (update seluruh data)
$client->put('https://api.example.com/users/1', [
    'json' => ['nama' => 'Nama Baru'],
]);

// PATCH (update sebagian)
$client->patch('https://api.example.com/users/1', [
    'json' => ['email' => 'baru@example.com'],
]);

// DELETE
$client->delete('https://api.example.com/users/1');
```

---

## Headers & Autentikasi

### Custom Headers

```php
$response = $client->get('https://api.example.com/users', [
    'headers' => [
        'Accept'     => 'application/json',
        'User-Agent' => 'MyApp/1.0',
    ],
]);
```

### Bearer Token

```php
$response = $client->get('https://api.example.com/profile', [
    'headers' => [
        'Authorization' => 'Bearer TOKEN_KAMU',
    ],
]);
```

### API Key

```php
$response = $client->get('https://api.example.com/data', [
    'headers' => [
        'X-Api-Key' => 'API_KEY_KAMU',
    ],
]);
```

### Basic Auth

```php
$response = $client->get('https://api.example.com/secure', [
    'auth' => ['username', 'password'],
]);
```

---

## Query Parameters

```php
$response = $client->get('https://api.example.com/users', [
    'query' => [
        'page'  => 1,
        'limit' => 10,
        'q'     => 'kolens',
    ],
]);
// Hasil URL: https://api.example.com/users?page=1&limit=10&q=kolens
```

---

## Upload File

```php
$response = $client->post('https://api.example.com/upload', [
    'multipart' => [
        [
            'name'     => 'file',
            'contents' => fopen('/path/ke/file.pdf', 'r'),
            'filename' => 'file.pdf',
        ],
        [
            'name'     => 'keterangan',
            'contents' => 'Dokumen penting',
        ],
    ],
]);
```

---

## Membaca Response

```php
$status  = $response->getStatusCode();            // 200
$reason  = $response->getReasonPhrase();          // OK
$headers = $response->getHeaders();               // semua header
$type    = $response->getHeaderLine('Content-Type');
$body    = (string) $response->getBody();         // isi response
$json    = json_decode($body, true);              // parse JSON
```

---

## Error Handling

Secara default, Guzzle melempar exception untuk status 4xx dan 5xx.

```php
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

try {
    $response = $client->get('https://api.example.com/users');
    echo $response->getBody();

} catch (ClientException $e) {
    // Error 4xx (400, 401, 404, dll)
    echo 'Client error: ' . $e->getResponse()->getStatusCode();
    echo $e->getResponse()->getBody();

} catch (ServerException $e) {
    // Error 5xx (500, 502, dll)
    echo 'Server error: ' . $e->getResponse()->getStatusCode();

} catch (ConnectException $e) {
    // Gagal konek (DNS, timeout, server mati)
    echo 'Gagal konek: ' . $e->getMessage();

} catch (RequestException $e) {
    // Error lainnya
    echo 'Request error: ' . $e->getMessage();
}
```

Kalau tidak mau exception dilempar untuk 4xx/5xx:

```php
$response = $client->get('https://api.example.com/users', [
    'http_errors' => false,
]);

if ($response->getStatusCode() !== 200) {
    echo 'Gagal: ' . $response->getStatusCode();
}
```

---

## Timeout

```php
$response = $client->get('https://api.example.com/users', [
    'timeout'         => 10, // maksimal total waktu request (detik)
    'connect_timeout' => 5,  // maksimal waktu untuk konek (detik)
]);
```

---

## Base URI & Konfigurasi Default

Biar tidak ngetik URL panjang berulang kali:

```php
$client = new Client([
    'base_uri' => 'https://api.example.com/',
    'timeout'  => 10,
    'headers'  => [
        'Accept'        => 'application/json',
        'Authorization' => 'Bearer TOKEN_KAMU',
    ],
]);

// Tinggal tulis path-nya saja
$client->get('users');
$client->post('users', ['json' => ['nama' => 'Kolens']]);
$client->delete('users/1');
```

---

## Request Async

Kirim beberapa request secara paralel:

```php
use GuzzleHttp\Promise\Utils;

$promises = [
    'users'    => $client->getAsync('users'),
    'products' => $client->getAsync('products'),
];

$results = Utils::unwrap($promises);

echo $results['users']->getBody();
echo $results['products']->getBody();
```

---

## Contoh Lengkap

Class sederhana sebagai API wrapper:

```php
<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ApiService
{
    private Client $client;

    public function __construct(string $token)
    {
        $this->client = new Client([
            'base_uri' => 'https://api.example.com/',
            'timeout'  => 10,
            'headers'  => [
                'Accept'        => 'application/json',
                'Authorization' => "Bearer $token",
            ],
        ]);
    }

    public function getUsers(int $page = 1): array
    {
        return $this->request('GET', 'users', ['query' => ['page' => $page]]);
    }

    public function createUser(array $data): array
    {
        return $this->request('POST', 'users', ['json' => $data]);
    }

    private function request(string $method, string $uri, array $options = []): array
    {
        try {
            $response = $this->client->request($method, $uri, $options);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return [
                'error'   => true,
                'message' => $e->getMessage(),
                'status'  => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null,
            ];
        }
    }
}

// Pemakaian
$api = new ApiService('TOKEN_KAMU');
print_r($api->getUsers(1));
print_r($api->createUser(['nama' => 'Kolens', 'email' => 'kolens@example.com']));
```

---

## Referensi

- Dokumentasi resmi: https://docs.guzzlephp.org
