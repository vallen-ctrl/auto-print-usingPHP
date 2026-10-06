<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengaturanToko extends Model
{
    use HasFactory;

    protected $table = 'pengaturan_toko';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'status_buka',
        'biaya_admin',
        'biaya_layanan',
        'id_toko',
        'lama_file_dihapus',
    ];
}

