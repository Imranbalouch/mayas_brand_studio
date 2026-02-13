<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderComment extends Model
{
    use HasFactory;
    protected $table = "order_comments";
    protected $fillable = [
        'uuid', 
        'order_id', 
        'auth_id', 
        'body'
    ];

    /**
     * Get the order that owns the comment
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'uuid');
    }

    /**
     * Get the user who created the comment
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'uuid');
    }
}
