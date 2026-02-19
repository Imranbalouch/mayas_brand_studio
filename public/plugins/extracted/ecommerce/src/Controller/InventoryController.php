<?php

namespace App\Http\Controllers\API\Ecommerce;

use AizPackages\CombinationGenerate\Services\CombinationService;
use App\Http\Controllers\Controller;
use App\Models\Ecommerce\AttributeValue;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;
use Mail;
use Auth;
use Session;
use Hash;
use DB;
use App\Models\Brand;
use App\Models\Brand_translation;
use App\Models\Menu;
use App\Models\Permission_assign;
use App\Models\Ecommerce\Product;
use App\Models\Ecommerce\ProductTranslation;
use App\Models\Language;
use App\Models\Ecommerce\ProductStock;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon; 
use Illuminate\Support\Str;
use App\Services\PermissionService;
use App\Services\ProductStockService;
use App\Traits\MessageTrait;
use App\Utility\ProductUtility;
use App\Models\Ecommerce\Attribute; 
use App\Models\Ecommerce\Inventory;
use App\Models\Ecommerce\InventoryAvailable;
use App\Models\Ecommerce\InventoryUnavailable;
use App\Models\Ecommerce\InventoryCommited;

class InventoryController extends Controller
{
    
    use MessageTrait;
    protected $permissionService;
    protected $productStockService;

    public function __construct(
        PermissionService $permissionService,
    )
    {
        $this->permissionService = $permissionService;
    }

    
    public function get_inventory(Request $request) 
    {
        try {
            $menuUuid = request()->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);  
            
            $inventories = ProductStock::with([
                'inventory',
                'product', // Make sure to include product relationship
                'location' // Include if you need warehouse info
            ])
            ->whereHas('product')
            ->whereHas('inventory');  

            // Location filter if selected
            if($request->location_id) {
                $inventories = $inventories->where('location_id', $request->location_id);
            }

            // Permission check logic
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $inventories = $inventories->where('auth_id', Auth::user()->uuid);
                }
            } else {
                if (!Auth::user()->hasPermission('viewglobal')) {
                    return response()->json([
                        'message' => 'You do not have permission to view this menu'
                    ], Response::HTTP_FORBIDDEN);
                }
            }

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = $request->search;
                $inventories = $inventories->where(function($query) use ($searchTerm) {
                    $query->whereHas('product', function($q) use ($searchTerm) {
                        $q->where('name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('sku', 'like', '%' . $searchTerm . '%');
                    })->orWhere('sku', 'like', '%' . $searchTerm . '%');
                });
            }

            // Get pagination parameters
            $perPage = $request->per_page ?? 10;
            $page = $request->page ?? 1;
            
            // Execute the query with pagination
            $paginatedInventories = $inventories->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);
            
            // Transform the data to match frontend expectations
            $inventoryData = $paginatedInventories->map(function($stock) { 
               // $committedOrders = InventoryCommited::where('inventory_id', $stock->id)->get(['order_id', 'qty']);
                //$orderCount = $committedOrders->sum('qty');
                
                return [
                    'inventory' => [
                        'id' => $stock->id,
                        'product_id' => $stock->product_id,
                        'location_id' => $stock->location_id,
                        'sku' => $stock->sku ?? $stock->product->sku,
                        'image' => $stock->product->thumbnail_img ?? null,
                        'variant' => $stock->variant ?? '',
                        'product' => [
                            'name' => $stock->product->name ?? '',
                            'vendor' => $stock->product->vendor ?? '',
                            'type' => $stock->product->type ?? '',
                            'sale_channel_id' => $stock->product->sale_channel_id ?? '',
                            'tags' => $stock->product->tags ?? []
                        ],
                        'inventory' => [
                            [
                                'stock_id' => $stock->id, // Assuming this is needed
                                'stock_uuid' => $stock->uuid // Assuming this is needed
                            ]
                        ]
                    ],
                    'net_unavailable_qty' => $stock->getNetUnavailableQty(),
                    'net_damaged_qty' => $stock->getNetDamagedQty(),
                    'net_quality_control_qty' => $stock->getNetQualityControlQty(),
                    'net_safety_stock_qty' => $stock->getNetSafetyStockQty(),
                    'net_other_qty' => $stock->getNetOtherQty(),
                    'inventory_on_hand' => $stock->inventory_on_hand(),
                    'committed_orders' => $stock->getNetCommittedQty(),
                    'order_count' => count($stock->getNetCommittedQty()),
                    'net_available_qty' => $stock->getNetAvailableQty(),
                    'in_coming' => 0,
                    'vendor' => $stock->product->vendor ?? '',
                    'type' => $stock->product->type ?? '',
                    'sale_channel_id' => $stock->product->sale_channel_id ?? '',
                    'tags' => $stock->product->tags ?? []
                ];
            });

            return response()->json([
                'status_code' => 200,
                'permissions' => $permissions,
                'data' => $inventoryData,
                'pagination' => [
                    'current_page' => $paginatedInventories->currentPage(),
                    'last_page' => $paginatedInventories->lastPage(),
                    'per_page' => $paginatedInventories->perPage(),
                    'total' => $paginatedInventories->total(),
                    'from' => $paginatedInventories->firstItem(),
                    'to' => $paginatedInventories->lastItem(),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function availableAdd(Request $request)
    {
        try { 
            if ($request->stock_sku == '' || empty($request->stock_sku) || $request->stock_sku == null) {
                // If stock_sku is empty, we can search by product_id and location_id only
                $productStock = ProductStock::where('location_id',$request->location_id)->where('product_id',$request->product_id)->where('uuid',$request->stock_id)->first();
            }else{
                $productStock = ProductStock::where('location_id',$request->location_id)->where('product_id',$request->product_id)->where('uuid',$request->stock_id)->where('variant_sku',$request->stock_sku)->first();
            }
          
        //  dd($productStock);
            if ($productStock != null) { 
                    $inventoryCreate = new Inventory;
                    $inventoryCreate->uuid = Str::uuid();
                    $inventoryCreate->location_id = $request->location_id;
                    $inventoryCreate->product_id = $request->product_id;
                    $inventoryCreate->stock_id = $request->stock_id;  
                    $inventoryCreate->status = $request->available_status;
                    $inventoryCreate->reason = $request->available_reason;
                    $inventoryCreate->qty = $request->available_adjust_qty;  
                    $inventoryCreate->save(); 
                    $productStock->qty = $productStock->getNetAvailableQty();
                    $productStock->save();
                    return response()->json(['status' => 200, 'message' => 'Inventory updated successfully.'],200);
                 
            }else{
                return response()->json(['status' => 404, 'message' => 'Product Not Found.'],404);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'message' => $th->getMessage()],500);
        }
    }


    public function unAvailableAdd(Request $request)
    {
        try { 
            if ($request->stock_sku == '' || empty($request->stock_sku) || $request->stock_sku == null) {
            //    dd("DD",$request->stock_sku);
            $productStock = ProductStock::where('location_id',$request->location_id)->where('product_id',$request->product_id)->where('uuid',$request->stock_id)->first();
            }else{
            $productStock = ProductStock::where('location_id',$request->location_id)->where('product_id',$request->product_id)->where('uuid',$request->stock_id)->where('variant_sku',$request->stock_sku)->first();
            }
            // $productStock = ProductStock::where('location_id', $request->location_id)
            //     ->where('product_id', $request->product_id)
            //     ->when($request->has('stock_sku'), function ($query) use ($request) {
            //         $query->where('sku', $request->stock_sku);
            //     })
            //     ->first();
            //  dd($request->all());
        if ($productStock != null) { 
                $inventoryCreate = new Inventory;
                $inventoryCreate->uuid = Str::uuid();
                $inventoryCreate->location_id = $request->location_id;
                $inventoryCreate->product_id = $request->product_id;
                $inventoryCreate->stock_id = $request->stock_id;  
                $inventoryCreate->status = $request->available_status;
                $inventoryCreate->reason = $request->available_reason;
                $inventoryCreate->qty = $request->available_adjust_qty;  
                $inventoryCreate->save(); 
                $productStock->qty = $productStock->getNetAvailableQty();
                $productStock->save();
                return response()->json(['status' => 200, 'message' => 'Inventory updated successfully.'],200);    
        }else{
                return response()->json(['status' => 404, 'message' => 'Product Not Found.'],404);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'message' => $th->getMessage()],500);
        }
    }





    public function unavailableDelete(Request $request)
    {
        try { 
            $productStock = ProductStock::where('location_id',$request->location_id)->where('product_id',$request->product_id)->where('sku',$request->stock_sku)->first();
            //  dd($request->all());
        if ($productStock != null) { 
                $inventoryCreate = new Inventory;
                $inventoryCreate->uuid = Str::uuid();
                $inventoryCreate->location_id = $request->location_id;
                $inventoryCreate->product_id = $request->product_id;
                $inventoryCreate->stock_id = $request->stock_id;  
                $inventoryCreate->status = $request->unavailable_status;
                $inventoryCreate->reason = $request->unavailable_reason;
                $inventoryCreate->qty = $request->unavailable_delete_qty;  
                $inventoryCreate->save(); 
                $productStock->qty = $productStock->getNetAvailableQty();
                $productStock->save();
                return response()->json(['status' => 200, 'message' => 'Inventory updated successfully.'],200);    
        }else{
                return response()->json(['status' => 404, 'message' => 'Product Not Found.'],404);
            }
        } catch (\Throwable $th) {
            return response()->json(['status' => 500, 'message' => $th->getMessage()],500);
        }
    }



    public function inventory_detail(Request $request,string $id)
    { 
        $menuUuid = request()->header('menu-uuid');
        $permissions = $this->permissionService->checkPermissions($menuUuid); 
        $date_range = null; 
        $inventory = Inventory::with(['product', 'product_stock'])->whereHas('product_stock', function ($query) use ($id) {
            $query->where('uuid', $id);
        }); 
         
        // Permission check logic
        if ($permissions['view']) {
            if (!$permissions['viewglobal']) {
                $inventory = $inventory->where('auth_id', Auth::user()->uuid);
            }
        } else {
            if (!Auth::user()->hasPermission('viewglobal')) {
                return response()->json([
                    'message' => 'You do not have permission to view this menu'
                ], Response::HTTP_FORBIDDEN);
            }
        } 
        $inventory = $inventory->get();  
        if ($inventory != null) {
            return response()->json(['status_code' => 200, 'permissions' => $permissions, 'data' => $inventory, 'date_range' => $date_range],200);
        }else{
            return response()->json(['status_code' => 404, 'message' => 'Inventory Not Found.'],404);
        }

    }



}
