<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;
use Mail;
use Auth;
use Session;
use DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon; 
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ActivityLogController extends Controller
{
    
    public function get_all_activity(Request $request){
   
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

    public function get_active_activity(Request $request){
   
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
    

    public function get_own_activity(){
        
        $authid = Auth::user()->uuid;
        $user = User::where('auth_id', $authid)->first();

        if ($user){

            $get_own_activity = Activity::where('causer_id', $user->id)
            ->where('causer_type', get_class($user))
            ->get();

        }

        return response()->json([
                    
            'status_code' => Response::HTTP_OK,
            'data' => $get_own_activity,

        ], Response::HTTP_OK);
    
    }


    public function get_activity_by_id($uuid){

        $user = User::where('uuid', $uuid)->first();
        $get_activity_by_id = Activity::where('causer_id', $user->id)->get();

        return response()->json([
                    
            'status_code' => Response::HTTP_OK,
            'data' => $get_activity_by_id,

        ], Response::HTTP_OK);
    
    }

    function cleanActivityLog(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'password' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'warning',
                    'message' => $validator->errors()->first(),
                ]);
            }
            $user = auth()->user()->password;
            $passwordCheck = Hash::check($request->password,$user);
            if ($passwordCheck) {
                $activity = Activity::truncate();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Clear All logs',
                    'data' => null,
                ],200);
            }else{
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid Password!',
                    'data' => null,
                ],200);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => 'error',
                'message' => $th->getMessage(),
                'data' => null,
            ],500);
        }
    }

    public function show($id) {
        $data = Activity::join('users', 'activity_log.causer_id', '=', 'users.id')
        ->select('activity_log.*', 'users.first_name as causer_name')->where('activity_log.id',$id)->first();
        // if ($data->log_name == 'Login') {
        //     $data = [
        //         'log_name' => $data->log_name,
        //         'description' => $data->description,
        //         'causer_name' => $data->causer_name,
        //         'created_at' => $data->created_at,
        //         ''
        //     ];
        // }else{

        // }

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'data' => $data,
        ], Response::HTTP_OK);
    }
}
