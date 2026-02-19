<?php

namespace App\Http\Controllers\API\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Inventory;
use App\Models\Ecommerce\TIReceiving;
use App\Models\Ecommerce\ProductStock;
use App\Models\Ecommerce\TransferInventory;
use App\Models\Ecommerce\TransferInventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Traits\MessageTrait;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class TransferInventoryController extends Controller
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
    public function get_transferinventory()
    {
        try{
            $menuUuid = request()->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);
            $transferInventorys = TransferInventory::select('uuid','ti_number','origin_location_id','destination_location_id','estimated_date','ship_carrier_id','tracking_number','reference_number','note_to_supplier','tags','status')
            ->with('warehouse:uuid,warehouse_name','currency:uuid,name,symbol,code','shipcarrier:uuid,name')->orderBy('id', 'desc');
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $transferInventorys = $transferInventorys->where('auth_id', Auth::user()->uuid);
                }
            }else{
                if (Auth::user()->hasPermission('viewglobal')) {
                    $transferInventorys = $transferInventorys;
                } else {
                    return response()->json([
                        'message' => 'You do not have permission to view this menu'
                    ], Response::HTTP_FORBIDDEN);
                }
            }
            $transferInventorys = $transferInventorys->get();

            return response()->json([
                'status_code'=>200,
                'permissions' => $permissions,
                'data'=>$transferInventorys
            ],200);

        }catch (\Exception $e) { 
            // Handle general exceptions
           // dd($e);
            Log::error('Transfer Inventory List Error:'.$e->getMessage());
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
                'origin_location_id' => [
                    'required',
                    'max:255',
                    'regex:/^[^<>]+$/',
                ],
                'destination_location_id' => [
                    'required',
                    'max:255',
                    'regex:/^[^<>]+$/',
                ],
            ], [
                'origin_location_id.required' => 'The Origin field is required.',
                'destination_location_id.required' => 'The Destination field is required.',
            ]);
    
            if($validator->fails()) {            
                $message = $validator->messages();
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => strval($message)
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
    
            $data = [
                "origin_location_id" => $request->origin_location_id,
                "destination_location_id" => $request->destination_location_id,  
                "estimated_date" => $request->estimated_date,
                "ship_carrier_id" => $request->ship_carrier_id,
                "note_to_supplier" => $request->note_to_supplier,
                "tracking_number" => $request->tracking_number,
                "reference_number" => $request->reference_number, 
                "tags" => $request->tags,
                "status" => $request->status,
                 
            ];
            DB::beginTransaction();
            $transferInventory = TransferInventory::create($data);
            // Add items to the TI
            if ($transferInventory) {
                foreach ($request->items as $item) {
                    $dataItem = [
                        'ti_id' => $transferInventory->uuid,
                        'product_id' => $item['product_id'],
                        'variant_id' => $item['variant_id'] ?? null,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'sku' => $item['sku'] ?? null,
                        'tax' => $item['tax'] ?? 0,
                        'total_amount' => $item['total_amount'],
                    ];
                    $transferInventoryItem = TransferInventoryItem::create($dataItem);
                    DB::commit();
                }
                if ($transferInventoryItem) {
                    return response()->json([
                        'status_code'=>200,
                        'message'=>"Transfer Inventory added successfully",
                    ], 200);
                }else{
                    return response()->json([
                        'status_code'=>200,
                        'message'=>$this->get_message('server_error'),
                    ], 500);
                } 
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error(['Transfer Inventory Store Error'=>$e->getMessage()]);
            return response()->json([
                'status_code'=>500,
                'message'=>$e->getMessage(),
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
            $transferInventory = TransferInventory::findByUuid($id);
            if ($transferInventory) {
                $transferInventoryData = [
                    'uuid' => $transferInventory->uuid,
                    'origin_location_id' => $transferInventory->origin_location_id,
                    'destination_location_id' => $transferInventory->destination_location_id, 
                    'ship_carrier_id' => $transferInventory->ship_carrier_id,
                    'tracking_number' => $transferInventory->tracking_number,
                    'reference_number' => $transferInventory->reference_number,
                    'note_to_supplier' => $transferInventory->note_to_supplier,
                    'estimated_date' => $transferInventory->estimated_date,
                    'tags' => $transferInventory->tags,
                    'status' => $transferInventory->status,
                    
                    'transferInventoryItems' => $transferInventory->transferInventoryitems->makeHidden(['created_at', 'updated_at'])->map(function ($item) {
                        return [ 
                            'product' => $item->product ? $item->product->only(['uuid', 'name','thumbnail_img']) : null,
                            'variant' => $item->variant ? $item->variant->only(['uuid', 'variant','image']) : null,
                            'unit_price' => $item->unit_price,
                            'quantity' => $item->quantity,
                            'tax' => $item->tax,
                            'total_amount' => $item->total_amount
                        ];
                    }),
                    
                ];
              //  dd($transferInventory);
                return response()->json([
                    'status_code' => 200,
                    'data' => $transferInventoryData,
                ], 200);
            }else{
                return response()->json([
                    'status_code' => 404,
                    'message' => $this->get_message('not_found'),
                ], 404);
            }
        } catch (\Throwable $e) {
            Log::error('Transfer Inventory Edit Error:',$e->getMessage());
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
        //
        try {
            //code...
            $validator = Validator::make($request->all(),[
                'origin_location_id' => [
                    'required',
                    'max:255',
                    'regex:/^[^<>]+$/',
                ],
            ], [
                'destination_location_id.required' => 'The destination location is required.',
                'origin_location_id.required' => 'The origin location is required.',
            ]);
    
            if($validator->fails()) {            
                $message = $validator->messages();
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => strval($message)
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            $transferInventory = TransferInventory::findByUuid($id);
            if ($transferInventory) {
                
                $data = [ 
                    "origin_location_id" => $request->origin_location_id,
                    "destination_location_id" => $request->destination_location_id, 
                    "esimated_date" => $request->esimated_date,
                    "note_to_supplier" => $request->note_to_supplier,
                    "ship_carrier_id" => $request->ship_carrier_id,
                    "tracking_number" => $request->tracking_number,
                    "reference_number" => $request->reference_number, 
                    "tags" => $request->tags,
                    "status" => $request->status,
                    
                ];
                DB::beginTransaction();
                $transferInventoryUpdate = $transferInventory->update($data);
                // Add items to the TI
                TransferInventoryItem::where('ti_id', $transferInventory->uuid)->delete();

                if ($transferInventoryUpdate) {
                    foreach ($request->items as $item) {
                        $dataItem = [
                            'ti_id' => $transferInventory->uuid,
                            'product_id' => $item['product_id'],
                            'variant_id' => $item['variant_id'],
                            'quantity' => $item['quantity'], 
                            'unit_price' => 0,
                            'tax' => 0,
                            'total_amount' => 0,
                        ];
                        $transferInventoryItem = TransferInventoryItem::create($dataItem);
                    }
                    DB::commit();
                    if ($transferInventoryItem) {
                        return response()->json([
                            'status_code'=>200,
                            'message'=>"Transfer Inventory updated successfully",
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
            Log::error(['Transfer Inventory Store Error'=>$e->getMessage()]);
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
            $transferInventory = TransferInventory::findByUuid($id);
            DB::beginTransaction();
            if ($transferInventory) {
                $transferInventory->transferInventoryitems()->delete();
                $transferInventory->delete();
                DB::commit();
                return response()->json([
                    'status_code'=>200,
                    'message' => "Transfer Inventory delete successfully",
                ],200);
            }else{
                return response()->json([
                    'status_code' => 404,
                    'message' => $this->get_message('not_found'),
                ], 404);
            }
          
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error(['Transfer Inventory Delete Error'=>$e->getMessage()]);
            return response()->json([
                'status_code'=>500,
                'message'=>$this->get_message('server_error'),
            ], 500);
        }
    }

    public function ti_receiving(string $id){
        try {
            //code...
            $transferInventory = TransferInventory::findByUuid($id);
            if ($transferInventory) {
                $transferInventoryData = [
                    'ti_uuid' => $transferInventory->uuid,
                    'ti_number' => $transferInventory->ti_number,
                    'transferInventoryItems' => $transferInventory->transferInventoryitems->makeHidden(['created_at', 'updated_at'])->map(function ($item) {
                        $acceptQty = $item->tiReceivings->sum('accept_qty');
                        $rejectQty = $item->tiReceivings->sum('reject_qty');

                        return [ 
                            'ti_item_id' => $item->uuid,
                            'product' => $item->product ? $item->product->only(['uuid', 'name','thumbnail_img']) : null,
                            'variant' => $item->variant ? $item->variant->only(['uuid', 'variant','image']) : null, 
                            'quantity' => $item->quantity,
                            'accept_qty' => $acceptQty,
                            'reject_qty' => $rejectQty,
                        ];
                    }),
                ];
                return response()->json([
                    'status_code' => 200,
                    'data' => $transferInventoryData,
                ], 200);
            }else{
                return response()->json([
                    'status_code' => 404,
                    'message' => $this->get_message('not_found'),
                ], 404);
            }
        } catch (\Throwable $e) {
            //throw $th;
            Log::error('Transfer Inventory Receiving List:'.$e->getMessage());
            return response()->json([
                'status_code'=>500,
                'message'=>$this->get_message('server_error'),
            ], 500);
        }
    }

    public function ti_receiving_add(Request $request,string $id){

        try {
            // DB::beginTransaction();
            $transferInventory = TransferInventory::findByUuid($id);
            $data = [];
            if ($transferInventory) {
                foreach ($request->items as $key => $item) {
                    $data = [
                        'ti_id'=>$transferInventory->uuid,
                        'ti_item_id'=>$item['ti_item_id'],
                        'product_id' => $item['product_id'],
                        'variant_id' => $item['variant_id'],
                        'sku' => $item['sku'],
                        'accept_qty' => $item['accept_qty'],
                        'reject_qty' => $item['reject_qty'],
                        'received_date' => date("Y-m-d")
                    ];
                    $tiResceiving = TIReceiving::create($data);
                    if ($tiResceiving) {

                        //origin transaction
                        $inventory = new Inventory();
                        $inventory->uuid = Str::uuid();
                        $inventory->ti_id = $transferInventory->uuid;
                        $inventory->ti_item_id = $tiResceiving->uuid;
                        $inventory->product_id = $tiResceiving->product_id; 
                        $inventory->stock_id = $tiResceiving->variant_id; // ProductStock ID
                        $inventory->location_id = $transferInventory->origin_location_id;
                        $inventory->status = 'adjust';
                        $inventory->reason = 'ti_transfer'; 
                        $inventory->sku = $tiResceiving->sku;
                        // $inventoryItem = $transferInventory->transferInventoryitems->where("uuid", $tiResceiving->ti_item_id)->first();
                        // if ($inventoryItem) {
                        //     $inventory->price = $inventoryItem->unit_price;
                        // } else {
                        //     $inventory->price = 0; 
                        //     Log::warning("Missing inventory item for ti_item_id: " . $tiResceiving->ti_item_id);
                        // }
                        $inventory->qty = '-' .$tiResceiving->accept_qty;
                        $inventory->auth_id = $tiResceiving->auth_id;
                        $inventory->save();
                        $productStock = ProductStock::where("uuid", $inventory->stock_id)->first();
                        if ($productStock) {
                            $productStock->qty = $productStock->qty - $inventory->qty;
                            $productStock->save();
                        } else {
                            Log::warning("Missing product stock for stock_id: " . $inventory->stock_id);
                        }
                        //origin transaction end

                        //destination transaction
                        $destination = ProductStock::where("product_id", $inventory->product_id)->where("location_id", $transferInventory->destination_location_id)->first();
                        if($destination){
                        $inventory = new Inventory();
                        $inventory->uuid = Str::uuid();
                        $inventory->ti_id = $transferInventory->uuid;
                        $inventory->ti_item_id = $tiResceiving->uuid;
                        $inventory->product_id = $tiResceiving->product_id; 
                        $inventory->stock_id = $tiResceiving->variant_id; // ProductStock ID
                        $inventory->location_id = $transferInventory->destination_location_id;
                        $inventory->status = 'adjust';
                        $inventory->reason = 'ti_received'; 
                        $inventory->sku = $tiResceiving->sku;
                        // $inventoryItem = $transferInventory->transferInventoryitems->where("uuid", $tiResceiving->ti_item_id)->first();
                        // if ($inventoryItem) {
                        //     $inventory->price = $inventoryItem->unit_price;
                        // } else {
                        //     $inventory->price = 0; 
                        //     Log::warning("Missing inventory item for ti_item_id: " . $tiResceiving->ti_item_id);
                        // }     
                        $inventory->qty = $tiResceiving->accept_qty;
                        $inventory->auth_id = $tiResceiving->auth_id;
                        $inventory->save();
                    
                        $productStock = ProductStock::where("uuid", $inventory->stock_id)->where("location_id", $transferInventory->origin_location_id)->first();
                        if ($productStock) {
                            $productStock->qty = $productStock->qty + $inventory->qty;
                            $productStock->save();
                        } else {
                            Log::warning("Missing product stock for stock_id: " . $inventory->stock_id);
                        }
                    }else{
                        //new entry in stock 

                    }
                        //destination transaction end
                    }
                }
                $isFullyReceived = true;
        foreach ($transferInventory->transferInventoryitems as $item) {
            $totalAcceptedQty = TIReceiving::where('ti_id', $transferInventory->uuid)
                ->where('ti_item_id', $item->uuid)
                ->sum('accept_qty');
            if ($totalAcceptedQty < $item->quantity) {
                $isFullyReceived = false;
                break;
            }
        }

        $transferInventory->status = $isFullyReceived ? 'transferred' : 'in_progress';
        $transferInventory->save();
                // foreach ($transferInventory->transferInventoryitemReceiving as $key => $transferInventoryitemReceiving) {
                //     $inventory = new Inventory();
                //     $inventory->uuid = Str::uuid();
                //     $inventory->ti_id = $transferInventory->uuid;
                //     $inventory->ti_item_id = $transferInventoryitemReceiving->uuid;
                //     $inventory->product_id = $transferInventoryitemReceiving->product_id; 
                //     $inventory->stock_id = $transferInventoryitemReceiving->variant_id; // ProductStock ID
                //     $inventory->location_id = $transferInventory->warehouse_id;
                //     $inventory->status = 'adjust';
                //     $inventory->reason = 'ti_receving'; 
                //     $inventory->sku = $transferInventoryitemReceiving->sku;
                //     $inventory->price = $transferInventory->transferInventoryitems->where("uuid",$transferInventoryitemReceiving->ti_item_id)->first()->unit_price;
                //     $inventory->qty = $transferInventoryitemReceiving->accept_qty;
                //     $inventory->auth_id = $transferInventoryitemReceiving->auth_id;
                //     $inventory->save();
                //     dd($inventory->qty);
                //     $productStock = ProductStock::where("uuid",$inventory->stock_id)->first();
                //     $productStock->qty = $productStock->qty + $inventory->qty;
                //     $productStock->save();
                // }
                // DB::commit();
                return response()->json([
                    'status_code'=>200,
                    'message'=>"Transfer Inventory Receiving store successfully",
                ], 200);
            }else{
                return response()->json([
                    'status_code'=>404,
                    'message'=>$this->get_message('not_found'),
                ], 404);
            } 
        } catch (\Throwable $th) {
            // DB::rollBack();
            Log::error("Transfer Inventory Receiving Error: ".$th->getMessage());
            return response()->json([
                'status_code'=>500,
                'message'=>$this->get_message('server_error'),
            ], 500);
        }
    }
}
