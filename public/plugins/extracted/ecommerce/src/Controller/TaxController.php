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
use App\Models\Ecommerce\Tax;
use App\Models\Menu;
use App\Models\Permission_assign;
use App\Services\PermissionService;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Str;
use App\Traits\MessageTrait;


class TaxController extends Controller
{

    use MessageTrait;
    protected $permissionService;
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function add_tax(Request $request){
        
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

            $check_if_already = Tax::where('name', $request->name)->get();

            if(count($check_if_already) > 0){

                return response()->json([ 
                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => $this->get_message('conflict'),

                ], Response::HTTP_CONFLICT); // 409 Conflict 


            }else{

                $tax = $request->all();
                $tax['uuid'] = Str::uuid();
                $tax['auth_id'] = Auth::user()->uuid;  

                $save_tax = Tax::create($tax);

                if($save_tax){ 

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


    public function edit_tax($uuid){

        
        try {
            
            $edit_tax = Tax::where('uuid', $uuid)->first();
            $edit_tax_translation = Tax::where('uuid', $uuid)->first();

            if($edit_tax)
            {

                return response()->json([

                    'status_code' => Response::HTTP_OK,
                    'data' => $edit_tax,

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


    public function update_tax(Request $request){
        
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
            $upd_tax = Tax::where('uuid', $uuid)->first();

            if (!$upd_tax) {

                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);

            }

             
            $upd_tax->name = $request->name; 
            $upd_tax->status = $request->status;
                        
            if($request->is_default == 1) {
                $upd_tax->is_default = $request->is_default;
            }

            if($request->is_admin_default == 1) {
                $upd_tax->is_admin_default = $request->is_admin_default;
            }

            $update_tax = $upd_tax->save();
            
            if($update_tax){
                
                if($request->is_default == 1) {
                    Tax::query()->update(['is_default' => 0]);
                    Tax::where('id', $upd_tax->id)->update(['is_default' => 1]);
                }

                if($request->is_admin_default == 1) {
                    Tax::query()->update(['is_admin_default' => 0]);
                    Tax::where('id', $upd_tax->id)->update(['is_admin_default' => 1]);
                }
                
                return response()->json([
                    
                    'status_code' => Response::HTTP_OK,
                    'message' => $this->get_message('update'),
                
                ], Response::HTTP_OK);

            }else{

                Tax::where('code', 'us')->update(['is_default' => 1]);
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


    public function delete_tax($uuid){

        try{

            $del_tax = Tax::where('uuid', $uuid)->first();
            
            if(!$del_tax)
            {
            
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                    
                ], Response::HTTP_NOT_FOUND);

         
            }else{

                $check_if_is_default = Tax::where('uuid', $uuid)->where('is_default', '1')->first();
                
                if($check_if_is_default)
                {

                    return response()->json([
                            
                        'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                        'message' => $this->get_message('can_not_delete'),
                    
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);

                }
                else{

                    $delete_tax = Tax::destroy($del_tax->id);

                    if($delete_tax){
                    
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


    public function get_tax(){


        try {  
                $menuUuid = request()->header('menu-uuid'); 
                $permissions = $this->permissionService->checkPermissions($menuUuid); 
                $get_all_taxes = Tax::orderBy('id', 'desc');
                if ($permissions['view']) {
                    if (!$permissions['viewglobal']) {
                        $get_all_taxes = $get_all_taxes->where('auth_id', Auth::user()->uuid);
                    }
                }else{
                    if (Auth::user()->hasPermission('viewglobal')) {
                        $get_all_taxes = $get_all_taxes;
                    } else {
                        return response()->json(['message' => 'You do not have permission to view this menu'], Response::HTTP_FORBIDDEN);
                    }
                }

            $get_all_taxes = $get_all_taxes->get();

            if($get_all_taxes){
                
                return response()->json([
                        
                    'status_code' => Response::HTTP_OK, 
                    'data' => $get_all_taxes,
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



    public function get_active_taxes(){  
        try {   
            $get_all_taxes = Tax::where('status' , '1')->orderBy('name', 'asc');   
            $get_all_taxes = $get_all_taxes->get(); 
            if($get_all_taxes){ 
                return response()->json([ 
                    'status_code' => Response::HTTP_OK, 
                    'data' => $get_all_taxes, 
                ], Response::HTTP_OK);  
            } 
        }catch (\Exception $e) { 
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        
        } 

            
        
    }

 
    public function updateTaxStatus(Request $request, string $id)
    {
        
        $request->validate([
            'status' => 'required|in:0,1', // Ensure status is either 0 or 1
        ]);

        try {
            // Find the Tax by UUID and active status
            $langage = Tax::where('uuid', $id)->first();
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