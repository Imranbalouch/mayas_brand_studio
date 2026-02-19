<?php

namespace App\Models\Ecommerce;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MasterImportProductLog extends Model
{
    use HasFactory;

    protected $table = 'master_import_product_logs';

    protected $fillable = [
        'uuid',
        'auth_id',
        'excel_file_name',
        'queue_status',
        'product_status'
    ];

    protected $appends = ['pass_count', 'fail_count','total_count'];

    public function importProductLogs()
    {
        return $this->hasMany(ImportProductLog::class, 'master_import_id', 'uuid');
    }

    public function importProductLogsPass()
    {
        return $this->hasMany(ImportProductLog::class, 'master_import_id', 'uuid')->where('status', 'pass');
    }

    public function getPassCountAttribute()
    {
        return $this->importProductLogsPass->count();
    }
    public function importProductLogsFail()
    {
        return $this->hasMany(ImportProductLog::class, 'master_import_id', 'uuid')->where('status', 'fail');
    }

    public function getFailCountAttribute()
    {
        return $this->importProductLogsFail->count();
    }

    public function importProductLogsTotal()
    {
        return $this->hasMany(ImportProductLog::class, 'master_import_id', 'uuid');
    }

    
    public function getTotalCountAttribute()
    {
        return $this->importProductLogsTotal->count();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'auth_id', 'uuid');
    }
}
