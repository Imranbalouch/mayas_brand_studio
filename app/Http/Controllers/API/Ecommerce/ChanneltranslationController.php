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
use App\Models\Ecommerce\Channel_translation;
use Illuminate\Support\Str;


class ChanneltranslationController extends Controller
{
    
    public function add_channel_translation(Request $request){ 
        
        $validator = Validator::make($request->all(), [ 
             
            'channel_id' => 'required|integer',
            'language_id' => 'required|integer',
            'channel' => 'required|string', 
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

            $check_if_already = Channel_translation::where('channel_id', $request->channel_id)->where('language_id', $request->language_id)->get();

            if(count($check_if_already) > 0){ 

                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'This Channel Translation has already been taken.',

                ], Response::HTTP_CONFLICT); // 409 Conflict 


            }else{

                $channel = $request->all();
                $channel['uuid'] = Str::uuid();

                if ($request->hasFile('logo')) {
                    $file = $request->file('logo');
                    $fileName = time() . '_' . $file->getClientOriginalName(); // Prepend timestamp for unique filename
                    $folderName = '/upload_files/channel_translation/';
                    $destinationPath = public_path() . $folderName;
            
                    
                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }
            
                    
                    $file->move($destinationPath, $fileName);
            
                    
                    $channel['logo'] = $folderName . $fileName;
                }
            
                
                $save_channel_translation = Channel_translation::create($channel);

                if($save_channel_translation) {
                    
                    return response()->json([
                            
                        'status_code' => Response::HTTP_CREATED,
                        'message' => 'Channel Translation add successfully',

                    ], Response::HTTP_CREATED);

                }

            }
            
        
        }catch (QueryException $e) {
            
            if ($e->getCode() === '23000') { // SQLSTATE code for integrity constraint violation
                
                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'Duplicate entry detected',
                    'error' => 'This Channel Translation has already been taken.',

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


    public function edit_channel_translation($uuid){

        $get_own_channel_translation = Channel_translation::where('uuid', $uuid)->first();

        if($get_own_channel_translation)
        {
            $get_own_channel_translation->base_url = config('app.base_url');
            return response()->json([

                'status_code' => Response::HTTP_OK,
                'get_own_channel_translation' => $get_own_channel_translation,

            ], Response::HTTP_OK);


        }else{

            return response()->json([

                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Record Not Found',

            ], Response::HTTP_NOT_FOUND);

        }

    }



    public function update_channel_translation(Request $request){

        
        $validator = Validator::make($request->all(), [
            
            'channel_id' => 'required|integer',
            'language_id' => 'required|integer',
            'channel' => 'required|string', 
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
            $upd_channel_translation = Channel_translation::where('uuid', $uuid)->first();

            if (!$upd_channel_translation) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Record not found'
                ], Response::HTTP_NOT_FOUND);
            }

            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $folderName = '/upload_files/channel_translation/';
                $destinationPath = public_path() . $folderName;
                
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $file->move($destinationPath, $fileName);

                $upd_channel_translation->logo = $folderName . $fileName;
            }

            
            $update_channel_translation = $upd_channel_translation->update($request->except('logo')); 

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Channel Translation updated successfully'
            ], Response::HTTP_OK);


        }catch (\Exception $e) { 
            
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Server error',
                'error' => $e->getMessage(),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); 
        }

        
    }



    public function delete_channel_translation($uuid){

        try{

            $del_channel_translation = Channel_translation::where('uuid', $uuid)->first();
            
            if(!$del_channel_translation)
            {
                
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Record not found'

                ], Response::HTTP_NOT_FOUND);


            }else{

                $delete_channel = Channel_translation::destroy($del_channel_translation->id);

                if($delete_channel){
                
                    return response()->json([
                        
                        'status_code' => Response::HTTP_OK,
                        'message' => 'Channel Translation has been deleted',
                    
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


    public function get_own_channel_translation($authid){

        $get_own_channel_translation = Channel_translation::where('auth_id', $authid)->get();

        if(count($get_own_channel_translation) > 0)
        {
            $get_own_channel_translation->base_url = config('app.base_url');
            return response()->json([

                'status_code' => Response::HTTP_OK,
                'get_own_channel_translation' => $get_own_channel_translation,

            ], Response::HTTP_OK);


        }else{

            return response()->json([

                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Record Not Found',

            ], Response::HTTP_NOT_FOUND);

        }

    }



    public function get_channel_translation(){

        try{

            $get_channel_translation = Channel_translation::all();

            if($get_channel_translation){

                foreach ($get_channel_translation as $menu) {
                    $menu->base_url = config('app.base_url');
                }
                
                return response()->json([
                        
                    'status_code' => Response::HTTP_OK,
                    'get_all_channel_translation' => $get_channel_translation,

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
