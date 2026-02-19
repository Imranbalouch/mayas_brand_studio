<?php

namespace App\Http\Controllers\API\Ecommerce;

use Illuminate\Http\Request;
use App\Services\PermissionService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Ecommerce\MasterImportProductLog;
use Symfony\Component\HttpFoundation\Response;

class ImportProductLogController extends Controller
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function getImportLogs(Request $request)
    {
        try {
            $menuUuid = $request->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);

            if (!$permissions['view'] && !Auth::user()->hasPermission('viewglobal')) {
                return response()->json([
                    'message' => 'You do not have permission to view this menu'
                ], Response::HTTP_FORBIDDEN);
            }

            $importLogs = MasterImportProductLog::query()
                ->with(['importProductLogs', 'user'])
                ->orderBy('created_at', 'desc');

            if ($permissions['view'] && !$permissions['viewglobal']) {
                $importLogs->where('auth_id', Auth::user()->uuid);
            }

            $importLogs = $importLogs->get();
          
            return response()->json([
                'status_code' => 200,
                'permissions' => $permissions,
                'data' => $importLogs,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'An error occurred while fetching the import logs.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getSpecificImportLog($uuid)
    {
        try {
            $importLog = MasterImportProductLog::where('uuid', $uuid)
                ->with(['importProductLogs'])
                ->first();

            if (!$importLog) {
                return response()->json([
                    'status_code' => 404,
                    'message' => 'Import log not found.',
                ], 404);
            }

            return response()->json([
                'status_code' => 200,
                'data' => $importLog,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'An error occurred while fetching the import log details.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
