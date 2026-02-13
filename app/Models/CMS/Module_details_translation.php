<?php

namespace App\Models\CMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Module_details_translation extends Model
{
    use HasFactory;

    protected $table = 'module_detail_translation';

    protected $fillable = [
        'uuid',
        'module_detail_id',
        'language_id',
        'details',
        'view',
        'lang',
        'auth_id',
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

    public function module_detail(){
        return $this->belongsTo(Module_details::class,'module_detail_id', 'uuid');
    }
}
