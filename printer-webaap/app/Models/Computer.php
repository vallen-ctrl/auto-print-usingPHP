<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Computer extends Model
{
    use HasFactory;

    protected $table = 'computer';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'binary';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'token',
        'toko_id',
        'status',
    ];

    protected $casts = [
        'tanggal_dibuat' => 'datetime',
    ];
}

