<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Support\Str;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Services\PermissionService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\User_special_permission;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;

class StaffController extends Controller
{  
      use MessageTrait;

      protected $permissionService;

      public function __construct(PermissionService $permissionService)
      {
          $this->permissionService = $permissionService;
      }

        public function get_staff(Request $request)
    {
        try {
            $menuUuid = $request->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);

            if (!$permissions['view']) {
                if (!Auth::user()->hasPermission('viewglobal')) {
                    return response()->json([
                        'message' => 'You do not have permission to view staff information'
                    ], Response::HTTP_FORBIDDEN);
                }
            }

            $staff = User::with('specialPermissions')
                ->orderBy('id', 'desc');

            if ($permissions['view'] && !$permissions['viewglobal']) {
                $staff->where('auth_id', Auth::user()->uuid);
            }

            $staff = $staff->get();

            return response()->json([
                'status_code' => 200,
                'data' => $staff
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'An error occurred while fetching staff data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



      public function add_staff(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|min:3|max:30|regex:/^[a-zA-Z0-9\s\-]+$/',
                'last_name' => 'nullable|min:3|max:40|regex:/^[a-zA-Z0-9\s\-]+$/',
                'email' => 'required|max:254|email|unique:users,email',
                'menus' => 'nullable|array', 
                'menus.*.permissions' => 'required|array',
                'menus.*.permissions.*' => 'required|exists:permissions,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $password = Str::random(10); 
            $hashedPassword = bcrypt($password);

            $userData = [
                'uuid' => Str::uuid(),
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => $hashedPassword,
                'ip' => $request->ip(),
                'auth_id' => Auth::user()->uuid,
            ];
            

            DB::beginTransaction();

            $save_user = User::create($userData);

            $defaultRole = Role::where('by_default', 1)->first();

            if ($defaultRole) {
                $save_user->role_id = $defaultRole->id;
                $save_user->save();
            }

            if($save_user) {
                if ($request->has('menus') && is_array($request->menus)) {
                    foreach ($request->menus as $menuItem) {
                        $menu = Menu::find($menuItem['menu_id']);
                        if (!$menu) {
                            DB::rollBack();
                            return response()->json([
                                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                                'message' => 'Invalid menu ID: ' . $menuItem['menu_id']
                            ], Response::HTTP_UNPROCESSABLE_ENTITY);
                        }
                
                        foreach ($menuItem['permissions'] as $permission_id) {
                            $permission = Permission::find($permission_id);
                            if (!$permission) {
                                DB::rollBack();
                                return response()->json([
                                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                                    'message' => 'Invalid permission ID: ' . $permission_id
                                ], Response::HTTP_UNPROCESSABLE_ENTITY);
                            }
                
                            User_special_permission::create([
                                'uuid' => Str::uuid(),
                                'auth_id' => Auth::user()->uuid,
                                'user_id' => $save_user->id,
                                'menu_id' => $menu->id,
                                'permission_id' => $permission->id,
                            ]);
                        }
                    }
                }
                    
                $data = [
                    'details'=>[
                        'WebsiteName' => config('app.name'),
                        'heading' => "Welcome to the Team!",
                        'FromEmail' => config('app.from_email'),
                        'FName' => $save_user->first_name, 
                        'LName' => $save_user->last_name, 
                        'SignupEmail' => $request->email, 
                        'hi_message' => $request->first_name.' '.$request->last_name,
                        'currentDate'  => Carbon::now()->format('d-M-Y'),
                    ]
                ];
                
                Mail::send('emailtemplate/welcome_email', $data, function($message) use ($data){
                    $message->from($data['details']['FromEmail'], $data['details']['WebsiteName']); 
                    $message->to($data['details']['SignupEmail'])->subject($data['details']['heading']);
                });

                DB::commit();
                
                return response()->json([
                    'status_code' => 200,
                    'message' => $this->get_message('add'),
                ], 200);
            }
        } catch (QueryException $e) {
            DB::rollBack();
            
            if ($e->getCode() === '23000') {
                return response()->json([
                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => $this->get_message('conflict'),
                    'error' => $e->getMessage(),
                ], Response::HTTP_CONFLICT);
            }

            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function edit_staff($uuid){
        try {
            
            $edit_staff = User::with('specialPermissions')->where('uuid', $uuid)->first();
            $edit_staff_translation = User::where('uuid', $uuid)->first();

            if($edit_staff)
            {

                return response()->json([

                    'status_code' => Response::HTTP_OK,
                    'data' => $edit_staff,

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

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
    }

      public function update_staff(Request $request, $uuid)
    {
        try {
            $user = User::where('uuid', $uuid)->first();
            
            if (!$user) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Staff not found'
                ], Response::HTTP_NOT_FOUND);
            }

            $validator = Validator::make($request->all(), [
                'first_name' => 'required|min:3|max:30|regex:/^[a-zA-Z0-9\s\-]+$/',
                'last_name' => 'nullable|min:3|max:40|regex:/^[a-zA-Z0-9\s\-]+$/',
                'email' => 'required|max:254|email|unique:users,email,' . $user->id,
                'notification' => 'in:0,1',
                'menus' => 'nullable|array',
                'menus.*.permissions' => 'required|array',
                'menus.*.permissions.*' => 'required|exists:permissions,id' 
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            DB::beginTransaction();

            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->bio = $request->bio;
            $user->personal_website = $request->personal_website;
            $user->notification = $request->notification;
            $user->auth_id = Auth::user()->uuid;
            
            $user->save();

            if ($request->has('menus') && is_array($request->menus)) {
                // First delete existing permissions for this user
                User_special_permission::where('user_id', $user->id)->delete();
                
                // Then add new permissions
                foreach ($request->menus as $menuItem) {
                    $menu = Menu::find($menuItem['menu_id']);
                    
                    if (!$menu) {
                        throw new \Exception("Invalid menu ID: {$menuItem['menu_id']}");
                    }

                    foreach ($menuItem['permissions'] as $permission_id) {
                        $permission = Permission::find($permission_id);
                        
                        if (!$permission) {
                            throw new \Exception("Invalid permission ID: {$permission_id}");
                        }

                        // Check if this permission already exists to avoid duplicates
                        $exists = User_special_permission::where([
                            'user_id' => $user->id,
                            'menu_id' => $menu->id,
                            'permission_id' => $permission->id
                        ])->exists();

                        if (!$exists) {
                            User_special_permission::create([
                                'uuid' => Str::uuid(),
                                'auth_id' => Auth::user()->uuid,
                                'user_id' => $user->id,
                                'menu_id' => $menu->id,
                                'permission_id' => $permission->id,
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            
            return response()->json([
                'status_code' => 200,
                'message' => $this->get_message('update'),
            ], 200);
            
        } catch (QueryException $e) {
            DB::rollBack();
            
            if ($e->getCode() === '23000') {
                return response()->json([
                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => $this->get_message('conflict'),
                    'error' => $e->getMessage(),
                ], Response::HTTP_CONFLICT);
            }

            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete_staff(Request $request, $uuid)
    {
        try {
            $user = User::where('uuid', $uuid)->first();
            
            if (!$user) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Staff not found'
                ], Response::HTTP_NOT_FOUND);
            }

            DB::beginTransaction();

            User_special_permission::where('user_id', $user->id)->delete();
            
            $user->delete();
            
            DB::commit();
            
            return response()->json([
                'status_code' => 200,
                'message' => $this->get_message('delete'),
            ], 200);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update_staff_status(Request $request, $uuid)
    {
        $request->validate([
            'status' => 'required|in:0,1', 
        ]);

        try {
            
            $user = User::where('uuid', $uuid)->first();

            if ($user) {

                // Update the status
                $user->status = $request->status;
                $user->save();

                return response()->json([
                    'status_code' => 200,
                    'message' => $this->get_message('update'),
                ], 200);

            } else {

                return response()->json([
                    'status_code' => 404,
                    'message' => $this->get_message('not_found'),
                ], 404);

            }
        } catch (\Throwable $e) {
            
            return response()->json([
                'status_code' => 500,
                'message' => $this->get_message('server_error'),
            ], 500);

        }

    }

}
