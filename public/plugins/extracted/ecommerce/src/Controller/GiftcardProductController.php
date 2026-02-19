<?php

namespace App\Http\Controllers\API\Ecommerce;

use App\Models\Ecommerce\Product;
use Illuminate\Support\Str;
use App\Models\Ecommerce\ProductStock;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Number;
use App\Models\Ecommerce\GiftcardProduct;
use App\Models\Ecommerce\WarehouseValues;
use Illuminate\Support\Facades\DB;
use App\Services\PermissionService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Ecommerce\GiftcardProductVariant;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class GiftcardProductController extends Controller
{
    use MessageTrait;
    protected $permissionService;

    public function __construct(
        PermissionService $permissionService,
    )
    {
        $this->permissionService = $permissionService;
    }

    public function add_giftcard_product(Request $request)
{
    $validator = Validator::make($request->all(), [
        'title' => 'required|max:255',
        'location_id' => 'nullable|exists:warehouse_locations,uuid',
        'variants' => 'required|array',
        'variants.*.variant' => 'nullable|string',
        'variants.*.price' => 'required|numeric',
        'variants.*.qty' => 'nullable|integer',
        'variants.*.sku' => 'nullable|string',
        'variants.*.location_id' => 'nullable|exists:warehouse_locations,uuid',
        'collection_id' => 'nullable|array',
        'collection_id.*' => 'exists:collections,uuid',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'errors' => strval($validator->errors())
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    try {
        DB::beginTransaction();
        
        // Create the giftcard product
        $giftcard = new GiftcardProduct();
        $giftcard->uuid = Str::uuid();
        $giftcard->auth_id = Auth::user()->uuid;
        $giftcard->title = $request->title;
        $giftcard->description = $request->description;
        $giftcard->short_desc = $request->short_desc;
        $giftcard->type = $request->type;
        $giftcard->tags = $request->tags;
        $giftcard->vendor = $request->vendor;
        $giftcard->status = $request->status ?? 0;
        
        // Handle media/images
        $media = null;
        $thumbnail = null;
        $remainingImages = null;
        if ($request->has('media') && !empty($request->media)) { 
            $media = $request->media;
            $mediaArray = is_string($media) ? explode(',', $media) : (is_array($media) ? $media : [$media]);
            $mediaArray = array_map('trim', $mediaArray);
            $thumbnail = $mediaArray[0];     
            array_shift($mediaArray);
            $remainingImages = !empty($mediaArray) ? implode(',', $mediaArray) : null;
            $mediaString = is_string($media) ? $media : implode(',', $mediaArray);
            $giftcard->media = $media;
        }
        
        // SEO fields
        $giftcard->page_title = $request->page_title;
        $giftcard->meta_description = $request->meta_description;
        
        // URL handling
        if ($request->url_handle) {
            $giftcard->url_handle = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->url_handle));
        } else {
            $giftcard->url_handle = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->title)) . '-' . Str::random(5);
        }
        
        $giftcard->published_date = $request->published_date;
        $giftcard->theme_template = $request->theme_template;
        $giftcard->giftcard_template = $request->giftcard_template;
        
        $giftcard->save();

        // Create the corresponding Product entry
        $product = new Product();
        $product->uuid = Str::uuid();
        $product->auth_id = Auth::user()->uuid;
        $product->giftcard_product_id = $giftcard->uuid; 
        $product->name = $request->title;
        $product->slug = $giftcard->url_handle;
        $product->unit_price = 0;
        $product->warehouse_location_id = $request->location_id;
        $product->thumbnail_img = $thumbnail; 
        $product->images = $remainingImages;
        $product->description = $request->description;
        $product->short_description = $request->short_desc;
        $product->meta_title = $request->page_title;
        $product->type = $request->type;
        $product->tags = $request->tags;
        $product->vendor = $request->vendor;
        $product->product_type = 'giftcard';
        $product->meta_description = $request->meta_description;
        $product->published = ($request->status == 'active') ? 1 : 0;
        $product->save();

        if ($request->has('collection_id') && !empty($request->collection_id)) {
            $collectionIds = is_array($request->collection_id) 
                ? $request->collection_id 
                : array_map('trim', explode(',', $request->collection_id));
            $product->collections()->attach($collectionIds);
        }
        $product->salesChannels()->attach($request->sale_channel_id);
        $product->markets()->attach($request->market_id);
        
        if ($request->has('variants') && is_array($request->variants)) {
            foreach ($request->variants as $variant) {
                $giftcardVariant = new GiftcardProductVariant();
                $giftcardVariant->uuid = Str::uuid();
                $giftcardVariant->auth_id = Auth::user()->uuid;
                $giftcardVariant->giftcard_product_id = $giftcard->uuid;
                $giftcardVariant->variant = $variant['variant'] ?? '';
                $giftcardVariant->sku =  random_int(10000000000000, 99999999999999);
                $giftcardVariant->price = $variant['price'] ?? 0;
                $giftcardVariant->qty = $variant['qty'] ?? 0;
                $giftcardVariant->image = "";
                $giftcardVariant->location_id = $variant['location_id'] ?? $request->location_id;
                $giftcardVariant->save();
                
                // Create corresponding ProductStock
                $productStock = new ProductStock();
                $productStock->uuid = Str::uuid();
                $productStock->product_id = $product->uuid;
                $productStock->location_id = $giftcardVariant->location_id;
                $productStock->variant = $giftcardVariant->variant;
                $productStock->sku = $giftcardVariant->sku;
                $productStock->price = $giftcardVariant->price;
                $productStock->qty = $giftcardVariant->qty;
                $productStock->image = $giftcardVariant->image;
                $productStock->auth_id = Auth::user()->uuid;
                $productStock->save();
            }
        } else {
            // Create default variant if no variants are provided
            $giftcardVariant = new GiftcardProductVariant();
            $giftcardVariant->uuid = Str::uuid();
            $giftcardVariant->auth_id = Auth::user()->uuid;
            $giftcardVariant->giftcard_product_id = $giftcard->uuid;
            $giftcardVariant->variant = '';
            $giftcardVariant->sku = $request->sku ?? '';
            $giftcardVariant->price = $request->price ?? 0;
            $giftcardVariant->qty = $request->qty ?? 0;
            $giftcardVariant->image = '';
            $giftcardVariant->location_id = $request->location_id;
            $giftcardVariant->save();
            
            // Create corresponding ProductStock
            $productStock = new ProductStock();
            $productStock->uuid = Str::uuid();
            $productStock->product_id = $product->uuid;
            $productStock->location_id = $request->location_id;
            $productStock->variant = '';
            $productStock->sku = $request->sku ?? '';
            $productStock->price = $request->price ?? 0;
            $productStock->qty = $request->qty ?? 0;
            $productStock->image = ''; // Use thumbnail for default variant
            $productStock->auth_id = Auth::user()->uuid;
            $productStock->save();
        }
        
        DB::commit();
        
        return response()->json([
            'status_code' => 200,
            'message' => $this->get_message('add'),
        ], 200);

    } catch (\Illuminate\Database\QueryException $e) {
        DB::rollBack();
        
        return response()->json([
            'status_code' => 500,
            'message' => $e->getMessage(),
        ], 500);

    } catch (\Throwable $th) {
        DB::rollBack();
        
        return response()->json([
            'status_code' => 500,
            'message' => $th->getMessage(),
        ], 500);
    }
}

    public function get_giftcard_product(Request $request)
    {
        try {
            $menuUuid = request()->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);
            
            $get_all_giftcards = GiftcardProduct::
            with('variants') 
            ->orderBy('id', 'desc');
            
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $get_all_giftcards = $get_all_giftcards->where('auth_id', Auth::user()->uuid);
                }
            } else {
                if (Auth::user()->hasPermission('viewglobal')) {
                    $get_all_giftcards = $get_all_giftcards;
                } else {
                    return response()->json([
                        'message' => 'You do not have permission to view this menu'
                    ], Response::HTTP_FORBIDDEN);
                }
            }
            
            $get_all_giftcards = $get_all_giftcards->get();
           
            return response()->json([
                'status_code' => 200,
                'permissions' => $permissions,
                'data' => $get_all_giftcards,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update giftcard product
     */
    public function update_giftcard_product(Request $request)
{
    $validator = Validator::make($request->all(), [
        'title' => 'required|max:255',
        'location_id' => 'nullable|exists:warehouse_locations,uuid',
        'variants' => 'required|array',
        'collection_id' => 'nullable|array', 
        'collection_id.*' => 'exists:collections,uuid',
    ]);
    
    if ($validator->fails()) {
        return response()->json([
            'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'errors' => strval($validator->errors())
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    $uuid = request()->header('uuid');

    try {
        // Find the giftcard product
        $giftcard = GiftcardProduct::where('uuid', $uuid)->first();
        
        if (!$giftcard) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Giftcard product not found',
            ], Response::HTTP_NOT_FOUND);
        }
        
        // Find the corresponding product
        $product = Product::where('giftcard_product_id', $giftcard->uuid)
        ->where('name', $giftcard->title)
        ->where('slug', $giftcard->url_handle)
        ->first();    
        
        // Start transaction
        DB::beginTransaction();
        
        // Update giftcard fields
        if ($request->has('title')) {
            $giftcard->title = $request->title;
        }
        
        if ($request->has('description')) {
            $giftcard->description = $request->description;
        }
        
        if ($request->has('short_desc')) {
            $giftcard->short_desc = $request->short_desc;
        }

        if ($request->has('type')) {
            $giftcard->type = $request->type;
        }

        if ($request->has('tags')) {
            $giftcard->tags	 = $request->tags;
        }

        if ($request->has('vendor')) {
            $giftcard->vendor = $request->vendor;
        }
        
        if ($request->has('status')) {
            $giftcard->status = $request->status;
        }
        
        // Handle media update
        $media = null;
        if ($request->has('media') && !empty($request->media)) {
            $media = $request->media;
            $giftcard->media = $media;
        }
        
        // SEO fields
        if ($request->has('page_title')) {
            $giftcard->page_title = $request->page_title;
        }
        
        if ($request->has('meta_description')) {
            $giftcard->meta_description = $request->meta_description;
        }
        
        // URL handle
        if ($request->has('url_handle')) {
            $giftcard->url_handle = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->url_handle));
        }
        
        if ($request->has('published_date')) {
            $giftcard->published_date = $request->published_date;
        }
        
        if ($request->has('theme_template')) {
            $giftcard->theme_template = $request->theme_template;
        }
        
        if ($request->has('giftcard_template')) {
            $giftcard->giftcard_template = $request->giftcard_template;
        }
        
        $giftcard->save();
        
        // Update corresponding product if it exists
        if ($product) {
            if ($request->has('title')) {
                $product->name = $request->title;
            }
            
            if ($request->has('description')) {
                $product->description = $request->description;
            }
            
            if ($request->has('short_desc')) {
                $product->short_description = $request->short_desc;
            }
            
            if ($request->has('url_handle')) {
                $product->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $request->url_handle));
            }
            
            if ($request->has('media')) {
                $product->thumbnail_img = $media;
                $product->images = $media;
            }
            
            if ($request->has('page_title')) {
                $product->meta_title = $request->page_title;
            }

            if ($request->has('type')) {
                $product->type = $request->type;
            }

            if ($request->has('tags')) {
                $product->tags = $request->tags;
            }

            if ($request->has('vendor')) {
                $product->vendor = $request->vendor ;
            }
            
            if ($request->has('meta_description')) {
                $product->meta_description = $request->meta_description;
            }
            
            if ($request->has('status')) {
                $product->published = ($request->status == 'active') ? 1 : 0;
            }
            
            if ($request->has('location_id')) {
                $product->warehouse_location_id = $request->location_id;
            }
            
            $product->save();
        }      
       
        if ($request->has('variants') && is_array($request->variants)) {
            $currentVariants = GiftcardProductVariant::where('giftcard_product_id', $giftcard->uuid)->get();
            
            $variantIds = $currentVariants->pluck('uuid')->toArray();
            
            if (!empty($variantIds) && $product) {
                $variantNames = $currentVariants->pluck('variant')->toArray();
                
                ProductStock::where('product_id', $product->uuid)
                    ->whereIn('variant', $variantNames)
                    ->delete();
            }

            $currentVariantsMap = GiftcardProductVariant::where('giftcard_product_id', $giftcard->uuid)
            ->get()
            ->keyBy('variant');
            
            GiftcardProductVariant::where('giftcard_product_id', $giftcard->uuid)->delete();
            
            foreach ($request->variants as $variantData) {
                $oldVariant = $currentVariantsMap[$variantData['variant']] ?? null;

                $newVariant = new GiftcardProductVariant();
                $newVariant->uuid = Str::uuid();
                $newVariant->auth_id = Auth::user()->uuid;
                $newVariant->giftcard_product_id = $giftcard->uuid;
                $newVariant->variant = $variantData['variant'] ?? '';
                $newVariant->sku = $oldVariant->sku;
                $newVariant->price = $variantData['price'] ?? 0;
                $newVariant->qty = $variantData['qty'] ?? 0;
                $newVariant->image = $variantData['image'] ?? null;
                $newVariant->location_id = $variantData['location_id'] ?? $request->location_id;
                $newVariant->save();
                
                if ($product) {
                    $productStock = new ProductStock();
                    $productStock->uuid = Str::uuid();
                    $productStock->product_id = $product->uuid;
                    $productStock->location_id = $newVariant->location_id;
                    $productStock->variant = $newVariant->variant;
                    $productStock->sku = $newVariant->sku;
                    $productStock->price = $newVariant->price;
                    $productStock->qty = $newVariant->qty;
                    $productStock->image = $newVariant->image;
                    $productStock->auth_id = Auth::user()->uuid;
                    $productStock->save();
                }
            }
        }

        if ($request->has('collection_id') && !empty($request->collection_id)) {
            $collectionIds = is_array($request->collection_id) 
                ? $request->collection_id 
                : array_map('trim', explode(',', $request->collection_id));
            $product->collections()->sync($collectionIds);
        } else {
            $product->collections()->sync([]); // Clear collections if none provided
        }
        $product->salesChannels()->sync($request->sale_channel_id, ['product_uuid' => $product->uuid]);
        $product->markets()->sync($request->market_id, ['product_uuid' => $product->uuid]);
        DB::commit();
        
        return response()->json([
            'status_code' => 200,
            'message' => $this->get_message('update'),
        ], 200);

    } catch (\Throwable $th) {
        DB::rollBack();
        
        return response()->json([
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $th->getMessage(),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
    /**
     * Edit giftcard product
     */
   public function edit_giftcard_product($uuid) {
    try {
        $edit_giftcard_product = GiftcardProduct::with(['variants', 'product.collections', 'product.salesChannels', 'product.markets'])
            ->where('uuid', $uuid)
            ->first();

        if ($edit_giftcard_product) {
            // Get collections associated with the product
            $collections = [];
            if ($edit_giftcard_product->product && $edit_giftcard_product->product->collections) {
                $collections = $edit_giftcard_product->product->collections->pluck('uuid')->toArray();
            }

            // Get sales channels associated with the product
            $salesChannels = [];
            if ($edit_giftcard_product->product && $edit_giftcard_product->product->salesChannels) {
                $salesChannels = $edit_giftcard_product->product->salesChannels->pluck('uuid')->toArray();
            }

            $markets = [];
            if ($edit_giftcard_product->product && $edit_giftcard_product->product->markets) {
                $markets = $edit_giftcard_product->product->markets->pluck('uuid')->toArray();
            }

            // Prepare the response data
            $responseData = [
                'title' => $edit_giftcard_product->title,
                'description' => $edit_giftcard_product->description,
                'short_desc' => $edit_giftcard_product->short_desc,
                'type' => $edit_giftcard_product->type,
                'vendor' => $edit_giftcard_product->vendor,
                'status' => $edit_giftcard_product->status,
                'tags' => $edit_giftcard_product->tags,
                'page_title' => $edit_giftcard_product->page_title,
                'meta_description' => $edit_giftcard_product->meta_description,
                'media' => $edit_giftcard_product->media,
                'url_handle' => $edit_giftcard_product->url_handle,
                'published_date' => $edit_giftcard_product->published_date,
                'theme_template' => $edit_giftcard_product->theme_template,
                'giftcard_template' => $edit_giftcard_product->giftcard_template,
                'collections' => $collections, // Return as array
                'sale_channel_id' => $salesChannels ? implode(',', $salesChannels) : '',
                'market_id' => $markets ? implode(',', $markets) : '',
                'variants' => $edit_giftcard_product->variants->map(function($variant) {
                    return [
                        'price' => $variant->price,
                        'variant' => $variant->variant,
                        'sku' => $variant->sku,
                        'qty' => $variant->qty,
                        'image' => $variant->image,
                        'location_id' => $variant->location_id
                    ];
                })->toArray()
            ];

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'data' => $responseData,
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => $this->get_message('not_found'),
            ], Response::HTTP_NOT_FOUND);
        }
    } catch (\Exception $e) {
        return response()->json([
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $this->get_message('server_error'),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}


    /**
     * Get single giftcard product
     */
    public function get_single_giftcard_product(Request $request)
    {
        try {
            $uuid = request()->header('uuid');
            
            $giftcard = GiftcardProduct::where('uuid', $uuid)
                ->with(['variants' => function ($query) {
                    $query->select('uuid', 'giftcard_product_id', 'variant', 'sku', 'price', 'qty', 'image', 'location_id');
                }])
                ->first();
            
            if (!$giftcard) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Giftcard product not found',
                ], Response::HTTP_NOT_FOUND);
            }
            
            return response()->json([
                'status_code' => 200,
                'data' => $giftcard,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete giftcard product
     */
    public function delete_giftcard_product(Request $request)
{
    try {
        $uuid = request()->header('uuid');

        $giftcard = GiftcardProduct::where('uuid', $uuid)->first();

        if (!$giftcard) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Giftcard product not found',
            ], Response::HTTP_NOT_FOUND);
        }

        DB::beginTransaction();

        $product = Product::where('giftcard_product_id', $giftcard->uuid)
            ->where('name', $giftcard->title)
            ->where('slug', $giftcard->url_handle)
            ->first(); 

        $giftcardVariants = GiftcardProductVariant::where('giftcard_product_id', $uuid)->get();
        $variantNames = $giftcardVariants->pluck('variant')->toArray();

        GiftcardProductVariant::where('giftcard_product_id', $uuid)->delete();

        if ($product) {
            ProductStock::where('product_id', $product->uuid)
                ->whereIn('variant', $variantNames)
                ->delete();

            $product->delete();
        }

        $product->collections()->detach();
        $product->salesChannels()->detach();
        $product->markets()->detach();

        $giftcard->delete();

        DB::commit();

        return response()->json([
            'status_code' => 200,
            'message' => $this->get_message('delete'),
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $this->get_message('server_error'),
            'error' => $e->getMessage()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

}
