<?php

namespace App\Models\CMS;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class WidgetTranslation extends Model
{
    use HasFactory;

    protected $table = 'widget_translation';

    protected $fillable = [
        'uuid',
        'widget_uuid',
        'name',
        'html_code',
        'default_data',
        'language_id',
        'lang',
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

    public function widget()
    {
        return $this->belongsTo(Widget::class);
    }
}
