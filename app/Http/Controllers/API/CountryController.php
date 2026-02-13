<?php

namespace App\Http\Controllers\API;

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
use App\Models\Country;
use App\Models\Menu;
use App\Models\Permission_assign;
use App\Services\PermissionService;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Str;
use App\Traits\MessageTrait;


class CountryController extends Controller
{

    use MessageTrait;
    protected $permissionService;
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function add_country(Request $request){
        
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

            $check_if_already = Country::where('name', $request->name)->get();

            if(count($check_if_already) > 0){

                return response()->json([ 
                    'status_code' => Response::HTTP_CONFLICT,
                    'message' => $this->get_message('conflict'),

                ], Response::HTTP_CONFLICT); // 409 Conflict 


            }else{

                $country = $request->all();
                $country['uuid'] = Str::uuid();
                $country['auth_id'] = Auth::user()->uuid;  

                $save_country = Country::create($country);

                if($save_country){ 

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


    public function edit_country($uuid){

        
        try {
            
            $edit_country = Country::where('uuid', $uuid)->first();
            $edit_country_translation = Country::where('uuid', $uuid)->first();

            if($edit_country)
            {

                return response()->json([

                    'status_code' => Response::HTTP_OK,
                    'data' => $edit_country,

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


    public function update_country(Request $request){
        
        $validator = Validator::make($request->all(), [
             
            'name' => 'required|regex:/^[a-zA-Z0-9\s\-]+$/', 
            'is_default' => 'nullable|numeric|in:0,1',
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
            $upd_country = Country::where('uuid', $uuid)->first();

            if (!$upd_country) {

                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),

                ], Response::HTTP_NOT_FOUND);

            }

            $check_if_already = Country::where('name', $request->name)
            ->where('uuid', '!=', $uuid)
            ->count();

        if ($check_if_already > 0) {
            return response()->json([
                'status_code' => Response::HTTP_CONFLICT,
                'message' => $this->get_message('conflict'),
            ], Response::HTTP_CONFLICT);
        }
             
            $upd_country->name = $request->name; 
            $upd_country->code = $request->code; 
            $upd_country->image = $request->image; 
            $upd_country->status = $request->status;
                        
            if($request->is_default == 1) {
                $upd_country->is_default = $request->is_default;
            }

            if($request->is_admin_default == 1) {
                $upd_country->is_admin_default = $request->is_admin_default;
            }

            $update_country = $upd_country->save();
            
            if($update_country){
                
                if($request->is_default == 1) {
                    Country::query()->update(['is_default' => 0]);
                    Country::where('id', $upd_country->id)->update(['is_default' => 1]);
                }

                if($request->is_admin_default == 1) {
                    Country::query()->update(['is_admin_default' => 0]);
                    Country::where('id', $upd_country->id)->update(['is_admin_default' => 1]);
                }
                
                return response()->json([
                    
                    'status_code' => Response::HTTP_OK,
                    'message' => $this->get_message('update'),
                
                ], Response::HTTP_OK);

            }else{

                Country::where('code', 'us')->update(['is_default' => 1]);
                return response()->json([
                    
                    'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => $this->get_message('server_error'),

                ], Response::HTTP_INTERNAL_SERVER_ERROR);

            }


        }catch (\Exception $e) { 
            // Handle general exceptions
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $e->getMessage(),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }

        
    }


    public function delete_country($uuid){

        try{

            $del_country = Country::where('uuid', $uuid)->first();
            
            if(!$del_country)
            {
            
                return response()->json([

                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                    
                ], Response::HTTP_NOT_FOUND);

         
            }else{

                $check_if_is_default = Country::where('uuid', $uuid)->where('is_default', '1')->first();
                
                if($check_if_is_default)
                {

                    return response()->json([
                            
                        'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                        'message' => $this->get_message('can_not_delete'),
                    
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);

                }
                else{

                    $delete_country = Country::destroy($del_country->id);

                    if($delete_country){
                    
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


    public function get_country(){


        try {  
                $menuUuid = request()->header('menu-uuid'); 
                $permissions = $this->permissionService->checkPermissions($menuUuid); 
                $get_all_countries = Country::orderBy('id', 'desc');
                if ($permissions['view']) {
                    if (!$permissions['viewglobal']) {
                        $get_all_countries = $get_all_countries->where('auth_id', Auth::user()->uuid);
                    }
                }else{
                    if (Auth::user()->hasPermission('viewglobal')) {
                        $get_all_countries = $get_all_countries;
                    } else {
                        return response()->json(['message' => 'You do not have permission to view this menu'], Response::HTTP_FORBIDDEN);
                    }
                }

            $get_all_countries = $get_all_countries->get();

            if($get_all_countries){
                
                return response()->json([
                        
                    'status_code' => Response::HTTP_OK, 
                    'data' => $get_all_countries,
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



    public function get_active_countries(){  
        try {   
            $get_all_countries = Country::where('status' , '1')->orderBy('name', 'asc');   
            $get_all_countries = $get_all_countries->get(); 
            if($get_all_countries){ 
                return response()->json([ 
                    'status_code' => Response::HTTP_OK, 
                    'data' => $get_all_countries, 
                ], Response::HTTP_OK);  
            } 
        }catch (\Exception $e) { 
            return response()->json([

                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),

            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        
        } 

            
        
    }


    // get Menu by parent

    public function get_sort_menu()
    {
        // Fetch all parent menus with parent_id = 0
        $menus = Menu::where('parent_id', 0)->where('status' , '1')->orderBy('sort_id')->get();

        // Add children to each parent menu
        $menus = $this->addChildren($menus);

        // return response()->json($menus);

        return response()->json([
                        
            'status_code' => Response::HTTP_OK,
            'data' => $menus,
        
        ], Response::HTTP_OK);

    }

    private function addChildren($menus)
    {
        foreach ($menus as $menu) {
            // Fetch children menus
            $children = Menu::where('parent_id', $menu->id)->where('status' , '1')->orderBy('sort_id')->get();
            
            // Recursively add children
            if ($children->isNotEmpty()) {
                $menu->is_child = 1;
                $menu->children = $this->addChildren($children);
            } else {
                $menu->is_child = 0;
                unset($menu->children);  // Remove the 'children' key if no children exist
            }
        }

        return $menus;

    }


    public function updateCountryStatus(Request $request, string $id)
    {
        
        $request->validate([
            'status' => 'required|in:0,1', // Ensure status is either 0 or 1
        ]);

        try {
            // Find the Country by UUID and active status
            $langage = Country::where('uuid', $id)->first();
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