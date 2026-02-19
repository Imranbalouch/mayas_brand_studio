<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Wishlist extends Model
{
    protected $guarded = [];
    protected $fillable = ['auth_id','product_id'];
    protected $hidden = ['id'];
    

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'uuid');
    }

     protected static function booted()
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
        });
    }
}