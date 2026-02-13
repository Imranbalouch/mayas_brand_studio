<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use Mail;
use Auth;
use Hash;
use DB;
use App\Models\Permission;
use App\Models\Menu;
use App\Models\Language;
use App\Models\PermissionTranslation;
use App\Models\Permission_assign;
use App\Models\User_special_permission;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon; 
use Illuminate\Support\Str;
use App\Traits\MessageTrait;


class PermissionController extends Controller
{

    use MessageTrait;

    public function add_permission(Request $request){
        
        $validator = Validator::make($request->all(), [
             
            'permission' => 'required|regex:/^[a-zA-Z0-9\s\-\(\)]+$/',
            'status' => 'required|numeric',

        ]);

        if($validator->fails()) {
            
            $message = $validator->messages();
            
            return response()->json([
                    
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        }


        try {
            

            $check_if_already = Permission::where('permission', $request->permission)->get();

            if(count($check_if_already) > 0){

                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => $this->get_message('conflict'),

                ], Response::HTTP_CONFLICT); // 409 Conflict 


            }else{

                $permission = $request->all();
                $permission['uuid'] = Str::uuid();
                $permission['auth_id'] = Auth::user()->uuid;
                $save_permission = Permission::create($permission);

                if($save_permission)
                {

                    return response()->json([
                            
                        'status_code' => Response::HTTP_CREATED,
                        'message' => $this->get_message('add'),

                    ], Response::HTTP_CREATED);

                }

            }


        }catch (QueryException $e) {
            
            if ($e->getCode() === '23000') { // SQLSTATE code for integrity constraint violation
                // Handle unique constraint violation
                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => $this->get_message('conflict'),

                ], Response::HTTP_CONFLICT); // 409 Conflict
            }

            // For other SQL errors
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                 
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        
        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }

        
    }


    public function edit_permission($uuid){

        try {
            
            $menu_id = request()->header('menu-uuid');
            $get_menu = Menu::where('uuid', $menu_id)->first();
            $edit_permission_by_id = Permission::where('uuid', $uuid)->first();

            $menu_translation_get = PermissionTranslation::where('permission_id', $edit_permission_by_id->id)
            ->where('status', '1')->get();
            
            $get_active_language = Language::where('status', '1')->get();

            $now = Carbon::now();
            $auth_id = Auth::user()->uuid;

            if(count($get_active_language) > 0){

                foreach ($get_active_language as $key => $language) {
                    
                    $check_permission_translation = PermissionTranslation::where('permission_id', $edit_permission_by_id->id)
                    ->where('language_id', $language->id)
                    ->where('status', '1')->first();

                    if($check_permission_translation)
                    {
                        
                       

                    }
                    else{

                        $save_permission_translation = PermissionTranslation::insert([
                            ['uuid' => Str::uuid(), 'permission_id' => $edit_permission_by_id->id, 'name' => $edit_permission_by_id->permission , 'language_id' => $language->id , 'auth_id' => $auth_id , 'created_at' => $now, 'updated_at' => $now],
                        ]);

                    }


                }


            }

            $permission_translations = PermissionTranslation::where('permission_id', $edit_permission_by_id->id)
            ->where('permission_translations.status', '1')
            ->join('languages', 'permission_translations.language_id', '=', 'languages.id')
            ->select('languages.code as language_code', 'languages.name as language_name' , 'languages.flag as flag' , 'languages.rtl as dir', 'permission_translations.*')
            ->get();

            
            if ($edit_permission_by_id) {

                $edit_permission_by_id->translations = $permission_translations;
       
                return response()->json([

                    'status_code' => Response::HTTP_OK,
                    'data' => $edit_permission_by_id,

                ], Response::HTTP_OK);


            }else{

                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);

            }


        }catch(\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }

    }


    public function update_permission(Request $request){ 
        

        $validator = Validator::make($request->all(), [
             
            'status' => 'required|numeric',
        
        ]);

        if($validator->fails()) {
            
            $message = $validator->messages();
            
            return response()->json([
                    
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        }

        
        try{
            
            $menu_id = request()->header('menu-uuid');
            $uuid = request()->header('uuid');
            $upd_permission = Permission::where('uuid', $uuid)->first();
            
            if(!$upd_permission){
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);
            }

            $upd_permission->status = $request->status;
            $update_menu = $upd_permission->save();

            foreach ($request->all() as $key => $value) {
                
                if (strpos($key, 'name_') === 0) {
                    
                    $languageCode = substr($key, 5);
            
                    $languageId = DB::table('languages')->where('code', $languageCode)->value('id');
            
                    if($languageId){
                        
                        PermissionTranslation::where('language_id', $languageId)
                        ->where('permission_id', $upd_permission->id)
                        ->update(['name' => $value]);
                    }

                }

            }


            $get_active_language = Language::where('status', '1')->where('is_default', '1')->first();
            $get_permission_trans_by_def_lang = PermissionTranslation::where('permission_id', $upd_permission->id)
            ->where('language_id', $get_active_language->id)
            ->first();
            
            if (!$get_permission_trans_by_def_lang) {
                $get_permission_trans_by_def_lang = new PermissionTranslation();
                $get_permission_trans_by_def_lang->permission_id = $upd_permission->id;
                $get_permission_trans_by_def_lang->uuid = $upd_permission->uuid;
                $get_permission_trans_by_def_lang->auth_id = $upd_permission->auth_id;
                $get_permission_trans_by_def_lang->language_id = $get_active_language->id;
                $get_permission_trans_by_def_lang->name = $upd_permission->permission; 
                $get_permission_trans_by_def_lang->save();
            }

            $upd_permission2 = DB::table('permissions')->where('id', $upd_permission->id)->update(['permission' => $get_permission_trans_by_def_lang->name]);
                
                return response()->json([
                
                    'status_code' => Response::HTTP_OK,
                    'message' => $this->get_message('update'),
    
                ], Response::HTTP_OK); 
          

        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                // 'message' => $e->getMessage(),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }

        
    }


    public function delete_permission($uuid){

        try{

            $del_permission = Permission::where('uuid', $uuid)->first();
            
            if(!$del_permission)
            {
                
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);


            }else{

                $delete_permission = Permission::destroy($del_permission->id);

                if($delete_permission){

                    $del_permission_translation = PermissionTranslation::where('permission_id', $del_permission->id)->delete();
                    $del_permission_assign = Permission_assign::where('permission_id', $del_permission->id)->delete();
                    $del_special_permission = User_special_permission::where('permission_id', $del_permission->id)->delete();

                    return response()->json([
                        
                        'status_code' => Response::HTTP_OK,
                        'message' => $this->get_message('delete'),
                    
                    ], Response::HTTP_OK);
    
                }

            }


        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
        

    }


    public function get_permission(){

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

            $get_all_permission = Permission::all();

            if($get_all_permission){
                
                return response()->json([
                        
                    'status_code' => Response::HTTP_OK,
                    'new' => $add,
                    'update' => $update,
                    'delete' => $delete,
                    'data' => $get_all_permission,
                
                ], Response::HTTP_OK);
    
            }

        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        } 

        
    }

    
}
