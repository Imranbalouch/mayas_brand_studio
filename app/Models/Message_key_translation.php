<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message_key_translation extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'key_id', 
        'language_id',
        'translation',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

}
