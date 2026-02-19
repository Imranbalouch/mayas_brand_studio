<?php

namespace App\Models;

use App\Http\Resources\ProductResource;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use LogsActivity;
    use HasFactory;
    
    protected $fillable = [
        'id',
        'uuid',
        'name',
        'auth_id',
        'warehouse_location_id',
        'thumbnail_img',
        'images',
        'tags',
        'description',
        'short_description',
        'unit_price',
        'attributes',
        'choice_options',
        'todays_deal',
        'published',
        'approved',
        'stock_visibility_state',
        'cash_on_delivery',
        'featured',
        'current_stock',
        'unit',
        'weight',
        'min_qty',
        'discount',
        'discount_type',
        'discount_start_date',
        'discount_end_date',
        'tax',
        'tax_type',
        'shipping_type',
        'shipping_cost',
        'meta_title',
        'meta_description',
        'meta_img',
        'pdf',
        'slug',
        'rating',
        'barcode',
        'digital',
        'auction_product',
        'wholesale_product',
        'product_top',
        'sort',
        'tax_enabled',
        'inventory_track_enabled',
        'selling_stock_enabled',
        'sku_barcode_enabled',
        'physical_product_enabled',
        'varient_market_location',
        'location_stock',
        'varient_data',
        'varient_data_view',
        'giftcard_product_id',
        'product_type',
        'status',
        'compare_price',
        'cost_per_item',
        'country_id',
        'vendor',
        'type',
        'hs_code',
        'published_date_time',
        'vat_id',

    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            Cache::flush();
            //    Cache::forget("filter_limit"); // Clear cache if needed  
            // if ($model->status == 1) {
            //     self::generateJson();
            // }
        });
        static::updating(function ($model) {
            Cache::flush();
            // Cache::forget("filter_limit"); // Clear cache if needed    
            // if ($model->status == 1) {
            //     self::generateJson();
            // }
        });
        static::deleting(function ($model) {
            Cache::flush();
            // Cache::forget("filter_limit"); // Clear cache if needed    
            // if ($model->status == 1) {
            //     self::generateJson();
            // }
        });
    }

    public function flashDealProducts()
    {
        return $this->hasMany(FlashDealProduct::class, 'product_id', 'uuid');
    }

    public function productStocks()
    {
        return $this->hasMany(ProductStock::class, 'product_id', 'uuid');
    }

    public function productInventories()
    {
        return $this->hasMany(Inventory::class, 'product_id', 'uuid');
    }

    public function totalVariations()
    {
        return $this->productStocks->where('variant', '!=', '')->unique('variant')->count();
    }
    public function totalStock()
    {
        return $this->productStocks->sum('qty');
    }

    public function totalInventory()
    {
        return $this->productInventories->sum('qty');
    }

    public function productTaxes()
    {
        return $this->hasMany(ProductTax::class, 'product_id');
    }

    public function productTranslations()
    {
        return $this->hasMany(ProductTranslation::class, 'product_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'uuid');
    }

    public function discounts()
    {
        return $this->hasMany(ProductDiscounts::class, 'product_id', 'uuid');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'uuid');
    }
    public function country()
    {
        return $this->belongsTo(Country::class, 'product_id', 'uuid');
    }
    public function productCountry()
    {
        return $this->belongsTo(Country::class, 'country_id', 'uuid');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_categories')->withPivot('product_uuid', 'category_uuid');
    }
    public function categories_simple()
    {
        return $this->belongsToMany(Category::class, 'product_categories', 'product_id', 'category_id');
    }
    public function collections()
    {
        return $this->belongsToMany(Collection::class, 'product_collections')->withPivot('product_uuid', 'collection_uuid');
    }

    public function collections_simple()
    {
        return $this->belongsToMany(Collection::class, 'product_collections', 'product_id', 'collection_id');
    }

    public function firstCollection_simple()
    {
        return $this->belongsToMany(Collection::class, 'product_collections', 'product_id', 'collection_id')
            ->orderBy('name', 'asc')
            ->take(1);
    }
    public function firstCollection()
    {
        return $this->belongsToMany(Collection::class, 'product_collections', 'product_uuid', 'collection_uuid', 'uuid', 'uuid')
            ->orderBy('name', 'asc')
            ->take(1);
    }
    public function firstCategory()
    {
        return $this->belongsToMany(Category::class, 'product_categories', 'product_uuid', 'category_uuid', 'uuid', 'uuid')
            ->orderBy('name', 'asc')
            ->take(1);
    }

    public function firstCategory_simple()
    {
        return $this->belongsToMany(Category::class, 'product_categories', 'product_id', 'category_id')
            ->orderBy('name', 'asc')
            ->take(1);
    }
    public function salesChannels()
    {
        return $this->belongsToMany(Channel::class, 'product_channels', 'product_uuid', 'channel_uuid', 'uuid', 'uuid');
    }
    public function markets()
    {
        return $this->belongsToMany(Market::class, 'product_markets', 'product_uuid', 'market_uuid', 'uuid', 'uuid');
    }

    public function vandors()
    {
        return $this->belongsTo(ProductVendor::class, 'product_uuid', 'vendor_uuid', 'uuid', 'uuid');
    }

    public function getThumbnailimgAttribute($value)
    {
        $path = public_path($value);
        return (!empty($value) && file_exists($path))
            ? getConfigValue('APP_ASSET_PATH') . $value
            : getConfigValue('APP_ASSET_PATH') . 'assets/images/no-image.png';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('Product')->logOnly(['*']);
    }

    protected static function generateJson()
    {
        // Fetch active products with only the fields you need
        $products = self::where('status', 1) ;
        $products = $products->with([
                'productStocks:uuid,product_id,variant,sku,price,image,qty',
                'categories:uuid,name,slug',
                'collections:uuid,name,slug,image',
                'brand:uuid,slug,brand'
            ])
            ->withSum('productStocks as total_stock', 'qty')
            ->get();

        // Convert to JSON
        $productJson = ProductResource::collection($products);
        $jsonData = $productJson->toJson(JSON_PRETTY_PRINT);
        // Save to storage/app/products.json
        Storage::disk('local')->put('products.json', $jsonData);
    }

    public function vat()
{
    return $this->belongsTo(Vat::class, 'vat_id', 'uuid');
}

    public function taxes()
{
    return $this->hasMany(ProductTax::class, 'product_id', 'uuid'); // or belongsToMany, depending on your schema
}

}
