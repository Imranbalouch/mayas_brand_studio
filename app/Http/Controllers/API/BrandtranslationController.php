<?php

namespace App\Http\Controllers\API;

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
use App\Models\Brand_translation;
use Illuminate\Support\Str;


class BrandtranslationController extends Controller
{
    
    public function add_brand_translation(Request $request){ 
        
        $validator = Validator::make($request->all(), [ 
             
            'brand_id' => 'required|integer',
            'language_id' => 'required|integer',
            'brand' => 'required|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'description' => 'nullable|string',
            'meta_title' => 'nullable|string',
            'meta_description' => 'nullable|string',
            'auth_id' => 'required',
        
        ],[
                
            'logo.logo' => 'The file must be an image.',
            'logo.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg.',
            'logo.max' => 'The image must not be greater than 2mb.',
        ]); 


        if($validator->fails()) {
            
            $message = $validator->messages();
            
            return response()->json([
                
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try{

            $check_if_already = Brand_translation::where('brand_id', $request->brand_id)->where('language_id', $request->language_id)->get();

            if(count($check_if_already) > 0){ 

                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'This Brand Translation has already been taken.',

                ], Response::HTTP_CONFLICT); // 409 Conflict 


            }else{

                $brand = $request->all();
                $brand['uuid'] = Str::uuid();

                if ($request->hasFile('logo')) {
                    $file = $request->file('logo');
                    $fileName = time() . '_' . $file->getClientOriginalName(); // Prepend timestamp for unique filename
                    $folderName = '/upload_files/brand_translation/';
                    $destinationPath = public_path() . $folderName;
            
                    
                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }
            
                    
                    $file->move($destinationPath, $fileName);
            
                    
                    $brand['logo'] = $folderName . $fileName;
                }
            
                
                $save_brand_translation = Brand_translation::create($brand);

                if($save_brand_translation) {
                    
                    return response()->json([
                            
                        'status_code' => Response::HTTP_CREATED,
                        'message' => 'Brand Translation add successfully',

                    ], Response::HTTP_CREATED);

                }

            }
            
        
        }catch (QueryException $e) {
            
            if ($e->getCode() === '23000') { // SQLSTATE code for integrity constraint violation
                
                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'Duplicate entry detected',
                    'error' => 'This Brand Translation has already been taken.',

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


    public function edit_brand_translation($uuid){

        $get_own_brand_translation = Brand_translation::where('uuid', $uuid)->first();

        if($get_own_brand_translation)
        {
            $get_own_brand_translation->base_url = config('app.base_url');
            return response()->json([

                'status_code' => Response::HTTP_OK,
                'get_own_brand_translation' => $get_own_brand_translation,

            ], Response::HTTP_OK);


        }else{

            return response()->json([

                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Record Not Found',

            ], Response::HTTP_NOT_FOUND);

        }

    }



    public function update_brand_translation(Request $request){

        
        $validator = Validator::make($request->all(), [
            
            'brand_id' => 'required|integer',
            'language_id' => 'required|integer',
            'brand' => 'required|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'description' => 'nullable|string',
            'meta_title' => 'nullable|string',
            'meta_description' => 'nullable|string',
            'auth_id' => 'required',
        
        ],[
                
            'logo.logo' => 'The file must be an image.',
            'logo.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg.',
            'logo.max' => 'The image must not be greater than 2mb.',
        ]); 
        
        
        if($validator->fails()) {
            
            $message = $validator->messages();
            
            return response()->json([
                
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        
        try{
            
            $uuid = $request->uuid;
            $upd_brand_translation = Brand_translation::where('uuid', $uuid)->first();

            if (!$upd_brand_translation) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Record not found'
                ], Response::HTTP_NOT_FOUND);
            }

            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $folderName = '/upload_files/brand_translation/';
                $destinationPath = public_path() . $folderName;
                
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $file->move($destinationPath, $fileName);

                $upd_brand_translation->logo = $folderName . $fileName;
            }

            
            $update_brand_translation = $upd_brand_translation->update($request->except('logo')); 

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Brand Translation updated successfully'
            ], Response::HTTP_OK);


        }catch (\Exception $e) { 
            
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Server error',
                'error' => $e->getMessage(),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); 
        }

        
    }



    public function delete_brand_translation($uuid){

        try{

            $del_brand_translation = Brand_translation::where('uuid', $uuid)->first();
            
            if(!$del_brand_translation)
            {
                
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Record not found'

                ], Response::HTTP_NOT_FOUND);


            }else{

                $delete_brand = Brand_translation::destroy($del_brand_translation->id);

                if($delete_brand){
                
                    return response()->json([
                        
                        'status_code' => Response::HTTP_OK,
                        'message' => 'Brand Translation has been deleted',
                    
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


    public function get_own_brand_translation($authid){

        $get_own_brand_translation = Brand_translation::where('auth_id', $authid)->get();

        if(count($get_own_brand_translation) > 0)
        {
            $get_own_brand_translation->base_url = config('app.base_url');
            return response()->json([

                'status_code' => Response::HTTP_OK,
                'get_own_brand_translation' => $get_own_brand_translation,

            ], Response::HTTP_OK);


        }else{

            return response()->json([

                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Record Not Found',

            ], Response::HTTP_NOT_FOUND);

        }

    }



    public function get_brand_translation(){

        try{

            $get_brand_translation = Brand_translation::all();

            if($get_brand_translation){

                foreach ($get_brand_translation as $menu) {
                    $menu->base_url = config('app.base_url');
                }
                
                return response()->json([
                        
                    'status_code' => Response::HTTP_OK,
                    'get_all_brand_translation' => $get_brand_translation,

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
