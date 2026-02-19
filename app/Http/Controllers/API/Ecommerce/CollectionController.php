<?php

namespace App\Http\Controllers\API\Ecommerce;

use DB;
use Carbon\Carbon;
use App\Models\Brand;
use App\Models\Ecommerce\Product;
use App\Models\Ecommerce\Collection;
use Illuminate\Support\Str;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Ecommerce\CollectionTranslation;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class CollectionController extends Controller
{
    protected $permissionService;
    use MessageTrait;
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{
            $menuUuid = request()->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);
            $collections = Collection::withCount('products')->orderBy('id', 'desc');
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $collections = $collections->where('auth_id', Auth::user()->uuid);
                }
            }else{
                if (Auth::user()->hasPermission('viewglobal')) {
                    $collections = $collections;
                } else {
                    return response()->json([
                        'message' => 'You do not have permission to view this menu'
                    ], Response::HTTP_FORBIDDEN);
                }
            } 
            $collections = $collections->get();

            return response()->json([
                'status_code'=>200,
                'permissions' => $permissions,
                'data'=>$collections
            ],200);

        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
    }




    public function get_active_collections(Request $request)
    {
        try{
            $limit = $request->has('limit') ? $request->limit : 10; // Default limit to 10 if not provided
            $collections = Collection::where('status', '1')
                ->orderBy('name', 'ASC')
                ->when($request->has('limit'), function ($query) use ($limit) {
                    return $query->limit($limit);
                })
                ->get()
                ->map(function ($collection) {
                    foreach ($collection->getAttributes() as $key => $value) {
                        if (is_null($value)) {
                            $collection->$key = '';
                        }
                    }
                    if (empty($collection->image) || !file_exists(public_path($collection->image))) {
                        $collection->image = getConfigValue('APP_ASSET_PATH').'assets/images/no-image.png';
                    } else {
                        $collection->image = getConfigValue('APP_ASSET_PATH').$collection->image;
                    }
                    return $collection;
                });

            return response()->json([
                'status_code' => 200,
                'data' => $collections
            ], 200);

        }catch (\Exception $e) { 
            //dd($e);
            // Handle general exceptions
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $lang   = $request->language ?? defaultLanguages()->app_language_code;
        $langId = getLanguage($lang);
        $all_languages = all_languages();

        $validator = Validator::make($request->all(),[ 
        'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[^<>]+$/',
                'unique:collections,slug',
            ],
            'name' => [
                'nullable',
                'string',
                'max:150',
            ],
            'published_datetime' => [
                'nullable',
                'date',
            ],
        ], [
            'name.required' => 'The name field is required.', 
            'published_datetime.date' => 'The published date must be a valid date.',
        ]);

        if($validator->fails()) {            
            $message = $validator->messages();
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($message)
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $data = [
                // 'uuid' => Str::uuid(),
                // 'auth_id' => Auth::user()->uuid,
                'name'=> $request->name,
                'slug' => $request->slug ? preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->slug)) : preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->name)) . '-' . Str::random(5),
                'description' => $request->description,
                'channel_uuid' => json_encode($request->channel_uuid),
                'smart' =>  $request->smart,
                'condition_status' =>  $request->condition_status,
                'conditions' => json_encode($request->conditions),
                'featured' => $request->featured,
                'top' => $request->top,
                'image' => $request->image,
                'status' => $request->status,
                'published_datetime' => $request->published_datetime ? Carbon::parse($request->published_datetime) : null,
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
                'og_title' => $request->og_title,
                'og_description' => $request->og_description,
                'og_image' => $request->og_image,
                'x_title' => $request->x_title,
                'x_description' => $request->x_description,
                'x_image' => $request->x_image,
            ];
            //dd($data); 
                DB::beginTransaction();
                $collection = Collection::create($data);
                $this->updateCollectionTranslation($collection, $lang, $langId->uuid, $request, $all_languages);
               // dd($request->product_id);
               // $collection->products()->attach($request->product_id);
                if ($request->has('product_id')) {
                    $productSyncData = [];
                    $products = Product::whereIn('uuid', $request->product_id)->pluck('id','uuid')->toArray();
                    foreach ($products as $uuid => $productId) {
                        $productSyncData[$productId] = [
                            'collection_uuid' => $collection->uuid,
                            'product_uuid' => $uuid,
                        ];
                        // dd($productId,$uuid,$productSyncData);
                    }            
                    $collection->products()->sync($productSyncData);
                }
                DB::commit();
            if ($collection) {
                return response()->json([
                    'status_code'=>200,
                    'message'=>"Collection added successfully",
                ], 200);
            } else {
                return response()->json([
                    'status_code'=>500,
                    'message'=>$this->get_message('server_error'),
                ], 500);
            }
        }catch(\Exception $e) {
            dd($e);
            DB::rollBack(); 
            Log::error(['Collection Store Error'=>$e->getMessage()]);
            return response()->json([
                'status_code'=>500,
                'message'=>$this->get_message('server_error'),
            ], 500);
        } 
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

// edit collection without transaltion

//     public function edit(string $id)
// {
//     try {
//         $collection = Collection::findByUuid($id);
//         $productsQuery = Product::orderBy('uuid', 'desc')->with('salesChannels');

//         if ($collection) {
//             $collection->product_id = $collection->products()->pluck('product_uuid')->toArray();

//             if ($collection->smart == "1" && !empty($collection->conditions)) {
//                 $conditions = json_decode($collection->conditions, true);

//                 if (is_array($conditions)) {
//                     // All conditions (AND)
//                     if ($collection->condition_status == 0) {
//                         foreach ($conditions as $condition) {
//                             $parts = explode(',', $condition);
//                             if (count($parts) === 3) {
//                                 [$field, $operator, $value] = array_map('trim', $parts);
//                                 self::applyCondition($productsQuery, $field, $operator, $value);
//                             }
//                         }
//                     } 
//                     // Any condition (OR)
//                     else {
//                         $productsQuery->where(function ($query) use ($conditions) {
//                             foreach ($conditions as $condition) {
//                                 $parts = explode(',', $condition);
//                                 if (count($parts) === 3) {
//                                     [$field, $operator, $value] = array_map('trim', $parts);
//                                     self::applyCondition($query, $field, $operator, $value, true); // OR mode
//                                 }
//                             }
//                         });
//                     }
//                 }
//             }

//             try {
//                 $collection->smart_products = $productsQuery->get();
//             } catch (\Exception $e) {
//                 Log::error('Smart products query error: ' . $e->getMessage());
//                 $collection->smart_products = [];
//             }

//             return response()->json([
//                 'status_code' => 200,
//                 'data' => $collection
//             ], 200);
//         } else {
//             return response()->json([
//                 'status_code' => 404,
//                 'message' => $this->get_message('not_found'),
//             ], 404);
//         }
//     } catch (\Exception $e) {
//         Log::error($e->getMessage());
//         return response()->json([
//             'status_code' => 500,
//             'message' => $this->get_message('server_error'),
//         ], 500);
//     }
// }


// edit collection with transaltion
public function edit(string $id, Request $request)
{
    try {
        // Get the default language or the one provided in the request
        $lang = getConfigValue('default_lang');
        if ($request->has('lang')) {
            $lang = $request->lang;
        }

        // Fetch the collection with translations for the specified language
        $collection = Collection::with(['collection_translation' => function ($query) use ($lang) {
            $query->where('lang', $lang);
        }])->where('uuid', $id)->first();

        if ($collection) {
            // Prepare the collection data with translated fields
            $data = [
                'uuid' => $collection->uuid,
                'name' => $collection->getTranslation('name', $lang),
                'slug' => $collection->slug,
                'channel_uuid' => $collection->channel_uuid,
                'condition_status' => $collection->condition_status,
                'conditions' => $collection->conditions,
                'description' => $collection->getTranslation('description', $lang),
                'featured' => $collection->featured,
                'top' => $collection->top,
                'image' => $collection->getTranslation('image', $lang),
                'smart' => $collection->smart,
                'meta_title' => $collection->getTranslation('meta_title', $lang),
                'meta_description' => $collection->getTranslation('meta_description', $lang),
                'og_title' => $collection->og_title,
                'og_description' => $collection->og_description,
                'og_image' => $collection->og_image,
                'x_title' => $collection->x_title,
                'x_description' => $collection->x_description,
                'x_image' => $collection->x_image,
                'status' => $collection->status,
                'published_datetime' => $collection->published_datetime,
            ];

            // Fetch associated product IDs
            $data['product_id'] = $collection->products()->pluck('product_uuid')->toArray();

            $productsQuery = Product::orderBy('uuid', 'desc')->with('salesChannels');
            if ($collection->smart == "1" && !empty($collection->conditions)) {
                $conditions = json_decode($collection->conditions, true);

                if (is_array($conditions)) {
                    // All conditions (AND)
                    if ($collection->condition_status == 0) {
                        foreach ($conditions as $condition) {
                            $parts = explode(',', $condition);
                            if (count($parts) === 3) {
                                [$field, $operator, $value] = array_map('trim', $parts);
                                self::applyCondition($productsQuery, $field, $operator, $value);
                            }
                        }
                    } 
                    // Any condition (OR)
                    else {
                        $productsQuery->where(function ($query) use ($conditions) {
                            foreach ($conditions as $condition) {
                                $parts = explode(',', $condition);
                                if (count($parts) === 3) {
                                    [$field, $operator, $value] = array_map('trim', $parts);
                                    self::applyCondition($query, $field, $operator, $value, true); // OR mode
                                }
                            }
                        });
                    }
                }
            }

            try {
                $data['smart_products'] = $productsQuery->get();
            } catch (\Exception $e) {
                Log::error('Smart products query error: ' . $e->getMessage());
                $data['smart_products'] = [];
            }

            return response()->json([
                'status_code' => 200,
                'data' => $data
            ], 200);
        } else {
            return response()->json([
                'status_code' => 404,
                'message' => $this->get_message('not_found'),
            ], 404);
        }
    } catch (\Exception $e) {
        Log::error($e->getMessage());
        return response()->json([
            'status_code' => 500,
            'message' => $this->get_message('server_error'),
        ], 500);
    }
}



private static function applyCondition($query, $field, $operator, $value, $useOr = false)
{
    // Map front-end field names to database columns or relationships
    $fieldMap = [
        'Title' => 'name',
        'Price' => 'unit_price',
        'Compare-at price' => 'unit_price', // Assuming no separate field; adjust if exists
        'Weight' => 'weight',
        'Inventory stock' => 'productStocks', // This will be handled specially in relationship logic
        'Variant\'s title' => 'variant', // Corrected - just the field name as it's accessed via relationship
        'Type' => 'digital', // Adjust based on actual field for product type
        'Category' => 'categories',
        'Tag' => 'tags',
        'Vendor' => 'vandors', // Corrected - just the relationship name
        'Meta Title' => 'meta_title',
        'Meta Description' => 'meta_description',
        'Meta URL' => 'slug',
    ];

    $dbField = $fieldMap[$field] ?? $field;

    // Handle different operators
    switch ($operator) {
        case 'contains':
            $clause = [$dbField, 'like', '%' . $value . '%'];
            break;
        case 'starts_with':
            $clause = [$dbField, 'like', $value . '%'];
            break;
        case 'ends_with':
            $clause = [$dbField, 'like', '%' . $value];
            break;
        case 'is_not_equal_to':
            $clause = [$dbField, '!=', $value];
            break;
        case 'is_equal_to':
            $clause = [$dbField, '=', $value];
            break;
        case 'does_not_contain':
            $clause = [$dbField, 'not like', '%' . $value . '%'];
            break;
        case 'is_greater_than':
            $clause = [$dbField, '>', $value];
            break;
        case 'is_less_than':
            $clause = [$dbField, '<', $value];
            break;
        case 'is_not_empty':
            $clause = [$dbField, '!=', null];
            break;
        case 'is_empty':
            $clause = [$dbField, '=', null];
            break;
        default:
            return; // Skip unknown operator
    }

    // Handle special cases for relationships or complex fields
    if ($field === 'Category') {
        // Join with product_categories and filter by category_uuid
        $method = $useOr ? 'orWhereHas' : 'whereHas';
        $query->$method('categories', function ($q) use ($value, $operator) {
            if ($operator === 'is_equal_to') {
                $q->where('category_uuid', $value);
            } elseif ($operator === 'is_not_equal_to') {
                $q->where('category_uuid', '!=', $value);
            }
        });
    } elseif ($field === 'Tag') {
        // Handle tags (assuming stored as JSON or comma-separated string)
        if ($useOr) {
            $query->orWhere('tags', 'like', '%' . $value . '%');
        } else {
            $query->where('tags', 'like', '%' . $value . '%');
        }
    } elseif ($field === 'Vendor') {
        if ($useOr) {
            $query->orWhere('vendor', 'like', '%' . $value . '%');
        } else {
            $query->where('vendor', 'like', '%' . $value . '%');
        }
    }elseif ($field === 'Inventory stock') {
        // Fix: Correctly handle inventory stock via productStocks relationship
        $method = $useOr ? 'orWhereHas' : 'whereHas';
        
        $query->$method('productStocks', function ($q) use ($value, $operator) {
            // Get the available quantity calculation logic from ProductStock model
            switch ($operator) {
                case 'is_equal_to':
                    // Using a subquery to calculate the inventory
                    $q->whereRaw('(SELECT SUM(qty) FROM inventories WHERE stock_id = product_stocks.uuid AND status = "available") = ?', [$value]);
                    break;
                case 'is_greater_than':
                    $q->whereRaw('(SELECT SUM(qty) FROM inventories WHERE stock_id = product_stocks.uuid AND status = "available") > ?', [$value]);
                    break;
                case 'is_less_than':
                    $q->whereRaw('(SELECT SUM(qty) FROM inventories WHERE stock_id = product_stocks.uuid AND status = "available") < ?', [$value]);
                    break;
                case 'is_not_empty':
                    $q->whereRaw('(SELECT SUM(qty) FROM inventories WHERE stock_id = product_stocks.uuid AND status = "available") > 0');
                    break;
                case 'is_empty':
                    $q->whereRaw('(SELECT SUM(qty) FROM inventories WHERE stock_id = product_stocks.uuid AND status = "available") = 0 OR (SELECT SUM(qty) FROM inventories WHERE stock_id = product_stocks.uuid AND status = "available") IS NULL');
                    break;
            }
        });
    } elseif ($field === 'Variant\'s title') {
        // Fix: Properly handle variant title via productStocks relationship
        $method = $useOr ? 'orWhereHas' : 'whereHas';
        $query->$method('productStocks', function ($q) use ($value, $operator) {
            // Apply the correct clause based on the operator
            switch ($operator) {
                case 'contains':
                    $q->where('variant', 'like', '%' . $value . '%');
                    break;
                case 'starts_with':
                    $q->where('variant', 'like', $value . '%');
                    break;
                case 'ends_with':
                    $q->where('variant', 'like', '%' . $value);
                    break;
                case 'is_not_equal_to':
                    $q->where('variant', '!=', $value);
                    break;
                case 'is_equal_to':
                    $q->where('variant', '=', $value);
                    break;
                case 'does_not_contain':
                    $q->where('variant', 'not like', '%' . $value . '%');
                    break;
                case 'is_not_empty':
                    $q->whereNotNull('variant')->where('variant', '!=', '');
                    break;
                case 'is_empty':
                    $q->where(function($query) {
                        $query->whereNull('variant')->orWhere('variant', '');
                    });
                    break;
            }
        }); 
    }  else {
        // Apply standard clause for simple fields
        if ($useOr) {
            $query->orWhere(...$clause);
        } else {
            $query->where(...$clause);
        }
    }
}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit1(string $id)
    {
        //
        try {
            $collection = Collection::findByUuid($id);
            $productsQuery = Product::orderBy('uuid','desc');  
                        if ($collection) {
                            $collection->product_id =$collection->products()->pluck('product_uuid')->toArray();
                            
                            if ($collection->smart == "1" && !empty($collection->conditions)) {
                                $conditions = json_decode($collection->conditions, true); // decode as array
                    
                                if (is_array($conditions)) {
                                    foreach ($conditions as $condition) { 
                                        $parts = explode(',', $condition);
                                        if (count($parts) === 3) {
                                            [$field, $operator, $value] = $parts;
                    
                                            switch ($operator) {
                                                case 'contain':
                                                    $productsQuery->where($field, 'like', '%' . $value . '%');
                                                    break;
                                                case 'start_with':
                                                    $productsQuery->where($field, 'like',  $value . '%');
                                                    break; 
                                                case 'end_with':
                                                    $productsQuery->where($field, 'like',  '%'. $value );
                                                    break;        
                                                case 'not_equql_to':
                                                    $productsQuery->where($field, '!=', $value);
                                                    break;
                                                case 'is_equql_to':
                                                    $productsQuery->where($field, '=', $value);
                                                    break; 
                                            }
                                        }
                                    }
                                }
                            }     
                          //  dd($productsQuery->toSQL());      
                          try {
                             $collection->smart_products = $productsQuery->get();
                            }catch (\Exception $e) {
                                $collection->smart_products = [];
                            }
                return response()->json([
                    'status_code'=>200,
                    'data'=>$collection
                ], 200);
            } else {
                return response()->json([
                    'status_code'=>404,
                    'message'=>$this->get_message('not_found'),
                ], 404);
            }
        }catch (\Exception $e) {
            dd($e);
            Log::error($e->getMessage());
            return response()->json([
                'status_code'=>500,
                'message'=>$this->get_message('server_error'),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    { 
        $lang   = $request->language ?? defaultLanguages()->app_language_code;
        $langId = getLanguage($lang);
       //dd($id);
        $validator = Validator::make($request->all(),[
            // 'slug' => [
            //     'nullable',
            //     'string',
            //     'max:255',
            //     'regex:/^[^<>]+$/',
            //     //'unique:collections,slug',
            //     'unique:collections,slug,'. $id . ',uuid', 
            // ],
            'name' => [
                'nullable',
                'string',
                'max:150',
            ],
            'published_datetime' => [
                'nullable',
                'date',
            ],
        ], [
            'name.required' => 'The name field is required.', 
            'published_datetime.date' => 'The published date must be a valid date.',
        ]);

        if($validator->fails()) {            
            $message = $validator->messages();
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($message)
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $data = [
                'name'=> $request->name,
                'slug' => $request->slug ? preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->slug)) : preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->name)) . '-' . Str::random(5),
                'description' => $request->description,
                'channel_uuid' => json_encode($request->channel_uuid),
                'smart' =>  $request->smart,
                'condition_status' =>  $request->condition_status,
                'conditions' => json_encode($request->conditions),
                'featured' => $request->featured,
                'published_datetime' => $request->published_datetime ? Carbon::parse($request->published_datetime) : null,
                'top' => $request->top,
                'image' => $request->image,
                'status' => $request->status,
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
                'og_title' => $request->og_title,
                'og_description' => $request->og_description,
                'og_image' => $request->og_image,
                'x_title' => $request->x_title,
                'x_description' => $request->x_description,
                'x_image' => $request->x_image,
            ];
            
            $collection = Collection::findByUuid($id);
            
            //$collection->products()->sync($request->product_id, ['product_uuid' => $id]);
           // dd($collection);
            //dd($id);
            if ($collection) {
                DB::beginTransaction();
                if($lang == defaultLanguages()->app_language_code){
                    $collection->update($data);
                }
                    $this->updateCollectionTranslation($collection, $lang, $langId->uuid, $request);
                
                // dd($request->product_id);
                // $collection->products()->sync($request->product_id);
                if ($request->has('product_id')) {
                    $productSyncData = [];
                    $products = Product::whereIn('uuid', $request->product_id)->pluck('id','uuid')->toArray();
                    foreach ($products as $uuid => $productId) {
                        $productSyncData[$productId] = [
                            'collection_uuid' => $collection->uuid,
                            'product_uuid' => $uuid,
                        ];
                        // dd($productId,$uuid,$productSyncData);
                    }            
                    $collection->products()->sync($productSyncData);
                }
                DB::commit();
                return response()->json([
                    'status_code'=>200,
                    'message'=>"Collection updated successfully",
                ], 200);
            }else{
                return response()->json([
                    'status_code'=>404,
                    'message'=>$this->get_message('not_found'),
                ], 404);
            } 
        }catch(\Exception $e) {
            DB::rollBack(); 
            Log::error(['Collection Update Error'=>$e->getMessage()]);
            return response()->json([
                'status_code'=>500,
                'message'=>$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        try {
            $collection = Collection::findByUuid($id);
            if ($collection) {
                $collection->delete();
                return response()->json([
                    'status_code'=>200,
                    'message' => "Collection delete successfully",
                ],200);
            } else {
                return response()->json([
                    'status_code'=>404,
                    'message'=>$this->get_message('not_found'),
                ], 404);
            }
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status_code'=>500,
                'message'=>$this->get_message('server_error'),
            ], 500);
        }
    }

    public function updateStatus(Request $request, string $id)
    {
        try {
            $collection = Collection::findByUuid($id);
            if ($collection) {
                $collection->status = $request->status;
                if ($collection->save()) {
                    return response()->json([
                        'status_code'=>200,
                        'message' => "Collection status updated successfully",
                    ], 200);
                } else {
                    return response()->json([
                        'status_code'=>500,
                        'message'=>$this->get_message('server_error'),
                    ], 500);
                }
            } else {
                return response()->json([
                    'status_code'=>404,
                    'message'=>$this->get_message('not_found'),
                ], 404);
            }
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
            return response()->json([
                'status_code'=>500,
                'message'=>$this->get_message('server_error'),
            ], 500);
        }
    }

    public function get_collection() {
        $collections = Collection::where('status', 1)->get();
        return response()->json([
            'status_code'=>200,
            'data'=>$collections
        ], 200);
    }


    private function updateCollectionTranslation(Collection $collection, string $lang, string $langUuid, Request $request, $languages = []): void
    {
        if ($languages) {
            foreach ($languages as $language) {
                $translation = CollectionTranslation::firstOrNew([
                    'lang' => $language->app_language_code,
                    'language_id' => $language->uuid,
                    'collection_uuid' => $collection->uuid
                ]);
                $translation->name = $request->name;
                $translation->description = $request->description;
                $translation->image = $request->image;
                $translation->meta_title = $request->meta_title;
                $translation->meta_description = $request->meta_description;
                $translation->save();
            }
        } else {
            $translation = CollectionTranslation::firstOrNew([
                'lang' => $lang,
                'language_id' => $langUuid,
                'collection_uuid' => $collection->uuid
            ]);
            $translation->name = $request->name;
            $translation->description = $request->description;
            $translation->image = $request->image;
            $translation->meta_title = $request->meta_title;
            $translation->meta_description = $request->meta_description;
            $translation->save();
        }
    }
}
