<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileQueue extends Model
{
    use HasFactory;

    protected $table = 'file_queue';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'status',
        'path',
        'order_id',
        'file_id',
        'computer_id',
    ];

    protected $casts = [
        'tanggal_dibuat' => 'datetime',
    ];
}

