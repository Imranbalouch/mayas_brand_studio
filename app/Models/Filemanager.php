<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
class Filemanager extends Model
{
    use LogsActivity;
    use HasFactory;
    protected $table = "filemanagers";
    protected $appends = ['uploaded_image','uploaded_image_size'];
    protected $fillable = [
        'uuid',
        'auth_id',
        'theme_id',
        'theme_path',
        'file_original_name',
        'file_name',
        'created_by',
        'file_size',
        'extension',
        'type',
        'external_link',
        'height',
        'width'
    ];
    public $timestamps = true;

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
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('filemanager')->logOnly(['*']);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getUploadedImageAttribute()
    {
        return asset($this->file_name);
    }

    public function getUploadedImageSizeAttribute()
    {
        $bytes = $this->file_size;
        $precision = 2;
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        // Uncomment one of the following alternatives
        $bytes /= pow(1024, $pow);
        // $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public static function findByUuid($uuid)
    {
        return self::where('uuid', $uuid)->first();
    }
}
