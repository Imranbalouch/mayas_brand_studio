<?php

namespace App\Models\Ecommerce;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderTimeLine extends Model
{
    use HasFactory;
    protected $table = 'order_timeline';

    protected $fillable = [
        'uuid',
        'auth_id',
        'order_id',
        'message',
        'status',
        'created_at',
        'updated_at',
    ];
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'uuid');
    }

    /**
     * Get the user who created the comment
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'auth_id', 'uuid');
    }
}
