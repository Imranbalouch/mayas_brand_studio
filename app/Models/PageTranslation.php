<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PageTranslation extends Model
{
    use HasFactory;
    protected $fillable = [
        'uuid',
        'page_uuid',
        'lang',
        'language_id',
        'description',
        'meta_title',
        'meta_description',
        'auth_id'
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

    public function page()
    {
        return $this->belongsTo(Page::class,'page_uuid','uuid');
    }
}
