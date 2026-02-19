<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftCardTimeLine extends Model
{
    use HasFactory;

    protected $table = 'giftcard_timeline';

    protected $fillable = [
        'uuid',
        'auth_id',
        'giftcard_id',
        'message',
        'status',
        'created_at',
        'updated_at',
    ];
}
