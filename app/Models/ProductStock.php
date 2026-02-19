<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStock extends Model
{
    use HasFactory;

    protected $fillable = ['uuid', 'product_id','location_id','product_name', 'variant', 'sku','variant_sku','barcode','hs_code','cost_per_item','compare_price', 'price', 'qty', 'image','auth_id'];
     
    public function product()
    {
        return $this->belongsTo(Product::class,'product_id', 'uuid');
    }

    public function inventory() {
        return $this->hasMany(Inventory::class,'stock_id','uuid');
    }

    public function location() {
        return $this->hasMany(WarehouseValues::class,'warehouse_id','uuid');
    }
 

    public function warehouse() {
        return $this->hasMany(WarehouseValues::class,'uuid','location_id');
    }
 
    // In the Inventory model
    public function getNetUnavailableQty() {
        // Sum all unavailable quantities (excluding add operations)
        $unavailableQty = $this->inventory
            ->where('status', 'unavailable')
            ->whereNotIn('reason', ['add_damaged', 'add_qualitycontrol', 'add_safetystock', 'add_other'])
            ->sum('qty');
        
        // Sum all "add" operations to unavailable
        $addUnavailableQty = $this->inventory
            ->where('status', 'unavailable')
            ->whereIn('reason', ['add_damaged', 'add_qualitycontrol', 'add_safetystock', 'add_other'])
            ->sum('qty');
        
        // Subtract deleted quantities
        $deleteQty = $this->inventory
            ->where('status', 'delete') 
            ->sum('qty');
        
        // Subtract quantities moved to available
        $movedToAvailableQty = $this->inventory
            ->where('status', 'available')
            ->whereIn('reason', ['damaged', 'qualitycontrol', 'safetystock', 'other'])
            ->sum('qty');

        return ($unavailableQty + $addUnavailableQty) - ($deleteQty + $movedToAvailableQty);
    }

    public function getNetAvailableQty() { 
        $unavailableQty = $this->inventory->where('status', 'unavailable')->whereNotIn('reason', ['add_damaged', 'add_qualitycontrol', 'add_safetystock', 'add_other'])->sum('qty');  
        $availableQty = $this->inventory->where('status', 'available')->sum('qty');
        $openingQty = $this->inventory->where('status', 'opening')->sum('qty'); 
        $adjustQty = $this->inventory->where('status', 'adjust')->sum('qty'); 
        $committedQty = $this->inventory->where('status', 'committed')->where('reason', 'sale')->sum('qty');  
       // dd($committedQty);
         //$deletedQty = $this->inventory->where('status', 'delete')->sum('qty'); 
         //dd($unavailableQty,$availableQty,$openingQty,$deletedQty,$adjustQty);
         //dd($availableQty+$openingQty+$adjustQty);
        // dd($unavailableQty + $deletedQty);   
        // dd($unavailableQty);
        //dd($deletedQty);
        return ($availableQty+$openingQty+$adjustQty) -  $unavailableQty -$committedQty;//($unavailableQty + $deletedQty);
        // return ($availableQty+$openingQty+$adjustQty) -  $unavailableQty;//($unavailableQty + $deletedQty);
    }

    public function getNetCommittedQty() {
        $availableQty = $this->inventory->where('status','committed');
        // $unavailableQty = $this->inventory_available->where('status', 'unavailable')->sum('qty');
        return $availableQty;
    }

    public function getNetDamagedQty() {
        $unavailable = $this->inventory
            ->where('status', 'unavailable')
            ->where('reason', 'damaged')
            ->sum('qty');
        
        $added = $this->inventory
            ->where('status', 'unavailable')
            ->where('reason', 'add_damaged')
            ->sum('qty');
        
        $deleted = $this->inventory
            ->where('status', 'delete')
            ->where('reason', 'damaged')
            ->sum('qty');
        
        $movedToAvailable = $this->inventory
            ->where('status', 'available')
            ->where('reason', 'damaged')
            ->sum('qty');

        return ($unavailable + $added) - ($deleted + $movedToAvailable);
    }

    public function getNetQualityControlQty() {
        $unavailable = $this->inventory
            ->where('status', 'unavailable')
            ->where('reason', 'qualitycontrol')
            ->sum('qty');
        
        $added = $this->inventory
            ->where('status', 'unavailable')
            ->where('reason', 'add_qualitycontrol')
            ->sum('qty');
        
        $deleted = $this->inventory
            ->where('status', 'delete')
            ->where('reason', 'qualitycontrol')
            ->sum('qty');
        
        $movedToAvailable = $this->inventory
            ->where('status', 'available')
            ->where('reason', 'qualitycontrol')
            ->sum('qty');

        return ($unavailable + $added) - ($deleted + $movedToAvailable);
    }
    public function getNetSafetyStockQty() {
        $unavailable = $this->inventory
            ->where('status', 'unavailable')
            ->where('reason', 'safetystock')
            ->sum('qty');
        
        $added = $this->inventory
            ->where('status', 'unavailable')
            ->where('reason', 'add_safetystock')
            ->sum('qty');
        
        $deleted = $this->inventory
            ->where('status', 'delete')
            ->where('reason', 'safetystock')
            ->sum('qty');
        
        $movedToAvailable = $this->inventory
            ->where('status', 'available')
            ->where('reason', 'safetystock')
            ->sum('qty');

        return ($unavailable + $added) - ($deleted + $movedToAvailable);
    }

    public function getNetOtherQty() {
        $unavailable = $this->inventory
            ->where('status', 'unavailable')
            ->where('reason', 'other')
            ->sum('qty');
        
        $added = $this->inventory
            ->where('status', 'unavailable')
            ->where('reason', 'add_other')
            ->sum('qty');
        
        $deleted = $this->inventory
            ->where('status', 'delete')
            ->where('reason', 'other')
            ->sum('qty');
        
        $movedToAvailable = $this->inventory
            ->where('status', 'available')
            ->where('reason', 'other')
            ->sum('qty');

        return ($unavailable + $added) - ($deleted + $movedToAvailable);
    }

    public function get_deletedQty(){
        $deleteUnavailableQty = $this->inventory
        ->where('status', 'delete') 
        ->sum('qty'); 
        return $deleteUnavailableQty;
    } 

    public function inventory_on_hand() {
        $netQty = $this->getNetUnavailableQty();
        $availableQty = $this->getNetAvailableQty();
        $deletedQty = $this->get_deletedQty();
        return ($netQty + $availableQty) ;
    }


}
