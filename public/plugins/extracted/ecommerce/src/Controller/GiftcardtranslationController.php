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
use App\Models\Ecommerce\Giftcard_translation;
use Illuminate\Support\Str;


class GiftcardtranslationController extends Controller
{
    
    public function add_giftcard_translation(Request $request){ 
        
        $validator = Validator::make($request->all(), [ 
             
            'giftcard_id' => 'required|integer',
            'language_id' => 'required|integer',
            'giftcard' => 'required|string', 
            'auth_id' => 'required',
        
        ],[
                
            // 'logo.logo' => 'The file must be an image.',
            // 'logo.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg.',
            // 'logo.max' => 'The image must not be greater than 2mb.',
        ]); 


        if($validator->fails()) {
            
            $message = $validator->messages();
            
            return response()->json([
                
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try{

            $check_if_already = Giftcard_translation::where('giftcard_id', $request->giftcard_id)->where('language_id', $request->language_id)->get();

            if(count($check_if_already) > 0){ 

                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'This Giftcard Translation has already been taken.',

                ], Response::HTTP_CONFLICT); // 409 Conflict 


            }else{

                $giftcard = $request->all();
                $giftcard['uuid'] = Str::uuid();

                if ($request->hasFile('logo')) {
                    $file = $request->file('logo');
                    $fileName = time() . '_' . $file->getClientOriginalName(); // Prepend timestamp for unique filename
                    $folderName = '/upload_files/giftcard_translation/';
                    $destinationPath = public_path() . $folderName;
            
                    
                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }
            
                    
                    $file->move($destinationPath, $fileName);
            
                    
                    $giftcard['logo'] = $folderName . $fileName;
                }
            
                
                $save_giftcard_translation = Giftcard_translation::create($giftcard);

                if($save_giftcard_translation) {
                    
                    return response()->json([
                            
                        'status_code' => Response::HTTP_CREATED,
                        'message' => 'Giftcard Translation add successfully',

                    ], Response::HTTP_CREATED);

                }

            }
            
        
        }catch (QueryException $e) {
            
            if ($e->getCode() === '23000') { // SQLSTATE code for integrity constraint violation
                
                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'Duplicate entry detected',
                    'error' => 'This Giftcard Translation has already been taken.',

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


    public function edit_giftcard_translation($uuid){

        $get_own_giftcard_translation = Giftcard_translation::where('uuid', $uuid)->first();

        if($get_own_giftcard_translation)
        {
            $get_own_giftcard_translation->base_url = config('app.base_url');
            return response()->json([

                'status_code' => Response::HTTP_OK,
                'get_own_giftcard_translation' => $get_own_giftcard_translation,

            ], Response::HTTP_OK);


        }else{

            return response()->json([

                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Record Not Found',

            ], Response::HTTP_NOT_FOUND);

        }

    }



    public function update_giftcard_translation(Request $request){

        
        $validator = Validator::make($request->all(), [
            
            'giftcard_id' => 'required|integer',
            'language_id' => 'required|integer',
            'giftcard' => 'required|string',
             
            'auth_id' => 'required',
        
        ],[
                
            // 'logo.logo' => 'The file must be an image.',
            // 'logo.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, svg.',
            // 'logo.max' => 'The image must not be greater than 2mb.',
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
            $upd_giftcard_translation = Giftcard_translation::where('uuid', $uuid)->first();

            if (!$upd_giftcard_translation) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Record not found'
                ], Response::HTTP_NOT_FOUND);
            }

            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $folderName = '/upload_files/giftcard_translation/';
                $destinationPath = public_path() . $folderName;
                
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $file->move($destinationPath, $fileName);

                $upd_giftcard_translation->logo = $folderName . $fileName;
            }

            
            $update_giftcard_translation = $upd_giftcard_translation->update($request->except('logo')); 

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Giftcard Translation updated successfully'
            ], Response::HTTP_OK);


        }catch (\Exception $e) { 
            
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Server error',
                'error' => $e->getMessage(),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); 
        }

        
    }



    public function delete_giftcard_translation($uuid){

        try{

            $del_giftcard_translation = Giftcard_translation::where('uuid', $uuid)->first();
            
            if(!$del_giftcard_translation)
            {
                
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Record not found'

                ], Response::HTTP_NOT_FOUND);


            }else{

                $delete_giftcard = Giftcard_translation::destroy($del_giftcard_translation->id);

                if($delete_giftcard){
                
                    return response()->json([
                        
                        'status_code' => Response::HTTP_OK,
                        'message' => 'Giftcard Translation has been deleted',
                    
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


    public function get_own_giftcard_translation($authid){

        $get_own_giftcard_translation = Giftcard_translation::where('auth_id', $authid)->get();

        if(count($get_own_giftcard_translation) > 0)
        {
            $get_own_giftcard_translation->base_url = config('app.base_url');
            return response()->json([

                'status_code' => Response::HTTP_OK,
                'get_own_giftcard_translation' => $get_own_giftcard_translation,

            ], Response::HTTP_OK);


        }else{

            return response()->json([

                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Record Not Found',

            ], Response::HTTP_NOT_FOUND);

        }

    }



    public function get_giftcard_translation(){

        try{

            $get_giftcard_translation = Giftcard_translation::all();

            if($get_giftcard_translation){

                foreach ($get_giftcard_translation as $menu) {
                    $menu->base_url = config('app.base_url');
                }
                
                return response()->json([
                        
                    'status_code' => Response::HTTP_OK,
                    'get_all_giftcard_translation' => $get_giftcard_translation,

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
