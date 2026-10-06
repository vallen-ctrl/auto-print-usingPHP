<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LayananTerpilih extends Model
{
    use HasFactory;

    protected $table = 'layanan_terpilih';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'files_id',
        'layanan_id',
        'nama_layanan',
    ];
}

