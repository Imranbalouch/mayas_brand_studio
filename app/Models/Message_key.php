<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message_key extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'key',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    
}
