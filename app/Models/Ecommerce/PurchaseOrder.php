<?php

namespace App\Models\Ecommerce;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Str;

class PurchaseOrder extends Model
{
    use HasFactory,LogsActivity;

    protected $fillable = [
        'uuid',
        'supplier_id',
        'warehouse_id',
        'payment_term_id',
        'supplier_currency_id',
        'ship_date',
        'ship_carrier_id',
        'tracking_number',
        'reference_number',
        'note_to_supplier',
        'tags',
        'status',
        'total_tax',
        'total_amount',
        'total_shipping',
        'auth_id',
        'status',
        'cost_summary'
    ];

    protected $hidden = [
        'id',
        'auth_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->useLogName('Purchase Order') // Set custom log name
        ->logOnly(['uuid', 'name', 'description', 'status', 'auth_id', 'created_at','updated_at'])
        ->setDescriptionForEvent(fn(string $eventName) => "Purchase {$eventName} successfully"); 
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->uuid = Str::uuid();
            $model->auth_id = Auth::user()->uuid;
            $model->po_number = self::generatePONumber(); // Fixed static call
        });
        static::updating(function ($model) {    
            $model->auth_id = Auth::user()->uuid;
        });
    }

    public static function findByUuid($uuid)
    {
        return self::where('uuid', $uuid)->first();
    }

    public static function generatePONumber()
    {
        // Get the last PO number
        $lastPO = PurchaseOrder::orderBy('id', 'desc')->first();

        // Extract the numeric part and increment it
        $lastNumber = $lastPO ? intval(substr($lastPO->po_number, 3)) : 0;
        $nextNumber = $lastNumber + 1;

        // Format the PO number
        return 'PO-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    public function supplier() {
        return $this->belongsTo(Supplier::class,'supplier_id','uuid');
    }

    public function country()
{
    return $this->belongsTo(Country::class, 'country_id', 'uuid');
}


    public function warehouse() {
        return $this->belongsTo(WarehouseValues::class,'warehouse_id','uuid');
    }

    public function paymentterm() {
        return $this->belongsTo(PaymentTerms::class,'payment_term_id','uuid');
    }

    public function currency() {
        return $this->belongsTo(Currency::class,'supplier_currency_id','uuid');
    }

    public function shipcarrier() {
        return $this->belongsTo(Carrier::class,'ship_carrier_id','uuid');
    }

    public function purchaseOrderitems() {
        return $this->hasMany(PurchaseOrderItem::class,'po_id','uuid');
    }

    public function purchaseOrderitemReceiving() {
        return $this->hasMany(POReceiving::class,'po_id','uuid');
    }

    public function po_timeline() {
        return $this->hasMany(POTimeLine::class,'po_id','uuid');
    }

    public function po_comment() {
        return $this->hasMany(PurchaseComments::class,'purchase_order_id','uuid')->with('user:uuid,first_name,last_name,email');
    }
}
