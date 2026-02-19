<?php

namespace App\Http\Controllers\API\Ecommerce;

use App\Http\Controllers\Controller;
use App\Ecommerce\Models\Customer;
use App\Ecommerce\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class WishlistController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $authId = Auth::user()->uuid;

            $wishlist = Wishlist::with(['product' => function ($query) {
                $query->select('uuid', 'name', 'slug', 'unit_price', 'images', 'thumbnail_img')
                    ->with(['categories' => function ($categoryQuery) {
                        $categoryQuery->select('categories.uuid', 'categories.name');
                    }]);
            }])->where('auth_id', $authId)->get();

            $baseUrl = getConfigValue('APP_ASSET_PATH');

            // Transform image URLs
            $wishlist->transform(function ($item) use ($baseUrl) {
                if ($item->product && $item->product->images) {
                    $images = explode(',', $item->product->images);
                    $fullUrls = array_map(function ($img) use ($baseUrl) {
                        $img = ltrim($img); // Remove leading spaces if any
                        return strpos($img, 'http') === 0 ? $img : $baseUrl . ltrim($img, '/');
                    }, $images);

                    $item->product->images = $fullUrls;
                }
                return $item;
            });

            return response()->json(['data' => $wishlist], Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error("Error fetching wishlist: " . $e->getMessage());
            return response()->json(['error' => 'Unable to fetch wishlist'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function wishlistCount(Request $request){
        try {
            // Retrieve wishlist data
            $authId = $request->header('authid'); // Get the authenticated user's auth_id();
            $wishlist = Wishlist::where('auth_id', $authId)->orderByDesc('id')->get();
            return response()->json(['data' => $wishlist, 'wishlist_count' => count($wishlist)], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error("Error fetching wishlist: " . $e->getMessage());
            return response()->json(['error' => 'Unable to fetch wishlist'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
       
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $authId = Auth::user()->uuid;

            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:products,uuid',
            ], [
                'product_id.required' => 'Product ID is required.',
                'product_id.exists' => 'The selected product does not exist.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_code' => Response::HTTP_BAD_REQUEST,
                    'errors' => $validator->errors()
                ], Response::HTTP_BAD_REQUEST);
            }

            $productId = $request->input('product_id');
            $alreadyExists = Wishlist::where('auth_id', $authId)
                ->where('product_id', $productId)
                ->first();

            if ($alreadyExists) {
                // Remove from wishlist
                $alreadyExists->delete();
                return response()->json([
                    'status_code' => Response::HTTP_OK,
                    'message' => 'Item removed from wishlist successfully.',
                    'wishlist_count' => Wishlist::where('auth_id', $authId)->count(),
                ]);
            }

            // Add to wishlist
            $wishlist = Wishlist::create([
                'auth_id' => $authId,
                'product_id' => $productId,
            ]);

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Item added to wishlist successfully.',
                'wishlist_count' => Wishlist::where('auth_id', $authId)->count(),
            ]);
        } catch (\Exception $e) {
            Log::error("Error storing wishlist: " . $e->getMessage());
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'error' => 'Unable to store wishlist',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
       
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            // Delete wishlist item
            $authId = Auth::user()->uuid;
            $wishlistItem = Wishlist::where('auth_id', $authId)->where('uuid', $uuid)->first();
            if (!$wishlistItem) {
                return response()->json(['message' => 'Wishlist item not found'], Response::HTTP_NOT_FOUND);
            }
            $wishlistItem->delete();
            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Wishlist item deleted'
            ], 
            Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error("Error deleting wishlist item: " . $e->getMessage());
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Unable to delete wishlist item'], 
            Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function get_columns()
    {
        $columns = (new Wishlist())->getConnection()->getSchemaBuilder()->getColumnListing((new Wishlist())->getTable());
        array_push($columns, 'product.name', 'product.slug', 'product.unit_price', 'product.thumbnail_img', 'product.uuid', 'product.categories[0].name');
                return response()->json([
            'status_code' => Response::HTTP_OK,
            'data' => $columns,
        ], Response::HTTP_OK);
    }
}

