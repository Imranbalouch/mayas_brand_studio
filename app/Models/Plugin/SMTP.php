<?php

namespace App\Models\Plugin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
class SMTP extends Model
{
    use HasFactory;

    protected $table = 'smtp';

    protected $fillable = [
        'uuid',
        'host',
        'port',
        'encryption',
        'username',
        'password',
        'from_address',
        'from_name',
        'active',
        'auth_id'
    ];

    protected $hidden = [
        'id',
        'auth_id',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
            $model->auth_id = Auth::user()->uuid;
        });
        static::updating(function ($model) {    
            $model->auth_id = Auth::user()->uuid;
        });
    }
}
