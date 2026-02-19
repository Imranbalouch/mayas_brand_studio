<?php

namespace App\Http\Controllers\API\Ecommerce;

use App\Models\Ecommerce\OrderPayment;
use Illuminate\Http\Request;
use App\Services\PermissionService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PaymentLogController extends Controller
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function getPaymentLogs(Request $request)
    {
        try {
            $menuUuid = $request->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);

            if (!$permissions['view'] && !Auth::user()->hasPermission('viewglobal')) {
                return response()->json([
                    'message' => 'You do not have permission to view this menu'
                ], Response::HTTP_FORBIDDEN);
            }

            $paymentLogs = OrderPayment::query()
                ->with(['order' => function($query) {
                    $query->select('uuid', 'code', 'customer_id', 'grand_total', 'mark_as_paid', 'payment_method')
                          ->with(['customer:uuid,first_name,last_name,email']);
                }])
                ->orderBy('created_at', 'desc');

            if ($permissions['view'] && !$permissions['viewglobal']) {
                $paymentLogs->whereHas('order', function($query) {
                    $query->where('auth_id', Auth::user()->uuid);
                });
            }

            $paymentLogs = $paymentLogs->get();
          
            return response()->json([
                'status_code' => 200,
                'permissions' => $permissions,
                'data' => $paymentLogs,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'An error occurred while fetching payment logs.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}