<?php

namespace App\Http\Controllers\API\Ecommerce;

use Carbon\Carbon;
use App\Models\Ecommerce\Inventory;
use App\Models\Ecommerce\POTimeLine;
use App\Models\Ecommerce\POReceiving;
use Illuminate\Support\Str;
use App\Models\Ecommerce\ProductStock;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use App\Models\Ecommerce\PurchaseOrder;
use App\Models\Ecommerce\PurchaseComments;
use App\Models\Ecommerce\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class PurchaseOrderController extends Controller
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
            $purchaseOrders = PurchaseOrder::select('uuid','po_number','supplier_id','warehouse_id','payment_term_id','supplier_currency_id','ship_date','ship_carrier_id','tracking_number','reference_number','note_to_supplier','tags','status','total_tax','total_amount')->with('supplier:uuid,company','warehouse:uuid,location_name','paymentterm:uuid,name','currency:uuid,name,symbol,code','shipcarrier:uuid,name','purchaseOrderitemReceiving:uuid,po_id,received_date,accept_qty,reject_qty')->orderBy('id', 'desc');
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $purchaseOrders = $purchaseOrders->where('auth_id', Auth::user()->uuid);
                }
            }else{
                if (Auth::user()->hasPermission('viewglobal')) {
                    $purchaseOrders = $purchaseOrders;
                } else {
                    return response()->json([
                        'message' => 'You do not have permission to view this menu'
                    ], Response::HTTP_FORBIDDEN);
                }
            }
            $purchaseOrders = $purchaseOrders->get();
            return response()->json([
                'status_code'=>200,
                'permissions' => $permissions,
                'data'=>$purchaseOrders
            ],200);

        }catch (\Exception $e) { 
            // Handle general exceptions
            Log::error('Purchase Order List Error:'.$e->getMessage());
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
        //
        try {
            //code...
            $validator = Validator::make($request->all(),[
                'warehouse_id' => [
                    'required',
                    'max:255',
                    'regex:/^[^<>]+$/',
                ],
            ], [
                'warehouse_id.required' => 'The warehouse field is required.',
            ]);
    
            if($validator->fails()) {            
                $message = $validator->messages();
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => strval($message)
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $costSummary = $request->cost_summary ? json_encode($request->cost_summary) : json_encode([]);   
            $data = [
                "supplier_id" => $request->supplier_id,
                "warehouse_id" => $request->warehouse_id,
                "payment_term_id" => $request->payment_term_id,
                "supplier_currency_id" => $request->supplier_currency_id,
                "ship_date" => $request->ship_date,
                "ship_carrier_id" => $request->ship_carrier_id,
                "tracking_number" => $request->tracking_number,
                "reference_number" => $request->reference_number,
                "note_to_supplier" => $request->note_to_supplier,
                "cost_summary" => $costSummary,
                "tags" => $request->tags,
                "status" => $request->status,
                "total_shipping" => $request->total_shipping,
                "total_tax" => $request->total_tax,
                "total_amount" => $request->total_amount,
            ];
            
            DB::beginTransaction();
            $purchaseOrder = PurchaseOrder::create($data);
            // Add items to the PO
            if ($purchaseOrder) {
                foreach ($request->items as $item) {
                    $dataItem = [
                        'po_id' => $purchaseOrder->uuid,
                        'product_id' => $item['product_id'],
                        'variant_id' => $item['variant_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'sku' => $item['sku'],
                        'tax' => $item['tax'],
                        'total_amount' => $item['total_amount'],
                    ];
                    $purchaseOrderItem = PurchaseOrderItem::create($dataItem);
                }
                if ($request->has("timeline")) {
                    POTimeLine::create([
                        "po_id" => $purchaseOrder->uuid,
                        "message" => json_encode($request->timeline),
                    ]);
                }
                DB::commit();
                if ($purchaseOrderItem) {
                    return response()->json([
                        'status_code'=>200,
                        'message'=>"Purchase Order added successfully",
                    ], 200);
                }else{
                    return response()->json([
                        'status_code'=>500,
                        'message'=>$this->get_message('server_error'),
                    ], 500);
                } 
            }
        } catch (\Throwable $e) {
            dd($e);
            DB::rollBack();
            Log::error(['Purchase Order Store Error'=>$e->getMessage()]);
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

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
        try {
            $purchaseOrder = PurchaseOrder::findByUuid($id);
            if ($purchaseOrder) {
                try {
                    $po_timeline = $purchaseOrder->po_timeline()->orderByDesc('created_at')->get(['created_at', 'message'])
                        ->map(function ($items, $date) {
                            return [
                                'time' => Carbon::parse($items->created_at)->diffInMinutes() < 1
                                ? 'Just now '
                                : Carbon::parse($items->created_at)->diffForHumans(),
                                'date' => Carbon::parse($items->created_at)->format('F-j'),
                                'messages' => $items->message,
                            ];
                        })->values()
                        ->toJson();
                } catch (\Throwable $e) {
                    Log::error('Purchase Order Timeline Error', ['error' => $e->getMessage()]);
                    $po_timeline = "[]";
                }
                $purchaseOrderData = [
                    'uuid' => $purchaseOrder->uuid,
                    'po_number' => $purchaseOrder->po_number,
                    'supplier_id' => $purchaseOrder->supplier_id,
                    'warehouse_id' => $purchaseOrder->warehouse_id,
                    'payment_term_id' => $purchaseOrder->payment_term_id,
                    'supplier_currency_id' => $purchaseOrder->supplier_currency_id,
                    'ship_date' => $purchaseOrder->ship_date,
                    'ship_carrier_id' => $purchaseOrder->ship_carrier_id,
                    'tracking_number' => $purchaseOrder->tracking_number,
                    'reference_number' => $purchaseOrder->reference_number,
                    'note_to_supplier' => $purchaseOrder->note_to_supplier,
                    'tags' => $purchaseOrder->tags,
                    'status' => $purchaseOrder->status,
                    'total_tax' => $purchaseOrder->total_tax,
                    'total_shipping' => $purchaseOrder->total_shipping,
                    'total_amount' => $purchaseOrder->total_amount,
                    "cost_summary" => $purchaseOrder->cost_summary,
                    'purchaseOrderItems' => $purchaseOrder->purchaseOrderitems->makeHidden(['created_at', 'updated_at'])->map(function ($item) {
                        return [
                            'product_id' => $item->product_id,
                            'variant_id' => $item->variant_id,
                            'product' => $item->product ? $item->product->only(['uuid', 'name','thumbnail_img']) : null,
                            'variant' => $item->variant ? $item->variant->only(['uuid', 'variant','sku','image']) : null,
                            'supplier_sku' => $item->sku,
                            'unit_price' => $item->unit_price,
                            'quantity' => $item->quantity,
                            'tax' => $item->tax,
                            'total_amount' => $item->total_amount,
                            'accepted' => $item->purchaseOrderitemReceiving->sum('accept_qty'),
                            'unreceived' => $item->purchaseOrderitemReceiving->sum('reject_qty')
                        ];
                    }),
                    'supplier' => $purchaseOrder->supplier ? [
                    'uuid'         => $purchaseOrder->supplier->uuid,
                    'company'      => $purchaseOrder->supplier->company,
                    'contact_name' => $purchaseOrder->supplier->contact_name,
                    'address'      => $purchaseOrder->supplier->address,
                    'email'      => $purchaseOrder->supplier->email,
                    'phone_number' => $purchaseOrder->supplier->phone_number,
                    'apart_suite' => $purchaseOrder->supplier->apart_suite,
                    'city' => $purchaseOrder->supplier->city,
                    'postal_code' => $purchaseOrder->supplier->postal_code,
                    'country'      => $purchaseOrder->supplier->country?->name, 
                    ] : null,
                    'warehouse' => $purchaseOrder->warehouse ? $purchaseOrder->warehouse->only(['uuid', 'location_name']) : null,
                    'paymentterm' => $purchaseOrder->paymentterm ? $purchaseOrder->paymentterm->only(['uuid', 'name']) : null,
                    'shipcarrier' => $purchaseOrder->shipcarrier ? $purchaseOrder->shipcarrier->only(['uuid', 'name']) : null,
                    'currency' => $purchaseOrder->currency ? $purchaseOrder->currency->only(['uuid', 'name', 'symbol', 'code']) : null,
                    'po_receving' => $purchaseOrder->purchaseOrderitemReceiving->count() > 0 ? true : false,
                    "po_timeline" => $po_timeline != [] ? json_decode($po_timeline) : [],
                    "po_comment" => $purchaseOrder->po_comment,
                ];
                return response()->json([
                    'status_code' => 200,
                    'data' => $purchaseOrderData,
                ], 200);
            } else {
                return response()->json([
                    'status_code' => 404,
                    'message' => $this->get_message('not_found'),
                ], 404);
            }
        } catch (\Throwable $e) {
            Log::error('Purchase Order Edit Error', ['error' => $e->getMessage()]);
            return response()->json([
                'status_code' => 500,
                'message' => $this->get_message('server_error'),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        try {
            //code...
            $validator = Validator::make($request->all(),[
                'warehouse_id' => [
                    'required',
                    'max:255',
                    'regex:/^[^<>]+$/',
                ],
            ], [
                'supplier_id.required' => 'The supplier field is required.',
                'warehouse_id.required' => 'The warehouse field is required.',
            ]);
    
            if($validator->fails()) {            
                $message = $validator->messages();
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => strval($message)
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $purchaseOrder = PurchaseOrder::findByUuid($id);
            if ($purchaseOrder) {
                $costSummary = $request->cost_summary ? json_encode($request->cost_summary) : [];
                $data = [
                    "supplier_id" => $request->supplier_id,
                    "warehouse_id" => $request->warehouse_id,
                    "payment_term_id" => $request->payment_term_id,
                    "supplier_currency_id" => $request->supplier_currency_id,
                    "ship_date" => $request->ship_date,
                    "ship_carrier_id" => $request->ship_carrier_id,
                    "tracking_number" => $request->tracking_number,
                    "reference_number" => $request->reference_number,
                    "note_to_supplier" => $request->note_to_supplier,
                    "cost_summary" => $costSummary,
                    "tags" => $request->tags,
                    "status" => $request->status,
                    "total_shipping" => $request->total_shipping,
                    "total_tax" => $request->total_tax,
                    "total_amount" => $request->total_amount,
                ];
                DB::beginTransaction();
                $purchaseOrderUpdate = $purchaseOrder->update($data);
                // Add items to the PO
                PurchaseOrderItem::where('po_id', $purchaseOrder->uuid)->delete();

                if ($purchaseOrderUpdate) {
                    foreach ($request->items as $item) {
                        $dataItem = [
                            'po_id' => $purchaseOrder->uuid,
                            'product_id' => $item['product_id'],
                            'variant_id' => $item['variant_id'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unit_price'],
                            'sku' => $item['sku'],
                            'tax' => $item['tax'],
                            'total_amount' => $item['total_amount'],
                        ];
                        $purchaseOrderItem = PurchaseOrderItem::create($dataItem);
                    }
                    if ($request->has("timeline")) {
                        POTimeLine::create([
                            "po_id" => $purchaseOrder->uuid,
                            "message" => json_encode($request->timeline),
                        ]);
                    }
                    DB::commit();
                    if ($purchaseOrderItem) {
                        return response()->json([
                            'status_code'=>200,
                            'message'=>"Purchase Order updated successfully",
                        ], 200);
                    }else{
                        return response()->json([
                            'status_code'=>200,
                            'message'=>$this->get_message('server_error'),
                        ], 500);
                    } 
                }
                
            }else{
                return response()->json([
                    'status_code'=>404,
                    'message'=>$this->get_message('not_found'),
                ], 404);
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error(['Purchase Order Store Error'=>$e->getMessage()]);
            return response()->json([
                'status_code'=>500,
                'message'=>$this->get_message('server_error'),
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
            $purchaseOrder = PurchaseOrder::findByUuid($id);
            DB::beginTransaction();
            if ($purchaseOrder) {
                $purchaseOrder->purchaseOrderitems()->delete();
                $purchaseOrder->purchaseOrderitemReceiving()->delete();
                $purchaseOrder->po_timeline()->delete();
                $purchaseOrder->po_comment()->delete();
                $purchaseOrder->delete();
                DB::commit();
                return response()->json([
                    'status_code'=>200,
                    'message' => "Purchase Order delete successfully",
                ],200);
            }else{
                return response()->json([
                    'status_code' => 404,
                    'message' => $this->get_message('not_found'),
                ], 404);
            }
          
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error(['Purchase Order Delete Error'=>$e->getMessage()]);
            return response()->json([
                'status_code'=>500,
                'message'=>$this->get_message('server_error'),
            ], 500);
        }
    }

    public function updateStatus(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required', // Ensure status is either Ordered or Received
        ],[
            'status.required' => 'The status field is required.',
            'status.in' => 'Purchase Order status must be either Ordered or Received',
        ]);
    
        try {
            $purchaseOrder = PurchaseOrder::findByUuid($id);
            if ($purchaseOrder) {
                $purchaseOrder->status = $request->status;
                $purchaseOrder->save();
                if ($request->has("timeline")) {
                    POTimeLine::create([
                        "po_id" => $purchaseOrder->uuid,
                        "message" => json_encode($request->timeline),
                    ]);
                }
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

    public function po_receiving(string $id)
    {
        try {
            $purchaseOrder = PurchaseOrder::findByUuid($id);
            if ($purchaseOrder) {
                if ($purchaseOrder->status == 'Draft') {
                    return response()->json([
                        'status_code' => 403,
                        'message' => 'Purchase Order status is not Ordered / Received / Partial',
                    ], 403);
                }
                $purchaseOrderData = [
                    'po_uuid' => $purchaseOrder->uuid,
                    'po_number' => $purchaseOrder->po_number,
                    'total_qty' => $purchaseOrder->purchaseOrderitems->sum('quantity'),
                    'purchaseOrderItems' => $purchaseOrder->purchaseOrderitems->makeHidden(['created_at', 'updated_at'])->map(function ($item) {
                        return [
                            'product_uuid' => $item->product_id,
                            'variant_uuid' => $item->variant_id,
                            'po_item_id' => $item->uuid,
                            'product' => $item->product ? $item->product->only(['uuid', 'name', 'slug', 'thumbnail_img']) : null,
                            'variant' => $item->variant ? $item->variant->only(['uuid', 'variant', 'sku', 'image']) : null,
                            'supplier_sku' => $item->sku,
                            'unit_price' => $item->unit_price,
                            'sku' => $item->sku,
                            'quantity' => $item->quantity,
                            'tax' => $item->tax,
                            'total_amount' => $item->total_amount,
                            'accepted' => $item->purchaseOrderitemReceiving->sum('accept_qty'),
                            'unreceived' => $item->purchaseOrderitemReceiving->sum('reject_qty'),
                            'is_product_deleted' => is_null($item->product), // Check if product is null
                            'is_variant_deleted' => is_null($item->variant), // Check if variant is deleted
                        ];
                    }),
                ];
                return response()->json([
                    'status_code' => 200,
                    'data' => $purchaseOrderData,
                ], 200);
            } else {
                return response()->json([
                    'status_code' => 404,
                    'message' => $this->get_message('not_found'),
                ], 404);
            }
        } catch (\Throwable $e) {
            Log::error('Purchase Order Receiving List: ' . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function po_receiving_add(Request $request,string $id){
        try {
            DB::beginTransaction();
            $purchaseOrder = PurchaseOrder::findByUuid($id);
            $data = [];
            if ($purchaseOrder) {
                if ($purchaseOrder->status == 'Draft') {
                    return response()->json([
                        'status_code' => 403,
                        'message' => 'Purchase Order status is not Ordered / Received / Partial',
                    ], 403);
                }
                foreach ($request->items as $key => $item) {
                    $data = [
                        'po_id'=>$purchaseOrder->uuid,
                        'po_item_id'=>$item['po_item_id'],
                        'product_id' => $item['product_id'],
                        'variant_id' => $item['variant_id'],
                        'sku' => $item['sku'],
                        'accept_qty' => $item['accept_qty'],
                        'reject_qty' => $item['reject_qty'],
                        'received_date' => date("Y-m-d")
                    ];
                    $poResceiving = POReceiving::create($data);
                    if ($poResceiving) {
                        $inventory = new Inventory();
                        $inventory->uuid = Str::uuid();
                        $inventory->po_id = $purchaseOrder->uuid;
                        $inventory->po_item_id = $poResceiving->uuid;
                        $inventory->product_id = $poResceiving->product_id; 
                        $inventory->stock_id = $poResceiving->variant_id; // ProductStock ID
                        $inventory->location_id = $purchaseOrder->warehouse_id;
                        $inventory->status = 'adjust';
                        $inventory->reason = 'po_receving'; 
                        $inventory->sku = $poResceiving->sku;
                        $inventory->price = $purchaseOrder->purchaseOrderitems->where("uuid",$poResceiving->po_item_id)->first()->unit_price;
                        $inventory->qty = $poResceiving->accept_qty;
                        $inventory->auth_id = $poResceiving->auth_id;
                        $inventory->save();
                        $productStock = ProductStock::where("uuid",$inventory->stock_id)->first();
                        $productStock->qty = $productStock->qty + $inventory->qty;
                        $productStock->save();
                    }
                }
                // $purchaseOrder->status = 'Partial';
                $purchaseOrder->save();
                if ($request->has("timeline")) {
                    POTimeLine::create([
                        "po_id" => $purchaseOrder->uuid,
                        "message" => json_encode($request->timeline),
                    ]);
                }
                DB::commit();
                return response()->json([
                    'status_code'=>200,
                    'message'=>"PO Receiving store successfully",
                ], 200);
            }else{
                return response()->json([
                    'status_code'=>404,
                    'message'=>$this->get_message('not_found'),
                ], 404);
            } 
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Purchase Order Receiving Error: ".$th->getMessage());
            return response()->json([
                'status_code'=>500,
                'message'=>$this->get_message('server_error'),
            ], 500);
        }
    }

    public function add_po_order_comment(Request $request, $poOrderUuid)
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
            $order = PurchaseOrder::where('uuid', $poOrderUuid)->first();

            if (!$order) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            // Create the comment
            PurchaseComments::create([
                'uuid' => Str::uuid(),
                'purchase_order_id' => $poOrderUuid,
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
    public function get_po_order_comments($poOrderUuid)
    {
        try {
            // Check if order exists
            $purchase = PurchaseOrder::where('uuid', $poOrderUuid)->first();

            if (!$purchase) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            // Retrieve comments with user information
            $comments = PurchaseComments::where('purchase_order_id', $poOrderUuid)
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

    /**
     * Update an existing comment
     *
     * @param Request $request
     * @param string $commentUuid
     * @return \Illuminate\Http\JsonResponse
     */

     public function edit_po_order_comment($poOrderUuid){

        
        try {
            
            $edit_po_order = PurchaseComments::where('uuid', $poOrderUuid)->first();
            $edit_order_translation = PurchaseComments::where('uuid', $poOrderUuid)->first();

            if($edit_po_order)
            {

                return response()->json([

                    'status_code' => Response::HTTP_OK,
                    'data' => $edit_po_order,

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


    public function update_po_order_comment(Request $request, $poOrderUuid)
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
            $comment = PurchaseComments::where('uuid', $poOrderUuid)
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
    public function delete_po_order_comment($poOrderUuid)
    {
        try {
            // Find the comment
            $comment = PurchaseComments::where('uuid', $poOrderUuid)
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
}
