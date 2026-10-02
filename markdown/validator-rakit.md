# rakit/validation: Daftar Rules

Install: `composer require rakit/validation`

```php
use Rakit\Validation\Validator;

$validator  = new Validator;
$validation = $validator->make($_POST + $_FILES, [
    'name'  => 'required',
    'email' => 'required|email',
    'age'   => 'required|numeric|min:17',
    'photo' => 'uploaded_file:0,2M,jpeg,png',
]);

$validation->validate();   // wajib dipanggil sebelum fails()/passes()

if ($validation->fails()) {
    print_r($validation->errors()->firstOfAll());
}
```

Rule bisa ditulis `'required|email'` atau array `['required', 'email']`.

---

## 1. Required & Presence

| Rule | Fungsi |
|---|---|
| `required` | Wajib ada dan tidak kosong |
| `required_if:field,val1,val2` | Wajib kalau field lain bernilai tertentu |
| `required_unless:field,val1,val2` | Wajib kecuali field lain bernilai tertentu |
| `required_with:f1,f2` | Wajib kalau salah satu field lain ada |
| `required_with_all:f1,f2` | Wajib kalau semua field lain ada |
| `required_without:f1,f2` | Wajib kalau salah satu field lain tidak ada |
| `required_without_all:f1,f2` | Wajib kalau semua field lain tidak ada |
| `present` | Key harus ada (boleh kosong) |
| `nullable` | Boleh null |
| `accepted` | Harus `yes`, `on`, `1`, `true` (cocok untuk checkbox ToS) |
| `default:value` / `defaults:value` | Isi nilai default kalau kosong |

## 2. Tipe & Format

| Rule | Fungsi |
|---|---|
| `email` | Format email valid |
| `url` | Format URL valid |
| `ip` / `ipv4` / `ipv6` | Alamat IP valid |
| `json` | String JSON valid |
| `numeric` | Angka (termasuk desimal) |
| `integer` | Bilangan bulat |
| `boolean` | `true`/`false`/`1`/`0`/`"1"`/`"0"` |
| `array` | Harus array |
| `alpha` | Huruf saja |
| `alpha_num` | Huruf dan angka |
| `alpha_dash` | Huruf, angka, `-`, `_` |
| `alpha_spaces` | Huruf dan spasi |
| `lowercase` | Huruf kecil semua |
| `uppercase` | Huruf besar semua |
| `regex:/pattern/` | Harus cocok dengan regex |
| `not_regex:/pattern/` | Tidak boleh cocok dengan regex |
| `date:format` | Tanggal valid, contoh `date:Y-m-d` |

## 3. Ukuran & Nilai

| Rule | Fungsi |
|---|---|
| `min:n` | Minimal (angka: nilai, string: panjang, array: jumlah item) |
| `max:n` | Maksimal (sama seperti `min`) |
| `between:min,max` | Di antara min dan max |
| `digits:n` | Tepat n digit |
| `digits_between:min,max` | Jumlah digit di antara min dan max |
| `in:a,b,c` | Harus salah satu nilai |
| `not_in:a,b,c` | Tidak boleh salah satu nilai |

## 4. Perbandingan Antar Field

| Rule | Fungsi |
|---|---|
| `same:field` | Sama dengan field lain (konfirmasi password) |
| `different:field` | Beda dengan field lain |
| `after:tanggal` | Tanggal setelah, contoh `after:tomorrow` |
| `before:tanggal` | Tanggal sebelum, contoh `before:yesterday` |

## 5. File Upload

| Rule | Fungsi |
|---|---|
| `uploaded_file:min,max,types` | Validasi file upload, contoh `uploaded_file:0,500K,png,jpeg` |
| `mimes:jpeg,png` | Cek MIME type |
| `extension:jpg,png` | Cek ekstensi file |

## 6. Custom

| Rule | Fungsi |
|---|---|
| `callback` | Closure sendiri |

---

## Fitur Lain

### Custom pesan error

```php
$validation = $validator->make($data, $rules, [
    'required'       => ':attribute wajib diisi',
    'email'          => ':attribute tidak valid',
    'age:min'        => 'Umur minimal :min tahun',
    'email:required' => 'Email jangan kosong ya',
]);
```

Format `field:rule` untuk pesan khusus per field.

### Alias nama field

```php
$validation->setAliases([
    'email' => 'Alamat Email',
    'name'  => 'Nama Lengkap',
]);
```

### Validasi array (dot notation & wildcard)

```php
$rules = [
    'items.*.name' => 'required',
    'items.*.qty'  => 'required|numeric|min:1',
    'user.email'   => 'required|email',
];
```

### Rule custom pakai callback

```php
'password' => [
    'required',
    function ($value) {
        return strlen($value) >= 8 ? true : ':attribute minimal 8 karakter';
    },
],
```

### Rule custom pakai class

```php
use Rakit\Validation\Rule;

class UniqueRule extends Rule
{
    protected $message = ":attribute :value sudah dipakai";
    protected $fillableParams = ['table', 'column'];

    public function check($value): bool
    {
        $this->requireParameters(['table', 'column']);
        // cek DB di sini
        return true;
    }
}

$validator->addValidator('unique', new UniqueRule);
// pakai: 'email' => 'required|unique:users,email'
```

### Override rule bawaan

```php
$validator->allowRuleOverride(true);
```

### Terjemahan pesan (Bahasa Indonesia)

```php
$validator->setTranslations([
    'and' => 'dan',
    'or'  => 'atau',
]);
$validator->setMessages([
    'required' => ':attribute wajib diisi',
    'email'    => ':attribute harus berupa email valid',
]);
```

### Humanized key

```php
$validator->setUseHumanizedKeys(true); // 'first_name' jadi 'First name'
```

---

## Method Hasil Validasi

```php
$validation->passes();          // true kalau lolos
$validation->fails();           // true kalau gagal
$validation->getValidData();    // data yang valid saja
$validation->getInvalidData();  // data yang tidak valid

$errors = $validation->errors();
$errors->all();                 // semua pesan error (flat)
$errors->firstOfAll();          // 1 error pertama per field
$errors->first('email');        // error pertama field tertentu
$errors->get('email');          // semua error field tertentu
$errors->has('email');          // cek field ada error atau tidak
$errors->count();               // jumlah error
$errors->toArray();             // array lengkap, cocok untuk JSON
```

> `validate()` wajib dipanggil sebelum `fails()` / `passes()`.
> Alternatif sekali jalan: `$validator->validate($data, $rules)`.