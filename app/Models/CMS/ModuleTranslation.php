<?php

namespace App\Models\CMS;

use App\Models\CMS\Module;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ModuleTranslation extends Model
{
    use HasFactory;

    protected $table = 'modules_translation';

    protected $fillable = [
        'uuid',
        'theme_id',
        'module_id',
        'language_id',
        'name',
        'shortkey',
        'api_url',
        'moduletype',
        'html_code',
        'moduleclass',
        'lang',
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

    public function module(){
        return $this->belongsTo(Module::class, 'module_id', 'uuid');
    }
}
