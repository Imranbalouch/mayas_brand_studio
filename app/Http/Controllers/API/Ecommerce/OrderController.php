<?php

namespace App\Http\Controllers\API\Ecommerce;

use Hash;
use Session;
use Exception;
use Carbon\Carbon; 
use App\Models\Menu;
use App\Models\User;
use DeepCopy\f001\B;
use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\Product;
use App\Models\Ecommerce\Customer;
use App\Models\Language;
use App\Models\Ecommerce\Tracking;
use App\Models\CMS\Theme;
use App\Models\Ecommerce\Inventory;
use App\Mail\EmailManager;
use App\Models\Ecommerce\CustomItem;
use App\Models\Ecommerce\Fulfillment;
use App\Models\Ecommerce\OrderDetail;
use Illuminate\Support\Str;
use App\Models\Ecommerce\OrderComment;
use App\Models\Ecommerce\ProductStock;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use App\Models\Ecommerce\OrderTimeLine;
use App\Models\Ecommerce\AddressCustomer;
use Barryvdh\DomPDF\Facade\Pdf;
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

class OrderController extends Controller
{
    
    use MessageTrait;
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function add_order(Request $request)
{
    DB::beginTransaction();

    $validator = Validator::make($request->all(), [
        'customer_id' => 'nullable',
        'market_id' => 'required',
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
        'tags' => 'nullable|array',
        'tags.*' => 'nullable',
        'custom_items' => 'nullable|array',
        'custom_items.*.item_name' => 'required|string',
        'custom_items.*.price' => 'required|numeric|min:0',
        'custom_items.*.item_taxable' => 'nullable|in:0,1',
        'custom_items.*.item_physical_product' => 'nullable|in:0,1',
        'custom_items.*.item_weight' => 'nullable|numeric',
        'custom_items.*.unit' => 'nullable|string',
        'custom_items.*.qty' => 'required|numeric|min:1',
        'items' => 'nullable|array',
        'items.*.product_id' => 'nullable|exists:products,uuid',
        'items.*.variant_id' => [
            'nullable',
            'exists:product_stocks,uuid',
            function ($attribute, $value, $fail) use ($request) {
                $index = explode('.', $attribute)[1];
                $productId = $request->input("items.{$index}.product_id");
                if ($productId && !ProductStock::where('uuid', $value)
                    ->where('product_id', $productId)
                    ->exists()) {
                    $fail('The selected variant does not belong to the specified product.');
                }
            },
        ],
        'items.*.product_name' => 'nullable|string',
        'items.*.product_price' => 'nullable|numeric|min:0',
        'items.*.product_qty' => 'nullable|numeric|min:1',
        'fulfilled_status' => 'nullable',
        'payment_method' => 'nullable',
        'timeline' => 'nullable|string', 
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'errors' => $validator->errors()
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    try {
        $order = $request->except(['product_ids', 'variation_ids', 'custom_items', 'items', 'grand_total']);
        $order['uuid'] = Str::uuid();
        $order['auth_id'] = Auth::user()->uuid;
        $order['code'] = Order::generateOrderCode();
        $order['location_id'] = $request->location_id; 
        $order['notes'] = $request->note; 
        $order['fulfilled_status'] = 0; 
        $order['delivery_status'] = 'Pending';

        if ($request->has('customer_id') && $request->customer_id) {
            $customer = Customer::where('uuid', $request->customer_id)->first();

            if ($customer) {
                $order['billing_first_name'] = $customer->first_name;
                $order['billing_last_name'] = $customer->last_name;
                $order['billing_email'] = $customer->email;
                $order['billing_phone'] = $customer->phone;
                $order['shipping_first_name'] = $customer->first_name;
                $order['shipping_last_name'] = $customer->last_name;
                $order['shipping_phone'] = $customer->phone;

                $addresses = AddressCustomer::where('customer_id', $customer->uuid)
                    ->whereIn('type', ['billing_address', 'shipping_address'])
                    ->get();

                $billingAddress = $addresses->where('type', 'billing_address')->first();
                if ($billingAddress) {
                    $order['billing_address'] = $billingAddress->address;
                    $order['billing_city'] = $billingAddress->city;
                    $order['billing_state'] = $billingAddress->state;
                    $order['billing_country'] = $billingAddress->country;
                }

                $shippingAddress = $addresses->where('type', 'shipping_address')->first();
                if ($shippingAddress) {
                    $order['shipping_address'] = $shippingAddress->address;
                    $order['shipping_email'] = $shippingAddress->address_email;
                    $order['shipping_city'] = $shippingAddress->city;
                    $order['billing_state'] = $shippingAddress->state;
                    $order['shipping_country'] = $shippingAddress->country;
                }
            }
        }
        
        if ($request->has('tags') && is_array($request->tags)) {
            $order['tags'] = json_encode($request->tags); 
        } 
        
        // Initialize grand total
        $grandTotal = 0;

        // Process custom items
        $customItemIds = [];
        if ($request->has('custom_items')) {
            foreach ($request->input('custom_items') as $customItemData) {
                $itemQty = $customItemData['qty'];
                $itemPrice = $customItemData['price'];
                $grandTotal += $itemPrice * $itemQty;
                
                $customItem = CustomItem::create([
                    'uuid' => Str::uuid(),
                    'auth_id' => Auth::user()->uuid,
                    'order_id' => $order['uuid'],
                    'item_name' => $customItemData['item_name'],
                    'price' => $itemPrice,
                    'qty' => $itemQty,
                    'item_taxable' => $customItemData['item_taxable'] ?? 0,
                    'item_physical_product' => $customItemData['item_physical_product'] ?? 0,
                    'item_weight' => $customItemData['item_weight'] ?? null,
                    'unit' => $customItemData['unit'] ?? null,
                ]);
                $customItemIds[] = $customItem->uuid;
            }
        }

        // Initialize array to collect all order details
        $orderDetails = [];

        // Handle product details
        $productDetails = $request->input('items', []);
        if (!empty($productDetails)) {
            foreach ($productDetails as $index => $productData) {
                // Validate variant belongs to product
                if (isset($productData['variant_id']) && isset($productData['product_id'])) {
                    $variantBelongsToProduct = ProductStock::where('uuid', $productData['variant_id'])
                        ->where('product_id', $productData['product_id'])
                        ->exists();
                    if (!$variantBelongsToProduct) {
                        DB::rollBack();
                        return response()->json([
                            'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                            'message' => 'The provided variant does not belong to the specified product',
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);
                    }
                }        
                
                $product = null;
                $productStock = null;
                $finalProductName = null;
                $finalProductPrice = null;
                
                // Validate Product
                if (isset($productData['product_id'])) {
                    $product = Product::where('uuid', $productData['product_id'])->first();
                    
                    // Check selling_stock_enabled flag and stock quantity
                    if ($product) {
                        $orderQuantity = $productData['product_qty'] ?? 1;
                        
                        // If variant is specified, check variant stock
                        if (isset($productData['variant_id'])) {
                            $productStock = ProductStock::where('uuid', $productData['variant_id'])->first();
                            
                            if ($productStock && $productStock->qty < $orderQuantity && $product->selling_stock_enabled == 0) {
                                DB::rollBack();
                                return response()->json([
                                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                                    'message' => 'Product stock is not available.',
                                    'requested_qty' => $orderQuantity,
                                    'available_qty' => $productStock->qty
                                ], Response::HTTP_UNPROCESSABLE_ENTITY);
                            }
                        } 
                    }
                }

                // Continue with variant validation
                if (isset($productData['variant_id'])) {
                    $productStock = ProductStock::where('uuid', $productData['variant_id'])->first();
                }

                $orderQuantity = $productData['product_qty'] ?? 1;

                // Determine Product Name
                if ($product && $product->name) {
                    $finalProductName = $product->name;
                } elseif (isset($productData['product_name'])) {
                    $finalProductName = $productData['product_name'];
                }

                // Determine Product Price
                if ($productStock && $productStock->price) {
                    $finalProductPrice = $productStock->price;
                } elseif ($product && $product->unit_price) {
                    $finalProductPrice = $product->unit_price;
                } elseif (isset($productData['product_price'])) {
                    $finalProductPrice = $productData['product_price'];
                } else {
                    $finalProductPrice = 0;
                }
                
                // Add to grand total
                $grandTotal += $finalProductPrice * $orderQuantity;

                // Create Order Detail
                $OrderDetail = [
                    'uuid' => Str::uuid(),
                    'auth_id' => Auth::user()->uuid,
                    'order_id' => $order['uuid'],
                    'product_id' => $productData['product_id'] ?? null,
                    'variant' => $productStock->variant ?? null,
                    'variant_id' => $productData['variant_id'] ?? null,
                    'custom_item_id' => $customItemIds[$index] ?? null,
                    'product_name' => $finalProductName,
                    'product_price' => $finalProductPrice,
                    'image' => $productData['image'] ?? null,
                    'product_qty' => $orderQuantity,
                ];
                
                // Store order detail in array
                $orderDetails[] = $OrderDetail;
               
                // Create Order Detail record
                OrderDetail::create($OrderDetail);
            }
        }

        // Apply shipping price
        $shipping_price = 0;
        if (!empty($request->input('shipping_price')) && $request->input('shipping_price') > 0) {
            $shipping_price = $request->input('shipping_price');
        }
        $grandTotal += $shipping_price;

        // Apply discount
        $discount_type = '';
        $discount_value = 0;
        if (!empty($request->input('discount_value')) && $request->input('discount_value') > 0) {
            $discount_type = $request->input('discount_type');
            $discount_value = $request->input('discount_value');
            if ($discount_type == 'amount') {
                $grandTotal -= $discount_value;
            } elseif ($discount_type == 'percentage') {
                $grandTotal -= ($grandTotal * ($discount_value / 100));
            }
            $order['discount_type'] = $discount_type;
            $order['discount_value'] = $discount_value;
        }

        // Set the calculated grand total
        $order['grand_total'] = $grandTotal;
      
        // Create the main order record
        $save_order = Order::create($order);

        // Create order timeline
        OrderTimeLine::create([
            'uuid' => Str::uuid(),
            'auth_id' => Auth::user()->uuid,
            'order_id' => $save_order->uuid,
            'message' => 'Order Created',
            'status' => $save_order->status ?? null,
        ]);

        // Commit the transaction
        DB::commit();

        // Return success response
        return response()->json([
            'status_code' => Response::HTTP_OK,
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
    
public function update_order(Request $request, $uuid)
{
    $validator = Validator::make($request->all(), [
        'customer_id' => 'nullable',
        'market_id' => 'required',
        'grand_total' => 'nullable|numeric',
        'reserve_item' => 'nullable|date_format:Y-m-d H:i:s',
        'payment_due_later' => 'nullable',
        'shipping_type' => 'nullable|string',
        'shipping_price' => 'nullable|numeric|min:0',
        'discount_code' => 'nullable|string',
        'estimated_tax' => 'nullable|in:0,1',
        'auto_discount' => 'nullable|in:0,1',
        'channel_id' => 'nullable',
        'discount_value' => 'nullable|numeric|min:0',
        'discount_amount' => 'nullable|numeric|min:0',
        'tags' => 'nullable|array',
        'tags.*' => 'nullable',
        'custom_items' => 'nullable|array',
        'custom_items.*.item_name' => 'required|string',
        'custom_items.*.price' => 'required|numeric|min:0',
        'custom_items.*.qty' => 'required|numeric|min:1',
        'custom_items.*.item_taxable' => 'nullable|in:0,1',
        'custom_items.*.item_physical_product' => 'nullable|in:0,1',
        'custom_items.*.item_weight' => 'nullable|numeric',
        'custom_items.*.unit' => 'nullable|string',
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
        'product_details.*.product_name' => 'nullable|string',
        'product_details.*.product_price' => 'nullable|numeric|min:0',
        'product_details.*.product_qty' => 'nullable|numeric|min:1',
        'fulfilled_status' => 'nullable',
        'payment_method' => 'nullable',
        'timeline' => 'nullable|string', 
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'errors' => $validator->errors()
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    try {
        DB::beginTransaction();

        $order = Order::where('uuid', $uuid)->first();
        if (!$order) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => $this->get_message('not_found'),
            ], Response::HTTP_NOT_FOUND);
        }

        // Initialize new grand total
        $grandTotal = 0;

        // Update product details if provided
        if ($request->has('product_details')) {
            // Delete existing order details and custom items
            OrderDetail::where('order_id', $uuid)->delete();
            CustomItem::where('order_id', $uuid)->delete();

            // Process custom items
            $customItemIds = [];
            if ($request->has('custom_items')) {
                foreach ($request->input('custom_items') as $customItemData) {
                    $itemQty = $customItemData['qty'];
                    $itemPrice = $customItemData['price'];
                    $grandTotal += $itemPrice * $itemQty;
                    $customItem = CustomItem::create([
                        'uuid' => Str::uuid(),
                        'auth_id' => Auth::user()->uuid,
                        'order_id' => $uuid,
                        'item_name' => $customItemData['item_name'],
                        'price' => $itemPrice,
                        'qty' => $itemQty,
                        'item_taxable' => $customItemData['item_taxable'] ?? 0,
                        'item_physical_product' => $customItemData['item_physical_product'] ?? 0,
                        'item_weight' => $customItemData['item_weight'] ?? null,
                        'unit' => $customItemData['unit'] ?? null,
                    ]);
                    $customItemIds[] = $customItem->uuid;
                }
            }

            $productDetails = $request->input('product_details', []);
            if (!empty($productDetails)) {
                foreach ($productDetails as $index => $productData) {
                    $product = null;
                    $productStock = null;
                    $finalProductName = null;
                    $finalProductPrice = null;

                    // Validate Product
                    if (isset($productData['product_id'])) {
                        $product = Product::where('uuid', $productData['product_id'])->first();
                        
                        // Check selling_stock_enabled flag and stock quantity
                        if ($product) {
                            $orderQuantity = $productData['product_qty'];
                            
                            // If variant is specified, check variant stock
                            if (isset($productData['variant_id'])) {
                                $productStock = ProductStock::where('uuid', $productData['variant_id'])->first();
                                
                                if ($productStock && $productStock->qty < $orderQuantity && $product->selling_stock_enabled == 0) {
                                    DB::rollBack();
                                    return response()->json([
                                        'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                                        'message' => 'Product stock is not available.',
                                        'requested_qty' => $orderQuantity,
                                        'available_qty' => $productStock->qty
                                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                                }
                            } 
                        }
                    }

                    // Validate Product Stock/Variation
                    if (isset($productData['variant_id'])) {
                        $productStock = ProductStock::where('uuid', $productData['variant_id'])->first();
                    }

                    $orderQuantity = $productData['product_qty'];

                    // Determine Product Name
                    if ($product && $product->name) {
                        $finalProductName = $product->name;
                    } elseif (isset($productData['product_name'])) {
                        $finalProductName = $productData['product_name'];
                    }

                    // Determine Product Price
                    if ($product && $product->unit_price) {
                        $finalProductPrice = $product->unit_price;
                    } elseif ($productStock && $productStock->price) {
                        $finalProductPrice = $productStock->price;
                    } elseif (isset($productData['product_price'])) {
                        $finalProductPrice = $productData['product_price'];
                    } else {
                        $finalProductPrice = 0;
                    }

                    // Add to grand total
                    $grandTotal += $finalProductPrice * $orderQuantity;

                    OrderDetail::create([
                        'uuid' => Str::uuid(),
                        'auth_id' => Auth::user()->uuid,
                        'order_id' => $uuid,
                        'product_id' => $productData['product_id'] ?? null,
                        'variant' => $productStock->varient ?? null,
                        'variant_id' => $productData['variant_id'] ?? null,
                        'custom_item_id' => $customItemIds[$index] ?? null,
                        'product_name' => $finalProductName,
                        'product_price' => $finalProductPrice,
                        'product_qty' => $orderQuantity,
                    ]);
                }
            }
        } else {
            // Calculate grand total from existing items
            $customItems = CustomItem::where('order_id', $uuid)->get();
            foreach ($customItems as $item) {
                $grandTotal += $item->price * $item->qty; 
            }
            $orderDetails = OrderDetail::where('order_id', $uuid)->get();
            foreach ($orderDetails as $detail) {
                $grandTotal += $detail->product_price * $detail->product_qty;
            }
        }

        // Update order fields
        $order->grand_total = $grandTotal;
        $order->notes = $request->notes ?? $order->notes;
        $order->customer_id = $request->customer_id ?? $order->customer_id;
        $order->market_id = $request->market_id ?? $order->market_id;
        $order->channel_id = $request->channel_id ?? $order->channel_id;
        $order->warehouse_id = $request->warehouse_id ?? $order->warehouse_id;
        if ($request->has('tags') && is_array($request->tags)) {
            $order->tags = json_encode($request->tags); 
        }
        $order->reserve_item = $request->reserve_item ?? $order->reserve_item;
        $order->reason_for_discount = $request->reason_for_discount ?? $order->reason_for_discount;
        $order->auto_discount = $request->auto_discount ?? $order->auto_discount;
        $order->estimated_tax = $request->estimated_tax ?? $order->estimated_tax;
        $order->discount_type = $request->discount_type ?? $order->discount_type;
        $order->discount_value = $request->discount_value ?? $order->discount_value;
        $order->discount_amount = $request->discount_amount ?? $order->discount_amount;
        $order->payment_due_later = $request->payment_due_later ?? $order->payment_due_later;
        $order->shipping_type = $request->shipping_type ?? $order->shipping_type;
        $order->shipping_price = $request->shipping_price ?? $order->shipping_price;
        $order->discount_code = $request->discount_code ?? $order->discount_code;
        $order->fulfilled_status = $request->fulfilled_status ?? $order->fulfilled_status;
        $order->payment_method = $request->payment_method ?? $order->payment_method;

        $order->save();

        if ($request->has('timeline')) {
            OrderTimeLine::create([
                'uuid' => Str::uuid(),
                'auth_id' => Auth::user()->uuid,
                'order_id' => $uuid,
                'message' => $request->timeline,
                'status' => $order->status,
            ]);
        }

        DB::commit();
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => $this->get_message('update'),
        ], Response::HTTP_OK);

    } catch (Exception $e) {
        DB::rollBack();
        return response()->json([
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $this->get_message('server_error'),
            'error' => $e->getMessage(),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

public function mark_as_paid(Request $request, $uuid)
{
    $validator = Validator::make($request->all(), [
        'payment_method' => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'errors' => $validator->errors()
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    try {
        DB::beginTransaction();

        $order = Order::where('uuid', $uuid)->first();

        if (!$order) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => $this->get_message('not_found'),
            ], Response::HTTP_NOT_FOUND);
        }

        // Check if already marked as paid
        if ($order->mark_as_paid) {
            return response()->json([
                'status_code' => Response::HTTP_BAD_REQUEST,
                'message' => 'Order is already marked as paid.',
            ], Response::HTTP_BAD_REQUEST);
        }

        // Mark the order as paid
        $order->mark_as_paid = true;
        $order->payment_method = $request->input('payment_method');
        $order->paid_at = now();
        $order->save();

        // Create a timeline entry if needed
        OrderTimeLine::create([
            'uuid' => Str::uuid(),
            'auth_id' => Auth::user()->uuid,
            'order_id' => $uuid,
            'message' => 'Order marked as paid using ' . $order->payment_method,
            'status' => $order->status,
        ]);

        $emailSent = false;
        if (env('MAIL_USERNAME') != null && $order->customer_id) {
            $customer = Customer::where('uuid', $order->customer_id)->first();
            // dd($customer);
            $orderDetails = OrderDetail::where('order_id', $order->uuid)->get();

            if ($customer && $customer->email) {
                $data = [
                    'details' => [
                        'order' => $order,
                        'orderDetail' => $orderDetails,
                        'customer' => $customer,
                    ]
                ];

                try {
                    Mail::send('emailtemplate.order_confirmation', $data, function($message) use ($data) {
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
                \Log::warning('Email not sent: Customer or customer email not found. Order UUID: ' . $order->uuid);
            }
        } else {
            \Log::warning('Email not sent: MAIL_USERNAME is null or order has no customer_id. Order UUID: ' . $order->uuid);
        }

        if (!$emailSent) {
            \Log::info('Email was not sent for order UUID: ' . $order->uuid);
        }

        DB::commit();
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => 'Order marked as paid successfully',
            'email_sent' => $emailSent
        ], Response::HTTP_OK);

    } catch (Exception $e) {
        DB::rollBack();
        \Log::error('Exception while marking order as paid: ' . $e->getMessage());
        return response()->json([
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $this->get_message('server_error'),
            'error' => $e->getMessage(),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}


    public function edit_order($uuid){

        
        try {
            
            $edit_order = Order::with('orderDetails','customer')->where('uuid', $uuid)->first();
            $edit_order_translation = Order::where('uuid', $uuid)->first();
            //dd($edit_order->orderDetails);

            $edit_order['time_line'] = OrderTimeLine::where('order_id', $uuid) ->orderBy('id', 'desc')->get();
            $edit_order['fullfillments'] = Fulfillment::where('order_id', $uuid)->get();
            if($edit_order)
            {

                return response()->json([ 
                    'status_code' => Response::HTTP_OK,
                    'data' => $edit_order, 
                ], Response::HTTP_OK);


            }else{

                return response()->json([ 
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'), 
                ], Response::HTTP_NOT_FOUND);

            }

        
        }catch (\Exception $e) { 
            // Handle general exceptions
            dd($e);
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }


    }


    // Get Orders

    public function get_order(Request $request)
    {
        try {
            $menuUuid = $request->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);
    
            if (!$permissions['view']) {
                if (!Auth::user()->hasPermission('viewglobal')) {
                    return response()->json([
                        'message' => 'You do not have permission to view this menu'
                    ], Response::HTTP_FORBIDDEN);
                }
            }
    
            $orders = Order::query()
                ->with([
                    'orderDetails',
                    'customer',
                    //'channel',
                    'tracking'
                ])
                ->select([
                    'orders.*',
                    \DB::raw('CAST(((SELECT SUM(product_qty) FROM order_details WHERE order_details.order_id = orders.uuid) ) AS UNSIGNED) as items_count')
                ])
                ->orderBy('id', 'desc');
    
            if ($permissions['view'] && !$permissions['viewglobal']) {
                $orders->where('auth_id', Auth::user()->uuid);
            }
    
            $orders = $orders->get();
    
            $orders->transform(function ($order) {
                $order->tags = json_decode($order->tags, true);
                return $order;
            });
    
            return response()->json([
                'status_code' => 200,
                'permissions' => $permissions,
                'data' => $orders,
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'An error occurred while fetching the orders.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function get_specific_order($uuid)
    {
        try {
            $order = Order::with(['orderDetails', 'customer', 'channel'])->where('uuid', $uuid)->first();

            if (!$order) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            $productItemCount = $order->orderDetails->sum('product_qty');
            
            $customItems = CustomItem::where('order_id', $order->uuid)->get();
            $customItemCount = $customItems->sum('qty');
            
            $order->items_count = $productItemCount + $customItemCount;
            $order->custom_items = $customItems;
            $order->tags = json_decode($order->tags, true);

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'data' => $order,
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete_order($uuid){
        try {
            $del_order = Order::where('uuid', $uuid)->first();
            
            if(!$del_order) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }
    
            $order_details = OrderDetail::where('order_id', $uuid)->get();
    
            $custom_item_ids = $order_details->pluck('custom_item_id')->filter()->unique();
    
            OrderDetail::where('order_id', $uuid)->delete();
    
            if ($custom_item_ids->count() > 0) {
                CustomItem::whereIn('uuid', $custom_item_ids)->delete();
            }
    
            $delete_order = Order::destroy($del_order->id);
    
            if($delete_order){
                return response()->json([
                    'status_code' => Response::HTTP_OK,
                    'message' => $this->get_message('delete'),
                ], Response::HTTP_OK);
            }
    
        } catch (\Exception $e) { 
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                'error' => $e->getMessage(), 
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } 
    }

    public function show_order_details($id)
    {
        try {
            // Fetch order with details, product relationships, and timeline
            $order = Order::where('code', $id)
                ->with([
                    'orderDetails', 
                    'orderDetails.product',
                    'orderTimeline' => function($query) {
                        $query->orderBy('created_at', 'desc')
                        ->with(['user' => function($query) {
                            $query->selectRaw("uuid, CONCAT(first_name, ' ', last_name) as name");
                        }]);}
                ])
                ->first();
    
            // If order not found, return a 404 response
            if (!$order) {
                return response()->json([
                    'status_code' => 404,
                    'message' => 'Order not found',
                ], 404);
            }
    
            // Decode JSON fields
            $order->shipping_address = json_decode($order->shipping_address);
    
            // Decode choice_options for each order detail and modify thumbnail_img
            foreach ($order->orderDetails as $detail) {
                if ($detail->product) {
                    // Decode choice_options if available
                    if ($detail->product->choice_options) {
                        $detail->product->choice_options = json_decode($detail->product->choice_options);
                    }
    
                    // Append APP_ASSET_PATH to thumbnail_img
                    if ($detail->product->thumbnail_img) {
                        $detail->product->thumbnail_img = env('APP_ASSET_PATH') . $detail->product->thumbnail_img;
                    }
                }
            }
    
            // Process timeline messages
            $processedTimeline = [];
            foreach ($order->orderTimeline as $timeline) {
                $processedTimeline[] = [
                    'uuid' => $timeline->uuid,
                    'message' => json_decode($timeline->message),
                    'status' => $timeline->status,
                    'created_at' => $timeline->created_at,
                    'user' => $timeline->user ? [
                        'uuid' => $timeline->user->uuid,
                        'name' => $timeline->user->first_name . ' ' . $timeline->user->last_name, 
                    ] : null
                ];
            }
    
            // Return the response
            return response()->json([
                'status_code' => 200,
                'data' => [
                    'order' => $order,
                    'timeline' => $processedTimeline,
                ],
            ], 200);
    
        } catch (\Exception $e) {
            // Handle errors gracefully
            return response()->json([
                'status_code' => 500,
                'message' => 'An error occurred while retrieving the order details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function add_order_comment(Request $request, $orderUuid)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'body' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            // Check if order exists
            $order = Order::where('uuid', $orderUuid)->first();

            if (!$order) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            // Create the comment
            $comment = OrderComment::create([
                'uuid' => Str::uuid(),
                'order_id' => $orderUuid,
                'auth_id' => Auth::user()->uuid,
                'body' => $request->input('body'),
            ]);

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Comment added successfully',
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Retrieve comments for a specific order
     *
     * @param string $orderUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function get_order_comments($orderUuid)
    {
        try {
            // Check if order exists
            $order = Order::where('uuid', $orderUuid)->first();

            if (!$order) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            // Retrieve comments with user information
            $comments = OrderComment::where('order_id', $orderUuid)
                ->with('user:uuid,first_name,last_name,email') // Adjust fields as needed
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'data' => [
                   'comments' => $comments
                    ]
            ], Response::HTTP_OK);  

        } catch (Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function edit_order_comment($commentUuid){

        
        try {
            
            $edit_order_comment = OrderComment::where('uuid', $commentUuid)->first();
            $edit_order_comment_translation = OrderComment::where('uuid', $commentUuid)->first();

            if($edit_order_comment)
            {

                return response()->json([

                    'status_code' => Response::HTTP_OK,
                    'data' => $edit_order_comment,

                ], Response::HTTP_OK);


            }else{

                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);

            }

        
        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }


    }    

    /**
     * Update an existing comment
     *
     * @param Request $request
     * @param string $commentUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function update_order_comment(Request $request, $commentUuid)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'body' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            // Find the comment
            $comment = OrderComment::where('uuid', $commentUuid)
                ->where('auth_id', Auth::user()->uuid)
                ->first();

            if (!$comment) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Comment not found or you are not authorized to edit this comment',
                ], Response::HTTP_NOT_FOUND);
            }

            // Update the comment
            $comment->body = $request->input('body');
            $comment->save();

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Comment updated successfully',
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a comment
     *
     * @param string $commentUuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete_order_comment($commentUuid)
    {
        try {
            // Find the comment
            $comment = OrderComment::where('uuid', $commentUuid)
                ->where('auth_id', Auth::user()->uuid)
                ->first();

            if (!$comment) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Comment not found or you are not authorized to delete this comment',
                ], Response::HTTP_NOT_FOUND);
            }

            // Delete the comment
            $comment->delete();

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Comment deleted successfully',
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update_delivery_status(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'delivery_status' => 'required|in:On_the_way,delivered,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $order = Order::where('uuid', $uuid)->first();
            
            if (!$order) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Order not found',
                ], Response::HTTP_NOT_FOUND);
            }

            $order->delivery_status = $request->delivery_status;
            $order->save();

            // Create timeline entry
            OrderTimeLine::create([
                'uuid' => Str::uuid(),
                'auth_id' => Auth::user()->uuid,
                'order_id' => $order->uuid,
                'message' => 'Delivery status updated to: ' . $request->delivery_status,
                'status' => $order->status ?? null,
            ]);

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Delivery status updated successfully',
            ], Response::HTTP_OK);

        } catch (Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function downloadOrderInvoice($uuid)
    {
        try {
            // Get order with relationships
            $order = Order::with(['orderDetails', 'customer', 'channel', 'tracking'])
                ->where('uuid', $uuid)
                ->first();

            if (!$order) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Order not found.',
                ], Response::HTTP_NOT_FOUND);
            }

            // Check if order has customer
            if (!$order->customer_id) {
                return response()->json([
                    'status_code' => Response::HTTP_FORBIDDEN,
                    'message' => 'Invoice download is only available for customer orders.',
                ], Response::HTTP_FORBIDDEN);
            }

            // Use the same logic as CustomerOrderController
            $order->formatted_created_at = $order->created_at->format('F j, Y');
            
            $subtotal = 0;
            $orderDetails = $order->orderDetails->map(function ($item) use (&$subtotal) {
                $item->product_total = $item->product_price * $item->product_qty;
                $subtotal += $item->product_total;
                return $item;
            });

            $activeTheme = Theme::where('status', 1)->first();

            $qrCodeData = QrCode::format('svg')
                ->size(150)
                ->generate($order->code);

            $qrCodeBase64 = base64_encode($qrCodeData);

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

            $pdf = Pdf::loadView('pdf.order-invoice', $data);
            $pdf->setPaper('a4', 'portrait');
            $pdf->setOption('dpi', 150);
            $pdf->setOption('defaultFont', 'Arial');

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
    
}   
