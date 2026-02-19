<?php

namespace App\Http\Controllers\API\Ecommerce;

use DB;
use Auth;
use Hash;
use Mail;
use Session;
use App\Models\User;
use App\Models\Brand;
use App\Models\Ecommerce\Product;
use App\Models\Ecommerce\Category;
use App\Models\Language;
use App\Models\Ecommerce\Collection;
use App\Models\Ecommerce\ProductStock;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use App\Models\Ecommerce\ProductDiscounts;
use App\Models\Ecommerce\ProductTranslation;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Http\Resources\ProductResource;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ApiProductController extends Controller
{

    use MessageTrait;

    // public function get_product($lang = "")
    // {
    //     try {
    //         // Check if the requested language exists in the languages table
    //         $language_exists = Language::where('app_language_code', $lang)->exists();

    //         // If the language doesn't exist, default to English ('en')
    //         if (!$language_exists) {
    //             $lang = 'en';
    //         }

    //         // Get all active products with status = 1
    //         $products = Product::where('status', '1')->get();

    //         // If no active products are found, return not found response
    //         if ($products->isEmpty()) {
    //             return response()->json([
    //                 'status_code' => Response::HTTP_NOT_FOUND,
    //                 'message' => $this->get_message('not_found'),
    //             ], Response::HTTP_NOT_FOUND);
    //         }

    //         $response = [];
    //         foreach ($products as $product) {
    //             // Try to get the translation for the requested language
    //             $translation = ProductTranslation::where('product_id', $product->id)
    //                 ->where('product_translations.status', '1')
    //                 ->join('languages', 'product_translations.language_id', '=', 'languages.id')
    //                 ->where('languages.app_language_code', $lang)
    //                 ->select(
    //                     'languages.app_language_code as language_code',
    //                     'languages.name as language_name',
    //                     'languages.flag as flag',
    //                     'languages.rtl as dir',
    //                     'product_translations.name as translate_name',
    //                     'product_translations.short_description as translate_short_description',
    //                     'product_translations.description as translate_description',
    //                     'product_translations.unit as translate_unit'
    //                 )
    //                 ->first();

    //             // If no translation is found, fall back to English
    //             if (!$translation) {
    //                 $translation = ProductTranslation::where('product_id', $product->id)
    //                     ->where('product_translations.status', '1')
    //                     ->join('languages', 'product_translations.language_id', '=', 'languages.id')
    //                     ->where('languages.app_language_code', 'en')
    //                     ->select(
    //                         'languages.app_language_code as language_code',
    //                         'languages.name as language_name',
    //                         'languages.flag as flag',
    //                         'languages.rtl as dir',
    //                         'product_translations.name as translate_name',
    //                         'product_translations.short_description as translate_short_description',
    //                         'product_translations.description as translate_description',
    //                         'product_translations.unit as translate_unit'
    //                     )
    //                     ->first();
    //             }

    //             // Prepare the response structure
    //             $response[] = [
    //                 'id' => $product->id,
    //                 'name' => $product->name,
    //                 'auth_id' => $product->auth_id,
    //                 'category_id' => $product->category_id,
    //                 'brand_id' => $product->brand_id,
    //                 'brand_name' => $product->brand->brand ?? null,
    //                 'brand_slug' => $product->brand->slug ?? null,
    //                 'thumbnail_img' => env('APP_ASSET_PATH').$product->thumbnail_img,
    //                 'tags' => $product->tags,
    //                 'description' => $product->description,
    //                 'short_description' => $product->short_description,
    //                 'unit_price' => $product->unit_price,
    //                 'attributes' => $product->attributes,
    //                 'choice_options' => $product->choice_options,
    //                 'todays_deal' => $product->todays_deal,
    //                 'published' => $product->published,
    //                 'approved' => $product->approved,
    //                 'stock_visibility_state' => $product->stock_visibility_state,
    //                 'cash_on_delivery' => $product->cash_on_delivery,
    //                 'featured' => $product->featured,
    //                 'current_stock' => $product->current_stock,
    //                 'unit' => $product->unit,
    //                 'weight' => $product->weight,
    //                 'min_qty' => $product->min_qty,
    //                 'discount' => $product->discount,
    //                 'discount_type' => $product->discount_type,
    //                 'discount_start_date' => $product->discount_start_date,
    //                 'discount_end_date' => $product->discount_end_date,
    //                 'tax' => $product->tax,
    //                 'tax_type' => $product->tax_type,
    //                 'shipping_type' => $product->shipping_type,
    //                 'shipping_cost' => $product->shipping_cost,
    //                 'meta_title' => $product->meta_title,
    //                 'meta_description' => $product->meta_description,
    //                 'meta_img' => $product->meta_img,
    //                 'pdf' => $product->pdf,
    //                 'slug' => $product->slug,
    //                 'rating' => $product->rating,
    //                 'barcode' => $product->barcode,
    //                 'digital' => $product->digital,
    //                 'auction_product' => $product->auction_product,
    //                 'wholesale_product' => $product->wholesale_product,
    //                 'product_top' => $product->product_top,
    //                 'sort' => $product->sort,
    //                 'created_at' => $product->created_at,
    //                 'updated_at' => $product->updated_at,
    //                 'language_code' => $translation->language_code ?? null,
    //                 'language_name' => $translation->language_name ?? null,
    //                 // 'flag' => env('APP_ASSET_PATH').$translation->flag ?? null,
    //                 'dir' => $translation->dir ?? null,
    //                 'translate_name' => $translation->translate_name ?? $product->name,
    //                 // 'translate_short_description' => $translation->translate_short_description ?? $product->short_description,
    //                 // 'translate_description' => $translation->translate_description ?? $product->description,
    //                 // 'translate_unit' => $translation->translate_unit ?? $product->unit,
    //                 'lang' => $translation->language_code ?? 'en',
    //             ];
    //         }

    //         // Return the structured response
    //         return response()->json([
    //             'status_code' => Response::HTTP_OK,
    //             'data' => $response,
    //         ], Response::HTTP_OK);

    //     } catch (\Exception $e) {
    //         // Handle any exception that occurs during the process
    //         return response()->json([
    //             'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
    //             'message' => $e->getMessage(),
    //         ], Response::HTTP_INTERNAL_SERVER_ERROR);
    //     }

    // }


    public function get_product(Request $request, $lang = "")
    {
        try {
            $products = Product::query()
                ->select([
                    'uuid',
                    'name',
                    'thumbnail_img',
                    'images',
                    'unit_price',
                    'compare_price',
                    'slug',
                    'tags',
                    'description',
                    'short_description',
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
                    'meta_title',
                    'meta_description',
                    'meta_img',
                    'pdf',
                    'sort',
                    'type',
                    'vat_id'
                ])
                ->where('status', '1');

            $filterableRelations = [
                'collection' => ['relation' => 'collections', 'column' => 'slug'],
                'category'   => ['relation' => 'categories', 'column' => 'slug'], // alias to collection maybe?
                'brand'      => ['relation' => 'brand', 'column' => 'slug'],
                'tag'        => ['column' => 'tags', 'type' => 'csv'],
                // Add more filters here as needed
            ];

            foreach ($request->only(array_keys($filterableRelations)) as $filterKey => $filterValue) {
                // if (!array_key_exists($filterKey, $filterableRelations)) continue;
                $filterConfig = $filterableRelations[$filterKey];
                $filterValues = array_filter(array_map('trim', explode(',', strtolower($filterValue))));
                if (empty($filterValues)) continue;
                if (!empty($filterConfig['type']) && $filterConfig['type'] === 'csv') {
                    $products->where(function ($query) use ($filterConfig, $filterValues) {
                        foreach ($filterValues as $val) {
                            $query->orWhereRaw("LOWER({$filterConfig['column']}) LIKE ?", ["%{$val}%"]);
                        }
                    });
                } else {
                    $products->whereHas($filterConfig['relation'], function ($query) use ($filterConfig, $filterValues) {
                        $query->whereIn($filterConfig['column'], $filterValues);
                    });
                }
            }

            $limit = $request->has('limit') ? $request->limit : null;
            if (empty($limit)) {
                $products = $products;
            } else {
                $products = $products->limit($limit);
            }

            $products = $products->with(['productStocks:uuid,product_id,variant,sku,price,image,qty', 'categories:uuid,name,slug', 'collections:uuid,name,slug,image', 'brand:uuid,slug,brand', 'discounts', 'vat'])->withSum('productStocks as total_stock', 'qty');
            $sort_order = $request->has('sort_order') && $request->sort_order === 'desc' ? 'desc' : 'asc';
            $products = $products->orderBy('id', $sort_order);
            
            $perPage = $request->has('limit') ? (int)$request->limit : null;
            $page = $request->has('page') ? (int)$request->page : 1;
            if (empty($perPage)) {
                $products = $products->get();
            } else {
                $products = $products->paginate($perPage, ['*'], 'page', $page);
            }


            $response  =  [
                'products' => ProductResource::collection($products),
                'pagination' => empty($perPage) && empty($page) ? [] : [
                    'current_page' => $page,
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'prev_page_url' => $products->previousPageUrl(),
                    'next_page_url' => $products->nextPageUrl(),
                ],
            ];

            // $filterableRelations = [
            //     'collection' => ['relation' => 'collections', 'column' => 'slug'],
            //     'category'   => ['relation' => 'categories', 'column' => 'slug'], // alias to collection maybe?
            //     'brand'      => ['relation' => 'brand', 'column' => 'slug'],
            //     'tag'        => ['column' => 'tags', 'type' => 'csv'],
            //     // Add more filters here as needed
            // ];
            // foreach ($request->all() as $filterKey => $filterValue) {
            //     if (!array_key_exists($filterKey, $filterableRelations)) continue;
            //     $filterConfig = $filterableRelations[$filterKey];
            //     $filterValues = array_map('trim', explode(',', strtolower($filterValue)));
            //     if (empty($filterValues)) continue;
            //     if (!empty($filterConfig['type']) && $filterConfig['type'] === 'csv') {
            //             $column = $filterConfig['column'];
            //             $products = $products->where(function ($query) use ($column, $filterValues) {
            //                 foreach ($filterValues as $val) {
            //                     $query->orWhereRaw("LOWER($column) LIKE ?", ["%{$val}%"]);
            //                 }
            //             });
            //     } else{
            //          // Relationship-based filter
            //         $relation = $filterConfig['relation'];
            //         $column   = $filterConfig['column'];
            //         $products = $products->whereHas($relation, function ($query) use ($filterValues, $column) {
            //             $query->whereIn($column, $filterValues);
            //         });
            //     }
            // }
            // $limit = $request->has('limit') ? $request->limit : null;
            // if (empty($limit)) {
            //     $products = $products->with(['productStocks'])->get();
            // } else {
            //     $products = $products->with(['productStocks'])->limit($limit)->get();
            // }

            // // If no active products are found, return not found response
            // if ($products->isEmpty()) {
            //     return response()->json([
            //         'data' => [],
            //         'status_code' => Response::HTTP_NOT_FOUND,
            //         'message' => $this->get_message('not_found'),
            //     ], Response::HTTP_NOT_FOUND);
            // }

            // $response = [];

            // foreach ($products as $product) {
            //     // Fetch the translation for the requested language or fallback to English
            //     // Prepare the product stock details
            //     $stocks = $product->productStocks->map(function ($stock) {
            //         return [
            //             'variant' => $stock->variant,
            //             'sku' => $stock->sku,
            //             'price' => $stock->price,
            //             'quantity' => $stock->qty,
            //             'image' => !is_null($stock->image) && !empty($stock->image) && file_exists(public_path($stock->image)) ? getConfigValue('APP_ASSET_PATH') . $stock->image : getConfigValue('APP_ASSET_PATH') . 'assets/images/no-image.png',
            //             'is_stock' => $stock->qty > 0 ? 'In Stock' : 'Out of Stock',
            //         ];
            //     });

            //     // If attributes column is null or an empty array, get only one product stock row
            //     if (is_null($product->attributes) || $product->attributes === '[]') {
            //         $stocks = $stocks->take(1);
            //     }

            //     $firstCollection = $product->collections->first(); // Include stock details
            //     $firstCollection = $firstCollection ? [
            //         'uuid' => $firstCollection->uuid,
            //         'name' => $firstCollection->name,
            //         'slug' => $firstCollection->slug,
            //         'image' => file_exists(public_path(trim($firstCollection->image))) ?  $assetPath . trim($firstCollection->image)  :  $assetPath . 'assets/images/no-image.png'
            //     ] : null;
            //     // Prepare the total stock quantity
            //     $total_quantity = $product->productStocks->sum('qty');

            //     // Prepare the response structure
            //     $response[] = [
            //         'uuid' => $product->uuid,
            //         'name' => $product->name,
            //         'auth_id' => $product->auth_id,
            //         'category_name' => $product->category->name ?? null,
            //         'category_slug' => $product->category->slug ?? null,
            //         'brand_name' => $product->brand->brand ?? null,
            //         'brand_slug' => $product->brand->slug ?? null,
            //         'thumbnail_img' => (is_null($product->thumbnail_img) || empty($product->thumbnail_img) || !file_exists(public_path($product->thumbnail_img))) ? $assetPath . 'assets/images/no-image.png' : $assetPath . $product->thumbnail_img,
            //         'images' => $product->images
            //             ? implode(',', array_map(function ($image) use ($assetPath) {
            //                 return file_exists(public_path(trim($image))) ?  $assetPath . trim($image)  :  $assetPath . 'assets/images/no-image.png';
            //             }, explode(',', $product->images)))
            //             : null,
            //         'tags' => $product->tags,
            //         'description' => $product->description,
            //         'short_description' => $product->short_description,
            //         'unit_price' => $product->unit_price,
            //         'compare_price' => $product->compare_price,
            //         'todays_deal' => $product->todays_deal,
            //         'published' => $product->published,
            //         'approved' => $product->approved,
            //         'stock_visibility_state' => $product->stock_visibility_state,
            //         'cash_on_delivery' => $product->cash_on_delivery,
            //         'featured' => $product->featured,
            //         'current_stock' => $product->current_stock,
            //         'unit' => $product->unit,
            //         'weight' => $product->weight,
            //         'min_qty' => $product->min_qty,
            //         'meta_title' => $product->meta_title,
            //         'meta_description' => $product->meta_description,
            //         'meta_img' => $product->meta_img,
            //         'pdf' => $product->pdf,
            //         'slug' => $product->slug,
            //         'is_stock' => $total_quantity > 0 ? 'In Stock' : 'Out of Stock',
            //         'sort' => $product->sort,
            //         'created_at' => $product->created_at,
            //         'updated_at' => $product->updated_at,
            //         'language_code' => $translation->language_code ?? null,
            //         'language_name' => $translation->language_name ?? null,
            //         'dir' => $translation->dir ?? null,
            //         'translate_name' => $translation->translate_name ?? $product->name,
            //         'lang' => $translation->language_code ?? 'en',
            //         'stocks' => $stocks, // Include stock details
            //         'type'=> $product->type,
            //         'firstCollection' => $firstCollection, // Include stock details
            //     ];
            // }

            // Return the structured response
            return response()->json([
                'status_code' => Response::HTTP_OK,
                'data' => $response,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            // Handle any exception that occurs during the process
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    public function get_featured_product($lang = "")
    {
        try {
            // Check if the requested language exists in the languages table
            $language_exists = Language::where('app_language_code', $lang)->exists();

            // If the language doesn't exist, default to English ('en')
            if (!$language_exists) {
                $lang = 'en';
            }

            // Get all active products with status = 1, including their stocks and brand
            $products = Product::where('status', '1')
                ->where('featured', '1')
                ->with(['brand', 'productStocks'])
                ->get();

            // If no active products are found, return not found response
            if ($products->isEmpty()) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            $response = [];
            foreach ($products as $product) {
                // Fetch the translation for the requested language or fallback to English
                $translation = ProductTranslation::where('product_id', $product->id)
                    ->where('product_translations.status', '1')
                    ->join('languages', 'product_translations.language_id', '=', 'languages.id')
                    ->where('languages.app_language_code', $lang)
                    ->select(
                        'languages.app_language_code as language_code',
                        'languages.name as language_name',
                        'languages.flag as flag',
                        'languages.rtl as dir',
                        'product_translations.name as translate_name',
                        'product_translations.short_description as translate_short_description',
                        'product_translations.description as translate_description',
                        'product_translations.unit as translate_unit'
                    )
                    ->first();

                if (!$translation) {
                    $translation = ProductTranslation::where('product_id', $product->id)
                        ->where('product_translations.status', '1')
                        ->join('languages', 'product_translations.language_id', '=', 'languages.id')
                        ->where('languages.app_language_code', 'en')
                        ->select(
                            'languages.app_language_code as language_code',
                            'languages.name as language_name',
                            'languages.flag as flag',
                            'languages.rtl as dir',
                            'product_translations.name as translate_name',
                            'product_translations.short_description as translate_short_description',
                            'product_translations.description as translate_description',
                            'product_translations.unit as translate_unit'
                        )
                        ->first();
                }

                // Prepare the product stock details
                $stocks = $product->productStocks->map(function ($stock) {
                    return [
                        'variant' => $stock->variant,
                        'sku' => $stock->sku,
                        'price' => $stock->price,
                        'quantity' => $stock->qty,
                        'image' => $stock->image ? getConfigValue('APP_ASSET_PATH') . $stock->image : getConfigValue('APP_ASSET_PATH') . 'assets/images/no-image.png',
                        'is_stock' => $stock->qty > 0 ? 'In Stock' : 'Out of Stock',
                    ];
                });

                // Prepare the total stock quantity
                $total_quantity = $product->productStocks->sum('qty');

                // Prepare the response structure
                $response[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'auth_id' => $product->auth_id,
                    'category_name' => $product->category->name ?? null,
                    'category_slug' => $product->category->slug ?? null,
                    'brand_name' => $product->brand->brand ?? null,
                    'brand_slug' => $product->brand->slug ?? null,
                    'thumbnail_img' => $product->thumbnail_img ? getConfigValue('APP_ASSET_PATH') . $product->thumbnail_img : getConfigValue('APP_ASSET_PATH') . 'assets/images/no-image.png',
                    'images' => $product->images
                        ? implode(',', array_map(function ($image) {
                            return getConfigValue('APP_ASSET_PATH') . trim($image);
                        }, explode(',', $product->images)))
                        : null,
                    'tags' => $product->tags,
                    'description' => $product->description,
                    'short_description' => $product->short_description,
                    'unit_price' => $product->unit_price,
                    'todays_deal' => $product->todays_deal,
                    'published' => $product->published,
                    'approved' => $product->approved,
                    'stock_visibility_state' => $product->stock_visibility_state,
                    'cash_on_delivery' => $product->cash_on_delivery,
                    'featured' => $product->featured,
                    'current_stock' => $product->current_stock,
                    'unit' => $product->unit,
                    'weight' => $product->weight,
                    'min_qty' => $product->min_qty,
                    'meta_title' => $product->meta_title,
                    'meta_description' => $product->meta_description,
                    'meta_img' => $product->meta_img,
                    'pdf' => $product->pdf,
                    'slug' => $product->slug,
                    'is_stock' => $total_quantity > 0 ? 'In Stock' : 'Out of Stock',
                    'sort' => $product->sort,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                    'language_code' => $translation->language_code ?? null,
                    'language_name' => $translation->language_name ?? null,
                    'dir' => $translation->dir ?? null,
                    'translate_name' => $translation->translate_name ?? $product->name,
                    'lang' => $translation->language_code ?? 'en',
                    'stocks' => $stocks, // Include stock details
                ];
            }

            // Return the structured response
            return response()->json([
                'status_code' => Response::HTTP_OK,
                'data' => $response,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            // Handle any exception that occurs during the process
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    public function get_product_by_slug($slug = "", $lang = "")
    {
        try {
            $assetPath = getConfigValue('APP_ASSET_PATH');
            // Check if the requested language exists in the languages table
            $language_exists = Language::where('app_language_code', $lang)->exists();

            // If the language doesn't exist, default to English ('en')
            if (!$language_exists) {
                $lang = 'en';
            }

            // Fetch the product by slug
            $product = Product::where('slug', $slug)
                ->where('status', '1')
                ->with(['brand', 'category', 'collections', 'productStocks', 'discounts', 'vat'])
                ->first();

            // If no product is found, return not found response
            if (!$product) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            // Try to get the translation for the requested language
            $translation = ProductTranslation::where('product_id', $product->id)
                ->where('product_translations.status', '1')
                ->join('languages', 'product_translations.language_id', '=', 'languages.id')
                ->where('languages.app_language_code', $lang)
                ->select(
                    'languages.app_language_code as language_code',
                    'languages.name as language_name',
                    'languages.flag as flag',
                    'languages.rtl as dir',
                    'product_translations.name as translate_name',
                    'product_translations.short_description as translate_short_description',
                    'product_translations.description as translate_description',
                    'product_translations.unit as translate_unit'
                )
                ->first();

            // If no translation is found, fall back to English
            if (!$translation) {
                $translation = ProductTranslation::where('product_id', $product->id)
                    ->where('product_translations.status', '1')
                    ->join('languages', 'product_translations.language_id', '=', 'languages.id')
                    ->where('languages.app_language_code', 'en')
                    ->select(
                        'languages.app_language_code as language_code',
                        'languages.name as language_name',
                        'languages.flag as flag',
                        'languages.rtl as dir',
                        'product_translations.name as translate_name',
                        'product_translations.short_description as translate_short_description',
                        'product_translations.description as translate_description',
                        'product_translations.unit as translate_unit'
                    )
                    ->first();
            }

            // Prepare the product stock details
            $stocks = $product->productStocks() // Use the relationship method, not the property
                ->whereHas('warehouse', function ($query) {
                    $query->where('is_default', 1);
                })->with('warehouse') // Optional: eager load warehouse if needed
                ->get()->map(function ($stock) {
                    return [
                        'attribute_uuid' => $stock->uuid,
                        'variant' => $stock->variant,
                        'sku' => $stock->sku,
                        'price' => $stock->price,
                        'quantity' => $stock->qty,
                        'image' => $stock->image ?  $stock->image : 'assets/images/no-image.png',
                        'is_stock' => $stock->qty > 0 ? 'In Stock' : 'Out of Stock',
                    ];
                });

            // Prepare the total stock quantity
            $total_quantity = $product->productStocks->sum('qty');

            $firstCollection = $product->collections->first(); // Include stock details
            $firstCollection = $firstCollection ? [
                'uuid' => $firstCollection->uuid,
                'name' => $firstCollection->name,
                'slug' => $firstCollection->slug,
                'image' => file_exists(public_path(trim($firstCollection->image))) ?  $assetPath . trim($firstCollection->image)  :  $assetPath . 'assets/images/no-image.png'
            ] : null;

            // Calculate discount amount
            $core_discount_value = 0;
            $discount_type = null;
            
            if ($product->discounts && $product->discounts->isNotEmpty()) {
                $firstDiscount = $product->discounts->first();
                $discount_type = $firstDiscount->type;
                
                if ($discount_type === 'percentage') {
                    $core_discount_value = $product->unit_price * ($firstDiscount->value / 100);
                } elseif ($discount_type === 'amount') {
                    $core_discount_value = $firstDiscount->value;
                }
            }

            // Calculate prices
            $priceAfterDiscount = $product->unit_price - $core_discount_value;
            $discountedPrice = max(0, $priceAfterDiscount); // Ensure price doesn't go negative

            // Calculate VAT
            $productVatAmount = 0;
            $vatWithoutDiscount = 0;
            
            if ($product->vat) {
                $productVatAmount = $product->vat->rate * $priceAfterDiscount / 100;
                $vatWithoutDiscount = $product->vat->rate * $product->unit_price / 100;
            }

            // Prepare the response structure
            $response = [
                'id' => $product->id,
                'uuid' => $product->uuid,
                'name' => $product->name,
                'auth_id' => $product->auth_id,
                'category_name' => $product->category ? $product->category->name : '',
                'firstCollection' => $firstCollection,
                'category_slug' => $product->category ? $product->category->slug : '',
                'brand_name' => $product->brand ? $product->brand->brand : '',
                'brand_slug' => $product->brand ? $product->brand->slug : '',
                'thumbnail_img' => (file_exists(public_path($product->thumbnail_img)) && !empty($product->thumbnail_img)) ? getConfigValue('APP_ASSET_PATH') . $product->thumbnail_img : getConfigValue('APP_ASSET_PATH') . 'assets/images/no-image.png',
                'images' => $product->images
                    ? implode(',', array_map(function ($image) {
                        return getConfigValue('APP_ASSET_PATH') . trim($image);
                    }, explode(',', $product->images)))
                    : null,
                'tags' => $product->tags,
                'description' => $product->description ?? '',
                'short_description' => $product->short_description ?? '',
                'discount_price' => $discountedPrice,
                'discount_amount' => $core_discount_value,
                'discount_type' => $discount_type,
                'unit_price' => $product->unit_price ?? '',
                'todays_deal' => $product->todays_deal ?? '',
                'published' => $product->published ?? '',
                'approved' => $product->approved ?? '',
                'stock_visibility_state' => $product->stock_visibility_state,
                'cash_on_delivery' => $product->cash_on_delivery,
                'featured' => $product->featured,
                'current_stock' => $product->current_stock,
                'unit' => $product->unit,
                'weight' => $product->weight,
                'min_qty' => $product->min_qty,
                'meta_title' => $product->meta_title,
                'meta_description' => $product->meta_description,
                'meta_img' => $product->meta_img,
                'pdf' => $product->pdf,
                'slug' => $product->slug,
                'is_stock' => $total_quantity > 0 ? 'In Stock' : 'Out of Stock',
                'choice_options' => $product->choice_options,
                'sort' => $product->sort,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
                'language_code' => $translation->language_code ?? null,
                'language_name' => $translation->language_name ?? null,
                'dir' => $translation->dir ?? null,
                'translate_name' => $translation->translate_name ?? $product->name,
                'lang' => $translation->language_code ?? 'en',
                'stocks' => $stocks, // Include stock details
                'vat' => $product->vat,
                'vat_amount' => $productVatAmount,
                'price' => number_format($priceAfterDiscount + $productVatAmount, 2, '.', ''), // Price with discount and VAT
                'price_with_vat' => number_format($product->unit_price + $vatWithoutDiscount, 2, '.', '') // Original price with VAT
            ];

            // Return the structured response
            return response()->json([
                'status_code' => Response::HTTP_OK,
                'data' => $response,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            // Handle any exception that occurs during the process
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function get_product_by_slug_all($slug, $id = null) {}

    public function productVaraition(Request $request)
    {
        // Fetch ProductStock with default warehouse
        $product = Product::where('uuid', $request->product_uuid)->first();
        // Check if product exists
        if (!$product) {
            return response()->json(['status' => 404, 'message' => 'Product Not Found.'], 404);
        }
        if (empty($request->attribute_id)) {
            $productStock = ProductStock::whereHas('warehouse', function ($query) {
                $query->where('is_default', 1);
            })
                ->where('product_id', $product->uuid)
                ->first();
        } else {
            $productStock = ProductStock::whereHas('warehouse', function ($query) {
                $query->where('is_default', 1);
            })
                ->where('product_id', $product->uuid)
                ->where('variant', $request->attribute_id)
                ->first();
        }

        // Check if product stock exists
        if (!$productStock) {
            return response()->json(['status' => 404, 'message' => 'Product Not Found.'], 404);
        }

        // Get the base price
        $price = $productStock->price;

        // Handle quantity if provided and non-zero
        if (isset($request->quantity) && $request->quantity != 0) {
            if ($productStock->qty >= $request->quantity) {
                $productStock->price = $price * $request->quantity;
            } else {
                return response()->json(['status' => 400, 'message' => 'Out of stock.', 'data' => $productStock], 400);
            }
        }
        $productStock->price = number_format($productStock->price, 2, '.', ',');
        $productStock->image = $productStock->image ? $productStock->image : 'assets/images/no-image.png';
        // Return success response
        return response()->json(['status' => 200, 'data' => $productStock], 200);
    }

    public function productSearch(Request $request)
    {
        $search = $request->search;
        $products = Product::where('status', '1');
        if ($search) {
            $products = $products->where('name', 'like', '%' . $search . '%');
        }
        $products = $products->with(['brand', 'productStocks', 'categories'])->get();
        return response()->json(['status' => 200, 'data' => $products], 200);
    }

    public function get_columns()
    {
        $columns = ['uuid', 'name', 'thumbnail_img', 'images', 'unit_price', 'slug', 'firstCollection.name', 'firstCollection.slug', 'firstCollection.image'];
        $columns['collection'] = [
            'name',
            'slug',
            'image',
        ];
        $columns['brand'] = [
            'brand',
            'slug',
            'logo',
        ];
        $columns['category'] = [
            'name',
            'slug',
            'banner',
        ];
        $columns['children_categories'] = [
            'name',
            'slug',
            'banner',
        ];
        $columns['tags'] = [
            'tag',
        ];
        $columns['pagination'] = [
            'current_page',
            'last_page',
            'per_page',
            'total',
            'prev_page_url',
            'next_page_url'
        ];
        return response()->json(['status' => 200, 'data' => $columns], 200);
    }

    public function get_active_collections(Request $request)
    {
        try {
            $limit = $request->has('limit') ? $request->limit : 10; // Default limit to 10 if not provided
            $collections = Collection::where('status', '1')
                ->orderBy('name', 'ASC')
                ->when($request->has('limit'), function ($query) use ($limit) {
                    return $query->limit($limit);
                })
                ->get()
                ->map(function ($collection) {
                    if (empty($collection->image) || !file_exists(public_path($collection->image))) {
                        $collection->image = getConfigValue('APP_ASSET_PATH') . 'assets/images/no-image.png';
                    } else {
                        $collection->image = getConfigValue('APP_ASSET_THEME_PATH') . $collection->image;
                    }
                    return $collection;
                });

            return response()->json([
                'status_code' => 200,
                'data' => $collections
            ], 200);
        } catch (\Exception $e) {
            // Handle general exceptions
            Log::error('get_active_collections', $e->getMessage());
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
    }


    // All Filter Old

    // public function get_active_filter(Request $request)
    // {
    //     try {
    //         $limit = $request->has('limit') ? $request->limit : 10; // Default limit to 10 if not provided
    //         $collections = Collection::where('status', '1')
    //             ->whereHas('products', function ($query) {
    //                 $query->where('status', '1');
    //             })
    //             ->orderBy('name', 'ASC')
    //             ->when($request->has('limit'), function ($query) use ($limit) {
    //                 return $query->limit($limit);
    //             })
    //             ->get()
    //             ->map(function ($collection) {
    //                 if (empty($collection->image) || !file_exists(public_path($collection->image))) {
    //                     $collection->image = getConfigValue('APP_ASSET_PATH') . 'assets/images/no-image.png';
    //                 } else {
    //                     $collection->image = getConfigValue('APP_ASSET_PATH') . $collection->image;
    //                 }
    //                 return $collection;
    //             });
    //         $brands = Brand::where('status', '1')
    //             ->get()->map(function ($brand) {
    //                 if (empty($brand->logo) || !file_exists(public_path($brand->logo))) {
    //                     $brand->logo = getConfigValue('APP_ASSET_PATH') . 'assets/images/no-image.png';
    //                 } else {
    //                     $brand->logo = getConfigValue('APP_ASSET_PATH') . $brand->logo;
    //                 }
    //                 return $brand;
    //             });
    //         $categories = Category::where('status', '1')
    //             ->whereHas('products', function ($query) {
    //                 $query->where('status', '1');
    //             })
    //             ->get()
    //             ->map(function ($category) {
    //                 if (empty($category->banner) || !file_exists(public_path($category->banner))) {
    //                     $category->banner = getConfigValue('APP_ASSET_PATH') . 'assets/images/no-image.png';
    //                 } else {
    //                     $category->banner = getConfigValue('APP_ASSET_PATH') . $category->banner;
    //                 }
    //                 return $category;
    //             });
    //         $tags = collect(Product::where('status', '1')->select('tags')->get())->map(function ($item) {
    //             return explode(',', $item->tags);
    //         })->flatten()->filter(function ($item) {
    //             return !empty($item);
    //         })->unique()->values();

    //         $finalTags = [];
    //         foreach ($tags as $key => $value) {
    //             $finalTags[] = [
    //                 'tag' => $value
    //             ];
    //         }
    //         $data = [
    //             'collection' => $collections,
    //             'brand' => $brands,
    //             'category' => $categories,
    //             'tags' => $finalTags
    //         ];
    //         return response()->json([
    //             'status_code' => 200,
    //             'data' => $data
    //         ], 200);
    //     } catch (\Exception $e) {
    //         // Handle general exceptions
    //         Log::error('get_active_collections' . $e->getMessage());
    //         return response()->json([
    //             'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
    //             'message' => $this->get_message('server_error'),
    //         ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
    //     }
    // }

    // Nks Filter
    public function get_active_filter(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            // Cache the data for 5 minutes (300 seconds)
            // Cache::forget("filter_limit");
            // dd($request->all());
            $firstCollection = Collection::where('status', '1')->where('slug', $request->firstCollection)->first();
            $cacheKey = "filter_limit_{$request->firstCollection}_limit_{$limit}";
            $data = Cache::remember($cacheKey, now()->addDays(2), function () use ($limit, $firstCollection) {
                return [
                    'collections' => Collection::select('uuid','name','description','featured','slug','top','image','meta_title','meta_description','og_title','og_description','og_image','x_title','x_description','x_image','status')->where('status', '1')
                    ->whereHas('products', fn($q) => $q->where('status', '1'))
                    ->orderBy('name')
                    ->limit($limit)
                    ->get(),
                    'firstCollection' => $firstCollection,
                    'brands' => Brand::select('uuid','brand','slug','logo','order_level','description','meta_title','meta_description','og_title','og_description','og_image','x_title','x_description','x_image','status')->where('status', '1')->get(),
                    'categories' => Category::with(['childrenCategories' => function($q) {
                        $q->whereHas('products', fn($p) => $p->where('status', 1)) // sirf un children ko lao jinke paas products hain
                        ->orderBy('order_level', 'asc');
                    }])
                    ->where('status', 1)
                    ->where(function($q) {
                        $q->whereHas('products', fn($p) => $p->where('status', 1)) // agar parent ke paas products hon
                        ->orWhereHas('childrenCategories.products', fn($p) => $p->where('status', 1)); // ya child ke paas products hon
                    })->orderBy('order_level','asc')
                    ->get(),
                    'tags' => Product::select('uuid','tags')->where('status', '1')
                    ->pluck('tags')
                    ->flatMap(fn($t) => array_map('trim', explode(',', $t)))
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->map(fn($tag) => ['tag' => $tag])
                ];
            });
            
            $collections = $data['collections'];
            $brands = $data['brands'];
            $categories = $data['categories'];
            $tags = $data['tags'];
            $firstCollection = $data['firstCollection'];

            return response()->json([
                'status_code' => 200,
                'data' => [
                    'collection' => $collections,
                    'brand' => $brands,
                    'category' => $categories,
                    'firstCollection' => $firstCollection,
                    'tags' => $tags
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('get_active_collections: '.$e->getMessage());
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function get_active_languages(Request $request)
    {
        try {
            $limit = $request->has('limit') ? $request->limit : 10; // Default limit to 10 if not provided
            $languages = Language::where('status', '1')
                ->orderBy('name', 'ASC')
                ->when($request->has('limit'), function ($query) use ($limit) {
                    return $query->limit($limit);
                })
                ->get()
                ->map(function ($language) {
                    if (empty($language->flag) || !file_exists(public_path($language->flag))) {
                        $language->flag = getConfigValue('APP_ASSET_PATH') . 'assets/images/no-image.png';
                    } else {
                        $language->flag = getConfigValue('APP_ASSET_THEME_PATH') . $language->flag;
                    }
                    if ($language->is_default==1) {
                        $language->link = getConfigValue('WEB_URL');
                    }else{
                        $language->link = getConfigValue('WEB_URL') .'/'. $language->app_language_code;
                    }
                    return $language;
                });

            return response()->json([
                'status_code' => 200,
                'data' => $languages
            ], 200);
        } catch (\Exception $e) {
            // Handle general exceptions
            Log::error('get_active_languages', $e->getMessage());
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
    }
}
