<?php

namespace App\Http\Controllers\API\Ecommerce;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;
use Exception; 
use Mail;
use Auth;
use Session;
use Hash;
use DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\Ecommerce\Currency;
use App\Models\Menu;
use App\Models\Permission_assign;
use App\Services\PermissionService;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Str;
use App\Traits\MessageTrait;


class CurrencyController extends Controller
{

    use MessageTrait;
    protected $permissionService;
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function add_currency(Request $request){
        
        $validator = Validator::make($request->all(), [
             
            'name' => 'required|regex:/^[a-zA-Z0-9\s\-]+$/',
             

        ]);

        if($validator->fails()){
            
            $message = $validator->messages();
            
            return response()->json([
                
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        }

        try {

            $check_if_already = Currency::where('name', $request->name)->get();

            if(count($check_if_already) > 0){

                return response()->json([ 
                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => $this->get_message('conflict'),

                ], Response::HTTP_CONFLICT); // 409 Conflict 


            }else{

                $currency = $request->all();
                $currency['uuid'] = Str::uuid();
                $currency['auth_id'] = Auth::user()->uuid;  

                $save_currency = Currency::create($currency);

                if($save_currency){ 

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


    public function edit_currency($uuid){

        
        try {
            
            $edit_currency = Currency::where('uuid', $uuid)->first();
            $edit_currency_translation = Currency::where('uuid', $uuid)->first();

            if($edit_currency)
            {

                return response()->json([

                    'status_code' => Response::HTTP_OK,
                    'data' => $edit_currency,

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


    public function update_currency(Request $request){
        
        $validator = Validator::make($request->all(), [
             
            'name' => 'required|regex:/^[a-zA-Z0-9\s\-]+$/', 
            'is_default' => 'required|numeric|in:0,1',
            'status' => 'required|numeric|in:0,1',
        
        ]);

        if($validator->fails()){
            
            $message = $validator->messages();
            
            return response()->json([
                
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => strval($validator->errors())
            
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        }

        
        try{
            
            $uuid = $request->header('uuid');
            $upd_currency = Currency::where('uuid', $uuid)->first();

            if (!$upd_currency) {

                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);

            }

             
            $upd_currency->name = $request->name; 
            $upd_currency->status = $request->status;
                        
            if($request->is_default == 1) {
                $upd_currency->is_default = $request->is_default;
            }

            if($request->is_admin_default == 1) {
                $upd_currency->is_admin_default = $request->is_admin_default;
            }

            $update_currency = $upd_currency->save();
            
            if($update_currency){
                
                if($request->is_default == 1) {
                    Currency::query()->update(['is_default' => 0]);
                    Currency::where('id', $upd_currency->id)->update(['is_default' => 1]);
                }

                if($request->is_admin_default == 1) {
                    Currency::query()->update(['is_admin_default' => 0]);
                    Currency::where('id', $upd_currency->id)->update(['is_admin_default' => 1]);
                }
                
                return response()->json([
                    
                    'status_code' => Response::HTTP_OK,
                    'message' => $this->get_message('update'),
                
                ], Response::HTTP_OK);

            }else{

                Currency::where('code', 'us')->update(['is_default' => 1]);
                return response()->json([
                    
                    'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => $this->get_message('server_error'),

                ], Response::HTTP_INTERNAL_SERVER_ERROR);

            }


        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }

        
    }


    public function delete_currency($uuid){

        try{

            $del_currency = Currency::where('uuid', $uuid)->first();
            
            if(!$del_currency)
            {
            
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                    
                ], Response::HTTP_NOT_FOUND);

         
            }else{

                $check_if_is_default = Currency::where('uuid', $uuid)->where('is_default', '1')->first();
                
                if($check_if_is_default)
                {

                    return response()->json([
                            
                        'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                        'message' => $this->get_message('can_not_delete'),
                    
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);

                }
                else{

                    $delete_currency = Currency::destroy($del_currency->id);

                    if($delete_currency){
                    
                        return response()->json([
                            
                            'status_code' => Response::HTTP_OK,
                            'message' => $this->get_message('delete'),
                        
                        ], Response::HTTP_OK);
        
                    }

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


    public function get_currency(){


        try {  
                $menuUuid = request()->header('menu-uuid'); 
                $permissions = $this->permissionService->checkPermissions($menuUuid); 
                $get_all_currencies = Currency::orderBy('id', 'desc');
                if ($permissions['view']) {
                    if (!$permissions['viewglobal']) {
                        $get_all_currencies = $get_all_currencies->where('auth_id', Auth::user()->uuid);
                    }
                }else{
                    if (Auth::user()->hasPermission('viewglobal')) {
                        $get_all_currencies = $get_all_currencies;
                    } else {
                        return response()->json(['message' => 'You do not have permission to view this menu'], Response::HTTP_FORBIDDEN);
                    }
                }

            $get_all_currencies = $get_all_currencies->get();

            if($get_all_currencies){
                
                return response()->json([
                        
                    'status_code' => Response::HTTP_OK, 
                    'data' => $get_all_currencies,
                    'permissions' => $permissions,
                    

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



    public function get_active_currencies(){  
        try {   
            $get_all_currencies = Currency::where('status' , '1')->orderBy('name', 'asc');   
            $get_all_currencies = $get_all_currencies->get(); 
            if($get_all_currencies){ 
                return response()->json([ 
                    'status_code' => Response::HTTP_OK, 
                    'data' => $get_all_currencies, 
                ], Response::HTTP_OK);  
            } 
        }catch (\Exception $e) { 
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        
        } 

            
        
    }

 
    public function updateCurrencyStatus(Request $request, string $id)
    {
        
        $request->validate([
            'status' => 'required|in:0,1', // Ensure status is either 0 or 1
        ]);

        try {
            // Find the Currency by UUID and active status
            $langage = Currency::where('uuid', $id)->first();
            //dd($langage);
            if ($langage) {
                // Update the status
                $langage->status = $request->status;
                $langage->save();

                return response()->json([
                    'status_code' => 200,
                    'message' => $this->get_message('update'), // Ensure `get_message` is properly defined
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