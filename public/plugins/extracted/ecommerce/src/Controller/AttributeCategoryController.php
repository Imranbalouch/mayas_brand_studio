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
use App\Models\Ecommerce\AttributeCategory;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon; 
use Illuminate\Support\Str;


class AttributeCategoryController extends Controller
{
    
    public function add_attribute_category(Request $request){
        
        $validator = Validator::make($request->all(), [ 
             
            'category_id' => 'required|integer',
            'attribute_id' => 'required|integer',
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

            $check_if_already = AttributeCategory::where('category_id', $request->category_id)->where('attribute_id', $request->attribute_id)->get();

            if(count($check_if_already) > 0){

                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'This Attribute Category has already been taken.',

                ], Response::HTTP_CONFLICT); // 409 Conflict 


            }else{

                $attribute_category = $request->all();
                $attribute_category['uuid'] = Str::uuid();
                
                $save_attribute_category = AttributeCategory::create($attribute_category);

                if($save_attribute_category) {
                    
                    return response()->json([
                            
                        'status_code' => Response::HTTP_CREATED,
                        'message' => 'Attribute Category add successfully',

                    ], Response::HTTP_CREATED);

                }

            }
            
        
        }catch (QueryException $e) {
            
            if ($e->getCode() === '23000') { // SQLSTATE code for integrity constraint violation
                
                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'Duplicate entry detected',
                    'error' => 'This Attribute Category has already been taken.',

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



    public function edit_attribute_category($uuid){

        $get_attribute_category = AttributeCategory::where('uuid', $uuid)->first();

        if($get_attribute_category)
        {

            return response()->json([

                'status_code' => Response::HTTP_OK,
                'get_attribute_category' => $get_attribute_category,

            ], Response::HTTP_OK);


        }else{

            return response()->json([

                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Record Not Found',

            ], Response::HTTP_NOT_FOUND);

        }

    }

    
    public function update_attribute_category(Request $request, $uuid){
        
        
        $validator = Validator::make($request->all(), [ 
             
            'category_id' => 'required|integer',
            'attribute_id' => 'required|integer',
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
            
            $upd_attribute_category = AttributeCategory::where('uuid', $uuid)->first();

            if (!$upd_attribute_category) {
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Record not found'

                ], Response::HTTP_NOT_FOUND);
            }

            $update_attribute_category = $upd_attribute_category->update($request->all());

            if($update_attribute_category){ 
                
                return response()->json([
                    
                    'status_code' => Response::HTTP_OK,
                    'message' => 'Attribute Category has been updated',
                
                ], Response::HTTP_OK);

            }


        }catch (QueryException $e) {
            
            if ($e->getCode() === '23000') { // SQLSTATE code for integrity constraint violation
                // Handle unique constraint violation
                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'Duplicate entry detected',
                    'error' => 'This Attribute Category has already been taken.',

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



    public function delete_attribute_category($uuid){

        try{

            $del_attribute_category = AttributeCategory::where('uuid', $uuid)->first();
            
            if(!$del_attribute_category)
            {
                
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Record not found'

                ], Response::HTTP_NOT_FOUND);


            }else{

                $delete_attribute = AttributeCategory::destroy($del_attribute_category->id);

                if($delete_attribute){
                
                    return response()->json([
                        
                        'status_code' => Response::HTTP_OK,
                        'message' => 'Attribute Category has been deleted',
                    
                    ], Response::HTTP_OK);
    
                }

            }


        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Server error',
                'error' => $e->getMessage(),

            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } 
        
    }


    public function get_own_attribute_category($authid){

        $get_own_attribute_category = AttributeCategory::where('auth_id', $authid)->get();

        if(count($get_own_attribute_category) > 0)
        {

            return response()->json([

                'status_code' => Response::HTTP_OK,
                'get_own_attribute_category' => $get_own_attribute_category,

            ], Response::HTTP_OK);

        }else{

            return response()->json([

                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Record Not Found',

            ], Response::HTTP_NOT_FOUND);

        }

    }


    public function get_attribute_category(){

        try{

            $get_all_attribute_category = AttributeCategory::all();

            if($get_all_attribute_category){

                return response()->json([
                        
                    'status_code' => Response::HTTP_OK,
                    'get_all_attribute' => $get_all_attribute_category,

                ], Response::HTTP_OK);
    
            }

        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Server error',
                'error' => $e->getMessage(),

            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

}
