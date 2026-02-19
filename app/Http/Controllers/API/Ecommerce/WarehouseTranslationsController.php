<?php

namespace App\Http\Controllers\API\Ecommerce;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Ecommerce\WarehouseTranslations;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;


class WarehouseTranslationsController extends Controller
{
    public function add_warehouse_translation(Request $request){ 
        
        $validator = Validator::make($request->all(), [ 
             
            'warehouse_id' => 'required|integer',
            'language_id' => 'required|integer',
            'warehouse_name' => 'required|string',
            'description' => 'nullable|string',
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

            $check_if_already = WarehouseTranslations::where('warehouse_id', $request->warehouse_id)->where('language_id', $request->language_id)->get();

            if(count($check_if_already) > 0){ 

                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'This Warehouse Translation has already been taken.',

                ], Response::HTTP_CONFLICT); // 409 Conflict 


            }else{

                $Warehouse_translation = $request->all();
                $Warehouse_translation['uuid'] = Str::uuid();
                
                $save_Warehouse_translation = WarehouseTranslations::create($Warehouse_translation);

                if($save_Warehouse_translation) {
                    
                    return response()->json([
                            
                        'status_code' => Response::HTTP_CREATED,
                        'message' => 'Warehouse Translation add successfully',

                    ], Response::HTTP_CREATED);

                }

            }
            
        
        }catch (QueryException $e) {
            
            if ($e->getCode() === '23000') { // SQLSTATE code for integrity constraint violation
                
                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'Duplicate entry detected',
                    'error' => 'This Warehouse Translation has already been taken.',

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


    public function edit_warehouse_translation($uuid){

        $get_own_Warehouse_translation = WarehouseTranslations::where('uuid', $uuid)->first();

        if($get_own_Warehouse_translation)
        {
            
            return response()->json([

                'status_code' => Response::HTTP_OK,
                'get_own_Warehouse_translation' => $get_own_Warehouse_translation,

            ], Response::HTTP_OK);


        }else{

            return response()->json([

                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Record Not Found',

            ], Response::HTTP_NOT_FOUND);

        }

    }



    public function update_warehouse_translation(Request $request, $uuid){
        

        $validator = Validator::make($request->all(), [ 
             
            'warehouse_id' => 'required|integer',
            'language_id' => 'required|integer',
            'warehouse_name' => 'required|string',
            'description' => 'nullable|string',
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
            
            $upd_Warehouse = WarehouseTranslations::where('uuid', $uuid)->first();

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
                    'message' => 'Warehouse Translation has been updated',
                
                ], Response::HTTP_OK);

            }


        }catch (QueryException $e) {
            
            if ($e->getCode() === '23000') { // SQLSTATE code for integrity constraint violation
                // Handle unique constraint violation
                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'Duplicate entry detected',
                    'error' => 'This Warehouse Translation has already been taken.',

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



    public function delete_warehouse_translation($uuid){

        try{

            $del_Warehouse_translation = WarehouseTranslations::where('uuid', $uuid)->first();
            
            if(!$del_Warehouse_translation)
            {
                
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Record not found'

                ], Response::HTTP_NOT_FOUND);


            }else{

                $delete_Warehouse = WarehouseTranslations::destroy($del_Warehouse_translation->id);

                if($delete_Warehouse){
                
                    return response()->json([
                        
                        'status_code' => Response::HTTP_OK,
                        'message' => 'Warehouse Translation has been deleted',
                    
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


    public function get_own_warehouse_translation($authid){

        $get_own_Warehouse_translation = WarehouseTranslations::where('auth_id', $authid)->get();

        if(count($get_own_Warehouse_translation) > 0)
        {
            $get_own_Warehouse_translation->base_url = config('app.base_url');
            return response()->json([

                'status_code' => Response::HTTP_OK,
                'get_own_Warehouse_translation' => $get_own_Warehouse_translation,

            ], Response::HTTP_OK);


        }else{

            return response()->json([

                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Record Not Found',

            ], Response::HTTP_NOT_FOUND);

        }

    }



    public function get_warehouse_translation(){

        try{

            $get_Warehouse_translation = WarehouseTranslations::all();

            if($get_Warehouse_translation){

                return response()->json([
                        
                    'status_code' => Response::HTTP_OK,
                    'get_all_Warehouse_translation' => $get_Warehouse_translation,

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
