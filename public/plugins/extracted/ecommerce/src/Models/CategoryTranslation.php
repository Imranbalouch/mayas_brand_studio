<?php

namespace App\Models\Ecommerce;

use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class CategoryTranslation extends Model
{
    use HasFactory , LogsActivity;

    protected $fillable = ['uuid','name','parent_id','level','order_level','featured', 'lang', 'category_uuid', 'language_id', 'banner', 'icon', 'cover_image', 'meta_title', 'meta_description', 'og_title', 'og_description', 'og_image', 'x_title', 'x_description', 'x_image', 'auth_id'];

    protected static $recordEvents = ['created','updated','deleted'];

    public function category(){
    	return $this->belongsTo(Category::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Category Translation') // Set custom log name
        ->logOnly(['name', 'lang', 'category_id'])
        ->setDescriptionForEvent(fn(string $eventName) => "Category Translation {$eventName} successfully"); 
    }

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
