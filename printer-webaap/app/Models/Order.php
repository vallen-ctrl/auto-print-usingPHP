<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'binary';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'tanggal_ambil',
        'biaya_admin',
        'total_pembayaran',
        'total_dibayar',
        'pengaturan_id',
        'status_pembayaran',
        'metode_pembayaran',
        'id_metode_pembayaran',
        'session_id',
        'tanggal_bayar',
    ];

    protected $casts = [
        'tanggal_pesan' => 'datetime',
        'tanggal_ambil' => 'datetime',
        'tanggal_bayar' => 'datetime',
    ];
}

