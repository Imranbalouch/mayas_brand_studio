<?php

namespace App\Models\Ecommerce;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserCoupon extends Model
{
    public $timestamps = false;
    use HasFactory;

    public function user(){
    	return $this->belongsTo(User::class);
    }

    public function coupon(){
    	return $this->belongsTo(Coupon::class);
    }
}
