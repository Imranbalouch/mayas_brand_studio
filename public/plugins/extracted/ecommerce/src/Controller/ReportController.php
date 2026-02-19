<?php

namespace App\Http\Controllers\API\Ecommerce;

use DB;
use Exception; 
use App\Models\Ecommerce\Order; 
use App\Models\Ecommerce\Report; 
use App\Models\Ecommerce\Customer;
use App\Models\Ecommerce\Collection;
use Illuminate\Support\Str;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use App\Models\Ecommerce\PurchaseOrder;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\PermissionService; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator; 
use Symfony\Component\HttpFoundation\Response;
use Spatie\Activitylog\Models\Activity;


class ReportController extends Controller
{

  use MessageTrait;
  protected $permissionService;
  public function __construct(PermissionService $permissionService)
  {
    $this->permissionService = $permissionService;
  }

 public function order_report(Request $request)
    {
        try {
            $menuUuid = $request->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);
    
            if (!$permissions['view']) {
                if (!Auth::user()->hasPermission('viewglobal')) {
                    return response()->json([
                        'message' => 'You do not have permission to view this menu'
                    ], Response::HTTP_FORBIDDEN);
                }
            }
    
            $orders = Order::query()
                ->with([
                    'orderDetails',
                    'customer',
                    //'channel',
                    'tracking'
                ])
                ->select([
                    'orders.*',
                    \DB::raw('CAST(((SELECT SUM(product_qty) FROM order_details WHERE order_details.order_id = orders.uuid) ) AS UNSIGNED) as items_count')
                ])
                ->orderBy('id', 'desc');
    
            if ($permissions['view'] && !$permissions['viewglobal']) {
                $orders->where('auth_id', Auth::user()->uuid);
            }
    
            $orders = $orders->get();
    
            $orders->transform(function ($order) {
                $order->tags = json_decode($order->tags, true);
                return $order;
            });
    
            return response()->json([
                'status_code' => 200,
                'permissions' => $permissions,
                'data' => $orders,
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => 500,
                'message' => 'An error occurred while fetching the orders.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function customer_report()
    {
        try {
            $menuUuid = request()->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);
    
            $customers = Customer::orderBy('id', 'desc');
    
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $customers = $customers->where('auth_id', Auth::user()->uuid);
                }
            } else {
                if (Auth::user()->hasPermission('viewglobal')) {
                    $customers = $customers;
                } else {
                    return response()->json([
                        'message' => 'You do not have permission to view this menu'
                    ], Response::HTTP_FORBIDDEN);
                }
            }
    
            $customers = $customers->with('address')->get();       
    
            return response()->json([
                'status_code' => 200,
                'permissions' => $permissions,
                'data' => $customers, 
            ], 200);
        } catch (\Exception $e) {
            Log::error('Customer List Error: ' . $e->getMessage());
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function purchase_order_report()
    {
        try{
            $menuUuid = request()->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);
            $purchaseOrders = PurchaseOrder::select('uuid','po_number','supplier_id','warehouse_id','payment_term_id','supplier_currency_id','ship_date','ship_carrier_id','tracking_number','reference_number','note_to_supplier','tags','status','total_tax','total_amount')->with('supplier:uuid,company','warehouse:uuid,location_name','paymentterm:uuid,name','currency:uuid,name,symbol,code','shipcarrier:uuid,name','purchaseOrderitemReceiving:uuid,po_id,received_date,accept_qty,reject_qty')->orderBy('id', 'desc');
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $purchaseOrders = $purchaseOrders->where('auth_id', Auth::user()->uuid);
                }
            }else{
                if (Auth::user()->hasPermission('viewglobal')) {
                    $purchaseOrders = $purchaseOrders;
                } else {
                    return response()->json([
                        'message' => 'You do not have permission to view this menu'
                    ], Response::HTTP_FORBIDDEN);
                }
            }
            $purchaseOrders = $purchaseOrders->get();
            return response()->json([
                'status_code'=>200,
                'permissions' => $permissions,
                'data'=>$purchaseOrders
            ],200);

        }catch (\Exception $e) { 
            // Handle general exceptions
            Log::error('Purchase Order List Error:'.$e->getMessage());
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
    }

    public function collection_report()
    {
        try{
            $menuUuid = request()->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);
            $collections = Collection::withCount('products')->orderBy('id', 'desc');
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $collections = $collections->where('auth_id', Auth::user()->uuid);
                }
            }else{
                if (Auth::user()->hasPermission('viewglobal')) {
                    $collections = $collections;
                } else {
                    return response()->json([
                        'message' => 'You do not have permission to view this menu'
                    ], Response::HTTP_FORBIDDEN);
                }
            } 
            $collections = $collections->get();

            return response()->json([
                'status_code'=>200,
                'permissions' => $permissions,
                'data'=>$collections
            ],200);

        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
    }
    public function activity_report(Request $request){
   
        // $get_all_activity = Activity::orderBy('created_at', 'desc')->join('users', 'activity_log.causer_id', '=', 'users.id')
        // ->select('activity_log.*', 'users.first_name as causer_name')
        // ->paginate(12);

        // return response()->json([
        //     'status_code' => Response::HTTP_OK,
        //     'data' => $get_all_activity,
        // ], Response::HTTP_OK);
        // Columns for sorting (update according to your columns)
        $columns = ['id', 'description', 'causer_name', 'created_at'];

        // DataTables pagination parameters
        $limit = $request->input('length', 12); // Default to 12 if not provided
        $start = $request->input('start', 0);
        // $orderColumn = $columns[$request->input('order.0.column', 0)];
        $searchValue = $request->input('search.value', '');
       
        // Query with join, search, and order
        $query = Activity::orderBy('created_at', 'desc')
            ->join('users', 'activity_log.causer_id', '=', 'users.id')
            ->select('activity_log.*', 'users.first_name as causer_name');
           
        if (!empty($searchValue)) {
            $query->where(function($q) use ($searchValue) {
                $q->where('log_name', 'LIKE', "%{$searchValue}%")
                  ->orWhere('users.first_name', 'LIKE', "%{$searchValue}%")
                  ->orWhere('event', 'LIKE', "%{$searchValue}%");
            });
            }

        $totalData = Activity::count(); // Total records count
        $totalFiltered = $query->count(); // Filtered records count

        // Apply pagination
      
        $data = $query->offset($start)
                    ->limit($limit)
                    ->get();
        // Format response for DataTables
        $json_data = [
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data
        ];
        return response()->json([
            'status_code' => Response::HTTP_OK,
            'data' => $json_data,
        ], Response::HTTP_OK);
    
    }

}
