<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;
use Exception; 
use Mail;
use Auth;
use Session;
use Hash;
use DB;
use App\Models\Menu_translation;
use App\Models\Permission;
use App\Models\Permission_assign;
use App\Models\Menu;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon; 
use Illuminate\Support\Str;


class Menu_translationController extends Controller
{
    
    public function add_menu_translation(Request $request){

        $validator = Validator::make($request->all(), [
                
            'name' => 'required',
            'description' => '',
            'icon' => '',
            'language_id' => 'required|numeric',
        
        ]);
        
        if($validator->fails()) {
            
            $message = $validator->messages();
            
            return response()->json([
                
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }


        try {

            $menu_id = request()->header('menu-uuid');
            $get_menu = Menu::where('uuid', $menu_id)->first();

            $check_if_already = Menu_translation::where('menu_id', $get_menu->id)->where('language_id', $request->language_id)->get();

            if(count($check_if_already) > 0){

                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'This Menu Translation has already been taken.',

                ], Response::HTTP_CONFLICT); // 409 Conflict 


            }else{

                $menu_translation = $request->all();
                $menu_translation['uuid'] = Str::uuid();
                $menu_translation['menu_id'] = $get_menu->id;
                $menu_translation['auth_id'] = Auth::user()->uuid;

                if ($request->hasFile('icon')) {
                    $file = $request->file('icon');
                    $fileName = time() . '_' . $file->getClientOriginalName(); // Prepend timestamp for unique filename
                    $folderName = '/upload_files/menu_translation/';
                    $destinationPath = public_path() . $folderName;
            
                    // Ensure the directory exists, if not create it
                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }
            
                    // Move the file to the destination path
                    $file->move($destinationPath, $fileName);
            
                    // Update the menu's icon path
                    $menu_translation['icon'] = $folderName . $fileName;
                }
            
                // Create the menu item
                $save_menu = Menu_translation::create($menu_translation);
                
                if($save_menu){ 
                    
                    return response()->json([
                            
                        'status_code' => Response::HTTP_CREATED,
                        'message' => 'Menu Translation add successfully',

                    ], Response::HTTP_CREATED);

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


    public function edit_menu_translation($uuid){


        $edit_menu_translation = Menu_translation::where('uuid', $uuid)->first();

        if($edit_menu_translation) 
        {
            $edit_menu_translation->base_url = config('app.base_url');
            return response()->json([

                'status_code' => Response::HTTP_OK,
                'data' => $edit_menu_translation,

            ], Response::HTTP_OK);


        }else{

            return response()->json([

                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Record Not Found',

            ], Response::HTTP_NOT_FOUND);

        }


    } 


    public function update_menu_translation(Request $request){
        

        $validator = Validator::make($request->all(), [
            
            'name' => 'required',
            'description' => '',
            'icon' => '',
            'language_id' => 'required|numeric',
        
        ]); 
        
        
        if($validator->fails()) {
            
            $message = $validator->messages();
            
            return response()->json([
                
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        
        try{
            
            $uuid = request()->header('uuid');
            $upd_menu_translation = Menu_translation::where('uuid', $uuid)->first();

            if (!$upd_menu_translation) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Record not found'
                ], Response::HTTP_NOT_FOUND);
            }

            // Check if the 'icon' file is present in the request
            if ($request->hasFile('icon')) {
                $file = $request->file('icon');
                $fileName = time() . '_' . $file->getClientOriginalName(); // Prepend timestamp for unique filename
                $folderName = '/upload_files/menu_translation/';
                $destinationPath = public_path() . $folderName;
                
                // Ensure the directory exists, if not create it
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                // Move the file to the destination path
                $file->move($destinationPath, $fileName);

                // Update the menu's icon path
                $upd_menu_translation->icon = $folderName . $fileName;
            }

            // Update the menu with the new data
            $update_menu = $upd_menu_translation->update($request->except('icon')); // Exclude the 'icon' field from mass assignment

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => 'Menu updated successfully'
            ], Response::HTTP_OK);


        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Server error',
                'error' => $e->getMessage(),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }

    }


    public function delete_menu_translation($uuid){

        try{

            $del_menu_translation = Menu_translation::where('uuid', $uuid)->first();
            
            if(!$del_menu_translation)
            {
                
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Record not found'

                ], Response::HTTP_NOT_FOUND);


            }else{

                $delete_menu_translation = Menu_translation::destroy($del_menu_translation->id);

                if($delete_menu_translation){
                
                    return response()->json([
                        
                        'status_code' => Response::HTTP_OK,
                        'message' => 'Menu has been deleted',
                    
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


    public function get_menu_translation(){

        try{

                $menu_id = request()->header('menu-uuid');
                $role_id = Auth::user()->role_id;
                $get_menu = Menu::where('uuid', $menu_id)->first();

                $add = 0;
                $update = 0;
                $delete = 0;
                
                if($role_id != "1"){

                    $check_permission = Permission_assign::where('role_id', $role_id)
                    ->where('menu_id', $get_menu->id)
                    ->where('status', '1')
                    ->get();

                    foreach ($check_permission as $permission) {
                        
                        if ($permission->permission_id == '1') {
                            $add = 1;
                        }

                        if ($permission->permission_id == '3') {
                            $update = 1;
                        }

                        if ($permission->permission_id == '4') {
                            $delete = 1;
                        }
                    }


                }else{

                    $add = 1;
                    $update = 1;
                    $delete = 1;

                }

            $get_all_menu_translation = Menu_translation::all();

            if($get_all_menu_translation){
                
                return response()->json([
                        
                    'status_code' => Response::HTTP_OK,
                    'new' => $add,
                    'update' => $update,
                    'delete' => $delete,
                    'data' => $get_all_menu_translation,
                
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
