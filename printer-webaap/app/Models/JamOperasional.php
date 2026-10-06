<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JamOperasional extends Model
{
    use HasFactory;

    protected $table = 'jam_operasional';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'nomor_hari',
        'hari',
        'waktu_buka',
        'waktu_tutup',
        'apakah_dibuka',
        'id_pengaturan',
    ];
}

