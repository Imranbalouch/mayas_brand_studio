<?php

// app/Services/PermissionService.php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use App\Models\Menu;
use App\Models\Permission_assign;
use App\Models\User_special_permission;
use Symfony\Component\HttpFoundation\Response;

class PermissionService
{
    public function checkPermissions($menuUuid)
    {
        $authUser = Auth::user();
        $roleId = $authUser->role_id;
        $userId = $authUser->id;

        // Initialize permissions
        $permissions = [
            'add' => 0,
            'edit' => 0,
            'update' => 0,
            'delete' => 0,
            'changepassword' => 0,
            'view' => 0,
            'viewglobal' => 0,
            'attributevalues' => 0
        ];

        // Get the specified menu
        $menu = Menu::where('uuid', $menuUuid)->first();
        
        if (!$menu) {
            return response()->json([
                'status_code' => Response::HTTP_FORBIDDEN,
                'message' => 'Menu Not Found',
            ], Response::HTTP_FORBIDDEN);
        }

        if ($roleId != "1") {
            // Get permissions based on role and user-specific permissions for the menu
            $rolePermissions = Permission_assign::where('role_id', $roleId)
                ->where('menu_id', $menu->id)
                ->get();

            $userSpecialPermissions = User_special_permission::where('user_id', $userId)
                ->where('menu_id', $menu->id)
                ->get();
        
            // Check permissions from both sources
            foreach ($rolePermissions as $permission) {
                $permissions[$this->getPermissionKey($permission->permission_id)] = 1;
            }

            foreach ($userSpecialPermissions as $permission) {
                $permissions[$this->getPermissionKey($permission->permission_id)] = 1;
            }

            // Check view permission
            if (!$permissions['view'] && !Auth::user()->hasPermission('viewglobal')) {
                return response()->json([
                    'status_code' => Response::HTTP_FORBIDDEN,
                    'message' => 'You do not have permission to view this menu',
                ], Response::HTTP_FORBIDDEN);
            }

        } else {
            // Admin has all permissions
            $permissions = array_fill_keys(array_keys($permissions), 1);
        }

        return $permissions;
    }

    private function getPermissionKey($permissionId)
    {
        $permissionMap = [
            '1' => 'add',
            '2' => 'edit',
            '3' => 'update',
            '4' => 'delete',
            '5' => 'view',
            '6' => 'viewglobal',
            '9' => 'changepassword',
            '12' => 'attributevalues'
        ];
        return $permissionMap[$permissionId] ?? null;
    }
}