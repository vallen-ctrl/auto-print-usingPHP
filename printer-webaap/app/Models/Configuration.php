<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    use HasFactory;

    protected $table = 'configuration';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'file_id',
        'orientasi',
        'warna',
        'ukuran',
        'kualitas',
        'skala',
        'jumlah_cetak',
        'custom_halaman',
        'catatan_tambahan',
        'paper_id',
        'pages_toprint',
    ];
}

