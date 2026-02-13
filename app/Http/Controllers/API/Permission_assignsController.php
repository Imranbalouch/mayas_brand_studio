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
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Permission;
use App\Models\Menu;
use App\Models\Menu_translation;
use App\Models\Role;
use App\Models\Language;
use App\Models\Permission_assign;
use App\Models\PermissionTranslation;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Str;
use App\Traits\MessageTrait;


class Permission_assignsController extends Controller
{
    
    use MessageTrait;

    public function add_permission_assign(Request $request){
        
        $validator = Validator::make($request->all(), [
             
            'role_id' => 'required|numeric',
            'permission_id' => 'required|numeric',
            'status' => 'required|numeric'

        ]);

        if($validator->fails()){
            
            $message = $validator->messages();
            
            return response()->json([
                
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        }

        try {

            $menu_id = request()->header('menu-uuid');
            $get_menu = Menu::where('uuid', $menu_id)->first();

            $check_if_already = Permission_assign::where('role_id', $request->role_id)->where('permission_id', $request->permission_id)->where('menu_id', $get_menu->id)->get();

            if(count($check_if_already) > 0){

                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => $this->get_message('conflict'),

                ], Response::HTTP_CONFLICT); // 409 Conflict 


            }else{

                $permission_assign = $request->all();
                $permission_assign['uuid'] = Str::uuid();
                $permission_assign['menu_id'] = $get_menu->id;
                $permission_assign['auth_id'] = Auth::user()->uuid;

                $save_per_assign = Permission_assign::create($permission_assign);

                if($save_per_assign){
                    
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
                'message' => $this->get_message('database_error'),
                // 'error' => $e->getMessage(), 

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        

        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                // 'error' => $e->getMessage(),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }


    }



    public function edit_permission_assign($uuid){

        
        try {
            
            // $edit_per_assign = Permission_assign::where('uuid', $uuid)->first();

            $edit_per_assign = Permission_assign::select('permission_assigns.*', 'roles.role as role_name', 'permissions.permission as permission_name' , 'menus.name as menu_name')
            ->join('roles', 'permission_assigns.role_id', '=', 'roles.id')
            ->join('permissions', 'permission_assigns.permission_id', '=', 'permissions.id')
            ->join('menus', 'permission_assigns.menu_id', '=', 'menus.id')
            ->where('permission_assigns.uuid', $uuid)
            ->first();
            

            if($edit_per_assign)
            {

                
                return response()->json([

                    'status_code' => Response::HTTP_OK,
                    'data' => $edit_per_assign,

                ], Response::HTTP_OK);


            }else{

                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);

            }


        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                // 'error' => $e->getMessage(),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }



    }



    public function update_permission_assign(Request $request){
        
        $validator = Validator::make($request->all(), [
             
            'permission_assign' => 'required',

        ]);

        if($validator->fails()){
            
            $message = $validator->messages();
            
            return response()->json([
                
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        }

        // dd($request->all());

        
        try{
            
             // Get role_id from request headers
            $role_id = $request->header('role-id');
            
            // Delete existing permissions for the role
            Permission_assign::where('role_id', $role_id)->delete();

            // Get current authenticated user's UUID
            $auth_id = Auth::user()->uuid;

            // Initialize an array to store new permissions
            $new_permissions = [];

            // Decode the permission_assign JSON
            $per_assign = json_decode($request->permission_assign, true);
            
            // Loop through the provided permissions
            foreach ($per_assign as $permission) {
                $new_permissions[] = [
                    'uuid' => (string) Str::uuid(),
                    'permission_id' => $permission['permission_id'],
                    'menu_id' => $permission['menu_id'],
                    'status' => $permission['status'],
                    'auth_id' => $auth_id,
                    'role_id' => $role_id, // Assuming you also want to store role_id
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insert new permissions into the database
            $update_per_assign = Permission_assign::insert($new_permissions);
            

            if($update_per_assign){
                
                return response()->json([
                    
                    'status_code' => Response::HTTP_OK,
                    'message' => "Permission updated successfully",
                
                ], Response::HTTP_OK);

            }


        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                'error' => $e->getMessage(),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }

        
    }



    public function delete_permission_assign($uuid){

        try{

            $del_per_assign = Permission_assign::where('uuid', $uuid)->first();
            
            if(!$del_per_assign)
            {
                
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);


            }else{

                $delete_per_assign = Permission_assign::destroy($del_per_assign->id);

                if($delete_per_assign){
                
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
                // 'error' => $e->getMessage(),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        } 
        
    }

 
    public function get_permission_assign(){


        try {
            
            $menu_id = request()->header('menu-uuid');
            $active_role_id = request()->header('role-id');
            $get_active_role = Role::where('id', $active_role_id)->where('status', '1')->first();
            $role_id = $get_active_role->id;
            $auth_role_id = Auth::user()->role_id;

            $get_menu = Menu::where('uuid', $menu_id)->first();

            $add = 0;
            $update = 0;
            $delete = 0;
            
            if($auth_role_id != '1'){

                $check_permission = Permission_assign::where('role_id', $auth_role_id)
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

            $get_all_permission_assigns = Permission_assign::select('permission_assigns.*', 'roles.role as role_name', 'permissions.permission as permission_name' , 'menus.name as menu_name')
            ->join('roles', 'permission_assigns.role_id', '=', 'roles.id')
            ->join('permissions', 'permission_assigns.permission_id', '=', 'permissions.id')
            ->join('menus', 'permission_assigns.menu_id', '=', 'menus.id')
            ->where('menus.status', '1')
            ->where('permission_assigns.role_id', $role_id)
            ->orderBy('menus.name', 'asc')
            ->get();

            // Translation
            $get_active_menus = Menu::orderBy('name', 'asc')->where('status', '1')->get();
            $get_active_permissions = Permission::where('status', '1')->get();
            $get_active_language = Language::where('status', '1')->first();

            // Prepare the permissions name array with translations
            $permissions_with_translations = [];
            foreach ($get_active_permissions as $permission) {
                $translations = PermissionTranslation::where('permission_id', $permission->id)
                                ->where('language_id', $get_active_language->id)
                                ->get();


                $translation_data = $translations->map(function($translation) use ($get_active_language) {
                    return [
                        'name_' . $get_active_language->code => $translation->name,
                        'language_code' => $get_active_language->code
                    ];
                })->toArray();

                $permissions_with_translations[] = [
                    'permission_id' => $permission->id,
                    'permission_name' => $permission->permission,
                    'translations' => $translation_data
                ];
            }


            // Prepare the menus name array with translations
            $menus_with_translations = [];
            foreach ($get_active_menus as $menu) {
                $translations = Menu_translation::where('menu_id', $menu->id)
                                ->where('language_id', $get_active_language->id)
                                ->get();

                $translation_data = $translations->map(function($translation) use ($get_active_language) {
                    return [
                        'name_' . $get_active_language->code => $translation->name,
                        'language_code' => $get_active_language->code
                    ];
                })->toArray();

                $menus_with_translations[] = [
                    'menu_id' => $menu->id,
                    'menu_name' => $menu->name,
                    'translations' => $translation_data
                ];
            }

    
            if($get_all_permission_assigns){ 
                
                return response()->json([
                        
                    'status_code' => Response::HTTP_OK,
                    'new' => $add,
                    'update' => $update,
                    'delete' => $delete,
                    'data' => $get_all_permission_assigns,
                    'permissions' => $permissions_with_translations,
                    'menus' => $menus_with_translations
    
                ], Response::HTTP_OK);
    
            }

        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                // 'error' => $e->getMessage(),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        } 

       
        
    }


}
