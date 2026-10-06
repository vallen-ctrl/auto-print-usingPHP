<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Toko extends Model
{
    use HasFactory;

    protected $table = 'toko';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'binary';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'username',
        'password',
        'token',
    ];

    protected $casts = [
        'tanggal_dibuat' => 'datetime',
        'tanggal_update' => 'datetime',
    ];
}

