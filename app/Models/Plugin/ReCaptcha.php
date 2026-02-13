<?php

namespace App\Models\Plugin;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
class ReCaptcha extends Model
{
    use HasFactory;

    protected $table = 'recaptcha';
    protected $fillable = [
        'uuid',
        'site_key',
        'secret_key',
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
