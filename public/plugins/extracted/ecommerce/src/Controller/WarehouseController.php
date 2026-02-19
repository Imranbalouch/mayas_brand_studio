<?php

namespace App\Http\Controllers\API\Ecommerce;

use Carbon\Carbon; 
use App\Models\User;
use App\Models\Language;
use App\Models\Ecommerce\Warehouse;
use Illuminate\Support\Str;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use App\Models\Ecommerce\WarehouseValues;
use Illuminate\Support\Facades\DB;
use App\Services\PermissionService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Ecommerce\WarehouseTranslations;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Models\Ecommerce\WarhouseLocationTranslation;
use Symfony\Component\HttpFoundation\Response;

class WarehouseController extends Controller
{
    use MessageTrait;
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }


    public function get_warehouse()
    {
        try {
            $menuUuid = request()->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);
    
           
            $query = Warehouse::with('warehouse_values')
                        ->select('id', 'uuid', 'auth_id', 'warehouse_name', 'prefix', 'status','featured'); //
           
            $query->orderBy('id', 'desc');
    
            // Check permissions
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $query->where('auth_id', Auth::user()->uuid);
                }
            } else {
                if (!Auth::user()->hasPermission('viewglobal')) {
                    return response()->json([
                        'message' => 'You do not have permission to view this menu',
                    ], Response::HTTP_FORBIDDEN);
                }
            }
    
            // Execute query
            $Warehouses = $query->get();
    
            // Return response
            return response()->json([
                'status_code' => 200,
                'permissions' => $permissions,
                'data' => $Warehouses,
            ], 200);
    
        } catch (\Exception $e) {
            // Return error response
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
   
 

    public function get_warehouse_values(Request $request)
    {
        try {
            $menuUuid = $request->header('menu-uuid');
            $permissions = $this->permissionService->checkPermissions($menuUuid);
            
            $warehouseUuid = $request->get('warehouse_uuid'); // Assuming warehouse UUID is passed in the request

            // Fetch warehouse values based on warehouse_id
            $query = WarehouseValues::with('warehouse')
                        ->select('uuid', 'warehouse_id', 'language_id', 'location_name', 'auth_id', 
                                 'location_address', 'contact_number', 'status','is_default', 'manager_id', 
                                 'featured', 'capacity', 'current_stock_level', 'created_at', 'updated_at');
            
            $query->orderBy('id', 'desc');
            
            // Check permissions
            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $query->where('auth_id', Auth::user()->uuid);
                }
            } else {
                if (!Auth::user()->hasPermission('viewglobal')) {
                    return response()->json([
                        'message' => 'You do not have permission to view this menu',
                    ], Response::HTTP_FORBIDDEN);
                }
            }

            // Check if warehouse UUID exists
            if ($warehouseUuid) {
                $query->where('warehouse_id', $warehouseUuid);
            }

            // Execute query
            $warehouseValues = $query->get();

            // Return response
            return response()->json([
                'status_code' => 200,
                'permissions' => $permissions,
                'data' => $warehouseValues,
            ], 200);
        } catch (\Exception $e) {
            // Return error response
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Server error, please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    
        public function add_warehouse(Request $request)
        {
            // Validate input
            $validator = Validator::make($request->all(), [
                'warehouse_name' => 'required|max:255',
                'prefix' => 'required|max:255',
            ]);
        
            if ($validator->fails()) {
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => strval($validator->errors())
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        
            try {
                // Check if the Warehouse already exists for the authenticated user
                $existingWarehouse = Warehouse::where('warehouse_name', $request->warehouse_name)
                    ->where('prefix', $request->prefix) // Ensure uniqueness by name and prefix
                    ->where('auth_id', Auth::user()->uuid)
                    ->first();
        
                if ($existingWarehouse) {
                    return response()->json([
                        'status_code' => 409,
                        'message' => 'Duplicate entry: The Warehouse already exists.',
                    ], 409);
                }
                
                // Create and save a new Warehouse
                $Warehouse = new Warehouse();
                $Warehouse->uuid = Str::uuid();
                $Warehouse->auth_id = Auth::user()->uuid;
                $Warehouse->warehouse_name = $request->warehouse_name;
                $Warehouse->prefix = $request->prefix; // Insert prefix
                $Warehouse->save();
        
                return response()->json([
                    'status_code' => 200,
                    'message' => $this->get_message('add'),
                ], 200);
        
            } catch (\Illuminate\Database\QueryException $e) {
                if ($e->errorInfo[1] == 1062) { // Error code for duplicate entry
                    return response()->json([
                        'status_code' => 409,
                        'message' => 'Duplicate entry: The Warehouse already exists.',
                    ], 409);
                }
        
                return response()->json([
                    'status_code' => 500,
                    'message' => $this->get_message('server_error'),
                ], 500);
        
            } catch (\Throwable $th) {
                \Log::error('Warehouse creation error: ' . $th->getMessage());
                \Log::error($th->getTraceAsString());
        
                return response()->json([
                    'status_code' => 500,
                    'message' => $this->get_message('server_error'),
                ], 500);
            }
        }
            


    public function edit_warehouse($uuid){

        try {
                
                $edit_Warehouse_by_id = Warehouse::where('uuid', $uuid)->first();

                // dd($edit_Warehouse_by_id);
            
                if(!$edit_Warehouse_by_id)
                {
                    return response()->json([
                        'status_code' => Response::HTTP_NOT_FOUND,
                        'message' => $this->get_message('not_found'),
                    ], Response::HTTP_NOT_FOUND);
                }
                
                $get_active_language = Language::where('status', '1')->get();
            
                $now = Carbon::now();
                $auth_id = Auth::user()->uuid;
            
                if(count($get_active_language) > 0){
            
                    foreach($get_active_language as $key => $language){
                        
                        $check_WarehouseTranslation = WarehouseTranslations::where('warehouse_id', $edit_Warehouse_by_id->id)
                        ->where('language_id', $language->id)
                        ->where('status', '1')->first();
                        // dd($check_WarehouseTranslation);
                        
                        if($check_WarehouseTranslation)
                        {
                            
                        
            
                        }
                        else{
            // dd( $language->id);
                            $save_WarehouseTranslation = WarehouseTranslations::insert([
                                ['uuid' => Str::uuid(), 'warehouse_id' => $edit_Warehouse_by_id->id, 'warehouse_name' => $edit_Warehouse_by_id->warehouse_name ,'prefix' => $edit_Warehouse_by_id->prefix , 'language_id' => $language->id , 'lang' => $language->app_language_code , 'auth_id' => $auth_id , 'created_at' => $now, 'updated_at' => $now],
                            ]);
            
                        }
            
            
                    }
            
            
                }
            
                $WarehouseTranslations = WarehouseTranslations::where('warehouse_id', $edit_Warehouse_by_id->id)
                ->where('warehouse_translation.status', '1')
                ->join('languages', 'warehouse_translation.language_id', '=', 'languages.id')
                ->select('languages.code as language_code', 'languages.name as language_name' , 'languages.flag as flag' , 'languages.rtl as dir', 'warehouse_translation.*')
                ->get();
            
                
                if ($edit_Warehouse_by_id) {
            
                    $edit_Warehouse_by_id->translations = $WarehouseTranslations;
            
                    return response()->json([
            
                        'status_code' => Response::HTTP_OK,
                        'data' => $edit_Warehouse_by_id,
            
                    ], Response::HTTP_OK);
            
            
                }else{
            
                    return response()->json([
            
                        'status_code' => Response::HTTP_NOT_FOUND,
                        'message' => $this->get_message('not_found'),
            
                    ], Response::HTTP_NOT_FOUND);
            
                }
            
            
        }catch(\Exception $e) { 
            // Handle general exceptions
            return response()->json([
        
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                // 'message' => $this->get_message('server_error'),
                'message' => $e->getMessage(),
        
            ], Response::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }
    
    }

    
    
    public function update_warehouse(Request $request)
    {
        $uuid = request()->header('uuid');

        try {
            // Find the Warehouse by uuid
            $Warehouse = Warehouse::where('uuid', $uuid)->first();

            // Return 404 if the Warehouse is not found
            if (!$Warehouse) {
                return response()->json([
                    'status_code' => 404,
                    'message' => $this->get_message('not_found'),
                ], 404);
            }
            //dd( $request->name);
            // Update Warehouse fields
            $Warehouse->warehouse_name = $request->warehouse_name;
            $Warehouse->prefix = $request->prefix;
            $Warehouse->save();

            $updatedTranslations = false;

            foreach ($request->all() as $key => $value) {
                if (strpos($key, 'name_') === 0) {
                    $languageCode = substr($key, 5);

                    $languageId = DB::table('languages')->where('code', $languageCode)->value('id');

                    if ($languageId) {
                        WarehouseTranslations::where('language_id', $languageId)
                        ->where('warehouse_id', $Warehouse->id)
                        ->update(['warehouse_name' => $value,]);

                        $updatedTranslations = true;
                    }
                }
                if (strpos($key, 'prefix_') === 0) {
                    $languageCode = substr($key, 7);  // Change from substr($key, 5) to substr($key, 7)
                
                    $languageId = DB::table('languages')->where('code', $languageCode)->value('id');
                    
                    if ($languageId) {
                        WarehouseTranslations::where('language_id', $languageId)
                        ->where('warehouse_id', $Warehouse->id)
                        ->update(['prefix' => $value]);
                
                        $updatedTranslations = true;
                    }
                }
            }
                // dd( $updatedTranslations);
                    if ($updatedTranslations) {
                $get_active_language = Language::where('status', '1')->where('is_default', '1')->first();
                $get_role_trans_by_def_lang = WarehouseTranslations::where('warehouse_id', $Warehouse->id)
                    ->where('language_id', $get_active_language->id)
                    ->first();

                if ($get_role_trans_by_def_lang) {
                    DB::table('warehouse')
                        ->where('id', $Warehouse->id)
                        ->update([
                            'warehouse_name' => $get_role_trans_by_def_lang->warehouse_name,
                        ]);
                }
            }
            


            return response()->json([
                'status_code' => 200,
                'message' => 'Warehouse has been updated',
            ], 200);

        } catch (\Throwable $th) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $th->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function delete_warehouse($uuid)
    {
        try {
            // Find the Warehouse by UUID
            $del_Warehouse = Warehouse::where('uuid', $uuid)->first();

            // If Warehouse is not found, return 404
            if (!$del_Warehouse) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            // Delete the Warehouse, translations, and values
            $deleted = $del_Warehouse->delete();
            if ($deleted) {
                // Delete related translations and values
                WarehouseTranslations::where('warehouse_id', $del_Warehouse->id)->delete();
                WarehouseValues::where('warehouse_id', $del_Warehouse->id)->delete();

                return response()->json([
                    'status_code' => Response::HTTP_OK,
                    'message' => $this->get_message('delete'),
                ], Response::HTTP_OK);
            }

            // If deletion fails, return a 500 response
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('delete_failed'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);

        } catch (\Exception $e) {
            // Handle exceptions
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function updatewarehouseStatus(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:0,1', // Ensure status is either 0 or 1
        ]);

        try {
            
            $Warehouse = Warehouse::where('uuid', $id)->first();

            if ($Warehouse) {

                // Update the status
                $Warehouse->status = $request->status;
                $Warehouse->save();

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
    public function updatewarehouseFeatured(Request $request, string $id)
    {
        $request->validate([
            'featured' => 'required|in:0,1', // Ensure status is either 0 or 1
        ]);

        try {
            
            $Warehouse = Warehouse::where('uuid', $id)->first();

            if ($Warehouse) {

                // Update the status
                $Warehouse->featured = $request->featured;
                $Warehouse->save();

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
    public function update_warehousevalue_Status(Request $request, string $id)
    {
        $request->validate([
            'status' => 'required|in:0,1', // Ensure status is either 0 or 1
        ]);

        try {
            
            $Warehouse = WarehouseValues::where('uuid', $id)->first();

            if ($Warehouse) {

                // Update the status
                $Warehouse->status = $request->status;
                $Warehouse->save();

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

    public function update_warehousevalue_isdefault(Request $request, string $id)
{
    $request->validate([
        'is_default' => 'required|in:1', 
    ]);

    try {
        // First, set all locations to is_default=0
        WarehouseValues::where('is_default', 1)->update(['is_default' => 0]);
        
        // Then set the selected location to is_default=1
        $Warehouse = WarehouseValues::where('uuid', $id)->first();

        if ($Warehouse) {
            $Warehouse->is_default = $request->is_default;
            $Warehouse->save();

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
    public function update_warehousevalue_featured(Request $request, string $id)
    {
        $request->validate([
            'featured' => 'required|in:0,1', // Ensure status is either 0 or 1
        ]);

        try {
            
            $Warehouse = WarehouseValues::where('uuid', $id)->first();

            if ($Warehouse) {

                // Update the status
                $Warehouse->featured = $request->featured;
                $Warehouse->save();

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


    public function getWarehouses()
    {
        try {
            $warehouses = Warehouse::where('status', 1)
                ->select('uuid', 'warehouse_name')
                ->get();
                
            return response()->json([
                'status_code' => Response::HTTP_OK,
                'data' => $warehouses
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error')
            ]);
        }
    }
    
    public function getManagers()
    {
        try {
            $managers = User::where('status', 1)
                ->select('uuid', DB::raw("CONCAT(first_name, ' ', last_name) as full_name"))
                ->get();
                
            return response()->json([
                'status_code' => Response::HTTP_OK,
                'data' => $managers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error')
            ]);
        }
    }

    // Add this new method to your controller
public function getManagerContactInfo($uuid)
{
    try {
        $manager = User::where('uuid', $uuid)
            ->where('status', 1)
            ->select('phone', 'email')
            ->first();

        if (!$manager) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Manager not found'
            ]);
        }

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'data' => [
                'phone' => $manager->phone,
                'email' => $manager->email
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $this->get_message('server_error')
        ]);
    }
}

// Modified store_warehouse_value method
public function store_warehouse_value(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'location_name' => 'required|string|max:255',
            'location_address' => 'nullable|string',
            'manager_uuid' => 'nullable',
            'warehouse_uuid' => 'required',
            'capacity' => 'nullable',
            'current_stock_level' => 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Get IDs from UUIDs
        $manager = User::where('uuid', $request->manager_uuid)->first();
        $warehouse = Warehouse::where('uuid', $request->warehouse_uuid)->first();

        if (!$manager || !$warehouse) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => $this->get_message('not_found'),
            ], Response::HTTP_NOT_FOUND);
        }

        // Create a new Warehouse value
        $warehouse_value = new WarehouseValues();
        $warehouse_value->uuid = Str::uuid();
        $warehouse_value->auth_id = Auth::user()->uuid;
        $warehouse_value->warehouse_id = $warehouse->id;
        $warehouse_value->manager_id = $manager->id;
        $warehouse_value->language_id = 1;
        $warehouse_value->location_name = $request->location_name;
        $warehouse_value->location_address = $request->location_address;
        $warehouse_value->capacity = $request->capacity;
        $warehouse_value->current_stock_level = $request->current_stock_level;
        $warehouse_value->country = $request->country;
        $warehouse_value->apartment = $request->apartment;
        $warehouse_value->city = $request->city;
        $warehouse_value->postal_code = $request->postal_code;
        $warehouse_value->phone = $request->phone;
        $warehouse_value->save();

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => $this->get_message('add'),
        ], Response::HTTP_OK);

    } catch (\Exception $e) {
        return response()->json([
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $this->get_message('server_error'),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

    

    public function edit_warehouse_value($id)
    {
        try {
            $get_Warehouse = Warehouse::where('uuid', $id)->first();

            if (!$get_Warehouse) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            $menuUuid = request()->header('menu-uuid');

            $permissions = $this->permissionService->checkPermissions($menuUuid);
           

            $query = WarehouseValues::where('warehouse_id', $get_Warehouse->id)->orderBy('id', 'desc');

            

            if ($permissions['view']) {
                if (!$permissions['viewglobal']) {
                    $query->where('auth_id', Auth::user()->uuid);
                }
            } else {
                if (!Auth::user()->hasPermission('viewglobal')) {
                    return response()->json([
                        'status_code' => Response::HTTP_FORBIDDEN,
                        'message' => 'You do not have permission to view this menu',
                    ], Response::HTTP_FORBIDDEN);
                }
            }

            $Warehouse_values = $query->get();

            $data_with_Warehouse_name = $Warehouse_values->map(function($Warehouse_value) use ($get_Warehouse) {
                $Warehouse_value->warehouse_name = $get_Warehouse->warehouse_name;
                return $Warehouse_value;
            });

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'permissions' => $permissions, // Include the Warehouse name at the top level
                'data' => $data_with_Warehouse_name,  // Modified data with the Warehouse name
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            // Handle exceptions and return an internal server error response
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
                'error' => $e->getMessage(), // Optional: Include for debugging in dev environments
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    

    public function edit_specific_warehouse_value($uuid)
    {
        
        try {
            // Fetch the Warehouse value using the ID
            $Warehouse_value = WarehouseValues::where('uuid', $uuid)->first();

            if (!$Warehouse_value) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            $warehouse_uuid = Warehouse::where('id', $Warehouse_value->warehouse_id)->pluck('uuid')->first();

            // Fetch manager UUID using manager_id
            $manager_uuid = User::where('id', $Warehouse_value->manager_id)->pluck('uuid')->first();
    
            // Add UUIDs to the response
            $Warehouse_value->warehouse_uuid = $warehouse_uuid;
            $Warehouse_value->manager_uuid = $manager_uuid;

            $get_active_language = Language::where('status', '1')->get();
            
            $now = Carbon::now();
            $auth_id = Auth::user()->uuid;
        
            if(count($get_active_language) > 0){
        
                foreach($get_active_language as $key => $language){
                    
                    $check_WarehouseTranslation = WarhouseLocationTranslation::where('location_id', $Warehouse_value->id)
                    ->where('language_id', $language->id)
                    ->where('status', '1')->first();
                    // dd($check_WarehouseTranslation);
                    
                    if($check_WarehouseTranslation)
                    {
                        
                    
        
                    }
                    else{
        // dd( $language->id);
                        $save_WarehouseTranslation = WarhouseLocationTranslation::insert([
                            ['uuid' => Str::uuid(), 'location_id' => $Warehouse_value->id, 'location_name' => $Warehouse_value->location_name ,'location_address' => $Warehouse_value->location_address , 'language_id' => $language->id , 'lang' => $language->app_language_code , 'auth_id' => $auth_id , 'created_at' => $now, 'updated_at' => $now],
                        ]);
        
                    }
        
        
                }
        
        
            }
        
            $WarehouseTranslations = WarhouseLocationTranslation::where('location_id', $Warehouse_value->id)
            ->where('warehouse_location_translations.status', '1')
            ->join('languages', 'warehouse_location_translations.language_id', '=', 'languages.id')
            ->select('languages.code as language_code', 'languages.name as language_name' , 'languages.flag as flag' , 'languages.rtl as dir', 'warehouse_location_translations.*')
            ->get();
        
            
            if ($Warehouse_value) {
        
                $Warehouse_value->translations = $WarehouseTranslations;
        
                return response()->json([
        
                    'status_code' => Response::HTTP_OK,
                    'data' => $Warehouse_value,
        
                ], Response::HTTP_OK);
        
        
            }else{
        
                return response()->json([
        
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
        
                ], Response::HTTP_NOT_FOUND);
        
            }
            // Return the fetched Warehouse value details
            return response()->json([
                'status_code' => Response::HTTP_OK,
                'data' => $Warehouse_value,
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    

    public function update_warehouse_value(Request $request)
{
    try {
        $uuid = $request->header('uuid');
        $Warehouse_value = WarehouseValues::where('uuid', $uuid)->first();

        if (!$Warehouse_value) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => $this->get_message('not_found'),
            ], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'location_name' => 'required|string|max:255',
            'location_address' => 'nullable|string',
            'manager_uuid' => 'nullable',
            'warehouse_uuid' => 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Get IDs from UUIDs
        $manager = User::where('uuid', $request->manager_uuid)->first();
        $warehouse = Warehouse::where('uuid', $request->warehouse_uuid)->first();

        if (!$manager || !$warehouse) {
            return response()->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => $this->get_message('not_found'),
            ], Response::HTTP_NOT_FOUND);
        }

        // Update warehouse values
        $Warehouse_value->location_name = $request->location_name;
        $Warehouse_value->location_address = $request->location_address;
        $Warehouse_value->country = $request->country;
        $Warehouse_value->apartment = $request->apartment;
        $Warehouse_value->city = $request->city;
        $Warehouse_value->postal_code = $request->postal_code;
        $Warehouse_value->phone = $request->phone;
        $Warehouse_value->manager_id = $manager->id;
        $Warehouse_value->warehouse_id = $warehouse->id;
        $Warehouse_value->save();

        $updatedTranslations = false;

        foreach ($request->all() as $key => $value) {
            if (strpos($key, 'name_') === 0) {
                $languageCode = substr($key, 5);

                $languageId = DB::table('languages')->where('code', $languageCode)->value('id');

                if ($languageId) {
                    WarhouseLocationTranslation::where('language_id', $languageId)
                    ->where('location_id', $Warehouse_value->id)
                    ->update(['location_name' => $value]);

                    $updatedTranslations = true;
                }
            }
            if (strpos($key, 'location_address_') === 0) {
                $languageCode = substr($key, 17);  // Correct substring extraction
            
                $languageId = DB::table('languages')->where('code', $languageCode)->value('id');
            
                if ($languageId) {
                    WarhouseLocationTranslation::where('language_id', $languageId)
                    ->where('location_id', $Warehouse_value->id)
                    ->update(['location_address' => $value]);
            
                    $updatedTranslations = true;
                }
            }
        }
            // dd( $updatedTranslations);
                if ($updatedTranslations) {
            $get_active_language = Language::where('status', '1')->where('is_default', '1')->first();
            $get_role_trans_by_def_lang = WarhouseLocationTranslation::where('location_id', $Warehouse_value->id)
                ->where('language_id', $get_active_language->id)
                ->first();

            if ($get_role_trans_by_def_lang) {
                DB::table('warehouse_locations')
                    ->where('id', $Warehouse_value->id)
                    ->update([
                        'location_name' => $get_role_trans_by_def_lang->location_name,
                    ]);
            }
        }

        return response()->json([
            'status_code' => Response::HTTP_OK,
            'message' => $this->get_message('update'),
        ], Response::HTTP_OK);

    } catch (\Exception $e) {
        return response()->json([
            'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
            'message' => $this->get_message('server_error'),
            'error' => $e->getMessage(), // Include for debugging (optional)
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}



    public function delete_warehouse_value($uuid)
    {
        try {

            $Warehouse_value = WarehouseValues::where('uuid', $uuid)->first();

            if (!$Warehouse_value) {
                return response()->json([
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => $this->get_message('not_found'),
                ], Response::HTTP_NOT_FOUND);
            }

            // Delete the Warehouse value
            $Warehouse_value->delete();

            return response()->json([
                'status_code' => Response::HTTP_OK,
                'message' => $this->get_message('delete'),
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function get_active_warehouses(){

        try{

            $get_all_active_Warehouses = Warehouse::where('status', '1')->get();

            if($get_all_active_Warehouses){
                
                return response()->json([
                    'status_code' => Response::HTTP_OK,
                    'data' => $get_all_active_Warehouses,
                ], Response::HTTP_OK);
    
            }

        }catch (\Exception $e) { 

            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);

        } 
        
    } 



    public function get_active_warehouse_locations() {
        try {
            // Get all active warehouse locations and eager load the associated Warehouse
            $get_all_active_locations = WarehouseValues::with('warehouse') // Eager load the warehouse relationship
                ->where('status', '1') // Filter by active status
                ->orderBy('warehouse_id', 'ASC') // Order by warehouse_id
                ->get();
    
            if ($get_all_active_locations->isNotEmpty()) {
                // Include the warehouse name in each location data
                $locations_with_warehouse_name = $get_all_active_locations->map(function($location) {
                    $location->warehouse_name = $location->warehouse->warehouse_name; // Access warehouse name from the relationship
                  // dd($location);
                    return $location;
                });
    
                return response()->json([
                    'status_code' => Response::HTTP_OK,
                    'data' => $locations_with_warehouse_name,
                ], Response::HTTP_OK);
            } else {
                return response()->json([
                    'status_code' => Response::HTTP_OK,
                    'message' => 'No active warehouse locations found.',
                ], Response::HTTP_OK);
            }
    
        } catch (\Exception $e) {
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
}
