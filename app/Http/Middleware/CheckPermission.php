<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;
use App\Models\User_special_permission;
use App\Models\Permission_assign;
use App\Models\Menu;
use App\Models\Permission;
use App\Traits\MessageTrait;


class CheckPermission
{
    use MessageTrait;

    public function handle($request, Closure $next, $action)
    {   
        // Ensure the user is authenticated
        $user = Auth::guard('api')->user();
     
        if (!$user){
            return response()->json([
                'status_code' => Response::HTTP_UNAUTHORIZED,
                'message' => $this->get_message('not_permission'),
            ], Response::HTTP_UNAUTHORIZED);
        }

        $menuUuid = $request->header('menu-uuid');

        // Retrieve the menu ID using the menu UUID
        $menu = Menu::where('uuid', $menuUuid)->first();
        if (!$menu) {
            return response()->json([
                'status_code' => Response::HTTP_FORBIDDEN,
                'message' => $this->get_message('not_permission'),
            ], Response::HTTP_FORBIDDEN);
        }
        
        if (in_array($action, ['view', 'viewglobal'])) {
            $hasViewPermission = $this->hasPermission($user, 'view', $menu->id);
            $hasViewGlobalPermission = $this->hasPermission($user, 'viewglobal', $menu->id);
            if ($hasViewPermission || $hasViewGlobalPermission) {
                return $next($request);
            }
        }
        // Check if the user has the required permission
        // dd($user, $action, $menu->id);
        
        if ($this->hasPermission($user, $action, $menu->id)) {
            return $next($request);
        } else {
            // If the user does not have the required permission, return a 403 error
            return response()->json([
                'status_code' => Response::HTTP_FORBIDDEN,
                'message' => $this->get_message('not_permission'),
            ], Response::HTTP_FORBIDDEN);
        }
    }

    private function hasPermission($user, $action, $menuId)
    {
        
        // If the user's role_id is 1, grant all permissions
        if ($user->role_id == 1) {
            return true;
        }
        
        // Retrieve the permission ID based on the action
        $permission = Permission::where('permissionkey', $action)->first();
        
        if (!$permission) {
            return false;
        }
        
        // Check user special permissions
        $specialPermission = User_special_permission::where('user_id', $user->id)
            ->where('permission_id', $permission->id)
            ->where('menu_id', $menuId)
            ->exists();

        if ($specialPermission) {
            return true;
        }

        // Check role-based permissions
        $rolePermission = Permission_assign::where('role_id', $user->role_id)
            ->where('permission_id', $permission->id)
            ->where('menu_id', $menuId)
            ->where('status', '1')
            ->exists();

        return $rolePermission;
    }
}

