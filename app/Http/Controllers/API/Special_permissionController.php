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
use App\Models\User;
use App\Models\Permission_assign;
use App\Models\Menu;
use App\Models\User_special_permission;
use Illuminate\Support\Facades\Validator; 
use Spatie\Activitylog\Models\Activity;
use App\Models\Permission;
use App\Models\Menu_translation;
use App\Models\Role;
use App\Models\Language;
use App\Models\PermissionTranslation;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Traits\MessageTrait;


class Special_permissionController extends Controller
{
    
    use MessageTrait;

    public function add_special_permission_assign(Request $request){
        
        $validator = Validator::make($request->all(), [
            
            'user_id' => 'required',
            'permission_id' => 'required',
            'status' => 'required|numeric'

        ]);

        if($validator->fails()){
            
            $message = $validator->messages();
            
            return response()->json([
                
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        }

        try{

            $user = Auth::user();
            $menu_id = request()->header('menu-uuid');
            $get_menu = Menu::where('uuid', $menu_id)->first();

            $check_if_already = User_special_permission::where('user_id', $request->user_id)
            ->where('permission_id', $request->permission_id)
            ->where('menu_id', $get_menu->id)
            ->get();

            if(count($check_if_already) > 0){

                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => $this->get_message('conflict'),

                ], Response::HTTP_CONFLICT); 


            }else{
            
                $special_permission = $request->all();
                $special_permission['uuid'] = Str::uuid();
                $special_permission['menu_id'] = $get_menu->id;
                $special_permission['auth_id'] = $user->uuid;
                $save_special_permission = User_special_permission::create($special_permission);

                if($save_special_permission){ 
                    
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
                    //  

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


    public function edit_special_permission_assign($uuid){

        $edit_special_permission_assign = User_special_permission::select('user_special_permissions.*', 'users.first_name as first_name', 'users.last_name as last_name' , 'permissions.permission as permission_name' , 'menus.name as menu_name')
        ->join('users', 'user_special_permissions.user_id', '=', 'users.id')
        ->join('permissions', 'user_special_permissions.permission_id', '=', 'permissions.id')
        ->join('menus', 'user_special_permissions.menu_id', '=', 'menus.id')
        ->where('user_special_permissions.uuid', $uuid)
        ->first();

        if($edit_special_permission_assign)
        {

            return response()->json([

                'status_code' => Response::HTTP_OK,
                'data' => $edit_special_permission_assign,

            ], Response::HTTP_OK);


        }else{

            return response()->json([

                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => $this->get_message('not_found'),

            ], Response::HTTP_NOT_FOUND);

        }


    }




    public function update_special_permission_assign(Request $request){
        

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


        try{
            
            // Get role_id from request headers
           $user_id = $request->header('user-id');
           
           // Delete existing permissions for the role
           User_special_permission::where('user_id', $user_id)->delete();

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
                   'user_id' => $user_id, // Assuming you also want to store role_id
                   'created_at' => now(),
                   'updated_at' => now(),
               ];
           }

           // Insert new permissions into the database
           $update_per_assign = User_special_permission::insert($new_permissions);
           

           if($update_per_assign){
               
               return response()->json([
                   
                   'status_code' => Response::HTTP_OK,
                   'message' => $this->get_message('update'),
               
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


    public function delete_special_permission_assign($uuid){

        try{

            $del_special_permission_assign = User_special_permission::where('uuid', $uuid)->first();
            
            if(!$del_special_permission_assign)
            {
                
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);


            }else{

                $delete_special_permission_assign = User_special_permission::destroy($del_special_permission_assign->id);

                if($delete_special_permission_assign){
                
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

    public function get_permission_menus()
    {
        try{
            
        $data = User_special_permission::with('menu', 'permission')->get();

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'data' => $data,
        ], Response::HTTP_OK);
    
         }catch (QueryException $e) {

            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR); 
            
        }
        
    }



    public function get_special_permission_assign()
{
    try {
        // Fetch the menu UUID and user ID from headers
        $menu_id = request()->header('menu-uuid');
        $user_id = request()->header('user-id');

        // Validate user
        $user = User::where('id', $user_id)->where('status', '1')->first();
        if (!$user) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => $this->get_message('not_found'),
            ], Response::HTTP_NOT_FOUND);
        }

        $auth_role_id = Auth::user()->role_id;

        // Validate menu
        $menu = Menu::where('uuid', $menu_id)->first();
        if (!$menu) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => $this->get_message('not_found'),
            ], Response::HTTP_NOT_FOUND);
        }

        // Initialize permissions
        $permissions = ['add' => 0, 'update' => 0, 'delete' => 0];

        // Check permissions for non-admin users
        if ($auth_role_id != '1') {
            $assigned_permissions = Permission_assign::where('role_id', $auth_role_id)
                ->where('menu_id', $menu->id)
                ->where('status', '1')
                ->pluck('permission_id')
                ->toArray();

            $permissions['add'] = in_array('1', $assigned_permissions) ? 1 : 0;
            $permissions['update'] = in_array('3', $assigned_permissions) ? 1 : 0;
            $permissions['delete'] = in_array('4', $assigned_permissions) ? 1 : 0;
        } else {
            // Admin users have full permissions
            $permissions = ['add' => 1, 'update' => 1, 'delete' => 1];
        }

        // Fetch user-specific permission assignments
        $user_permissions = User_special_permission::select(
            'user_special_permissions.*',
            'users.first_name as user_first_name',
            'users.last_name as user_last_name',
            'permissions.permission as permission_name',
            'menus.name as menu_name'
        )
            ->join('users', 'user_special_permissions.user_id', '=', 'users.id')
            ->join('permissions', 'user_special_permissions.permission_id', '=', 'permissions.id')
            ->join('menus', 'user_special_permissions.menu_id', '=', 'menus.id')
            ->where('user_special_permissions.user_id', $user_id)
            ->get();

        // Fetch active menus and permissions
        $active_menus = Menu::where('status', '1')->orderBy('name', 'asc')->get();
        $active_permissions = Permission::where('status', '1')->get();
        $active_language = Language::where('status', '1')->first();

        if (!$active_language) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => $this->get_message('not_found'),
            ], Response::HTTP_NOT_FOUND);
        }

        // Translate permissions
        $permissions_with_translations = $active_permissions->map(function ($permission) use ($active_language) {
            $translations = PermissionTranslation::where('permission_id', $permission->id)
                ->where('language_id', $active_language->id)
                ->get();

            return [
                'permission_id' => $permission->id,
                'permission_name' => $permission->permission,
                'translations' => $translations->map(function ($translation) use ($active_language) {
                    return [
                        'name_' . $active_language->code => $translation->name,
                        'language_code' => $active_language->code,
                    ];
                }),
            ];
        });

        // Translate menus
        $menus_with_translations = $active_menus->map(function ($menu) use ($active_language) {
            $translations = Menu_translation::where('menu_id', $menu->id)
                ->where('language_id', $active_language->id)
                ->get();

            return [
                'menu_id' => $menu->id,
                'menu_name' => $menu->name,
                'translations' => $translations->map(function ($translation) use ($active_language) {
                    return [
                        'name_' . $active_language->code => $translation->name,
                        'language_code' => $active_language->code,
                    ];
                }),
            ];
        });

        // Return response
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'new' => $permissions['add'],
            'update' => $permissions['update'],
            'delete' => $permissions['delete'],
            'data' => $user_permissions,
            'permissions' => $permissions_with_translations,
            'menus' => $menus_with_translations,
        ], Response::HTTP_OK);

    } catch (\Exception $e) {
        // Handle exceptions
        return response()->json([
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $this->get_message('server_error'),
            // Uncomment below for debugging purposes
            // 'error' => $e->getMessage(),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

public function permissions_menu(){
    $user_permissions = User_special_permission::select(
        'user_special_permissions.*',
        'users.first_name as user_first_name',
        'users.last_name as user_last_name',
        'permissions.permission as permission_name',
        'menus.name as menu_name'
    )
        ->join('users', 'user_special_permissions.user_id', '=', 'users.id')
        ->join('permissions', 'user_special_permissions.permission_id', '=', 'permissions.id')
        ->join('menus', 'user_special_permissions.menu_id', '=', 'menus.id')
        ->get();

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'data' => $user_permissions,
            ], Response::HTTP_OK);

}

    

}
