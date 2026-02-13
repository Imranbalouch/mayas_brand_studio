<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class CategoryTranslation extends Model
{
    use HasFactory , LogsActivity;

    protected $fillable = ['name', 'lang', 'category_id'];

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
 
}
