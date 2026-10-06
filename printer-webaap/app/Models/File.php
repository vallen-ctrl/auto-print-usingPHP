<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;

    protected $table = 'files';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'binary';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'path',
        'size',
        'mime',
        'diupload_oleh',
        'total_pages',
        'order_id',
        'dihapus',
        'subtotal',
        'layanan',
    ];

    protected $casts = [
        'tanggal_dibuat' => 'datetime',
        'dihapus' => 'boolean',
    ];
}

