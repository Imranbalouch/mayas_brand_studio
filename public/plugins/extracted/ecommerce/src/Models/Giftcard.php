<?php

namespace App\Models\Ecommerce;

use App\Models\User;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Giftcard extends Model
{
    
    use HasFactory , LogsActivity;

    protected $fillable = [
        'uuid',
        'giftcard',
        'code',
        'balance',
        'value',
        'customer_id',
        'note',
        'auth_id',
        'status',
         ];


    protected $hidden = [
        // 'id',
        'auth_id',
    ];

 
    

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Giftcard') // Set custom log name
        ->logOnly(['uuid','giftcard','code','value','customer_id','note','auth_id','created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Language {$eventName} successfully"); 
    }

    public function giftcard_translations()
    {
        return $this->hasMany(Giftcard_translation::class);
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'uuid');
    } 
     public function user()
    {
        return $this->belongsTo(User::class, 'auth_id', 'auth_id');
    }
    public function giftcard_timeline()
    {
        return $this->hasMany(GiftCardTimeLine::class, 'giftcard_id', 'uuid');
    }
}
