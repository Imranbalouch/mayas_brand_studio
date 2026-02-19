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
use App\Models\Ecommerce\Market_translation;
use Illuminate\Support\Str;


class MarkettranslationController extends Controller
{
    
    public function add_market_translation(Request $request){ 
        
        $validator = Validator::make($request->all(), [ 
             
            'market_id' => 'required|integer',
            'language_id' => 'required|integer',
            'market' => 'required|string',
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

            $check_if_already = Market_translation::where('market_id', $request->market_id)->where('language_id', $request->language_id)->get();

            if(count($check_if_already) > 0){ 

                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'This Market Translation has already been taken.',

                ], Response::HTTP_CONFLICT); // 409 Conflict 


            }else{

                $market = $request->all();
                $market['uuid'] = Str::uuid();

                if ($request->hasFile('logo')) {
                    $file = $request->file('logo');
                    $fileName = time() . '_' . $file->getClientOriginalName(); // Prepend timestamp for unique filename
                    $folderName = '/upload_files/market_translation/';
                    $destinationPath = public_path() . $folderName;
            
                    
                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }
            
                    
                    $file->move($destinationPath, $fileName);
            
                    
                    $market['logo'] = $folderName . $fileName;
                }
            
                
                $save_market_translation = Market_translation::create($market);

                if($save_market_translation) {
                    
                    return response()->json([
                            
                        'status_code' => Response::HTTP_CREATED,
                        'message' => 'Market Translation add successfully',

                    ], Response::HTTP_CREATED);

                }

            }
            
        
        }catch (QueryException $e) {
            
            if ($e->getCode() === '23000') { // SQLSTATE code for integrity constraint violation
                
                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'Duplicate entry detected',
                    'error' => 'This Market Translation has already been taken.',

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


    public function edit_market_translation($uuid){

        $get_own_market_translation = Market_translation::where('uuid', $uuid)->first();

        if($get_own_market_translation)
        {
            $get_own_market_translation->base_url = config('app.base_url');
            return response()->json([

                'status_code' => Response::HTTP_OK,
                'get_own_market_translation' => $get_own_market_translation,

            ], Response::HTTP_OK);


        }else{

            return response()->json([

                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Record Not Found',

            ], Response::HTTP_NOT_FOUND);

        }

    }



    public function update_market_translation(Request $request){

        
        $validator = Validator::make($request->all(), [
            
            'market_id' => 'required|integer',
            'language_id' => 'required|integer',
            'market' => 'required|string',
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
            $upd_market_translation = Market_translation::where('uuid', $uuid)->first();

            if (!$upd_market_translation) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Record not found'
                ], Response::HTTP_NOT_FOUND);
            }

            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $folderName = '/upload_files/market_translation/';
                $destinationPath = public_path() . $folderName;
                
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $file->move($destinationPath, $fileName);

                $upd_market_translation->logo = $folderName . $fileName;
            }

            
            $update_market_translation = $upd_market_translation->update($request->except('logo')); 

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Market Translation updated successfully'
            ], Response::HTTP_OK);


        }catch (\Exception $e) { 
            
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Server error',
                'error' => $e->getMessage(),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); 
        }

        
    }



    public function delete_market_translation($uuid){

        try{

            $del_market_translation = Market_translation::where('uuid', $uuid)->first();
            
            if(!$del_market_translation)
            {
                
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Record not found'

                ], Response::HTTP_NOT_FOUND);


            }else{

                $delete_market = Market_translation::destroy($del_market_translation->id);

                if($delete_market){
                
                    return response()->json([
                        
                        'status_code' => Response::HTTP_OK,
                        'message' => 'Market Translation has been deleted',
                    
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


    public function get_own_market_translation($authid){

        $get_own_market_translation = Market_translation::where('auth_id', $authid)->get();

        if(count($get_own_market_translation) > 0)
        {
            $get_own_market_translation->base_url = config('app.base_url');
            return response()->json([

                'status_code' => Response::HTTP_OK,
                'get_own_market_translation' => $get_own_market_translation,

            ], Response::HTTP_OK);


        }else{

            return response()->json([

                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Record Not Found',

            ], Response::HTTP_NOT_FOUND);

        }

    }



    public function get_market_translation(){

        try{

            $get_market_translation = Market_translation::all();

            if($get_market_translation){

                foreach ($get_market_translation as $menu) {
                    $menu->base_url = config('app.base_url');
                }
                
                return response()->json([
                        
                    'status_code' => Response::HTTP_OK,
                    'get_all_market_translation' => $get_market_translation,

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
