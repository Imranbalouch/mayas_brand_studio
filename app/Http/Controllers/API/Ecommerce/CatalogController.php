<?php

namespace App\Http\Controllers\API\Ecommerce;

use DB;
use Hash;
use Mail;
use Session;
use Carbon\Carbon; 
use App\Models\Menu;
use DeepCopy\f001\B;
use App\Models\Ecommerce\Catalog;
use App\Models\Ecommerce\Product;
use App\Models\Language;
use Illuminate\Support\Str;
use App\Models\Ecommerce\ProductStock;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use App\Models\Ecommerce\CatalogProduct;
use App\Models\Permission_assign;
use App\Models\Ecommerce\Catalog_translation;
use App\Services\PermissionService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class CatalogController extends Controller
{
    
    use MessageTrait;
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }


    public function get_catalog(){

        try{

            $menuUuid = request()->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);
           
            $get_all_catalog = Catalog::orderBy('id', 'desc');
           
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $get_all_catalog = $get_all_catalog->where('auth_id', Auth::user()->uuid);
                }
            }else{
                if (Auth::user()->hasPermission('viewglobal')) {
                    $get_all_catalog = $get_all_catalog;
                } else {
                    return response()->json([
                        'message' => 'You do not have permission to view this menu'
                    ], Response::HTTP_FORBIDDEN);
                }
            }
            $get_all_catalog = $get_all_catalog->get();
           
            return response()->json([
                'status_code'=>200,
                'permissions' => $permissions,
                'data'=>$get_all_catalog
            ],200);

        }catch (\Exception $e) { 
            dd($e);
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        } 

    }


    public function add_catalog(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'catalog' => 'required|max:255',
            'currency' => 'required',
            'price_adjustment' => 'required|in:Price increase,Price decrease',
            'percentage' => 'required|numeric|min:0',
            'items' => 'required|array',
            'items.*.product_id' => 'required',
            'items.*.variant_id' => 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            // Create Catalog
            $catalog = new Catalog();
            $catalog->uuid = Str::uuid();
            $catalog->auth_id = Auth::user()->uuid;
            $catalog->catalog = $request->catalog;
            $catalog->status = $request->status;
            $catalog->currency = $request->currency;
            $catalog->price_adjustment = $request->price_adjustment;
            $catalog->percentage = $request->percentage;
            $catalog->slug = $request->catalog
                ? preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->catalog)) . '-' . Str::random(5)
                : Str::random(10);
            $catalog->save();

            // Insert products and variants with calculated prices
            foreach ($request->items as $item) {
                // Get the product price
                $product = Product::where('uuid', $item['product_id'])->first();
                $price = $product->unit_price; // Assuming this is the field name
                
                // If variant, get variant price
                if (!empty($item['variant_id'])) {
                    $variant = ProductStock::where('uuid', $item['variant_id'])->first();
                    if ($variant) {
                        $price = $variant->price;
                    }
                }

                // Calculate adjusted price
                $adjustedPrice = $price;
                if ($request->price_adjustment == 'Price increase') {
                    $adjustedPrice = $price * (1 + ($request->percentage / 100));
                } elseif ($request->price_adjustment == 'Price decrease') {
                    $adjustedPrice = $price * (1 - ($request->percentage / 100));
                }

                $catalogProduct = new CatalogProduct();
                $catalogProduct->uuid = Str::uuid();
                $catalogProduct->catalog_id = $catalog->uuid;
                $catalogProduct->product_id = $item['product_id'];
                $catalogProduct->variant_id = $item['variant_id'];
                $catalogProduct->price = $adjustedPrice;
                $catalogProduct->save();
            }

            return response()->json([
                'status_code' => 200,
                'message' => $this->get_message('update'),
            ], 200);

        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->errorInfo[1] == 1062) {
                return response()->json([
                    'status_code' => 409,
                    'message' => 'Duplicate entry: The catalog already exists.',
                ], 409);
            }

            return response()->json([
                'status_code' => 500,
                'message' => $this->get_message('server_error'),
            ], 500);

        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => 500,
                'message' => $this->get_message('server_error'),
            ], 500);
        }
    }


    public function edit_catalog($uuid){
    try {
        $edit_catalog_by_id = Catalog::with(['products.product.productStocks', 'currency'])->where('uuid', $uuid)->first();
        
        if(!$edit_catalog_by_id) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => $this->get_message('not_found'),
            ], Response::HTTP_NOT_FOUND);
        }
        
        return response()->json([
            'status_code' => 200,
            'data' => $edit_catalog_by_id
        ], 200);

    } catch(\Exception $e) {
        return response()->json([
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $e->getMessage(),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}


    public function update_catalog(Request $request)
{
    $validator = Validator::make($request->all(), [
        'catalog' => 'required|max:255',
        'currency' => 'required',
        'price_adjustment' => 'required|in:Price increase,Price decrease',
        'percentage' => 'required|numeric|min:0',
        'items' => 'required|array',
        'items.*.product_id' => 'required',
        'items.*.variant_id' => 'nullable'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'errors' => strval($validator->errors())
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    $uuid = request()->header('uuid');

    try {
        // Find the catalog
        $catalog = Catalog::where('uuid', $uuid)->first();
        if (!$catalog) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Catalog not found',
            ], Response::HTTP_NOT_FOUND);
        }

        // Update catalog fields
        $catalog->catalog = $request->catalog;
        $catalog->currency = $request->currency;
        $catalog->percentage = $request->percentage;
        $catalog->price_adjustment = $request->price_adjustment;
        $catalog->status = $request->status;
        if ($request->slug) {
            $catalog->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->slug));
        } else {
            $catalog->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->catalog)) . '-' . Str::random(5);
        }
        $catalog->save();

        // Sync products
        $catalog->products()->delete(); // Remove existing products
        
        foreach ($request->items as $item) {
            // Find the original product/variant to get price
            $product = Product::where('uuid', $item['product_id'])->first();
            $price = $product->unit_price;
            
            if (!empty($item['variant_id'])) {
                $variant = ProductStock::where('uuid', $item['variant_id'])->first();
                if ($variant) {
                    $price = $variant->price;
                }
            }
            
            // Calculate adjusted price
            $adjustedPrice = $price;
            if ($request->price_adjustment == 'Price increase') {
                $adjustedPrice = $price * (1 + ($request->percentage / 100));
            } elseif ($request->price_adjustment == 'Price decrease') {
                $adjustedPrice = $price * (1 - ($request->percentage / 100));
            }

            $catalog->products()->create([
                'uuid' => Str::uuid(),
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'original_price' => $price,
                'price' => $adjustedPrice
            ]);
        }

        return response()->json([
            'status_code' => 200,
            'message' => 'Catalog has been updated',
        ], 200);

    } catch (\Throwable $th) {
        return response()->json([
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $th->getMessage(),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}


    public function delete_catalog($uuid){

        try{

            $del_catalog = Catalog::where('uuid', $uuid)->first();
            
            if(!$del_catalog)
            {
                
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);


            }else{

                $delete_catalog = Catalog::destroy($del_catalog->id);

                if($delete_catalog){
                    
                    $del_catalog_translation = Catalog_translation::where('catalog_id', $del_catalog->id)->delete();

                    return response()->json([
                        
                        'status_code' => Response::HTTP_OK,
                        'message' => $this->get_message('delete'),
                    
                    ], Response::HTTP_OK);
    
                }

            }


        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        } 
        
    }

    
    public function updateCatalogStatus(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:0,1', // Ensure status is either 0 or 1
        ]);

        try {
            // Find the category by UUID and active status
            $catalog = Catalog::where('uuid', $id)->first();

            if ($catalog) {
                // Update the status
                $catalog->status = $request->status;
                $catalog->save();

                return response()->json([
                    'status_code' => 200,
                    'message' => $this->get_message('update'),
                ], 200);
            } else {
                return response()->json([
                    'status_code' => 404,
                    'message' => $this->get_message('not_found'),
                ], 404);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'status_code' => 500,
                'message' => $this->get_message('server_error'),
            ], 500);
        }

    }


    public function updateCatalogFeatured(Request $request, string $id)
    {
        $request->validate([
            'featured' => 'required|in:0,1',
        ]);

        try {
            // Find the category by UUID and active featured
            $catalog = Catalog::where('uuid', $id)->first();

            if ($catalog) {
                // Update the featured
                $catalog->featured = $request->featured;
                $catalog->save();

                return response()->json([
                    'featured_code' => 200,
                    'message' => $this->get_message('update'),
                ], 200);
            } else {
                return response()->json([
                    'featured_code' => 404,
                    'message' => $this->get_message('not_found'),
                ], 404);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'featured_code' => 500,
                'message' => $this->get_message('server_error'),
            ], 500);
        }

    }


    public function get_active_catalogs(){

        try{

            $get_all_active_catalog = Catalog::where('status', '1')->get();

            if($get_all_active_catalog){
                
                return response()->json([
                    'status_code' => Response::HTTP_OK,
                    'data' => $get_all_active_catalog,
                ], Response::HTTP_OK);
    
            }

        }catch (\Exception $e) { 

            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);

        } 
        
    } 
    

}
