<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $table = "companies";

    protected $fillable = [
        'uuid',
        'auth_id',
        'company_name',
        'company_id',
        'main_contact_id',
        'address_id',
        'location_id',
        'catalogs_id',
        'payment_terms_id',
        'deposit',
        'ship_to_address',
        'order_submission',
        'tax_id',
        'tax_setting',
        'approved',
    ];
    
    public function addresses()
    {
        return $this->hasMany(AddressCustomer::class, 'company_id', 'uuid');
    }

}