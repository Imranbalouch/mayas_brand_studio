<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportProductLog extends Model
{
    use HasFactory;

    protected $table = 'import_product_logs';

    protected $fillable = [
        'uuid',
        'auth_id',  
        'master_import_id',
        'product_slug',
        'product_name',
        'status',
        'message',
    ];

    public function masterImportLog()
    {
        return $this->belongsTo(MasterImportProductLog::class, 'master_import_id', 'uuid');
    }
}
