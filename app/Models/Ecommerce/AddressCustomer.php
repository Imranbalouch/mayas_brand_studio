<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AddressCustomer extends Model
{
    use HasFactory;

    protected $table = 'address';

    protected $fillable = [
        'uuid',
        'auth_id',
        'customer_id',
        'company_id',
        'type',
        'country',
        'country_uuid',
        'city_uuid',
        'is_default',
        'address_first_name',
        'address_last_name',
        'address_email',
        'company',
        'address',
        'apartment',
        'postal_code',
        'city',
        'state',
        'address_phone',
    ];
    
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'uuid');
    }
}
