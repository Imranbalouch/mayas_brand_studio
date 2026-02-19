<?php

namespace App\Http\Controllers\API\Ecommerce;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Ecommerce\WarehouseValues;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\QueryException;



class WarehouseValuesController extends Controller
{
    public function add_warehouse_value(Request $request){ 
        
        $validator = Validator::make($request->all(), [ 
             
            'warehouse_id' => 'required|integer',
            'language_id' => 'required|integer',
            'location_name' => 'required|string',
            'auth_id' => 'required',
        
        ]);


        if($validator->fails()) {
            
            $message = $validator->messages();
            
            return response()->json([
                
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try{

            $check_if_already = WarehouseValues::where('warehouse_id', $request->warehouse_id)->where('location_name', $request->location_name)->get();

            if(count($check_if_already) > 0){ 

                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'This Location has already been taken.',

                ], Response::HTTP_CONFLICT); // 409 Conflict 


            }else{

                $Warehouse_value = $request->all();
                $Warehouse_value['uuid'] = Str::uuid();
                
                $save_Warehouse_translation = WarehouseValues::create($Warehouse_value);

                if($save_Warehouse_translation) {
                    
                    return response()->json([
                            
                        'status_code' => Response::HTTP_CREATED,
                        'message' => 'Location add successfully',

                    ], Response::HTTP_CREATED);

                }

            }
            
        
        }catch (QueryException $e) {
            
            if ($e->getCode() === '23000') { // SQLSTATE code for integrity constraint violation
                
                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'Duplicate entry detected',
                    'error' => 'This Location has already been taken.',

                ], Response::HTTP_CONFLICT); // 409 Conflict
            }

            // For other SQL errors
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Database error',
                'error' => $e->getMessage(), 

            ], Response::HTTP_INTERNAL_SERVER_ERROR); 
        

        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Server error',
                'error' => $e->getMessage(),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); 
        }

    }


    public function edit_warehouse_value($uuid){

        $get_Warehouse_value = WarehouseValues::where('uuid', $uuid)->first();
        if($get_Warehouse_value)
        {
            
            return response()->json([

                'status_code' => Response::HTTP_OK,
                'get_Warehouse_value' => $get_Warehouse_value,

            ], Response::HTTP_OK);


        }else{

            return response()->json([

                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Record Not Found',

            ], Response::HTTP_NOT_FOUND);

        }

    }



    public function update_warehouse_value(Request $request, $uuid){
        

        $validator = Validator::make($request->all(), [ 
             
            'warehouse_id' => 'required|integer',
            'language_id' => 'required|integer',
            'location_name' => 'required|string',
            'auth_id' => 'required',
        
        ]);
        
        
        if($validator->fails()) {
            
            $message = $validator->messages();
            
            return response()->json([
                
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        }

        try {
            
            $upd_Warehouse = WarehouseValues::where('uuid', $uuid)->first();

            if (!$upd_Warehouse) {
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Record not found'

                ], Response::HTTP_NOT_FOUND);
            }

            $update_Warehouse = $upd_Warehouse->update($request->all());

            if($update_Warehouse){
                
                return response()->json([
                    
                    'status_code' => Response::HTTP_OK,
                    'message' => 'Location has been updated',
                
                ], Response::HTTP_OK);

            }


        }catch (QueryException $e) {
            
            if ($e->getCode() === '23000') { // SQLSTATE code for integrity constraint violation
                // Handle unique constraint violation
                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'Duplicate entry detected',
                    'error' => 'This Location has already been taken.',

                ], Response::HTTP_CONFLICT); // 409 Conflict
            }

            // For other SQL errors
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Database error',
                'error' => $e->getMessage(), 

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        
        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Server error',
                'error' => $e->getMessage(),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
        
    }



    public function delete_warehouse_value($uuid){

        try{

            $del_Warehouse_translation = WarehouseValues::where('uuid', $uuid)->first();
            
            if(!$del_Warehouse_translation)
            {
                
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Record not found'

                ], Response::HTTP_NOT_FOUND);


            }else{

                $delete_Warehouse = WarehouseValues::destroy($del_Warehouse_translation->id);

                if($delete_Warehouse){
                
                    return response()->json([
                        
                        'status_code' => Response::HTTP_OK,
                        'message' => 'Location has been deleted',
                    
                    ], Response::HTTP_OK);
    
                }

            }


        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Server error',
                'error' => $e->getMessage(),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        } 
        
    }


    public function get_own_warehouse_value($authid){

        $get_own_Warehouse_value = WarehouseValues::where('auth_id', $authid)->get();

        if(count($get_own_Warehouse_value) > 0)
        {
            $get_own_Warehouse_value->base_url = config('app.base_url');
            return response()->json([

                'status_code' => Response::HTTP_OK,
                'get_own_Warehouse_value' => $get_own_Warehouse_value,

            ], Response::HTTP_OK);


        }else{

            return response()->json([

                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Record Not Found',

            ], Response::HTTP_NOT_FOUND);

        }

    }



    public function get_all_warehouse_value(){

        try{

            $get_all_Warehouse_value = WarehouseValues::all();

            foreach ($get_all_Warehouse_value as $get_all_attr_val) {

                $get_all_attr_val->warehouse_name = $get_all_attr_val->language ? $get_all_attr_val->language->name : null;
                $get_all_attr_val->language_name = $get_all_attr_val->warehouse ? $get_all_attr_val->warehouse->warehouse_name : null;

            }

            if($get_all_Warehouse_value){

                return response()->json([
                        
                    'status_code' => Response::HTTP_OK,
                    'get_all_Warehouse_value' => $get_all_Warehouse_value,

                ], Response::HTTP_OK);
    
            }

        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Server error',
                'error' => $e->getMessage(),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        } 

    }

}
