<?php

namespace App\Http\Controllers\API\Ecommerce;

use App\Models\Ecommerce\Order;
use App\Models\Ecommerce\Product;
use App\Models\Ecommerce\Tracking;
use App\Models\Ecommerce\Inventory;
use App\Models\Ecommerce\Fulfillment;
use App\Models\Ecommerce\OrderDetail;
use Illuminate\Support\Str;
use App\Models\Ecommerce\ProductStock;
use Illuminate\Http\Request;
use App\Models\Ecommerce\OrderTimeLine;
use App\Models\Ecommerce\ReturnExchange;
use App\Models\Ecommerce\InventoryCommited;
use App\Models\Ecommerce\InventoryAvailable;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class FulfillmentController extends Controller
{
    public function get_fulfillments()
    {
        $fulfillments = Fulfillment::get();
        
        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => 'Fulfillment list',
            'data' => $fulfillments
        ]);
    }

    public function create_fulfillment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,uuid',
            'order_detail_id' => 'required|exists:order_details,uuid',
            'quantity' => 'required|integer|min:1',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            DB::beginTransaction();

            // Get the order detail to check available quantity
            $orderDetail = OrderDetail::where('uuid', $request->order_detail_id)
                ->where('order_id', $request->order_id)
                ->firstOrFail();

            // Calculate already fulfilled quantity
            $fulfilledQuantity = Fulfillment::where('order_detail_id', $request->order_detail_id)
                ->sum('quantity');

            $availableQuantity = $orderDetail->product_qty - $fulfilledQuantity;
        
            if ($request->quantity > $availableQuantity) {
                DB::rollBack();
                return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'Requested quantity exceeds available quantity. Available: ' . $availableQuantity,
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Check inventory for product or variant
            $productStock = null;

            if ($orderDetail->variant_id) {
                $productStock = ProductStock::where('uuid', $orderDetail->variant_id)->lockForUpdate()->first();
            } elseif ($orderDetail->product_id) {
                $productStock = ProductStock::where('product_id', $orderDetail->product_id)->lockForUpdate()->first();
            }

            // Check if the order is paid
            $order = Order::where('uuid', $request->order_id)->firstOrFail();
            if ($order->mark_as_paid != 1) {
                DB::rollBack();
                return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'The order is not paid',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Create the fulfillment
            $fulfillment = Fulfillment::create([
                'uuid' => Str::uuid(),
                'auth_id' => Auth::user()->uuid,
                'order_id' => $request->order_id,
                'order_detail_id' => $request->order_detail_id,
                'quantity' => $request->quantity,
                'status' => 1, 
            ]);

            // Update inventory records
            if ($productStock) {
                $inventory = Inventory::create([
                    'uuid' => Str::uuid(),
                    'auth_id' => Auth::user()->uuid,
                    'stock_id' => $productStock->uuid,
                    'sku' => $productStock->sku,
                    'order_id' => $request->order_id,
                    'status' => 'committed',
                    'reason' => 'sale',
                    'location_id' => $productStock->location_id,
                    'price' => $productStock->price,
                    'product_id' => $orderDetail->product_id,
                    'qty' => $request->quantity,
                    'order_code' => $order->code,
                ]);
                if ($productStock->qty < $orderDetail->product_qty) {
                    DB::rollBack();
                    return response()->json([
                        'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                        'message' => 'Product ' . $productStock->sku . ' not enough stock',
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
                $productStock->qty -= $orderDetail->product_qty;
                if (!$productStock->save()) {
                    DB::rollBack();
                    return response()->json([
                        'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                        'message' => 'Failed to update product stock',
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }

            // Update order detail fulfillment status
            $this->updateOrderDetailFulfillmentStatus($request->order_detail_id);

            // Update order fulfillment status
            $this->updateOrderFulfillmentStatus($request->order_id);

            OrderTimeLine::create([
                'uuid' => Str::uuid(),
                'auth_id' => Auth::user()->uuid,
                'order_id' => $request->order_id,
                'message' => 'Fulfillment created for ' . $request->quantity . ' item(s) of ' . $orderDetail->product_name,
                'status' => 'fulfillment_created',
            ]);

            DB::commit();

            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => 'Fulfillment created successfully',
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Failed to create fulfillment',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function add_tracking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string',
            'fulfillment_id' => 'required|string',
            'shipping_carrier' => 'required|string',
            'tracking_numbers' => 'required|array',
            'tracking_numbers.*' => 'required|string',
            'tracking_urls' => 'nullable|array',
            'tracking_urls.*' => 'nullable|url',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            DB::beginTransaction();

            $fulfillment = Fulfillment::where('uuid', $request->fulfillment_id)
                ->where('order_id', $request->order_id)
                ->first();
                
            if (!$fulfillment) {
                DB::rollBack();
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Fulfillment not found or does not belong to this order',
                ], Response::HTTP_NOT_FOUND);
            }

            $existingTracking = Tracking::where('fulfillment_id', $request->fulfillment_id)->exists();

            if ($existingTracking) {
                DB::rollBack();
                return response()->json([
                    'status' => Response::HTTP_CONFLICT,
                    'message' => 'Tracking information already exists for this fulfillment',
                ], Response::HTTP_CONFLICT);
            }

            $trackingEntries = [];
            foreach ($request->tracking_numbers as $index => $trackingNumber) {
                $trackingEntries[] = [
                    'uuid' => Str::uuid(),
                    'auth_id' => Auth::user()->uuid,
                    'order_id' => $request->order_id,
                    'fulfillment_id' => $request->fulfillment_id,
                    'shipping_carrier' => $request->shipping_carrier,
                    'tracking_number' => $trackingNumber,
                    'tracking_url' => $request->tracking_urls[$index] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Tracking::insert($trackingEntries);
            
            $order = Order::where('uuid', $request->order_id)->first();
            if ($order) {
                $order->update([
                    'delivery_status' => 'Tracking added',
                    'tracking_status' => 1
                ]);
            }

            OrderTimeLine::create([
                'uuid' => Str::uuid(),
                'auth_id' => Auth::user()->uuid,
                'order_id' => $request->order_id,
                'message' => 'Tracking information added for fulfillment',
                'status' => 'tracking_added',
            ]);

            DB::commit();

            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => 'Tracking information added successfully',
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Failed to add tracking information',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function update_fulfillment(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'nullable|integer|min:1',
            'shipping_carrier' => 'nullable|string',
            'tracking_number' => 'nullable|string',
            'tracking_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            DB::beginTransaction();

            $fulfillment = Fulfillment::where('uuid', $uuid)->firstOrFail();

            // Get the order detail
            $orderDetail = OrderDetail::where('uuid', $fulfillment->order_detail_id)
                ->where('order_id', $fulfillment->order_id)
                ->firstOrFail();

            // Calculate fulfilled quantity excluding current fulfillment
            $fulfilledQuantity = Fulfillment::where('order_detail_id', $fulfillment->order_detail_id)
                ->where('uuid', '!=', $uuid)
                ->sum('quantity');

            $availableQuantity = $orderDetail->product_qty - $fulfilledQuantity;
            $newQuantity = $request->quantity ?? $fulfillment->quantity;

            if ($newQuantity > $availableQuantity) {
                DB::rollBack();
                return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'Requested quantity exceeds available quantity. Available: ' . $availableQuantity,
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Determine product stock
            $productStock = null;
            if ($orderDetail->variant_id) {
                $productStock = ProductStock::where('uuid', $orderDetail->variant_id)->lockForUpdate()->first();
            } elseif ($orderDetail->product_id) {
                $productStock = ProductStock::where('product_id', $orderDetail->product_id)->lockForUpdate()->first();
            }

            $order = Order::where('uuid', $fulfillment->order_id)->firstOrFail();
            if ($order->mark_as_paid != 1) {
                DB::rollBack();
                return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'message' => 'The order is not paid',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Delete previous inventory records related to this fulfillment
            Inventory::where('order_id', $fulfillment->order_id)
                ->where('stock_id', $productStock->uuid)
                ->where('reason', 'sale')
                ->where('status', 'committed')
                ->delete();

            // Create new inventory records
            $inventory = Inventory::create([
                'uuid' => Str::uuid(),
                'auth_id' => Auth::user()->uuid,
                'stock_id' => $productStock->uuid,
                'order_id' => $fulfillment->order_id,
                'status' => 'committed',
                'reason' => 'sale',
                'location_id' => $productStock->location_id,
                'price' => $productStock->price,
                'product_id' => $orderDetail->product_id,
                'sku' => $orderDetail->sku,
                'qty' => $newQuantity,
            ]);

            // Get the order
            $order = Order::where('uuid', $fulfillment->order_id)->first();

            // Delete existing tracking for this fulfillment
            Tracking::where('fulfillment_id', $uuid)->delete();

            // Update the fulfillment
            $fulfillment->update([
                'quantity' => $newQuantity,
            ]);

            // Create new tracking entries
            if (!empty($request->tracking_numbers)) {
                $trackingEntries = [];
                foreach ($request->tracking_numbers as $index => $trackingNumber) {
                    $trackingEntries[] = [
                        'uuid' => Str::uuid(),
                        'auth_id' => Auth::user()->uuid,
                        'order_id' => $fulfillment->order_id,
                        'fulfillment_id' => $fulfillment->uuid,
                        'shipping_carrier' => $request->shipping_carrier,
                        'tracking_number' => $trackingNumber,
                        'tracking_url' => $request->tracking_urls[$index] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                Tracking::insert($trackingEntries);

                if ($order) {
                    $order->update([
                        'delivery_status' => 'Tracking added'
                    ]);
                }
            }

            // Update order detail fulfillment status
            $this->updateOrderDetailFulfillmentStatus($fulfillment->order_detail_id);

            // Update order fulfillment status
            $this->updateOrderFulfillmentStatus($fulfillment->order_id);

            OrderTimeLine::create([
                'uuid' => Str::uuid(),
                'auth_id' => Auth::user()->uuid,
                'order_id' => $fulfillment->order_id,
                'message' => 'Fulfillment updated for ' . $newQuantity . ' item(s) of ' . $orderDetail->product_name,
                'status' => 'fulfillment_updated',
            ]);

            DB::commit();

            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => 'Fulfillment updated successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Failed to update fulfillment',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function edit_fulfillment($uuid)
    {
        try {
            $edit_fulfillment = Fulfillment::where('uuid', $uuid)->first();

            if ($edit_fulfillment) {
                return response()->json([
                    'status_code' => Response::HTTP_OK,
                    'data' => $edit_fulfillment,
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

    public function delete_fulfillment($uuid)
    {
        try {
            DB::beginTransaction();

            $fulfillment = Fulfillment::where('uuid', $uuid)->first();

            if (!$fulfillment) {
                DB::rollBack();
                return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                    'message' => 'Fulfillment not found',
                ], Response::HTTP_NOT_FOUND);
            }

            $orderId = $fulfillment->order_id;
            $orderDetail = OrderDetail::where('uuid', $fulfillment->order_detail_id)->first();

            $productStock = null;
            if ($orderDetail->variant_id) {
                $productStock = ProductStock::where('uuid', $orderDetail->variant_id)->lockForUpdate()->first();
            } elseif ($orderDetail->product_id) {
                $productStock = ProductStock::where('product_id', $orderDetail->product_id)->lockForUpdate()->first();
            }

            Tracking::where('fulfillment_id', $uuid)->delete();

            $fulfillment->delete();

            $this->updateOrderFulfillmentStatus($orderId);

            OrderTimeLine::create([
            'uuid' => Str::uuid(),
            'auth_id' => Auth::user()->uuid,
            'order_id' => $orderId,
            'message' => 'Fulfillment deleted for ' . $orderDetail->product_name,
            'status' => 'fulfillment_deleted',
        ]);
            DB::commit();

            return response()->json([
                'status' => Response::HTTP_OK,
                'message' => 'Fulfillment deleted successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Failed to delete fulfillment',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    protected function updateOrderFulfillmentStatus($orderId)
    {
        $order = Order::where('uuid', $orderId)->firstOrFail();
        $orderDetails = OrderDetail::where('order_id', $orderId)->get();

        $totalOrdered = 0;
        $totalFulfilled = 0;

        foreach ($orderDetails as $detail) {
            $totalOrdered += $detail->product_qty;
            $totalFulfilled += Fulfillment::where('order_detail_id', $detail->uuid)->sum('quantity');
        }

        if ($totalFulfilled == 0) {
            $status = 0;
        } elseif ($totalFulfilled < $totalOrdered) {
            $status = 2;
        } else {
            $status = 1;
        }
        $status = 1;
        $order->update(['fulfilled_status' => $status]);
    }

    private function updateOrderDetailFulfillmentStatus($orderDetailId)
    {
        $orderDetail = OrderDetail::where('uuid', $orderDetailId)->first();
        if (!$orderDetail) {
            return;
        }

        // Calculate total fulfilled quantity
        $fulfilledQuantity = Fulfillment::where('order_detail_id', $orderDetailId)
            ->sum('quantity');

        // Determine fulfillment status based on quantity
        if ($fulfilledQuantity == 0) {
            $status = 0;
        } elseif ($fulfilledQuantity < $orderDetail->product_qty) {
            $status = 2;
        } else {
            $status = 1;
        }

        // Update the order detail status
        $orderDetail->update([
            'fulfilled_status' => $status
        ]);
    }

    public function create_return(Request $request)
{
    $validator = Validator::make($request->all(), [
        'order_id' => 'required|exists:orders,uuid',
        'order_detail_id' => 'required|exists:order_details,uuid',
        'qty' => 'required|integer|min:1',
        'reason_for_return' => 'required|string',
        'return_file' => 'nullable|string',
        'return_url' => 'nullable|url',
        'tracking_number' => 'required|string',
        'shipping_carrier' => 'nullable|string',
        'exchange_products' => 'nullable|array',
        'exchange_products.*.product_id' => 'required_with:exchange_products|exists:products,uuid',
        'exchange_products.*.variant_id' => [
            'nullable',
            'exists:product_stocks,uuid',
            function ($attribute, $value, $fail) use ($request) {
                $index = explode('.', $attribute)[1];
                $productId = $request->input("exchange_products.{$index}.product_id");
                if ($productId && $value && !ProductStock::where('uuid', $value)
                    ->where('product_id', $productId)
                    ->exists()) {
                    $fail('The selected variant does not belong to the specified product.');
                }
            },
        ],
        'exchange_products.*.qty' => 'required_with:exchange_products|integer|min:1',
        'return_shipping_fees' => 'nullable|numeric|min:0',
        'restocking_fees' => 'nullable|numeric|min:0|max:100',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'errors' => $validator->errors()
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    try {
        DB::beginTransaction();

        // Verify the order detail and its fulfillment status
        $orderDetail = OrderDetail::where('uuid', $request->order_detail_id)
            ->where('order_id', $request->order_id)
            ->first();

        if (!$orderDetail) {
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'Order detail not found',
            ], Response::HTTP_NOT_FOUND);
        }

        // Check if the order detail has been fulfilled
        if (is_null($orderDetail->fulfilled_status) || $orderDetail->fulfilled_status == 0) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'Cannot create return for unfulfilled item',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Check if the return quantity is valid
        if ($request->qty > $orderDetail->product_qty) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'Return quantity cannot exceed ordered quantity',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Calculate existing returns for this order detail
        $existingReturnQty = ReturnExchange::where('order_detail_id', $request->order_detail_id)
            ->sum('qty');
        
        $availableForReturn = $orderDetail->product_qty - $existingReturnQty;
        
        if ($request->qty > $availableForReturn) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'Return quantity exceeds available quantity. Available: ' . $availableForReturn,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Calculate return price and fees
        $returnPrice = $orderDetail->product_price * $request->qty;
        $restockingFeeAmount = 0;
        
        if ($request->has('restocking_fees') && $request->restocking_fees > 0) {
            $restockingFeeAmount = ($returnPrice * $request->restocking_fees) / 100;
        }
        
        $returnShippingFees = $request->return_shipping_fees ?? 0;
        $expectedReturn = $returnPrice - $restockingFeeAmount - $returnShippingFees;

        // Prepare exchange products data if provided
        $exchangeProducts = null;
        if ($request->has('exchange_products') && is_array($request->exchange_products)) {
            $exchangeProducts = json_encode($request->exchange_products);
        }

        // Create return record
        $return = ReturnExchange::create([
            'uuid' => Str::uuid(),
            'auth_id' => Auth::user()->uuid,
            'order_id' => $request->order_id,
            'order_detail_id' => $request->order_detail_id,
            'qty' => $request->qty,
            'reason_for_return' => $request->reason_for_return,
            'return_file' => $request->return_file,
            'return_url' => $request->return_url,
            'tracking_number' => $request->tracking_number,
            'shipping_carrier' => $request->shipping_carrier,
            'exchange_products' => $exchangeProducts,
            'return_shipping_fees' => $returnShippingFees,
            'restocking_fees' => $request->restocking_fees,
            'expected_return' => $expectedReturn,
            'return_price' => $returnPrice,
        ]);

        // Create timeline entry for the return
        OrderTimeLine::create([
            'uuid' => Str::uuid(),
            'auth_id' => Auth::user()->uuid,
            'order_id' => $request->order_id,
            'message' => 'Return requested for ' . $request->qty . ' item(s) of ' . $orderDetail->product_name,
            'status' => 'return_requested',
        ]);

        // Update order status to reflect return process
        $order = Order::where('uuid', $request->order_id)->first();
        if ($order) {
            // Default to pending
            $order->update([
                'return_status' => 'return_pending'
            ]);
        
            // Then conditionally close it if all items are returned
            $totalOrderedQty = OrderDetail::where('order_id', $request->order_id)->sum('product_qty');
            $totalReturnedQty = ReturnExchange::where('order_id', $request->order_id)->sum('qty');
        
            if ($totalReturnedQty >= $totalOrderedQty) {
                $order->update([
                    'return_status' => 'return_closed',
                    'mark_as_paid' => 3
                ]);
            }
        }
        

        DB::commit();

        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => 'Return request created successfully',
        ], Response::HTTP_OK);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => 'Failed to create return request',
            'error' => $e->getMessage()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

public function restock_return(Request $request)
{
    $validator = Validator::make($request->all(), [
        'return_id' => 'required|exists:return_exchanges,uuid',
        'location_id' => 'nullable|exists:locations,uuid', // Optional: restock to a specific location
        'restock_qty' => 'required|integer|min:1', // Required: specify quantity to restock
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'errors' => $validator->errors()
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    try {
        DB::beginTransaction();

        // Get the return record
        $return = ReturnExchange::where('uuid', $request->return_id)->lockForUpdate()->firstOrFail();
        
        // Check if the return is already fully restocked
        $alreadyRestockedQty = $return->restocked_qty ?? 0;
        $remainingQty = $return->qty - $alreadyRestockedQty;
        
        if ($remainingQty <= 0) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'This return has already been fully restocked',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Validate requested restock quantity doesn't exceed remaining quantity
        if ($request->restock_qty > $remainingQty) {
            return response()->json([
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'Restock quantity exceeds remaining quantity. Maximum available: ' . $remainingQty,
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Get order detail for this return
        $orderDetail = OrderDetail::where('uuid', $return->order_detail_id)->firstOrFail();
        
        // Get the correct product stock to update
        $productStock = null;
        if ($orderDetail->variant_id) {
            $productStock = ProductStock::where('uuid', $orderDetail->variant_id)->lockForUpdate()->first();
        } elseif ($orderDetail->product_id) {
            $productStock = ProductStock::where('product_id', $orderDetail->product_id)->lockForUpdate()->first();
        }
        
        if (!$productStock) {
            DB::rollBack();
            return response()->json([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'Product stock not found',
            ], Response::HTTP_NOT_FOUND);
        }

        // Determine the location for restocking
        $locationId = $request->location_id ?? $productStock->location_id;
        
        // Create inventory record for the restocked item
        Inventory::create([
            'uuid' => Str::uuid(),
            'auth_id' => Auth::user()->uuid,
            'stock_id' => $productStock->uuid,
            'sku' => $productStock->sku,
            'order_id' => $return->order_id,
            'status' => 'available',
            'reason' => 'return_restock',
            'location_id' => $locationId,
            'price' => $productStock->price,
            'product_id' => $orderDetail->product_id,
            'qty' => $request->restock_qty,
        ]);
        
        // Calculate new restocked total
        $newRestockedTotal = $alreadyRestockedQty + $request->restock_qty;
        
        // Determine restock status based on quantities
        $restockStatus = ($newRestockedTotal < $return->qty) ? 'partial' : 'completed';
        
        // Update return record with restock information
        $return->update([
            'restock_status' => $restockStatus,
            'restocked_qty' => $newRestockedTotal,
            'last_restocked_at' => now(),
            'last_restocked_by' => Auth::user()->uuid,
        ]);
        
        // Add timeline entry
        OrderTimeLine::create([
            'uuid' => Str::uuid(),
            'auth_id' => Auth::user()->uuid,
            'order_id' => $return->order_id,
            'message' => $request->restock_qty . ' item(s) from return have been restocked (' . 
                         $newRestockedTotal . ' of ' . $return->qty . ' total)',
            'status' => 'return_' . $restockStatus . '_restocked',
        ]);

        DB::commit();

        // Prepare response with remaining quantity information
        $remainingAfterRestock = $return->qty - $newRestockedTotal;
        $message = 'Return items successfully restocked';
        
        if ($remainingAfterRestock > 0) {
            $message .= '. ' . $remainingAfterRestock . ' items still pending restock';
        } else {
            $message .= '. All items have been restocked';
        }

        return response()->json([
            'status' => Response::HTTP_OK,
            'message' => $message,
        ], Response::HTTP_OK);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => 'Failed to restock return items',
            'error' => $e->getMessage()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

}