<?php

namespace App\Http\Controllers\API\Ecommerce;

use DB;
use Hash;
use Mail;
use Session;
use Exception; 
use Carbon\Carbon;
use App\Models\Menu;
use App\Models\Ecommerce\Product;
use App\Models\Ecommerce\Discount;
use Illuminate\Support\Str;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use App\Models\Ecommerce\DiscountTimeLine;
use App\Models\Ecommerce\ProductDiscounts;
use App\Models\Permission_assign;
use App\Services\PermissionService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;


class DiscountController extends Controller
{

    use MessageTrait;
    protected $permissionService;
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function add_discount(Request $request){ 
        $validator = Validator::make($request->all(), [ 
             'code' => 'nullable|regex:/^[a-zA-Z0-9\s\-]+$/', 
        ]);

        if($validator->fails()){ 
            $message = $validator->messages(); 
            return response()->json([ 
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors()) 
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        }

        try { 
            $method = $request->method;
            if($method=='code'){
                $check_if_already = Discount::where('code', $request->code)->get(); 
            }else{ 
                $check_if_already = Discount::where('name', $request->name)->get();} 
            if(count($check_if_already) > 0){ 
                return response()->json([ 
                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => $this->get_message('conflict'), 
                ], Response::HTTP_CONFLICT); // 409 Conflict  
            }else{ 
                $discount = $request->all();
                $discount['uuid'] = Str::uuid();
                $discount['auth_id'] = Auth::user()->uuid;    
                 if ($request->eligibility === 'specific_customers' && !empty($request->specific_customer)) {
                    $discount['specific_customer'] = $request->specific_customer;
                }

                if ($request->eligibility === 'specific_groups' && !empty($request->customer_segments)) {
                    $discount['customer_segments'] = $request->customer_segments;
                }
                DB::beginTransaction();
                //dd($discount);
                $save_discount = Discount::create($discount);  
                if ($save_discount) { 
                    foreach ($request->items as $item) {
                        $discountItem = ProductDiscounts::where('product_id', $item['product_id'])->first();
                        if($discountItem){
                            $discountItem->delete();
                        }
                        $dataItem = [
                            'uuid' => Str::uuid(),
                            'auth_id'=> Auth::user()->uuid,
                            'di_id' => $save_discount->uuid,
                            'product_id' => $item['product_id'],
                            'variant_id' => $item['variant_id'],
                            'collection_id' => $item['collection_id'] ?? null, 
                            'countries_id' => $item['countries_id'] ?? null,  
                            'method' => $save_discount->method,
                            'value' =>  $save_discount->value, 
                            'type' =>  $save_discount->type, 
                            'minimum_shopping' =>  $save_discount->minimum_shopping, 
                            'maximum_discount_amount' =>  $save_discount->maximum_discount_amount, 
                            'customer_buy_product_id' =>  $item['customer_buy_product_id'] ?? null, 
                            'customer_buy_variant_id' =>   $item['customer_buy_variant_id'] ?? null, 
                            'customer_get_product_id' =>   $item['customer_get_product_id'] ?? null, 
                            'customer_get_variant_id' =>   $item['customer_get_variant_id'] ?? null, 
                            'customer_buy_collection_id' =>   $item['customer_buy_collection_id'] ?? null, 
                            'customer_get_collection_id' =>   $item['customer_get_collection_id'] ?? null, 
                        ];
                       //dd($dataItem);
                        $save_discountItem = ProductDiscounts::create($dataItem);
                    
                       // dd($save_discountItem);
                    } 
                   
                }
                DB::commit();
                if($save_discount){  
                    return response()->json([ 
                        'status_code' => Response::HTTP_CREATED,
                        'message' => $this->get_message('add'), 
                    ], Response::HTTP_CREATED); 
                }

            }
            
        
        }catch (QueryException $e) { 
            
           // dd($e);
            // For other SQL errors
            return response()->json([ 
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $e->getMessage(), 
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        

        }catch (\Exception $e) { 
            // Handle general exceptions
           // dd($e);
            DB::rollBack();
            return response()->json([ 
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $e->getMessage(), 
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
        

    }


    public function edit_discount($uuid){  
        try { 
            $edit_discount = Discount::where('uuid', $uuid)->first(); 
            if($edit_discount){    
                    try {
                        $discountItems = $edit_discount->discountItems->makeHidden(['created_at', 'updated_at'])->map(function ($item) {
                        //  dd($item->product);
                            return [
                                'product' => $item->product ? $item->product->only(['uuid', 'name','thumbnail_img']) : null, 
                            ];
                        }); 
                    } catch (\Throwable $e) { 
                        $discountItems = "[]";
                    }
 
                    try {
                        $di_timeline = $edit_discount->di_timeline()->orderByDesc('created_at')->get(['created_at', 'message'])
                            ->groupBy(function($date) {
                                return Carbon::parse($date->created_at)->format('F-j');
                            })
                            ->map(function ($items, $date) {
                                return [
                                    'date' => $date,
                                    'messages' => $items->pluck('message')->toArray(),
                                ];
                            })->values()
                            ->toJson();
                    } catch (\Throwable $e) { 
                        $di_timeline = "[]";
                    }

              
                $edit_discount["discountItems"]= $discountItems;
                $edit_discount["di_timeline"]= $di_timeline; 
                return response()->json([ 
                    'status_code' => Response::HTTP_OK,
                    'data' => $edit_discount, 
                ], Response::HTTP_OK); 
            }else{

                return response()->json([ 
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'), 
                ], Response::HTTP_NOT_FOUND);

            }

        
        }catch (\Exception $e) { 
           // dd($e);
            // Handle general exceptions
            return response()->json([ 
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),  
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }


    }


    public function update_discount(Request $request){
        
        $validator = Validator::make($request->all(), [ 
            'code' => 'nullable|regex:/^[a-zA-Z0-9\s\-]+$/',
        ]);

        if($validator->fails()){ 
            $message = $validator->messages(); 
            return response()->json([ 
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors()) 
            ], Response::HTTP_UNPROCESSABLE_ENTITY); 
        }

        
        try{ 
            $uuid = $request->header('uuid');
            $upd_discount = Discount::where('uuid', $uuid)->first(); 
            if (!$upd_discount) { 
                return response()->json([ 
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'), 
                ], Response::HTTP_NOT_FOUND);
            }
            // Check if code already exists (excluding current discount)
            $method = $request->method;
            if($method=='code'){
                $check_if_already = Discount::where('code', $request->code)
                                      ->where('uuid', '!=', $uuid)
                                      ->get();  
            }else{ 
            $check_if_already = Discount::where('name', $request->name)
                                      ->where('uuid', '!=', $uuid)
                                      ->get();  
             }
            if(count($check_if_already) > 0){ 
                return response()->json([ 
                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => $this->get_message('conflict'), 
                ], Response::HTTP_CONFLICT); // 409 Conflict  
            }

            $upd_discount->name = $request->name;
            $upd_discount->method = $request->method; 
            $upd_discount->code = $request->code; 
            $upd_discount->type = $request->type; 
            $upd_discount->value = $request->value; 
            $upd_discount->applies_to = $request->applies_to; 
            $upd_discount->applies_to_value = $request->applies_to_value; 
            $upd_discount->requirement_type = $request->requirement_type; 
            $upd_discount->requirement_value = $request->requirement_value; 
            $upd_discount->eligibility = $request->eligibility; 
            $upd_discount->eligibility_value = $request->eligibility_value; 
            $upd_discount->minimum_shopping = $request->minimum_shopping; 
            $upd_discount->maximum_discount_amount = $request->maximum_discount_amount; 
            $upd_discount->uses_customer_limit = $request->uses_customer_limit; 
            $upd_discount->apply_on_pos = $request->apply_on_pos; 
            $upd_discount->discount_type = $request->discount_type; 
            $upd_discount->uses_limit = $request->uses_limit; 
            $upd_discount->combination_type = $request->combination_type; 
            $upd_discount->start_date = $request->start_date;  
            $upd_discount->start_time = $request->start_time; 
            $upd_discount->end_date = $request->end_date; 
            $upd_discount->end_time = $request->end_time;  
            $upd_discount->status = $upd_discount->status; 
            $upd_discount->shipping_rate = $request->shipping_rate; 
            $upd_discount->exclude_shipping_rates = $request->exclude_shipping_rates; 
            $upd_discount->customer_buys = $request->customer_buys; 
            $upd_discount->customer_buys_quantity = $request->customer_buys_quantity; 
            $upd_discount->customer_buys_amount = $request->customer_buys_amount; 
            $upd_discount->customer_get_quantity = $request->customer_get_quantity; 
            $upd_discount->customer_get_percentage = $request->customer_get_percentage; 
            $upd_discount->customer_get_amount_off_each = $request->customer_get_amount_off_each; 
            $upd_discount->customer_get_free = $request->customer_get_free; 
            $upd_discount->maximum_number_per_order = $request->maximum_number_per_order; 

            // Handle specific customer eligibility (missing from original update)
            if ($request->eligibility === 'specific_customers' && !empty($request->specific_customer)) {
                $upd_discount->specific_customer = $request->specific_customer;
            } else {
                $upd_discount->specific_customer = null; // Clear if not applicable
            }

            // Handle customer segments eligibility (missing from original update)
            if ($request->eligibility === 'specific_groups' && !empty($request->customer_segments)) {
                $upd_discount->customer_segments = $request->customer_segments;
            } else {
                $upd_discount->customer_segments = null; // Clear if not applicable
            }
            
            DB::beginTransaction();
            $update_discount = $upd_discount->save();
                // Add items
                ProductDiscounts::where('di_id', $upd_discount->uuid)->delete();

                if ($update_discount) {
                    foreach ($request->items as $item) {
                        $discountItem = ProductDiscounts::where('product_id', $item['product_id'])->first();
                        if($discountItem){
                            $discountItem->delete();
                        }
                       
                        $dataItem = [
                            'uuid' => Str::uuid(),
                            'auth_id'=> Auth::user()->uuid,
                            'di_id' => $upd_discount->uuid,
                            'product_id' => $item['product_id'],
                            'variant_id' => $item['variant_id'],
                            'collection_id' => $item['collection_id'] ?? null, // Added null coalescing like in add method
                            'countries_id' => $item['countries_id'] ?? null,  // Added null coalescing like in add method
                            'method' => $upd_discount->method,
                            'value' =>  $upd_discount->value, 
                            'type' =>  $upd_discount->type, 
                            'customer_buy_product_id' =>  $item['customer_buy_product_id'] ?? null, 
                            'customer_buy_variant_id' =>   $item['customer_buy_variant_id'] ?? null, 
                            'customer_get_product_id' =>   $item['customer_get_product_id'] ?? null, 
                            'customer_get_variant_id' =>   $item['customer_get_variant_id'] ?? null, 
                            'customer_buy_collection_id' =>   $item['customer_buy_collection_id'] ?? null, 
                            'customer_get_collection_id' =>   $item['customer_get_collection_id'] ?? null, 
                        ];
                       //dd($dataItem);
                        $save_discountItem = ProductDiscounts::create($dataItem); 
                    
                    }
                    if ($request->has("timeline")) {
                        DiscountTimeLine::create([
                            "di_id" => $upd_discount->uuid,
                            "message" => json_encode($request->timeline),
                        ]);
                    }
                    DB::commit();
                    if($update_discount){  
                        return response()->json([ 
                            'status_code' => Response::HTTP_OK,
                            'message' => $this->get_message('update'), 
                        ], Response::HTTP_OK);

                    }else{ 
                        return response()->json([ 
                            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                            'message' => $this->get_message('server_error'), 
                        ], Response::HTTP_INTERNAL_SERVER_ERROR);

                    }
                }

        }catch (QueryException $e) { 
            DB::rollBack();
            // For other SQL errors
            return response()->json([ 
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $e->getMessage(), 
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        
        }catch (\Exception $e) { 
            DB::rollBack();
            // Handle general exceptions
            //dd($e);
            return response()->json([ 
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }

        
    }


    public function delete_discount($uuid){

        try{

            $del_discount = Discount::where('uuid', $uuid)->first(); 
            DB::beginTransaction();
            if(!$del_discount){ 
                return response()->json([ 
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'), 
                ], Response::HTTP_NOT_FOUND); 

            }else{ 
                //$delete_discount = Discount::destroy($del_discount->id); 
                $del_discount->discountItems()->delete();
                $del_discount->di_timeline()->delete();
                $del_discount->delete();
                DB::commit();
                if($del_discount){ 
                    return response()->json([ 
                        'status_code' => Response::HTTP_OK,
                        'message' => $this->get_message('delete'), 
                    ], Response::HTTP_OK);
    
                } 
            }


        }catch (\Exception $e) { 
            // Handle general exceptions
            dd($e);
            DB::rollBack();
            return response()->json([ 
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'), 
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        } 
        
    }


    public function get_discount(){ 
        try {  
                $menuUuid = request()->header('menu-uuid'); 
                $permissions = $this->permissionService->checkPermissions($menuUuid); 
                $get_all_discountes = Discount::orderBy('id', 'desc');
                if ($permissions['view']) {
                    if (!$permissions['viewglobal']) {
                        $get_all_discountes = $get_all_discountes->where('auth_id', Auth::user()->uuid);
                    }
                }else{
                    if (Auth::user()->hasPermission('viewglobal')) {
                        $get_all_discountes = $get_all_discountes;
                    } else {
                        return response()->json(['message' => 'You do not have permission to view this menu'], Response::HTTP_FORBIDDEN);
                    }
                }

            $get_all_discountes = $get_all_discountes->get(); 
            if($get_all_discountes){ 
                return response()->json([ 
                    'status_code' => Response::HTTP_OK, 
                    'data' => $get_all_discountes,
                    'permissions' => $permissions,  
                ], Response::HTTP_OK); 
            }

        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([ 
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'), 
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error 
        } 

            
        
    } 

 
    public function updateStatus(Request $request, string $id)
    { 
        $request->validate([
            'status' => 'required|in:0,1', // Ensure status is either 0 or 1
        ]);

        try {
            // Find the Discount by UUID and active status
            $discount = Discount::where('uuid', $id)->first();
            //dd($discount);
            if ($discount) {
                // Update the status
                $discount->status = $request->status;
                $discount->save(); 
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

    public function get_active_discounts(){ 
        try {  
            $get_all_discountes = Discount::where('status', 1)->where('method', 'automatic')->orderBy('id', 'desc');

        $get_all_discountes = $get_all_discountes->get(); 
        if($get_all_discountes){ 
            return response()->json([ 
                'status_code' => Response::HTTP_OK, 
                'data' => $get_all_discountes,
            ], Response::HTTP_OK); 
        }

    }catch (\Exception $e) { 
        // Handle general exceptions
        return response()->json([ 
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $this->get_message('server_error'), 
        ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error 
    } 

        
    }

}