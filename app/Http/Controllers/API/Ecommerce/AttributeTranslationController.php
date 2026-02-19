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
use App\Models\Ecommerce\Attribute_translation;
use Illuminate\Support\Str;



class AttributeTranslationController extends Controller
{
    
    public function add_attribute_translation(Request $request){ 
        
        $validator = Validator::make($request->all(), [ 
             
            'attribute_id' => 'required|integer',
            'language_id' => 'required|integer',
            'attribute_name' => 'required|string',
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

            $check_if_already = Attribute_translation::where('attribute_id', $request->attribute_id)->where('language_id', $request->language_id)->get();

            if(count($check_if_already) > 0){ 

                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'This Attribute Translation has already been taken.',

                ], Response::HTTP_CONFLICT); // 409 Conflict 


            }else{

                $attribute_translation = $request->all();
                $attribute_translation['uuid'] = Str::uuid();
                
                $save_attribute_translation = Attribute_translation::create($attribute_translation);

                if($save_attribute_translation) {
                    
                    return response()->json([
                            
                        'status_code' => Response::HTTP_CREATED,
                        'message' => 'Attribute Translation add successfully',

                    ], Response::HTTP_CREATED);

                }

            }
            
        
        }catch (QueryException $e) {
            
            if ($e->getCode() === '23000') { // SQLSTATE code for integrity constraint violation
                
                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'Duplicate entry detected',
                    'error' => 'This Attribute Translation has already been taken.',

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


    public function edit_attribute_translation($uuid){

        $get_own_attribute_translation = Attribute_translation::where('uuid', $uuid)->first();

        if($get_own_attribute_translation)
        {
            
            return response()->json([

                'status_code' => Response::HTTP_OK,
                'get_own_attribute_translation' => $get_own_attribute_translation,

            ], Response::HTTP_OK);


        }else{

            return response()->json([

                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Record Not Found',

            ], Response::HTTP_NOT_FOUND);

        }

    }



    public function update_attribute_translation(Request $request, $uuid){
        

        $validator = Validator::make($request->all(), [ 
             
            'attribute_id' => 'required|integer',
            'language_id' => 'required|integer',
            'attribute_name' => 'required|string',
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
            
            $upd_attribute = Attribute_translation::where('uuid', $uuid)->first();

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
                    'message' => 'Attribute Translation has been updated',
                
                ], Response::HTTP_OK);

            }


        }catch (QueryException $e) {
            
            if ($e->getCode() === '23000') { // SQLSTATE code for integrity constraint violation
                // Handle unique constraint violation
                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'Duplicate entry detected',
                    'error' => 'This Attribute Translation has already been taken.',

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



    public function delete_attribute_translation($uuid){

        try{

            $del_attribute_translation = Attribute_translation::where('uuid', $uuid)->first();
            
            if(!$del_attribute_translation)
            {
                
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Record not found'

                ], Response::HTTP_NOT_FOUND);


            }else{

                $delete_attribute = Attribute_translation::destroy($del_attribute_translation->id);

                if($delete_attribute){
                
                    return response()->json([
                        
                        'status_code' => Response::HTTP_OK,
                        'message' => 'Attribute Translation has been deleted',
                    
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


    public function get_own_attribute_translation($authid){

        $get_own_attribute_translation = Attribute_translation::where('auth_id', $authid)->get();

        if(count($get_own_attribute_translation) > 0)
        {
            $get_own_attribute_translation->base_url = config('app.base_url');
            return response()->json([

                'status_code' => Response::HTTP_OK,
                'get_own_attribute_translation' => $get_own_attribute_translation,

            ], Response::HTTP_OK);


        }else{

            return response()->json([

                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Record Not Found',

            ], Response::HTTP_NOT_FOUND);

        }

    }



    public function get_attribute_translation(){

        try{

            $get_attribute_translation = Attribute_translation::all();

            if($get_attribute_translation){

                return response()->json([
                        
                    'status_code' => Response::HTTP_OK,
                    'get_all_attribute_translation' => $get_attribute_translation,

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
