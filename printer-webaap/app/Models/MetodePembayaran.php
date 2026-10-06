<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetodePembayaran extends Model
{
    use HasFactory;

    protected $table = 'metode_pembayaran';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'binary';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'nama',
        'webhook',
        'token',
        'image_path',
        'id_pengaturan',
    ];
}

