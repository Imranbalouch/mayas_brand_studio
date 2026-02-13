<?php

namespace App\Models;

use App\Models\CMS\Theme;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'theme_id',
        'title',
        'slug',
        'description',
        'meta_title',
        'meta_description',
        'og_title',
        'og_description',
        'og_image',
        'x_title',
        'x_description',
        'x_image',
        'custom_css',
        'custom_js',
        'status',
        'auth_id',
        'default_header',
        'default_footer',
        'page_type',
        'product_detail'
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

    public static function findByUuid($uuid)
    {
        return self::where('uuid', $uuid)->first();
    }

    public function theme()
    {
        return $this->belongsTo(Theme::class, 'theme_id','uuid');
    }

    public function getTranslation($field = '', $lang = false){
        $lang = $lang == false ? getConfigValue('defaul_lang') : $lang;
        $page_translation = $this->page_translations->where('lang', $lang)->first();
        return $page_translation != null ? $page_translation->$field : $this->$field;
    }

    public function page_translations(){
        return $this->hasMany(PageTranslation::class,'page_uuid','uuid');
    }
}
