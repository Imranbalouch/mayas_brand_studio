<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CollectionTranslation extends Model
{
    use HasFactory;

    protected $table = 'collection_translation';


      protected $fillable = [
        'uuid',
        'collection_uuid',
        'language_id',
        'lang',
        'name',
        'description',
        'image',
        'meta_title',
        'meta_description',
        'auth_id',
        'status',
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

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }
}
