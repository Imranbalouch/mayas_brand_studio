<?php

namespace App\Http\Controllers\API\Ecommerce;

use Exception;
use App\Models\Ecommerce\Cart;
use App\Models\Ecommerce\Product;
use App\Models\Ecommerce\Discount;
use App\Models\Ecommerce\CouponUsage;
use Illuminate\Support\Str;
use App\Models\Ecommerce\ProductStock;
use App\Traits\MessageTrait;
use App\Utility\CartUtility;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Ecommerce\ProductDiscounts;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
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
    public function index(Request $request)
    {
        try {
            // Step 1: Determine the current auth_id (user or guest)
            $sessionAuthId = Auth::check()
                ? (string) Auth::user()->uuid
                : session('temp_user');
            $headerAuthId = $request->header('authid');
            
            // Step 2: Only return cart if IDs match (security check)
            $cart = Cart::where('auth_id', $headerAuthId)
                ->with('products.vat', 'products.discounts', 'coupon')
                ->orderByDesc('id')
                ->get()
                ->map(function ($item) {
                    // Calculate discount value
                    $core_discount_value = 0;
                    $discount_type = null;
                    
                    if ($item->products && $item->products->discounts && $item->products->discounts->isNotEmpty()) {
                        $firstDiscount = $item->products->discounts->first();
                        $discount_type = $firstDiscount->type;
                        
                        if ($discount_type == 'percentage') {
                            // Calculate percentage discount on unit price
                            $core_discount_value = $item->products->unit_price * ($firstDiscount->value / 100);
                        } else {
                            // For fixed amount discount
                            $core_discount_value = $firstDiscount->value;
                        }
                    }
                    
                    // Calculate price after discount
                    $priceAfterDiscount = $item->products->unit_price - $core_discount_value;
                                        
                    // Calculate VAT amounts
                    $productVatAmount = 0;
                    $vatWithoutDiscount = 0;
                    
                    if ($item->products && $item->products->vat) {
                        // VAT on original price
                        $vatWithoutDiscount = $item->products->vat->rate * $item->products->unit_price / 100;
                    }

                    // Coupon round
                    // $couponDiscount = round($item->coupon_amount);
                    
                    // Calculate prices
                    $item->price_without_vat = number_format($priceAfterDiscount, 2, '.', ''); // Price after discount, before VAT
                    $item->price = number_format($priceAfterDiscount + $productVatAmount, 2, '.', ''); // Final price with discount and VAT
                    $item->price_with_vat = number_format($item->products->unit_price + $vatWithoutDiscount, 2, '.', ''); // Original price with VAT
                    // $item->coupon_amount = $couponDiscount;
                    
                    // Format existing fields
                    $item->product_price = number_format($item->product_price, 2, '.', ',');
                    $item->product_img = $item->product_img 
                        ? $item->product_img 
                        : getConfigValue('APP_ASSET_PATH') . 'assets/images/no-image.png';
                    
                    return $item;
                });

            return response()->json([
                'status_code' => 200,
                'data' => $cart,
                'cart_count' => $cart->count(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('cart List Error: ' . $e->getMessage());
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function get_cart()
    {
        try {
            $cart = Cart::with('product', 'variant', 'customer')->orderByDesc('id')->get();

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'data' => $cart
            ]);
        } catch (\Exception $e) {
            Log::error('cart List Error: ' . $e->getMessage());
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'uuid' => 'required|exists:cart,uuid',
                'change' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $cartItem = Cart::where('uuid', $request->uuid)->first();

            if (Auth::check()) {
                $authId = Auth::user()->uuid;
            } else {
                $authId = session('temp_user');
            }

            if ($cartItem->auth_id !== $authId) {
                return response()->json([
                    'status_code' => Response::HTTP_FORBIDDEN,
                    'message' => 'Unauthorized action',
                ], Response::HTTP_FORBIDDEN);
            }

            $newQuantity = $cartItem->product_qty + $request->change;

            if ($newQuantity < 1) {
                $cartItem->delete();
            } else {
                // Check stock if needed
                $cartItem->update(['product_qty' => $newQuantity]);
            }

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Cart updated successfully',
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function remove(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'uuid' => 'required|exists:cart,uuid',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // if (Auth::check()) {
            //     $authId = Auth::user()->uuid;
            // } else {
            //     $authId = session('temp_user');
            // }
            $authId = $request->authid;
            $cartItem = Cart::where('uuid', $request->uuid)
                ->where('auth_id', $request->authid)
                ->first();

            if (!$cartItem) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Cart item not found',
                ], Response::HTTP_NOT_FOUND);
            }

            // Remove coupon usage if this was the last item with coupon
            // if ($cartItem->coupon_applied) {
            //     $remainingItemsWithCoupon = Cart::where('auth_id', $authId)
            //         ->where('coupon_applied', true)
            //         ->where('uuid', '!=', $request->uuid)
            //         ->count();

            //     if ($remainingItemsWithCoupon === 0) {
            //         $this->remove_coupon($request);
            //     
            
            CouponUsage::where('user_uuid', $authId)->delete();

            // Reset coupon when updating quantity
            Cart::where('auth_id', $authId)
                ->whereNotNull('coupon_uuid')
                ->update([
                    'coupon_applied' => false,
                    'coupon_code' => null,
                    'coupon_amount' => 0,
                    'coupon_percentage' => 0,
                    'coupon_uuid' => null,
                ]);

            // Recalculate all cart items
            $cartItems = Cart::where('auth_id', $authId)->get();
            foreach ($cartItems as $cartItem) {
                CartUtility::calculateOrder($cartItem);
            }

            $cartItem->delete();

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Item removed from cart',
                'cart_count' => Cart::where('auth_id', $authId)->count(),
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function removeAll(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'authid' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $authId = $request->authid;

        // Remove coupon usage records
        $couponsUsed = Cart::where('auth_id', $authId)
            ->where('coupon_applied', true)
            ->pluck('coupon_uuid')
            ->unique()
            ->filter();

        foreach ($couponsUsed as $couponUuid) {
            $coupon = Discount::where('uuid', $couponUuid)->first();
            if ($coupon) {
                CouponUsage::where('user_uuid', $authId)
                    ->where('coupon_uuid', $couponUuid)
                    ->delete();
            }
        }

        $deleted = Cart::where('auth_id', $authId)->delete();

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'All items removed from cart',
            'cart_count' => 0,
        ], Response::HTTP_OK);
    } catch (Exception $e) {
        return response()->json([
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => 'Something went wrong',
            'error' => $e->getMessage()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
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
    public function add_to_cart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_details' => 'nullable|array',
            'product_details.*.product_id' => 'nullable|exists:products,uuid',
            'product_details.*.variant_id' => [
                'nullable',
                'exists:product_stocks,uuid',
                function ($attribute, $value, $fail) use ($request) {
                    $index = explode('.', $attribute)[1];
                    $productId = $request->input("product_details.{$index}.product_id");

                    if ($productId && !ProductStock::where('uuid', $value)
                        ->where('product_id', $productId)
                        ->exists()) {
                        $fail('The selected variant does not belong to the specified product.');
                    }
                },
            ],
            'product_details.*.product_qty' => 'nullable|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            // Step 1: Get or generate auth_id (user or guest)
            $authId = $request->header('authid');

            if ($authId == "null" || !$authId) {
                if (!session('temp_user')) {
                    $authId = (string) Str::uuid();
                    session(['temp_user' => $authId]);
                } else {
                    $authId = session('temp_user');
                }
            } else {
                session(['temp_user' => $authId]);
            }

            $authId = session('temp_user');

            // Reset coupon for all cart items when adding new items
            Cart::where('auth_id', $authId)->update([
                'discount' => 0.00,
                'coupon_code' => '',
                'coupon_applied' => false,
                'coupon_amount' => 0,
                'coupon_percentage' => 0,
            ]);

            // Process product items
            if ($request->has('product_details')) {
                foreach ($request->input('product_details') as $productItem) {
                    $product = Product::where('uuid', $productItem['product_id'])->first();
                    $productStock = ProductStock::where('product_id', $product->uuid)
                        ->when(isset($productItem['variant_id']), function ($query) use ($productItem) {
                            return $query->where('uuid', $productItem['variant_id']);
                        })
                        ->first();

                    $productName = $product->name ?? 'Unnamed Product';
                    $variantName = $productStock->variant ?? '';
                    $productPrice = $productStock->price ?? $product->unit_price ?? 0;

                


                    $qtyRequested = $productItem['product_qty'] ?? 1;

                    if (!$productStock) {
                        return response()->json([
                            'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                            'message' => "Product ({$productName}) is not available.",
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);
                    }

                    $existingCart = Cart::where('auth_id', $authId)
                        ->where('product_id', $product->uuid)
                        ->where('variant_id', $productStock->uuid)
                        ->first();

                    // Calculate total quantity (existing + requested)
                    $totalQtyRequested = $qtyRequested;
                    if ($existingCart) {
                        $totalQtyRequested += $existingCart->product_qty;
                    }

                    // Validate total quantity against stock
                    if ($totalQtyRequested > $productStock->qty) {
                        return response()->json([
                            'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                            'message' => "The quantity you are trying to add exceeds the available stock ({$productStock->qty}).",
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);
                    }

                    // Update or create cart item
                    if ($existingCart) {
                        $existingCart->update([
                            'product_qty' => $totalQtyRequested,
                        ]);
                        CartUtility::save_cart_data($existingCart, $product, $productStock, $totalQtyRequested);
                    } else {
                        $cart = Cart::create([
                            'uuid' => Str::uuid(),
                            'auth_id' => $authId,
                            'item_type' => 'product',
                            'product_id' => $product->uuid,
                            'variant_id' => $productStock->uuid,
                            'product_name' => $productName,
                            'varaint_name' => $variantName, 
                            'product_price' => $productPrice,
                            'product_qty' => $qtyRequested,
                            'product_img' => $product->thumbnail_img ?? null,
                        ]);

                        CartUtility::save_cart_data($cart, $product, $productStock, $qtyRequested);
                    }
                }
            }

            // Recalculate all cart items
            $cartItems = Cart::where('auth_id', $authId)->get();
            foreach ($cartItems as $cartItem) {
                CartUtility::calculateOrder($cartItem);
            }

            $cartTotal = Cart::where('auth_id', $authId)->sum('total_amount');

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Items added to cart successfully.',
                'cart_count' => Cart::where('auth_id', $authId)->count(),
                'cart_total' => number_format($cartTotal, 2),
                'auth_id' => $authId,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Something went wrong while adding to cart.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($uuid)
    {
        try {
            $cart = Cart::where('uuid', $uuid)->first();

            if ($cart) {
                // Include the address in the response
                return response()->json([
                    'status_code' => Response::HTTP_OK,
                    'data' => [
                        'cart' => $cart,
                    ],
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
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update_cart_quantity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cart_id' => 'required',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $authId = $request->header('authid');

            // Find the cart item belonging to the authenticated user
            $cartItem = Cart::where('uuid', $request->cart_id)
                ->where('auth_id', $authId)
                ->first();

            if (!$cartItem) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Cart item not found or does not belong to you.'
                ], Response::HTTP_NOT_FOUND);
            }

            $product = Product::where('uuid', $cartItem->product_id)->first();
            if (!$product) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Product not found.',
                ], Response::HTTP_NOT_FOUND);
            }

            $productStock = ProductStock::where('uuid', $cartItem->variant_id)->first();
            $qtyRequested = $request->quantity;

            if ($productStock && $qtyRequested > $productStock->qty) {
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => "Product [{$product->name}] quantity is out of stock.",
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }


            CouponUsage::where('user_uuid', $authId)->delete();

            // Reset coupon when updating quantity
            Cart::where('auth_id', $authId)
                ->whereNotNull('coupon_uuid')
                ->update([
                    'coupon_applied' => false,
                    'coupon_code' => null,
                    'coupon_amount' => 0,
                    'coupon_percentage' => 0,
                    'coupon_uuid' => null,
                ]);



            // Update quantity and recalculate
            $cartItem->update([
                'product_qty' => $qtyRequested
            ]);

            CartUtility::save_cart_data($cartItem, $product, $productStock, $qtyRequested);

            // Recalculate all cart items
            $cartItems = Cart::where('auth_id', $authId)->get();
            foreach ($cartItems as $cartItem) {
                CartUtility::calculateOrder($cartItem);
            }


            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Cart quantity updated successfully.',
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Something went wrong while updating cart quantity.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($uuid)
    {
        try {
            $cartItem = Cart::where('uuid', $uuid)->first();

            if (!$cartItem) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            $authId = $cartItem->auth_id;

            // If there's a linked cart_cart entry, delete it
            if ($cartItem->cart_id) {
                $linkedCart = Cart::where('uuid', $cartItem->cart_id)->first();
                if ($linkedCart) {
                    $linkedCart->delete();
                }
            }

            // Delete the cart item itself
            $cartItem->delete();

            // Recalculate remaining cart items
            $cartItems = Cart::where('auth_id', $authId)->get();
            foreach ($cartItems as $cartItem) {
                CartUtility::calculateOrder($cartItem);
            }

            $cartTotal = Cart::where('auth_id', $authId)->sum('total_amount');

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Cart item deleted successfully.',
                'cart_count' => Cart::where('auth_id', $authId)->count(),
                'cart_total' => number_format($cartTotal, 2),
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function get_columns()
    {
        $columns = (new Cart())->getConnection()->getSchemaBuilder()->getColumnListing((new Cart())->getTable());
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'data' => $columns,
        ], Response::HTTP_OK);
    }

    public function apply_coupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $authId = $request->header('authid');

            if ($authId == "null" || !$authId) {
                if (!session('temp_user')) {
                    $authId = (string) Str::uuid();
                    session(['temp_user' => $authId]);
                } else {
                    $authId = session('temp_user');
                }
            } else {
                session(['temp_user' => $authId]);
            }

            $authId = session('temp_user');
            $couponCode = $request->coupon_code;

            // Find valid coupon
            $coupon = Discount::where('code', $couponCode)
                ->where('status', 1)
                ->where(function($query) {
                    $query->whereNull('start_date')
                        ->orWhere('start_date', '<=', now());
                })
                ->where(function($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', now());
                })
                ->first();

            if (!$coupon) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Invalid or expired coupon code.',
                ], Response::HTTP_NOT_FOUND);
            }

            // Check if already used
            if ($authId) {
                $alreadyUsed = CouponUsage::where('user_uuid', $authId)
                    ->where('coupon_uuid', $coupon->uuid)
                    ->exists();
                    
                if ($alreadyUsed) {
                    return response()->json([
                        'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                        'message' => 'Coupon already used.',
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            }

            // Get cart items
            $cartItems = Cart::where('auth_id', $authId)->get();
            
            if ($cartItems->isEmpty()) {
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'Cart is empty.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Calculate subtotal from gross_amount
            $subtotal = $cartItems->sum('gross_amount');
            $coupon_discount = 0;

            // Check minimum shopping requirement
            if ($subtotal < $coupon->minimum_shopping) {
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => "Minimum shopping amount of {$coupon->minimum_shopping} is required to use this coupon.",
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Calculate total coupon discount
            if ($coupon->type == 'percentage') {
                $coupon_discount = ($subtotal * $coupon->value) / 100;
                
                // Apply maximum discount limit if exists
                // if ($coupon->maximum_discount_amount && $coupon_discount > $coupon->maximum_discount_amount) {
                //     $coupon_discount = $coupon->maximum_discount_amount;
                // }
            } elseif ($coupon->type == 'amount') {
                $coupon_discount = $coupon->value;
            }

            // Check if discount is applicable
            if ($coupon_discount <= 0) {
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'This coupon is not applicable to your cart.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Distribute discount proportionally across cart items
            foreach ($cartItems as $cartItem) {
                // Calculate this item's proportion of the total
                $itemProportion = $cartItem->gross_amount / $subtotal;
                
                // Calculate discount for this item
                $itemDiscountAmount = $coupon_discount * $itemProportion;
                
                // Update cart item with coupon details
                $cartItem->coupon_applied = true;
                $cartItem->coupon_code = $coupon->code;
                $cartItem->coupon_uuid = $coupon->uuid;
                $cartItem->coupon_amount = $itemDiscountAmount;
                
                // Store percentage only if it's a percentage-based coupon
                if ($coupon->type == 'percentage') {
                    // If max discount was applied, calculate effective percentage
                        $cartItem->coupon_percentage = $coupon->value;
                } else {
                    $cartItem->coupon_percentage = 0;
                }
                
                $cartItem->save();
                
                // Recalculate the cart item with coupon applied
                CartUtility::calculateOrder($cartItem);
            }
            
            // Record coupon usage AFTER successful application
            if ($authId) {
                CouponUsage::create([
                    'uuid' => Str::uuid(),
                    'user_uuid' => $authId,
                    'coupon_uuid' => $coupon->uuid,
                ]);
            }

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Coupon applied successfully!',
                'coupon_details' => [
                    'code' => $coupon->code,
                    'type' => $coupon->type,
                    'value' => $coupon->value,
                    'discount_amount' => $coupon_discount,
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Something went wrong while applying coupon.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Remove the private calculateDiscount method as it's no longer needed

    private function calculateDiscount($price, $coupon)
    {
        if ($coupon->type === 'percentage') {
            return ($price * $coupon->value) / 100;
        } elseif ($coupon->type === 'amount') {
            return min($coupon->value, $price);
        }
        
        return 0;
    }

    public function remove_coupon(Request $request)
    {
        try {
            $authId = $request->header('authid');

            Cart::where('auth_id', $authId)
                ->whereNotNull('coupon_uuid')
                ->update([
                    'coupon_applied' => false,
                    'coupon_code' => null,
                    'coupon_amount' => 0,
                    'coupon_percentage' => 0,
                    'coupon_uuid' => null,
                ]);

            $carts = Cart::where('auth_id', $authId)->get();
            
            foreach ($carts as $key => $cartItem) {
                CartUtility::calculateOrder($cartItem);
            }

            CouponUsage::where('user_uuid', $authId)->delete();

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Coupon removed successfully!',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Something went wrong while removing coupon.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
