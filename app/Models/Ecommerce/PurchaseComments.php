<?php

namespace App\Models\Ecommerce;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseComments extends Model
{
    use HasFactory;

    protected $table = "po_comments";

    protected $fillable = [
        'uuid',
        'auth_id',
        'purchase_order_id',
        'body',
    ];

    public function user(){
        return $this->belongsTo(User::class , 'auth_id','uuid');
    }
}
