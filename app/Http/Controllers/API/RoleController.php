<?php

namespace App\Http\Controllers\API;

use DB;
use Hash;
use Mail;
use Exception;
use Carbon\Carbon; 
use App\Models\Menu;
use App\Models\User;
use App\Models\Role; 
use App\Models\Language;
use App\Models\Permission;
use Illuminate\Support\Str;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use App\Models\RoleTranslation;
use App\Models\Permission_assign;
use App\Services\PermissionService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User_special_permission;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;


class RoleController extends Controller
{
    
    use MessageTrait;
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function add_role(Request $request){
        
        $validator = Validator::make($request->all(), [
             
            'role' => 'required|regex:/^[a-zA-Z0-9\s\-]+$/|max:50',
            'status' => 'required|numeric',
            'by_default' => 'nullable|numeric',
        
        ]);


        if($validator->fails()) {
            
            $message = $validator->messages();
            
            return response()->json([
                    
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        }


        try {
            
            $check_if_already = Role::where('role', $request->role)->where('auth_id', $request->auth_id)->get();

            if(count($check_if_already) > 0){

                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => $this->get_message('conflict'),

                ], Response::HTTP_CONFLICT); // 409 Conflict 


            }else{
                if($request->by_default == 1) {
                    $default_exists = Role::where('auth_id', Auth::user()->uuid)
                        ->where('by_default', 1)
                        ->exists();
                        
                    if($default_exists) {
                        return response()->json([
                            'status_code' => Response::HTTP_CONFLICT,
                            'message' => 'A default role already exists. Only one role can be set as default.',
                        ], Response::HTTP_CONFLICT); 
                    }
                }
        
                
                $role = $request->all();
                $role['uuid'] = Str::uuid();
                $role['auth_id'] = Auth::user()->uuid; 
                $save_role = Role::create($role);

                if($save_role)
                {

                    return response()->json([
                            
                        'status_code' => Response::HTTP_CREATED,
                        'message' => 'Role added successfully',

                    ], Response::HTTP_CREATED);

                }

            }
            

        }catch (QueryException $e) {
            
            if ($e->getCode() === '23000') { // SQLSTATE code for integrity constraint violation
                // Handle unique constraint violation
                return response()->json([

                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'Role already exists',

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


    public function edit_role($uuid){

        try {
            
            $menu_id = request()->header('menu-uuid');
            $get_menu = Menu::where('uuid', $menu_id)->first();
            $edit_role_by_id = Role::where('uuid', $uuid)->first();

            if(!$edit_role_by_id)
            {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            $role_translation_get = RoleTranslation::where('role_id', $edit_role_by_id->id)
            ->where('status', '1')->get();
            
            $get_active_language = Language::where('status', '1')->get();

            $now = Carbon::now();
            $auth_id = Auth::user()->uuid;

            if(count($get_active_language) > 0){

                foreach($get_active_language as $key => $language){
                    
                    $check_role_translation = RoleTranslation::where('role_id', $edit_role_by_id->id)
                    ->where('language_id', $language->id)
                    ->where('status', '1')->first();

                    if($check_role_translation)
                    {
                        
                       

                    }
                    else{

                        $save_role_translation = RoleTranslation::insert([
                            ['uuid' => Str::uuid(), 'role_id' => $edit_role_by_id->id, 'name' => $edit_role_by_id->role , 'language_id' => $language->id , 'auth_id' => $auth_id , 'created_at' => $now, 'updated_at' => $now],
                        ]);

                    }


                }


            }

            $role_translations = RoleTranslation::where('role_id', $edit_role_by_id->id)
            ->where('role_translations.status', '1')
            ->join('languages', 'role_translations.language_id', '=', 'languages.id')
            ->select('languages.code as language_code', 'languages.name as language_name' , 'languages.flag as flag' , 'languages.rtl as dir', 'role_translations.*')
            ->get();

            
            if ($edit_role_by_id) {

                $edit_role_by_id->translations = $role_translations;
       
                return response()->json([

                    'status_code' => Response::HTTP_OK,
                    'data' => $edit_role_by_id,

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


    public function update_role(Request $request){
        $inputs = $request->all();
        $rules = [];
        foreach ($inputs as $key => $value) {
            if (strpos($key, 'name_') === 0) {
                $rules[$key] = 'required|string|max:50';
            }
        }
        $rules['status'] = 'required|numeric';
        $validator = Validator::make($request->all(), $rules);
        
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

            $upd_role = Role::where('uuid', $uuid)->first();
            
            if(!$upd_role){
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);
            }

            if($request->by_default == 1 && $upd_role->by_default != 1) {
                $default_exists = Role::where('auth_id', $upd_role->auth_id)
                    ->where('by_default', 1)
                    ->exists();
                    
                if($default_exists) {
                    return response()->json([
                        'status_code' => Response::HTTP_CONFLICT,
                        'message' => 'A default role already exists. Only one role can be set as default.',
                    ], Response::HTTP_CONFLICT);
                }
            }

            $upd_role->status = $request->status;  
            $upd_role->by_default = $request->by_default;  
            $update_role = $upd_role->save(); 
            foreach ($request->all() as $key => $value) {
                
                if (strpos($key, 'name_') === 0) {
                    
                    $languageCode = substr($key, 5);
            
                    $languageId = DB::table('languages')->where('code', $languageCode)->value('id');
            
                    if($languageId){
                        
                        RoleTranslation::where('language_id', $languageId)
                        ->where('role_id', $upd_role->id)
                        ->update(['name' => $value]);
                    }

                }

            }


            $get_active_language = Language::where('status', '1')->where('is_default', '1')->first();
            $get_role_trans_by_def_lang = RoleTranslation::where('role_id', $upd_role->id)
            ->where('language_id', $get_active_language->id)
            ->first();
            if (!$get_role_trans_by_def_lang) {
                $get_role_trans_by_def_lang = new RoleTranslation();
                $get_role_trans_by_def_lang->role_id = $upd_role->id;
                $get_role_trans_by_def_lang->uuid = $upd_role->uuid;
                $get_role_trans_by_def_lang->auth_id = $upd_role->auth_id;
                $get_role_trans_by_def_lang->language_id = $get_active_language->id;
                $get_role_trans_by_def_lang->name = $upd_role->role; 
                $get_role_trans_by_def_lang->save();
            }
            $upd_role2 = DB::table('roles')->where('id', $upd_role->id)->update(['role' => $get_role_trans_by_def_lang->name]);
                
            return response()->json([
            
                'status_code' => Response::HTTP_OK,
                'message' => 'Role updated successfully',

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


    public function delete_role($uuid) {
        try {
            $del_role = Role::where('uuid', $uuid)->first();
            
            if (!$del_role) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }
            
            $users_with_role = User::where('role_id', $del_role->id)
                                  ->exists();
            
            if ($users_with_role) {
                return response()->json([
                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => 'Cannot delete role as it is assigned to one or more staff',
                ], Response::HTTP_CONFLICT);
            }
    
            $delete_role = Role::destroy($del_role->id);
    
            if ($delete_role) {
                $del_role_translation = RoleTranslation::where('role_id', $del_role->id)->delete();
                $del_role_assign = Permission_assign::where('role_id', $del_role->id)->delete();
        
                return response()->json([
                    'status_code' => Response::HTTP_OK,
                    'message' => 'Role deleted successfully',
                ], Response::HTTP_OK);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function get_roles(){
        try{
            $menuUuid = request()->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);
            $get_all_roles = Role::where('id','!=',1)->orderBy('created_at', 'desc');
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $get_all_roles = $get_all_roles->where('auth_id', Auth::user()->uuid);
                }
            }else{
                if (Auth::user()->hasPermission('viewglobal')) {
                    $get_all_roles = $get_all_roles;
                } else {
                    return response()->json(['message' => 'You do not have permission to view this menu'], Response::HTTP_FORBIDDEN);
                }
            }
            $get_all_roles = $get_all_roles->get();
            return response()->json([
                'status_code'=>200,
                'permissions' => $permissions,
                'data'=>$get_all_roles
            ],200);
        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        } 
        
    }


    public function get_active_roles(){

        try{

            $get_all_active_roles = Role::where('id','!=',1)->where('status', '1')->get();

            if($get_all_active_roles){
                
                return response()->json([
                        
                    'status_code' => Response::HTTP_OK,
                    'data' => $get_all_active_roles,
                
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
