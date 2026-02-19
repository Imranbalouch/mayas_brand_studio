<?php

namespace App\Http\Controllers\API\Ecommerce;

use Hash;
use Session;
use Exception;
use Carbon\Carbon;
use App\Models\Ecommerce\Cart;
use App\Models\Ecommerce\City;
use App\Models\Menu;
use App\Models\User;
use DeepCopy\f001\B;
use App\Models\Order;
use App\Models\Country;
use App\Models\Ecommerce\Product;
use App\Models\Customer;
use App\Models\Language;
use App\Models\Ecommerce\Tracking;
use App\Models\CMS\Theme;
use App\Models\Ecommerce\Inventory;
use App\Models\Ecommerce\CustomItem;
use App\Models\Ecommerce\OrderDetail;
use Illuminate\Support\Str;
use App\Models\Ecommerce\OrderComment;
use App\Models\Ecommerce\ProductStock;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use App\Models\Ecommerce\OrderTimeLine;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\CMS\PageTemplate;
use App\Models\Brand_translation;
use App\Models\Ecommerce\InventoryCommited;
use App\Models\Permission_assign;
use App\Models\Ecommerce\InventoryAvailable;
use Illuminate\Support\Facades\DB;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\User_special_permission;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\API\CMS\PaymentController;

class CustomerOrderController extends Controller
{
    use MessageTrait;

    public function index(Request $request)
    {
        try {
            $headerOrderId = $request->header('orderid');
            $orders = Order::with(['orderDetails','customer','channel'])->where('uuid',$headerOrderId)->first();

            if ($orders == null) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Order not found.',
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'data' => $orders,
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function authOrder(Request $request)
    {
        try {
            $user = Auth::user();
            $orders = Order::with(['orderDetails','customer','channel'])->where('customer_id',$user->uuid)->orderBy('created_at', 'desc')->get();

            if ($orders == null) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Order not found.',
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'data' => $orders,
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function authOrderDetail(Request $request)
    {
        try {
            $user = Auth::user();
            $orders = Order::with(['orderDetails','customer','channel', 'tracking'])->where('customer_id',$user->uuid)->where('code',$request->order_code)->first();
            if ($orders) {
                $orders->formatted_created_at = $orders->created_at->format('F j, Y');
                $subtotal = 0;

                $baseUrl = getConfigValue('APP_ASSET_PATH');

                $orders->orderDetails->transform(function ($item) use (&$subtotal, $baseUrl) {
                    $item->product_total = $item->product_price * $item->product_qty;
                    $subtotal += $item->product_total;

                    // Handle image URL transformation (similar to wishlist logic)
                    if (!empty($item->image)) {
                        $images = explode(',', $item->image);
                        $fullUrls = array_map(function ($img) use ($baseUrl) {
                            $img = ltrim($img); // Remove leading whitespace
                            return strpos($img, 'http') === 0 ? $img : $baseUrl . ltrim($img, '/');
                        }, $images);
                        $item->image = $fullUrls;
                    }

                    return $item;
                });

                $orders->subtotal = $subtotal;
            }

            if (!$orders) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Order not found.',
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'data' => $orders,
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function OrderCount(Request $request){
        try {
            // Retrieve order data
            $authId = $request->header('authid'); // Get the authenticated user's auth_id();
            $order = Order::where('customer_id', $authId)->orderByDesc('id')->get();
            return response()->json(['data' => $order, 'order_count' => count($order)], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error("Error fetching order: " . $e->getMessage());
            return response()->json(['error' => 'Unable to fetch order'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    

    public function update_payment_method(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,uuid',
            'payment_method' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            DB::beginTransaction();

            $order = Order::where('uuid', $request->order_id)->first();

            if (!$order) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Order not found'
                ], Response::HTTP_NOT_FOUND);
            }

            // Update payment method
            $order->update([
                'payment_method' => $request->payment_method,
            ]);

            // Add timeline entry
            OrderTimeLine::create([
                'uuid' => Str::uuid(),
                'order_id' => $order->uuid,
                'message' => 'Payment method updated to: ' . $request->payment_method,
                'status' => $order->status ?? null,
            ]);

            DB::commit();

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Payment method updated successfully',
                'order' => $order
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Failed to update payment method',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function OrderExpenditure(Request $request){
        try {
            // Retrieve order data
            $authId = $request->header('authid'); // Get the authenticated user's auth_id();
            $order = Order::where('customer_id', $authId)->orderByDesc('id')->get();
            $orderGrandToTal = $order->sum('grand_total');
            return response()->json(['data' => $order, 'order_grand_total' => $orderGrandToTal], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error("Error fetching order: " . $e->getMessage());
            return response()->json(['error' => 'Unable to fetch order'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function add_order(Request $request)
    {
        DB::beginTransaction();
        
        $rules = [
            'customer_id' => 'nullable',
            'grand_total' => 'nullable|numeric',
            'reserve_item' => 'nullable|date_format:Y-m-d H:i:s',
            'payment_due_later' => 'nullable',
            'shipping_type' => 'nullable|string',
            'shipping_price' => 'nullable|numeric|min:0',
            'discount_code' => 'nullable|string',
            'estimated_tax' => 'nullable|in:0,1',
            'auto_discount' => 'nullable|in:0,1',
            'discount_value' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'channel_id' => 'nullable',
            'fulfilled_status' => 'nullable',
            'payment_method' => 'nullable',
            'billing_first_name' => 'required',
            'billing_last_name' => 'required',
            'billing_email' => 'required|email',
            'billing_phone' => 'required',
            'billing_address' => 'required',
            'billing_cities_id' => 'required',
            'billing_countries_id' => 'required',
        ];

        if ($request->input('shipping_address_check') === 1) {
            $rules = array_merge($rules, [
                'shipping_first_name' => 'required',
                'shipping_last_name' => 'required',
                'shipping_email' => 'required|email',
                'shipping_phone' => 'required',
                'shipping_address' => 'required',
                'shipping_cities_id' => 'required',
                'shipping_countries_id' => 'required',
            ]);
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $authId = $request->header("authid");
            $customer_id = $authId;
            $market_id = "f1635fc1-aaec-4903-8d7e-ec59757ebf4e";
            $location_id = "50725ab5-5eef-45e7-a305-9959337664c1";
            $channel_id = "de784a53-09bc-424b-98f7-ca902a14ed1c";
            
            // Get cart items for this customer
            $cartItems = Cart::where('auth_id', $authId)->with(['product', 'variant', 'coupon'])->get();
            
            if ($cartItems->isEmpty()) {
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'Your cart is empty'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $subtotal = 0;
            // foreach ($cartItems as $cartItem) {
            //     $lineTotal = $cartItem->product_price * $cartItem->product_qty;
            //     $subtotal += $lineTotal;
            // }

            // SHIPPING CALCULATION LOGIC
            $shippingPrice = 0;
            $shippingVatAmount = 0;
            $shippingVatPercent = 0;

            if ($request->shipping_address_check == 1) {
                $selectedShippingCity = City::findByUuid($request->shipping_cities_id);
                
                if ($selectedShippingCity) {
                    $shippingVatPercent = $selectedShippingCity->vat_percent ?? 0;
                    
                    if ($selectedShippingCity->min_price > 0 && $subtotal >= $selectedShippingCity->min_price) {
                        $shippingPrice = 0;
                    } else {
                        $shippingPrice = $selectedShippingCity->price ?? 0;
                    }
                    
                    $shippingVatAmount = ($shippingPrice * $shippingVatPercent) / 100;
                }
            } else {
                $selectedBillingCity = City::findByUuid($request->billing_cities_id);
                
                if ($selectedBillingCity) {
                    $shippingVatPercent = $selectedBillingCity->vat_percent ?? 0;
                    
                    if ($selectedBillingCity->min_price > 0 && $subtotal >= $selectedBillingCity->min_price) {
                        $shippingPrice = 0;
                    } else {
                        $shippingPrice = $selectedBillingCity->price ?? 0;
                    }
                    
                    $shippingVatAmount = ($shippingPrice * $shippingVatPercent) / 100;
                }
            }

            $order = $request->except(['grand_total']);
            $order['uuid'] = Str::uuid();
            $order['customer_id'] = $customer_id;
            $order['market_id'] = $market_id;
            // $order['auth_id'] = Auth::user()->uuid;
            // $order['auth_id']=$customer_id;
            $order['code'] = Order::generateOrderCode();
            $order['delivery_status'] = 'Pending';

            $order['billing_first_name'] = $request->billing_first_name;
            $order['billing_last_name'] = $request->billing_last_name;
            $order['billing_email'] = $request->billing_email;
            $order['billing_phone'] = $request->billing_phone;
            $order['billing_address'] = $request->billing_address;
            $order['billing_address2'] = $request->billing_address2;
            $order['billing_city'] = $request->billing_city;
            $order['billing_cities_id'] = $request->billing_cities_id;
            $order['billing_countries_id'] = $request->billing_countries_id;
            $order['billing_state'] = $request->billing_state;
            $order['billing_country'] = $request->billing_country;

            $order['shipping_first_name'] = $request->shipping_first_name;
            $order['shipping_last_name'] = $request->shipping_last_name;
            $order['shipping_email'] = $request->shipping_email;
            $order['shipping_phone'] = $request->shipping_phone;
            $order['shipping_address'] = $request->shipping_address;
            $order['shipping_address2'] = $request->shipping_address2;
            $order['shipping_city'] = $request->shipping_city;
            $order['shipping_cities_id'] = $request->shipping_cities_id;
            $order['shipping_countries_id'] = $request->shipping_countries_id;
            $order['shipping_state'] = $request->shipping_state;
            $order['shipping_country'] = $request->shipping_country;

            $order['shipping_vat_percent'] = $shippingVatPercent;
            $order['shipping_vat_amount'] = $shippingVatAmount;
            $order['shipping_price'] = $shippingPrice;
            $totalShippingVat = $shippingPrice + $shippingVatAmount;

            if($request->billing_cities_id){
                $billing_city_name =City::where('uuid',$request->billing_cities_id)->value('name');
                $order['billing_city'] = $billing_city_name;
            }

            if($request->billing_countries_id){
                $billing_country_name =Country::where('uuid',$request->billing_countries_id)->value('name');
                $order['billing_country'] = $billing_country_name;
            }

            if($request->shipping_cities_id){
                $shipping_city_name =City::where('uuid',$request->shipping_cities_id)->value('name');
                $order['shipping_city'] = $shipping_city_name;
            }

            if($request->shipping_countries_id){
                $shipping_country_name =Country::where('uuid',$request->shipping_countries_id)->value('name');
                $order['shipping_country'] = $shipping_country_name;
            }
            
            // Initialize grand total
            $grandTotal = 0;
            $totalVat   = 0;
            $totalDiscount = 0;
            $couponUuid = null;
            $totalAmount = 0;
            $totalCouponAmount = 0;

            foreach ($cartItems as $cartItem) {
                $product = $cartItem->product->first();
                $productStock = $cartItem->variant->first();
                
                $productId = $cartItem->product_id;
                $variantId = $cartItem->variant_id;
                $orderQuantity = $cartItem->product_qty;
                $finalProductName = $cartItem->product_name;
                $finalProductVaraintName = $cartItem->varaint_name;
                $finalProductPrice = $cartItem->product_price;
                $finalProductImage = $cartItem->product_img;
                $discountAmount = $cartItem->coupon_amount ?? 0;

                if ($cartItem->coupon_uuid && !$couponUuid) {
                    $couponUuid = $cartItem->coupon_uuid;
                }
                
                // Validate Product and Variant relationship if both exist
                if ($productId && $variantId) {
                    $variantBelongsToProduct = ProductStock::where('uuid', $variantId)
                        ->where('product_id', $productId)
                        ->exists();

                    if (!$variantBelongsToProduct) {
                        DB::rollBack();
                        return response()->json([
                            'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                            'message' => 'The provided variant does not belong to the specified product',
                            'product_id' => $productId,
                            'variant_id' => $variantId
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);
                    }
                }

                // Check available quantity
                $availableQuantity = 0;
                if ($productStock) {
                    $availableQuantity = $productStock->qty;
                } elseif ($product) {
                    $availableQuantity = $product->current_stock;
                }

                // Validate quantity
                if ($orderQuantity > $availableQuantity) {
                    DB::rollBack();
                    return response()->json([
                        'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                        'message' => 'Order quantity exceeds available stock for product: ' . $finalProductName,
                        'available_quantity' => $availableQuantity,
                        'requested_quantity' => $orderQuantity
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }

                // Add to grand total
                // $lineTotal = $finalProductPrice * $orderQuantity;
                // $vatRate = optional($product->vat)->rate ?? 0;
                // $vatAmount = ($lineTotal * $vatRate) / 100;
                // $lineTotalAfterDiscount = $lineTotal - $discountAmount;
                // $grandTotal += $lineTotalAfterDiscount + $vatAmount;
                // $totalVat += $vatAmount;
                // $totalDiscount += $discountAmount;
                $vatAmount = $cartItem->vat_amount;
                $totalVat = $cartItem->sum('vat_amount');
                $totalDiscount = $cartItem->sum('product_discount_amount');


                // Create Order Detail with new calculation fields
                $order_detail = OrderDetail::create([
                    'uuid' => Str::uuid(),
                    'auth_id' => $customer_id,
                    'order_id' => $order['uuid'],
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'custom_item_id' => $cartItem->custom_item_id ?? null,
                    'product_name' => $finalProductName,
                    'variant' => $finalProductVaraintName,
                    'product_price' => $finalProductPrice,
                    'image' => $finalProductImage,
                    'product_qty' => $orderQuantity,
                    'vat' => $vatAmount,
                    'discount_amount' => $discountAmount, 
                    'coupon_uuid' => $cartItem->coupon_uuid,
                    
                    // New calculation fields from reference code
                    'rate' => $cartItem->rate ?? $finalProductPrice,
                    'total_rate_amount' => $cartItem->total_rate_amount ?? 0,
                    'flat_discount' => $cartItem->flat_discount ?? 0,
                    'percentage_discount' => $cartItem->percentage_discount ?? 0,
                    'each_discount' => $cartItem->each_discount ?? 0,
                    'product_discount_amount' => $cartItem->product_discount_amount ?? 0,
                    'gross_amount' => $cartItem->gross_amount ?? 0,
                    'coupon_percentage' => $cartItem->coupon_percentage ?? 0,
                    'coupon_amount' => $cartItem->coupon_amount ?? 0,
                    'net_amount' => $cartItem->net_amount ?? 0,
                    'vat_percentage' => $cartItem->vat_percentage ?? 0,
                    'vat_amount' => $cartItem->vat_amount ?? 0,
                    'total_amount' => $cartItem->total_amount ?? 0,
                    'total_amount' => $cartItem->total_amount ?? 0,
                    'total_amount' => $cartItem->total_amount ?? 0,
                    'total_amount' => $cartItem->total_amount ?? 0,
                ]);

                $totalCouponAmount = $cartItem->sum('coupon_amount');
                $totalAmount +=  $order_detail['total_amount'];
            }
            
            $grandTotal += $totalShippingVat;

            $totalAmount += $totalShippingVat;

            // Set the calculated grand total
            $order['grand_total'] = $totalAmount;
            $order['total_vat'] = $totalVat;
            $order['discount_amount'] = $totalDiscount; 
            $order['coupon_uuid'] = $couponUuid; 
            $order['total_coupon_amount'] = $totalCouponAmount;
            $order['fulfilled_status'] = 0;
            
            $save_order = Order::create($order);

            //if ($request->has('timeline')) {
            OrderTimeLine::create([
                'uuid' => Str::uuid(),
                //'auth_id'=>$customer_id,
                'order_id' => $save_order->uuid,
                'message' => 'Order Created',
                'status' => $save_order->status ?? null,
            ]);

            // Clear the cart after order is created
            Cart::where('auth_id', $authId)->delete();

            // Email sending logic
            $emailSent = false;
            if (env('MAIL_USERNAME') != null && $save_order->customer_id) {
                $customer = Customer::where('uuid', $save_order->customer_id)->first();
                // dd($customer);
                $orderDetails = OrderDetail::where('order_id', $save_order->uuid)->get();

                $activeTheme = Theme::where('status', 1)->first();
                // dd($save_order);
                if ($customer && $customer->email) {
                    $data = [
                        'details' => [
                            'order' => $save_order,
                            'orderDetail' => $orderDetails,
                            'customer' => $customer,
                            'theme' => $activeTheme
                        ]
                    ];
                    // dd($data);

                    try {
                        Mail::send('emailtemplate.front_order_confirmation', $data, function($message) use ($data) {
                            // dd(env('MAIL_USERNAME'));
                            $message->from(env('MAIL_FROM_ADDRESS'));
                            $message->to($data['details']['customer']['email']);
                            $message->subject($data['details']['order']['code']);
                        });
                        $emailSent = true;
                    } catch (\Exception $e) {
                        \Log::error('Failed to send payment confirmation email: ' . $e->getMessage());
                    }
                } else {
                    \Log::warning('Email not sent: Customer or customer email not found. Order UUID: ' . $save_order->uuid);
                }
            } else {
                \Log::warning('Email not sent: MAIL_USERNAME is null or order has no customer_id. Order UUID: ' . $save_order->uuid);
            }

            if (!$emailSent) {
                \Log::info('Email was not sent for order UUID: ' . $save_order->uuid);
            }
            
            DB::commit();

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'order_id' => $save_order->uuid,
                'order_amount' => $save_order->grand_total,
                'message' => $this->get_message('add'),
            ], Response::HTTP_OK);
        } catch (QueryException $e) {
            DB::rollBack();
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function downloadOrderPdf($orderUuid, Request $request)
    {
        try {
            $authId = $request->header("authid");
            $customer_id = $authId;
            
            // Get order with relationships
            $order = Order::with(['orderDetails', 'customer', 'channel', 'tracking'])
                ->where('customer_id', $customer_id)
                ->where('uuid', $orderUuid)
                ->first();

            if (!$order) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Order not found.',
                ], Response::HTTP_NOT_FOUND);
            }

            // Format order data
            $order->formatted_created_at = $order->created_at->format('F j, Y');
            
            // Calculate subtotal and prepare order details with base64 images
            $subtotal = 0;

            $orderDetails = $order->orderDetails->map(function ($item) use (&$subtotal) {
                $item->product_total = $item->product_price * $item->product_qty;
                $subtotal += $item->product_total;
                
                // Convert product image to base64
                if (!empty($item->image)) {
                    $imageUrl = $item->image;

                    // Handle absolute URLs directly (e.g., https://yourdomain.com/uploads/image.jpg)
                    if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                        $item->image_base64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($imageUrl));
                    } else {
                        // Handle relative paths
                        $imagePath = public_path($imageUrl);
                        if (file_exists($imagePath)) {
                            $imageData = base64_encode(file_get_contents($imagePath));
                            $imageType = pathinfo($imagePath, PATHINFO_EXTENSION);
                            $item->image_base64 = 'data:image/' . $imageType . ';base64,' . $imageData;
                        } else {
                            $item->image_base64 = null;
                        }
                    }
                } else {
                    $item->image_base64 = null;
                }
                
                return $item;
            });

            // Get active theme and convert logo to base64
            $activeTheme = Theme::where('status', 1)->first();

            if ($activeTheme && !empty($activeTheme->theme_logo)) {
                $logoUrl = getConfigValue('APP_ASSET_PATH') . $activeTheme->theme_logo;
                
                // Debug: Log the logo URL
                Log::info('Logo URL: ' . $logoUrl);

                if (filter_var($logoUrl, FILTER_VALIDATE_URL)) {
                    // Absolute URL (e.g. https://domain.com/storage/logo.png)
                    try {
                        $logoContent = file_get_contents($logoUrl);
                        if ($logoContent !== false) {
                            $logoData = base64_encode($logoContent);
                            $logoType = pathinfo(parse_url($logoUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
                            $activeTheme->logo_base64 = 'data:image/' . $logoType . ';base64,' . $logoData;
                        } else {
                            Log::error('Failed to fetch logo from URL: ' . $logoUrl);
                            $activeTheme->logo_base64 = null;
                        }
                    } catch (Exception $e) {
                        Log::error('Logo fetch error: ' . $e->getMessage());
                        $activeTheme->logo_base64 = null;
                    }
                } else {
                    // Relative local path - try different approaches
                    $logoPath = public_path($logoUrl);
                    
                    // Debug: Log the full path
                    Log::info('Logo Path: ' . $logoPath);
                    Log::info('File exists: ' . (file_exists($logoPath) ? 'yes' : 'no'));
                    
                    if (file_exists($logoPath)) {
                        $logoData = base64_encode(file_get_contents($logoPath));
                        $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
                        $activeTheme->logo_base64 = 'data:image/' . $logoType . ';base64,' . $logoData;
                    } else {
                        // Try without APP_ASSET_PATH
                        $alternatePath = public_path($activeTheme->theme_logo);
                        Log::info('Alternate Path: ' . $alternatePath);
                        
                        if (file_exists($alternatePath)) {
                            $logoData = base64_encode(file_get_contents($alternatePath));
                            $logoType = pathinfo($alternatePath, PATHINFO_EXTENSION);
                            $activeTheme->logo_base64 = 'data:image/' . $logoType . ';base64,' . $logoData;
                        } else {
                            Log::error('Logo file not found at any path');
                            $activeTheme->logo_base64 = null;
                        }
                    }
                }
            } else {
                // Set to null if no theme or logo
                if ($activeTheme) {
                    $activeTheme->logo_base64 = null;
                }
            }


            // Generate QR code
            $qrCodeData = QrCode::format('svg')
                ->size(150)
                ->generate($order->code);

            $qrCodeBase64 = base64_encode($qrCodeData);

            // Prepare data for PDF
            $data = [
                'details' => [
                    'order' => $order,
                    'orderDetail' => $orderDetails,
                    'customer' => $order->customer,
                    'theme' => $activeTheme
                ],
                'subtotal' => $subtotal,
                'qrCode' => $qrCodeBase64,
            ];

            // Generate PDF
            $pdf = Pdf::loadView('pdf.order-invoice', $data);
            
            // Set PDF options
            $pdf->setPaper('a4', 'portrait');
            $pdf->setOption('dpi', 150);
            $pdf->setOption('defaultFont', 'Arial');
            $pdf->setOption('isRemoteEnabled', true); // Enable remote content if needed

            $filename = 'invoice-' . $order->code . '.pdf';

            return $pdf->download($filename);

        } catch (Exception $e) {
            Log::error('PDF Download Error: ' . $e->getMessage());
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Failed to generate PDF',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function get_columns()
    {
        $columns = (new Order())->getConnection()->getSchemaBuilder()->getColumnListing((new Order())->getTable());
        $columns_detail =(new OrderDetail())->getConnection()->getSchemaBuilder()->getColumnListing((new OrderDetail())->getTable());
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => $this->get_message('add'),
            'data' => $columns,
            'details' => $columns_detail,
        ], Response::HTTP_OK);
    }

    public function get_column_order()
    {
        $columns = [
            'uuid', 'code', 'grand_total', 'mark_as_paid','payment_status' ,'delivery_status',
        ];
        $columns_detail = (new OrderDetail())->getConnection()->getSchemaBuilder()->getColumnListing((new OrderDetail())->getTable());
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => $this->get_message('add'),
            'data' => $columns,
            'details' => $columns_detail,
        ], Response::HTTP_OK);
    }

    public function get_column_order_detail()
    {
        $columns = (new Order())->getConnection()->getSchemaBuilder()->getColumnListing((new Order())->getTable());
        $columns[] = 'formatted_created_at';
        $columns[] = 'subtotal';
        $columns_detail = (new OrderDetail())->getConnection()->getSchemaBuilder()->getColumnListing((new OrderDetail())->getTable());
        $columns_detail[] = 'product_total';
         return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => $this->get_message('add'),
            'data' => $columns,
            'order_details' => $columns_detail,
        ], Response::HTTP_OK);
    }
}
