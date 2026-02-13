<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable , LogsActivity;

    // protected static $logAttributes = ['*'];
    protected static $recordEvents = ['created','updated','deleted'];

    protected $guard = "admin";
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $fillable = [

        'uuid',
        'first_name',
        'last_name',
        'email',
        'password',
        'role_id',
        'organization',
        'phone',
        'address',
        'state',
        'zipcode',
        'country',
        'language',
        'auth_id',
        'ip',
        'image',
        'status',
        'bio',
        'personal_website',
        'notification',
        'last_login',
        'permission_status'

    ];

    public function getActivitylogOptions(): LogOptions
    {
        
        return LogOptions::defaults()
        ->useLogName('User') // Set custom log name
        ->logOnly(['uuid','first_name','last_name','email','role_id','auth_id','ip','image','created_at','updated_at','deleted_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "User {$eventName} Successfully"); 
        
    }


    public function role()
    {
        return $this->belongsTo(Role::class);
    }


    public function hasPermission($permissionName)
    {
        return $this->role->permissions()->where('permissionkey', $permissionName)->exists();
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */


    protected $hidden = [
        
        'password',
        'remember_token',
        // 'id',
        'ip',
        'email_verified_at',
        'auth_id'

    ];
    

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */


    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    // public function customer()
    // {
    //     return $this->hasOne(Customer::class);
    // }

    public static function findByUuid($uuid)
    {
        return self::where('uuid', $uuid)->first();
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function shop()
    {
        return $this->hasOne(Shop::class);
    }

    public function seller()
    {
        return $this->hasOne(Seller::class);
    }


    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function seller_orders()
    {
        return $this->hasMany(Order::class, "seller_id");
    }
    public function seller_sales()
    {
        return $this->hasMany(OrderDetail::class, "seller_id");
    }

    public function specialPermissions(){
        return $this->hasMany(User_special_permission::class);
    }

    public function customer_package()
    {
        return $this->belongsTo(CustomerPackage::class);
    }

    public function customer_package_payments()
    {
        return $this->hasMany(CustomerPackagePayment::class);
    }

    public function customer_products()
    {
        return $this->hasMany(CustomerProduct::class);
    }

    public function seller_package_payments()
    {
        return $this->hasMany(SellerPackagePayment::class);
    }


    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function billingAddress()
    {
        return $this->belongsTo(Address::class,'user_id')->where('address_type','billing');
    }

    public function shippingAddress()
    {
        return $this->belongsTo(Address::class,'user_id')->where('address_type','shipping');
    }



    public function userCoupon(){
        return $this->hasOne(UserCoupon::class);
    }

    

    public function getInitials()
    {
        return collect(explode(' ', $this->name))->map(function($word) {
            return strtoupper($word[0]);
        })->join('');
    }


}
