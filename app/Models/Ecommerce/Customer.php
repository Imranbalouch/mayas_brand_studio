<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $table = 'customers';

    protected $guard = "customer";
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the address associated with the customer.
     */


    protected $fillable = [
        'uuid',
        'auth_id',
        'name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'image',
        'password',
        'language',
        'tax_setting',
        'notes',
        'tags',
        'address_id', 
        'status',
    ];

    public function address()
    {
        return $this->hasMany(AddressCustomer::class, 'customer_id', 'uuid');
    }

    
    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id', 'uuid');
    }
}
