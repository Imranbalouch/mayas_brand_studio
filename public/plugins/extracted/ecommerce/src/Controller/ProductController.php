<?php

namespace App\Http\Controllers\API\Ecommerce;

use Hash;
use Mail;
use Session;
use Carbon\Carbon;
use App\Models\Menu;
use App\Models\User;
use App\Models\Brand;
use App\Models\Ecommerce\Product;
use App\Models\Ecommerce\Category;
use App\Models\Ecommerce\Discount;
use App\Models\Language;
use App\Models\Ecommerce\Attribute;
use App\Models\Ecommerce\Inventory;
use App\Models\Ecommerce\Collection;
use App\Models\Ecommerce\ProductTag;
use App\Models\FileManager;
use App\Models\Ecommerce\ProductTemp;
use App\Models\Ecommerce\ProductType;
use Illuminate\Support\Str;
use App\Models\Ecommerce\ProductStock;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use App\Models\Ecommerce\ProductFilter;
use App\Models\Ecommerce\ProductVendor;
use App\Jobs\ProductImportJob;
use App\Models\Ecommerce\AttributeValue;
use App\Exports\ProductsExport;
use App\Utility\ProductUtility;
use Illuminate\Validation\Rule;
use App\Imports\ProductsImport; 
use App\Models\Ecommerce\ProductDiscounts;
use App\Models\Brand_translation;
use App\Models\Permission_assign;
use App\Models\Ecommerce\InventoryAvailable;
use App\Models\Ecommerce\ProductCollections;
use App\Models\Ecommerce\ProductTranslation;
use Illuminate\Support\Facades\DB;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\ProductStockService;
use App\Models\Ecommerce\MasterImportProductLog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\ToArray;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;  
use Symfony\Component\HttpFoundation\Response;
use App\Models\Ecommerce\ProductTemp as ModelsProductTemp;

class ProductController extends Controller
{
    use MessageTrait;
    protected $permissionService;
    protected $productStockService;

    public function __construct(
        PermissionService $permissionService,
        ProductStockService $productStockService
    )
    {
        $this->permissionService = $permissionService;
        $this->productStockService = $productStockService;
    }
    
    public function get_product(Request $request)
{
    try {
        $menuUuid = request()->header('menu-uuid'); 
        $permissions = $this->permissionService->checkPermissions($menuUuid); 

        if (!$permissions['view']) {
            if (!Auth::user()->hasPermission('viewglobal')) {
                return response()->json([
                    'message' => 'You do not have permission to view this menu'
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $perPage = $request->get('per_page', 10); // default items per page

        $search = $request->get('search'); // search keyword
        
        $query = Product::select(
                'id', 'vendor', 'type', 'uuid', 'name', 'status', 
                'product_type', 'giftcard_product_id', 'thumbnail_img', 
                'unit_price', 'created_at', 'updated_at'
            )
            ->with([
                'categories:id,name',
                'collections',
                'salesChannels',
                'markets',
                'productStocks' => function ($query) {
                    $query->selectRaw('product_id, variant, SUM(qty) as total_qty')
                          ->groupBy('product_id', 'variant');
                }
            ])
            ->orderBy('id', 'desc');

        // Filter by user permissions
        if (!$permissions['viewglobal']) {
            $query->where('auth_id', Auth::user()->uuid);
        }

        // Search filter
        if (!empty($search)) {
           // dd($search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('vendor', 'like', "%{$search}%")
                  ->orWhereHas('categories', function ($cat) use ($search) {
                      $cat->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('collections', function ($col) use ($search) {
                      $col->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Check if "all" is selected for per_page
        if ($perPage === 'all') {
            $products = $query->get(); // Fetch all products
            $total = $products->count(); // Total number of products

            // Transform the collection
            $transformedProducts = $products->transform(function ($product) {
                $product->category_name = $product->categories->pluck('name')->implode(', ');
                $product->collection_name = $product->collections->pluck('name')->implode(', ');
                $product->sale_channel_name = $product->salesChannels->pluck('name')->implode(', ');
                $product->market_name = $product->markets->pluck('market_name')->implode(', ');
                $product->total_qty = $product->productStocks->sum('total_qty');
                $product->total_variations = $product->totalVariations();
                return $product;
            });

            // Create a pagination-like structure
            $data = [
                'current_page' => 1,
                'data' => $transformedProducts,
                'first_page_url' => $request->fullUrlWithQuery(['per_page' => 'all', 'page' => 1]),
                'from' => 1,
                'last_page' => 1,
                'last_page_url' => $request->fullUrlWithQuery(['per_page' => 'all', 'page' => 1]),
                'links' => [
                    ['url' => null, 'label' => '&laquo; Previous', 'active' => false],
                    ['url' => $request->fullUrlWithQuery(['per_page' => 'all', 'page' => 1]), 'label' => '1', 'active' => true],
                    ['url' => null, 'label' => 'Next &raquo;', 'active' => false],
                ],
                'next_page_url' => null,
                'path' => $request->url(),
                'per_page' => 'all',
                'prev_page_url' => null,
                'to' => $total,
                'total' => $total,
            ];
        } else {
            $products = $query->paginate($perPage); // Paginate based on per_page value

            // Transform the collection
            $transformedProducts = $products->getCollection()->transform(function ($product) {
                $product->category_name = $product->categories->pluck('name')->implode(', ');
                $product->collection_name = $product->collections->pluck('name')->implode(', ');
                $product->sale_channel_name = $product->salesChannels->pluck('name')->implode(', ');
                $product->market_name = $product->markets->pluck('market_name')->implode(', ');
                $product->total_qty = $product->productStocks->sum('total_qty');
                $product->total_variations = $product->totalVariations();
                return $product;
            });

            // Update the collection in the paginator
            $products->setCollection($transformedProducts);
            $data = $products; // Paginator object already has the required structure
        }

        return response()->json([
            'status_code' => 200,
            'permissions' => $permissions,
            'data' => $data,
            'sale_rate' => 0,
            'inventory_remaining' => 0,
            'analysis' => 'No data',
        ], 200);

    } catch (\Exception $e) {
        dd($e);
        return response()->json([
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $this->get_message('server_error'),
            'error' => $e->getMessage(),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

    public function get_products_by_ids(Request $request)
    {
        try {
            $menuUuid = request()->header('menu-uuid'); 
            $permissions = $this->permissionService->checkPermissions($menuUuid); 

            if (!$permissions['view']) {
                if (!Auth::user()->hasPermission('viewglobal')) {
                    return response()->json([
                        'message' => 'You do not have permission to view this menu'
                    ], Response::HTTP_FORBIDDEN);
                }
            }

            // Validate request - expect product_ids array
            $request->validate([
                'product_ids' => 'required|array',
                'product_ids.*' => 'required|string|uuid'
            ]);

            $productIds = $request->get('product_ids');
            
            if (empty($productIds)) {
                return response()->json([
                    'status_code' => 200,
                    'permissions' => $permissions,
                    'data' => [],
                    'message' => 'No product IDs provided'
                ], 200);
            }

            $query = Product::select(
                    'id', 'vendor', 'type', 'uuid', 'name', 'status', 
                    'product_type', 'giftcard_product_id', 'thumbnail_img', 
                    'unit_price', 'created_at', 'updated_at'
                )
                ->with([
                    'categories:id,name',
                    'collections',
                    'salesChannels',
                    'markets',
                    'productStocks' => function ($query) {
                        $query->selectRaw('product_id, variant, SUM(qty) as total_qty')
                            ->groupBy('product_id', 'variant');
                    }
                ])
                ->whereIn('uuid', $productIds)
                ->orderBy('id', 'desc');

            // Filter by user permissions
            if (!$permissions['viewglobal']) {
                $query->where('auth_id', Auth::user()->uuid);
            }

            $products = $query->get();

            // Transform the collection
            $transformedProducts = $products->transform(function ($product) {
                $product->category_name = $product->categories->pluck('name')->implode(', ');
                $product->collection_name = $product->collections->pluck('name')->implode(', ');
                $product->sale_channel_name = $product->salesChannels->pluck('name')->implode(', ');
                $product->market_name = $product->markets->pluck('market_name')->implode(', ');
                $product->total_qty = $product->productStocks->sum('total_qty');
                $product->total_variations = $product->totalVariations();
                return $product;
            });

            // Sort products to match the order of input IDs (optional)
            $sortedProducts = collect($productIds)->map(function ($id) use ($transformedProducts) {
                return $transformedProducts->firstWhere('uuid', $id);
            })->filter(); // Remove null values if any IDs weren't found

            return response()->json([
                'status_code' => 200,
                'permissions' => $permissions,
                'data' => $sortedProducts->values(), // Reset array keys
                'requested_count' => count($productIds),
                'found_count' => $sortedProducts->count(),
                'missing_ids' => collect($productIds)->diff($transformedProducts->pluck('uuid'))->values()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'Validation failed',
                'errors' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
            
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function get_products_with_channels(){
        try {
        $menuUuid = request()->header('menu-uuid'); 
        $permissions = $this->permissionService->checkPermissions($menuUuid); 
        
        $channelUuid = request()->input('channel_uuid');
        $filter = request()->input('filter', 'all');
        
        \Log::info('Product fetch request', [
            'channel_uuid' => $channelUuid,
            'filter' => $filter
        ]);
        
        $channelRecords = \DB::table('product_channels')
            ->where('channel_uuid', $channelUuid)
            ->get();
        
        \Log::info('Direct database check of product_channels', [
            'channel_uuid' => $channelUuid,
            'records_count' => $channelRecords->count(),
            'records' => $channelRecords->toArray()
        ]);
        
        $sampleProductUuid = Product::first()->uuid ?? null;
        
        \Log::info('Sample product UUID format', [
            'sample_uuid' => $sampleProductUuid,
            'type' => gettype($sampleProductUuid),
            'length' => $sampleProductUuid ? strlen($sampleProductUuid) : 0
        ]);
        
        $productUuidSample = \DB::table('products')->select('uuid')->first();
        $channelUuidSample = \DB::table('product_channels')->select('channel_uuid')->first();
        
        \Log::info('UUID format comparison', [
            'product_uuid_sample' => $productUuidSample ? $productUuidSample->uuid : null,
            'channel_relation_uuid_sample' => $channelUuidSample ? $channelUuidSample->channel_uuid : null
        ]);
        
        $rawSql = "SELECT p.id, p.uuid as product_uuid, pc.channel_uuid 
                  FROM products p 
                  INNER JOIN product_channels pc ON p.uuid = pc.product_uuid 
                  WHERE pc.channel_uuid = ?";
        
        $rawResults = \DB::select($rawSql, [$channelUuid]);
        
        \Log::info('Raw SQL join results', [
            'count' => count($rawResults),
            'results' => $rawResults
        ]);
        
        $query = Product::select('id', 'vendor', 'type', 'uuid', 'name', 'status', 'thumbnail_img', 'unit_price', 'created_at', 'updated_at')
            ->orderBy('id', 'desc');

        if ($permissions['view']) {
            if (!$permissions['viewglobal']) {
                $query = $query->where('auth_id', Auth::user()->uuid);
            }
        } else {
            if (!Auth::user()->hasPermission('viewglobal')) {
                return response()->json([
                    'message' => 'You do not have permission to view this menu'
                ], Response::HTTP_FORBIDDEN);
            }
        }
        
         $productIds = \DB::table('product_channels')
            ->where('channel_uuid', $channelUuid)
            ->pluck('product_uuid');

        $includedQuery = Product::whereIn('uuid', $productIds);
        $excludedQuery = Product::whereNotIn('uuid', $productIds);
        
        if (!$permissions['viewglobal']) {
            $includedQuery = $includedQuery->where('auth_id', Auth::user()->uuid);
            $excludedQuery = $excludedQuery->where('auth_id', Auth::user()->uuid);
        }
        
        \DB::enableQueryLog();
        $includedCount = $includedQuery->count();
        $excludedCount = $excludedQuery->count();
        $includedQueries = \DB::getQueryLog();
        \DB::flushQueryLog();
        
        \Log::info('Included query SQL', [
            'queries' => $includedQueries
        ]);
        
        \Log::info('Product counts', [
            'included' => $includedCount,
            'excluded' => $excludedCount
        ]);
        
        $reflector = new \ReflectionClass(Product::class);
        $method = $reflector->getMethod('salesChannels');
        
        \Log::info('Relationship method', [
            'exists' => $reflector->hasMethod('salesChannels'),
            'parameters' => $method ? $method->getParameters() : []
        ]);
        
        if ($filter === 'included') {
             $productIds = \DB::table('product_channels')
        ->where('channel_uuid', $channelUuid)
        ->pluck('product_uuid');
    
    $query->whereIn('uuid', $productIds);
        } elseif ($filter === 'excluded') {
             $productIds = \DB::table('product_channels')
        ->where('channel_uuid', $channelUuid)
        ->pluck('product_uuid');
    
    $query->whereNotIn('uuid', $productIds);
        }
        
        $products = $query->get();
        
        \Log::info('Products found after filtering', [
            'filter' => $filter,
            'count' => $products->count()
        ]);
        
        $products->load([
            'productStocks' => function ($query) {
                $query->selectRaw('product_id, variant, SUM(qty) as total_qty')
                    ->groupBy('product_id', 'variant');
            },
            'salesChannels' => function ($query) use ($channelUuid) {
                $query->where('channel_uuid', $channelUuid);
            },
            'categories',
            'collections',
            'markets'
        ]);
        
        $processedProducts = $products->map(function ($product) use ($channelUuid) {
            $categories = $product->categories->pluck('name')->implode(', ');
            $product->category_name = $categories;
            $collections = $product->collections->pluck('name')->implode(', ');
            $product->collection_name = $collections;
            $salesChannels = $product->salesChannels->pluck('name')->implode(', ');
            $product->sale_channel_name = $salesChannels;
            $markets = $product->markets->pluck('market_name')->implode(', ');
            $product->market_name = $markets;
            $product->total_qty = $product->productStocks->sum('total_qty');
            $product->total_variations = $product->totalVariations();
            
            $product->isIncluded = \DB::table('product_channels')
                ->where('product_uuid', $product->uuid)
                ->where('channel_uuid', $channelUuid)
                ->exists();            
            \Log::info('Product inclusion check', [
                'product_id' => $product->id,
                'product_uuid' => $product->uuid,
                'channel_uuid' => $channelUuid,
                'isIncluded' => $product->isIncluded,
                'channels_count' => $product->salesChannels->count(),
                'channels' => $product->salesChannels->pluck('channel_uuid')->toArray()
            ]);
            
            $product->makeHidden(['categories', 'collections', 'markets', 'salesChannels']); 
            return $product;
        });
        
        $productsArray = $processedProducts->values()->all();
        
        \Log::info('Response products count', [
            'filter' => $filter,
            'count' => count($productsArray)
        ]);

        return response()->json([
            'status_code' => 200,
            'permissions' => $permissions,
            'data' => $productsArray,
            'counts' => [
                'included' => $includedCount,
                'excluded' => $excludedCount
            ],
            'sale_rate' => 0,
            'inventory_remaining' => 0,
            'analysis' => 'No data',
        ], 200);
    
    } catch (\Exception $e) {
        \Log::error('Error in get_product', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $this->get_message('server_error'),
            'error' => $e->getMessage(), 
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    }

    public function toggle_channel_inclusion() {
    try {
        $menuUuid = request()->header('menu-uuid'); 
        $permissions = $this->permissionService->checkPermissions($menuUuid); 
        
        // Check if user has edit permission
        if (!$permissions['edit']) {
            return response()->json([
                'message' => 'You do not have permission to edit products'
            ], Response::HTTP_FORBIDDEN);
        }
        
        $channelUuid = request()->input('channel_uuid');
        $productUuids = request()->input('product_uuids', []);
        $include = request()->input('include', true);
        
        if (!$channelUuid || empty($productUuids)) {
            return response()->json([
                'status_code' => 400,
                'message' => 'Channel UUID and product UUIDs are required'
            ], 400);
        }
        
       
        
        // Get products by UUIDs
        $products = Product::whereIn('uuid', $productUuids)->get();
        
        \DB::beginTransaction();
        try {
            foreach ($products as $product) {
                if ($include) {
                    // Check if relationship already exists
                    $exists = \DB::table('product_channels')
                        ->where('product_uuid', $product->uuid)
                        ->where('channel_uuid', $channelUuid)
                        ->exists();
                    
                    if (!$exists) {
                        // Add product to channel
                        \DB::table('product_channels')->insert([
                            'product_uuid' => $product->uuid,
                            'channel_uuid' => $channelUuid,
                        ]);
                    }
                } else {
                    // Remove product from channel
                    \DB::table('product_channels')
                        ->where('product_uuid', $product->uuid)
                        ->where('channel_uuid', $channelUuid)
                        ->delete();
                }
            }
            
            \DB::commit();
            
            return response()->json([
                'status_code' => 200,
                'message' => $include ? 'Products added to channel successfully' : 'Products removed from channel successfully'
            ], 200);
            
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
        
    } catch (\Exception $e) {
        \Log::error('Error in toggle_channel_inclusion', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $this->get_message('server_error'),
            'error' => $e->getMessage(), // Include error message for debugging
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
    
    public function get_product_types() {
        try { 
            $get_all_product_types = Product::select('type')->distinct()->where('type','!=',null)->orderBy('type', 'asc');  
             $get_all_product_types = $get_all_product_types->get(); 
            // dd($get_all_product_types->toSQL());
            //dd(count($get_all_product_types->get()));
            return response()->json([
                'status_code' => 200, 
                'data' => $get_all_product_types
            ], 200);
    
        } catch (\Exception $e) {
            // Handle general exceptions and return an internal server error response
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function get_product_vendors() {
        try {  
            $get_all_product_vendors = Product::select('vendor')->distinct()->where('vendor','!=',null)->orderBy('vendor', 'asc');
            $get_all_product_vendors = $get_all_product_vendors->get(); 
            return response()->json([
                'status_code' => 200, 
                'data' => $get_all_product_vendors
            ], 200);
    
        } catch (\Exception $e) {
            // Handle general exceptions and return an internal server error response
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function get_active_tags() {
        try {
            // Fetch all product tags
            $productTags = Product::select('tags')
                ->where('tags', '!=', null)
                ->get(); 
            $distinctTags = []; 
            $tags = [];  
            foreach ($productTags as $productTag) { 
                $array=json_decode($productTag->tags); 
              
                foreach ($array as $item) { 
                    $tags = array_merge($tags, explode(',', $item));
                }  
                $distinctTags = array_values(array_unique($tags));
                
            }  
            return response()->json([
                'status_code' => 200,
                'data' => $distinctTags
            ], 200);
    
        } catch (\Exception $e) {
            // Handle general exceptions and return an internal server error response
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    
    public function get_product_tags() {
        try { 
            $get_all_product_types = ProductTag::select('name')->orderBy('name', 'asc');  
            $get_all_product_types = $get_all_product_types->get(); 
            return response()->json([
                'status_code' => 200, 
                'data' => $get_all_product_types
            ], 200);
    
        } catch (\Exception $e) {
            // Handle general exceptions and return an internal server error response
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function get_product_collections() {
        try { 
            $get_all_product_types = Collection::select('name', 'uuid')->orderBy('name', 'asc');  
            $get_all_product_types = $get_all_product_types->get(); 
            return response()->json([
                'status_code' => 200, 
                'data' => $get_all_product_types
            ], 200);
    
        } catch (\Exception $e) {
            // Handle general exceptions and return an internal server error response
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    

    public function get_filter_views() {
        try { 
            $get_all_product = ProductFilter::select('name','uuid','search_type')->where('auth_id',Auth::user()->uuid)->orderBy('name', 'asc');  
            $get_all_product = $get_all_product->get(); 
            return response()->json([
                'status_code' => 200, 
                'data' => $get_all_product
            ], 200);
    
        } catch (\Exception $e) {
          //  dd($e);
            // Handle general exceptions and return an internal server error response
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    public function add_filter_view(Request $request) {
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255', 
            'search_type' => 'required|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $check_if_already = ProductFilter::where('name', $request->name)->get();
               if(count($check_if_already) > 0){ 
                return response()->json([ 
                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'This name has already been taken.', 
                ], Response::HTTP_CONFLICT); // 409 Conflict 


            }else{
            // Create filter
            $filter = new ProductFilter();
            $filter->uuid = Str::uuid();
            $filter->auth_id = Auth::user()->uuid;
            $filter->name = $request->name;
            $filter->search_type = $request->search_type;  
            $filter->save(); 
            return response()->json([
                'status_code' => 200,
                'message' => 'Filter has been added',
            ], 200);
            }
        } catch (\Exception $e) {
          // dd($e); 
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update_filter_view(Request $request) {
        $uuid = request()->header('uuid'); 
         //dd($uuid);
        try { 

            $check_if_already = ProductFilter::where('name', $request->name)->get();
               if(count($check_if_already) > 0){ 
                return response()->json([ 
                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'This name has already been taken.', 
                ], Response::HTTP_CONFLICT); // 409 Conflict 


            }else{

            $filter = ProductFilter::where('uuid', $uuid)->first();  
            $filter->name = $request->name; 
            $filter->save();
            return response()->json([
                'status_code' => 200,
                'message' => 'Filter has been updated',
            ], 200);
        }
        } catch (\Exception $e) {
          // dd($e); 
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function delete_filter_view($uuid){

        try{ 
            $del_filter = ProductFilter::where('uuid', $uuid)->first(); 
            if(!$del_filter){ 
                return response()->json([ 
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Record not found' 
                ], Response::HTTP_NOT_FOUND); 
            }else{ 
                $delete_data = ProductFilter::destroy($del_filter->id); 
                if($delete_data){ 
                    return response()->json([ 
                        'status_code' => Response::HTTP_OK,
                        'message' => 'Filter has been deleted', 
                    ], Response::HTTP_OK); 
                } 
            } 
        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([ 
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Server error',
                'error' => $e->getMessage(), 
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } 
        
    }



    public function get_active_products(Request $request) {
        try {  
            $get_all_products = Product:: where('status', '1')->with(['productStocks' => function ($query) use ($request) {
            $query->where('location_id', $request->location_id);
        }])
            ->orderBy('id', 'desc');

        if ($request->has('location_id') && $request->location_id != '') { 
            $get_all_products->whereHas('productStocks', function ($query) use ($request) {
                $query->where('location_id', $request->location_id);
            });
           // dd($get_all_products);
        }

        $get_all_products = $get_all_products->get();
        //dd($get_all_products);
            return response()->json([
                'status_code' => 200, 
                'data' => $get_all_products
            ], 200);
    
        } catch (\Exception $e) {
            // Handle general exceptions and return an internal server error response
            dd($e);
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
      

    public function add_product(Request $request)
    { 
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                //'regex:/^[a-zA-Z0-9_ ]+$/',
                'regex:/^[\w\s\-]+$/',
                'min:1', 
                'max:255' 
            ],
            'slug' => [
                'required',
                'min:1',  
                'max:255', 
                Rule::unique('products', 'slug')
            ] 
        ],[
            'name.required' => 'The name field is required.',
            'slug.unique' => 'The product slug has already been taken..',
        ]);
        //dd($request->name,$validator->fails());
        if($validator->fails()) {
            $message = $validator->messages();
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($message) 
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }   


        try { 
            $product = new Product();
            $product->uuid = Str::uuid();
            $product->auth_id = Auth::user()->uuid;


            $product->name = $request->name; 
            $product->description = $request->description;
            if ($request->has('images') && !empty($request->images)) { 
                $images = $request->images; 
                $imageArray = explode(',', $images);  
                $thumbnailImg = trim($imageArray[0]); 
                //$restOfImages = array_slice($imageArray, 1);  // if first image will not incluedes in gallery images
                $restOfImages = $imageArray; 
                $product->thumbnail_img = $thumbnailImg;
                $product->images = implode(',', $restOfImages);
            } 
           // dd($request->category_id);
            // if($request->has('category_id') && !empty($request->category_id)){
            //     $product->category_id = json_encode($request->category_id);
            // } 
            $product->unit_price = $request->unit_price ?? 0.00; 
            $product->compare_price = $request->compare_price ?? 0.00;
            $product->cost_per_item = $request->cost_per_item ?? 0.00;
            $product->sku =$request->simple_sku ?? '';
            $product->barcode =$request->simple_barcode ?? '';
            $product->weight =$request->sipmle_shipping_weight ?? 0.00;
            $product->unit =$request->sipmle_shipping_weight_unit ?? '';
            $product->country_id =$request->simple_country_id ?? '';
            $product->hs_code =$request->simple_hscode ?? '';
            $product->published_date_time =$request->published_date_time;

            $product->choice_options = ($request->varient_data_view ?? json_encode([]));
                if($request->variation_data!=''){
                    $product->varient_data = json_encode($request->variation_data, JSON_UNESCAPED_UNICODE);
                }else{
                    $product->varient_data = json_encode([]);
                } 
            $product->meta_title = $request->meta_title;
            $product->meta_description = $request->meta_description; 
            if ($request->slug) {
                $product->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->slug));
            } else {
                $product->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->name)) . '-' . Str::random(5);
            }
            $product->status =$request->status ?? 0;
           // dd($request->status);
            $product->salesChannels()->sync($request->sale_channel_id, ['product_uuid' => $product->uuid]);
            $product->markets()->sync($request->market_id, ['product_uuid' => $product->uuid]);
            $product->type =$request->type ?? '';
            $product->vendor =$request->vendor ?? '';


            if($request->has('discount_id') && !empty($request->discount_id)){
              ProductDiscounts::where('di_id', $request->discount_id)->delete();
              
              $discount = Discount::where('uuid', $request->discount_id)->first();
                if ($discount) {
                    ProductDiscounts::updateOrCreate(
                        ['product_id' => $product->uuid], 
                        [
                            'di_id'  => $request->discount_id,
                            'auth_id'   => Auth::user()->uuid,
                            'value'  => $discount->value,
                            'method' => $discount->method,
                            'type'   => $discount->type,
                        ]
                    );
                }
            }
            
            // $product->collections()->sync($request->collection_id, ['product_uuid' => $product->uuid]); 
            if ($request->has('tags') && !empty($request->tags)) {
                if (is_array($request->tags)) {
                    $product->tags = implode(',', $request->tags);
                } 
                else {
                    $product->tags = $request->tags;
                }
            }
            $product->template_product =$request->template_product ?? '';
           
            $product->vat_id = $request->vat_id ?? 0;
            $product->tax_enabled = $request->tax_enabled ?? 0;
            $product->inventory_track_enabled = $request->track_quantity?? 0;
            $product->selling_stock_enabled = $request->continue_selling_out_of_stock?? 0;
            $product->sku_barcode_enabled = $request->sku_barcode_enabled?? 0;
            $product->physical_product_enabled = $request->physical_product_enabled?? 0; 
            
           // $product->choice_options = json_encode($request->varient_data_view ?? []);
            //dd($product); 
            DB::beginTransaction();
            
            $product->save(); 
            // collection
            if ($request->has('collection_id')) {
                $collectionSyncData = [];
                $collections = Collection::whereIn('uuid', $request->collection_id)->pluck('id','uuid')->toArray();
                foreach ($collections as $uuid => $categoryid) {
                    $collectionSyncData[$categoryid] = [
                        'product_uuid' => $product->uuid,
                        'collection_uuid' => $uuid,
                    ];
                }            
                $product->collections()->sync($collectionSyncData);
            }
            
            //category
            if ($request->has('category_id')) {
                $categorySyncData = [];
                $categories = Category::whereIn('uuid', $request->category_id)->pluck('id','uuid')->toArray();
                foreach ($categories as $uuid => $categoryid) {
                    $categorySyncData[$categoryid] = [
                        'product_uuid' => $product->uuid,
                        'category_uuid' => $uuid,
                    ];
                }
                $product->categories()->sync($categorySyncData);
            }
            // dd($product); 
                            $variation_data = []; 
                        if ($request->has('varient_data_view') && !empty($request->varient_data_view)) { 
                            if ($request->variation_data!="") {
                                //dd($request->varient_data_view);
                                //$variation_data=json_decode($request->variation_data);
                                $variation_dataRaw=json_decode($request->variation_data,JSON_UNESCAPED_UNICODE);
                                //dd($variation_data);
                                // dd([
                                //     'data' => $variation_dataRaw,
                                //     'count' => count($variation_dataRaw)
                                // ]);
                                if (count($variation_dataRaw) > 0) {
                                    foreach ($variation_dataRaw as $key => $variation_data) {
                                        if($key!='all'){
                                            if(count($variation_data)>0){
                                                foreach ($variation_data as $key => $variation) {
                                                // dd($product->uuid);
                                                    $variant_product_id=$product->uuid;
                                                    $variant=$variation['variantName'];
                                                    $variant_price=$variation['variantPrice']; 
                                                    $variant_sku=$variation['SKU'];
                                                    $variant_qty=$variation['variantQuantity'];
                                                    $variant_image=$variation['selectedImageFile'];
                                                    $variant_location_id=$variation['location_id'];
                                                    
                                         
                                                        $product_stock = new ProductStock();
                                                        $product_stock->uuid = Str::uuid();
                                                        $product_stock->product_id = $variant_product_id; 
                                                        $product_stock->variant = $variant;
                                                        $product_stock->price = $variant_price;
                                                        $product_stock->sku = $variant_sku;
                                                        $product_stock->variant_sku = $variant_sku;;
                                                        $product_stock->cost_per_item = $variation['variantCostPrice'];
                                                        $product_stock->barcode = $variation['variantBarCode'];
                                                        $product_stock->hs_code = $variation['variantHSCode'];
                                                        $product_stock->qty = $variant_qty;
                                                        $product_stock->image = $variant_image;
                                                        $product_stock->location_id = $variant_location_id;
                                                        $product_stock->auth_id = Auth::user()->uuid;
                                                        $product_stock->save(); 
                                                     
                                                        if($product->inventory_track_enabled==1){    
                                                            $inventory = new Inventory();
                                                            $inventory->uuid = Str::uuid();
                                                            $inventory->product_id = $variant_product_id;
                                                            $inventory->stock_id = $product_stock->uuid; // ProductStock ID
                                                            $inventory->location_id = $variant_location_id;
                                                            $inventory->status = 'opening';
                                                            $inventory->reason = 'opening'; 
                                                            $inventory->sku = $variant_sku ?? $product->sku;
                                                            $inventory->price = $variant_price;
                                                            $inventory->qty = $variant_qty;
                                                            $inventory->auth_id = Auth::user()->uuid; 
                                                            $inventory->save(); 
                                                        }
                                                }
                                            }  
                                        }
                                    }
                                }
                            }
                        } else {
                            $warehouse_location_ids= explode(',', $request->warehouse_location_id); 
                                    // Simple Product
                                    foreach ($warehouse_location_ids as $key => $location_id) { 
                                        $qty = $request->location_stock[$key]; 
                                        $price = $request->unit_price; 
                                        $data['uuid'] = Str::uuid();
                                        $data['product_id'] = $product->uuid; 
                                        $data['auth_id'] = Auth::user()->uuid;
                                        $data['image']=$product->thumbnail_img;
                                        $data['location_id'] = $location_id; 
                                        $data['variant'] = ''; 
                                        $data['qty'] = $qty; 
                                        $data['price'] = $price; 
                                        $data['variant_sku'] = $request->simple_sku; 
                                        $data['barcode'] = $request->simple_barcode; 
                                        $data['hs_code'] = $request->simple_hscode; 
                                        $data['cost_per_item'] = $request->cost_per_item;
                                        $data['compare_price'] = $request->compare_price;
                                         //dd($data);
                                        $product_stock = ProductStock::create($data); 
                                       // dd($product_stock);
                                        // Create Inventory record
                                        if($product->inventory_track_enabled==1){
                                            $inventory = new Inventory();
                                            $inventory->uuid = Str::uuid();
                                            $inventory->product_id = $product->uuid; 
                                            $inventory->stock_id = $product_stock->uuid; // ProductStock ID
                                            $inventory->location_id = $location_id;
                                            $inventory->status = 'opening';
                                            $inventory->reason = 'opening'; 
                                            $inventory->sku = $product_stock->sku ?? $product->sku;
                                            $inventory->price = $product_stock->price;
                                            $inventory->qty = $product_stock->qty; 
                                            $inventory->auth_id = $product_stock->auth_id;
                                            //dd($inventory);
                                            $inventory->save();  
                                        }
                                    }
                            }
                        //dd($product); 
            DB::commit();
            return response()->json([
                'status_code' => 200,
                'message' => $this->get_message('add'),
            ], 200);


        } catch (\Illuminate\Database\QueryException $e) {
           dd($e);
            if ($e->errorInfo[1] == 1062) {
                return response()->json([
                    'status_code' => 409,
                    'message' => 'Duplicate entry: The product already exists.',
                ], 409);
            }

            return response()->json([
                'status_code' => 500,
                'message' => $e->getMessage(),
                // 'message' => $this->get_message('server_error'),
            ], 500);

        } catch (\Throwable $th) {
            dd($th);
            DB::rollBack(); 
            return response()->json([
                'status_code' => 500,
                'message' => $th->getMessage(),
                // 'message' => $this->get_message('server_error'),
            ], 500);
        }
    }


    public function edit_product($uuid)
    {
        try {
            // Fetch the product by its UUID
            $edit_product_by_id = Product::with('categories')->where('uuid', $uuid)->first();
           // dd($uuid);
            // Check if product exists
            if (!$edit_product_by_id) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

             // Fetch product stocks
            $product_stocks = ProductStock::where('product_id', $edit_product_by_id->uuid)->get();



            // Generate HTML for attribute selection
            $html_attribute = '';
            // foreach (\App\Models\Attribute::all() as $key => $attribute) {
            //     $selected = '';
            //     if ($edit_product_by_id->attributes != null && in_array($attribute->id, json_decode($edit_product_by_id->attributes, true))) {
            //         $selected = 'selected';
            //     }
            //     $html_attribute .= '<option value="' . $attribute->id . '" ' . $selected . '>' . $attribute->attribute_name . '</option>';
            // }


            // Generate HTML for choice options
            $html = '';
            // if ($edit_product_by_id->choice_options) {
            //     foreach (json_decode($edit_product_by_id->choice_options) as $choice_option) {
            //         $attribute = Attribute::where('id', $choice_option->attribute_id)->first();
            //         if ($attribute) {
            //             $attribute_name = $attribute->attribute_name;
            //             $options = AttributeValue::where('attribute_id', $choice_option->attribute_id)
            //                 ->select('id', 'value')
            //                 ->get();

            //             // Construct HTML for each attribute
            //             $html .= '<div class="form-group row mb-3" data-attr="' . $attribute_name . '">';
            //             $html .= '<div class="col-lg-3">';
            //             $html .= '<input type="hidden" name="choice_no[]" value="' . $choice_option->attribute_id . '">';
            //             $html .= '<input type="text" class="form-control" name="choice[]" value="' . $attribute_name . '" placeholder="Choice Title" disabled>';
            //             $html .= '</div>';
            //             $html .= '<div class="col-lg-8">';
            //             $html .= '<select class="form-control selectpicker attribute_choice" data-live-search="true" name="choice_options_' . $choice_option->attribute_id . '[]" multiple>';
            //             foreach ($options as $option) {
            //                 $selected = in_array($option->value, $choice_option->values) ? 'selected' : '';
            //                 $html .= '<option value="' . $option->value . '" ' . $selected . '>' . $option->value . '</option>';
            //             }
            //             $html .= '</select>';
            //             $html .= '</div>';
            //             $html .= '</div>';
            //         }
            //     }
            // }

            $product_discount = ProductDiscounts::where('product_id', $edit_product_by_id->uuid)->first();
            


            // Get active languages
            $get_active_language = Language::where('status', '1')->get();

            $now = Carbon::now();
            $auth_id = Auth::user()->uuid;

            // Check if there are active languages and handle translations
            if (count($get_active_language) > 0) {
                foreach ($get_active_language as $key => $language) {
                    // Check if a translation for the product already exists in the language
                    $check_product_translation = ProductTranslation::where('product_id', $edit_product_by_id->id)
                        ->where('language_id', $language->id)
                        ->where('status', '1')->first();

                    // If no translation exists, create a new one
                    if (!$check_product_translation) {
                        $save_product_translation = ProductTranslation::insert([
                            [
                                'uuid' => Str::uuid(),
                                'product_id' => $edit_product_by_id->id,
                                'name' => $edit_product_by_id->name,
                                'short_description' => $edit_product_by_id->short_description,
                                'description' => $edit_product_by_id->description,
                                'language_id' => $language->id,
                                'lang' => $language->app_language_code,
                                'auth_id' => $auth_id,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]
                        ]);
                    }
                }
            }

            // Fetch product translations for all active languages
            $product_translations = ProductTranslation::where('product_id', $edit_product_by_id->id)
                ->where('product_translations.status', '1')
                ->join('languages', 'product_translations.language_id', '=', 'languages.id')
                ->select('languages.code as language_code', 'languages.name as language_name', 'languages.flag as flag', 'languages.rtl as dir', 'product_translations.*')
                ->get();

            // Add translations to the product
            if ($edit_product_by_id) {
               
                $edit_product_by_id->translations = $product_translations;
                $edit_product_by_id->html_attribute = $html_attribute;
                $edit_product_by_id->html = $html;
                $edit_product_by_id->stocks = $product_stocks;
                $edit_product_by_id->product_discount = $product_discount;
                $edit_product_by_id->category_id =$edit_product_by_id->categories()->pluck('category_uuid')->toArray();
                $edit_product_by_id->collection_id =$edit_product_by_id->collections()->pluck('collection_uuid')->toArray();
                $edit_product_by_id->sale_channel_id =$edit_product_by_id->salesChannels()->pluck('channel_uuid')->toArray();
                $edit_product_by_id->market_id =$edit_product_by_id->markets()->pluck('market_uuid')->toArray();
               // $edit_product_by_id->tags =$edit_product_by_id->tags()->pluck('id')->toArray();
               
                // dd($edit_product_by_id);
                return response()->json([
                    'status_code' => Response::HTTP_OK,
                    'data' => $edit_product_by_id,
                ], Response::HTTP_OK);
            } else {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

        } catch (\Exception $e) {
            // Handle general exceptions
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
    }
     

    public function update_product(Request $request)
    {
        $uuid = request()->header('uuid');
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                //'regex:/^[a-zA-Z0-9_ ]+$/',
                'regex:/^[\w\s\-]+$/',
                'min:1', 
                'max:255' 
            ],
            'slug' => [
                'required',
                'min:1',  
                'max:255', 
                Rule::unique('products')->where(function ($query) use ($request) {
                    return $query->where('uuid','!=', request()->header('uuid'));
                })
            ] 
        ],[
            'name.required' => 'The name field is required.',
            'slug.unique' => 'The page slug has already been taken..',
        ]);
        if($validator->fails()) {
            $message = $validator->messages();
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($message) 
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }   


        

        try { 
            // Find the product
            $product = Product::where('uuid', $uuid)->first(); 
            if (!$product) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Product not found',
                ], Response::HTTP_NOT_FOUND);
            }

            // Update product fields
           
            $product->name = $request->name; 
            $product->description = $request->description;
            if ($request->has('images') && !empty($request->images)) { 
                $images = $request->images; 
                $imageArray = explode(',', $images);  
                $thumbnailImg = trim($imageArray[0]); 
                //$restOfImages = array_slice($imageArray, 1);  // if first image will not incluedes in gallery images
                $restOfImages = $imageArray; 
                $product->thumbnail_img = $thumbnailImg;
                $product->images = implode(',', $restOfImages);
            }else{
                $product->thumbnail_img = '';
                $product->images = '';
            }
            // if ($request->has('category_id') && !empty($request->category_id)) {
            //     $syncData = [];
            //     foreach ($request->category_id as $categoryId) {
            //         $syncData[$categoryId] = ['product_uuid' => $product->uuid];
            //     }
            //     $product->categories()->sync($syncData);
            // } else {
            //     $product->categories()->sync([]); 
            // }

           // dd($request->category_id);
            // if($request->has('category_id') && !empty($request->category_id)){
            //     $product->category_id = json_encode($request->category_id);
            // } 
            $product->unit_price = $request->unit_price ?? 0.00; 
            $product->compare_price = $request->compare_price ?? 0.00;
            $product->cost_per_item = $request->cost_per_item ?? 0.00;
            $product->sku =$request->simple_sku ?? '';
            $product->barcode =$request->simple_barcode ?? '';
            $product->vat_id = $request->vat_id ?? 0;
            $product->weight =$request->sipmle_shipping_weight ?? 0.00;
            $product->unit =$request->sipmle_shipping_weight_unit ?? '';
            $product->country_id =$request->simple_country_id ?? '';
            $product->hs_code =$request->simple_hscode ?? '';
            $product->published_date_time =$request->published_date_time;

            $product->choice_options= $request->varient_data_view ?? json_encode([]); 
                if($request->variation_data!=''){
                    $product->varient_data = json_encode($request->variation_data, JSON_UNESCAPED_UNICODE);
                }else{
                    $product->varient_data = json_encode([]);
                } 
            $product->meta_title = $request->meta_title;
            $product->meta_description = $request->meta_description; 
            if ($request->slug) {
                $product->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->slug));
            } else {
                $product->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->name)) . '-' . Str::random(5);
            }
            $product->status =$request->status ?? 0;
           // dd($request->status);
            $product->salesChannels()->sync($request->sale_channel_id, ['product_uuid' => $product->uuid]);
            $product->markets()->sync($request->market_id, ['product_uuid' => $product->uuid]);
            $product->type =$request->type ?? '';
            $product->vendor =$request->vendor ?? '';
            // $product->collections()->sync($request->collection_id, ['product_uuid' => $product->uuid]);
            if ($request->has('tags') && !empty($request->tags)) {
                if (is_array($request->tags)) {
                    $product->tags = implode(',', $request->tags);
                } 
                else {
                    $product->tags = $request->tags;
                }
            }
            $product->template_product =$request->template_product ?? '';
           
              
            $product->tax_enabled = $request->tax_enabled ?? 0;
            $product->inventory_track_enabled = $request->track_quantity?? 0;
            $product->selling_stock_enabled = $request->continue_selling_out_of_stock?? 0;
            $product->sku_barcode_enabled = $request->sku_barcode_enabled?? 0;
            $product->physical_product_enabled = $request->physical_product_enabled?? 0; 
            //dd($product->sku_barcode_enabled); 
            
            //dd();
            DB::beginTransaction();
            $product->save();

            // collection
            if ($request->has('collection_id')) {
                $collectionSyncData = [];
                $collections = Collection::whereIn('uuid', $request->collection_id)->pluck('id','uuid')->toArray();
                foreach ($collections as $uuid => $categoryid) {
                    $collectionSyncData[$categoryid] = [
                        'product_uuid' => $product->uuid,
                        'collection_uuid' => $uuid,
                    ];
                }            
                $product->collections()->sync($collectionSyncData);
            }

            //discount

            if($request->has('discount_id') && !empty($request->discount_id)){
              ProductDiscounts::where('di_id', $request->discount_id)->delete();
              
              $discount = Discount::where('uuid', $request->discount_id)->first();
                if ($discount) {
                    ProductDiscounts::updateOrCreate(
                        ['product_id' => $product->uuid], 
                        [
                            'di_id'  => $request->discount_id,
                            'auth_id'   => Auth::user()->uuid,
                            'value'  => $discount->value,
                            'method' => $discount->method,
                            'type'   => $discount->type,
                        ]
                    );
                }
            }
            
            //category
            if ($request->has('category_id')) {
                $categorySyncData = [];
                $categories = Category::whereIn('uuid', $request->category_id)->pluck('id','uuid')->toArray();
                foreach ($categories as $uuid => $categoryid) {
                    $categorySyncData[$categoryid] = [
                        'product_uuid' => $product->uuid,
                        'category_uuid' => $uuid,
                    ];
                }
                $product->categories()->sync($categorySyncData);
            }

            $updatedTranslations = false;

            // Update translations
            foreach ($request->all() as $key => $value) {
                if (strpos($key, 'name_') === 0) {
                    $languageCode = substr($key, 5);
                    $languageId = DB::table('languages')->where('code', $languageCode)->value('id');

                    if ($languageId) {
                        ProductTranslation::where('language_id', $languageId)
                            ->where('product_id', $product->id)
                            ->update(['name' => $value]);

                        $updatedTranslations = true;
                    }
                }
            }


            foreach ($request->all() as $key => $value) {
                if (strpos($key, 'short_description_') === 0) {
                    $languageCode = substr($key, 18);
                    $languageId = DB::table('languages')->where('code', $languageCode)->value('id');

                    if ($languageId) {
                        ProductTranslation::where('language_id', $languageId)
                            ->where('product_id', $product->id)
                            ->update(['short_description' => $value]);

                        $updatedTranslations = true;
                    }
                }
            }


            foreach ($request->all() as $key => $value) {
                if (strpos($key, 'description_') === 0) {
                    $languageCode = substr($key, 12);
                    $languageId = DB::table('languages')->where('code', $languageCode)->value('id');

                    if ($languageId) {
                        ProductTranslation::where('language_id', $languageId)
                            ->where('product_id', $product->id)
                            ->update(['description' => $value]);

                        $updatedTranslations = true;
                    }
                }
            }

            // If translations were updated, update the default language's product data
            if ($updatedTranslations) {
                $get_active_language = Language::where('status', '1')->where('is_default', '1')->first();
                $get_product_trans_by_def_lang = ProductTranslation::where('product_id', $product->id)
                    ->where('language_id', $get_active_language->id)
                    ->first();

                // Update product name in default language
                DB::table('products')
                    ->where('id', $product->id)
                    ->update([
                        'name' => $get_product_trans_by_def_lang->name,
                        'short_description' => $get_product_trans_by_def_lang->short_description,
                        'description' => $get_product_trans_by_def_lang->description,
                    ]);
            } 




            // Product Stock Logic 
           if ($request->variation_data!="") {
                //$variation_data=json_decode($request->variation_data);
                $variation_dataRaw=json_decode($request->variation_data,JSON_UNESCAPED_UNICODE);
                //dd($variation_data);
                // dd([
                //     'data' => $variation_dataRaw,
                //     'count' => count($variation_dataRaw)
                // ]);
                 if (count($variation_dataRaw) > 0) {
                    foreach ($variation_dataRaw as $key => $variation_data) {
                        if($key!='all'){
                            if(count($variation_data)>0){
                            
                                foreach ($variation_data as $key => $variation) {
                                // dd($product->uuid);
                                    $variant_product_id=$variation['product_id'];
                                    $variant=$variation['variantName'];
                                    $variant_price=$variation['variantPrice']; 
                                    $variant_sku=$variation['SKU'];
                                    $variant_qty=$variation['variantQuantity'];
                                    $variant_image=$variation['selectedImageFile'];
                                    $variant_location_id=$variation['location_id'];
                                    $adjust_value=0;
                                    $check_stock_exist = ProductStock::where('location_id',$variant_location_id)->where('product_id',$product->uuid)->where('variant', $variant)->first();
                                // dd($check_stock_exist);            
                                                    if($check_stock_exist ==null){
                                                        $product_stock = new ProductStock();
                                                        $product_stock->uuid = Str::uuid();
                                                        $product_stock->product_id = $variant_product_id; 
                                                        $product_stock->variant = $variant;
                                                        $product_stock->price = $variant_price;
                                                        $product_stock->sku = $variant_sku;
                                                        $product_stock->variant_sku = $variant_sku;
                                                        $product_stock->cost_per_item = $variation['variantCostPrice'];
                                                        $product_stock->barcode = $variation['variantBarCode'];
                                                        $product_stock->hs_code = $variation['variantHSCode'];
                                                        $product_stock->qty = $variant_qty;
                                                        $product_stock->image = $variant_image;
                                                        $product_stock->location_id = $variant_location_id;
                                                        $product_stock->auth_id = Auth::user()->uuid;
                                                        $product_stock->save();
                                                    }else{
                                                        // If the record exists, update it 
                                                        if($check_stock_exist->qty<$variant_qty){
                                                            $adjust_value=$variant_qty-$check_stock_exist->qty;
                                                        }else if($check_stock_exist->qty>$variant_qty){
                                                            $adjust_value=$variant_qty-$check_stock_exist->qty;
                                                        }
                                                        else{
                                                            $adjust_value=$check_stock_exist->qty;
                                                        } 
                                                        //dd($check_stock_exist->qty,$variant_qty,$adjust_value);
                                                        $check_stock_exist->price = $variant_price;
                                                        $check_stock_exist->sku = $variant_sku;
                                                        $check_stock_exist->variant_sku = $variant_sku;
                                                        $check_stock_exist->cost_per_item = $variation['variantCostPrice'];
                                                        $check_stock_exist->barcode = $variation['variantBarCode'];
                                                        $check_stock_exist->hs_code = $variation['variantHSCode'];
                                                        $check_stock_exist->qty =$variant_qty;
                                                        $check_stock_exist->image = $variant_image;
                                                        $check_stock_exist->location_id = $variant_location_id;
                                                        $check_stock_exist->auth_id = Auth::user()->uuid; 
                                                        $check_stock_exist->save();
                                                    } 

                                                    if($check_stock_exist ==null){
                                                        $product_stock_id = $product_stock->uuid;
                                                    }else{
                                                        $product_stock_id=$check_stock_exist->uuid;
                                                    }
                                                    $check_inventory_exist = Inventory::where('location_id',$variant_location_id)->where('product_id',$variant_product_id)->where('stock_id', $product_stock_id)->where('sku',$variant_sku)->where('status', 'opening')->first();
                                                    //dd($check_inventory_exist);
                                                    if($variant_sku=='Green-Small'){
                                                        //dd($check_inventory_exist);
                                                    }
                                            //  dd($check_stock_exist->qty,$adjust_value);
                                                    if($product->inventory_track_enabled==1){
                                                        if($check_inventory_exist !=null){ 
                                                            if($check_stock_exist->qty!=$adjust_value){
                                                                $check_inventory_exist = new Inventory();
                                                                $check_inventory_exist->uuid = Str::uuid();
                                                                $check_inventory_exist->product_id = $variant_product_id;
                                                                $check_inventory_exist->stock_id = $product_stock_id; // ProductStock ID
                                                                $check_inventory_exist->location_id = $variant_location_id;
                                                                $check_inventory_exist->status = 'adjust';
                                                                $check_inventory_exist->reason = 'item updated';  
                                                                $check_inventory_exist->price = $variant_price;
                                                                $check_inventory_exist->qty = $adjust_value; 
                                                                $check_inventory_exist->auth_id = Auth::user()->uuid;  
                                                                $check_inventory_exist->save(); 
                                                            }else{  
                                                            // $check_inventory_exist->qty = $variant_qty;
                                                                $check_inventory_exist->price = $variant_price; 
                                                                $check_inventory_exist->auth_id = Auth::user()->uuid;  
                                                                $check_inventory_exist->save(); 
                                                            } 
                                                        }else{
                                                                $inventory = new Inventory();
                                                                $inventory->uuid = Str::uuid();
                                                                $inventory->product_id = $variant_product_id;
                                                                $inventory->stock_id = $product_stock_id; // ProductStock ID
                                                                $inventory->location_id = $variant_location_id;
                                                                $inventory->status = 'opening';
                                                                $inventory->reason = 'opening'; 
                                                                $inventory->sku = $variant_sku ?? $product->sku;
                                                                $inventory->price = $variant_price;
                                                                $inventory->qty = $variant_qty;
                                                                $inventory->auth_id = Auth::user()->uuid; 
                                                                $inventory->save(); 
                                                        }    
                                                    }
                                            }
                            }  
                    }
                     }
                 }
            } 
            // else {
            //         /// Simple Product

            //         $warehouse_location_ids= explode(',', $request->warehouse_location_id); 
            //             dd($warehouse_location_ids);             
            //             foreach ($warehouse_location_ids as $key => $location_id) {  
            //                     $check_stock_exist = ProductStock::where('location_id',$location_id)->where('product_id',$product->uuid)->where('variant', '')->first();
            //                         if($check_stock_exist ==null){
            //                             $product_stock = ProductStock::create($data);
            //                         }else{
            //                             // If the record exists, update it 
            //                             $qty=$request->location_stock[$key];
            //                             if($check_stock_exist->qty<$qty){
            //                                 $adjust_value=$qty-$check_stock_exist->qty;
            //                             }else if($check_stock_exist->qty>$qty){
            //                                 $adjust_value=$qty-$check_stock_exist->qty;
            //                             }
            //                             else{
            //                                 $adjust_value=$check_stock_exist->qty;
            //                             }
            //                             $check_stock_exist->price = $request->unit_price;
            //                             $check_stock_exist->sku = $request->sku;
            //                             $check_stock_exist->qty = $adjust_value;
            //                             $check_stock_exist->image = $request->thumbnail_img;
            //                             $check_stock_exist->location_id = $location_id;
            //                             $check_stock_exist->auth_id = Auth::user()->uuid; 
            //                             $check_stock_exist->save();
            //                         } 
            //                         if($check_stock_exist ==null){
            //                             $product_stock_id = $product_stock->uuid;
            //                         }else{
            //                             $product_stock_id=$check_stock_exist->uuid;
            //                         }
                
                                
            //                             $check_inventory_exist = Inventory::where('location_id',$location_id)->where('product_id',$product->uuid)->where('stock_id', $product_stock_id)->where('status', 'opening')->first();
            //                             // dd($check_inventory_exist);
            //                             if($check_inventory_exist !=null){ 
                                            
            //                                 if($check_stock_exist->qty!=$adjust_value){
            //                                     $check_inventory_exist = new Inventory();
            //                                     $check_inventory_exist->uuid = Str::uuid();
            //                                     $check_inventory_exist->product_id = $product->uuid;
            //                                     $check_inventory_exist->stock_id = $product_stock_id; // ProductStock ID
            //                                     $check_inventory_exist->location_id = $location_id;
            //                                     $check_inventory_exist->status = 'adjust';
            //                                     $check_inventory_exist->reason = 'item updated';  
            //                                     $check_inventory_exist->price = $request->unit_price;
            //                                     $check_inventory_exist->qty = $adjust_value; 
            //                                     $check_inventory_exist->auth_id = Auth::user()->uuid;  
            //                                     $check_inventory_exist->save(); 
            //                                 }else{  
            //                                     $check_inventory_exist->price = $request->unit_price;
            //                                     //$check_inventory_exist->qty = $adjust_value; 
            //                                     $check_inventory_exist->auth_id = Auth::user()->uuid;  
            //                                     $check_inventory_exist->save(); 
            //                                 } 
                                            
            //                             }else{
            //                                 // Create Inventory record
            //                                 $inventory = new Inventory();
            //                                 $inventory->uuid = Str::uuid();
            //                                 $inventory->product_id = $request->uuid;
            //                                 $inventory->stock_id = $product_stock_id; // ProductStock ID
            //                                 $inventory->location_id = $location_id;
            //                                 $inventory->status = 'opening';
            //                                 $inventory->reason = 'opening'; 
            //                                 $inventory->sku = $request->sku;
            //                                 $inventory->price = $request->unit_price;
            //                                 $inventory->qty = $request->current_stock; 
            //                                 $inventory->auth_id = Auth::user()->uuid; 
            //                                 $inventory->save(); 
            //                             }
                                
            //                     $update_product_current_stock = Inventory::where('stock_id', $product_stock_id)->first(); 
            //                     $product->current_stock = $update_product_current_stock->getNetAvailableQty(); 
            //                     $product->save();


                                        
            //                         }
                            
                             
                    
            //     }









            // if (count($combinations) > 0) {
                 
            //     foreach ($combinations as $key => $combination) {
            //         $str = ProductUtility::get_combination_string($combination, $collection);
            //         $check_stock_exist = ProductStock::where('location_id',$product->warehouse_location_id)->where('product_id',$product->uuid)->where('variant', $str)->first();
            //         //dd($check_stock_exist);
            //         $adjust_value=0;
            //         if($check_stock_exist ==null){
            //             $product_stock = new ProductStock();
            //             $product_stock->uuid = Str::uuid();
            //             $product_stock->product_id = $product->uuid;
            //             //$product_stock->product_name = $product->name;
            //             $product_stock->variant = $str;
            //             $product_stock->price = $request->input('price_' . str_replace('.', '_', $str), 0);
            //             $product_stock->sku = $request->input('sku_' . str_replace('.', '_', $str));
            //             $product_stock->qty = $request->input('qty_' . str_replace('.', '_', $str), 0);
            //             $product_stock->image = $request->input('img_' . str_replace('.', '_', $str));
            //             $product_stock->location_id = $request->warehouse_location_id;
            //             $product_stock->auth_id = Auth::user()->uuid;
            //             $product_stock->save();
            //         }else{
            //             // If the record exists, update it
            //             //$check_stock_exist->product_name = $product->name;
            //             if($check_stock_exist->qty<$request->input('qty_' . str_replace('.', '_', $str), 0)){
            //                 $adjust_value=$request->input('qty_' . str_replace('.', '_', $str), 0)-$check_stock_exist->qty;
            //             }else if($check_stock_exist->qty>$request->input('qty_' . str_replace('.', '_', $str), 0)){
            //                 $adjust_value=$request->input('qty_' . str_replace('.', '_', $str), 0)-$check_stock_exist->qty;
            //             }
            //             else{
            //                 $adjust_value=$check_stock_exist->qty;
            //             }
            //              //dd($adjust_value,$check_stock_exist->qty,$request->input('qty_' . str_replace('.', '_', $str), 0));
            //             $check_stock_exist->price = $request->input('price_' . str_replace('.', '_', $str), 0);
            //             $check_stock_exist->sku = $request->input('sku_' . str_replace('.', '_', $str));
            //             $check_stock_exist->qty =$request->input('qty_' . str_replace('.', '_', $str), 0);
            //             $check_stock_exist->image = $request->input('img_' . str_replace('.', '_', $str));
            //             $check_stock_exist->location_id = $request->warehouse_location_id;
            //             $check_stock_exist->auth_id = Auth::user()->uuid;
            //              //dd($check_stock_exist);
            //             $check_stock_exist->save();
            //         } 
            //         if($check_stock_exist ==null){
            //             $product_stock_id = $product_stock->uuid;
            //         }else{
            //             $product_stock_id=$check_stock_exist->uuid;
            //         }
            //         //dd($check_stock_exist->qty!=$adjust_value);
            //         $check_inventory_exist = Inventory::where('location_id',$product->warehouse_location_id)->where('product_id',$product->uuid)->where('stock_id', $product_stock_id)->where('sku',$str)->where('status', 'opening')->first();
            //         //dd($check_inventory_exist);
            //         if($check_inventory_exist !=null){ 
            //             if($check_stock_exist->qty!=$adjust_value){
            //                 $check_inventory_exist = new Inventory();
            //                 $check_inventory_exist->uuid = Str::uuid();
            //                 $check_inventory_exist->product_id = $product->uuid;
            //                 $check_inventory_exist->stock_id = $product_stock_id; // ProductStock ID
            //                 $check_inventory_exist->location_id = $request->warehouse_location_id;
            //                 $check_inventory_exist->status = 'adjust';
            //                 $check_inventory_exist->reason = 'item updated';  
            //                 $check_inventory_exist->price = $request->input('price_' . str_replace('.', '_', $str), 0);
            //                 $check_inventory_exist->qty = $adjust_value; 
            //                 $check_inventory_exist->auth_id = Auth::user()->uuid;  
            //                 $check_inventory_exist->save(); 
            //             }else{  
            //                 $check_inventory_exist->price = $request->input('price_' . str_replace('.', '_', $str), 0);
            //                 //$check_inventory_exist->qty = $adjust_value; 
            //                 $check_inventory_exist->auth_id = Auth::user()->uuid;  
            //                 $check_inventory_exist->save(); 
            //             } 
            //         }else{
            //                 $inventory = new Inventory();
            //                 $inventory->uuid = Str::uuid();
            //                 $inventory->product_id = $request->uuid;
            //                 $inventory->stock_id = $product_stock_id; // ProductStock ID
            //                 $inventory->location_id = $request->warehouse_location_id;
            //                 $inventory->status = 'opening';
            //                 $inventory->reason = 'opening'; 
            //                 $inventory->sku = $request->input('sku_' . str_replace('.', '_', $str));
            //                 $inventory->price = $request->input('price_' . str_replace('.', '_', $str), 0);
            //                 $inventory->qty = $request->input('qty_' . str_replace('.', '_', $str), 0);
            //                 $inventory->auth_id = Auth::user()->uuid; 
            //                 $inventory->save(); 
            //         }    
            //     }

            // } else {
            //         /// Simple Product
            //     $qty = $collection->get('current_stock', 0);
            //     $price = $collection->get('unit_price', 0);
            //     $adjust_value=0;
            //     $data = $collection->merge(compact('variant', 'qty', 'price'))->toArray();
            //     $data['uuid'] = Str::uuid();
            //     $data['product_id'] = $product->uuid;
            //     $data['auth_id'] = Auth::user()->uuid;
            //     $data['image']=$request->thumbnail_img; 
            //     $data['location_id'] = $request->warehouse_location_id;
            //     $check_stock_exist = ProductStock::where('location_id',$product->warehouse_location_id)->where('product_id',$product->uuid)->where('variant', '')->first();
            //     if($check_stock_exist ==null){
            //         $product_stock = ProductStock::create($data);
            //     }else{
            //         // If the record exists, update it 

            //         if($check_stock_exist->qty<$request->current_stock){
            //             $adjust_value=$request->current_stock-$check_stock_exist->qty;
            //         }else if($check_stock_exist->qty>$request->current_stock){
            //             $adjust_value=$request->current_stock-$check_stock_exist->qty;
            //         }
            //         else{
            //             $adjust_value=$check_stock_exist->qty;
            //         }
            //         $check_stock_exist->price = $request->unit_price;
            //         $check_stock_exist->sku = $request->sku;
            //         $check_stock_exist->qty = $request->current_stock;
            //         $check_stock_exist->image = $request->thumbnail_img;
            //         $check_stock_exist->location_id = $request->warehouse_location_id;
            //         $check_stock_exist->auth_id = Auth::user()->uuid; 
            //         $check_stock_exist->save();
            //     } 
            //     if($check_stock_exist ==null){
            //         $product_stock_id = $product_stock->uuid;
            //     }else{
            //         $product_stock_id=$check_stock_exist->uuid;
            //     }

                
            //     $check_inventory_exist = Inventory::where('location_id',$product->warehouse_location_id)->where('product_id',$product->uuid)->where('stock_id', $product_stock_id)->where('status', 'opening')->first();
            //    // dd($check_inventory_exist);
            //     if($check_inventory_exist !=null){ 
                    
            //         if($check_stock_exist->qty!=$adjust_value){
            //             $check_inventory_exist = new Inventory();
            //             $check_inventory_exist->uuid = Str::uuid();
            //             $check_inventory_exist->product_id = $product->uuid;
            //             $check_inventory_exist->stock_id = $product_stock_id; // ProductStock ID
            //             $check_inventory_exist->location_id = $request->warehouse_location_id;
            //             $check_inventory_exist->status = 'adjust';
            //             $check_inventory_exist->reason = 'item updated';  
            //             $check_inventory_exist->price = $request->unit_price;
            //             $check_inventory_exist->qty = $adjust_value; 
            //             $check_inventory_exist->auth_id = Auth::user()->uuid;  
            //             $check_inventory_exist->save(); 
            //         }else{  
            //             $check_inventory_exist->price = $request->unit_price;
            //             //$check_inventory_exist->qty = $adjust_value; 
            //             $check_inventory_exist->auth_id = Auth::user()->uuid;  
            //             $check_inventory_exist->save(); 
            //         } 
                     
            //     }else{
            //         // Create Inventory record
            //         $inventory = new Inventory();
            //         $inventory->uuid = Str::uuid();
            //         $inventory->product_id = $request->uuid;
            //         $inventory->stock_id = $product_stock_id; // ProductStock ID
            //         $inventory->location_id = $request->warehouse_location_id;
            //         $inventory->status = 'opening';
            //         $inventory->reason = 'opening'; 
            //         $inventory->sku = $request->sku;
            //         $inventory->price = $request->unit_price;
            //         $inventory->qty = $request->current_stock; 
            //         $inventory->auth_id = Auth::user()->uuid; 
            //         $inventory->save(); 
            //     }
                
            //     // $update_product_current_stock = Inventory::where('stock_id', $product_stock_id)->first(); 
            //     // $product->current_stock = $update_product_current_stock->getNetAvailableQty(); 
            //     // $product->save();
            // }

            DB::commit();
            return response()->json([
                'status_code' => 200,
                'message' => 'Product has been updated',
            ], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $th->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function delete_product($uuid)
    {
        try {
            // Find the product by UUID
            $del_product = Product::where('uuid', $uuid)->first();
    
            if (!$del_product) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }
            DB::beginTransaction(); 
            try {
                ProductTranslation::where('product_id', $del_product->id)->delete();
                // ProductStock::where('product_id', $product->id)->delete();
                // Inventory::where('product_id', $product->id)->delete();
                ProductStock::where('product_id', $del_product->uuid)->delete();

                Inventory::where('product_id', $del_product->uuid)->delete();

                // Detach related categories (sync with empty array)
                $del_product->categories()->sync([]);

                // Detach related sales channels
                $del_product->salesChannels()->sync([]);

                // Detach related markets
                $del_product->markets()->sync([]);

                // Detach related collections
                $del_product->collections()->sync([]);
                
                // if (!empty($del_product->images)) {
                //     $imagePaths = explode(',', $del_product->images); 
                //     foreach ($imagePaths as $path) {
                //         // Delete image file from storage
                //             if (Storage::disk('local')->exists($path)) {
                //                 Storage::disk('local')->delete($path);
                //             } 
                //         // Delete entry from filemanagers table
                //         FileManager::where('file_name', $path)->delete();
                //     }
                // }

                $deleted = $del_product->delete();
                if ($deleted) {
                    DB::commit();
                    return response()->json([
                        'status_code' => Response::HTTP_OK,
                        'message' => $this->get_message('delete'),
                    ], Response::HTTP_OK);
                } else {
                    DB::rollBack();
                    return response()->json([
                        'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                        'message' => $this->get_message('delete_failed'),
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => $this->get_message('delete_failed') . ': ' . $e->getMessage(),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
    
        } catch (\Exception $e) {
            // Handle general exceptions
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
    }


    public function bulkDelete(Request $request)
    {
        $request->validate([
            'uuids' => 'required|array',
            'uuids.*' => 'exists:products,uuid',
        ]);

        try {
            DB::beginTransaction();

            // Delete ProductTranslations, ProductStocks, and Inventories for each product
            foreach ($request->uuids as $uuid) {
                $product = Product::where('uuid', $uuid)->first(); 
                if ($product) {
                    ProductTranslation::where('product_id', $product->id)->delete();
                    ProductStock::where('product_id', $product->uuid)->delete();
                    Inventory::where('product_id', $product->uuid)->delete();

                    $product->categories()->sync([]);
                    $product->salesChannels()->sync([]);
                    $product->markets()->sync([]);
                    $product->collections()->sync([]);
                    //     if (!empty($product->images)) {
                    //     $imagePaths = explode(',', $product->images);  
                    //     foreach ($imagePaths as $path) {
                    //         // Delete image file from storage 
                    //         if (Storage::disk('local')->exists($path)) {
                    //             Storage::disk('local')->delete($path);
                    //         } 
                    //         // Delete entry from filemanagers table
                    //         FileManager::where('file_name', $path)->delete();
                    //     }
                    // }
                    $product->delete();
                }
            }

            DB::commit();

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Products have been deleted successfully',
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('delete_failed') . ': ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }
    

    
    public function updateCategoryStatus(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:0,1', // Ensure status is either 0 or 1
        ]);

        try {
            // Find the category by UUID and active status
            $product = Product::where('uuid', $id)->first();

            if ($product) {
                // Update the status
                $product->status = $request->status;
                $product->save();

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


    public function updateCategoryFeatured(Request $request, string $id)
    {
        $request->validate([
            'featured' => 'required|in:0,1',
        ]);

        try {
            // Find the category by UUID and active featured
            $product = Product::where('uuid', $id)->first();

            if ($product) {
                // Update the featured
                $product->featured = $request->featured;
                $product->save();

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


    public function get_product_attribute() {

        try {
            // Get the menu UUID from the request header
            $menuUuid = request()->header('menu-uuid');
    
            // Check permissions for the current user
            $permissions = $this->permissionService->checkPermissions($menuUuid);
    
            // Initialize the query to get all product attributes, ordered by ID in descending order
            $get_all_attributes = ProductStock::orderBy('id', 'desc');
    
            // Check if the user has permission to view the product attributes
            if ($permissions['view']) {
                // If the user doesn't have global view permission, filter by products belonging to their auth_id
                if (!$permissions['viewglobal']) {
                    $authUserProducts = Product::where('auth_id', Auth::user()->uuid)->pluck('id')->toArray();
                    $get_all_attributes = $get_all_attributes->whereIn('product_id', $authUserProducts);
                }
            } else {
                // If the user doesn't have 'view' permission, check if they have 'viewglobal'
                if (Auth::user()->hasPermission('viewglobal')) {
                    $get_all_attributes = $get_all_attributes;
                } else {
                    // If the user doesn't have any view permissions, return a forbidden response
                    return response()->json([
                        'message' => 'You do not have permission to view this menu'
                    ], Response::HTTP_FORBIDDEN);
                }
            }
    
            // Get the product attributes from the database
            $get_all_attributes = $get_all_attributes->get();
    
            // Prepare the response with product attribute data and permissions
            return response()->json([
                'status_code' => 200,
                'permissions' => $permissions,
                'data' => $get_all_attributes
            ], 200);
    
        } catch (\Exception $e) {
            // Handle general exceptions and return an internal server error response
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        
    }


    public function delete_product_attribute($uuid)
    {
        try {
            // Find the product attribute by UUID
            $productAttribute = ProductStock::where('uuid', $uuid)->first();

            if (!$productAttribute) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            } else {
                // Delete the product attribute
                $deleteProductAttribute = ProductStock::destroy($productAttribute->id);

                if ($deleteProductAttribute) {
                    return response()->json([
                        'status_code' => Response::HTTP_OK,
                        'message' => $this->get_message('delete'),
                    ], Response::HTTP_OK);
                }
            }
        } catch (\Exception $e) {
            // Handle general exceptions
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
    }


    public function add_more_choice_option(Request $request)
    {
        $all_attribute_values = AttributeValue::with('attribute')->where('attribute_id', $request->attribute_id)->get();

        $html = '';

        foreach ($all_attribute_values as $row) {
            $html .= '<option value="' . $row->value . '">' . $row->value . '</option>';
        }

        // return response()->json([
        //     'status_code' => Response::HTTP_OK,
        //     'message' => "success",
        //     'data' => $html
        // ], Response::HTTP_OK);

        echo json_encode($html);
    }


    // public function sku_combination(Request $request)
    // {
    //     $options = [];

    //     // Collecting options based on the request
    //     if ($request->has('choice_no')) {
    //         foreach ($request->choice_no as $key => $no) {
    //             $name = 'choice_options_' . $no;
    //             if (isset($request[$name])) {
    //                 $data = [];
    //                 foreach ($request[$name] as $item) {
    //                     array_push($data, $item);
    //                 }
    //                 array_push($options, $data);
    //             }
    //         }
    //     }

    //     // Generate combinations
    //     $combinations = (new CombinationService())->generate_combination($options);

    //     // Prepare data for the view
    //     $product_name = $request->input('product_name', 'Product'); 
    //     $unit_price = $request->input('unit_price', 1); 
    //     $quantity = $request->input('current_stock', 1); 
    //     $sku = $request->input('sku', '-');

    //     $html = '';

    //     if (count($combinations) > 0) {
    //         $html .= '<table class="table table-bordered aiz-table">';
    //         $html .= '<thead>
    //             <tr>
    //                 <td class="text-center">Variant</td>
    //                 <td class="text-center">Variant Price</td>
    //                 <td class="text-center" data-breakpoints="lg">SKU</td>
    //                 <td class="text-center" data-breakpoints="lg">Quantity</td>
    //                 <td class="text-center">Image</td>
    //             </tr>
    //         </thead>';
    //         $html .= '<tbody>';

    //         foreach ($combinations as $combination) {
    //             $sku = '';
    //             foreach (explode(' ', $product_name) as $value) {
    //                 $sku .= substr($value, 0, 1);
    //             }

    //             $str = '';
    //             foreach ($combination as $key => $item) {
    //                 if ($key > 0) {
    //                     $str .= '-' . str_replace(' ', '', $item);
    //                     $sku .= '-' . str_replace(' ', '', $item);
    //                 } else {
    //                     $str .= str_replace(' ', '', $item);
    //                     $sku .= '-' . str_replace(' ', '', $item);
    //                 }
    //             }

    //             if (strlen($str) > 0) {
    //                 $html .= '<tr class="variant">';
    //                 $html .= '<td><label for="" class="control-label">' . $str . '</label></td>';
    //                 $html .= '<td><input type="number" lang="en" name="price_' . $str . '" value="' . $unit_price . '" min="0" step="0.01" class="form-control" required></td>';
    //                 $html .= '<td><input type="text" name="sku_' . $str . '" value="' . $str . '" class="form-control"></td>';
    //                 $html .= '<td><input type="number" lang="en" name="qty_' . $str . '" value="' . $quantity . '" min="1" step="1" class="form-control" required></td>';
    //                 $html .= '<td>
    //                             <div class="input-group custome-filemanager" data-type="image" onclick="customFilemanager(this)">
    //                                     <div class="input-group-prepend">
    //                                         <div
    //                                             class="input-group-text bg-soft-secondary font-weight-medium">
    //                                             Browse
    //                                         </div>
    //                                     </div>
    //                                     <div class="form-control file-amount">Choose File</div>
    //                                     <input type="hidden" name="img_' . $str . '" class="selected-files" id="logoFile">
    //                                 </div>
    //                             <div class="row mx-1  filemanager-image-preview"></div>
    //                           </td>';
    //                 $html .= '</tr>';
    //             }
    //         }

    //         $html .= '</tbody>';
    //         $html .= '</table>';

    //     }

    //     // Return the generated HTML in the response
    //     return response()->json($html);
    // }

    
    // public function sku_combination_edit(Request $request)
    // {
    //       //dd($request->warehouse_location_id);
    //     $location_id= $request->warehouse_location_id;
    //     if(!$location_id){
    //         return response()->json([
    //             'status_code' => 500,
    //             'message' => $this->get_message('Please select the warehouse location'),
    //         ], 500);
    //     }
    //     $product = Product::where('uuid',$request->uuid)->first();
    //      // dd($product);
    //     $product_name = $product->name;
    //     $unit_price = $product->unit_price;

    //     $options = [];

    //     // Collecting options based on the request
    //     if ($request->has('choice_no')) {
    //         foreach ($request->choice_no as $key => $no) {
    //             $name = 'choice_options_' . $no;
    //             if (isset($request[$name])) {
    //                 $data = [];
    //                 foreach ($request[$name] as $item) {
    //                     array_push($data, $item);
    //                 }
    //                 array_push($options, $data);
    //             }
    //         }
    //     }
    //     //dd($product);
    //     // Generate combinations
    //     $combinations = (new CombinationService())->generate_combination($options);
    //    // dd($options);
    //     $html = '';

    //     if (count($combinations) > 0) {
    //         $html .= '<table class="table table-bordered aiz-table">';
    //         $html .= '<thead>
    //             <tr>
    //                 <td class="text-center">Variant</td>
    //                 <td class="text-center">Variant Price</td>
    //                 <td class="text-center" data-breakpoints="lg">SKU</td>
    //                 <td class="text-center" data-breakpoints="lg">Quantity</td>
    //                 <td class="text-center" data-breakpoints="lg">Image</td>
    //             </tr>
    //         </thead>';
    //         $html .= '<tbody>';

    //         foreach ($combinations as $key => $combination) {

    //             $sku = '';

    //             // Generate SKU from product name
    //             foreach (explode(' ', $product_name) as $value) {
    //                 $sku .= substr($value, 0, 1);
    //             }

    //             $str = '';
    //             foreach ($combination as $key => $item) {
    //                 if ($key > 0) {
    //                     $str .= '-' . str_replace(' ', '', $item);
    //                     $sku .= '-' . str_replace(' ', '', $item);
    //                 } else {
    //                     $str .= str_replace(' ', '', $item);
    //                     $sku .= '-' . str_replace(' ', '', $item);
    //                 }
    //                 // Fetch stock details if available
    //                 $stock = $product->productStocks->where('variant', $str)->where('location_id',$location_id)->first();
    //                 //dd($stock);
    //               //  dd($product);
    //             }

    //             if (strlen($str) > 0) {
    //                 //dd($stock);
    //                 $html .= '<tr class="variant">';
    //                 $html .= '<td><label for="" class="control-label">' . $str . '</label></td>';
    //                 $html .= '<td><input type="number" lang="en" name="price_' . $str . '" value="' . ($stock != null && $stock->price != null ? $stock->price : $unit_price) . '" min="0" step="0.01" class="form-control" required></td>';
    //                 $html .= '<td><input type="text" name="sku_' . $str . '" value="' . ($stock != null && $stock->sku != null ? $stock->sku : $str) . '" class="form-control" required></td>';
    //                 $html .= '<td><input type="number" lang="en" name="qty_' . $str . '" value="' . ($stock != null ? $stock->qty : 1) . '" min="0" step="1" class="form-control" required></td>';
    //                 $html .= '<td>
    //                             <div class="input-group custome-filemanager" data-type="image" onclick="customFilemanager(this)">
    //                                     <div class="input-group-prepend">
    //                                         <div
    //                                             class="input-group-text bg-soft-secondary font-weight-medium">
    //                                             Browse
    //                                         </div>
    //                                     </div>
    //                                     <div class="form-control file-amount">Choose File</div>
    //                                     <input type="hidden" name="img_' . $str . '" value="'.($stock != null ? $stock->image : '').'" class="selected-files" id="logoFile">
    //                                 </div>
    //                         <div class="row mx-1  filemanager-image-preview"></div>
    //                 </td>';
    //                 $html .= '</tr>';
    //             }
    //         }

    //         $html .= '</tbody>';
    //         $html .= '</table>';
    //     }

    //     // Return the generated HTML in the response
    //     return response()->json($html);

    // }




    public function sku_simple_edit(Request $request)
    {
        $location_id= $request->warehouse_location_id;
        // dd($request->uuid);
        if(!$location_id){
            return response()->json([
                'status_code' => 500,
                'message' => $this->get_message('Please select the warehouse location'),
            ], 500);
        }
        $product = Product::where('uuid',$request->uuid)->first();
       // dd($product); 
        $stock = $product->productStocks->where('location_id',$location_id)->where('product_id', $request->uuid)->first();
       
       //  dd($stock); 
        if(!$stock){
            $price = 0;
            $sku = '';
            $qty = 0;
        }else{
            $price = $stock->price?$stock->price:0;
            $sku = $stock->sku?$stock->sku:'';
            $qty = $stock->qty?$stock->qty:0;
        }
        $data=[ 
            'price' =>$price,
            'sku' =>$sku,
            'qty' =>$qty,
        ];
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'data' => $data,
        ], Response::HTTP_OK);
      

    }


    public function import_products(Request $request)
{
    $validator = Validator::make($request->all(), [
        'excel_file' => 'required',
    ]);
    
    // If validation fails, return validation errors
    if ($validator->fails()) {
        return response()->json([
            'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'errors' => strval($validator->errors())
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
    
    try {
        // DB::beginTransaction();
        $file = $request->file('excel_file');
        
        // Load the spreadsheet and get total row count (excluding header)
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow(); // gets highest row with data
        
        $nonEmptyRowCount = 0;
 
        $expectedHeaders = ['name', 'slug', 'warehouse_location', 'unit_price', 'compare_price', 'cost_price','current_stock','description','categories','weight','unit','meta_title','meta_description','vendor','country_name','product_type','physical_product','tags','channels','markets','hscode','collections'];
        $headerRow = $sheet->rangeToArray("A1:V1", null, true, false)[0];
        $normalizedHeaders = array_map('strtolower', array_map('trim', $headerRow));
        //dd( $headerRow);
        $missingHeaders = [];
            foreach ($expectedHeaders as $index => $expected) {
                if (!isset($normalizedHeaders[$index]) || $normalizedHeaders[$index] !== $expected) {
                    $missingHeaders[] = $expected;
                }
            }
        if (!empty($missingHeaders)) {
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'Missing or incorrect column headers: ' . implode(', ', $missingHeaders),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        // Loop through each row starting from 2 (assuming row 1 is the header)
        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = $sheet->rangeToArray("A{$row}:" . $sheet->getHighestColumn() . "{$row}", null, true, false)[0];

            // Count row only if it contains any non-empty cell
            if (array_filter($rowData)) {
                $nonEmptyRowCount++;
            }
        }

        if ($nonEmptyRowCount > 1000) {
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'You can upload a maximum of 1000 products.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        Excel::import(new ProductTemp($request), $request->file('excel_file'));
        $this->productImportFinal();

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Products Imported.',
            'data' => [],
        ], Response::HTTP_OK);

    } catch (\Exception $e) {
        // Handle general exceptions
        // dd($e->getMessage());
        return response()->json([
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            // 'message' => $this->get_message('server_error'),
            'message' => $e->getMessage(),
        ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
    }
}

  
    public function productImportFinal(){
        $masterImport = MasterImportProductLog::where('queue_status','!=','Completed')->get();
       
        if ($masterImport->count() > 0) {
            //dd($masterImport->count()); 
          //  $this->runQueueWorker();
            foreach($masterImport as $master){
                $userId = null;
                $userData = User::where('uuid',$master->auth_id)->first();
                if ($userData) {
                    $userId = $userData->id;
                }
                $productTemps = ModelsProductTemp::where('master_import_uuid', $master->uuid)->get();
                ///////////////
                (new ProductImportJob($master->uuid, $productTemps, $master->auth_id, $userId))->handle(); 
                ///////////////
                // if ($productTemps->isNotEmpty()) {
                //     dispatch(new ProductImportJob($master->uuid, $productTemps,$master->auth_id, $userId))
                //         ->onQueue('imports');
                //     $master->update(['queue_status' => 'Processing']);
                // }
            }
            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Product import process has been initiated.',
            ], Response::HTTP_OK);
        }
    }

//     public function productImportFinal()
// {
//     $masterImport = MasterImportProductLog::where('queue_status', '!=', 'Completed')->get();

//     if ($masterImport->count() > 0) {
//         $this->runQueueWorker();
//         foreach ($masterImport as $master) {
//             $userId = null;
//             $userData = User::where('uuid', $master->auth_id)->first();
//             if ($userData) {
//                 $userId = $userData->id;
//             }

//             $productTemps = ModelsProductTemp::where('master_import_uuid', $master->uuid)->get();

//             if ($productTemps->isNotEmpty()) {
//                 // Break productTemps into chunks of 10
//                 $chunks = $productTemps->chunk(10);
//                 foreach ($chunks as $chunk) {
//                     dispatch(new ProductImportJob($master->uuid, $chunk, $master->auth_id, $userId))
//                         ->onQueue('imports');
//                 }

//                 $master->update(['queue_status' => 'Processing']);
//             }
//         }

//         return response()->json([
//             'status_code' => Response::HTTP_OK,
//             'message' => 'Product import process has been initiated in batches of 10.',
//         ], Response::HTTP_OK);
//     }

//     // Optional: return response when no imports found
//     return response()->json([
//         'status_code' => Response::HTTP_NO_CONTENT,
//         'message' => 'No pending product imports found.',
//     ], Response::HTTP_NO_CONTENT);
// }



    public function runQueueWorker()
    {
        Artisan::call('queue:work', [
            '--queue' => 'imports',
            '--stop-when-empty' => true,
            '--timeout'=>600, '--tries'=>5,
        ]);

        return true;
    }


    public function exportProducts()
    {
        //$filename = 'products_export_' . Str::random(6) . '.csv';
        $filename = 'products_export_' . Str::random(6) . '.xlsx';
        $path = 'exports/' . $filename; 
        Excel::store(new ProductsExport, $path, 'public'); 
        $url = Storage::disk('public')->url('app/public/exports/' . $filename);
       // dd($url);
        return response()->json(['success' => true, 'url' => $url]);
    }

    public function emptyTables()
    {
        try {
            // Truncate the tables
          //  DB::table('products')->truncate();
            DB::table('product_stocks')->truncate();
            DB::table('inventories')->truncate();

            return response()->json([
                'message' => 'Tables emptied successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error while emptying tables',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
