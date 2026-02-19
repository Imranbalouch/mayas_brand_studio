<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Ecommerce\ReportController;
use App\Http\Controllers\API\Ecommerce\CustomerOrderController;
use App\Http\Controllers\API\Ecommerce\CurrencyController;
use App\Http\Controllers\API\Ecommerce\CountryController;
use App\Http\Controllers\API\Ecommerce\CityController;
use App\Http\Controllers\API\Ecommerce\PaymentLogController;
use App\Http\Controllers\API\Ecommerce\AttributeController;
use App\Http\Controllers\API\Ecommerce\AttributeTranslationController;
use App\Http\Controllers\API\Ecommerce\AttributeValueController;
use App\Http\Controllers\API\Ecommerce\CategoryController;
use App\Http\Controllers\API\Ecommerce\CategoryTranslationController;
use App\Http\Controllers\API\Ecommerce\ProductController;
use App\Http\Controllers\API\Ecommerce\ApiBrandController;
use App\Http\Controllers\API\Ecommerce\InventoryController;
use App\Http\Controllers\API\Ecommerce\TaxController;
use App\Http\Controllers\API\Ecommerce\TaxTypeController;
use App\Http\Controllers\API\Ecommerce\VatController;
use App\Http\Controllers\API\Ecommerce\DiscountController;
use App\Http\Controllers\API\Ecommerce\WarehouseController;
use App\Http\Controllers\API\Ecommerce\WarehouseTranslationsController;
use App\Http\Controllers\API\Ecommerce\AttributeCategoryController;
use App\Http\Controllers\API\Ecommerce\WarehouseValuesController;
use App\Http\Controllers\API\Ecommerce\GalleryCategoryController;
use App\Http\Controllers\API\Ecommerce\CatalogController;
use App\Http\Controllers\API\Ecommerce\GiftcardController;
use App\Http\Controllers\API\Ecommerce\ChannelController;
use App\Http\Controllers\API\Ecommerce\GiftcardProductController;
use App\Http\Controllers\API\Ecommerce\TransferInventoryController;
use App\Http\Controllers\API\Ecommerce\CustomerController;
use App\Http\Controllers\API\Ecommerce\CompanyController;
use App\Http\Controllers\API\Ecommerce\OrderController;
use App\Http\Controllers\API\Ecommerce\FulfillmentController;
use App\Http\Controllers\API\Ecommerce\CarrierController;
use App\Http\Controllers\API\Ecommerce\PaymentTermsController;
use App\Http\Controllers\API\Ecommerce\CartController;
use App\Http\Controllers\API\Ecommerce\CustomerAuthController;
use App\Http\Controllers\API\Ecommerce\SupplierController;
use App\Http\Controllers\API\Ecommerce\CollectionController;
use App\Http\Controllers\API\Ecommerce\PurchaseOrderController;
use App\Http\Controllers\API\Ecommerce\MarketController;
use App\Http\Controllers\API\Ecommerce\WishlistController;
use App\Http\Controllers\API\Ecommerce\AddressController;
use App\Http\Controllers\API\Ecommerce\ApiAttributeController;
use App\Http\Controllers\API\Ecommerce\ApiCategoryController;
use App\Http\Controllers\API\Ecommerce\ApiProductController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('customer_order')->group(function () {
    Route::controller(CustomerOrderController::class)->group(function () {
        Route::post('add_order','add_order');
    });
});

Route::middleware('auth:api')->group(function() {
     // Countries
     Route::post('add_currency', [CurrencyController::class, 'add_currency'])->middleware('check.permission:add');
     Route::get('edit_currency/{id?}', [CurrencyController::class, 'edit_currency'])->middleware('check.permission:edit');
     Route::post('update_currency/{id?}', [CurrencyController::class, 'update_currency'])->middleware('check.permission:update');
     Route::delete('delete_currency/{id?}', [CurrencyController::class, 'delete_currency'])->middleware('check.permission:delete');
     Route::get('get_currency', [CurrencyController::class, 'get_currency'])->middleware('check.permission:view');
     Route::post('status/{id}', [CurrencyController::class,'updateCurrencyStatus'])->middleware('check.permission:Update');
     Route::get('get_active_currencies', [CurrencyController::class, 'get_active_currencies']);

     // Countries
     Route::post('add_currency', [CurrencyController::class, 'add_currency'])->middleware('check.permission:add');
     Route::get('edit_currency/{id?}', [CurrencyController::class, 'edit_currency'])->middleware('check.permission:edit');
     Route::post('update_currency/{id?}', [CurrencyController::class, 'update_currency'])->middleware('check.permission:update');
     Route::delete('delete_currency/{id?}', [CurrencyController::class, 'delete_currency'])->middleware('check.permission:delete');
     Route::get('get_currency', [CurrencyController::class, 'get_currency'])->middleware('check.permission:view');
     Route::post('status_currency/{id}', [CurrencyController::class,'updateCurrencyStatus'])->middleware('check.permission:Update');
     Route::get('get_active_currencies', [CurrencyController::class, 'get_active_currencies']);

     //Countries
     Route::post('add_country', [CountryController::class, 'add_country'])->middleware('check.permission:add');
     Route::get('edit_country/{id?}', [CountryController::class, 'edit_country'])->middleware('check.permission:edit');
     Route::post('update_country/{id?}', [CountryController::class, 'update_country'])->middleware('check.permission:update');
     Route::delete('delete_country/{id?}', [CountryController::class, 'delete_country'])->middleware('check.permission:delete');
     Route::get('get_country', [CountryController::class, 'get_country'])->middleware('check.permission:view');
     Route::post('status_country/{id}', [CountryController::class,'updateCountryStatus'])->middleware('check.permission:Update');
     Route::get('get_active_countries', [CountryController::class, 'get_active_countries']);
    
    
     // Taxes
     Route::post('add_tax', [TaxController::class, 'add_tax'])->middleware('check.permission:add');
     Route::get('edit_tax/{id?}', [TaxController::class, 'edit_tax'])->middleware('check.permission:edit');
     Route::post('update_tax/{id?}', [TaxController::class, 'update_tax'])->middleware('check.permission:update');
     Route::delete('delete_tax/{id?}', [TaxController::class, 'delete_tax'])->middleware('check.permission:delete');
     Route::get('get_tax', [TaxController::class, 'get_tax'])->middleware('check.permission:view');
     Route::post('status_tax/{id}', [TaxController::class,'updateTaxStatus'])->middleware('check.permission:Update');
     Route::get('get_active_taxes', [TaxController::class, 'get_active_taxes']);

    Route::get('get_active_tax_type', [TaxTypeController::class, 'get_active_tax_type'])->middleware('check.permission:view');
    Route::get('/get_tax_type',[TaxTypeController::class, 'index'])->middleware('check.permission:view');
    Route::post('/add_tax_type', [TaxTypeController::class, 'store'])->middleware('check.permission:add');
    Route::get('/edit_tax_type/{id}', [TaxTypeController::class, 'edit'])->middleware('check.permission:edit');
    Route::post('/update_tax_type/{id}', [TaxTypeController::class, 'update'])->middleware('check.permission:update');
    Route::post('/status_tax_type/{id}', [TaxTypeController::class, 'updateStatus'])->middleware('check.permission:delete');
    Route::delete('/delete_tax_type/{id}',[TaxTypeController::class, 'destroy'])->middleware('check.permission:update');

    Route::get('get_active_vat', [VatController::class, 'get_active_vat'])->middleware('check.permission:view');
    Route::get('/get_vat',[VatController::class, 'index'])->middleware('check.permission:view');
    Route::post('/add_vat', [VatController::class, 'store'])->middleware('check.permission:add');
    Route::get('/edit_vat/{id}', [VatController::class, 'edit'])->middleware('check.permission:edit');
    Route::post('/update_vat/{id}', [VatController::class, 'update'])->middleware('check.permission:update');
    Route::post('/status_vat/{id}', [VatController::class, 'updateStatus'])->middleware('check.permission:delete');
    Route::delete('/delete_vat/{id}',[VatController::class, 'destroy'])->middleware('check.permission:update');

     // Reports
     Route::post('add_report', [ReportController::class, 'add_report'])->middleware('check.permission:add');
     Route::get('edit_report/{id?}', [ReportController::class, 'edit_report'])->middleware('check.permission:edit');
    // Route::post('update_report/{id?}', [ReportController::class, 'update_report'])->middleware('check.permission:update');
     Route::delete('delete_report/{id?}', [ReportController::class, 'delete_report'])->middleware('check.permission:delete');
     Route::get('get_report', [ReportController::class, 'get_report'])->middleware('check.permission:view');  
     Route::get('order_report', [ReportController::class, 'order_report'])->middleware('check.permission:view');  
     Route::get('customer_report', [ReportController::class, 'customer_report'])->middleware('check.permission:view');  
     Route::get('purchase_order_report', [ReportController::class, 'purchase_order_report'])->middleware('check.permission:view');  
     Route::get('collection_report', [ReportController::class, 'collection_report'])->middleware('check.permission:view');  
     Route::get('activity_report', [ReportController::class, 'activity_report'])->middleware('check.permission:view');  

     // Discounts
     Route::post('add_discount', [DiscountController::class, 'add_discount'])->middleware('check.permission:add');
     Route::get('edit_discount/{id?}', [DiscountController::class, 'edit_discount'])->middleware('check.permission:edit');
     Route::post('update_discount/{id?}', [DiscountController::class, 'update_discount'])->middleware('check.permission:update');
     Route::delete('delete_discount/{id?}', [DiscountController::class, 'delete_discount'])->middleware('check.permission:delete');
     Route::get('get_discount', [DiscountController::class, 'get_discount'])->middleware('check.permission:view');
     Route::post('status_discount/{id}', [DiscountController::class,'updateStatus'])->middleware('check.permission:Update');
     Route::get('get_active_discounts', [DiscountController::class, 'get_active_discounts']);

    // Category
    Route::post('add_category', [CategoryController::class, 'add_category'])->middleware('check.permission:add');
    Route::get('edit_category/{id?}', [CategoryController::class, 'edit_category'])->middleware('check.permission:edit');
    Route::put('update_category/{id?}', [CategoryController::class, 'update_category'])->middleware('check.permission:update');
    Route::delete('delete_category/{id?}', [CategoryController::class, 'delete_category'])->middleware('check.permission:delete');
    Route::get('get_own_category/{authid?}', [CategoryController::class, 'get_own_category'])->middleware('check.permission:view');
    Route::get('get_category', [CategoryController::class, 'get_category'])->middleware('check.permission:viewglobal');


    // Category Translation
    Route::post('add_category_translation', [CategoryTranslationController::class, 'add_category_translation'])->middleware('check.permission:add');
    Route::get('edit_category_translation/{id?}', [CategoryTranslationController::class, 'edit_category_translation'])->middleware('check.permission:edit');
    Route::put('update_category_translation/{id?}', [CategoryTranslationController::class, 'update_category_translation'])->middleware('check.permission:update');
    Route::delete('delete_category_translation/{id?}', [CategoryTranslationController::class, 'delete_category_translation'])->middleware('check.permission:delete');
    Route::get('get_own_category_translation/{authid?}', [CategoryTranslationController::class, 'get_own_category_translation'])->middleware('check.permission:view');
    Route::get('get_category_translation', [CategoryTranslationController::class, 'get_category_translation'])->middleware('check.permission:viewglobal');


    // Attribute
    Route::post('add_attribute', [AttributeController::class, 'add_attribute'])->middleware('check.permission:add');
    Route::get('edit_attribute/{id?}', [AttributeController::class, 'edit_attribute'])->middleware('check.permission:edit');
    Route::put('update_attribute/{id?}', [AttributeController::class, 'update_attribute'])->middleware('check.permission:update');
    Route::delete('delete_attribute/{id?}', [AttributeController::class, 'delete_attribute'])->middleware('check.permission:delete');
    //Route::get('get_own_attribute/{authid?}', [AttributeController::class, 'get_own_attribute'])->middleware('check.permission:view');
    Route::get('get_attribute', [AttributeController::class, 'get_attribute'])->middleware('check.permission:viewglobal');

    Route::get('edit_warehouse/{id?}', [WarehouseController::class, 'edit_warehouse'])->middleware('check.permission:edit');
    Route::put('update_warehouse/{id?}', [WarehouseController::class, 'update_warehouse'])->middleware('check.permission:update');
    Route::delete('delete_warehouse/{id?}', [WarehouseController::class, 'delete_warehouse'])->middleware('check.permission:delete');
    //Route::get('get_own_warehouse/{authid?}', [WarehouseController::class, 'get_own_warehouse'])->middleware('check.permission:view');
    Route::get('get_warehouse', [WarehouseController::class, 'get_warehouse'])->middleware('check.permission:viewglobal');


    // Attribute Translation 
    Route::post('add_attribute_translation', [AttributeTranslationController::class, 'add_attribute_translation'])->middleware('check.permission:add');
    Route::get('edit_attribute_translation/{id?}', [AttributeTranslationController::class, 'edit_attribute_translation'])->middleware('check.permission:edit');
    Route::put('update_attribute_translation/{id?}', [AttributeTranslationController::class, 'update_attribute_translation'])->middleware('check.permission:update');
    Route::delete('delete_attribute_translation/{id?}', [AttributeTranslationController::class, 'delete_attribute_translation'])->middleware('check.permission:delete');
    Route::get('get_own_attribute_translation/{authid?}', [AttributeTranslationController::class, 'get_own_attribute_translation'])->middleware('check.permission:view');
    Route::get('get_attribute_translation', [AttributeTranslationController::class, 'get_attribute_translation'])->middleware('check.permission:viewglobal');

    Route::post('add_warehouse_translation', [WarehouseTranslationsController::class, 'add_warehouse_translation'])->middleware('check.permission:add');
    Route::get('edit_warehouse_translation/{id?}', [WarehouseTranslationsController::class, 'edit_warehouse_translation'])->middleware('check.permission:edit');
    Route::put('update_warehouse_translation/{id?}', [WarehouseTranslationsController::class, 'update_warehouse_translation'])->middleware('check.permission:update');
    Route::delete('delete_warehouse_translation/{id?}', [WarehouseTranslationsController::class, 'delete_warehouse_translation'])->middleware('check.permission:delete');
    Route::get('get_own_warehouse_translation/{authid?}', [WarehouseTranslationsController::class, 'get_own_warehouse_translation'])->middleware('check.permission:view');
    Route::get('get_warehouse_translation', [WarehouseTranslationsController::class, 'get_warehouse_translation'])->middleware('check.permission:viewglobal');


    // Attribute Category 
    Route::post('add_attribute_category', [AttributeCategoryController::class, 'add_attribute_category'])->middleware('check.permission:add');
    Route::get('edit_attribute_category/{id?}', [AttributeCategoryController::class, 'edit_attribute_category'])->middleware('check.permission:edit');
    Route::put('update_attribute_category/{id?}', [AttributeCategoryController::class, 'update_attribute_category'])->middleware('check.permission:update');
    Route::delete('delete_attribute_category/{id?}', [AttributeCategoryController::class, 'delete_attribute_category'])->middleware('check.permission:delete');
    Route::get('get_own_attribute_category/{authid?}', [AttributeCategoryController::class, 'get_own_attribute_category'])->middleware('check.permission:view');
    Route::get('get_attribute_category', [AttributeCategoryController::class, 'get_attribute_category'])->middleware('check.permission:viewglobal');


    // Attribute Value 
    Route::post('add_attribute_value', [AttributeValueController::class, 'add_attribute_value'])->middleware('check.permission:add');
    Route::get('edit_attribute_value/{id?}', [AttributeValueController::class, 'edit_attribute_value'])->middleware('check.permission:edit');
    Route::put('update_attribute_value/{id?}', [AttributeValueController::class, 'update_attribute_value'])->middleware('check.permission:update');
    Route::delete('delete_attribute_value/{id?}', [AttributeValueController::class, 'delete_attribute_value'])->middleware('check.permission:delete');
    Route::get('get_own_attribute_value/{authid?}', [AttributeValueController::class, 'get_own_attribute_value'])->middleware('check.permission:view');
    Route::get('get_all_attribute_value', [AttributeValueController::class, 'get_all_attribute_value'])->middleware('check.permission:viewglobal');

    Route::post('add_warehouse_value', [WarehouseValuesController::class, 'add_warehouse_value']);
    Route::get('edit_warehouse_value/{id?}', [WarehouseValuesController::class, 'edit_warehouse_value'])->middleware('check.permission:edit');
    Route::put('update_warehouse_value/{id?}', [WarehouseValuesController::class, 'update_warehouse_value'])->middleware('check.permission:update');
    Route::delete('delete_warehouse_value/{id?}', [WarehouseValuesController::class, 'delete_warehouse_value'])->middleware('check.permission:delete');
    Route::get('get_own_warehouse_value/{authid?}', [WarehouseValuesController::class, 'get_own_warehouse_value'])->middleware('check.permission:view');
    Route::get('get_all_warehouse_value', [WarehouseValuesController::class, 'get_all_warehouse_value'])->middleware('check.permission:viewglobal');

    // Gallery Category
    Route::post('add_gallery_category', [GalleryCategoryController::class, 'add_gallery_category'])->middleware('check.permission:add');
    Route::get('edit_gallery_category/{id?}', [GalleryCategoryController::class, 'edit_gallery_category'])->middleware('check.permission:edit');
    Route::put('update_gallery_category/{id?}', [GalleryCategoryController::class, 'update_gallery_category'])->middleware('check.permission:update');
    Route::delete('delete_gallery_category/{id?}', [GalleryCategoryController::class, 'delete_gallery_category'])->middleware('check.permission:delete');
    Route::get('get_own_gallery_category/{authid?}', [GalleryCategoryController::class, 'get_own_gallery_category'])->middleware('check.permission:view');
    Route::get('get_all_gallery_category', [GalleryCategoryController::class, 'get_all_gallery_category'])->middleware('check.permission:viewglobal');    

    Route::get('get_all_gallery_category', [GalleryCategoryController::class, 'get_all_gallery_category'])->middleware('check.permission:viewglobal');

    //Payment Logs
    Route::prefix('payment')->group(function () {
        Route::controller(CategoryController::class)->group(function () {
            Route::get('/get_payment_logs', [PaymentLogController::class, 'getPaymentLogs']);
        });
    });

    Route::prefix('category')->group(function () {
        Route::controller(CategoryController::class)->group(function () {
            Route::get('/get_category', 'get_category');
            Route::post('/add_category', 'add_category')->middleware('check.permission:add');
            Route::get('/edit_category/{id?}', 'edit_category')->middleware('check.permission:edit');
            Route::post('/update_category', 'update_category')->middleware('check.permission:update');
            Route::delete('/delete_category/{id?}', 'delete_category')->middleware('check.permission:delete');
            Route::post('/status/{id}', 'updateCategoryStatus')->middleware('check.permission:Update');
            Route::post('/featured/{id}', 'updateCategoryFeatured')->middleware('check.permission:Update');
            Route::get('/get_active_categories', 'get_active_categories');
        });
    });

  
    Route::prefix('catalog')->group(function () {
        Route::controller(CatalogController::class)->group(function () {
            Route::get('get_catalog', 'get_catalog')->middleware('check.permission:viewglobal,view');
            Route::post('add_catalog', 'add_catalog')->middleware('check.permission:add');
            Route::get('edit_catalog/{id?}', 'edit_catalog')->middleware('check.permission:edit');
            Route::post('update_catalog', 'update_catalog')->middleware('check.permission:update');
            Route::delete('delete_catalog/{id?}', 'delete_catalog')->middleware('check.permission:delete');
            Route::post('/status/{id}', 'updateCatalogStatus')->middleware('check.permission:Update');
            Route::post('/featured/{id}', 'updateCatalogFeatured')->middleware('check.permission:Update');
            Route::get('/get_active_catalogs', 'get_active_catalogs');
        });
    });

    Route::prefix('giftcard')->group(function () {
        Route::controller(GiftcardController::class)->group(function () {
            Route::get('get_giftcard', 'get_giftcard')->middleware('check.permission:viewglobal,view');
            Route::post('add_giftcard', 'add_giftcard')->middleware('check.permission:add');
            Route::get('edit_giftcard/{id?}', 'edit_giftcard')->middleware('check.permission:edit');
            Route::post('update_giftcard', 'update_giftcard')->middleware('check.permission:update');
            Route::delete('delete_giftcard/{id?}', 'delete_giftcard')->middleware('check.permission:delete');
            Route::post('/status/{id}', 'updateGiftcardStatus')->middleware('check.permission:Update'); 
            Route::get('/get_active_giftcards', 'get_active_giftcards');
            Route::post('add_giftcard_to_order', 'applyGiftcardToOrder')->middleware('check.permission:add');
        });
    });
    Route::prefix('channel')->group(function () {
        Route::controller(ChannelController::class)->group(function () {
            Route::get('get_channel', 'get_channel')->middleware('check.permission:viewglobal,view');
            Route::post('add_channel', 'add_channel')->middleware('check.permission:add');
            Route::get('edit_channel/{id?}', 'edit_channel')->middleware('check.permission:edit');
            Route::post('update_channel', 'update_channel')->middleware('check.permission:update');
            Route::delete('delete_channel/{id?}', 'delete_channel')->middleware('check.permission:delete');
            Route::post('/status/{id}', 'updateChannelStatus')->middleware('check.permission:Update');
            Route::post('/featured/{id}', 'updateChannelFeatured')->middleware('check.permission:Update');
            Route::get('/get_active_channels', 'get_active_channels');
        });
    });

    Route::prefix('market')->group(function () {
        Route::controller(MarketController::class)->group(function () {
            Route::get('get_market', 'get_market')->middleware('check.permission:viewglobal,view');
            Route::post('add_market', 'add_market')->middleware('check.permission:add');
            Route::get('edit_market/{id?}', 'edit_market')->middleware('check.permission:edit');
            Route::post('update_market', 'update_market')->middleware('check.permission:update');
            Route::delete('delete_market/{id?}', 'delete_market')->middleware('check.permission:delete');
            Route::post('/status/{id}', 'updateMarketStatus')->middleware('check.permission:Update');
            Route::post('/featured/{id}', 'updateMarketFeatured')->middleware('check.permission:Update');
            Route::get('/get_active_markets', 'get_active_markets');
        });
    });


    Route::prefix('attribute')->group(function () {
        Route::controller(AttributeController::class)->group(function () {
            Route::get('get_attribute', 'get_attribute')->middleware('check.permission:viewglobal,view');
            Route::post('add_attribute', 'add_attribute')->middleware('check.permission:add');
            Route::get('edit_attribute/{id?}', 'edit_attribute')->middleware('check.permission:edit');
            Route::post('update_attribute', 'update_attribute')->middleware('check.permission:update');
            Route::delete('delete_attribute/{id?}', 'delete_attribute')->middleware('check.permission:delete');
            Route::post('/status/{id}', 'updateAttributeStatus')->middleware('check.permission:Update');
            Route::get('/get_active_attributes', 'get_active_attributes');
            

            // Attribute Value
            Route::post('store_attribute_value', 'store_attribute_value')->middleware('check.permission:add');
            Route::get('edit_attribute_value/{id?}', 'edit_attribute_value')->middleware('check.permission:edit');

            Route::get('edit_specific_attribute_value/{id?}', 'edit_specific_attribute_value')->middleware('check.permission:edit'); 
            Route::post('update_attribute_value', 'update_attribute_value')->middleware('check.permission:update');
            Route::delete('delete_attribute_value/{id?}', 'delete_attribute_value')->middleware('check.permission:delete');

        });
    });
    
    Route::prefix('warehouse')->group(function () {
        Route::controller(WarehouseController::class)->group(function () {
            Route::get('get_warehouse', 'get_warehouse')->middleware('check.permission:viewglobal,view');
            Route::get('get_warehouse_values', 'get_warehouse_values')->middleware('check.permission:viewglobal,view');
            Route::get('/get-manager-contact/{uuid}', 'getManagerContactInfo')->middleware('check.permission:viewglobal,view');
            Route::post('add_warehouse', 'add_warehouse')->middleware('check.permission:add');
            Route::get('edit_warehouse/{id?}', 'edit_warehouse')->middleware('check.permission:edit');
            Route::post('update_warehouse', 'update_warehouse')->middleware('check.permission:update');
            Route::delete('delete_warehouse/{id?}', 'delete_warehouse')->middleware('check.permission:delete');
            Route::post('/status/{id}', 'updatewarehouseStatus')->middleware('check.permission:Update');
            Route::post('/featured/{id}', 'updatewarehouseFeatured')->middleware('check.permission:Update');
            Route::post('/update_warehousevalue_Status/{id}', 'update_warehousevalue_Status')->middleware('check.permission:Update');
            Route::post('/update_warehousevalue_isdefault/{id}', 'update_warehousevalue_isdefault')->middleware('check.permission:Update');
            Route::post('/update_warehousevalue_featured/{id}', 'update_warehousevalue_featured')->middleware('check.permission:Update');
            Route::get('/get_active_warehouses', 'get_active_warehouses');
            Route::get('/get-managers', 'getManagers')->middleware('check.permission:viewglobal,view');
            Route::get('/get-warehouses', 'getWarehouses')->middleware('check.permission:viewglobal,view');

            Route::post('store_warehouse_value', 'store_warehouse_value')->middleware('check.permission:add');
            Route::get('edit_warehouse_value/{id?}', 'edit_warehouse_value')->middleware('check.permission:edit');

            Route::get('edit_specific_warehouse_value/{id?}', 'edit_specific_warehouse_value')->middleware('check.permission:edit'); 
            Route::post('update_warehouse_value', 'update_warehouse_value')->middleware('check.permission:update');
            Route::delete('delete_warehouse_value/{id?}', 'delete_warehouse_value')->middleware('check.permission:delete');
            Route::get('/get_active_warehouse_locations', 'get_active_warehouse_locations');
        });
    });


    Route::prefix('product')->group(function () {
        Route::controller(ProductController::class)->group(function () {
            Route::get('get_product', 'get_product')->middleware('check.permission:viewglobal,view');
            Route::post('get_products_by_ids', 'get_products_by_ids')->middleware('check.permission:viewglobal,view');
            Route::get('get_products_with_channels', 'get_products_with_channels')->middleware('check.permission:viewglobal,view');
            Route::post('toggle_channel_inclusion', 'toggle_channel_inclusion')->middleware('check.permission:add');
            Route::post('add_product', 'add_product')->middleware('check.permission:add');
            Route::get('edit_product/{id?}', 'edit_product')->middleware('check.permission:edit');
            Route::post('update_product', 'update_product')->middleware('check.permission:update');
            Route::delete('delete_product/{id?}', 'delete_product')->middleware('check.permission:delete');
            Route::post('/status/{id}', 'updateCategoryStatus')->middleware('check.permission:Update');
            Route::post('/featured/{id}', 'updateCategoryFeatured')->middleware('check.permission:Update');
            Route::post('/featured/{id}', 'updateCategoryFeatured')->middleware('check.permission:Update');

            // Attribute Section
            Route::get('get_product_attribute', 'get_product_attribute')->middleware('check.permission:viewglobal,view');
            Route::delete('delete_product_attribute/{id?}', 'delete_product_attribute')->middleware('check.permission:delete');

            Route::post('add-more-choice-option', 'add_more_choice_option');
            Route::post('sku-combination', 'sku_combination');
            Route::post('sku-combination-edit', 'sku_combination_edit');
            Route::post('sku-simple-edit', 'sku_simple_edit');
            Route::get('/get_active_products', 'get_active_products');
            Route::get('/emptyTables', 'emptyTables');
            Route::get('/get_product_types', 'get_product_types');
            Route::get('/get_product_vendors', 'get_product_vendors');
            Route::get('/get_product_tags', 'get_product_tags'); 
            Route::get('/get_product_collections', 'get_product_collections'); 
            Route::get('/get_active_tags', 'get_active_tags');
            Route::post('/import_products', 'import_products')->middleware('check.permission:add');
            Route::get('/export-products', 'exportProducts');

            Route::get('get_filter_views', 'get_filter_views')->middleware('check.permission:viewglobal,view');
            Route::post('add_filter_view', 'add_filter_view')->middleware('check.permission:viewglobal,view');
            Route::post('update_filter_view', 'update_filter_view')->middleware('check.permission:viewglobal,view');
            Route::delete('delete_filter_view/{id?}', 'delete_filter_view')->middleware('check.permission:viewglobal,view');

        });
    });

    Route::prefix('giftcard-product')->group(function () {
        Route::controller(ProductController::class)->group(function () {
            Route::post('/store', [GiftcardProductController::class, 'add_giftcard_product']);
            Route::get('/index', [GiftcardProductController::class, 'get_giftcard_product']);
            Route::post('/update', [GiftcardProductController::class, 'update_giftcard_product']);
            Route::get('/get-single-product', [GiftcardProductController::class, 'get_single_giftcard_product']);
            Route::get('/edit/{uuid}', [GiftcardProductController::class, 'edit_giftcard_product']);
            Route::delete('/delete', [GiftcardProductController::class, 'delete_giftcard_product']);
        });
    });

    Route::prefix('inventory')->group(function () {
        Route::controller(InventoryController::class)->group(function () {
            Route::get('get_inventory', 'get_inventory')->middleware('check.permission:viewglobal,view');
            Route::post('/add-unavailable', 'unavailableAdd');
            Route::post('/delete-unavailable', 'unavailableDelete');
            Route::post('/add-inventory', 'availableAdd');
            Route::get('/inventory-detail/{id}', 'inventory_detail');
        });
    });



    Route::prefix('transferinventory')->group(function () {
        Route::controller(TransferInventoryController::class)->group(function () {
            Route::get('get_transferinventory', 'get_transferinventory')->middleware('check.permission:viewglobal,view');
            Route::post('/store', 'store')->middleware('check.permission:add');
            Route::get('/edit/{id}', 'edit')->middleware('check.permission:edit');
            Route::post('/update/{id}', 'update')->middleware('check.permission:update'); 
            Route::delete('/{tiId}/items/{itemId}', 'removeItem')->middleware('check.permission:delete'); 
            Route::delete('/delete/{id}','destroy')->middleware('check.permission:delete'); 
            Route::get('/ti-receving/{id}','ti_receiving')->middleware('check.permission:view,viewglobal'); 
            Route::post('/ti-receving-add/{id}','ti_receiving_add')->middleware('check.permission:add');   
        });
    });


    Route::prefix('customer')->group(function () {
        Route::controller(CustomerController::class)->group(function () {
           Route::get('','index')->middleware('check.permission:viewglobal,view');
           Route::get('active_customers','active_customers');
           Route::post('store','store')->middleware('check.permission:add');
           Route::post('status/{id}','update_status')->middleware('check.permission:update');
           Route::post('store','store')->middleware('check.permission:add');
           Route::post('update/{uuid}','update')->middleware('check.permission:update');
           Route::get('edit/{uuid}','edit')->middleware('check.permission:update');
           Route::delete('delete/{uuid}','destroy')->middleware('check.permission:delete');
           Route::get('show/{uuid}','show');
           Route::post('status/{id}','update_status')->middleware('check.permission:update');
           Route::delete('{customerUuid}/shipping-address/{addressUuid}', 'deleteShippingAddress');
        });
    });

    Route::prefix('company')->group(function () {
        Route::controller(CompanyController::class)->group(function () {
           Route::get('','index')->middleware('check.permission:viewglobal,view');
           Route::get('active_companies','active_companies');
           Route::post('store','store')->middleware('check.permission:add');
           Route::post('update/{uuid}','update')->middleware('check.permission:update');
           Route::get('edit/{uuid}','edit')->middleware('check.permission:update');
           Route::delete('delete/{uuid}','destroy')->middleware('check.permission:delete');
           Route::post('approved_status/{id}','ApprovedStatus')->middleware('check.permission:update');
           Route::delete('{companyUuid}/shipping-address/{addressUuid}', 'deleteShippingAddress');
        });
    });

    Route::prefix('order')->group(function () {
        Route::controller(OrderController::class)->group(function () {
            Route::get('get_order','get_order')->middleware('check.permission:viewglobal,view');
            Route::get('get_specific_order/{uuid?}','get_specific_order')->middleware('check.permission:viewglobal,view');
            Route::post('add_order', 'add_order')->middleware('check.permission:add');
            Route::post('update_order/{uuid?}', 'update_order')->middleware('check.permission:update');
            Route::post('update_delivery_status/{uuid?}', 'update_delivery_status')->middleware('check.permission:update');
            Route::get('edit_order/{uuid?}', 'edit_order')->middleware('check.permission:edit');
            Route::delete('delete_order/{uuid?}', 'delete_order')->middleware('check.permission:delete');
            Route::get('show-order-details/{code?}', 'show_order_details')->middleware('check.permission:edit');
            Route::post('mark_as_paid/{uuid}', 'mark_as_paid')->middleware('check.permission:update');
            Route::post('comments_add/{orderUuid}','add_order_comment');
            Route::get('comments_get/{orderUuid}','get_order_comments');
            Route::post('comments_update/{commentUuid}','update_order_comment');
            Route::get('comments_edit/{commentUuid}','edit_order_comment');
            Route::delete('comments_delete/{commentUuid}','delete_order_comment');
            Route::get('download-invoice/{uuid}', 'downloadOrderInvoice');
        });
    });
    Route::prefix('fulfillment')->group(function () {
        Route::controller(FulfillmentController::class)->group(function () {
            Route::get('get_fulfillments', 'get_fulfillments')->middleware('check.permission:viewglobal,view');
            Route::get('edit_fulfillment/{uuid}', 'edit_fulfillment')->middleware('check.permission:edit');
            Route::post('create_fulfillment', 'create_fulfillment')->middleware('check.permission:add');
            Route::post('update_fulfillment/{uuid}', 'update_fulfillment')->middleware('check.permission:update');
            Route::delete('delete_fulfillment/{uuid}', 'delete_fulfillment')->middleware('check.permission:delete');
            Route::post('create_return', 'create_return')->middleware('check.permission:add');
            Route::get('get_return', 'get_return')->middleware('check.permission:viewglobal,view');
        });
    });
    Route::prefix('tracking')->group(function () {
        Route::controller(FulfillmentController::class)->group(function () {
            Route::post('add_tracking', 'add_tracking')->middleware('check.permission:add');
        });
     });
    Route::prefix('return')->group(function () {
    Route::controller(FulfillmentController::class)->group(function () {
        Route::post('create_return', 'create_return')->middleware('check.permission:add');
        Route::post('restock_return', 'restock_return')->middleware('check.permission:add');
        });
    });

    Route::prefix('carriers')->group(function () {
        Route::controller(CarrierController::class)->group(function () {
            Route::get('/','index')->middleware('check.permission:view,viewglobal');
            Route::post('/store', 'store')->name('filemanager.store')->middleware('check.permission:add');
            Route::get('/edit/{id}', 'edit')->middleware('check.permission:edit');
            Route::post('/update/{id}', 'update')->middleware('check.permission:update');
            Route::post('/status/{id}', 'updateStatus')->middleware('check.permission:update');
            Route::delete('/delete/{id}','destroy')->middleware('check.permission:delete'); 
        });
    });

    Route::prefix('cities')->group(function () {
        Route::controller(CityController::class)->group(function () {
            Route::get('/','index')->middleware('check.permission:view,viewglobal');
            Route::post('/store', 'store')->middleware('check.permission:add');
            Route::get('/edit/{id}', 'edit')->middleware('check.permission:edit');
            Route::post('/update/{id}', 'update')->middleware('check.permission:update');
            Route::post('/status/{id}', 'updateStatus')->middleware('check.permission:update');
            Route::delete('/delete/{id}','destroy')->middleware('check.permission:delete'); 
        });
    });

    Route::prefix('payment-terms')->group(function () {
        Route::controller(PaymentTermsController::class)->group(function () {
            Route::get('/','index')->middleware('check.permission:view,viewglobal');
            Route::post('/store', 'store')->name('filemanager.store')->middleware('check.permission:add');
            Route::get('/edit/{id}', 'edit')->middleware('check.permission:edit');
            Route::post('/update/{id}', 'update')->middleware('check.permission:update');
            Route::post('/status/{id}', 'updateStatus')->middleware('check.permission:update');
            Route::delete('/delete/{id}','destroy')->middleware('check.permission:delete'); 
        });
    });

    Route::prefix('supplier')->group(function () {
        Route::controller(SupplierController::class)->group(function () {
            Route::get('/','index')->middleware('check.permission:view,viewglobal');
            Route::post('/store', 'store')->name('filemanager.store')->middleware('check.permission:add');
            Route::get('/edit/{id}', 'edit')->middleware('check.permission:edit');
            Route::post('/update/{id}', 'update')->middleware('check.permission:update');
            Route::post('/status/{id}', 'updateStatus')->middleware('check.permission:update');
            Route::delete('/delete/{id}','destroy')->middleware('check.permission:delete'); 
        });
    });
    
    // Collection
    Route::prefix('collection')->group(function () {
        Route::controller(CollectionController::class)->group(function () {
            Route::get('/','index')->middleware('check.permission:view,viewglobal');
            Route::post('/store', 'store')->name('filemanager.store')->middleware('check.permission:add');
            Route::get('/edit/{id}', 'edit')->middleware('check.permission:edit');
            Route::post('/update/{id}', 'update')->middleware('check.permission:update');
            Route::post('/status/{id}', 'updateStatus')->middleware('check.permission:update');
            Route::delete('/delete/{id}','destroy')->middleware('check.permission:delete');  
        });
        Route::get('/get_active_collections',[CollectionController::class,'get_active_collections']);
    });
    Route::prefix('purchase-order')->group(function () {
        Route::controller(PurchaseOrderController::class)->group(function () {
            Route::get('/','index')->middleware('check.permission:view,viewglobal');
            Route::post('/store', 'store')->name('filemanager.store')->middleware('check.permission:add');
            Route::get('/edit/{id}', 'edit')->middleware('check.permission:edit');
            Route::post('/update/{id}', 'update')->middleware('check.permission:update');
            Route::post('/status/{id}', 'updateStatus')->middleware('check.permission:update');
            Route::delete('/delete/{id}','destroy')->middleware('check.permission:delete'); 
            Route::get('/po-receving/{id}','po_receiving')->middleware('check.permission:view,viewglobal'); 
            Route::post('/po-receving-add/{id}','po_receiving_add')->middleware('check.permission:add'); 
            // Route::get('/get_active_purhcase','get_supplier'); 
            Route::post('/add_comments/{poOrderUuid}','add_po_order_comment');
            Route::get('/get_comments/{poOrderUuid}','get_po_order_comments');
            Route::post('/comments_update/{poOrderUuid}','update_po_order_comment');
            Route::get('/comments_edit/{poOrderUuid}','edit_po_order_comment');
            Route::delete('/comments_delete/{poOrderUuid}','delete_po_order_comment');
        });
    });

    Route::prefix('admin-cart')->group(function () {
        Route::controller(CartController::class)->group(function () {
            Route::get('','get_cart');
        });
    });


    //Need Api's to create purchase order active lists
    Route::get('/get_active_paymentterm',[PaymentTermsController::class,'get_paymentterm']);
    Route::get('/get_active_supplier',[SupplierController::class,'get_supplier']);
    Route::get('/get_active_carriers',[CarrierController::class,'get_carriers']);
});


Route::middleware(['auth:customer', 'scopes:customer'])->group(function () {
    Route::post('customer/logout', [CustomerAuthController::class, 'logout']);
    Route::get('customer/profile', [CustomerAuthController::class, 'profile']);
    Route::prefix('cart')->group(function () {
        Route::controller(CartController::class)->group(function () {
            Route::get('','index');
            Route::post('add_to_cart','add_to_cart');
            Route::get('edit_to_cart/{uuid}','edit');
            Route::post('update_cart_quantity','update_cart_quantity');
            Route::delete('delete_cart/{uuid}','destroy');
        });
    });
});




// For Frontend API without authentication

Route::prefix('frontend')->group(function () {
    Route::controller(ApiCategoryController::class)->group(function () {
        Route::get('/get_category/{id?}', 'get_category');
        Route::get('/get_featured_category/{id?}', 'get_featured_category');
    });
});


Route::prefix('frontend')->group(function () {
    Route::controller(ApiBrandController::class)->group(function () {
        Route::get('get_brand/{id?}', [ApiBrandController::class, 'get_brand']);
        Route::get('get_featured_brand/{id?}', [ApiBrandController::class, 'get_featured_brand']);
    });
});


Route::prefix('frontend')->group(function () {
    Route::controller(ApiAttributeController::class)->group(function () {
        Route::get('get_attribute/{id?}', [ApiAttributeController::class, 'get_attribute']);
    });
});


Route::prefix('frontend')->group(function () {
    Route::get('get_active_countries', [CountryController::class, 'get_active_countries']);
    Route::get('get_city_by_country', [CityController::class, 'get_city_by_country']);
    Route::get('/get_active_collections', [ApiProductController::class, 'get_active_collections']);
    Route::get('/get_active_filter', [ApiProductController::class, 'get_active_filter']);
    Route::get('/get_active_languages', [ApiProductController::class, 'get_active_languages']);


    Route::controller(ApiProductController::class)->group(function () {
        Route::get('get_product/{id?}', 'get_product');
        Route::post('get-product-variation', 'productVaraition');
        Route::get('get_featured_product/{id?}', 'get_featured_product');
        Route::get('get_product_by_slug/{slug?}/{id?}', 'get_product_by_slug');
        Route::get('get_product_by_slug_all/{slug?}/{id?}', 'get_product_by_slug_all');
        Route::get('product/get_columns', 'get_columns');
        Route::get('search', 'productSearch');
    });

    Route::get("wishlist/get_columns", [WishlistController::class, 'get_columns']);


    Route::prefix('cart')->group(function () {
        Route::controller(CartController::class)->group(function () {
            Route::get('', 'index');
            Route::get('cart_get', 'cart_get');
            Route::post('add_to_cart', 'add_to_cart');
            Route::get('edit_to_cart/{uuid}', 'edit');
            Route::get('count', 'count');
            Route::post('update_cart_quantity', 'update_cart_quantity');
            Route::delete('delete_cart/{uuid}', 'destroy');
            Route::post('update', 'update');
            Route::post('remove', 'remove');
            Route::post('remove-all', 'removeAll');
            Route::get('get_columns', 'get_columns');
            Route::post('apply_coupon', 'apply_coupon');
            Route::post('remove_coupon', 'remove_coupon');
        });
    });


    Route::prefix('customer_order')->group(function () {
        Route::controller(CustomerOrderController::class)->group(function () {
            Route::get('get-order', 'index');
            Route::post('add_order', 'add_order');
            Route::post('update_payment_method','update_payment_method');
            Route::get('get_columns', 'get_columns');
            Route::get('get_column_order', 'get_column_order');
            Route::get('get_column_order_detail', 'get_column_order_detail');
            Route::get('{orderUuid}/download-pdf', 'downloadOrderPdf');
        });
    });

   

    Route::prefix('customer')->group(function () {
        Route::get('login/get_columns', [CustomerAuthController::class, 'get_columns']);
        Route::get('signup/get_columns', [CustomerAuthController::class, 'signup_get_columns']);
        Route::get('forgetpassword/get_columns', [CustomerAuthController::class, 'forgetpassword_get_columns']);
        Route::get('resetpassword/get_columns', [CustomerAuthController::class, 'resetpassword_get_columns']);
        Route::get('profile/get_columns', [CustomerAuthController::class, 'customerprofile_get_columns']);
        Route::post('/register', [CustomerAuthController::class, 'register']);
        Route::post('/login', [CustomerAuthController::class, 'login']);
        Route::post('/forget-password', [CustomerAuthController::class, 'forgot_password_customer'])->middleware(["throttle:5,1"]);
        Route::post('reset-password', [CustomerAuthController::class, 'reset_password_customer'])->middleware(["throttle:5,1"]);
        Route::get('wishlist_count', [WishlistController::class, 'wishlistCount']);
        Route::get('order_count', [CustomerOrderController::class, 'OrderCount']);
        Route::get('order_expenditure', [CustomerOrderController::class, 'OrderExpenditure']);
        Route::prefix('address')->group(function () {
            Route::controller(AddressController::class)->group(function () {
                Route::get('get_address_default', 'get_address_default');
                Route::get('get_address', 'get_address');
                Route::post('add_address', 'add_address');
                Route::post('update_address/{uuid}', 'update_address');
                Route::get('get_columns', 'get_columns');
            });
        });
        Route::middleware(['auth:customer', 'scopes:customer'])->group(function () {
            Route::controller(WishlistController::class)->group(function () {
                Route::get('wishlist', 'index');
                Route::post('wishlist/add', 'store');
                Route::delete('wishlist/{id}', 'destroy');
            });
            Route::post('logout', [CustomerAuthController::class, 'logout']);
            Route::get('profile', [CustomerAuthController::class, 'profile']);
            Route::get('authOrder', [CustomerOrderController::class, 'authOrder']);
            Route::get('authOrderDetail', [CustomerOrderController::class, 'authOrderDetail']);
            Route::post('profile-update', [CustomerAuthController::class, 'update_profile']);
            Route::post('change-password', [CustomerAuthController::class, 'change_password']);
        });
    });
});

