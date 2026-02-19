<?php

namespace App\Http\Controllers\API\Ecommerce;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;
use Mail;
use Auth;
use Session;
use Hash;
use DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Ecommerce\AttributeValue;
use Illuminate\Support\Str;


class AttributeValueController extends Controller
{
    
    public function add_attribute_value(Request $request){ 
        
        $validator = Validator::make($request->all(), [ 
             
            'attribute_id' => 'required|integer',
            'language_id' => 'required|integer',
            'value' => 'required|string',
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

            $check_if_already = AttributeValue::where('attribute_id', $request->attribute_id)->where('value', $request->value)->get();

            if(count($check_if_already) > 0){ 

                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'This Attribute Value has already been taken.',

                ], Response::HTTP_CONFLICT); // 409 Conflict 


            }else{

                $attribute_value = $request->all();
                $attribute_value['uuid'] = Str::uuid();
                
                $save_attribute_translation = AttributeValue::create($attribute_value);

                if($save_attribute_translation) {
                    
                    return response()->json([
                            
                        'status_code' => Response::HTTP_CREATED,
                        'message' => 'Attribute Value add successfully',

                    ], Response::HTTP_CREATED);

                }

            }
            
        
        }catch (QueryException $e) {
            
            if ($e->getCode() === '23000') { // SQLSTATE code for integrity constraint violation
                
                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'Duplicate entry detected',
                    'error' => 'This Attribute Value has already been taken.',

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


    public function edit_attribute_value($uuid){

        $get_attribute_value = AttributeValue::where('uuid', $uuid)->first();

        if($get_attribute_value)
        {
            
            return response()->json([

                'status_code' => Response::HTTP_OK,
                'get_attribute_value' => $get_attribute_value,

            ], Response::HTTP_OK);


        }else{

            return response()->json([

                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Record Not Found',

            ], Response::HTTP_NOT_FOUND);

        }

    }



    public function update_attribute_value(Request $request, $uuid){
        

        $validator = Validator::make($request->all(), [ 
             
            'attribute_id' => 'required|integer',
            'language_id' => 'required|integer',
            'value' => 'required|string',
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
            
            $upd_attribute = AttributeValue::where('uuid', $uuid)->first();

            if (!$upd_attribute) {
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Record not found'

                ], Response::HTTP_NOT_FOUND);
            }

            $update_attribute = $upd_attribute->update($request->all());

            if($update_attribute){
                
                return response()->json([
                    
                    'status_code' => Response::HTTP_OK,
                    'message' => 'Attribute Value has been updated',
                
                ], Response::HTTP_OK);

            }


        }catch (QueryException $e) {
            
            if ($e->getCode() === '23000') { // SQLSTATE code for integrity constraint violation
                // Handle unique constraint violation
                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'Duplicate entry detected',
                    'error' => 'This Attribute Value has already been taken.',

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



    public function delete_attribute_value($uuid){

        try{

            $del_attribute_translation = AttributeValue::where('uuid', $uuid)->first();
            
            if(!$del_attribute_translation)
            {
                
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Record not found'

                ], Response::HTTP_NOT_FOUND);


            }else{

                $delete_attribute = AttributeValue::destroy($del_attribute_translation->id);

                if($delete_attribute){
                
                    return response()->json([
                        
                        'status_code' => Response::HTTP_OK,
                        'message' => 'Attribute Value has been deleted',
                    
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


    public function get_own_attribute_value($authid){

        $get_own_attribute_value = AttributeValue::where('auth_id', $authid)->get();

        if(count($get_own_attribute_value) > 0)
        {
            $get_own_attribute_value->base_url = config('app.base_url');
            return response()->json([

                'status_code' => Response::HTTP_OK,
                'get_own_attribute_value' => $get_own_attribute_value,

            ], Response::HTTP_OK);


        }else{

            return response()->json([

                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Record Not Found',

            ], Response::HTTP_NOT_FOUND);

        }

    }



    public function get_all_attribute_value(){

        try{

            $get_all_attribute_value = AttributeValue::all();

            foreach ($get_all_attribute_value as $get_all_attr_val) {

                $get_all_attr_val->attribute_name = $get_all_attr_val->language ? $get_all_attr_val->language->name : null;
                $get_all_attr_val->language_name = $get_all_attr_val->attribute ? $get_all_attr_val->attribute->attribute_name : null;

            }

            if($get_all_attribute_value){

                return response()->json([
                        
                    'status_code' => Response::HTTP_OK,
                    'get_all_attribute_value' => $get_all_attribute_value,

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
